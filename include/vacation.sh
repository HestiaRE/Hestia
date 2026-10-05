#!/bin/bash
# The panel's autoreply: exim's autoreply.<account>.msg without Sieve, otherwise the vacation rule in the mailbox's
# active script in its webmail's format (include/vacation.php), so panel and webmail edit one entry and one answers.
# Record AUTOREPLY and $USER_DATA/mail/<account>@<domain>.msg stay either way: both travel in a HestiaCP backup.
# Needs main.sh.

# shellcheck source=/usr/local/hestia/include/sieve.sh
source "$HESTIA/include/sieve.sh"

VACATION_PHP="$HESTIA/include/vacation.php"

vacation_sieve_on() { webmail_sieve_on; }

# The customer can point the dovecot.sieve link anywhere; root reads and rewrites only their own script in the box.
vacation_script() { # USER DOMAIN_IDN ACCOUNT
	local box="$HOMEDIR/$1/mail/$2/$3" f
	[ -e "$box/dovecot.sieve" ] || return 0
	f=$(readlink -f "$box/dovecot.sieve")
	[[ "$f" == "$box/"* ]] && [ -f "$f" ] && [ "$(stat -c %U "$f")" = "$1" ] && echo "$f"
	return 0
}

vacation_state() { # USER DOMAIN_IDN ACCOUNT -> JSON
	"$HESTIA_PHP" "$VACATION_PHP" state "$(vacation_script "$1" "$2" "$3")"
}

# A mailbox without a script gets its rule in the format of the webmail its domain uses.
vacation_new_format() { # USER DOMAIN
	local w
	w=$(record_field "$(grep -F "DOMAIN='$2'" "$CONF_DIR/users/$1/mail.conf" 2> /dev/null | head -n1)" WEBMAIL)
	[ "$w" = 'tachyon' ] && echo 'tachyon' || echo 'roundcube'
}

# Through doveadm: it refuses a script that does not compile and writes as the mailbox owner.
vacation_put() { # DOMAIN_IDN ACCOUNT NAME FILE
	doveadm sieve put -u "$2@$1" -a "$3" < "$4"
}

vacation_enable() { # USER DOMAIN DOMAIN_IDN ACCOUNT MSGFILE
	local file name fmt out rc
	file=$(vacation_script "$1" "$3" "$4")
	fmt=$(vacation_new_format "$1" "$2")
	out=$(mktemp) || return 1
	"$HESTIA_PHP" "$VACATION_PHP" enable "$fmt" "$file" "$5" > "$out"
	rc=$?
	if [ "$rc" -eq 0 ]; then
		if [ -n "$file" ]; then
			name=$(basename "$file" .sieve)
		elif [ "$fmt" = 'tachyon' ]; then
			name='rainloop.user'
		else
			name='managesieve'
		fi
		vacation_put "$3" "$4" "$name" "$out" || rc=$?
	fi
	rm -f "$out"
	return "$rc"
}

vacation_disable() { # USER DOMAIN_IDN ACCOUNT
	local file out rc
	file=$(vacation_script "$1" "$2" "$3")
	[ -n "$file" ] || return 0
	out=$(mktemp) || return 1
	"$HESTIA_PHP" "$VACATION_PHP" disable "$file" > "$out"
	rc=$?
	if [ "$rc" -eq 0 ] && ! cmp -s "$file" "$out"; then
		vacation_put "$2" "$3" "$(basename "$file" .sieve)" "$out" || rc=$?
	fi
	rm -f "$out"
	return "$rc"
}

# The file exim's autoreply router looks for; keep in step with share/exim/exim4.conf.template.
vacation_exim_file() { # USER DOMAIN ACCOUNT
	echo "$HOMEDIR/$1/conf/mail/$2/autoreply.$3.msg"
}

vacation_exim_write() { # USER DOMAIN ACCOUNT MSGFILE
	local f
	f=$(vacation_exim_file "$1" "$2" "$3")
	cp -f "$4" "$f" && chown "${MAIL_USER:-Debian-exim}:mail" "$f" && chmod 660 "$f"
}

