#!/bin/bash
# The system key registry (share/hestia/sys-keys.json): readers and the schema guard. jq only and no
# other include, so the same code serves the smoke on a box and CI on a checkout. Every reader fails
# loudly: a broken registry never returns an empty set a caller could read as "no keys"; the login
# feeds its session from here.

# The file: $HESTIA on a box, the checkout otherwise; SYSREG_FILE overrides for tests and CI.
sysreg_file() {
	echo "${SYSREG_FILE:-${HESTIA:-.}/share/hestia/sys-keys.json}"
}

# The schema: name, class, default per class, token_fn present wherever tokens is true, secret boolean,
# and values holding its own default. One line per defect, rc 1 on any. Duplicates are caught textually
# because a JSON parser keeps the last of two and says nothing.
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
	# \x1f, not a tab: read collapses consecutive whitespace, so an empty default would shift the columns.
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
		# A token list whose function is unnamed is the half nothing can use.
		if [ "$tok" = true ] && [ "$fn" = "__absent__" ]; then
			echo "sysreg: $k is tokens:true without a token_fn" >&2
			bad=1
		fi
		# The vocabulary as data, so a guard can hold a manifest against it. The default must be a member,
		# or the repair writes a value the key may not carry; a system key must list the empty value,
		# because absent and empty are one state there and a set without it makes a legal box illegal.
		if [ "$vals" != "__absent__" ]; then
			_def="$def"
			[ "$_def" = "__absent__" ] && _def=""
			# An empty array and a set holding only the empty value join to the same string; neither is a
			# vocabulary.
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

# The readers refuse a missing, unparsable or empty file in one jq call. The full schema stays with
# sysreg_check: it costs several processes and the emitter runs on every login.
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

# One value per line; an allowed empty value prints as an empty line, so read with mapfile.
# rc 1 unknown key, rc 2 deliberately open set: two different answers, and a caller that cannot tell
# them apart reads "no contract" as "no such key".
sysreg_values() {
	_sysreg_ok || return 1
	jq -e --arg k "$1" '.keys | has($k)' "$(sysreg_file)" > /dev/null || return 1
	jq -e --arg k "$1" '.keys[$k] | has("values")' "$(sysreg_file)" > /dev/null || return 2
	jq -r --arg k "$1" '.keys[$k].values[]' "$(sysreg_file)"
}

# Silent on purpose: a probe that logs writes a line into every legitimate run. rc 0 allowed or no
# closed set, rc 1 not in the set, rc 2 unknown key. Membership is decided by jq against the array,
# never against joined text, because a value may contain the separators used elsewhere.
sysreg_value_ok() {
	local k="$1" v="$2"
	_sysreg_ok || return 2
	jq -e --arg k "$k" '.keys | has($k)' "$(sysreg_file)" > /dev/null 2>&1 || return 2
	jq -e --arg k "$k" '.keys[$k] | has("values")' "$(sysreg_file)" > /dev/null 2>&1 || return 0
	jq -e --arg k "$k" --arg v "$v" '.keys[$k].values | index($v) != null' "$(sysreg_file)" > /dev/null 2>&1
}

# The name comes from the registry, never from a table in a caller: a second list goes stale.
sysreg_token_fn() {
	_sysreg_ok || return 1
	jq -er --arg k "$1" '.keys[$k].token_fn // empty' "$(sysreg_file)"
}
