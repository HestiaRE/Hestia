#!/bin/bash
# Update building blocks: conditions and actions behind one dispatcher, so a manifest entry is data.
# Boundary: a guard reports, an action writes. No upd_act_* in the smoke.
# One root, $HESTIA. The release is unpacked over the install tree before any of this runs.

# shellcheck source=/usr/local/hestia/include/main.sh
source "${HESTIA:-/usr/local/hestia}/include/main.sh"
# shellcheck source=/usr/local/hestia/include/sysreg.sh
source "${HESTIA:-/usr/local/hestia}/include/sysreg.sh"

#----------------------------------------------------------#
# Conditions #
#----------------------------------------------------------#
# rc 0 true, rc 1 false, rc 2 entry is wrong. True and false silent: most are legitimately false.
# Box decides the value, tree decides the name. Unknown in hestia.conf is a box fact, unknown in the
# registry is a typo.

# Anchored, never sourced: key names are prefixes of each other, and a config is not a script.
upd_key_value() {
	local conf="${HESTIA:-/usr/local/hestia}/conf/hestia.conf"
	[ -f "$conf" ] || return 1
	sed -n "s/^$1='\\(.*\\)'\$/\\1/p" "$conf" | head -1
}

# Name check, first in every key condition.
upd_key_known() {
	sysreg_class "$1" > /dev/null 2>&1 && return 0
	echo "update: '$1' is no key in this tree's registry - a manifest entry cannot reference it" >&2
	return 2
}

# Absent == empty, one name.
upd_cond_key_empty() {
	upd_key_known "$1" || return 2
	[ -z "$(upd_key_value "$1")" ]
}

# Value outside the vocabulary: wrong entry, not false condition.
upd_cond_key_is() {
	upd_key_known "$1" || return 2
	sysreg_value_ok "$1" "$2" || {
		echo "update: '$2' is not a value $1 may carry" >&2
		return 2
	}
	[ "$(upd_key_value "$1")" = "$2" ]
}

# Token lists only; the registry says which.
upd_cond_key_has_token() {
	local cur tok
	upd_key_known "$1" || return 2
	[ "$(sysreg_tokens "$1")" = yes ] || {
		echo "update: $1 is not a token list - key_has_token does not apply" >&2
		return 2
	}
	cur=$(upd_key_value "$1")
	for tok in ${cur//,/ }; do [ "$tok" = "$2" ] && return 0; done
	return 1
}

# File, directory or symlink in one type.
upd_cond_path_exists() {
	[ -n "$1" ] || {
		echo "update: path_exists needs a path" >&2
		return 2
	}
	[ -e "$1" ] || [ -L "$1" ]
}

# command_exists NAME
upd_cond_command_exists() {
	[ -n "$1" ] || {
		echo "update: command_exists needs a name" >&2
		return 2
	}
	command -v "$1" > /dev/null 2>&1
}

# Half-configured counts as not installed, so a dependent action runs again.
upd_cond_package_installed() {
	local st
	[ -n "$1" ] || {
		echo "update: package_installed needs a name" >&2
		return 2
	}
	st=$(dpkg-query -W -f='${db:Status-Abbrev}' "$1" 2> /dev/null) || return 1
	case "$st" in ii*) return 0 ;; *) return 1 ;; esac
}

# Absent target counts as different: that is what the copy action is for.
upd_cond_file_differs() {
	local src="${HESTIA:-/usr/local/hestia}/$1"
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: file_differs needs a tree-relative source and a target" >&2
		return 2
	}
	[ -f "$src" ] || {
		echo "update: $1 is not a file in this tree" >&2
		return 2
	}
	[ -f "$2" ] || return 0
	! cmp -s "$src" "$2"
}

#----------------------------------------------------------#
# Actions #
#----------------------------------------------------------#
# rc 0 done or already so, rc 1 could not. Validated once by upd_action; a failure stops.
# Already right means do not write: a needless rewrite restarts a service and moves an mtime a guard reads.
# Abort-safe: temp beside the target then rename; `dpkg --configure -a` first.

