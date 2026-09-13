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

# rc 0 done, rc 1 action failed, rc 2 the entry is wrong.
upd_action() {
	local t="$1"
	upd_action_check "$@" || return 2
	shift
	"upd_act_$t" "$@"
}

# The one place an entry is validated, and the only one that may run without writing: the derivation
# checks a manifest it must not execute. rc 0 sound, rc 2 the entry is wrong.
upd_action_check() {
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
}

#----------------------------------------------------------#
# Manifests #
#----------------------------------------------------------#
# An entry carries exactly ONE action, so its reversibility IS its action's. "Erst A, dann B" are two
# entries with `nach`; a bundle would drag a reversible part behind the line for its irreversible half.
# An entry may declare itself less reversible than its action, never more: only the upper bound is a lie.
# Identity is version/id. Nothing here writes: the derivation reads the tree and the box.

UPDATE_DIR="${HESTIA:-/usr/local/hestia}/share/updates"

# Entries of the last scan, one JSON object per line. Global so scan, check and plan share one read.
UPD_ENTRIES=()

# The tag carries the v, the manifest never does.
upd_version_norm() { echo "${1#v}"; }

# By version, never by string: 0.10 sorts before 0.9 alphabetically.
upd_version_le() {
	local a b
	a=$(upd_version_norm "$1")
	b=$(upd_version_norm "$2")
	[ "$a" = "$b" ] && return 0
	[ "$(printf '%s\n%s\n' "$a" "$b" | sort -V | head -1)" = "$a" ]
}

