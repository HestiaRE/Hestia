#!/usr/bin/env bash

#===========================================================================#
# #
# Hestia Control Panel - Core Function Library #
# #
#===========================================================================#

# Source conf function for correct variable initialisation
# Names a record must never bind: the value is validated everywhere, the NAME was not, so a restored
# user.conf with PATH=/tmp/x rebound PATH in the root shell reading it.
# NOT here, because each is a legitimate key somewhere: ROOT_USER, REPO, BACKUP, BACKUP_TEMP.
SOURCE_CONF_PROTECTED="PATH IFS ENV BASH_ENV BASHOPTS SHELLOPTS CDPATH GLOBIGNORE PROMPT_COMMAND
PS1 PS2 PS3 PS4 LD_PRELOAD LD_LIBRARY_PATH LD_AUDIT HISTFILE BASH_XTRACEFD FUNCNAME
HESTIA HESTIA_PHP BIN SBIN CONF_DIR HOMEDIR USER_DATA SENDMAIL SOURCE_CONF_PROTECTED"

# Two sinks, two floors: source_conf reads CONFIGS where these three are honest keys,
# parse_object_kv_list reads RECORDS where they are not fields at all. BACKUP is absent because it IS
# a record field (the archive name).
RECORD_ONLY_PROTECTED="ROOT_USER REPO BACKUP_TEMP"

# Storage encoding for record VALUES: record_line_valid refuses ' " ` and \ inside a value. One
# encoder and one decoder, so a fifth writer cannot know half the set. Before, four writers and
# thirteen readers each carried their own, and a record its own checker rejects was reachable with a
# cron command as ordinary as `echo "hallo"`.
# Known limit: a value literally containing a placeholder decodes to the character it stands for.
record_value_encode() {
	local _v="$1"
	_v="${_v//\\/%backslash%}"
	_v="${_v//\'/%quote%}"
	_v="${_v//\"/%dquote%}"
	_v="${_v//\`/%backtick%}"
	printf '%s' "$_v"
}

record_value_decode() {
	local _v="$1"
	_v="${_v//%quote%/\'}"
	_v="${_v//%dquote%/\"}"
	_v="${_v//%backtick%/\`}"
	_v="${_v//%backslash%/\\}"
	printf '%s' "$_v"
}

is_protected_key() {
	case " ${SOURCE_CONF_PROTECTED//$'\n'/ } " in *" $1 "*) return 0 ;; esac
	return 1
}

# An archived record is hostile input. Only keys the registry knows reach the instance: the floor
# above stops the shell names, this stops the rest - including names that are legitimate in another
# file and therefore cannot be in the floor (ROOT_USER in hestia.conf, REPO in restic.conf). The
# archive itself is not modified; a dropped key is named on stderr, and a filter that keeps nothing
# fails instead of installing an empty record.
copy_record_filtered() {
	local _src="$1" _dst="$2" _type="$3" _allow _lhs _line _kept=0 _tmp
	command -v syshealth_known_keys > /dev/null 2>&1 || return 1
	_allow=" $(syshealth_known_keys "$_type") " || return 1
	[ -n "${_allow// /}" ] || return 1
	[ -f "$_src" ] || return 1
	_tmp=$(mktemp "$_dst.XXXXXX") || return 1
	while IFS= read -r _line || [ -n "$_line" ]; do
		[ -n "${_line// /}" ] || continue
		_lhs=${_line%%=*}
		case "$_allow" in
			*" $_lhs "*)
				printf '%s\n' "$_line" >> "$_tmp"
				_kept=$((_kept + 1))
				;;
			*) echo "Warning: dropping unknown key '$_lhs' from the archived $_type record" >&2 ;;
		esac
	done < "$_src"
	if [ "$_kept" -eq 0 ]; then
		rm -f "$_tmp"
		return 1
	fi
	chmod 660 "$_tmp" && mv -f "$_tmp" "$_dst"
}

# The system key registry readers (share/hestia/sys-keys.json): class and default per hestia.conf key,
# the schema guard, nothing else. Loaded here because the panel's config emitter and the repair both
# sit behind main.sh.
# shellcheck source=/usr/local/hestia/include/sysreg.sh
source "$HESTIA/include/sysreg.sh"

source_conf() {
	while IFS='= ' read -r lhs rhs; do
		if [[ ! $lhs =~ ^\ *# && -n $lhs ]]; then
			# A config key is a plain identifier, never an array subscript. Rejecting anything else stops
			# `declare -g $lhs=` from evaluating a command substitution smuggled into a `key[$(...)]`
			# subscript (the GHSA-xffx-jj33-p2px class) - a hardening of the sink that covers every caller,
			# not just the one command that reads an attacker-supplied file.
			[[ $lhs =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] || continue
			# The identifier check above accepts PATH and BIN: those are valid identifiers, just
			# not valid config keys. Loud, because a record carrying one is an attack.
			if is_protected_key "$lhs"; then
				echo "Warning: $1 tries to bind the protected name $lhs - ignored" >&2
				continue
			fi
			rhs="${rhs%%^\#*}" # Del in line right comments
			rhs="${rhs%%*( )}" # Del trailing spaces
			rhs="${rhs%\'*}"   # Del opening string quotes
			rhs="${rhs#\'*}"   # Del closing string quotes
			declare -g $lhs="$rhs"
		fi
	done < $1
}

# Read a value from the release manifest (share/manifest.json). jq is a prereq
# (install.sh), so it is available at install- and run-time. Empty on miss.
manifest_get() {
	jq -r "$1" "$HESTIA/share/manifest.json" 2> /dev/null
}

if [ -z "$user" ]; then
	if [ -z "$ROOT_USER" ]; then
		if [ -z "$HESTIA" ]; then
			# shellcheck source=/etc/hestia/hestia.env
			source /etc/hestia/hestia.env
		fi
		source_conf "$HESTIA/conf/hestia.conf" # load config file
	fi
	user="$ROOT_USER"
fi

# Internal variables
# BACKUP and BACKUP_GZIP used to sit here as defaults. They are operator keys: the registry carries
# their default and the repair writes it, so a copy here is a second home that drifts the moment the
# registry changes, and it silently answered for the operator on every box. HOMEDIR is the
# opposite case and moved down to the constants: no registry entry, no writer, never in hestia.conf,
# and source_conf refuses to bind it (SOURCE_CONF_PROTECTED).
BACKUP_DISK_LIMIT=95
BACKUP_LA_LIMIT=$(grep -c '^processor' /proc/cpuinfo)
RRD_STEP=300
HOMEDIR='/home'
BIN=$HESTIA/bin
# sbin holds what the sudo wildcard on bin/* must NOT reach: the panel-PHP wrappers and the
# lifecycle commands. Its own anchor so a caller cannot silently keep pointing at bin/.
SBIN=$HESTIA/sbin
# instance config root; fallback covers installs whose hestia.env predates the var
CONF_DIR="${CONF_DIR:-/etc/hestia}"
# Panel certificate: out of the install root, which h-update-hestia replaces wholesale. Not under
# CONF_DIR either: that is 0700 and caddy, exim and proftpd all read this as non-root.
HESTIA_SSL="/etc/ssl/hestia"
# Exim looks a certificate up by interpolating the SNI name into a path, so it needs a directory
# whose filenames ARE the SNI names. Kept flat and exact rather than stripping a "mail." prefix:
# a domain literally called mail.kunde.de would strip to another customer's domain.
MAIL_SNI_DIR="/etc/exim4/ssl"
HESTIA_BACKUP="/root/hst_backups/$(date +%d%m%Y%H%M)"
# CLI helpers run through the hestia-php wrapper (panel PHP version indirection)
HESTIA_PHP="$SBIN/hestia-php"
USER_DATA=$CONF_DIR/users/$user
# Selectable vhost templates (the customer picks these), the non-selectable ones served
# from share/, and the FPM pool profiles. PHPTPL is its own anchor rather than
# $WEBTPL/$WEB_BACKEND: WEB_BACKEND is a config VALUE, and tying a directory name to it
# means renaming the directory would have to rename the value everywhere it is compared.
WEBTPL=$HESTIA/templates
SHARETPL=$HESTIA/share/web
PHPTPL=$HESTIA/templates/php
# webmail templates live per app at $HESTIA/share/$WEB_SYSTEM/webmail/ (no MAILTPL var)
RRD=$HESTIA/web/rrd
SENDMAIL="$HESTIA/web/inc/mail-wrapper.php"
HESTIA_GIT_REPO="https://raw.githubusercontent.com/hestiacp/hestiacp"
HESTIA_THEMES="$HESTIA/web/css/src/themes"
HESTIA_THEMES_CUSTOM="$HESTIA/web/css/src/themes/custom"
SCRIPT="$(basename $0)"
CHECK_RESULT_CALLBACK=""

# Return codes
OK=0
E_ARGS=1
E_INVALID=2
E_NOTEXIST=3
E_EXISTS=4
E_SUSPENDED=5
E_UNSUSPENDED=6
E_INUSE=7
E_LIMIT=8
E_PASSWORD=9
E_FORBIDEN=10
E_DISABLED=11
E_PARSING=12
E_DISK=13
E_LA=14
E_CONNECT=15
E_FTP=16
E_DB=17
E_RRD=18
E_UPDATE=19
E_RESTART=20
E_BACKUP=22

# Detect operating system
detect_os() {
	if [ -e "/etc/os-release" ]; then
		get_os_type=$(grep "^ID=" /etc/os-release | cut -f 2 -d '=')
		if [ "$get_os_type" = "ubuntu" ]; then
			if [ -e '/usr/bin/lsb_release' ]; then
				OS_VERSION="$(lsb_release -s -r)"
				OS_TYPE='Ubuntu'
			fi
		elif [ "$get_os_type" = "debian" ]; then
			OS_TYPE='Debian'
			OS_VERSION=$(grep -o "[0-9]\{1,2\}" /etc/debian_version | head -n1)
		fi
	else
		OS_TYPE="Unsupported OS"
		OS_VERSION="Unknown"
	fi
}

# Generate time stamp
new_timestamp() {
	time_n_date=$(date +'%T %F')
	time=$(echo "$time_n_date" | cut -f 1 -d \ )
	date=$(echo "$time_n_date" | cut -f 2 -d \ )
}

# Event string for logger
ARGS=("$@")
for ((I = 1; I <= $#; I++)); do
	if [[ "$HIDE" != "$I" ]]; then
		ARGUMENTS="$ARGUMENTS '${ARGS[${I} - 1]}'"
	else
		ARGUMENTS="$ARGUMENTS '******'"
	fi
done

# Log event function
log_event() {
	if [ -z "$time" ]; then
		LOG_TIME="$(date +'%F %T') $(basename $0)"
	else
		LOG_TIME="$date $time $(basename $0)"
	fi
	if [ "$1" -eq 0 ]; then
		echo "$LOG_TIME $2" >> $HESTIA/log/system.log
	else
		echo "$LOG_TIME $2 [Error $1]" >> $HESTIA/log/error.log
	fi
}

# Log user history
log_history() {
	message=${1//\'/\´} # Avoid single quotes broken the log
	evt_level=${2:-$event_level}
	log_user=${3-$user}
	evt_category=${4:-$event_category}

	# Set default event level and category if not specified
	if [ -z "$evt_level" ]; then
		evt_level="Info"
	fi
	if [ -z "$evt_category" ]; then
		evt_category="System"
	fi

	# Log system events to system log file
	if [ "$log_user" = "system" ]; then
		log=$HESTIA/log/activity.log
	else
		if ! $BIN/h-list-user "$log_user" > /dev/null; then
			return $E_NOTEXIST
		fi
		log=$CONF_DIR/users/$log_user/history.log
	fi
	touch $log

	if [ '300' -lt "$(wc -l $log | cut -f 1 -d ' ')" ]; then
		tail -n 250 $log > $log.moved
		mv -f $log.moved $log
		chmod 660 $log
	fi

	if [ -z "$date" ]; then
		time_n_date=$(date +'%T %F')
		time=$(echo "$time_n_date" | cut -f 1 -d \ )
		date=$(echo "$time_n_date" | cut -f 2 -d \ )
	fi

	curr_str=$(tail -n1 $log | grep "ID=" --text | cut -f2 -d \')
	id="$((curr_str + 1))"
	echo "ID='$id' DATE='$date' TIME='$time' LEVEL='$evt_level' CATEGORY='$evt_category' MESSAGE='$message'" >> $log
}

# Result checker
check_result() {
	# An unusable code silently disarms the check below.
	case "${1:-}" in
		'' | *[!0-9]*)
			echo "Error: $(basename "$0") called check_result with an unusable exit code" \
				"'${1:-}'${2:+ while reporting: $2}" >&2
			echo "$(date +'%F %T') $(basename "$0") unusable check_result code '${1:-}': ${2:-}" \
				>> "$HESTIA/log/error.log" 2> /dev/null
			exit "$E_INVALID"
			;;
	esac
	if [ $1 -ne 0 ]; then
		local err_code="${3:-$1}"
		if [[ -n "$CHECK_RESULT_CALLBACK" && "$(type -t "$CHECK_RESULT_CALLBACK")" == 'function' ]]; then
			$CHECK_RESULT_CALLBACK "$err_code" "$2"
		else
			echo "Error: $2"
			log_event "$err_code" "$ARGUMENTS"
		fi

		exit $err_code
	fi
}

# Argument list checker
check_args() {
	if [ "$1" -gt "$2" ]; then
		echo "Usage: $(basename $0) $3"
		check_result "$E_ARGS" "not enought arguments" > /dev/null
	fi
}

# Define version check function
version_ge() { test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1" -o -n "$1" -a "$1" = "$2"; }

# Subsystem checker
is_system_enabled() {
	if [ -z "$1" ] || [ "$1" = no ]; then
		check_result "$E_DISABLED" "$2 is not enabled"
	fi
}

# Customer web and the webmail front are separate concepts: on a mailfront
# box WEB_SYSTEM is empty - customer web absent, every web command's
# is_system_enabled guard keys on exactly that - while nginx still fronts the
# webmail vhosts, carried by WEBMAIL_FRONT. Everywhere the webmail chain used
# $WEB_SYSTEM it means this value; on every other model the two are identical.
webmail_front() { echo "${WEBMAIL_FRONT:-$WEB_SYSTEM}"; }

# User package check
# package_key_value KEY - what this customer's package file says for KEY, or nothing. The same file
# h-add-user seeds a new user.conf from, so it is not a second opinion. KEY reaches a sed pattern,
# so it comes from the registry, never from input.
package_key_value() {
	local _pkg
	[ -f "$USER_DATA/user.conf" ] || return 1
	_pkg=$(sed -n "s/^PACKAGE='\(.*\)'$/\1/p" "$USER_DATA/user.conf" | head -n1)
	[ -n "$_pkg" ] || return 1
	[ -f "$CONF_DIR/packages/$_pkg.pkg" ] || return 1
	sed -n "s/^$1='\(.*\)'$/\1/p" "$CONF_DIR/packages/$_pkg.pkg" | head -n1
}

is_package_full() {
	case "$1" in
		WEB_DOMAINS) used=$(wc -l $USER_DATA/web.conf) ;;
		WEB_ALIASES) used=$(echo $aliases | tr ',' '\n' | wc -l) ;;
		MAIL_DOMAINS) used=$(wc -l $USER_DATA/mail.conf) ;;
		MAIL_ACCOUNTS) used=$(wc -l $USER_DATA/mail/$domain.conf) ;;
		DATABASES) used=$(wc -l $USER_DATA/db.conf) ;;
		CRON_JOBS) used=$(wc -l $USER_DATA/cron.conf) ;;
	esac
	used=$(echo "$used" | cut -f 1 -d \ )
	limit=$(grep "^$1=" $USER_DATA/user.conf | cut -f 2 -d \')
	# An absent limit is a broken record, not a limit of zero - but [[ n -ge "" ]] reads it as one
	# and refuses every request with a message blaming the customer's package. Ask the package file
	# instead, which is where the value came from.
	case "$limit" in
		'unlimited') ;;
		'' | *[!0-9]*)
			limit=$(package_key_value "$1")
			case "$limit" in
				'unlimited') return 0 ;;
				'' | *[!0-9]*)
					# Not enforcing is the safe direction for the customer and an invisible one
					# for the operator: one typo in a package would silently unlimit everyone on
					# it. So it is said.
					echo "Warning!: neither user.conf nor the package gives a usable $1 limit - not enforcing one"
					return 0
					;;
			esac
			;;
	esac
	if [ "$1" = WEB_ALIASES ]; then
		# Used is always calculated with the new alias added
		if [ "$limit" != 'unlimited' ] && [[ "$used" -gt "$limit" ]]; then
			check_result "$E_LIMIT" "$1 limit is reached :: upgrade user package"
		fi
	else
		if [ "$limit" != 'unlimited' ] && [[ "$used" -ge "$limit" ]]; then
			check_result "$E_LIMIT" "$1 limit is reached :: upgrade user package"
		fi
	fi
}

