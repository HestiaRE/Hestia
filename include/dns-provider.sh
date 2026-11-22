#!/bin/bash
# DNS provider accounts and their drivers (include/dns-providers/<provider>.sh). An account is a root-only file
# under $CONF_DIR/dns, outside every backup: after a restore it is set up again. What the provider answered is
# cached under /var/cache/hestia/dns-provider, recomputable and never backed up.

DNSP_CONF_DIR="$CONF_DIR/dns"
DNSP_CACHE=/var/cache/hestia/dns-provider
DNSP_DRIVERS="$HESTIA/include/dns-providers"

# A zone's records once per process where DNSP_MEMO_ON is set: a run over many sections of one zone that writes
# nothing itself. A write reads afresh, that read is its compare-and-set.
declare -A DNSP_MEMO

# Spelled out: under a German locale a-z matches umlauts.
DNSP_AN='abcdefghijklmnopqrstuvwxyz0123456789'

# Names an account may have: they become file names and panel ids.
dnsp_account_format_valid() {
	[[ "$1" =~ ^[$DNSP_AN][${DNSP_AN}_-]{0,31}$ ]] || check_result "$E_INVALID" "invalid account name: $1"
}

dnsp_driver_exists() {
	[[ "$1" =~ ^[$DNSP_AN]+$ ]] && [ -f "$DNSP_DRIVERS/$1.sh" ]
}

# The drivers there are, one per line.
dnsp_drivers() {
	local f
	for f in "$DNSP_DRIVERS"/*.sh; do
		[ -f "$f" ] || continue
		f=${f##*/}
		echo "${f%.sh}"
	done
}

# Account names, one per line.
dnsp_accounts() {
	local f
	for f in "$DNSP_CONF_DIR"/*.conf; do
		[ -f "$f" ] || continue
		f=${f##*/}
		echo "${f%.conf}"
	done
}

# dnsp_load ACCOUNT: its values into DNSP_* and its driver sourced. rc 1 when the account or the driver is gone.
dnsp_load() {
	local f="$DNSP_CONF_DIR/$1.conf" line
	[ -f "$f" ] || return 1
	line=$(tr '\n' ' ' < "$f")
	DNSP_ACCOUNT=$1
	DNSP_PROVIDER=$(dns_kv PROVIDER "$line")
	DNSP_KEY=$(dns_kv KEY "$line")
	DNSP_CONTEXT=$(dns_kv CONTEXT "$line")
	dnsp_driver_exists "$DNSP_PROVIDER" || return 1
	# shellcheck source=/usr/local/hestia/include/dns-providers/autodns.sh
	source "$DNSP_DRIVERS/$DNSP_PROVIDER.sh"
}

# dnsp_call VERB [ARG...]: the loaded account's driver verb; rc 1 key refused, 2 other failure (DNSP_ERROR). Call it
# with stdout redirected, never inside $(): DNSP_ERROR would die with the subshell.
dnsp_call() {
	DNSP_ERROR=''
	"dnsp_${DNSP_PROVIDER}_$1" "${@:2}"
}

# dnsp_throttle SECONDS: at least that long between two requests of one account, across processes. The lock is
# held through the wait, so the next process measures from this request.
dnsp_throttle() {
	local f="$DNSP_CACHE/$DNSP_ACCOUNT.last" last now wait fd
	dnsp_cache_dir
	exec {fd}>> "$f.lock"
	flock "$fd"
	now=$(date +%s%N)
	last=$(cat "$f" 2> /dev/null)
	if [[ "$last" =~ ^[0-9]+$ ]]; then
		wait=$(awk -v l="$last" -v n="$now" -v s="$1" 'BEGIN { w = s - (n - l) / 1e9; if (w > 0) printf "%.3f", w }')
		[ -n "$wait" ] && sleep "$wait"
	fi
	date +%s%N > "$f"
	exec {fd}>&-
}

