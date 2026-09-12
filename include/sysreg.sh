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

# sysreg_check [file]: the schema: name, class, default per class, token_fn exists and is named wherever
# tokens is true, secret is a boolean, and the closed value set (values) holds its own default.
# Prints one line per defect, returns 1 on any. Duplicate names are
# caught textually, since a JSON parser keeps the last of two and says nothing.
sysreg_check() {
	local f="${1:-$(sysreg_file)}" bad=0 n names dups k class def fn sec tok vals _v _def _has_def _has_empty
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
	while IFS=$'\x1f' read -r k class def fn sec tok vals; do
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
		case "$sec" in __absent__ | true | false) ;; *)
			echo "sysreg: $k has secret '$sec', expected true or false" >&2
			bad=1
			;;
		esac
		if [ "$fn" != "__absent__" ]; then
			grep -qE "^${fn}\(\) \{" "${HESTIA:-.}"/include/*.sh 2> /dev/null || {
				echo "sysreg: $k names token_fn '$fn', which no include/*.sh defines" >&2
				bad=1
			}
		fi
		# A token list without its function is the half that cannot be used: phase 2's token actions
		# find the function through this field, so tokens=true without token_fn is a silent dead end (E8).
		if [ "$tok" = true ] && [ "$fn" = "__absent__" ]; then
			echo "sysreg: $k is tokens:true without a token_fn" >&2
			bad=1
		fi
		# The closed value set. It is the machine-readable half of the vocabulary contract in $comment,
		# which until #946 existed only as prose - and prose is not something a guard can hold a manifest
		# against (E24). Two rules, both with teeth:
		#   the default must be a member, or the repair would write a value the key may not carry;
		#   a system key must list the empty string, because absent and empty mean the same thing there
		#   and the tree writes both - a set without it would make a legitimate box illegal.
		if [ "$vals" != "__absent__" ]; then
			_def="$def"
			[ "$_def" = "__absent__" ] && _def=""
			# join of an empty array is the empty string, and so is a set holding only the empty value -
			# both are degenerate and must not pass as "a vocabulary"
			if [ -z "$vals" ]; then
				echo "sysreg: $k has an empty values set" >&2
				bad=1
			else
				_has_def=no _has_empty=no
				while IFS= read -r _v; do
					[ "$_v" = "$_def" ] && _has_def=yes
					[ -z "$_v" ] && _has_empty=yes
				done < <(printf '%s\n' "$vals" | tr '\036' '\n')
				[ "$_has_def" = yes ] || {
					echo "sysreg: $k has values without its own default '$_def'" >&2
					bad=1
				}
				if [ "$class" = system ] && [ "$_has_empty" != yes ]; then
					echo "sysreg: $k is system and its values omit the empty value (absent == empty)" >&2
					bad=1
				fi
			fi
		fi
	done < <(jq -r '.keys | to_entries[] | [.key, (.value.class // "__absent__"), (.value.default // "__absent__"), (.value.token_fn // "__absent__"), (if .value | has("secret") then (.value.secret | tostring) else "__absent__" end), (if .value.tokens == true then "true" else "false" end), (if .value | has("values") then (.value.values | join("\u001e")) else "__absent__" end)] | join("\u001f")' "$f")
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

# sysreg_values KEY - the closed value set, one per line (an allowed empty value prints as an empty
# line, so read it with mapfile, not with a bare $(...)). rc 1 for an unknown key, rc 2 for a key
# whose set is deliberately open (a version pin, a token list): the two are different answers and a
# caller that cannot tell them apart would treat "no contract" as "key does not exist".
sysreg_values() {
	_sysreg_ok || return 1
	jq -e --arg k "$1" '.keys | has($k)' "$(sysreg_file)" > /dev/null || return 1
	jq -e --arg k "$1" '.keys[$k] | has("values")' "$(sysreg_file)" > /dev/null || return 2
	jq -r --arg k "$1" '.keys[$k].values[]' "$(sysreg_file)"
}

# sysreg_value_ok KEY VALUE - the predicate behind it, silent on purpose: it is used as a probe, and a
# probe that logs writes an error line into every legitimate run (the #925 class). rc 0 when the value
# is allowed OR the key has no closed set, rc 1 when the key has one and the value is not in it, rc 2
# for an unknown key. "Allowed" is decided by jq against the array, never by comparing joined text: a
# value may contain anything a hestia.conf value may contain, including the separators used elsewhere.
sysreg_value_ok() {
	local k="$1" v="$2"
	_sysreg_ok || return 2
	jq -e --arg k "$k" '.keys | has($k)' "$(sysreg_file)" > /dev/null 2>&1 || return 2
	jq -e --arg k "$k" '.keys[$k] | has("values")' "$(sysreg_file)" > /dev/null 2>&1 || return 0
	jq -e --arg k "$k" --arg v "$v" '.keys[$k].values | index($v) != null' "$(sysreg_file)" > /dev/null 2>&1
}

# sysreg_token_fn KEY - the function that adds or removes one token of a token list (E8). The name
# comes from the registry, never from a table in a caller: a second list is the one that goes stale.
sysreg_token_fn() {
	_sysreg_ok || return 1
	jq -er --arg k "$1" '.keys[$k].token_fn // empty' "$(sysreg_file)"
}
