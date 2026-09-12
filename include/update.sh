#!/bin/bash
# The update building blocks: conditions and actions as named functions, with a dispatcher over them.
# Runs from the NEW tree against a box that may still be several releases old, so it must presuppose
# nothing the box's own version brings: every helper it uses comes from this tarball, and the registry
# it asks is the one shipped beside it.
#
# THE BOUNDARY - a guard reports, an action writes. Phase 1 set the first half: a guard verifies an
# artefact and never fills it, because a repair hidden inside a check makes the check agree with
# itself (#945, 1d-3). This library is the first place in the tree that does the opposite on purpose.
# The two must not drift into each other: nothing in bin/h-check-sys-smoke may call an upd_act_*, and
# nothing here may decide that something "looks healthy enough" and leave it alone quietly - an action
# either changes the box or says why it did not. Every guard that verifies an artefact from phase 1c
# or 1d carries a pointer back to this paragraph in its comment.
#
# ONE ROOT. $HESTIA, as in every other file here. An update unpacks the new release OVER the install
# tree and only then plays the manifest, so by the time anything in this library runs, the tree it was
# read from and the box's install root are the same directory. Nothing here may introduce a second one:
# a library that can be pointed at a foreign tree is a library that can be pointed at the wrong tree,
# and the ordering that would have required it (deriving the entry list before the overlay) is gone.
#
# SOURCING - two files, and deliberately not more. What a manifest may CALL lives elsewhere in
# include/, and function_call finds the defining file rather than making this header grow.

# shellcheck source=/usr/local/hestia/include/main.sh
source "${HESTIA:-/usr/local/hestia}/include/main.sh"
# shellcheck source=/usr/local/hestia/include/sysreg.sh
source "${HESTIA:-/usr/local/hestia}/include/sysreg.sh"

#----------------------------------------------------------#
#                       Conditions                         #
#----------------------------------------------------------#
# Every condition is a predicate: rc 0 true, rc 1 false, rc 2 the entry is wrong. Silent on rc 0 and
# rc 1 - in a merged manifest most conditions are legitimately false for a given box, and a probe that
# logs every false turns the log into noise nobody reads any more (the #925 class). rc 2 is loud,
# because it is never the box's doing.
#
# WHOSE ANSWER IS IT - the BOX decides the value, the TREE decides whether the name is a name at all.
# A key absent from hestia.conf is an ordinary box fact and reads as empty; a key absent from the
# registry shipped in this tarball cannot be a legitimate reference, because manifest and registry
# travel together, so it is a misspelling and rc 2. Without that split a typo in an entry would make
# its condition false for ever and the entry would simply never apply - the same silent failure E24
# removed one level down, at the value instead of the name.

# The box value of a hestia.conf key. Anchored grep, never `source`: a key name is a prefix of another
# one often enough to matter (DB_SYSTEM in DB_MARIADB_SYSTEM, #983), and a config file must never be
# executed (#955).
upd_key_value() {
	local conf="${HESTIA:-/usr/local/hestia}/conf/hestia.conf"
	[ -f "$conf" ] || return 1
	sed -n "s/^$1='\\(.*\\)'\$/\\1/p" "$conf" | head -1
}

# The name check every key condition runs first.
upd_key_known() {
	sysreg_class "$1" > /dev/null 2>&1 && return 0
	echo "update: '$1' is no key in this tree's registry - a manifest entry cannot reference it" >&2
	return 2
}

# key_empty KEY - absent and empty are the same state and have ONE name (E7); key_missing is refused
# by the dispatcher with a pointer to this one rather than silently accepted as a synonym.
upd_cond_key_empty() {
	upd_key_known "$1" || return 2
	[ -z "$(upd_key_value "$1")" ]
}

# key_is KEY VALUE - the value must be one the key may carry, or the entry is wrong (E24). This is the
# check that makes a misspelled value loud instead of for ever false.
upd_cond_key_is() {
	upd_key_known "$1" || return 2
	sysreg_value_ok "$1" "$2" || {
		echo "update: '$2' is not a value $1 may carry" >&2
		return 2
	}
	[ "$(upd_key_value "$1")" = "$2" ]
}

# key_has_token KEY TOKEN - only for a key the registry marks as a token list, and the membership is
# decided by the same splitting the writer uses, never by a generic comma split here (E8).
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

# path_exists PATH - file, directory or symlink; the one type test a manifest author should not have
# to spell out three ways.
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

# package_installed NAME - installed means dpkg says ok/installed, not "a file of that name is around":
# a half-configured package (dpkg interrupted) is deliberately NOT installed here, so an action that
# depends on it runs again rather than assuming the first run finished.
upd_cond_package_installed() {
	local st
	[ -n "$1" ] || {
		echo "update: package_installed needs a name" >&2
		return 2
	}
	st=$(dpkg-query -W -f='${db:Status-Abbrev}' "$1" 2> /dev/null) || return 1
	case "$st" in ii*) return 0 ;; *) return 1 ;; esac
}