# User owner for reseller plugin
get_user_owner() {
	if [ -z "$RESELLER_KEY" ]; then
		owner="$ROOT_USER"
	else
		owner=$(grep "^OWNER" $USER_DATA/user.conf | cut -f 2 -d \')
		if [ -z "$owner" ]; then
			owner="$ROOT_USER"
		fi
	fi
}

# github.com has no AAAA, so on a v6-only box the two upstream repos below are reachable only
# through the release mirror, which serves their assets under /wp-cli and /tachyon. Third literal
# beside install.sh and sbin/h-update-hestia (both run before or without this tree); a smoke check
# measures the three against each other. Every caller verifies the payload against a manifest pin,
# which is what makes a second host acceptable here at all.
HESTIA_RELEASE_MIRROR="https://dl.hestiare.com"

# Bounded fetch with the mirror as the second try. $1 = route below the mirror, $2 = the github.com
# release URL, $3 = destination. Bounded because wget defaults to 20 tries at a 900s read timeout,
# so a host that drops SYNs costs ~45 minutes before the first error surfaces. wget -O leaves a
# 0-byte file behind on failure, which a plain [ -f ] check accepts.
fetch_release_asset() {
	local route="$1" url="$2" dest="$3" rest
	if wget "$url" --timeout=30 --tries=3 --retry-connrefused --quiet -O "$dest" && [ -s "$dest" ]; then
		return 0
	fi
	rest="${url#*/releases/download/}"
	{ [ -n "$HESTIA_RELEASE_MIRROR" ] && [ "$rest" != "$url" ]; } || return 1
	wget "$HESTIA_RELEASE_MIRROR/$route/$rest" --timeout=30 --tries=3 --retry-connrefused --quiet -O "$dest" || return 1
	[ -s "$dest" ]
}

# Fetch the wp-cli phar pinned in share/manifest.json (version + sha256) into $1. The phar runs
# as every customer, so never a moving or unverified source. No partial file on failure; the
# caller owns chown of the destination.
fetch_wp_cli_phar() {
	local dest="$1" ver sum tmp
	ver=$(manifest_get '.software_versions.wp_cli.version')
	sum=$(manifest_get '.software_versions.wp_cli.sha256')
	{ [ -n "$ver" ] && [ "$ver" != "null" ] && [ -n "$sum" ] && [ "$sum" != "null" ]; } || return 1
	tmp=$(mktemp -t wp-cli.XXXXXX.phar) || return 1
	if ! fetch_release_asset wp-cli \
		"https://github.com/wp-cli/wp-cli/releases/download/v${ver}/wp-cli-${ver}.phar" "$tmp" \
		|| ! echo "$sum  $tmp" | sha256sum -c --quiet - 2> /dev/null; then
		rm -f "$tmp"
		return 1
	fi
	mv -f "$tmp" "$dest" || {
		rm -f "$tmp"
		return 1
	}
	chmod 755 "$dest"
}

# The webmail sqlite backend needs its driver in the PANEL pool. Heals the one known ordering
# gap (package landed after hestia-php-confd built the conf.d) by rebuilding once and
# reloading; a genuinely missing package stays the caller's hard failure.
ensure_panel_sqlite_driver() {
	ls /etc/php/hestia/fpm/conf.d/*-pdo_sqlite.ini > /dev/null 2>&1 && return 0
	"$HESTIA/sbin/hestia-php-confd" > /dev/null 2>&1
	systemctl reload hestia-php 2> /dev/null || systemctl restart hestia-php 2> /dev/null || true
	ls /etc/php/hestia/fpm/conf.d/*-pdo_sqlite.ini > /dev/null 2>&1
}

# The webmail clients this codebase ships. ONE list on purpose: the write-path value
# domain (h-add-mail-domain-webmail) reads it, and h-check-sys-smoke asserts every shipped
# webmail template's client appears here - a third client added by template alone would
# otherwise have its records normalized to 'disabled' while everything else works.
WEBMAIL_KNOWN_CLIENTS='roundcube tachyon'

# Random password generator. The default matrix (A-Za-z0-9) is sed-replacement-safe (no /, &, \)
# and callers rely on that when substituting into configs - a custom matrix must keep the property.
# A random draw misses a requested class often enough to matter: 16 characters out of A-Za-z0-9
# carry no digit in about 6 % of draws, and a database with a password policy rejects exactly those.
# The filter can also return fewer characters than asked for, which used to pass silently.
generate_password() {
	local matrix="${1:-A-Za-z0-9}" length="${2:-16}" attempt=0 pass='' classes=0
	[[ "$matrix" == *A-Z* ]] && classes=$((classes + 1))
	[[ "$matrix" == *a-z* ]] && classes=$((classes + 1))
	[[ "$matrix" == *0-9* ]] && classes=$((classes + 1))
	# The only request that cannot be met is fewer characters than classes, and that is knowable
	# before the first draw. Caught here, the loop below cannot run out.
	if [ "$length" -lt "$classes" ]; then
		check_result "$E_INVALID" "cannot fit $classes character classes into $length characters"
	fi
	while [ "$attempt" -lt 100 ]; do
		attempt=$((attempt + 1))
		pass=$(head -c 4096 /dev/urandom | tr -dc "$matrix" | head -c "$length")
		[ "${#pass}" -eq "$length" ] || continue
		[[ "$matrix" == *A-Z* && ! "$pass" =~ [[:upper:]] ]] && continue
		[[ "$matrix" == *a-z* && ! "$pass" =~ [[:lower:]] ]] && continue
		[[ "$matrix" == *0-9* && ! "$pass" =~ [[:digit:]] ]] && continue
		printf "%s" "$pass"
		return 0
	done
	# Unreachable after the check above. If it were ever reached, printing the last candidate beats
	# printing nothing: a caller that passes an empty password on could create an account without one.
	echo "Warning: no password matching '$matrix' in $length characters after $attempt tries" >&2
	printf "%s" "$pass"
	return 1
}

# Package existence check
is_package_valid() {
	if [ -z $1 ]; then
		if [ ! -e "$CONF_DIR/packages/$package.pkg" ]; then
			check_result "$E_NOTEXIST" "package $package doesn't exist"
		fi
	else
		if [ ! -e "$CONF_DIR/packages/$1.pkg" ]; then
			check_result "$E_NOTEXIST" "package $1 doesn't exist"
		fi
	fi

}

is_package_new() {
	if [ -e "$CONF_DIR/packages/$1.pkg" ]; then
		echo "Error: package $1 already exists."
		log_event "$E_EXISTS" "$ARGUMENTS"
		exit "$E_EXISTS"
	fi
}

# Validate system type
is_type_valid() {
	if [ -z "$(echo $1 | grep -w $2)" ]; then
		check_result "$E_INVALID" "$2 type is invalid"
	fi
}

# Check user backup settings
is_backup_enabled() {
	BACKUPS=$(grep "^BACKUPS=" $USER_DATA/user.conf | cut -f2 -d \')
	if [ -z "$BACKUPS" ] || [[ "$BACKUPS" -le '0' ]]; then
		check_result "$E_DISABLED" "user backup is disabled"
	fi
}

# The normalised repository base, rc=1 when unset. Pure, so the smoke guard can ask too.
restic_repo_base() {
	local _repo
	[ -f "$HESTIA/conf/restic.conf" ] || return 1
	_repo=$(sed -n "s/^REPO='\([^']*\)'.*/\1/p" "$HESTIA/conf/restic.conf" | head -1)
	[ -n "$_repo" ] || return 1
	case "$_repo" in */ | *:) ;; *) _repo="$_repo/" ;; esac
	echo "$_repo"
}

# Is the repository base a local directory? Only an absolute path is. Our addon takes a local path
# or rclone:remote:path, and restic itself also speaks sftp:, rest: and s3: - none of those is a
# path, so a glob over one stays literal and an rm -rf on it removes nothing while the caller
# believes it deleted. A relative path is not local either: it resolves against whatever directory
# the command happened to start in. Anything unrecognised counts as remote on purpose - for a
# destructive command that is the safe direction to be wrong in.
restic_repo_is_local() {
	case "$1" in /*) return 0 ;; esac
	return 1
}

is_restic_repo_configured() {
	REPO=$(restic_repo_base) \
		|| check_result "$E_NOTEXIST" "no restic repository configured - run h-add-backup-host-restic"
}

is_backup_mode_restic() {
	local _mode
	_mode=$(sed -n "s/^.*BACKUPS_MODE='\([^']*\)'.*/\1/p" "$USER_DATA/user.conf" | head -1)
	if [ "${_mode:-full}" != 'restic' ]; then
		check_result "$E_DISABLED" "$user is not in restic backup mode (BACKUPS_MODE='${_mode:-full}')"
	fi
}

# Check user backup settings
is_backup_scheduled() {
	if [ -e "$CONF_DIR/queue/backup.pipe" ]; then
		check_q=$(grep " $user " $CONF_DIR/queue/backup.pipe | grep $1)
		if [ -n "$check_q" ]; then
			check_result "$E_EXISTS" "$1 is already scheduled"
		fi
	fi
}

