#!/bin/bash
# DNS provider accounts and their drivers (include/dns-providers/<provider>.sh). An account is a root-only file
# under $CONF_DIR/dns, outside every backup: after a restore it is set up again. What the provider answered is
# cached under /var/cache/hestia/dns-provider, recomputable and never backed up.

DNSP_CONF_DIR="$CONF_DIR/dns"
DNSP_CACHE=/var/cache/hestia/dns-provider
DNSP_DRIVERS="$HESTIA/include/dns-providers"

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

# dnsp_check_zone ZONE: the zone's desired rows (dns_zone_rows) against what its account holds, into DNSP_CHECK as
# dns-compare.php prints it, and the number of differing rows into DNSP_DIFFS and the status cache. rc 3 when no
# account holds the zone, else as dnsp_call. Needs dns.sh.
dnsp_check_zone() {
	local zone="$1" account ref desired rc tmp apex=no
	DNSP_CHECK='' DNSP_DIFFS=''
	dns_load
	read -r account ref <<< "$(dns_zone_account "$zone")"
	[ -n "$account" ] || return 3
	dnsp_load "$account" || return 3
	desired=$(dns_zone_rows "$zone" | dns_sections)
	tmp=$(mktemp -d) || return 2
	dnsp_call records "$zone" "$ref" > "$tmp/actual"
	rc=$?
	dnsp_state_set "$account" "$(dnsp_state_of "$rc")" "$DNSP_ERROR"
	if [ "$rc" != 0 ]; then
		rm -rf "$tmp"
		dnsp_status_set "$zone" "$account" error
		return "$rc"
	fi
	awk -F '\t' '$6 == "@" { f = 1 } END { exit !f }' <<< "$desired" && apex=yes
	printf '%s\n' "$desired" > "$tmp/desired"
	DNSP_CHECK=$("$HESTIA_PHP" "$HESTIA/include/dns-compare.php" "$zone" "$tmp/desired" "$tmp/actual" "$apex" < /dev/null)
	rc=$?
	rm -rf "$tmp"
	[ "$rc" = 0 ] || return 2
	DNSP_DIFFS=$(awk -F '\t' '$1 != "foreign" && $1 != "hidden" && $9 != "ok" { n++ } END { print n + 0 }' <<< "$DNSP_CHECK")
	dnsp_status_set "$zone" "$account" "$DNSP_DIFFS"
}