# No directory means no manifests. That is the state until the first release ships one, not an error.
# A name that is not a version is refused, never skipped: sort -V would quietly sort it out of range
# and the file would be missing from every plan without a word.
upd_manifest_files() {
	local target="$1" f v out=""
	[ -d "$UPDATE_DIR" ] || return 0
	for f in "$UPDATE_DIR"/*.json; do
		[ -f "$f" ] || continue
		v=$(basename "$f" .json)
		case "$v" in [0-9]*.[0-9]*) ;; *)
			echo "update: $f is not named after a version" >&2
			return 2
			;;
		esac
		upd_version_le "$v" "$target" && out="$out$v	$f"$'\n'
	done
	printf '%s' "$out" | sort -V | cut -f2
}

# JSON fields to the argv each building block takes. One place, so a renamed field is one edit.
# A missing field becomes an empty argument and the block itself names what it wanted.
UPD_ARGS_JQ='
def argv(t):
  if t=="key_empty" or t=="command_exists" or t=="package_installed" or t=="key_clear"
     or t=="package_install" or t=="package_remove" or t=="service_restart" then [.name // ""]
  elif t=="key_is" or t=="key_has_token" or t=="key_set" or t=="token_add" or t=="token_remove"
    then [.name // "", .wert // ""]
  elif t=="path_exists" or t=="path_delete" then [.pfad // ""]
  elif t=="file_differs" then [.quelle // "", .ziel // ""]
  elif t=="file_copy" then [.quelle // "", .ziel // ""] + (if has("modus") then [.modus] else [] end)
  elif t=="function_call" then [.funktion // ""]
  else [] end;
argv(.typ // "")[]
'

# Evaluating a condition is read-only, and every rc 2 in one comes from the tree (unknown key, value
# outside the vocabulary), never from the box. So this one call serves the smoke and the derivation.
upd_entry_check() {
	local entry="$1" ident="$2" msg rc typ n i _argv=()
	ident="${ident:-<unnamed>}"
	typ=$(jq -r '.aktion.typ // ""' <<< "$entry")
	mapfile -t _argv < <(jq -r ".aktion | $UPD_ARGS_JQ" <<< "$entry")
	msg=$(upd_action_check "$typ" "${_argv[@]}" 2>&1)
	rc=$?
	[ "$rc" -eq 0 ] || {
		echo "update: $ident: ${msg#update: }" >&2
		return 2
	}
	case "$(jq -r '.umkehrbar | type' <<< "$entry")" in
		boolean) ;;
		*)
			echo "update: $ident: umkehrbar must be true or false" >&2
			return 2
			;;
	esac
	# The upper bound. Claiming less than the action can do is an author's choice, claiming more is a lie.
	if [ "$(jq -r '.umkehrbar' <<< "$entry")" = true ] && [ "$(upd_action_reversible "$typ")" != yes ]; then
		echo "update: $ident: umkehrbar is true, but $typ can never be taken back by putting files back" >&2
		return 2
	fi
	n=$(jq -r '.bedingungen | length' <<< "$entry")
	[ "$n" -gt 0 ] 2> /dev/null || {
		echo "update: $ident: needs at least one condition, so a second run can see it is done" >&2
		return 2
	}
	for ((i = 0; i < n; i++)); do
		typ=$(jq -r ".bedingungen[$i].typ // \"\"" <<< "$entry")
		mapfile -t _argv < <(jq -r ".bedingungen[$i] | $UPD_ARGS_JQ" <<< "$entry")
		msg=$(upd_condition "$typ" "${_argv[@]}" 2>&1)
		rc=$?
		[ "$rc" -eq 2 ] && {
			echo "update: $ident: ${msg#update: }" >&2
			return 2
		}
	done
	return 0
}

# Reads every manifest up to the target into UPD_ENTRIES. A half-read set is not a plan, so a wrong
# file aborts instead of being skipped.
upd_scan() {
	local target="$1" f v dup files
	UPD_ENTRIES=()
	files=$(upd_manifest_files "$target") || return 2
	while read -r f; do
		[ -n "$f" ] || continue
		v=$(basename "$f" .json)
		jq -e . "$f" > /dev/null 2>&1 || {
			echo "update: $f is not valid JSON" >&2
			return 2
		}
		[ "$(jq -r '.version // ""' "$f")" = "$v" ] || {
			echo "update: $f and its version field '$(jq -r '.version // ""' "$f")' must agree" >&2
			return 2
		}
		jq -e '.eintraege | type == "array"' "$f" > /dev/null 2>&1 || {
			echo "update: $f has no 'eintraege' array" >&2
			return 2
		}
		jq -e 'all(.eintraege[]; (.id? // "") | test("^[A-Za-z0-9][A-Za-z0-9._-]*$"))' "$f" > /dev/null 2>&1 || {
			echo "update: $f has an entry without a usable id (letters, digits, . _ -)" >&2
			return 2
		}
		dup=$(jq -r '.eintraege[].id' "$f" | sort | uniq -d | tr '\n' ' ')
		dup="${dup% }"
		[ -z "$dup" ] || {
			echo "update: $f uses an id twice: $dup" >&2
			return 2
		}
		mapfile -t -O "${#UPD_ENTRIES[@]}" UPD_ENTRIES \
			< <(jq -c --arg v "$v" '.eintraege[] | . + {version: $v, identitaet: ($v + "/" + .id)}' "$f")
	done <<< "$files"
	return 0
}

# Every entry once, then the graph. Identities are global, so an id may repeat across files only by a
# rewrite the author cannot make: an entry never moves between files.
upd_check_entries() {
	local e ident dep bad seen=" " known=" " rc=0
	for e in "${UPD_ENTRIES[@]}"; do
		ident=$(jq -r '.identitaet' <<< "$e")
		case "$seen" in *" $ident "*)
			echo "update: $ident appears twice" >&2
			return 2
			;;
		esac
		seen="$seen$ident "
		known="$known$ident "
	done
	for e in "${UPD_ENTRIES[@]}"; do
		ident=$(jq -r '.identitaet' <<< "$e")
		upd_entry_check "$e" "$ident" || rc=2
		dep=$(jq -r '.nach // ""' <<< "$e")
		[ -n "$dep" ] || continue
		case "$known" in *" $dep "*) ;; *)
			echo "update: $ident: 'nach' points at $dep, which no manifest defines" >&2
			rc=2
			continue
			;;
		esac
		# A reversible entry behind an irreversible one would sit after the line and lose its rollback.
		if [ "$(jq -r '.umkehrbar' <<< "$e")" = true ] \
			&& [ "$(upd_entry_field "$dep" .umkehrbar)" != true ]; then
			echo "update: $ident: is reversible but waits for $dep, which is not" >&2
			rc=2
		fi
	done
	[ "$rc" -eq 0 ] || return 2
	bad=$(upd_cycle_find)
	[ -z "$bad" ] || {
		echo "update: 'nach' runs in a circle: $bad" >&2
		return 2
	}
	return 0
}

upd_entry_field() {
	local e
	for e in "${UPD_ENTRIES[@]}"; do
		[ "$(jq -r '.identitaet' <<< "$e")" = "$1" ] && jq -r "$2" <<< "$e" && return 0
	done
	return 1
}

# Peel off what has no unmet dependency; whatever is left is in a circle or waits on one.
upd_cycle_find() {
	local e ident dep left=() next=() done_=" " moved=1
	for e in "${UPD_ENTRIES[@]}"; do left+=("$(jq -r '.identitaet + "\t" + (.nach // "")' <<< "$e")"); done
	while [ "$moved" -eq 1 ] && [ "${#left[@]}" -gt 0 ]; do
		moved=0
		next=()
		for e in "${left[@]}"; do
			ident="${e%%$'\t'*}"
			dep="${e#*$'\t'}"
			if [ -z "$dep" ] || [[ "$done_" == *" $dep "* ]]; then
				done_="$done_$ident "
				moved=1
			else
				next+=("$e")
			fi
		done
		left=("${next[@]}")
	done
	local out=""
	for e in "${left[@]}"; do out="$out${e%%$'\t'*} "; done
	echo "${out% }"
}

# True only when every condition holds. A false condition is the normal case: it says already done.
upd_entry_applies() {
	local entry="$1" n i typ _argv=()
	n=$(jq -r '.bedingungen | length' <<< "$entry")
	for ((i = 0; i < n; i++)); do
		typ=$(jq -r ".bedingungen[$i].typ // \"\"" <<< "$entry")
		mapfile -t _argv < <(jq -r ".bedingungen[$i] | $UPD_ARGS_JQ" <<< "$entry")
		upd_condition "$typ" "${_argv[@]}" > /dev/null 2>&1 || return 1
	done
	return 0
}

# Reversible first so the line falls as late as it can, then dependencies, then version, then id.
# A dependency outside this run counts as met: it either ran in an earlier update, or its condition
# says it is unnecessary. The list is derived once, before the run, so an entry whose condition only
# becomes true through another entry is not in it at all; that is an authoring error, see the README.
upd_order() {
	local pending=("$@") next=() open="" done_=" " pick e ident dep cand
	while [ "${#pending[@]}" -gt 0 ]; do
		open=" "
		for e in "${pending[@]}"; do open="$open$(jq -r '.identitaet' <<< "$e") "; done
		cand=""
		for e in "${pending[@]}"; do
			dep=$(jq -r '.nach // ""' <<< "$e")
			# Still waiting only if the entry it waits for is in this run and has not been picked yet.
			[ -n "$dep" ] && [[ "$done_" != *" $dep "* ]] && [[ "$open" == *" $dep "* ]] && continue
			cand="$cand$(jq -r '(if .umkehrbar then "0" else "1" end) + "\t" + .version + "\t" + .id' <<< "$e")"$'\n'
		done
		[ -n "$cand" ] || {
			echo "update: 'nach' cannot be satisfied for the remaining entries:${open% }" >&2
			return 2
		}
		pick=$(printf '%s' "$cand" | sort -t$'\t' -k1,1 -k2,2V -k3,3 | head -1 | cut -f2,3 | tr '\t' '/')
		next=()
		for e in "${pending[@]}"; do
			ident=$(jq -r '.identitaet' <<< "$e")
			if [ "$ident" = "$pick" ]; then
				printf '%s\n' "$e"
				done_="$done_$ident "
			else
				next+=("$e")
			fi
		done
		pending=("${next[@]}")
	done
	return 0
}

# The derivation: reads the tree, evaluates against the box, writes nothing.
upd_plan() {
	local target="$1" from e sel=() ordered
	from=$(upd_version_norm "$(upd_key_value VERSION)")
	target=$(upd_version_norm "$target")
	upd_scan "$target" || return 2
	upd_check_entries || return 2
	for e in "${UPD_ENTRIES[@]}"; do
		upd_entry_applies "$e" && sel+=("$e")
	done
	if [ "${#sel[@]}" -eq 0 ]; then
		ordered=""
	else
		ordered=$(upd_order "${sel[@]}") || return 2
	fi
	printf '%s' "${ordered:+$ordered$'\n'}" | jq -s --arg von "$from" --arg bis "$target" '{
		version_von: $von,
		version_bis: $bis,
		anzahl: length,
		unumkehrbar: [.[] | select(.umkehrbar != true) | .identitaet],
		eintraege: .
	}'
}

# Structure of every manifest in the tree, whatever the box carries. rc 0 sound, rc 2 something is wrong.
upd_manifest_check() {
	upd_scan 99999 || return 2
	upd_check_entries || return 2
	echo "${#UPD_ENTRIES[@]}"
	return 0
}