# Live answer for the listers: "ACCOUNT yes|no" per line, one php run for all of them.
vacation_live() { # USER DOMAIN_IDN ACCOUNT...
	local user="$1" domain_idn="$2" acc
	shift 2
	local files=()
	for acc in "$@"; do
		files+=("$(vacation_script "$user" "$domain_idn" "$acc")")
	done
	[ "$#" -gt 0 ] || return 0
	paste -d' ' <(printf '%s\n' "$@") <("$HESTIA_PHP" "$VACATION_PHP" enabled "${files[@]}")
}

# Keyed on the exim file, not on the record: the record stays 'yes' when the customer switches the vacation off in
# the webmail, and a later rebuild must not bring it back.
vacation_domain_to_sieve() { # USER DOMAIN
	local user="$1" domain="$2" domain_idn f acc rc=0
	format_domain_idn
	for f in "$HOMEDIR/$user/conf/mail/$domain"/autoreply.*.msg; do
		[ -e "$f" ] || continue
		acc=${f##*/autoreply.}
		acc=${acc%.msg}
		if ! vacation_enable "$user" "$domain" "$domain_idn" "$acc" "$f"; then
			echo "Warning!: autoreply of $acc@$domain not moved to Sieve, exim keeps answering" >&2
			rc=1
			continue
		fi
		rm -f "$f"
	done
	return "$rc"
}

# Record, panel copy and an exim file in DIR catch up with the script, which the customer may have
# changed in the webmail. Prints each account whose vacation is on.
vacation_domain_sync() { # USER DOMAIN DIR
	local user="$1" domain="$2" dir="$3" domain_idn acc st msg rc=0
	local USER_DATA="$CONF_DIR/users/$1"
	format_domain_idn
	msg=$(mktemp) || return 1
	while read -r acc; do
		[ -n "$acc" ] || continue
		st=$("$HESTIA_PHP" "$VACATION_PHP" message "$(vacation_script "$user" "$domain_idn" "$acc")" "$msg")
		if [ "$st" = 'yes' ]; then
			cp -f "$msg" "$USER_DATA/mail/$acc@$domain.msg" \
				&& chmod 660 "$USER_DATA/mail/$acc@$domain.msg" \
				&& cp -f "$msg" "$dir/autoreply.$acc.msg" \
				&& chown "${MAIL_USER:-Debian-exim}:mail" "$dir/autoreply.$acc.msg" \
				&& chmod 660 "$dir/autoreply.$acc.msg" \
				&& update_object_value "mail/$domain" 'ACCOUNT' "$acc" '$AUTOREPLY' 'yes' \
				&& echo "$acc" || rc=1
		elif [ "$st" = 'no' ]; then
			update_object_value "mail/$domain" 'ACCOUNT' "$acc" '$AUTOREPLY' 'no'
		else
			echo "Warning!: autoreply of $acc@$domain is in a Sieve script the panel does not manage, left there" >&2
		fi
	done < <(sed -n "s/^ACCOUNT='\([^']*\)'.*/\1/p" "$USER_DATA/mail/$domain.conf" 2> /dev/null)
	rm -f "$msg"
	return "$rc"
}

# Back to exim, and the rule is switched off so a returning Sieve addon does not answer twice: the
# exim file is what brings it back then.
vacation_domain_to_exim() { # USER DOMAIN
	local user="$1" domain="$2" domain_idn acc accs rc=0
	format_domain_idn
	accs=$(vacation_domain_sync "$user" "$domain" "$HOMEDIR/$user/conf/mail/$domain") || rc=1
	for acc in $accs; do
		vacation_disable "$user" "$domain_idn" "$acc" || rc=1
	done
	return "$rc"
}

# Every mail domain on the box, for the Sieve addon arriving (to_sieve) or leaving (to_exim).
vacation_all() { # sieve|exim
	local u d rc=0
	for u in $("$BIN/h-list-users" list); do
		for d in $("$BIN/h-list-mail-domains" "$u" plain | cut -f 1); do
			"vacation_domain_to_$1" "$u" "$d" || rc=1
		done
	done
	return "$rc"
}