# Can this be taken back by putting files back. "no" is where rollback becomes "restore the tarball".
upd_action_reversible() {
	case "$1" in
		key_set | key_clear | token_add | token_remove | file_copy | package_install) echo yes ;;
		path_delete | package_remove | service_restart | function_call) echo no ;;
		*) return 1 ;;
	esac
}

# The line between a vocabulary and arbitrary code.
UPDATE_CALLABLE="deploy_hestia_sudoers proc_hardening_apply customer_php_limit_apply login_defs_guard"

upd_act_key_set() {
	[ "$(upd_key_value "$1")" = "$2" ] && return 0
	change_sys_value "$1" "$2"
}

upd_act_key_clear() {
	[ -z "$(upd_key_value "$1")" ] && return 0
	clear_sys_value "$1"
}

upd_act_token_add() {
	upd_cond_key_has_token "$1" "$2" && return 0
	"$(sysreg_token_fn "$1")" "$1" add "$2" > /dev/null
}

upd_act_token_remove() {
	upd_cond_key_has_token "$1" "$2" || return 0
	"$(sysreg_token_fn "$1")" "$1" remove "$2" > /dev/null
}

# Mode and owner before the rename, never briefly world-readable.
upd_act_file_copy() {
	local src="${HESTIA:-/usr/local/hestia}/$1" dst="$2" mode="${3:-}" tmp prev
	cmp -s "$src" "$dst" 2> /dev/null && return 0
	# SIGKILL skips the trap; five minutes separates a dead run's temp from a live writer's.
	find -H "$(dirname "$dst")" -maxdepth 1 -name "$(basename "$dst").??????" -mmin +5 -delete 2> /dev/null
	tmp=$(mktemp "$dst.XXXXXX") || return 1
	prev=$(trap -p EXIT)
	# shellcheck disable=SC2064 # expand now: tmp is local and gone when the trap fires
	trap "rm -f '$tmp'" EXIT
	if cat "$src" > "$tmp" \
		&& { [ -z "$mode" ] || chmod "$mode" "$tmp"; } \
		&& { [ -n "$mode" ] || ! [ -f "$dst" ] || chmod --reference="$dst" "$tmp"; } \
		&& { ! [ -f "$dst" ] || chown --reference="$dst" "$tmp"; } \
		&& mv -f "$tmp" "$dst"; then
		eval "${prev:-trap - EXIT}"
		return 0
	fi
	rm -f "$tmp"
	eval "${prev:-trap - EXIT}"
	return 1
}

upd_act_path_delete() {
	[ -e "$1" ] || [ -L "$1" ] || return 0
	rm -rf -- "$1"
}

