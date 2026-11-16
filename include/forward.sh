#!/bin/bash
# The panel's forward: one rule in the mailbox's active Sieve script in its webmail's format (include/forward.php), so
# panel and webmail edit one entry, and a refused forward reports to the mailbox instead of to the sender. exim keeps
# a forward only where Sieve cannot hold it: :blackhole:, an own script, a template from before this. Never both, the
# target would get every mail twice. Record FWD and FWD_ONLY follow the script and travel in a backup.
# Needs main.sh.

# shellcheck source=/usr/local/hestia/include/vacation.sh
source "$HESTIA/include/vacation.sh"

FORWARD_PHP="$HESTIA/include/forward.php"
# Pigeonhole's sieve_max_redirects: with one more it runs none of them.
FORWARD_MAX=4

# FORMAT|RULE|TARGETS|KEEP, TARGETS comma-separated, KEEP yes|no. Not tabs: read collapses empty fields between them.
forward_read() { # USER DOMAIN_IDN ACCOUNT
	local js
	js=$("$HESTIA_PHP" "$FORWARD_PHP" state "$(vacation_script "$1" "$2" "$3")") || return 1
	jq -r '[.format, .rule, (.targets | join(",")), (if .keep then "yes" else "no" end)] | join("|")' <<< "$js"
}

# Before the template knows a forward in Sieve, exim drops every mail of a forward-only account before Sieve sees it,
# so such a box keeps its forwards in exim. Asked of the running config, once per process.
forward_exim_ready() {
	local r
	if [ -z "${FORWARD_EXIM_READY:-}" ]; then
		r=$(exim -bP router localuser_fwd_only 2> /dev/null)
		FORWARD_EXIM_READY=no
		[[ $r == *X-Spam-Status* ]] && FORWARD_EXIM_READY=yes
	fi
	[ "$FORWARD_EXIM_READY" = yes ]
}

# The rule as asked, or without TARGET none, read back from the active script before the caller drops exim's line.
forward_sieve_write() { # USER DOMAIN DOMAIN_IDN ACCOUNT KEEP [TARGET...]
	local user=$1 domain=$2 domain_idn=$3 account=$4 keep=$5 file fmt='' name out rc st want='none||yes'
	shift 5
	file=$(vacation_script "$user" "$domain_idn" "$account")
	[ "$#" -gt 0 ] || [ -n "$file" ] || return 0
	out=$(mktemp) || return 1
	if [ "$#" -gt 0 ]; then
		fmt=$(vacation_new_format "$user" "$domain")
		"$HESTIA_PHP" "$FORWARD_PHP" set "$fmt" "$file" "$keep" "$@" > "$out"
		want="ours|$(IFS=, && echo "$*")|$keep"
	else
		"$HESTIA_PHP" "$FORWARD_PHP" clear "$file" > "$out"
	fi
	rc=$?
	if [ "$rc" -eq 0 ] && { [ -z "$file" ] || ! cmp -s "$file" "$out"; }; then
		if [ -n "$file" ]; then
			name=$(basename "$file" .sieve)
		elif [ "$fmt" = 'tachyon' ]; then
			name='rainloop.user'
		else
			name='managesieve'
		fi
		vacation_put "$domain_idn" "$account" "$name" "$out" || rc=1
	fi
	rm -f "$out"
	[ "$rc" -eq 0 ] || return "$rc"
	st=$(forward_read "$user" "$domain_idn" "$account") || return 1
	[ "${st#*|}" = "$want" ] && return 0
	echo "Warning!: the forward of $account@$domain does not read back from its Sieve script as written" >&2
	return 1
}

forward_has_exim_line() { # USER DOMAIN DOMAIN_IDN ACCOUNT
	awk -v p="$4@$3:" 'index($0, p) == 1 { f = 1 } END { exit !f }' "$HOMEDIR/$1/conf/mail/$2/aliases" 2> /dev/null
}

forward_record() { # DOMAIN ACCOUNT FWD FWD_ONLY
	update_object_value "mail/$1" 'ACCOUNT' "$2" '$FWD' "$3"
	add_object_key "mail/$1" 'ACCOUNT' "$2" 'FWD_ONLY' 'MD5'
	update_object_value "mail/$1" 'ACCOUNT' "$2" '$FWD_ONLY' "$4"
}

# The record after the script, before a command changes the forward: a rule there is the forward, also one the
# webmail changed. A script without one while exim holds none means the customer took it out there, also by switching
# to an own script. Returns 3 for a script the panel does not write.
forward_sync() { # USER DOMAIN DOMAIN_IDN ACCOUNT
	local user=$1 domain=$2 domain_idn=$3 account=$4 st fmt rule targets keep only=''
	st=$(forward_read "$user" "$domain_idn" "$account") || return 1
	IFS='|' read -r fmt rule targets keep <<< "$st"
	[ "$rule" != 'changed' ] || return 3
	if [ "$rule" = 'none' ]; then
		if forward_has_exim_line "$user" "$domain" "$domain_idn" "$account"; then
			[ "$fmt" != 'custom' ] || return 3
			return 0
		fi
		[ -n "$(vacation_script "$user" "$domain_idn" "$account")" ] || return 0
	fi
	[ -n "$targets" ] && [ "$keep" = 'no' ] && only='yes'
	forward_record "$domain" "$account" "$targets" "$only"
	[ "$fmt" != 'custom' ] || return 3
}