dnsp_cache_dir() {
	[ -d "$DNSP_CACHE" ] || { mkdir -p "$DNSP_CACHE" && chmod 700 "$DNSP_CACHE"; }
}

# dnsp_write FILE: stdin into FILE, atomically and root-only.
dnsp_write() {
	local tmp
	dnsp_cache_dir
	tmp=$(mktemp "$1.XXXXXX") || return 1
	cat > "$tmp" && chmod 600 "$tmp" && mv -f "$tmp" "$1"
}

# dnsp_state_set ACCOUNT STATE [MESSAGE]: STATE is ok, refused (the key) or error (anything else).
dnsp_state_set() {
	printf '%s\t%s\t%s\n' "$2" "$(date +%s)" "${3//[$'\t\n']/ }" | dnsp_write "$DNSP_CACHE/$1.state"
}

# dnsp_state_of RC: the account state an rc of dnsp_call stands for.
dnsp_state_of() {
	case "$1" in
		0) echo ok ;;
		1) echo refused ;;
		*) echo error ;;
	esac
}

# dnsp_zones_refresh ACCOUNT: the account's zone list from the provider. A failure keeps the last list and says so
# in the account state; rc as dnsp_call.
dnsp_zones_refresh() {
	local tmp rc
	dnsp_load "$1" || return 2
	tmp=$(mktemp) || return 2
	dnsp_call zones > "$tmp"
	rc=$?
	if [ "$rc" = 0 ]; then
		sed '/^$/d' "$tmp" | dnsp_write "$DNSP_CACHE/$1.zones"
	fi
	rm -f "$tmp"
	dnsp_state_set "$1" "$(dnsp_state_of "$rc")" "$DNSP_ERROR"
	return "$rc"
}

# dnsp_zones_fresh: every account's zone list, refreshed when older than a day. Listing a zone never waits on it
# more than once a day per account, and after a failed request not again for ten minutes: every page of the DNS
# tab lists zones.
dnsp_zones_fresh() {
	local a f now state since
	now=$(date +%s)
	while IFS= read -r a; do
		[ -n "$a" ] || continue
		f="$DNSP_CACHE/$a.zones"
		[ -f "$f" ] && [ $((now - $(stat -c %Y "$f"))) -lt 86400 ] && continue
		# Emptied per account: a failed read keeps the previous account's values.
		state='' since=''
		[ -f "$DNSP_CACHE/$a.state" ] && IFS=$'\t' read -r state since _ < "$DNSP_CACHE/$a.state"
		[ "$state" != 'ok' ] && [[ "$since" =~ ^[0-9]+$ ]] && [ $((now - since)) -lt 600 ] && continue
		dnsp_zones_refresh "$a" > /dev/null 2>&1
	done <<< "$(dnsp_accounts)"
	return 0
}

# dnsp_status_set ZONE ACCOUNT DIFFERENCES: what the last check of a zone found, "error" when it could not run.
dnsp_status_set() {
	dnsp_locked status dnsp_status_put "$@"
}

dnsp_status_put() {
	local f="$DNSP_CACHE/status"
	{
		[ -f "$f" ] && awk -F '\t' -v z="$1" '$1 != z' "$f"
		printf '%s\t%s\t%s\t%s\n' "$1" "$2" "$3" "$(date +%s)"
	} | dnsp_write "$f"
}

# dnsp_status_drop ACCOUNT: the status of the account's zones forgotten. Under dnsp_locked status.
dnsp_status_drop() {
	local f="$DNSP_CACHE/status"
	[ -f "$f" ] || return 0
	awk -F '\t' -v a="$1" '$2 != a' "$f" | dnsp_write "$f"
}

# dnsp_locked NAME COMMAND...: COMMAND under the lock NAME, against a second process rewriting the same file.
dnsp_locked() {
	local fd rc
	dnsp_cache_dir
	exec {fd}>> "$DNSP_CACHE/$1.lock"
	flock "$fd"
	"${@:2}"
	rc=$?
	exec {fd}>&-
	return "$rc"
}