# File found, not listed: a second list goes stale.
upd_act_function_call() {
	local fn="$1" src
	shift
	if ! declare -F "$fn" > /dev/null 2>&1; then
		src=$(grep -lE "^${fn}\(\) \{" "${HESTIA:-/usr/local/hestia}"/include/*.sh 2> /dev/null | head -1)
		[ -n "$src" ] || return 1
		# shellcheck disable=SC1090 # the file is the one that defines the allow-listed name
		source "$src" || return 1
		declare -F "$fn" > /dev/null 2>&1 || return 1
	fi
	"$fn" "$@"
}

# A halfway-killed apt makes the next one refuse, for a reason two runs old.
upd_act_package_install() {
	upd_cond_package_installed "$1" && return 0
	dpkg --configure -a > /dev/null 2>&1
	DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confold" install "$1" > /dev/null 2>&1
}

upd_act_package_remove() {
	upd_cond_package_installed "$1" || return 0
	dpkg --configure -a > /dev/null 2>&1
	DEBIAN_FRONTEND=noninteractive apt-get -y purge "$1" > /dev/null 2>&1
}

upd_act_service_restart() {
	systemctl restart "$1" > /dev/null 2>&1
}

#----------------------------------------------------------#
# Dispatcher #
#----------------------------------------------------------#
# Unknown type is an error, never a skip: a skip is indistinguishable from a false condition.

# rc 0 true, rc 1 false, rc 2 the entry is wrong.
upd_condition() {
	local t="$1"
	shift
	case "$t" in
		key_missing)
			echo "update: there is no condition 'key_missing' - absent and empty are one state, use key_empty" >&2
			return 2
			;;
		key_empty | key_is | key_has_token | path_exists | command_exists | package_installed | file_differs)
			"upd_cond_$t" "$@"
			;;
		*)
			echo "update: unknown condition type '$t'" >&2
			return 2
			;;
	esac
}

# The one place an entry is validated. rc 0 done, rc 1 action failed, rc 2 entry is wrong.
upd_action() {
	local t="$1" fn _p
	shift
	case "$t" in
		key_set)
			[ $# -eq 2 ] || {
				echo "update: key_set needs a key and a value" >&2
				return 2
			}
			upd_key_known "$1" || return 2
			# System keys only: the daily repair overwrites an operator key overnight.
			[ "$(sysreg_class "$1")" = system ] || {
				echo "update: $1 is an operator key - an update may not set it (the daily repair owns it)" >&2
				return 2
			}
			sysreg_value_ok "$1" "$2" || {
				echo "update: '$2' is not a value $1 may carry" >&2
				return 2
			}
			;;
		key_clear)
			[ $# -eq 1 ] || {
				echo "update: key_clear needs a key" >&2
				return 2
			}
			upd_key_known "$1" || return 2
			[ "$(sysreg_class "$1")" = system ] || {
				echo "update: $1 is an operator key - an update may not clear it" >&2
				return 2
			}
			;;
		token_add | token_remove)
			[ $# -eq 2 ] || {
				echo "update: $t needs a key and a token" >&2
				return 2
			}
			upd_key_known "$1" || return 2
			[ "$(sysreg_tokens "$1")" = yes ] || {
				echo "update: $1 is not a token list" >&2
				return 2
			}
			;;
		file_copy)
			[ $# -ge 2 ] && [ -n "$1" ] && [ -n "$2" ] || {
				echo "update: file_copy needs a tree-relative source and a target" >&2
				return 2
			}
			[ -f "${HESTIA:-/usr/local/hestia}/$1" ] || {
				echo "update: $1 is not a file in this tree" >&2
				return 2
			}
			;;
		path_delete)
			# Absolute, not only slashes, ".." as a component: a glob let "/..", "//" and relative paths
			# through. "/etc/foo..bar" stays allowed.
			_p="${1:-}"
			case "$_p" in /*) ;; *)
				echo "update: path_delete needs an absolute path, got '$_p'" >&2
				return 2
				;;
			esac
			while [ "${_p%/}" != "$_p" ]; do _p="${_p%/}"; done
			[ -n "$_p" ] || {
				echo "update: path_delete refuses '$1' - that is the root" >&2
				return 2
			}
			case "/${_p#/}/" in */../*)
				echo "update: path_delete refuses '$1' - '..' as a path component" >&2
				return 2
				;;
			esac
			;;
		function_call)
			[ -n "${1:-}" ] || {
				echo "update: function_call needs a name" >&2
				return 2
			}
			for fn in $UPDATE_CALLABLE; do [ "$fn" = "$1" ] && break; done
			[ "$fn" = "$1" ] || {
				echo "update: '$1' is not a function a manifest may call" >&2
				return 2
			}
			;;
		package_install | package_remove | service_restart)
			[ -n "${1:-}" ] || {
				echo "update: $t needs a name" >&2
				return 2
			}
			;;
		*)
			echo "update: unknown action type '$t'" >&2
			return 2
			;;
	esac
	"upd_act_$t" "$@"
}