# file_differs REL TARGET - the box file against the one shipped in this tree (E9's eighth type, for
# the re-applications that reapply_outside_tree used to do unconditionally). An absent target counts
# as different: that is the case the copy action exists for.
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
#                        Actions                           #
#----------------------------------------------------------#
# An action does one thing and says whether it did it: rc 0 changed or already so, rc 1 it could not.
# It does NOT validate its own arguments - upd_action does that once for the whole entry, so there is
# one place to read the rules instead of ten. And it is not clever on failure: it stops, and the
# executor names the entry and the two ways out. Recovery is a human with the run directory, not a
# state machine in here.
#
# ALREADY RIGHT MEANS DO NOT WRITE. Rewriting a file that is already correct costs a service restart
# every run, and it moves the file's mtime - which is what check_config_loaded compares a unit's start
# time against (#1007), so a needless write can turn a healthy guard red. Every action checks first.
#
# SURVIVING AN ABORT (E14) is not a guard either, it is the simpler shape: a file writer works through
# a temp file next to the target and renames, so a kill leaves the old file or the new one and never a
# half of either; a package action runs `dpkg --configure -a` first, so an interrupted dpkg from a
# previous run is finished rather than tripped over. Repeating a run converges. Measured per type.

# upd_action_reversible TYPE - one word, and its only job is to answer the question a human asks after
# an abort: can this be taken back by putting files back, or not. "no" is not a refusal, it is the line
# after which rollback means restoring the run's tarball instead of undoing entries.
upd_action_reversible() {
	case "$1" in
		key_set | key_clear | token_add | token_remove | file_copy | package_install) echo yes ;;
		path_delete | package_remove | service_restart | function_call) echo no ;;
		*) return 1 ;;
	esac
}

# The functions a manifest may call. Small on purpose: this is the difference between a vocabulary and
# arbitrary code, and the entries that need it are known (the re-applications E15 moves out of
# reapply_outside_tree).
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

# file_copy REL TARGET [MODE] - a file from this tree onto the box. Temp file next to the target, mode
# and owner set before the rename, so the target is never briefly world-readable and never half.
upd_act_file_copy() {
	local src="${HESTIA:-/usr/local/hestia}/$1" dst="$2" mode="${3:-}" tmp prev
	cmp -s "$src" "$dst" 2> /dev/null && return 0
	# A kill cannot run the trap, so a killed run leaves its temp file behind - measured, 114 of them in
	# 40 runs. Older than five minutes means "not a run that is happening right now", which is the whole
	# distinction needed here: a live writer keeps its file for a fraction of a second.
	find -H "$(dirname "$dst")" -maxdepth 1 -name "$(basename "$dst").??????" -mmin +5 -delete 2> /dev/null
	tmp=$(mktemp "$dst.XXXXXX") || return 1
	prev=$(trap -p EXIT)
	# shellcheck disable=SC2064  # expand now: tmp is local and gone when the trap fires
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

# The callable functions live in include/ files this library does not source - and it should not start
# sourcing half the tree for them. So the defining file is FOUND, the same way sysreg_check already
# locates a token_fn: one grep over include/, no second list that says where each function lives.
upd_act_function_call() {
	local fn="$1" src
	shift
	if ! declare -F "$fn" > /dev/null 2>&1; then
		src=$(grep -lE "^${fn}\(\) \{" "${HESTIA:-/usr/local/hestia}"/include/*.sh 2> /dev/null | head -1)
		[ -n "$src" ] || return 1
		# shellcheck disable=SC1090  # the file is the one that defines the allow-listed name
		source "$src" || return 1
		declare -F "$fn" > /dev/null 2>&1 || return 1
	fi
	"$fn" "$@"
}

# dpkg --configure -a first: an apt run killed halfway leaves the box in a state where the next apt
# refuses, and an update that stops there for a reason two runs old is the worst kind of puzzle.
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
#                       Dispatcher                         #
#----------------------------------------------------------#
# Type name -> function, and an unknown type is an ERROR, never a skip. A skipped entry looks exactly
# like an entry whose condition was false, so a typo in a type name would make the entry vanish without
# a word - the same silent failure the key and value checks remove one level down.

# upd_condition TYPE ARGS... - rc 0 true, rc 1 false, rc 2 the entry is wrong.
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

# upd_action TYPE ARGS... - the ONE place an entry is validated. The actions behind it are plain
# writers; every rule a manifest author can break is stated here and nowhere else.
# rc 0 done, rc 1 the action failed, rc 2 the entry is wrong.
upd_action() {
	local t="$1" fn
	shift
	case "$t" in
		key_set)
			[ $# -eq 2 ] || {
				echo "update: key_set needs a key and a value" >&2
				return 2
			}
			upd_key_known "$1" || return 2
			# Only a system key. Not tidiness: since #1006 the daily repair writes every operator key's
			# registry default back, so a value an update put there is gone by 04:40 the next morning.
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
			# Not a policy, just not shooting ourselves: an empty argument would delete the working
			# directory's contents and "/" needs no explanation.
			case "${1:-}" in "" | / | /*/..*)
				echo "update: path_delete needs a real path" >&2
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