# Before a backup: every record after its script, so a restore elsewhere writes the forward the customer has now.
forward_domain_sync() { # USER DOMAIN
	local user=$1 domain=$2 domain_idn acc
	local USER_DATA="$CONF_DIR/users/$1"
	format_domain_idn
	while read -r acc; do
		[ -n "$acc" ] || continue
		forward_sync "$user" "$domain" "$domain_idn" "$acc"
	done < <(sed -n "s/^ACCOUNT='\([^']*\)'.*/\1/p" "$USER_DATA/mail/$domain.conf" 2> /dev/null)
	return 0
}

# The commands' answer to what forward_sync or forward_apply returned.
forward_result() { # RC ACCOUNT@DOMAIN MESSAGE
	[ "$1" -ne 3 ] || check_result "$E_FORBIDEN" "$2 keeps its forward in a Sieve script the panel does not manage, change it there"
	[ "$1" -eq 0 ] || check_result "$E_UPDATE" "$3"
}

# Sieve can hold this forward on this box.
forward_sieve_fits() { # FWD
	[ -n "$1" ] && [[ ",$1," != *,:blackhole:,* ]] && vacation_sieve_on && forward_exim_ready
}

# Puts ACCOUNT's forward where it belongs: the rule written and read back, and only then exim's line gone; for exim,
# the rule gone first. FWD empty takes both out. Returns 3 for a script the panel does not write, nothing changed.
forward_apply() { # USER DOMAIN DOMAIN_IDN ACCOUNT FWD FWD_ONLY
	local user=$1 domain=$2 domain_idn=$3 account=$4 fwd=$5 only=$6 keep=yes st fmt rule
	local dir="$HOMEDIR/$1/conf/mail/$2"
	local -a list
	st=$(forward_read "$user" "$domain_idn" "$account") || return 1
	IFS='|' read -r fmt rule _ _ <<< "$st"
	[ "$fmt" != 'custom' ] && [ "$rule" != 'changed' ] || return 3
	[ "$only" = 'yes' ] && keep=no
	IFS=, read -ra list <<< "$fwd"
	if forward_sieve_fits "$fwd"; then
		forward_sieve_write "$user" "$domain" "$domain_idn" "$account" "$keep" "${list[@]}" || return $?
		remove_line_by_prefix "$dir/aliases" "$account@$domain_idn:"
	else
		forward_sieve_write "$user" "$domain" "$domain_idn" "$account" yes || return $?
		remove_line_by_prefix "$dir/aliases" "$account@$domain_idn:"
		[ -z "$fwd" ] || echo "$account@$domain_idn:$fwd" >> "$dir/aliases"
	fi
	remove_exact_line "$dir/fwd_only" "$account"
	[ -z "$fwd" ] || [ "$only" != 'yes' ] || echo "$account" >> "$dir/fwd_only"
}

# The rebuild's half for one account, appended to its fresh aliases and fwd_only files. The script is the truth where
# it holds a rule. Without one the record writes it only where nothing can have been taken out in the webmail: no
# script, or RECORD set because exim held this forward before or the domain arrives by a restore. Else the customer
# took it out there, also by switching to an own script, and the record follows; an own script keeps exim's line only
# under RECORD. A rule that cannot be written leaves the forward in exim, never nowhere.
forward_rebuild() { # USER DOMAIN DOMAIN_IDN ACCOUNT FWD FWD_ONLY RECORD
	local user=$1 domain=$2 domain_idn=$3 account=$4 fwd=$5 only=$6 st fmt='' rule='none' targets keep exim=''
	local dir="$HOMEDIR/$1/conf/mail/$2" w=yes
	local -a list
	st=$(forward_read "$user" "$domain_idn" "$account") && IFS='|' read -r fmt rule targets keep <<< "$st"
	if [ "$rule" = 'ours' ]; then
		fwd=$targets only=''
		[ -n "$fwd" ] && [ "$keep" = 'no' ] && only='yes'
		forward_record "$domain" "$account" "$fwd" "$only"
	elif [ "$rule" = 'none' ] && [ -n "$fwd" ]; then
		exim=yes
		if [ -z "$fmt" ]; then
			# Unreadable: the record stays, exim keeps only a line it held.
			[ "$7" = 'yes' ] || exim=''
		elif [ "$fmt" = 'custom' ] || forward_sieve_fits "$fwd"; then
			if [ "$7" != 'yes' ] && [ -n "$(vacation_script "$user" "$domain_idn" "$account")" ]; then
				fwd='' only='' exim=''
				forward_record "$domain" "$account" '' ''
			elif [ "$fmt" != 'custom' ]; then
				IFS=, read -ra list <<< "$fwd"
				[ "$only" = 'yes' ] && w=no
				if forward_sieve_write "$user" "$domain" "$domain_idn" "$account" "$w" "${list[@]}"; then
					exim=''
				else
					echo "Warning!: the forward of $account@$domain stays in exim, its Sieve rule could not be written" >&2
				fi
			fi
		fi
	fi
	[ -z "$exim" ] || echo "$account@$domain_idn:$fwd" >> "$dir/aliases"
	[ -z "$fwd" ] || [ "$only" != 'yes' ] || echo "$account" >> "$dir/fwd_only"
}
