#!/bin/bash
# The system key registry (share/hestia/sys-keys.json): readers and the schema guard. jq only, no other
# include, no /etc/hestia - the same functions run in the smoke on a box and in CI on a checkout, so
# the guard is one implementation called twice (E21). Every reader fails loudly: a missing, broken or
# empty registry returns non-zero with a line on stderr and prints nothing, never an empty set that a
# caller could mistake for "no keys" (the login reads through h-list-sys-config, #575 class).

# The file: $HESTIA on a box, the checkout otherwise; SYSREG_FILE overrides for tests and CI.
sysreg_file() {
	echo "${SYSREG_FILE:-${HESTIA:-.}/share/hestia/sys-keys.json}"
}

# sysreg_check [file] - the schema. Prints one line per defect, returns 1 on any. Duplicate names are
# caught textually, since a JSON parser keeps the last of two and says nothing.
sysreg_check() {
	local f="${1:-$(sysreg_file)}" bad=0 n names dups k class def fn
	[ -f "$f" ] || {
		echo "sysreg: $f is missing" >&2
		return 1
	}
	command -v jq > /dev/null 2>&1 || {
		echo "sysreg: jq is not installed" >&2
		return 1
	}
	jq -e '.keys | type == "object"' "$f" > /dev/null 2>&1 || {
		echo "sysreg: $f does not parse or has no .keys object" >&2
		return 1
	}
	n=$(jq -r '.keys | length' "$f")
	[ "${n:-0}" -gt 0 ] || {
		echo "sysreg: registry is empty" >&2
		return 1
	}
	names=$(grep -oE '^ *"[A-Z][A-Z0-9_]*": *\{' "$f" | grep -oE '[A-Z][A-Z0-9_]*')
	dups=$(echo "$names" | sort | uniq -d)
	[ -z "$dups" ] || {
		echo "sysreg: duplicate key(s): $(echo "$dups" | tr '\n' ' ')" >&2
		bad=1
	}
	# \x1f as the separator, not a tab: read collapses consecutive whitespace separators, and an empty
	# default would shift the columns one to the left
	while IFS=$'\x1f' read -r k class def fn; do
		case "$k" in [A-Z]*) ;; *)
			echo "sysreg: $k is not a key name" >&2
			bad=1
			;;
		esac
		case "$class" in
			betreiber) [ "$def" != "__absent__" ] || {
				echo "sysreg: $k is betreiber without a default" >&2
				bad=1
			} ;;
			system) [ "$def" = "__absent__" ] || [ -z "$def" ] || {
				echo "sysreg: $k is system with a default '$def' (the repair never fills system keys)" >&2
				bad=1
			} ;;
			*)
				echo "sysreg: $k has class '$class', expected system or betreiber" >&2
				bad=1
				;;
		esac
		if [ "$fn" != "__absent__" ]; then
			grep -qE "^${fn}\(\) \{" "${HESTIA:-.}"/include/*.sh 2> /dev/null || {
				echo "sysreg: $k names token_fn '$fn', which no include/*.sh defines" >&2
				bad=1
			}
		fi
	done < <(jq -r '.keys | to_entries[] | [.key, (.value.class // "__absent__"), (.value.default // "__absent__"), (.value.token_fn // "__absent__")] | join("\u001f")' "$f")
	return $bad
}

# The readers below refuse a file that is missing, does not parse or has no keys - the loud part, in
# one jq call. The full schema is sysreg_check's job, run by the smoke and CI, not on every login:
# the emitter feeds the panel session at each login, and the schema costs a handful of processes.
_sysreg_ok() {
	local f
	[ -n "${_SYSREG_CHECKED:-}" ] && return 0
	f=$(sysreg_file)
	[ -f "$f" ] || {
		echo "sysreg: $f is missing" >&2
		return 1
	}
	jq -e '.keys | type == "object" and length > 0' "$f" > /dev/null 2>&1 || {
		echo "sysreg: $f does not parse, or holds no keys" >&2
		return 1
	}
	_SYSREG_CHECKED=yes
}

# sysreg_keys [betreiber|system] - key names, file order
sysreg_keys() {
	_sysreg_ok || return 1
	if [ -n "${1:-}" ]; then
		jq -r --arg c "$1" '.keys | to_entries[] | select(.value.class == $c) | .key' "$(sysreg_file)"
	else
		jq -r '.keys | keys_unsorted[]' "$(sysreg_file)"
	fi
}

# sysreg_class KEY / sysreg_default KEY / sysreg_tokens KEY (prints yes|no); an unknown key is rc 1
sysreg_class() {
	_sysreg_ok || return 1
	jq -er --arg k "$1" '.keys[$k].class // empty' "$(sysreg_file)"
}
sysreg_default() {
	_sysreg_ok || return 1
	jq -e --arg k "$1" '.keys | has($k)' "$(sysreg_file)" > /dev/null || return 1
	jq -r --arg k "$1" '.keys[$k].default // ""' "$(sysreg_file)"
}
sysreg_tokens() {
	_sysreg_ok || return 1
	jq -e --arg k "$1" '.keys | has($k)' "$(sysreg_file)" > /dev/null || return 1
	jq -r --arg k "$1" 'if .keys[$k].tokens == true then "yes" else "no" end' "$(sysreg_file)"
}