# resolve an object's.conf path: absolute (global objects) as-is, else under $USER_DATA
_object_conf() {
	case "$1" in
		/*) printf '%s' "$1.conf" ;;
		*) printf '%s' "$USER_DATA/$1.conf" ;;
	esac
}

# Check if object is new
is_object_new() {
	if [ $2 = 'USER' ]; then
		if [ -d "$USER_DATA" ]; then
			object="OK"
		fi
	else
		object=$(grep -F "$2='$3'" "$(_object_conf "$1")")
	fi
	if [ -n "$object" ]; then
		check_result "$E_EXISTS" "$2=$3 already exists"
	fi
}

# Check if object is valid
is_object_valid() {
	if [ $2 = 'USER' ]; then
		tstpath="$(readlink -f "$CONF_DIR/users/$3")"
		if [ "$(dirname "$tstpath")" != "$(readlink -f "$CONF_DIR/users")" ] || [ ! -d "$CONF_DIR/users/$3" ]; then
			check_result "$E_NOTEXIST" "$1 $3 doesn't exist"
		fi
	else
		# -F: the value is a domain or account and a dot in it matches any character - with
		# a.b.com and aXb.com on one box, a lookup on one finds the other's record
		object=$(grep -F "$2='$3'" "$(_object_conf "$1")")
		if [ -z "$object" ]; then
			arg1=$(basename $1)
			arg2=$(echo $2 | tr '[:upper:]' '[:lower:]')
			check_result "$E_NOTEXIST" "$arg1 $arg2 $3 doesn't exist"
		fi
	fi
}

# Check if a object string with key values pairs has the correct format and load it afterwards
parse_object_kv_list_non_eval() {
	local str objkv obj_key obj_val out rc
	local -a pairs=()

	str=${*//$'\n'/ }

	# mapfile, not `for x in $(...)`: word splitting also globs, so a value holding a * came back as
	# a filename.
	# Captured first, status checked: a process substitution's exit status is not observable, and with
	# perl missing it returned 0 having set nothing.
	out=$(printf '%s\n' "$str" | perl -n -e "while(/\b([a-zA-Z]+[\w]*)='(.*?)'(\s|\$)/g) {print \$1.'='.\$2 . \"\n\" }")
	rc=$?
	if [ "$rc" -ne 0 ]; then
		check_result "$E_PARSING" "record could not be tokenised [$str]"
	fi
	mapfile -t pairs <<< "$out"

	for objkv in "${pairs[@]}"; do
		[ -n "$objkv" ] || continue

		if ! [[ "$objkv" =~ ^([[:alnum:]][_[:alnum:]]{0,64}[[:alnum:]])=(\'?[^\']+?\'?)?$ ]]; then
			check_result "$E_INVALID" "Invalid key value format [$objkv]"
		fi

		obj_key=${objkv%%=*} # strip everything after first  '=' char
		obj_val=${objkv#*=}  # strip everything before first '=' char
		declare -g $obj_key="$obj_val"

	done
}

# Check if a object string with key values pairs has the correct format and load it afterwards
_parse_object_kv_list_php() {
	local str check_status validated_output
	local IFS=$'\n'

	str=${@//$'\n'/ }
	validated_output=$(
		SOURCE_CONF_PROTECTED="$SOURCE_CONF_PROTECTED $RECORD_ONLY_PROTECTED" "$HESTIA_PHP" -- "$str" << 'EOPHP'
<?php
declare(strict_types=1);

// Supported syntax:
//   KEY1='value1' KEY2='value with spaces' KEY3=''
//   KEY=value
//   KEY=value\ with\ spaces
//   KEY=ab\ c'de f'ghi
//   KEY=
//   KEY= KEY2=value
//   KEY='abc'def
//   KEY=abc''
//   KEY=\'
//   KEY=\\
//
// Notes:
// - Key names must match: [a-zA-Z][a-zA-Z0-9_]*
// - Inside single quotes, every character is literal except the closing single quote.
// - Outside single quotes, backslash escapes the next character.
function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(2);
}

if ($argc < 2) {
    fail('Usage: php '.$argv[0].' "KEY=\'value\' KEY2=value"');
}

$unparsed = ($argv[1]);
$result = [];

while ($unparsed !== '') {
    $unparsed = ltrim($unparsed);
    if ($unparsed === '') {
        break;
    }

    $key_name_extracted = preg_match('/^([a-zA-Z][a-zA-Z0-9_]*)=/', $unparsed, $m);

    if ($key_name_extracted !== 1) {
		// example: `eval(code)=123`
        fail('Invalid key value format. Could not extract key name from: ' . $unparsed);
    }

    $key_name = $m[1];

    // Same protection as source_conf: the form of the key was checked, the NAME was not, so a
    // record could bind PATH or BIN in the calling shell (#807). Hard failure - our own records
    // never carry these, so one that does is an attack, not a version skew.
    $protected = getenv('SOURCE_CONF_PROTECTED') ?: '';
    $protected = preg_split('/\s+/', trim($protected), -1, PREG_SPLIT_NO_EMPTY);
    if ($protected === []) {
        // An empty list would silently protect nothing.
        fail('Protected name list is empty - refusing to parse.');
    }
    if (in_array($key_name, $protected, true)) {
        fail('Refused key name: ' . $key_name . ' is a protected shell or path name.');
    }

    $unparsed = substr($unparsed, strlen($m[0]));

    $key_value = '';
    $is_in_quote = false;

    while (true) {
        if ($is_in_quote) {
            // Inside single quotes, only a single quote is special.
            $pos = strpos($unparsed, "'");

            if ($pos === false) {
				// example: `KEY='value` (missing closing quote)
                fail('Invalid key value format. No closing quote for key: ' . $key_name . ' in: ' . $unparsed);
            }

            $key_value .= substr($unparsed, 0, $pos);
            $unparsed = substr($unparsed, $pos + 1);
            $is_in_quote = false;

            if ($unparsed === '') {
				// parsing complete
                break;
            }
            continue;
        }

        // Outside single quotes, whitespace, single quote, and backslash are special.
        $match = preg_match('/\s|\'|\\\\/u', $unparsed, $m, PREG_OFFSET_CAPTURE);
        if ($match !== 1) {
            // No more special chars; rest of string is the value.
            $key_value .= $unparsed;
            $unparsed = '';
            break;
        }

        $matched_char = $m[0][0];
        $pos = $m[0][1];

        // Add everything before the matched special character.
        $key_value .= substr($unparsed, 0, $pos);
		$unparsed = substr($unparsed, $pos + strlen($matched_char));

        if ($matched_char === "'") {
            if ($unparsed === '') {
				// example: `KEY='` - missing closing quote. nearest legal alternative is `KEY=''`
                fail('Invalid key value format. No closing quote for key: ' . $key_name);
            }
            $is_in_quote = true;
            continue;
        }
        if ($matched_char === '\\') {
			if($unparsed === '') {
				// example: `KEY=foo\`
                fail('Invalid key value format. Escape character cannot be the last character in value for key: ' . $key_name . ' in: ' . $unparsed);
            }

            // Backslash escapes the next character verbatim
			$next_char = mb_substr($unparsed, 0, 1, 'UTF-8'); // remember multi-byte unicode support, æøåÆØÅ
			$key_value .= $next_char;
			$unparsed = substr($unparsed, strlen($next_char));
            continue;
        }

        // Matched whitespace: end of this key-value pair.
        $unparsed = ltrim($unparsed);
        break;
    }
    if (array_key_exists($key_name, $result)) {
        $msg = 'Warning: Duplicate key name: ' . $key_name . '. ';

        if ($result[$key_name] === $key_value) {
            $msg .= 'value is identical.';
        } else {
            $msg .= var_export([
                'old_value' => $result[$key_name],
                'new_value' => $key_value,
            ], true);
        }
        fwrite(STDERR, $msg . PHP_EOL);
    }
    $result[$key_name] = $key_value;
}
$assignments = [];
foreach ($result as $k => $v) {
    $v_quoted = "'" . strtr($v, ["'" => "'\\''"]) . "'";
    $assignments[] = "$k=$v_quoted";
}
echo implode(' ', $assignments);
EOPHP
	)
	check_status=$?
	check_result "$check_status" "Invalid object format: ${str}" "$E_INVALID"
	eval "$validated_output"
}

# QUOTE THE ARGUMENT. Both parsers are safe on what they receive, but an unquoted `$(grep...)` is
# split and GLOBBED by the shell before either of them sees a character: a record holding MIN='*'
# picks up any file named MIN='...' in the working directory and the parsed value becomes that
# filename. Reachable wherever a customer chooses the directory a h-* runs in. Unquoted also
# collapses runs of whitespace inside a value, so TPL='two spaces' comes back with one.
parse_object_kv_list() {
	_parse_object_kv_list_php "$@"
}

# Check if object is supended
is_object_suspended() {
	if [ "$2" = 'USER' ]; then
		spnd=$(grep "SUSPENDED='yes'" "$(_object_conf "$1")")
	else
		spnd=$(grep -F "$2='$3'" "$(_object_conf "$1")" | grep "SUSPENDED='yes'")
	fi
	if [ -z "$spnd" ]; then
		check_result "$E_UNSUSPENDED" "$(basename $1) $3 is not suspended"
	fi
}

# Check if object is unsupended
is_object_unsuspended() {
	if [ $2 = 'USER' ]; then
		spnd=$(grep "SUSPENDED='yes'" "$(_object_conf "$1")")
	else
		spnd=$(grep -F "$2='$3'" "$(_object_conf "$1")" | grep "SUSPENDED='yes'")
	fi
	if [ -n "$spnd" ]; then
		check_result "$E_SUSPENDED" "$(basename $1) $3 is suspended"
	fi
}

# Check if object value is empty
is_object_value_empty() {
	str=$(grep -F "$2='$3'" "$(_object_conf "$1")")
	parse_object_kv_list "$str"
	local varname="${4#\$}"
	value="${!varname}"
	if [ -n "$value" ] && [ "$value" != 'no' ]; then
		check_result "$E_EXISTS" "${4//$/}=$value already exists"
	fi
}

# Check if object value is empty
is_object_value_exist() {
	str=$(grep -F "$2='$3'" "$(_object_conf "$1")")
	parse_object_kv_list "$str"
	local varname="${4#\$}"
	value="${!varname}"
	if [ -z "$value" ] || [ "$value" = 'no' ]; then
		check_result "$E_NOTEXIST" "${4//$/}=$value doesn't exist"
	fi
}

# Check if password is transmitted via file
# Panel convention: a secret arrives as the PATH of a /tmp file so it never enters argv. Value
# and path space overlap - a password shaped like an existing /tmp path is read as that file.
# Safe because the caller is already root-equivalent via sudoers; the symlink guard keeps the
# root-side read honest should the pattern ever move somewhere that is not.
is_password_valid() {
	if [[ "$password" =~ ^/tmp/ ]]; then
		if ! [[ "$password" == *../* ]] && [ ! -L "$password" ]; then
			if [ -f "$password" ]; then
				password="$(head -n1 $password)"
			fi
		fi
	fi
}

# Check if hash is transmitted via file
is_hash_valid() {
	if [[ "$hash" =~ ^/tmp/ ]]; then
		# symlink guard as in is_password_valid
		if ! [[ "$hash" == *../* ]] && [ ! -L "$hash" ]; then
			if [ -f "$hash" ]; then
				hash="$(head -n1 $hash)"
			fi
		fi
	fi
}

# Check if directory is a symlink
is_dir_symlink() {
	if [[ -L "$1" ]]; then
		check_result "$E_FORBIDEN" "$1 directory is a symlink"
	fi
}

# Escape named variables IN PLACE for a JSON string literal. In place and by name to avoid a subshell
# per field: a 300-domain listing would fork twelve thousand times.
# Emitters only, and only last: it destroys the raw value.
# Emit with printf %s, never spliced into an echo argument: the shell would split and glob the
# escaped value afterwards.
json_escape() {
	local _n _v _c _out
	for _n in "$@"; do
		_v="${!_n}"
		_v="${_v//\\/\\\\}"
		_v="${_v//\"/\\\"}"
		_v="${_v//$'\t'/\\t}"
		_v="${_v//$'\r'/\\r}"
		_v="${_v//$'\n'/\\n}"
		# JSON forbids every raw control character, not just the three with a short form. Rare
		# enough to pay for a per-character loop only when one is actually present.
		if [[ "$_v" == *[$'\x01'-$'\x1f']* ]]; then
			_out=''
			while IFS= read -r -n1 -d '' _c; do
				if [[ "$_c" == [$'\x01'-$'\x1f'] ]]; then
					printf -v _c '\\u%04x' "'$_c"
				fi
				_out+="$_c"
			done < <(printf '%s' "$_v")
			_v="$_out"
		fi
		printf -v "$_n" '%s' "$_v"
	done
}

# Get object value
get_object_value() {
	object=$(grep -F "$2='$3'" "$(_object_conf "$1")")
	parse_object_kv_list "$object"
	local varname="${4#\$}"
	value="${!varname}"
	echo "$value"
}

get_object_values() {
	parse_object_kv_list "$(grep -F "$2='$3'" "$(_object_conf "$1")")"
}

# Update object value
update_object_value() {
	# all helpers local: the escaped $old must never leak into a caller's $old
	local row lnr object varname old new
	row=$(grep -nF "$2='$3'" "$(_object_conf "$1")")
	lnr=$(echo $row | cut -f 1 -d ':')
	object=$(echo $row | sed "s/^$lnr://")
	parse_object_kv_list "$object"
	varname="${4#\$}"
	old="${!varname}"
	# the old value is used as a sed BRE pattern: every metacharacter must be
	# escaped or values like '[SPAM]' silently never match (bracket expression)
	old=$(echo "$old" | sed -e 's/\\/\\\\/g' -e 's/&/\\&/g' -e 's/\//\\\//g' \
		-e 's/\[/\\[/g' -e 's/\]/\\]/g' -e 's/\./\\./g' -e 's/\^/\\^/g' -e 's/\$/\\$/g')
	new=$(echo "$5" | sed -e 's/\\/\\\\/g' -e 's/&/\\&/g' -e 's/\//\\\//g')
	sed -i "$lnr s/${4//$/}='${old//\*/\\*}'/${4//$/}='${new//\*/\\*}'/g" \
		"$(_object_conf "$1")"
}

# Add object key
add_object_key() {
	local row lnr object varname old
	row=$(grep -nF "$2='$3'" "$(_object_conf "$1")")
	lnr=$(echo "$row" | cut -f 1 -d ':')
	object=$(echo "$row" | sed "s/^$lnr://")
	# Bail on an empty line number or anchor key: sed without an address edits EVERY line, so a
	# lookup that found nothing would inject the key into every record in the file.
	if [[ -z "$lnr" || -z "$5" ]]; then
		return 1
	fi
	# Anchored on a separator, and on the opening quote: unanchored, a key that is a SUFFIX of one
	# already in the record counted as present and was silently not added - adding LIST to a record
	# that has DIR_LIST would do nothing at all. Measured today: no key any caller adds is a suffix
	# of another key in the same record, so this is latent rather than live, and it goes sharp the
	# moment the registry grows one more name.
	if [[ "$object" != "$4='"* && "$object" != *" $4='"* ]]; then
		local varname="${4#\$}"
		old="${!varname}"
		sed -i "$lnr s/$5='/$4='' $5='/" "$(_object_conf "$1")"
	fi
}

# Remove a domain's cache-zone line from a shared nginx pool file. Literal match on the
# full keys_zone prefix: a dot in the domain is a regex wildcard, so a.b.com would take
# aXb.com's zone with it - and a vhost still referencing the zone breaks nginx -t box-wide.
remove_pool_zone() {
	local conf="$1" domain="$2"
	[ -e "$conf" ] || return 0
	grep -vF "keys_zone=${domain}:" "$conf" > "$conf.tmp" || true
	mv -f "$conf.tmp" "$conf"
}

# Literal line removal for the account-keyed mail files: any widening of the localpart
# charset would silently under-escape a sed pattern. index==1 anchors at line start.
# Owner/mode are copied onto the rewrite - passwd is dovecot:mail, and a root:root rewrite
# would cut dovecot off from auth.
remove_line_by_prefix() {
	local file="$1" prefix="$2"
	[ -e "$file" ] || return 0
	awk -v p="$prefix" 'index($0, p) != 1' "$file" > "$file.tmp"
	chown --reference="$file" "$file.tmp" 2> /dev/null
	chmod --reference="$file" "$file.tmp" 2> /dev/null
	mv -f "$file.tmp" "$file"
}

# Same, keyed by the whole line (fwd_only holds one bare account per line, so a prefix
# match on john.doe would also take john.doex).
remove_exact_line() {
	local file="$1" line="$2"
	[ -e "$file" ] || return 0
	awk -v p="$line" '$0 != p' "$file" > "$file.tmp"
	chown --reference="$file" "$file.tmp" 2> /dev/null
	chmod --reference="$file" "$file.tmp" 2> /dev/null
	mv -f "$file.tmp" "$file"
}

# Search objects
# The only accessor that stays a regex: its search value is a flag, and h-backup-user-config
# passes "*" on purpose to mean "any". The guard below is what keeps that from decaying into
# "matches a domain by accident" - a comment cannot stop the next caller, a refusal can.
search_objects() {
	# A dot only appears in a domain or an account, never in a flag value, and as a pattern it
	# would match any character. The wildcard is the one legitimate pattern here.
	case "$3" in
		'*') ;;
		*.*) check_result "$E_INVALID" "search_objects takes flag values, not names (got '$3')" ;;
	esac
	OLD_IFS="$IFS"
	IFS=$'\n'
	if [ -f "$(_object_conf "$1")" ]; then
		for line in $(grep "$2='$3'" "$(_object_conf "$1")"); do
			parse_object_kv_list "$line"
			echo "${!4}"
		done
	fi
	IFS="$OLD_IFS"
}

# Get user value
get_user_value() {
	grep "^${1//$/}=" $USER_DATA/user.conf | head -1 | awk -F "'" '{print $2}'
}

# Update user value in user.conf
update_user_value() {
	key="${2//$/}"
	lnr=$(grep -m 1 -n "^$key='" $CONF_DIR/users/$1/user.conf | cut -f 1 -d ':')
	if [ -n "$lnr" ]; then
		# Rewrite the line in place with 'c' (change). The old delete+insert lost a
		# key that sat on the LAST line: after deleting line $lnr the file was
		# $lnr-1 long, so "insert before $lnr" addressed past EOF and silently wrote
		# nothing: the value just vanished. 'c' rewrites any line, last
		# included, and (unlike 's') has no delimiter that a value could contain.
		sed -i "${lnr}c\\$key='${3}'" $CONF_DIR/users/$1/user.conf
	fi
}

# Increase user counter
increase_user_value() {
	key="${2//$/}"
	factor="${3-1}"
	conf="$CONF_DIR/users/$1/user.conf"
	old=$(grep "$key=" $conf | cut -f 2 -d \')
	if [ -z "$old" ]; then
		old=0
	fi
	new=$((old + factor))
	sed -i "s/$key='$old'/$key='$new'/g" $conf
}

# Decrease user counter
decrease_user_value() {
	key="${2//$/}"
	factor="${3-1}"
	conf="$CONF_DIR/users/$1/user.conf"
	old=$(grep "$key=" $conf | cut -f 2 -d \')
	if [ -z "$old" ]; then
		old=0
	fi
	if [ "$old" -le 1 ]; then
		new=0
	else
		new=$((old - factor))
	fi
	if [ "$new" -lt 0 ]; then
		new=0
	fi
	sed -i "s/$key='$old'/$key='$new'/g" $conf
}

# Notify user
send_notice() {
	topic=$1
	notice=$2

	if [ "$notify" = 'yes' ]; then
		# Second writer of notifications.conf besides h-add-user-notification: sanitize NOTICE
		# (rendered via x-html) here too or it's an XSS bypass. %quote% keeps the record intact.
		topic=$(record_value_encode "$topic")
		notice=$(record_value_encode "$("$HESTIA_PHP" "$HESTIA/include/sanitize_html.php" "$notice")")

		touch $USER_DATA/notifications.conf
		chmod 660 $USER_DATA/notifications.conf

		time_n_date=$(date +'%T %F')
		time=$(echo "$time_n_date" | cut -f 1 -d \ )
		date=$(echo "$time_n_date" | cut -f 2 -d \ )

		nid=$(grep "NID=" $USER_DATA/notifications.conf | cut -f 2 -d \')
		nid=$(echo "$nid" | sort -n | tail -n1)
		if [ -n "$nid" ]; then
			nid="$((nid + 1))"
		else
			nid=1
		fi

		str="NID='$nid' TOPIC='$topic' NOTICE='$notice' TYPE='$type'"
		str="$str ACK='no' TIME='$time' DATE='$date'"

		echo "$str" >> $USER_DATA/notifications.conf

		if [ -z "$(grep NOTIFICATIONS $USER_DATA/user.conf)" ]; then
			sed -i "s/^TIME/NOTIFICATIONS='yes'\nTIME/g" $USER_DATA/user.conf
		else
			update_user_value "$user" '$NOTIFICATIONS' "yes"
		fi
	fi
}

# Recalculate U_DISK value
recalc_user_disk_usage() {
	u_usage=0
	if [ -f "$USER_DATA/web.conf" ]; then
		usage=0
		dusage=$(grep 'U_DISK=' $USER_DATA/web.conf \
			| awk -F "U_DISK='" '{print $2}' | cut -f 1 -d \')
		for disk_usage in $dusage; do
			usage=$((usage + disk_usage))
		done
		d=$(grep "U_DISK_WEB='" $USER_DATA/user.conf | cut -f 2 -d \')
		sed -i "s/U_DISK_WEB='$d'/U_DISK_WEB='$usage'/g" $USER_DATA/user.conf
		u_usage=$((u_usage + usage))
	fi

	if [ -f "$USER_DATA/mail.conf" ]; then
		usage=0
		dusage=$(grep 'U_DISK=' $USER_DATA/mail.conf \
			| awk -F "U_DISK='" '{print $2}' | cut -f 1 -d \')
		for disk_usage in $dusage; do
			usage=$((usage + disk_usage))
		done
		d=$(grep "U_DISK_MAIL='" $USER_DATA/user.conf | cut -f 2 -d \')
		sed -i "s/U_DISK_MAIL='$d'/U_DISK_MAIL='$usage'/g" $USER_DATA/user.conf
		u_usage=$((u_usage + usage))
	fi

	if [ -f "$USER_DATA/db.conf" ]; then
		usage=0
		dusage=$(grep 'U_DISK=' $USER_DATA/db.conf \
			| awk -F "U_DISK='" '{print $2}' | cut -f 1 -d \')
		for disk_usage in $dusage; do
			usage=$((usage + disk_usage))
		done
		d=$(grep "U_DISK_DB='" $USER_DATA/user.conf | cut -f 2 -d \')
		sed -i "s/U_DISK_DB='$d'/U_DISK_DB='$usage'/g" $USER_DATA/user.conf
		u_usage=$((u_usage + usage))
	fi
	usage=$(grep -m 1 'U_DISK_DIRS=' $USER_DATA/user.conf | cut -f 2 -d "'")
	u_usage=$((u_usage + usage))
	old=$(grep "U_DISK='" $USER_DATA/user.conf | cut -f 2 -d \')
	sed -i "s/U_DISK='$old'/U_DISK='$u_usage'/g" $USER_DATA/user.conf
}

# Recalculate U_BANDWIDTH value
recalc_user_bandwidth_usage() {
	usage=0
	bandwidth_usage=$(grep 'U_BANDWIDTH=' $USER_DATA/web.conf \
		| awk -F "U_BANDWIDTH='" '{print $2}' | cut -f 1 -d \')
	for bandwidth in $bandwidth_usage; do
		usage=$((usage + bandwidth))
	done
	old=$(grep "U_BANDWIDTH='" $USER_DATA/user.conf | cut -f 2 -d \')
	sed -i "s/U_BANDWIDTH='$old'/U_BANDWIDTH='$usage'/g" $USER_DATA/user.conf
}

# Get next cron job id
get_next_cronjob() {
	if [ -z "$job" ]; then
		curr_str=$(grep "JOB=" $USER_DATA/cron.conf | cut -f 2 -d \' \
			| sort -n | tail -n1)
		job="$((curr_str + 1))"
	fi
}

# Sort cron jobs by id
sort_cron_jobs() {
	sort -n -k 2 -t \' $USER_DATA/cron.conf > $USER_DATA/cron.tmp
	mv -f $USER_DATA/cron.tmp $USER_DATA/cron.conf
}

# Sync cronjobs with system cron
sync_cron_jobs() {
	source_conf "$USER_DATA/user.conf"
	if [ -e "/var/spool/cron/crontabs" ]; then
		crontab="/var/spool/cron/crontabs/$user"
	else
		crontab="/var/spool/cron/$user"
	fi

	# remove file if exists
	if [ -e "$crontab" ]; then
		rm -f $crontab
	fi

	# touch new crontab file
	touch $crontab

	if [ "$CRON_REPORTS" = 'yes' ]; then
		echo "MAILTO=$CONTACT" > $crontab
		echo 'CONTENT_TYPE="text/plain; charset=utf-8"' >> $crontab
	else
		echo 'MAILTO=""' > $crontab
	fi

	# read -r: do not let a backslash in a stored CMD field be consumed as a
	# line-continuation/escape while assembling the crontab (GHSA-5fpv hardening).
	while read -r line; do
		parse_object_kv_list "$line"
		if [ "$SUSPENDED" = 'no' ]; then
			# Decode the command alone: a schedule field cannot carry a storage placeholder, and
			# decoding the assembled line rewrote parts of the command that were never encoded.
			printf '%s %s %s %s %s %s\n' "$MIN" "$HOUR" "$DAY" "$MONTH" "$WDAY" \
				"$(record_value_decode "$CMD")" >> "$crontab"
		fi
	done < $USER_DATA/cron.conf
	chown $user:$user $crontab
	chmod 600 $crontab
}

# The one hestia crontab: two copies existed and had drifted in four ways, so rendering it here makes
# drift impossible rather than merely comparable.
# The renewal time is drawn per write, which only happens where there is no file to preserve it from.
system_crontab_write() {
	local _dst='/var/spool/cron/crontabs/hestia' _tmp _min _hour
	# Arithmetic, not a pipeline into `head`: the old form could only produce a minute of 00-55.
	_min=$((RANDOM % 60))
	_hour=$((RANDOM % 7 + 1))
	mkdir -p /var/spool/cron/crontabs || return 1
	# A killed run leaves its temp; cron ignores it, but it would pile up unseen.
	rm -f /var/spool/cron/crontabs/.hestia.* 2> /dev/null || true
	_tmp="/var/spool/cron/crontabs/.hestia.$$"
	{
		echo "MAILTO=\"\""
		echo "CONTENT_TYPE=\"text/plain; charset=utf-8\""
		echo "*/2 * * * * sudo $HESTIA/bin/h-update-sys-queue restart"
		echo "10 00 * * * sudo $HESTIA/bin/h-update-sys-queue daily"
		echo "15 02 * * * sudo $HESTIA/bin/h-update-sys-queue disk"
		echo "10 00 * * * sudo $HESTIA/bin/h-update-sys-queue traffic"
		echo "30 03 * * * sudo $HESTIA/bin/h-update-sys-queue webstats"
		echo "*/5 * * * * sudo $HESTIA/bin/h-update-sys-queue backup"
		echo "10 05 * * * sudo $HESTIA/bin/h-backup-users"
		echo "20 00 * * * sudo $HESTIA/bin/h-update-user-stats"
		echo "*/5 * * * * sudo $HESTIA/bin/h-update-sys-rrd"
		echo "$_min $_hour * * * sudo $HESTIA/bin/h-update-letsencrypt-ssl"
	} > "$_tmp" || return 1
	chmod 600 "$_tmp" && chown hestia:hestia "$_tmp" || {
		rm -f "$_tmp"
		return 1
	}
	# Rename, never truncate: with fs.protected_regular=2 and a sticky, group-writable crontabs
	# directory owned by hestia, opening the file for writing is EACCES even for root. rename is not
	# subject to that check and root owns the directory.
	mv -f "$_tmp" "$_dst"
}

# The periodic repair, in /etc/cron.d and NOT in the hestia crontab: a deleted crontab would take the
# line that restores it with it.
# Daily at 04:40, where nothing else runs. What it heals changes almost never, and a week without a
# crontab is a week without queue processing.
# Root directly: cron.d entries name their user, and this one is not reachable from the panel.
system_repair_cron_write() {
	local _dst='/etc/cron.d/hestia-repair' _tmp
	_tmp=$(mktemp "/etc/cron.d/.hestia-repair.XXXXXX") || return 1
	echo "40 04 * * * root $HESTIA/bin/h-repair-sys-config repair" > "$_tmp" || {
		rm -f "$_tmp"
		return 1
	}
	# cron REFUSES a group- or world-writable file in /etc/cron.d and says so only in its log
	# ("INSECURE MODE"), which is how the hestia-ssl fallback once never ran anywhere.
	chmod 644 "$_tmp" && chown root:root "$_tmp" || {
		rm -f "$_tmp"
		return 1
	}
	mv -f "$_tmp" "$_dst"
}

# Validates Local part email and mail alias
is_localpart_format_valid() {
	if [ ${#1} -eq 1 ]; then
		if ! [[ "$1" =~ ^[[:alnum:]]$ ]]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	else
		if [ -n "$3" ]; then
			maxlenght=$(($3 - 2))
			# Allow leading and trailing special characters by adjusting the regex
			if ! [[ "$1" =~ ^[[:alnum:]_.-][[:alnum:]_.-]{0,$maxlenght}[[:alnum:]_.-]$ ]]; then
				check_result "$E_INVALID" "invalid $2 format :: $1"
			fi
		else
			# Allow leading and trailing special characters by adjusting the regex
			if ! [[ "$1" =~ ^[[:alnum:]_.-][[:alnum:]_.-]{0,28}[[:alnum:]_.-]$ ]]; then
				check_result "$E_INVALID" "invalid $2 format :: $1"
			fi
		fi
	fi
	if [ "$1" != "${1//[^[:ascii:]]/}" ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Username / ftp username format validator
is_user_format_valid() {
	if [ ${#1} -eq 1 ]; then
		if ! [[ "$1" =~ ^^[[:alnum:]]$ ]]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	else
		if [ -n "$3" ]; then
			maxlenght=$(($3 - 2))
			if ! [[ "$1" =~ ^[[:alnum:]][-._[:alnum:]]{0,$maxlenght}[[:alnum:]]$ ]]; then
				check_result "$E_INVALID" "invalid $2 format :: $1"
			fi
		else
			if ! [[ "$1" =~ ^[[:alnum:]][-._[:alnum:]]{0,28}[[:alnum:]]$ ]]; then
				check_result "$E_INVALID" "invalid $2 format :: $1"
			fi
		fi
	fi
	if [ "$1" != "${1//[^[:ascii:]]/}" ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# Only for new users
	if [[ "$FROM_V_ADD_USER" == "true" ]]; then
		if ! [[ "$1" =~ ^[a-zA-Z][-_[:alnum:]]{0,28}[[:alnum:]]$ ]]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	fi
}

# Names a service account owns, or will own once its component is installed. The live databases
# only know what is on the box today, so a curated list is the only way to keep a name free that
# an optional component takes later. Callers still check passwd/group themselves.
is_login_name_reserved() {
	local name="${1,,}" label="${2:-user}" reserved r
	reserved=(
		# ours
		hestia hestia-users procvis sftp-jailed
		# always installed
		nginx apache2 www-data caddy php mysql mariadb phpmyadmin
		# standard profile + optional components (h-add-sys-*, h-add-user-*)
		exim dovecot dovenull rspamd roundcube tachyon crowdsec fail2ban
		docker containerd proftpd ftp clamav postgres postgresql redis opensearch filemanager
		# MariaDB/MySQL database names, and sudo (that group always has sudo rights)
		aria aria_log mysql_upgrade ib ib_buffer ddl ddl_recovery performance sudo
		# h-backup-server writes server.*.tar into the same /backup namespace as customer archives
		server
	)
	for r in "${reserved[@]}"; do
		if [ "$name" = "$r" ]; then
			check_result "$E_INVALID" "$label name '$1' is reserved for system use"
		fi
	done
	# h-add-user-docker names the companion account <user>-docker
	if [ "${name%-docker}" != "$name" ]; then
		check_result "$E_INVALID" "$label name '$1' is reserved: -docker is the companion suffix"
	fi
	return 0
}

# Domain format validator
is_domain_format_valid() {
	object_name=${2-domain}
	exclude='[][!@#$^&*()+={},<>?_/\\"|'\''`;%[:space:]]'
	if [[ $1 =~ $exclude ]] \
		|| [[ $1 =~ ^[0-9]+$ ]] \
		|| [[ $1 =~ \.\. ]] \
		|| [[ $1 =~ ^- ]] \
		|| [[ $1 =~ -$ ]] \
		|| [[ $1 =~ ^\. ]] \
		|| [[ $1 =~ \.$ ]] \
		|| [[ $1 =~ \.- ]] \
		|| [[ $1 =~ -\. ]] \
		|| [[ "$1" = "www" ]]; then
		check_result "$E_INVALID" "invalid $object_name format :: $1"
	fi
	is_no_new_line_format "$1"
}

# Alias forman validator
is_alias_format_valid() {
	for object in ${1//,/ }; do
		exclude='[][!@#$^&()+={},<>?_/\\"|'\''`;%[:space:]]'
		if [[ $object =~ $exclude ]] \
			|| [[ $object =~ \.\. ]] \
			|| [[ $object =~ ^- ]] \
			|| [[ $object =~ -$ ]] \
			|| [[ $object =~ ^\. ]] \
			|| [[ $object =~ \.$ ]] \
			|| [[ $object =~ \.- ]] \
			|| [[ $object =~ -\. ]]; then
			check_result "$E_INVALID" "invalid alias format :: $object"
		fi
		if [[ "$object" =~ [*] ]] && ! [[ "$object" =~ ^[*]\..* ]]; then
			check_result "$E_INVALID" "invalid alias format :: $object"
		fi
	done
}

# IP format validator
# Address family by CONTENT, never by path or field (a rename must not change the answer).
# Echoes 4, 6, or nothing for neither - callers treat empty as "skip", like fw_addr_family.
# Lives here, not in ip.sh: the web-model consumers need it without sourcing ip.sh.
ip_family() {
	case "$1" in
		*:*) echo 6 ;;
		*) [[ "$1" =~ ^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$ ]] && echo 4 ;;
	esac
	return 0
}

is_ip_format_valid() {
	object_name=${2-ip}
	valid=$($HESTIA_PHP -r '$ip=$argv[1]; echo (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 0 : 1);' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

# IPv6 format validator
is_ipv6_format_valid() {
	object_name=${2-ipv6}
	valid=$($HESTIA_PHP -r '$ip=$argv[1]; echo (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 0 : 1);' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

# Silent predicate: exit status only, never check_result. The validator below refuses AND logs, so
# using it as a probe in a subshell wrote an [Error 2] line on every successful domain add.
# Named far from is_ip46_format_valid on purpose: one asks, the other refuses.
looks_like_ip46() {
	[ "$($HESTIA_PHP -r '$ip=$argv[1]; echo (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) ? 0 : 1);' "$1")" = 0 ]
}

is_ip46_format_valid() {
	if ! looks_like_ip46 "$1"; then
		check_result "$E_INVALID" "invalid IP format :: $1"
	fi
}

is_ipv4_cidr_format_valid() {
	object_name=${2-ip}
	valid=$($HESTIA_PHP -r '[$ip, $net] = [...explode("/", $argv[1]), "32"]; echo (preg_match("/^(\d{1,3}\.){3}\d{1,3}$/", $ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && is_numeric($net) && $net >= 0 && $net <= 32) ? 0 : 1;' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

# Either family. Bans accept both since the firewall carries a v6 set per jail; the single-family
# validators stay for the places that genuinely mean one family (an IP object, a NAT address).
is_ip_cidr_format_valid() {
	object_name=${2-ip}
	valid=$($HESTIA_PHP -r '$cidr=$argv[1]; $p=explode("/", $cidr); $ip=$p[0]; $m=$p[1]??null;
		$v4=filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		$v6=filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
		$ok=($v4 && ($m===null || $m<=32)) || ($v6 && ($m===null || $m<=128));
		echo $ok ? 0 : 1;' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

is_ipv6_cidr_format_valid() {
	object_name=${2-ipv6}
	valid=$($HESTIA_PHP -r '$cidr=$argv[1]; list($ip, $netmask) = [...explode("/", $cidr), 128]; echo ((filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $netmask <= 128) ? 0 : 1);' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

is_netmask_format_valid() {
	object_name=${2-netmask}
	valid=$($HESTIA_PHP -r '$netmask=$argv[1]; echo (preg_match("/^(128|192|224|240|248|252|254|255)\.(0|128|192|224|240|248|252|254|255)\.(0|128|192|224|240|248|252|254|255)\.(0|128|192|224|240|248|252|254|255)/", $netmask) ? 0 : 1);' "$1")
	if [ "$valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid $object_name :: $1"
	fi
}

# Proxy extention format validator
is_extention_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|#|$|^|&|(|)|+|=|{|}|:|@|<|>|?|/|\|\"|'|;|%|\`| ]"
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "invalid proxy extention format :: $1"
	fi
	is_no_new_line_format "$1"
}

# Number format validator
is_number_format_valid() {
	object_name=${2-number}
	if ! [[ "$1" =~ ^[0-9]+$ ]]; then
		check_result "$E_INVALID" "invalid $object_name format :: $1"
	fi
}

# Autoreply format validator
is_autoreply_format_valid() {
	if [ 10240 -le ${#1} ]; then
		check_result "$E_INVALID" "invalid autoreply format :: $1"
	fi
}

# Boolean format validator
is_boolean_format_valid() {
	if [ "$1" != 'yes' ] && [ "$1" != 'no' ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Refresh IPset format validator
is_refresh_ipset_format_valid() {
	if [ "$1" != 'load' ] && [ "$1" != 'yes' ] && [ "$1" != 'no' ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Addresses and networks, both families: the common deny list rejects colon and slash, so
# nothing v6 could ever be stored. An empty list is legal - switching the feature off writes one.
is_ip_list_format_valid() {
	local list="$1" name="${2-ip list}" entry
	local -a entries
	is_no_new_line_format "$list"
	[ ${#list} -lt 400 ] || check_result "$E_INVALID" "invalid $name format :: $list"
	IFS=',' read -ra entries <<< "$list"
	for entry in "${entries[@]}"; do
		# OUTER whitespace only, like the panel: stripping inner spaces would accept
		# "192.168.1. 0/24", store it, and the matcher could never hit it
		entry="${entry#"${entry%%[![:space:]]*}"}"
		entry="${entry%"${entry##*[![:space:]]}"}"
		[ -z "$entry" ] && continue
		is_ip_cidr_format_valid "$entry" "$name"
	done
}

# Common format validator
is_common_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|#|$|^|&|(|)|+|=|{|}|:|<|>|?|/|\|\"|'|;|%|\`| ]"
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [ 400 -le ${#1} ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ "$1" =~ @ ]] && [ ${#1} -gt 1 ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ $1 =~ \* ]]; then
		if [[ "$(echo $1 | grep -o '\*\.' | wc -l)" -eq 0 ]] && [[ $1 != '*' ]]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	fi
	if [[ $(echo -n "$1" | tail -c 1) =~ [^a-zA-Z0-9_*@.] ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ $(echo -n "$1" | grep -c '\.\.') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ $(echo -n "$1" | head -c 1) =~ [^a-zA-Z0-9_*@] ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ $(echo -n "$1" | grep -c '\-\-') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	if [[ $(echo -n "$1" | grep -c '\_\_') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	is_no_new_line_format "$1"
}

# Common format validator for fields that need spaces
is_common_format_spaces_valid() {
	# Block injection chars but allow spaces
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|#|$|^|&|(|)|+|=|{|}|:|<|>|?|/|\|\"|'|;|%|\`]"

	# Block tabs, newlines, carriage returns
	if [[ "$1" == *$'\t'* || "$1" == *$'\n'* || "$1" == *$'\r'* || "$1" == *$'\v'* || "$1" == *$'\f'* ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No leading/trailing spaces
	if [[ "$1" == " "* || "$1" == *" " ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No multiple consecutive spaces
	if [[ "$1" =~ "  " ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# Check excluded chars
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# Max length
	if [ 400 -le ${#1} ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No @ (except single char)
	if [[ "$1" =~ @ ]] && [ ${#1} -gt 1 ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No wildcards
	if [[ "$1" =~ \* ]]; then
		if [[ "$(echo "$1" | grep -o '\*\.' | wc -l)" -eq 0 ]] && [[ "$1" != '*' ]]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	fi

	# Must end with alphanumeric
	if [[ $(echo -n "$1" | tail -c 1) =~ [^a-zA-Z0-9] ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No consecutive dots
	if [[ $(echo -n "$1" | grep -c '\.\.') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# Must start with alphanumeric
	if [[ $(echo -n "$1" | head -c 1) =~ [^a-zA-Z0-9] ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No consecutive dashes
	if [[ $(echo -n "$1" | grep -c '\-\-') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	# No consecutive underscores
	if [[ $(echo -n "$1" | grep -c '\_\_') -gt 0 ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi

	is_no_new_line_format "$1"
}

is_no_new_line_format() {
	test=$(echo "$1" | head -n1)
	if [[ "$test" != "$1" ]]; then
		check_result "$E_INVALID" "invalid value :: $1"
	fi
}

# Restore-queue selector guard: block only ' and CR/LF (they escape a single-quoted field in
# the executed backup.pipe, GHSA-2xw3); comma-lists/'*'/'no'/paths must still pass.
is_no_quote_format() {
	if [[ "$1" == *\'* ]] || [[ "$1" == *$'\n'* ]] || [[ "$1" == *$'\r'* ]]; then
		check_result "$E_INVALID" "invalid $2 format :: quotes and line breaks are not allowed"
	fi
}

# A restore selector: empty, '*', 'no'/'yes', or a comma list of object names.
#
# CLOSED character set, not a deny list: the value is spliced into a queue line bash runs as root.
# Space is in, a home entry can be "my documents". TAB is not: tar prints it escaped in the listing
# the restore matches against, so such a selector would silently select nothing.
is_selector_format_valid() {
	# In a variable: an unquoted space inside [[ =~ ]] would split the pattern into two words.
	local _re='^[-._,* [:alnum:]]+$'
	if [ -n "$1" ] && ! [[ "$1" =~ $_re ]]; then
		check_result "$E_INVALID" "invalid $2 format :: only letters, digits, spaces and - . _ , * are allowed"
	fi
}

is_string_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|#|$|^|&|(|)|+|=|{|}|:|<|>|?|/|\|\"|'|;|%|\`]"
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	is_no_new_line_format "$1"
}

# TOPIC is one field in the single-line notifications.conf record: reject CR/LF, cap length.
is_notification_topic_valid() {
	if [[ "$1" == *$'\n'* ]] || [[ "$1" == *$'\r'* ]]; then
		check_result "$E_INVALID" "invalid topic format :: line breaks are not allowed"
	fi
	if [ ${#1} -gt 255 ]; then
		check_result "$E_INVALID" "invalid topic format :: too long"
	fi
}

# NOTICE is HTML-sanitized elsewhere; here just guard the single-line conf format (CR/LF, length).
is_notification_notice_valid() {
	if [[ "$1" == *$'\n'* ]] || [[ "$1" == *$'\r'* ]]; then
		check_result "$E_INVALID" "invalid notice format :: line breaks are not allowed"
	fi
	if [ ${#1} -gt 4000 ]; then
		check_result "$E_INVALID" "invalid notice format :: too long"
	fi
}
is_cron_command_valid_format() {
	if [[ ! "$1" =~ ^[^\`]*?$ ]]; then
		check_result "$E_INVALID" "Invalid cron command format"
	fi
	# A crontab CMD field is a single line - reject embedded newlines so a value
	# cannot inject an extra crontab entry (GHSA-5fpv). Semicolons are allowed:
	# they are legitimate in cron commands (multiple commands on one line).
	is_no_new_line_format "$1"
}
# Database format validator
is_database_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|@|#|$|^|&|*|(|)|+|=|{|}|:|,|<|>|?|/|\|\"|'|;|%|\`| ]"
	if [[ "$1" =~ $exclude ]] || [ 64 -le ${#1} ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	is_no_new_line_format "$1"
}

# Date format validator
is_date_format_valid() {
	if ! [[ "$1" =~ ^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$ ]]; then
		check_result "$E_INVALID" "invalid date format :: $1"
	fi
}

# Database user validator
is_dbuser_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="[!|@|#|$|^|&|*|(|)|+|=|{|}|:|,|<|>|?|/|\|\"|'|;|%|\`| ]"
	if [ 33 -le ${#1} ]; then
		check_result "$E_INVALID" "mysql username can be up to 32 characters long"
	fi
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
	is_no_new_line_format "$1"
}

# DNS record type validator
is_dns_type_format_valid() {
	is_valid=$(
		$HESTIA_PHP -- "$1" << 'EOPHP'
	<?php
	$type = $argv[1];
	$known_types = array("A","AAAA","NS","CNAME","MX","TXT","SRV","DNSKEY",
	"KEY","IPSECKEY","PTR","SPF","TLSA","CAA","DS");
	echo in_array($type, $known_types, true) ? "0" : "1";
EOPHP
	)
	if [ "$is_valid" -ne 0 ]; then
		check_result "$E_INVALID" "invalid dns record type format :: $1"
	fi
}

# Email format validator
is_email_format_valid() {
	if [[ ! "$1" =~ ^[A-Za-z0-9._%+-]+@[[:alnum:].-]+\.[A-Za-z]{2,63}$ ]]; then
		if [[ ! "$1" =~ ^[A-Za-z0-9._%+-]+@[[:alnum:].-]+\.(xn--)[[:alnum:]]{2,63}$ ]]; then
			check_result "$E_INVALID" "invalid email format :: $1"
		fi
	fi
}

# Firewall action validator
is_fw_action_format_valid() {
	if [ "$1" != "ACCEPT" ] && [ "$1" != 'DROP' ]; then
		check_result "$E_INVALID" "invalid action format :: $1"
	fi
}

# Firewall protocol validator
is_fw_protocol_format_valid() {
	if [ "$1" != "ICMP" ] && [ "$1" != 'UDP' ] && [ "$1" != 'TCP' ]; then
		check_result "$E_INVALID" "invalid protocol format :: $1"
	fi
}

# Firewall port validator
is_fw_port_format_valid() {
	if [ "${#1}" -eq 1 ]; then
		if ! [[ "$1" =~ [0-9] ]]; then
			check_result "$E_INVALID" "invalid port format :: $1"
		fi
	else
		if ! [[ "$1" =~ ^[0-9][-,:0-9]{0,76}[0-9]$ ]]; then
			check_result "$E_INVALID" "invalid port format and/or more than 78 chars used :: $1"
		fi
	fi
}

# DNS record id validator
is_id_format_valid() {
	if ! echo "$1" | grep -qE '^[1-9][0-9]{0,}$'; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Integer validator
is_int_format_valid() {
	if ! [[ "$1" =~ ^[0-9]+$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Interface validator
is_interface_format_valid() {
	nic_names="$(ip -d -j link show | jq -r '.[] | if .link_type == "loopback" then empty else .ifname, if .altnames then .altnames[] else empty end end')"
	if [ -z "$(echo "$nic_names" | grep -x "$1")" ]; then
		check_result "$E_INVALID" "invalid interface format :: $1"
	fi
}

# IP status validator
is_ip_status_format_valid() {
	if [ -z "$(echo shared,dedicated | grep -w "$1")" ]; then
		check_result "$E_INVALID" "invalid status format :: $1"
	fi
}

# Comment validator
is_comment_format_valid() {
	if ! [[ "$1" =~ ^[[:alnum:]][[:alnum:][:space:]._-]{0,64}[[:alnum:]]$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Cron validator
is_cron_format_valid() {
	limit=59
	check_format=''
	if [ "$2" = 'hour' ]; then
		limit=23
	fi

	if [ "$2" = 'day' ]; then
		limit=31
	fi
	if [ "$2" = 'month' ]; then
		limit=12
	fi
	if [ "$2" = 'wday' ]; then
		limit=7
	fi
	if [ "$1" = '*' ]; then
		check_format='ok'
	fi
	if [[ "$1" =~ ^[\*]+[/]+[0-9] ]]; then
		if [ "$(echo $1 | cut -f 2 -d /)" -lt $limit ]; then
			check_format='ok'
		fi
	fi
	if [[ "$1" =~ ^[0-9][-,0-9]{0,70}[\/][0-9]$ ]]; then
		check_format='ok'
		crn_values=${1//,/ }
		crn_values=${crn_values//-/ }
		crn_values=${crn_values//\// }
		for crn_vl in $crn_values; do
			if [ "$crn_vl" -gt $limit ]; then
				check_format='invalid'
			fi
		done
	fi
	crn_values=$(echo $1 | tr "," " " | tr "-" " ")
	for crn_vl in $crn_values; do
		if [[ "$crn_vl" =~ ^[0-9]+$ ]] && [ "$crn_vl" -le $limit ]; then
			check_format='ok'
		fi
	done
	if [ "$check_format" != 'ok' ]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

is_object_name_format_valid() {
	if ! [[ "$1" =~ ^[-\ ._[:alnum:]]{0,50}$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}
# Name validator
is_name_format_valid() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="['|\"|<|>]"
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "Invalid $2 contains qoutes (\" or ') :: $1"
	fi
	is_no_new_line_format "$1"
}

# Object validator
is_object_format_valid() {
	if ! [[ "$1" =~ ^[[:alnum:]][-._[:alnum:]]{0,64}[[:alnum:]]$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# A remote host: a name OR a bare address, the name form has no colon.
is_host46_format_valid() {
	case "$1" in
		*:*) is_ipv6_format_valid "$1" "${2-host}" ;;
		*) is_object_format_valid "$1" "${2-host}" ;;
	esac
}

# A host for a URL or an ssh-style host:path: a v6 literal needs brackets, or its colons read as
# the port separator. NOT for expect (Tcl) or the ftp client - see include/backup.sh.
url_host() {
	case "$1" in
		*:*) echo "[$1]" ;;
		*) echo "$1" ;;
	esac
}

# Role validator
is_role_valid() {
	if ! [[ "$1" =~ ^admin$|^user$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Password validator
is_password_format_valid() {
	if [ "${#1}" -lt '6' ]; then
		check_result "$E_INVALID" "invalid password format :: $1"
	fi
}
# Curated login-shell allowlist: one source for the panel (h-list-sys-shells)
# and the validator (is_format_valid_shell). Dropped rssh - gone from Debian since
# bullseye, it selects a missing binary and silently acts like nologin. screen/tmux/
# dash/rbash dropped too. Existing off-list shells are kept (rebuild.sh), not offered.
HESTIA_SHELL_ALLOWLIST="nologin jailbash bash sh"

# Emit the effective shells: allowlist ∩ /etc/shells, one basename per line, ladder
# order. nologin is emitted unconditionally - it is the default shell of every new
# user, so it must stay assignable even if /etc/shells has not been seeded yet.
list_allowed_shells() {
	local allow
	for allow in $HESTIA_SHELL_ALLOWLIST; do
		if [ "$allow" = 'nologin' ]; then
			echo 'nologin'
		elif grep -qE "/${allow}\$" /etc/shells 2> /dev/null; then
			echo "$allow"
		fi
	done
}

# shell must be one of the curated, /etc/shells-backed login shells
is_format_valid_shell() {
	local shell
	shell=$(basename -- "$1")
	if ! list_allowed_shells | grep -qxF "$shell"; then
		echo "Error: shell $1 is not valid"
		log_event "$E_INVALID" "$EVENT"
		exit $E_INVALID
	fi
}

# Service name validator
is_service_format_valid() {
	if ! [[ "$1" =~ ^[[:alnum:]][-._[:alnum:]]{0,64}$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

is_hash_format_valid() {
	if ! [[ "$1" =~ ^[[:alnum:]|\:|\=|_|-]{1,80}$ ]]; then
		check_result "$E_INVALID" "invalid $2 format :: $1"
	fi
}

# Format validation controller. Validates by VARIABLE NAME: the name is both the type and the variable
# read via ${!name}, so renaming one changes which check runs. The name is part of the interface.
# An UNSET variable is a hard error: empty used to mean "nothing to check", which silently disabled a
# check twice. A declared-but-empty variable stays a legitimate skip.
is_format_valid() {
	for arg_name in $*; do
		if ! declare -p "$arg_name" > /dev/null 2>&1; then
			check_result "$E_INVALID" "internal: is_format_valid '$arg_name' names no variable - check for a rename or typo"
		fi
		arg="${!arg_name}"
		if [ -n "$arg" ]; then
			case $arg_name in
				account) is_localpart_format_valid "$arg" "$arg_name" '64' ;;
				action) is_fw_action_format_valid "$arg" ;;
				active) is_boolean_format_valid "$arg" 'active' ;;
				aliases) is_alias_format_valid "$arg" ;;
				alias) is_alias_format_valid "$arg" ;;
				antispam) is_boolean_format_valid "$arg" 'antispam' ;;
				antivirus) is_boolean_format_valid "$arg" 'antivirus' ;;
				autoreply) is_autoreply_format_valid "$arg" ;;
				backup) is_object_format_valid "$arg" 'backup' ;;
				charset) is_object_format_valid "$arg" "$arg_name" ;;
				charsets) is_common_format_valid "$arg" 'charsets' ;;
				chain) is_object_format_valid "$arg" 'chain' ;;
				comment) is_comment_format_valid "$arg" 'comment' ;;
				cron_command) is_cron_command_valid_format "$arg" ;;
				database) is_database_format_valid "$arg" 'database' ;;
				day) is_cron_format_valid "$arg" $arg_name ;;
				dbpass) is_password_format_valid "$arg" ;;
				dbuser) is_dbuser_format_valid "$arg" 'dbuser' ;;
				dkim) is_boolean_format_valid "$arg" 'dkim' ;;
				dkim_size) is_int_format_valid "$arg" ;;
				domain) is_domain_format_valid "$arg" ;;
				dom_alias) is_alias_format_valid "$arg" ;;
				email) is_email_format_valid "$arg" ;;
				email_forward) is_email_format_valid "$arg" ;;
				exp) is_date_format_valid "$arg" ;;
				extentions) is_common_format_valid "$arg" 'extentions' ;;
				format) is_type_valid 'plain json shell' "$arg" ;;
				ftp_password) is_password_format_valid "$arg" ;;
				ftp_user) is_user_format_valid "$arg" "$arg_name" ;;
				hash) is_hash_format_valid "$arg" "$arg_name" ;;
				host) is_object_format_valid "$arg" "$arg_name" ;;
				host46) is_host46_format_valid "$arg" "$arg_name" ;;
				hour) is_cron_format_valid "$arg" $arg_name ;;
				id) is_id_format_valid "$arg" 'id' ;;
				iface) is_interface_format_valid "$arg" ;;
				ip) is_ip_format_valid "$arg" ;;
				ipv6) is_ipv6_format_valid "$arg" ;;
				ip46) is_ip46_format_valid "$arg" ;;
				ip_cidr) is_ip_cidr_format_valid "$arg" ;;
				ipv4_cidr) is_ipv4_cidr_format_valid "$arg" ;;
				ipv6_cidr) is_ipv6_cidr_format_valid "$arg" ;;
				ip_name) is_domain_format_valid "$arg" 'IP name' ;;
				ip_status) is_ip_status_format_valid "$arg" ;;
				job) is_int_format_valid "$arg" 'job' ;;
				key) is_common_format_valid "$arg" "$arg_name" ;;
				malias) is_localpart_format_valid "$arg" "$arg_name" '64' ;;
				max_db) is_int_format_valid "$arg" 'max db' ;;
				min) is_cron_format_valid "$arg" $arg_name ;;
				month) is_cron_format_valid "$arg" $arg_name ;;
				name) is_name_format_valid "$arg" "name" ;;
				nat_ip) is_ip_format_valid "$arg" ;;
				netmask) is_netmask_format_valid "$arg" 'netmask' ;;
				newid) is_int_format_valid "$arg" 'id' ;;
				notice) is_notification_notice_valid "$arg" ;;
				ns1) is_domain_format_valid "$arg" 'ns1' ;;
				ns2) is_domain_format_valid "$arg" 'ns2' ;;
				ns3) is_domain_format_valid "$arg" 'ns3' ;;
				ns4) is_domain_format_valid "$arg" 'ns4' ;;
				ns5) is_domain_format_valid "$arg" 'ns5' ;;
				ns6) is_domain_format_valid "$arg" 'ns6' ;;
				ns7) is_domain_format_valid "$arg" 'ns7' ;;
				ns8) is_domain_format_valid "$arg" 'ns8' ;;
				object) is_object_name_format_valid "$arg" 'object' ;;
				package) is_object_format_valid "$arg" "$arg_name" ;;
				password) is_password_format_valid "$arg" ;;
				priority) is_int_format_valid $arg ;;
				port) is_int_format_valid "$arg" 'port' ;;
				port_ext) is_fw_port_format_valid "$arg" ;;
				protocol) is_fw_protocol_format_valid "$arg" ;;
				proxy_ext) is_extention_format_valid "$arg" ;;
				quota) is_int_format_valid "$arg" 'quota' ;;
				rate) is_int_format_valid "$arg" 'rate' ;;
				record) is_common_format_valid "$arg" 'record' ;;
				reject) is_boolean_format_valid "$arg" 'reject' ;;
				restart) is_restart_format_valid "$arg" 'restart' ;;
				role) is_role_valid "$arg" 'role' ;;
				rtype) is_dns_type_format_valid "$arg" ;;
				rule) is_int_format_valid "$arg" "rule id" ;;
				service) is_service_format_valid "$arg" "$arg_name" ;;
				soa) is_domain_format_valid "$arg" 'SOA' ;;
				#missing command: is_format_valid_shell
				shell) is_format_valid_shell "$arg" ;;
				ssl_dir) is_folder_exists "$arg" "$arg_name" ;;
				stats_pass) is_password_format_valid "$arg" ;;
				stats_user) is_user_format_valid "$arg" "$arg_name" ;;
				template) is_object_format_valid "$arg" "$arg_name" ;;
				theme) is_common_format_valid "$arg" "$arg_name" ;;
				topic) is_notification_topic_valid "$arg" ;;
				ttl) is_int_format_valid "$arg" 'ttl' ;;
				user) is_user_format_valid "$arg" $arg_name ;;
				wday) is_cron_format_valid "$arg" $arg_name ;;
				value) is_common_format_valid "$arg" $arg_name ;;
			esac
		fi
	done
}

is_folder_exists() {
	if [ ! -d "$1" ]; then
		check_result "$E_NOTEXIST" "folder $1 does not exist"
	fi
}

# Domain argument formatting
format_domain() {
	if [[ "$domain" = *[![:ascii:]]* ]]; then
		if [[ "$domain" =~ [[:upper:]] ]]; then
			domain=$(echo "$domain" | sed 's/[[:upper:]].*/\L&/')
		fi
	else
		if [[ "$domain" =~ [[:upper:]] ]]; then
			domain=$(echo "$domain" | tr '[:upper:]' '[:lower:]')
		fi
	fi
	if [[ "$domain" =~ ^www\..* ]]; then
		domain=$(echo "$domain" | sed -e "s/^www.//")
	fi
	if [[ "$domain" =~ .*\.$ ]]; then
		domain=$(echo "$domain" | sed -e "s/[.]*$//g")
	fi
	if [[ "$domain" =~ ^\. ]]; then
		domain=$(echo "$domain" | sed -e "s/^[.]*//")
	fi
	# Remove white spaces
	domain=$(echo $domain | sed 's/^[ \t]*//;s/[ \t]*$//')
}

# Always the twin of $domain as format_domain left it. Seeding only when empty let a caller's raw
# argument survive: with `www.other.com` every duplicate guard then checked a name no record carries,
# while record and vhost were created under the stripped name, another customer's.
format_domain_idn() {
	domain_idn=$domain
	if [[ "$domain_idn" = *[![:ascii:]]* ]]; then
		domain_idn=$(idn2 --quiet $domain_idn)
	fi
}

format_aliases() {
	if [ -n "$aliases" ] && [ "$aliases" != 'none' ]; then
		aliases=$(echo $aliases | tr '[:upper:]' '[:lower:]' | tr ',' '\n')
		aliases=$(echo "$aliases" | sed -e "s/\.$//" | sort -u)
		aliases=$(echo "$aliases" | tr -s '.')
		aliases=$(echo "$aliases" | sed -e "s/[.]*$//g")
		aliases=$(echo "$aliases" | sed -e "s/^[.]*//")
		aliases=$(echo "$aliases" | sed -e "/^$/d")
		aliases=$(echo "$aliases" | tr '\n' ',' | sed -e "s/,$//")
	fi
}

is_restart_format_valid() {
	# 'now' is the restart.pipe's own grammar: every h-restart-* queues itself as
	# "$SCRIPT now" under SCHEDULED_RESTART, and the queue runs the pipe with all
	# errors swallowed - the inherited list rejected the one value the family
	# writes, so every queued restart died silently. now = run immediately,
	# do not requeue (it falls through the scheduling branch by design).
	if [ -n "$1" ]; then
		if [ "$1" != 'yes' ] && [ "$1" != 'no' ] && [ "$1" != 'ssl' ] && [ "$1" != 'reload' ] && [ "$1" != 'updatessl' ] && [ "$1" != "scheduled" ] && [ "$1" != 'now' ]; then
			check_result "$E_INVALID" "invalid $2 format :: $1"
		fi
	fi
}

check_backup_conditions() {
	# Checking load average
	la=$(awk -F'[. ]' '{print $1}' /proc/loadavg)
	# i=0
	while [ "$la" -ge "$BACKUP_LA_LIMIT" ]; do
		echo -e "$(date "+%F %T") Load Average $la"
		sleep 60
		la=$(awk -F'[. ]' '{print $1}' /proc/loadavg)
	done
}

# Define download function
download_file() {
	local url=$1
	local destination=$2
	local force=$3

	# Default destination is the curent working directory
	local dstopt=""

	if [ -n "$(echo "$url" | grep -E "\.(gz|gzip|bz2|zip|xz)$")" ]; then
		# When an archive file is downloaded it will be first saved localy
		dstopt="--directory-prefix=$ARCHIVE_DIR"
		local is_archive="true"
		local filename="${url##*/}"
		if [ -z "$filename" ]; then
			echo >&2 "[!] No filename was found in url, exiting ($url)"
			exit 1
		fi
		if [ -n "$force" ] && [ -f "$ARCHIVE_DIR/$filename" ]; then
			rm -f $ARCHIVE_DIR/$filename
		fi
	elif [ -n "$destination" ]; then
		# Plain files will be written to specified location
		dstopt="-O $destination"
	fi
	# check for corrupted archive
	if [ -f "$ARCHIVE_DIR/$filename" ] && [ "$is_archive" = "true" ]; then
		tar -tzf "$ARCHIVE_DIR/$filename" > /dev/null 2>&1
		if [ $? -ne 0 ]; then
			echo >&2 "[!] Archive $ARCHIVE_DIR/$filename is corrupted, redownloading"
			rm -f $ARCHIVE_DIR/$filename
		fi
	fi

	if [ ! -f "$ARCHIVE_DIR/$filename" ]; then
		wget $url -q $dstopt --show-progress --progress=bar:force --limit-rate=3m
	fi

	if [ -n "$destination" ] && [ "$is_archive" = "true" ]; then
		if [ "$destination" = "-" ]; then
			cat "$ARCHIVE_DIR/$filename"
		elif [ -d "$(dirname $destination)" ]; then
			cp "$ARCHIVE_DIR/$filename" "$destination"
		fi
	fi
}

multiphp_count() {
	$BIN/h-list-sys-php plain | wc -l
}

multiphp_versions() {
	local -a php_versions_list
	local php_ver
	if [ "$(multiphp_count)" -gt 0 ]; then
		for php_ver in $($BIN/h-list-sys-php plain); do
			[ ! -d "/etc/php/$php_ver/fpm/pool.d/" ] && continue
			php_versions_list+=($php_ver)
		done
		echo "${php_versions_list[@]}"
	fi
}

multiphp_default_version() {
	# Get system wide default php version (set by update-alternatives)
	local sys_phpversion=$(php -r "echo substr(phpversion(),0,3);")

	# Check if the system php also has php-fpm enabled, otherwise return
	# the most recent php version which does have it installed.
	if [ ! -d "/etc/php/$sys_phpversion/fpm/pool.d/" ]; then
		local all_versions="$(multiphp_versions)"
		if [ -n "$all_versions" ]; then
			sys_phpversion="${all_versions##*\ }"
		fi
	fi

	echo "$sys_phpversion"
}

is_hestia_package() {
	check=false
	for pkg in $1; do
		if [ "$pkg" == "$2" ]; then
			check="true"
		fi
	done
	if [ "$check" != "true" ]; then
		check_result $E_INVALID "$2 package is not controlled by hestiacp"
	fi
}

# run a command as $user with dropped privileges
user_exec() {
	is_object_valid 'user' 'USER' "$user"

	local user_groups=$(id -G "$user")
	user_groups=${user_groups//\ /,}

	setpriv --groups "$user_groups" --reuid "$user" --regid "$user" -- "${@}"
}

# Simple chmod wrapper that skips symlink files after glob expand
no_symlink_chmod() {
	local filemode=$1
	shift

	for i in "$@"; do
		[[ -L ${i} ]] && continue

		chmod "${filemode}" "${i}"
	done
}

format_no_quotes() {
	# Deny list: the `|` are literal members, not separators. Dropping them drops | too.
	exclude="['|\"]"
	if [[ "$1" =~ $exclude ]]; then
		check_result "$E_INVALID" "Invalid $2 contains qoutes (\" or ' or | ) :: $1"
	fi
	is_no_new_line_format "$1"
}

is_username_format_valid() {
	if [[ ! "$1" =~ ^[A-Za-z0-9._%+-]+@[[:alnum:].-]+\.[A-Za-z]{2,63}$ ]] \
		&& [[ ! "$1" =~ ^[A-Za-z0-9._%+-]+(/|\\)[A-Za-z0-9._%+-]+$ ]]; then
		is_string_format_valid "$1" "$2"
	fi
}

# The line is built and checked BEFORE the file is touched, then written whole through a temp file
# and rename: the sed it replaces expanded & and \ inside the value (a plain "Foo & Bar" glued the
# old value into the new one and left the quotes unbalanced). A value that cannot form a
# KEY='VALUE' line is refused, not written. Every matching line is replaced, as before; the
# duplicate collapse stays with syshealth.
change_sys_value() {
	local _key="$1" _value="$2" _conf="$HESTIA/conf/hestia.conf" _tmp _prev_trap
	# check_result exits; the returns behind it keep the write unreachable even where it does not
	case "$_value" in
		*\'* | *$'\n'*)
			check_result "$E_INVALID" "invalid value for $_key: a quote or a line break cannot be stored"
			return "$E_INVALID"
			;;
	esac
	# SIGKILL never runs the trap, so a killed run leaves its temp in the instance directory. Only ones
	# older than five minutes, so a concurrent writer keeps its own. -H because $HESTIA/conf is a
	# symlink and find does not follow one given as its argument.
	find -H "$(dirname "$_conf")" -maxdepth 1 -name "$(basename "$_conf").??????" -mmin +5 -delete 2> /dev/null
	_tmp=$(mktemp "$_conf.XXXXXX") || {
		check_result "$E_UPDATE" "hestia.conf: cannot create a temp file next to it"
		return "$E_UPDATE"
	}
	# the temp file has one owner, this trap, until the rename; the caller's EXIT trap is kept and put back
	_prev_trap=$(trap -p EXIT)
	# shellcheck disable=SC2064 # expand now on purpose: _tmp is local and gone when the trap fires
	trap "rm -f '$_tmp'" EXIT
	# one subshell behind one redirect: a failed write (full disk) is rc 1 here, not a truncated file later.
	# Every line of the key is replaced, quoted or not; collapsing duplicates stays with syshealth.
	# Mode and owner from the file, not the umask: the seed sets 660.
	if (
		found=no
		while IFS= read -r line || [ -n "$line" ]; do
			if [[ $line == "$_key="* ]]; then
				printf "%s='%s'\n" "$_key" "$_value" || exit 1
				found=yes
			else
				printf '%s\n' "$line" || exit 1
			fi
		done < "$_conf"
		[ "$found" = yes ] || printf "%s='%s'\n" "$_key" "$_value"
	) > "$_tmp" \
		&& chmod --reference="$_conf" "$_tmp" \
		&& { [ "$(stat -c %u:%g "$_conf")" = "$(stat -c %u:%g "$_tmp")" ] || chown --reference="$_conf" "$_tmp"; } \
		&& mv -f "$_tmp" "$_conf"; then
		eval "${_prev_trap:-trap - EXIT}"
	else
		rm -f "$_tmp"
		eval "${_prev_trap:-trap - EXIT}"
		check_result "$E_UPDATE" "hestia.conf was not written: $_key"
		return "$E_UPDATE"
	fi
}

# Delete a hestia.conf key line entirely (vs change_sys_value which sets it empty).
# Used by the web-model switch so a target model that never sets a key ends up
# byte-identical to a fresh install of that model, not carrying a present-but-empty line.
clear_sys_value() {
	sed -i "/^$1=/d" "$HESTIA/conf/hestia.conf"
}

# One token in a comma-separated hestia.conf key, order kept, never doubled. Named as token_fn in the
# registry; never a bare sed, an unanchored one once rewrote DB_MARIADB_SYSTEM.
# rc: on a success path the caller fails the command with it, the status is part of the job. On an
# exit path it is dropped, so a write problem does not turn "not installed" into a different error.
sys_key_token_set() {
	local key="$1" op="$2" tok="$3" cur out=() t
	[ -n "$key" ] && [ -n "$tok" ] || return 1
	cur=$(grep -m1 "^$key=" "$HESTIA/conf/hestia.conf" 2> /dev/null)
	cur=${cur#*=}
	cur=${cur#[\"\']}
	cur=${cur%[\"\']}
	IFS=',' read -r -a out <<< "$cur"
	case "$op" in
		add)
			for t in "${out[@]}"; do [ "$t" = "$tok" ] && {
				printf '%s\n' "$cur"
				return 0
			}; done
			out+=("$tok")
			;;
		remove)
			local keep=()
			for t in "${out[@]}"; do [ "$t" = "$tok" ] || [ -z "$t" ] || keep+=("$t"); done
			out=("${keep[@]}")
			;;
		*) return 1 ;;
	esac
	cur=$(
		IFS=','
		echo "${out[*]}"
	)
	cur=${cur#,}
	change_sys_value "$key" "$cur" && printf '%s\n' "$cur"
}

# MariaDB status keys from the installed package, not from an argument: version from dpkg, source read
# off the version string and named in the recipe's own words, so recipe and status agree.
# The version string and not apt-cache policy: policy describes the repo configured NOW, the string
# travels with the package.
mariadb_status_record() {
	local v src
	v=$(dpkg-query -W -f='${Version}' mariadb-server 2> /dev/null) || v=''
	if [ -z "$v" ]; then
		change_sys_value "DB_MARIADB_SYSTEM" ""
		change_sys_value "DB_MARIADB_VERSION" ""
		return 0
	fi
	case "$v" in *maria*) src="mariadb_repo" ;; *) src="os_default" ;; esac
	v=${v#*:}
	v=$(printf '%s' "$v" | grep -oE '^[0-9]+\.[0-9]+')
	change_sys_value "DB_MARIADB_SYSTEM" "$src" && change_sys_value "DB_MARIADB_VERSION" "$v"
}

# The composer channel from the box: the upstream phar in /usr/local/bin shadows the OS package on
# PATH, so it decides when both exist (a switch in h-update-sys-composer removes the other afterwards).
# The recipe's own words (source_default: os_package | upstream_installer), because that field IS the
# channel; the same rule as DB_MARIADB_SYSTEM, the opposite of PHP_SOURCE. A vocabulary is contract from 1d.
composer_status_record() {
	local src=''
	if [ -x /usr/local/bin/composer ]; then
		src='upstream_installer'
	elif dpkg -s composer > /dev/null 2>&1; then
		src='os_package'
	fi
	change_sys_value "COMPOSER_SYSTEM" "$src"
}

# The wp-cli version as the installed phar reports it, not the manifest pin: the pin is intent,
# the key is status. wp-cli refuses root without --allow-root; no phar or no answer is an empty key.
wpcli_status_record() {
	local v=''
	[ -x /usr/local/bin/wp ] && v=$(timeout 30 /usr/local/bin/wp cli version --allow-root 2> /dev/null | awk '{print $2}')
	change_sys_value "WPCLI_SYSTEM" "$v"
}

# ── Web-model maintenance freeze ──────────────────────────────────────
# A live web-model switch (h-add-sys-nginx/-apache2, h-delete-sys-nginx/-apache2)
# holds an exclusive lock for the whole operation. Domain-config mutators acquire it
# (bounded wait) so nothing changes web/mail state mid-flip; reload chokepoints
# (h-restart-web/-proxy/-service, apache logrotate, LE renewal) defer while it is held.
WEB_MODEL_LOCK="/run/hestia/web-model.lock"

# True (0) if a switch currently holds the freeze - for reload chokepoints to defer.
# The switch owner and any subprocess it spawns carry HESTIA_WEB_LOCK_HELD and are
# never frozen by their own lock (the switch does its own controlled restarts).
web_freeze_held() {
	[ "${HESTIA_WEB_LOCK_HELD:-}" = "1" ] && return 1
	[ -e "$WEB_MODEL_LOCK" ] || return 1
	if flock -n -x "$WEB_MODEL_LOCK" -c true > /dev/null 2>&1; then
		return 1 # acquired freely -> nobody holds it
	fi
	return 0 # busy -> a switch holds it
}

# Acquire the freeze (reentrant + bounded). Reentrant: the owner and its children
# skip re-acquiring. Bounded: never an unbounded block - a hung switch must not
# freeze every domain op + cron forever. Holds the lock via WEB_LOCK_FD until the
# process exits or web_lock_release is called. Returns non-zero on timeout.
web_lock_acquire() {
	[ "${HESTIA_WEB_LOCK_HELD:-}" = "1" ] && return 0
	local timeout="${1:-300}"
	mkdir -p /run/hestia
	exec {WEB_LOCK_FD}>> "$WEB_MODEL_LOCK"
	if ! flock -x -w "$timeout" "$WEB_LOCK_FD"; then
		exec {WEB_LOCK_FD}>&-
		echo "Error: a web-model switch is in progress; timed out after ${timeout}s waiting for the lock." >&2
		return 1
	fi
	export HESTIA_WEB_LOCK_HELD=1
	# Who owns it, so the release can check instead of relying on which variable happens
	# not to be exported.
	export HESTIA_WEB_LOCK_PID=$$
	return 0
}

# Release the freeze early (otherwise it drops on process exit).
#
# Only the acquiring process may release it: a child inherits the lock as held and would otherwise
# unlock the parent mid-operation. Used to hold only because WEB_LOCK_FD is not exported.
web_lock_release() {
	[ "${HESTIA_WEB_LOCK_PID:-}" = "$$" ] || return 0
	[ -n "${WEB_LOCK_FD:-}" ] || return 0
	flock -u "$WEB_LOCK_FD" 2> /dev/null
	exec {WEB_LOCK_FD}>&- 2> /dev/null
	unset WEB_LOCK_FD HESTIA_WEB_LOCK_HELD HESTIA_WEB_LOCK_PID
}

# SFTP jail membership: the sftp-jailed group is the sshd chroot selector and
# the pam_namespace scope; the jail is built per session (h-add-sys-sftp-jail).
add_chroot_jail() {
	local user=$1
	getent group sftp-jailed > /dev/null 2>&1 || groupadd sftp-jailed
	usermod -aG sftp-jailed "$user" > /dev/null 2>&1
}

delete_chroot_jail() {
	gpasswd -d "$1" sftp-jailed > /dev/null 2>&1 || true
}

# The one sshd "Subsystem sftp" line. The sftp-server binary, so a jailbash user's sftp runs in bwrap;
# no longer a decision, both jails are on every box and cannot be removed.
# /usr/lib/sftp-server is the compat symlink shipped on all four targets, and jailbash binds /usr
# read-only, so it resolves inside the jail too. Kept distinct from the distro line.
# Prints "changed" so the caller restarts; validating stays with the caller.
jail_sshd_subsystem_apply() {
	local config='/etc/ssh/sshd_config' want='/usr/lib/sftp-server' _tmp _prev_trap
	# Checked before anything is written: rebuilding a file that is already right moved the distro line
	# to the bottom every run and cost an sshd restart each time.
	awk -v w="Subsystem sftp $want" '
		/^Match[[:space:]]/ || /^# Hestia SFTP Chroot$/ { exit (ok ? 0 : 1) }
		$0 == w { ok = 1 }
		END { exit (ok ? 0 : 1) }
	' "$config" && return 0
	# Temp file NEXT TO the target so the rename is atomic, mode and owner from the target because sshd
	# refuses a config with wrong permissions. A truncated sshd_config is a box nobody logs into.
	# A killed run leaves its temp behind; sshd ignores it, but it would pile up in /etc/ssh unseen.
	rm -f "$config".?????? 2> /dev/null || true
	_tmp=$(mktemp "$config.XXXXXX") || return 1
	_prev_trap=$(trap -p EXIT)
	# shellcheck disable=SC2064 # expand now on purpose: _tmp is local and gone when the trap fires
	trap "rm -f '$_tmp'" EXIT
	# The line must stand in the GLOBAL section: everything after the first Match belongs to that block,
	# where sshd ignores a Subsystem and `sshd -t` stays quiet about it. So drop every such line and
	# write one before the first Match, or before the marker comment, which belongs to the block it
	# introduces and whose own finder looks for the Match lines that FOLLOW it.
	awk -v line="Subsystem sftp $want" '
		/^Subsystem[[:space:]]+sftp[[:space:]]/ { next }
		!placed && (/^Match[[:space:]]/ || /^# Hestia SFTP Chroot$/) { print line; placed = 1 }
		{ print }
		END { if (!placed) print line }
	' "$config" > "$_tmp" || {
		rm -f "$_tmp"
		eval "${_prev_trap:-trap - EXIT}"
		return 1
	}
	if cmp -s "$_tmp" "$config"; then
		rm -f "$_tmp"
		eval "${_prev_trap:-trap - EXIT}"
		return 0
	fi
	if chmod --reference="$config" "$_tmp" \
		&& { [ "$(stat -c %u:%g "$config")" = "$(stat -c %u:%g "$_tmp")" ] || chown --reference="$config" "$_tmp"; } \
		&& mv -f "$_tmp" "$config"; then
		eval "${_prev_trap:-trap - EXIT}"
	else
		rm -f "$_tmp"
		eval "${_prev_trap:-trap - EXIT}"
		return 1
	fi
	echo changed
}

# Co-maintain the SSH AllowUsers allowlist. Opt-in: acts only if a line exists
# (installer seeds one commented). Touches only the $user token (base before @), so
# operator entries + the commented/active state survive. add=append, del=drop; sshd -t
# rollback, reload only when active, re-comments rather than leave an active line empty.
manage_sshd_allowusers() {
	local action=$1 user=$2
	local config='/etc/ssh/sshd_config'
	[ -f "$config" ] || return 0

	# First managed AllowUsers line (commented or active); none -> nothing to maintain.
	# The '#' must sit DIRECTLY on the keyword (#AllowUsers) - that is sshd's own
	# commented-directive form. We must NOT match a prose line like "# AllowUsers is a
	# login allowlist..." (space after the #), or we would tokenise the sentence and
	# append the user to it, mangling the comment and never touching the real directive.
	local lineno
	lineno=$(grep -niE '^[[:space:]]*#?AllowUsers([[:space:]]|$)' "$config" \
		| head -n1 | cut -d: -f1)
	[ -n "$lineno" ] || return 0

	local line
	line=$(sed -n "${lineno}p" "$config")

	# commented if the first non-space char is '#'
	local prefix=''
	[[ "$line" =~ ^[[:space:]]*# ]] && prefix='#'

	# tokens after the keyword (may be empty); drop $user's, re-add on 'add'
	local rest
	rest=$(echo "$line" | sed -E 's/^[[:space:]]*#?AllowUsers[[:space:]]*//')
	local -a tokens=() kept=()
	read -r -a tokens <<< "$rest"
	local t present=0
	for t in "${tokens[@]}"; do
		[ "${t%%@*}" = "$user" ] && present=1
	done
	# add of an already-listed user is a true no-op (keep order, skip rewrite/reload)
	[ "$action" = 'add' ] && [ "$present" = 1 ] && return 0
	for t in "${tokens[@]}"; do
		[ "${t%%@*}" = "$user" ] || kept+=("$t")
	done
	[ "$action" = 'add' ] && kept+=("$user")

	# lockout guard: never leave an ACTIVE AllowUsers with zero tokens
	if [ -z "$prefix" ] && [ "${#kept[@]}" -eq 0 ]; then
		prefix='#'
		echo "WARNING: AllowUsers would be empty and active - re-commented to avoid lockout."
	fi

	local newline="${prefix}AllowUsers"
	[ "${#kept[@]}" -gt 0 ] && newline="$newline ${kept[*]}"
	[ "$newline" = "$line" ] && return 0

	# swap the line on a temp copy, keep it only if sshd accepts the whole config
	# (ENVIRON, not -v, so awk doesn't reinterpret backslashes in the value)
	local tmp
	tmp=$(mktemp) || return 0
	newline_env="$newline" awk -v n="$lineno" \
		'NR==n{print ENVIRON["newline_env"]; next} {print}' "$config" > "$tmp"
	if ! /usr/sbin/sshd -t -f "$tmp" > /dev/null 2>&1; then
		echo "WARNING: sshd rejected the AllowUsers update - left unchanged."
		rm -f "$tmp"
		return 0
	fi
	cat "$tmp" > "$config"
	rm -f "$tmp"

	# reload only when the line is active (commented = inert, no reload needed)
	[ -z "$prefix" ] && systemctl reload ssh > /dev/null 2>&1
	return 0
}