# dnsp_mask KEY: enough of a key to tell two apart, never enough to use one.
dnsp_mask() {
	local k="$1"
	if [ "${#k}" -le 12 ]; then
		echo '****'
	else
		echo "...${k: -4}"
	fi
}

# dnsp_check_zone ZONE [SECTION OBJECT OWNER [CONFIRM [MOVE]]]: the zone's desired rows (dns_zone_rows) against what
# its account holds, into DNSP_CHECK as dns-compare.php prints it, and the number of differing rows (orphans among them)
# into DNSP_DIFFS and the status cache; with SECTION OBJECT OWNER the writes for that section from the same read into
# DNSP_PLAN, CONFIRM and MOVE as dns-compare.php takes them. DNSP_ACTUAL keeps the provider's records, DNSP_REF names
# the zone at the provider. rc 3 when no account holds the zone, else as dnsp_call. Needs dns.sh.
dnsp_check_zone() {
	local zone="$1" account ref desired rc tmp apex=no owners
	DNSP_CHECK='' DNSP_DIFFS='' DNSP_PLAN='' DNSP_REF='' DNSP_ACTUAL=''
	dns_load
	read -r account ref <<< "$(dns_zone_account "$zone")"
	[ -n "$account" ] || return 3
	dnsp_load "$account" || return 3
	DNSP_REF=$ref
	desired=$(dns_zone_rows "$zone" | dns_sections)
	tmp=$(mktemp -d) || return 2
	if [ -n "${DNSP_MEMO[$zone]+set}" ]; then
		printf '%s\n' "${DNSP_MEMO[$zone]}" > "$tmp/actual"
		rc=0
	else
		dnsp_call records "$zone" "$ref" > "$tmp/actual"
		rc=$?
		dnsp_state_set "$account" "$(dnsp_state_of "$rc")" "$DNSP_ERROR"
		[ "$rc" = 0 ] && [ -n "${DNSP_MEMO_ON-}" ] && DNSP_MEMO[$zone]=$(cat "$tmp/actual")
	fi
	if [ "$rc" != 0 ]; then
		rm -rf "$tmp"
		dnsp_status_set "$zone" "$account" error
		return "$rc"
	fi
	DNSP_ACTUAL=$(cat "$tmp/actual")
	awk -F '\t' '$6 == "@" { f = 1 } END { exit !f }' <<< "$desired" && apex=yes
	printf '%s\n' "$desired" > "$tmp/desired"
	# Every owner's exceptions: the admin view holds the sections of all users in the zone.
	mapfile -t owners < <(cut -f 5 <<< "$desired" | sort -u | sed '/^$/d')
	dns_exception_rows "$zone" "${owners[@]}" > "$tmp/kept"
	dns_written_rows "$zone" > "$tmp/written"
	DNSP_CHECK=$("$HESTIA_PHP" "$HESTIA/include/dns-compare.php" "$zone" "$tmp/desired" "$tmp/actual" "$apex" \
		"$tmp/kept" "$tmp/written" < /dev/null)
	rc=$?
	if [ "$rc" = 0 ] && [ -n "${4-}" ]; then
		DNSP_PLAN=$("$HESTIA_PHP" "$HESTIA/include/dns-compare.php" "$zone" "$tmp/desired" "$tmp/actual" "$apex" \
			"$tmp/kept" "$tmp/written" "$2" "$3" "$4" "${5-}" ${6:+"$6"} < /dev/null)
		rc=$?
	fi
	rm -rf "$tmp"
	[ "$rc" = 0 ] || return 2
	DNSP_DIFFS=$(awk -F '\t' '$1 != "foreign" && $1 != "hidden" && $9 != "ok" && $9 != "exception" { n++ }
		END { print n + 0 }' <<< "$DNSP_CHECK")
	dnsp_status_set "$zone" "$account" "$DNSP_DIFFS"
}

# dnsp_written_gone ZONE: OWNER REL TYPE VALUE of each record noted as written that DNSP_ACTUAL no longer holds.
dnsp_written_gone() {
	local tmp rc
	tmp=$(mktemp -d) || return 2
	printf '%s\n' "$DNSP_ACTUAL" > "$tmp/actual"
	dns_written_rows "$1" > "$tmp/written"
	"$HESTIA_PHP" "$HESTIA/include/dns-compare.php" "$1" "$tmp/actual" "$tmp/written" < /dev/null
	rc=$?
	rm -rf "$tmp"
	return "$rc"
}

# DNS-01: the _acme-challenge TXT of a name, set through the account holding its zone. Each one set is noted in
# $DNSP_CACHE/acme (TIME PID ZONE ACCOUNT REF REL VALUE) until it is gone again: a run killed before its cleanup
# leaves its records to a later run, and records of the same name set by anyone else are never touched.

# dnsp_acme_zone NAME: "ZONE ACCOUNT REF" of the account zone NAME lies in, into DNSP_ACME_ZONE; rc 1 for none.
# Without any account it returns at once, so a box without DNS providers issues as it always did. Needs dns.sh.
dnsp_acme_zone() {
	local zone
	DNSP_ACME_ZONE=''
	compgen -G "$DNSP_CONF_DIR/*.conf" > /dev/null || return 1
	dnsp_zones_fresh
	[ -n "${DNS_ACCOUNT_ZONES+set}" ] || dns_account_zones_load
	zone=$(dns_account_zone "$1")
	[ -n "$zone" ] || return 1
	DNSP_ACME_ZONE="$zone $(dns_zone_account "$zone")"
}

# dnsp_acme_log NAME LINE: into the sync log beside the zone writes, as section acme with this process as its id.
dnsp_acme_log() {
	(
		umask 077
		printf '%s\t%s\tacme\t%s\t%s\t%s\t%s\n' "$(date '+%F %T')" "${DNSP_ACME_ZONE%% *}" "$1" "$DNSP_ACCOUNT" "$$" \
			"${2//$'\n'/ }" >> /var/log/hestia/dns-sync.log
	)
}

dnsp_acme_note() {
	local f="$DNSP_CACHE/acme"
	{
		[ -f "$f" ] && cat "$f"
		printf '%s\t%s\t%s\n' "$(date +%s)" "$$" "$1"
	} | dnsp_write "$f"
}

# dnsp_acme_set NAME VALUE: the TXT _acme-challenge.NAME with VALUE added beside whatever is there. rc as dnsp_call,
# 3 when no account holds the zone.
dnsp_acme_set() {
	local zone account ref rel tmp rc
	dnsp_acme_zone "$1" || return 3
	read -r zone account ref <<< "$DNSP_ACME_ZONE"
	dnsp_load "$account" || return 2
	rel=_acme-challenge
	[ "$1" = "$zone" ] || rel+=".${1%."$zone"}"
	# Noted before the write: a run killed in between leaves a note for a record that may exist, never the reverse.
	dnsp_locked acme dnsp_acme_note "$zone"$'\t'"$account"$'\t'"$ref"$'\t'"$rel"$'\t'"$2"
	tmp=$(mktemp) || return 2
	printf 'add\t%s\tTXT\t\t60\t%s\n' "$rel" "$2" > "$tmp"
	dnsp_acme_log "$1" "add"$'\t'"$rel"$'\t'"TXT"$'\t\t'"60"$'\t'"$2"
	dnsp_call change "$zone" "$ref" "$tmp" > /dev/null
	rc=$?
	rm -f "$tmp"
	[ "$rc" = 0 ] || dnsp_acme_log "$1" "result"$'\t'"refused: $DNSP_ERROR"
	return "$rc"
}

# dnsp_acme_wait NAME VALUE SECONDS: until every name server the parent delegates the zone to answers VALUE at
# _acme-challenge.NAME, asked directly: a resolver could hold an older answer. rc 1 when that takes longer.
dnsp_acme_wait() {
	local zone="${DNSP_ACME_ZONE%% *}" end ns n left
	end=$(($(date +%s) + $3))
	ns=$(dig +short NS "$zone" 2> /dev/null | tr '[:upper:]' '[:lower:]' | sed -n 's/\.$//; /^[a-z0-9.-]*$/p')
	[ -n "$ns" ] || return 1
	while :; do
		left=0
		for n in $ns; do
			dig +short +norecurse +time=3 +tries=1 @"$n" TXT "_acme-challenge.$1" 2> /dev/null | grep -qxF "\"$2\"" \
				|| left=$((left + 1))
		done
		[ "$left" = 0 ] && return 0
		[ "$(date +%s)" -lt "$end" ] || return 1
		sleep 2
	done
}

# dnsp_acme_clear PID|stale: the noted records of that run, or those older than an hour (no run takes that long),
# removed at their provider. A note goes once the zone no longer holds its record; a failed removal keeps it.
dnsp_acme_clear() {
	dnsp_locked acme dnsp_acme_clear_locked "$1"
}

dnsp_acme_clear_locked() {
	local f="$DNSP_CACHE/acme" now sel zone account ref keep='' tmp rows rc left
	[ -s "$f" ] || return 0
	now=$(date +%s)
	sel=$(awk -F '\t' -v w="$1" -v n="$now" 'NF && (w == "stale" ? n - $1 > 3600 : $2 == w)' "$f")
	[ -n "$sel" ] || return 0
	tmp=$(mktemp) || return 2
	while IFS=$'\t' read -r zone account ref; do
		[ -n "$zone" ] || continue
		DNSP_ACME_ZONE="$zone $account $ref"
		rows=$(awk -F '\t' -v z="$zone" -v a="$account" '$3 == z && $4 == a { print "rem\t" $6 "\tTXT\t\t\t" $7 }' \
			<<< "$sel" | sort -u)
		if ! dnsp_load "$account"; then
			keep+=$(awk -F '\t' -v z="$zone" -v a="$account" '$3 == z && $4 == a' <<< "$sel")$'\n'
			continue
		fi
		printf '%s\n' "$rows" > "$tmp"
		dnsp_acme_log "$zone" "$(cut -f 2,6 <<< "$rows" | paste -sd ' ')"
		dnsp_call change "$zone" "$ref" "$tmp" > /dev/null
		rc=$?
		# A rem matching nothing answers success as well, so the zone is read again.
		[ "$rc" = 0 ] && dnsp_call records "$zone" "$ref" > "$tmp" || rc=2
		if [ "$rc" != 0 ]; then
			dnsp_acme_log "$zone" "result"$'\t'"not removed: $DNSP_ERROR"
			keep+=$(awk -F '\t' -v z="$zone" -v a="$account" '$3 == z && $4 == a' <<< "$sel")$'\n'
			continue
		fi
		left=$(awk -F '\t' -v z="$zone" -v a="$account" 'NR == FNR { if ($2 == "TXT") h[$1 "\t" $7] = 1; next }
			$3 == z && $4 == a && (($6 "\t" $7) in h)' "$tmp" - <<< "$sel")
		[ -z "$left" ] \
			|| dnsp_acme_log "$zone" "result"$'\t'"not removed: $(cut -f 6 <<< "$left" | paste -sd ' ') still in the zone"
		keep+="$left"$'\n'
	done <<< "$(cut -f 3-5 <<< "$sel" | sort -u)"
	rm -f "$tmp"
	awk -F '\t' 'NR == FNR { if (NF) s[$0] = 1; next } !($0 in s)' <(printf '%s\n' "$sel") "$f" \
		| cat - <(sed '/^$/d' <<< "$keep") | dnsp_write "$f"
}
