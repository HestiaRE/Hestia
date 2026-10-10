#!/bin/bash
# Tree guards for CI: checks whose answer depends on the shipped code alone, so they run once per PR
# against the checkout instead of on every box (#1147). What only a box can answer stays in
# bin/h-check-sys-smoke. Needs bash, coreutils, grep/sed/awk and jq, which lint.yml already
# requires; a guard that would need more belongs on the box, not on the runner.
# Prints PASS/FAIL per check; exits 0 if all pass, 1 otherwise.
cd "$(dirname "$0")/../.." || exit 1
HESTIA="$PWD"
export HESTIA
command -v jq > /dev/null 2>&1 || {
	echo "jq is missing on this host"
	exit 1
}
# shellcheck source=include/sysreg.sh
source "$HESTIA/include/sysreg.sh"
# Not main.sh: it reads the box's hestia.conf, which a checkout does not have. The one value a guard
# here needs from it is read as the literal instead; empty makes check_webmail_value_domain red.
WEBMAIL_KNOWN_CLIENTS=$(sed -n "s/^WEBMAIL_KNOWN_CLIENTS='\(.*\)'\$/\1/p" "$HESTIA/include/main.sh")

pass=0
fail=0
ok() {
	echo "[ PASS ] $1"
	pass=$((pass + 1))
}
no() {
	echo "[ FAIL ] $1${2:+, $2}"
	fail=$((fail + 1))
}
skip() { echo "[ skip ] $1"; }

# Every JSON under share/ parses: the readers take them in place, and a file jq refuses fails at runtime.
check_share_json() {
	local f n=0 bad=''
	while IFS= read -r f; do
		n=$((n + 1))
		jq empty "$f" > /dev/null 2>&1 || bad="$bad $f"
	done < <(find share -name '*.json' -type f)
	if [ "$n" -eq 0 ]; then
		no "share JSON: no file found under share/" "nothing was checked"
	elif [ -n "$bad" ]; then
		no "share JSON: jq refuses" "${bad# }"
	else
		ok "share JSON: $n file(s) parse"
	fi
}

# GHSA-xffx class: source_conf must keep its identifier guard on the key, or a `key[$(cmd)]=` line in any
# parsed conf executes cmd through declare -g. The sink has ~500 callers, so losing the guard is a silent hole.
check_source_conf_guard() {
	if grep -qE '\[\[ \$lhs =~ \^\[A-Za-z_\]' "$HESTIA/include/main.sh" 2> /dev/null; then
		ok "invariant: source_conf rejects non-identifier keys (GHSA-xffx class)"
	else
		no "invariant: source_conf rejects non-identifier keys (GHSA-xffx class)" "guard missing in include/main.sh"
	fi
}

# Two lists describe the panel's languages: the catalog directories (what h-list-sys-languages offers) and
# languages.json (their names, and what an update and a restore ask). Each catalog carries hestia.mo, the domain
# web/inc/i18n.php binds; without it the language shows English. An empty list fails.
check_panel_catalogs() {
	local list="$HESTIA/web/locale/languages.json" d dirs=() named odd=''
	named=$(jq -r 'keys[] | select(endswith("_locale") | not) | select(. != "en")' "$list" 2> /dev/null | sort)
	for d in "$HESTIA/web/locale"/*/; do
		[ -e "$d" ] || continue
		dirs+=("$(basename "$d")")
		[ -f "$d/LC_MESSAGES/hestia.mo" ] || odd="$odd $(basename "$d")(no hestia.mo)"
	done
	if [ -z "$named" ] || [ "${#dirs[@]}" -eq 0 ]; then
		no "panel catalogs" "languages.json names no language or no catalog directory exists, nothing was compared"
		return
	fi
	odd="$odd $(comm -3 <(printf '%s\n' "${dirs[@]}" | sort) <(echo "$named") | tr -d '\t' | tr '\n' ' ')"
	odd=$(echo $odd)
	if [ -n "$odd" ]; then
		no "panel catalogs and languages.json disagree" "$odd"
	else
		ok "panel catalogs: ${#dirs[@]} agree with languages.json, each carries hestia.mo"
	fi
}

# The protected-name list judged by its effect: a record that tries to bind PATH must not change it, and an
# ordinary key must still bind in the same run. The three definitions come from main.sh, which a checkout
# cannot source whole.
check_protected_names_effective() {
	local out
	out=$(
		shopt -s extglob
		eval "$(sed -n '/^SOURCE_CONF_PROTECTED="/,/"$/p; /^is_protected_key() {/,/^}/p; /^source_conf() {/,/^}/p' \
			"$HESTIA/include/main.sh")"
		declare -F source_conf is_protected_key > /dev/null || exit 1
		tmp=$(mktemp) || exit 1
		printf "NAME='probe'\nPATH=/tmp/hestia-tree-should-not-happen\n" > "$tmp"
		before="$PATH"
		source_conf "$tmp" 2> /dev/null
		after="$PATH"
		PATH="$before"
		rm -f "$tmp"
		[ "$after" = "$before" ] && printf 'kept:%s' "$NAME"
	)
	if [ "$out" = 'kept:probe' ]; then
		ok "invariant: a config file cannot rebind PATH, an ordinary key still binds"
	elif [ "$out" = 'kept:' ]; then
		no "invariant: protected names" "the probe bound nothing, so the check proves nothing"
	else
		no "invariant: protected names" "source_conf rebound PATH or did not load from include/main.sh"
	fi
}

# check_sys_key_registry: the system key registry (share/hestia/sys-keys.json), three guards:
# (1) the schema, through sysreg_check; (2) every key the tree writes into
# hestia.conf is registered; the write sites are extracted from the shipped code (the seven mechanisms:
# wcv/_wcv, change_sys_value/clear_sys_value, sys_key_token_set, h-change-sys-config-value with a literal
# key, repair_key, a direct echo/sed on hestia.conf, the web-model key set), never listed here; (3) an empty registry
# or an empty extraction is red. A key written through a variable name cannot be extracted and is
# not covered; the emitter and the repair read the registry, so such a key would surface there.
check_sys_key_registry() {
	local out written unknown='' n k
	if ! out=$(sysreg_check 2>&1); then
		no "system key registry: schema" "$(echo "$out" | head -n3 | tr '\n' ';')"
		return
	fi
	# full-line comments are dropped first: a usage line like "sys_key_token_set KEY add|remove TOKEN" is
	# not a write site, and it made the placeholder KEY an unregistered key
	written=$({
		grep -rhvE '^[[:space:]]*(#|//)' "$HESTIA/bin" "$HESTIA/include" "$HESTIA/sbin" 2> /dev/null | grep -oE "(_wcv|wcv|change_sys_value|clear_sys_value|repair_key|sys_key_token_set) +['\"]?[A-Z][A-Z0-9_]+"
		grep -rhvE '^[[:space:]]*(#|//)' "$HESTIA/bin" "$HESTIA/include" "$HESTIA/sbin" "$HESTIA/web" 2> /dev/null | grep -oE "h-change-sys-config-value +['\"]?[A-Z][A-Z0-9_]+"
		grep -rhvE '^[[:space:]]*(#|//)' "$HESTIA/bin" "$HESTIA/include" "$HESTIA/sbin" 2> /dev/null | grep -E "hestia\.conf" | grep -oE "(echo \"|s[|/]\^?)[A-Z][A-Z0-9_]+="
		grep -hoE '^WEB_MODEL_KEYS="[A-Z_ ]+"' "$HESTIA/include/web-model.sh" 2> /dev/null | tr ' ' '\n'
	} | grep -oE '[A-Z][A-Z0-9_]{2,}' | grep -vx 'WEB_MODEL_KEYS' | sort -u)
	# The one exclusion is the variable's own name, picked up from the line the key set is read from; it
	# is not a key. Nothing else is excluded - a written key that is not registered is the finding.
	n=$(echo "$written" | grep -c .)
	if [ "$n" -lt 20 ]; then
		no "system key registry: write-site extraction found only $n key(s)" "the grep patterns no longer match the code"
		return
	fi
	for k in $written; do
		sysreg_class "$k" > /dev/null 2>&1 || unknown="$unknown $k"
	done
	if [ -n "$unknown" ]; then
		no "system key registry: written but unregistered key(s)" "$unknown"
	else
		ok "system key registry: schema holds, all $n written keys registered ($(sysreg_keys | wc -l) entries)"
	fi
}

# THE BOUNDARY: a guard verifies an artefact, it never fills one. Nothing here may call an upd_act_*.
# check_manifest_status_link: the manifest names, for every component that is neither always_installed
# nor no_status_key, the hestia.conf key it writes (status_key, 1c ); every named key must be in the
# registry; a status_token is a plain status word, and empty only on an option whose value means off
# (false, no, off, disabled), so a forgotten token is told apart from a deliberate one. The token is
# recorded here, not compared with the box; recipe against status is 1d. Both reference sets are read
# from the files, never listed here; an empty component set is red. Names keys, never values.
check_manifest_status_link() {
	local m="$HESTIA/share/manifest.json" reg n=0 missing='' unknown='' badtok='' cid key tok
	reg=$(sysreg_keys 2> /dev/null) || reg=''
	if [ -z "$reg" ]; then
		no "manifest status link: the registry read no keys" "share/hestia/sys-keys.json unreadable"
		return
	fi
	while IFS=$'\t' read -r cid key; do
		[ -n "$cid" ] || continue
		n=$((n + 1))
		if [ -z "$key" ]; then
			missing="$missing $cid"
		elif ! grep -qx -- "$key" <<< "$reg"; then
			unknown="$unknown $cid:$key"
		fi
	done < <(jq -r '.components | to_entries[] | select((.value.always_installed // false) | not) | select(.value.no_status_key == null) | [.key, (.value.status_key // "")] | @tsv' "$m" 2> /dev/null)
	while IFS=$'\t' read -r cid val tok; do
		if [ -z "$tok" ]; then
			case "$val" in false | no | off | disabled) ;; *) badtok="$badtok $cid($val:empty)" ;; esac
		else
			[[ "$tok" =~ ^[a-z0-9_.]+$ ]] || badtok="$badtok $cid($val)"
		fi
	done < <(jq -r '.components | to_entries[] | .key as $c | (.value.options // [])[]? | select(type == "object" and has("status_token")) | [$c, (.value | tostring), .status_token] | @tsv' "$m" 2> /dev/null)
	if [ "$n" -eq 0 ]; then
		no "manifest status link: no component examined" "share/manifest.json unreadable or without components"
	elif [ -n "$missing$unknown$badtok" ]; then
		no "manifest status link:${missing:+ no status_key:$missing}${unknown:+ key not in registry:$unknown}${badtok:+ bad status_token:$badtok}" "every component names a registered key (1c)"
	else
		ok "manifest status link: $n components name a registered status key"
	fi
}

# THE BOUNDARY: this verifies, it never repairs. See the header of include/update.sh.
# Structure of the update manifests, not their effect: types, ids, the `after` graph and the
# reversibility claim. It says how many entries it read, because until the first release ships a
# manifest the honest answer is zero and a green line must not read as coverage.
check_update_manifests() {
	local lib="$HESTIA/include/update.sh" out n files
	[ -f "$lib" ] || return
	# In a subshell: the library re-sources main.sh, and the smoke keeps its own state.
	if ! out=$(
		# shellcheck source=/usr/local/hestia/include/update.sh
		source "$lib" > /dev/null 2>&1
		upd_manifest_check 2>&1
	); then
		no "update manifests are not sound" "$(echo "$out" | head -3 | tr '\n' '|')"
		return
	fi
	n=$(echo "$out" | tail -1)
	case "$n" in '' | *[!0-9]*) n=-1 ;; esac
	if [ "$n" -lt 0 ]; then
		no "update manifests: the check returned no count" "the derivation moved, so nothing was measured"
	elif [ "$n" -eq 0 ]; then
		# Zero was the right answer while share/updates was empty. Counted is what is IN SCOPE, not
		# what lies in the directory: a manifest at or below the lower bound is deliberately not read
		# (#1093), so its presence is no reason to expect entries. Not covered, said out loud: such a
		# file is then not validated either, which is the price of not reading it.
		files=$(
			# shellcheck source=/usr/local/hestia/include/update.sh
			source "$lib" > /dev/null 2>&1
			upd_manifest_files "$UPD_VERSION_MAX" 2> /dev/null | grep -c .
		)
		case "$files" in '' | *[!0-9]*) files=0 ;; esac
		if [ "$files" -gt 0 ]; then
			no "update manifests: $files file(s) in scope, but the check read 0 entries" \
				"the scan found none of them, so nothing was measured"
		else
			ok "update manifests: none in scope above the lower bound, nothing to check"
		fi
	else
		ok "update manifests: $n entry(s) read, every type, id and dependency holds"
	fi
}

# A type the dispatcher accepts but nothing defines would be skipped, and a skipped entry looks exactly
# like one whose condition was false. Both sets come from the code, never from a list here.
check_update_dispatcher() {
	local lib="$HESTIA/include/update.sh" defined accepted missing extra
	if [ ! -f "$lib" ]; then
		no "update dispatcher: include/update.sh is missing" "the update building blocks are part of the tree"
		return
	fi
	defined=$(grep -oE '^upd_(cond|act)_[a-z_]+\(\)' "$lib" | sed -E 's/^upd_(cond|act)_//; s/\(\)$//' | sort -u)
	accepted=$(sed -n '/^upd_condition() {/,/^}/p;/^upd_action_check() {/,/^}/p' "$lib" \
		| grep -oE '^[[:space:]]*[a-z_]+( \| [a-z_]+)*\)' | tr -d '\t )' | tr '|' '\n' \
		| sed 's/^ *//; s/ *$//' | grep -vE '^(\*|key_missing)$' | sort -u)
	if [ -z "$defined" ] || [ -z "$accepted" ]; then
		no "update dispatcher: derived an empty set" "the function or case shape moved - update the derivation"
		return
	fi
	missing=$(comm -13 <(echo "$defined") <(echo "$accepted") | tr '\n' ' ')
	extra=$(comm -23 <(echo "$defined") <(echo "$accepted") | tr '\n' ' ')
	if [ -n "$missing" ] || [ -n "$extra" ]; then
		no "update dispatcher disagrees with its functions" \
			"accepted but undefined:${missing:- -} | defined but unreachable:${extra:- -}"
	else
		ok "update dispatcher: $(echo "$defined" | wc -l) type(s), every accepted name is defined and reachable"
	fi
}

# check_eval_sites: two named files used to be guarded one by one, which is a list nobody measures:
# a new eval in a third file would have gone unseen. Derived instead - every eval used as a command
# in bin/h-* and include/*.sh is found, and only the two that have a reason may remain.
#
# Whole-line comments are dropped BEFORE the search so the search can stay broad. Command position
# is more than line start, ; & and |: `then eval`, `do eval` and `else eval` sit in it too, and the
# narrow pattern that was here first missed all three - the very blind spot a hand-kept list has.
# A trailing # is never stripped (it is not a comment in $#, ${v#p}, heredocs), so a comment that
# writes "eval " can still match. That direction is a loud false positive; the other one is the hole
# this guard exists to close.
#
# Does NOT cover: sbin/, web/, share/, and eval reached through a variable or an alias.
check_eval_sites() {
	local allowed="h-add-web-domain-ssl main.sh update.sh" f base extra='' hit='' miss='' scanned=0 carry=0
	for f in "$HESTIA/bin"/h-* "$HESTIA/include"/*.sh; do
		[ -f "$f" ] || continue
		scanned=$((scanned + 1))
		# One awk instead of `sed … | grep -q`: grep -q leaves on the first match, and sed then reports the
		# broken pipe for everything it still had to write. On 555 files that printed a stray
		# "sed: couldn't write 56 items to stdout" into an otherwise clean smoke run. Same two
		# conditions, same early exit, no pipe.
		awk '!/^[[:space:]]*#/ && /(^|[^[:alnum:]_])eval[[:space:]]/ { found = 1; exit } END { exit !found }' "$f" || continue
		carry=$((carry + 1))
		base=$(basename "$f")
		hit="$hit $base"
		case " $allowed " in *" $base "*) continue ;; esac
		extra="$extra $base"
	done
	# Every known site must still turn up. A sweep that looked at nothing, or one that lost sight of
	# a site it used to see, shrank its reference set - which is worse than no guard, because green
	# then means "looked at less" instead of "found nothing".
	for base in $allowed; do
		case " $hit " in *" $base "*) ;; *) miss="$miss $base" ;; esac
	done
	if [ "$scanned" -lt 400 ]; then
		no "the eval-sweep scanned only $scanned file(s)" "expected all of $HESTIA/bin/h-* and $HESTIA/include/*.sh"
	elif [ -n "$extra" ]; then
		no "eval-use over record text outside the known places" "$extra"
	elif [ -n "$miss" ]; then
		no "a known eval-site stopped matching - sweep or file changed" "$miss"
	else
		ok "no new eval-sites ($carry of $scanned scanned file(s) carry one, all known)"
	fi
}

# check_record_name_patterns: a record name goes into grep and sed as a PATTERN, and an archive or
# domain name carries dots, which match any character. swept that class once; it came back at
# the backup record sites, so the reference set is derived here instead of remembered: every
# grep or sed in bin/ and include/ whose pattern splices a shell variable next to BACKUP=' or
# DOMAIN=' must either use -F or escape through ${v//./\\.}.
#
# Does NOT cover: the KEY='$var' shape is all it recognises - h-backup-user splices a bare
# "$user.$stamp.tar" with no key in front, which this pattern cannot see without flagging half the
# tree. Nor patterns in web/ or share/, a name reached through an intermediate variable, or
# characters other than the dot (* ? [ ] are equally special; no record name has carried one yet).
check_record_name_patterns() {
	local scanned=0 bad='' f hits
	for f in "$HESTIA/bin"/h-* "$HESTIA/include"/*.sh; do
		[ -f "$f" ] || continue
		scanned=$((scanned + 1))
		hits=$(sed '/^[[:space:]]*#/d' "$f" \
			| grep -nE "(grep|sed)[^|]*(^|[^A-Z_])(BACKUP|DOMAIN)='\\\$" \
			| grep -v -- '-[a-zA-Z]*F' | grep -v '//\./' || true)
		[ -n "$hits" ] && bad="$bad $(basename "$f")"
	done
	if [ "$scanned" -lt 400 ]; then
		no "the record-pattern sweep scanned only $scanned file(s)" "expected all of $HESTIA/bin/h-* and $HESTIA/include/*.sh"
	elif [ -n "$bad" ]; then
		no "a record name is spliced into a pattern unescaped" "$bad - use grep -F or \${v//./\\.}"
	else
		ok "record names reach grep and sed escaped or literal ($scanned file(s) scanned)"
	fi
}

# check_hestia_conf_key_patterns: a grep/sed on $HESTIA/conf/hestia.conf that names a key must anchor it
# (^KEY, or grep -x): key names nest (DB_SYSTEM inside DB_MARIADB_SYSTEM, VERSION inside PHP_VERSIONS), and
# an unanchored "s/DB_SYSTEM=.*/" rewrote the nested key on the first h-add-sys-mariadb. Judged on
# the first key-like token after the command with its flags stripped; a $VAR there is a path, not a key.
# Not covered: a line that reaches the file through a variable or awk. Zero lines examined is red.
check_hestia_conf_key_patterns() {
	local lines n=0 bad='' l body tok key pre xflag
	lines=$(grep -rnE '(grep|sed)' "$HESTIA/bin" "$HESTIA/include" "$HESTIA/sbin" 2> /dev/null \
		| grep -E '(HESTIA|CONF_DIR|conf_dir)\}?/(conf/)?hestia\.conf' | grep -vE '^[^:]+:[0-9]+:[[:space:]]*#')
	while IFS= read -r l; do
		[ -n "$l" ] || continue
		n=$((n + 1))
		body=${l#*:*:}
		xflag=no
		printf '%s' "$body" | grep -qE 'grep( +-[a-zA-Z]*x[a-zA-Z]*)' && xflag=yes
		body=$(printf '%s' "$body" | sed -E 's/(grep|sed)( +-[a-zA-Z]+)+/\1/g')
		tok=$(printf '%s' "$body" | grep -oE '(grep|sed) [^A-Z]*[A-Z][A-Z0-9_]{2,}' | head -n1)
		[ -n "$tok" ] || continue
		key=$(printf '%s' "$tok" | grep -oE '[A-Z][A-Z0-9_]{2,}$')
		pre=${tok%"$key"}
		case "${pre: -1}" in
			'^' | '$' | '{') ;;
			*) [ "$xflag" = yes ] || bad="$bad $(printf '%s' "$l" | cut -d: -f1-2 | sed "s|^$HESTIA/||")($key)" ;;
		esac
	done <<< "$lines"
	if [ "$n" -eq 0 ]; then
		no "hestia.conf key patterns: no grep/sed line examined" "the selector no longer matches the code"
	elif [ -n "$bad" ]; then
		no "unanchored key pattern on hestia.conf:$bad" "a nested key name is rewritten too (DB_SYSTEM in DB_MARIADB_SYSTEM); anchor with ^KEY"
	else
		ok "hestia.conf key patterns anchored ($n grep/sed lines examined)"
	fi
}

# One encoder and one decoder for record values. A call site that spells a placeholder
# itself is a second home for the mapping: that is how the set stayed at one character while the
# grammar refused four, and how thirteen readers each ended up with their own copy. The set is read
# OUT of the helpers rather than repeated here, so this check cannot drift from them and its own
# file carries no literal. Comment lines may name a placeholder; code may not. An empty set or an
# empty scan fails rather than passes.
check_record_encoding_central() {
	local src="$HESTIA/include/main.sh" ph f rel n=0 bad=''
	ph=$(grep -oE '%[a-z]+%' "$src" 2> /dev/null | sort -u | paste -sd'|')
	if [ -z "$ph" ]; then
		no "record encoding: no placeholder found in include/main.sh" "nothing to compare against"
		return
	fi
	while read -r f; do
		[ -n "$f" ] || continue
		n=$((n + 1))
		rel=${f#"$HESTIA/"}
		[ "$rel" = 'include/main.sh' ] && continue
		bad="$bad $rel"
	done <<< "$(grep -rlE "^[^#]*($ph)" "$HESTIA/bin" "$HESTIA/include" 2> /dev/null)"
	if [ "$n" -eq 0 ]; then
		no "record encoding: nothing scanned" "not even include/main.sh matched, so this proves nothing"
	elif [ -n "$bad" ]; then
		no "record encoding spelled outside the helpers:$bad" \
			"$n file(s) carry one in code - use record_value_encode/record_value_decode"
	else
		ok "record encoding: placeholders appear in code only in include/main.sh ($n file(s) scanned)"
	fi
}

# check_session_user_allowlist <webdir>, $_SESSION["user"] is the REAL logged-in
# user; during impersonation it is the ADMIN, not the customer (who is in "look").
# Scoping customer data by it mis-targets. Rather than chase every spelling
# of the bug, allowlist the files that may touch the real user at all (auth /
# dispatch / reset / real-identity display); ANY other file is red, it must use the
# look-aware $user/$user_plain from inc/main.php. Turns "did we catch every spelling"
# into "may this file see the real user".
check_session_user_allowlist() {
	local webdir="$1" bad="" f rel
	local allow="inc/main.php login/index.php logout/index.php index.php list/index.php \
fm-auth.php rspamd-auth.php reset/index.php reset2fa/index.php edit/user/index.php \
list/log/index.php templates/includes/panel.php templates/pages/edit_server.php \
templates/pages/edit_user.php templates/pages/list_key.php templates/pages/list_log.php \
templates/pages/list_search.php templates/pages/list_user.php"
	while IFS= read -r f; do
		[ -n "$f" ] || continue
		rel=${f#"$webdir"/}
		case " $allow " in *" $rel "*) continue ;; esac
		bad="$bad $rel"
	done <<< "$(grep -rlE '\$_SESSION\["user"\]' "$webdir" 2> /dev/null)"
	if [ -z "$bad" ]; then
		ok "invariant: \$_SESSION[user] only in allowlisted files (#438)"
	else
		no "invariant: \$_SESSION[user] in non-allowlisted file (#438 mis-target risk)" "${bad# }"
	fi
}

# check_error_code_registry: the exit codes live twice, in the shell and in the panel, and nothing
# holds them together. Known alias: E_FORBIDEN/E_FORBIDDEN share 10 on purpose.
check_error_code_registry() {
	local sh_file="$HESTIA/include/main.sh" php_file="$HESTIA/web/inc/helpers.php"
	local name val conflict=0 dupes=0 n=0 m=0 first=""
	if [ ! -f "$sh_file" ] || [ ! -f "$php_file" ]; then
		skip "error code registries not both present"
		return
	fi
	declare -A _sh=() _php=() _seen=()
	while IFS='=' read -r name val; do
		_sh[$name]=$val
		n=$((n + 1))
	done < <(grep -oE '^E_[A-Z_]+=[0-9]+' "$sh_file")
	while read -r name val; do
		_php[$name]=$val
		m=$((m + 1))
	done < <(sed -n 's/^const \(E_[A-Z_]*\) *= *\([0-9]*\);.*/\1 \2/p' "$php_file")
	# Control bytes in the replacement once made the panel side read as empty, and empty agreed with everything.
	if [ "$n" -eq 0 ] || [ "$m" -eq 0 ]; then
		no "error code registries: read $n shell and $m panel code(s)" "a side came up empty, so nothing was compared"
		return
	fi
	for name in "${!_sh[@]}"; do
		[ -n "${_php[$name]:-}" ] || continue
		if [ "${_sh[$name]}" != "${_php[$name]}" ]; then
			conflict=$((conflict + 1))
			[ -z "$first" ] && first="$name is ${_sh[$name]} in the shell and ${_php[$name]} in the panel"
		fi
	done
	for name in "${!_php[@]}"; do
		case "$name" in E_FORBIDEN | E_FORBIDDEN) continue ;; esac
		val=${_php[$name]}
		if [ -n "${_seen[$val]:-}" ]; then
			dupes=$((dupes + 1))
			[ -z "$first" ] && first="${_seen[$val]} and $name both use $val in the panel"
		fi
		_seen[$val]=$name
	done
	if [ "$conflict" -eq 0 ] && [ "$dupes" -eq 0 ]; then
		ok "error codes agree between shell and panel ($n shell, $m panel codes)"
	else
		no "$((conflict + dupes)) error code(s) out of step" "$first"
	fi
}

# Two files have to agree on how a diff payload is spelled: backup.sh names it, h-restore-user greps
# for it. Both are used AS SHIPPED here - the namer in a subshell, the pattern lifted out of the
# restore: so a rename on one side cannot go unnoticed. Plus the fallback that keeps pre-#840
# archives restorable: diff-named wins, whole-named still resolves.
check_backup_diff_member_name() {
	local ext n p d w r pats missing='' seen=0
	# BOTH codecs: the restore greps twice, once per codec, and a drift in the gzip line would sail
	# past a guard that only ever looks at the zstd one.
	pats=$(grep -oE "grep -E 'domain_data[^']*'" "$HESTIA/bin/h-restore-user" | cut -d"'" -f2)
	if [ "$(grep -c . <<< "$pats")" -ne 2 ]; then
		no "diff-member guard found $(grep -c . <<< "$pats") domain-list pattern(s), expected 2" \
			"the restore's domain-list greps moved or changed shape"
		return
	fi
	for ext in zst gz; do
		n=$(bash -c "source \$HESTIA/include/backup.sh; backup_diff_member_name web/x/domain_data.tar.$ext" 2> /dev/null)
		p=$(grep -F "$ext" <<< "$pats" | head -1)
		if [ -z "$n" ] || [ -z "$p" ]; then
			missing="$missing $ext(no-name-or-pattern)"
			continue
		fi
		seen=$((seen + 1))
		grep -qE "$p" <<< "$n" || missing="$missing $ext"
		# The fallback that keeps pre-#840 archives restorable, per codec: diff-named wins when both
		# are there, whole-named still resolves when it is alone.
		d=$(mktemp -d -p "$HESTIA") || return
		: > "$d/domain_data.tar.$ext"
		: > "$d/domain_data.diff.tar.$ext"
		r=$(bash -c "source \$HESTIA/include/backup.sh; backup_payload_path '$d' domain_data.tar.$ext" 2> /dev/null)
		rm -f "$d/domain_data.diff.tar.$ext"
		w=$(bash -c "source \$HESTIA/include/backup.sh; backup_payload_path '$d' domain_data.tar.$ext" 2> /dev/null)
		rm -rf "$d"
		[ "${r##*/}" = "domain_data.diff.tar.$ext" ] && [ "${w##*/}" = "domain_data.tar.$ext" ] \
			|| missing="$missing $ext(fallback:${r##*/}/${w##*/})"
	done
	if [ "$seen" -ne 2 ]; then
		no "diff-member guard checked $seen of 2 codecs" "the namer or the patterns did not answer for both"
		return
	fi
	[ -z "$missing" ] && {
		ok "diff payload name agrees across writer and restore for both codecs, fallback resolves"
		return
	}
	no "diff payload name or fallback disagrees:$missing" \
		"backup_diff_member_name, backup_payload_path and the restore's greps must match"
}

# BACKUP_CONTAINER_META is hand-kept, and the restore copies anything not on it into $USER_DATA -
# twice already a member the backup side added leaked there unread. So derive what the writers
# actually emit into hestia/ and assert every name is either meta or explicitly handled. An empty
# derived set fails: it would mean the grep, not the code, changed.
check_backup_container_meta() {
	local n meta found=0 missing='' src="$HESTIA/include/backup.sh"
	# Both sides out of the file, so neither depends on this script sourcing backup.sh, and an empty
	# reference set fails instead of agreeing with everything.
	meta=$(sed -n "s/^BACKUP_CONTAINER_META='\([^']*\)'.*/\1/p" "$src")
	if [ -z "$meta" ]; then
		no "container-member guard read no BACKUP_CONTAINER_META" "the constant moved or emptied in include/backup.sh"
		return
	fi
	for n in $(grep -ohE '(\$tmpdir|\$_tmp|\$_wd)/(hestia|\$BACKUP_CONTAINER)/[A-Za-z0-9._-]+' \
		"$HESTIA/bin/h-backup-user" "$src" 2> /dev/null | sed -E 's#.*/##' | sort -u); do
		found=$((found + 1))
		# user.conf, ssl and packages are the three the restore handles by name.
		[[ " $meta user.conf ssl packages " == *" $n "* ]] || missing="$missing $n"
	done
	if [ "$found" -eq 0 ]; then
		no "container-member guard derived no members" "the writers or the path spelling moved - re-check the sweep"
		return
	fi
	[ -z "$missing" ] && {
		ok "every archive container member is meta or handled by name ($found seen)"
		return
	}
	no "container member(s) neither meta nor handled:$missing" "add them to BACKUP_CONTAINER_META in include/backup.sh"
}

# The write-path value domain (WEBMAIL_KNOWN_CLIENTS, include/main.sh) is a hand-kept list;
# this derives the shipped-client set from the webmail templates and asserts coverage - a
# client added by template alone would have its records normalized to 'disabled' while
# everything else works. Infrastructure template names are not clients; an empty derived
# set fails rather than passes.
check_webmail_value_domain() {
	local t c found=0 missing=''
	for t in "$HESTIA"/share/*/webmail/*.tpl; do
		[ -e "$t" ] || continue
		c=$(basename "$t" .tpl)
		case "$c" in default | web_system | disabled | default_*) continue ;; esac
		found=$((found + 1))
		grep -qwF -- "$c" <<< "$WEBMAIL_KNOWN_CLIENTS" || missing="$missing $c"
	done
	if [ "$found" -eq 0 ]; then
		no "webmail value-domain guard found no client templates" "share/*/webmail/ empty or moved"
		return
	fi
	[ -z "$missing" ] && {
		ok "webmail value domain covers the shipped client templates"
		return
	}
	no "webmail templates outside the value domain:$missing" "extend WEBMAIL_KNOWN_CLIENTS in include/main.sh"
}

check_share_json
check_sys_key_registry
check_manifest_status_link
check_update_manifests
check_update_dispatcher
check_source_conf_guard
check_protected_names_effective
check_panel_catalogs
# GHSA-cr7q and the GHSA-xffx class were once guarded file by file; this covers both and every other file.
check_eval_sites
check_record_name_patterns
check_hestia_conf_key_patterns
check_record_encoding_central
# The real session user may only be touched by allowlisted files, everything else must use the look-aware
# $user/$user_plain, or an admin impersonating a customer mis-targets.
check_session_user_allowlist "$HESTIA/web"
check_error_code_registry
check_backup_diff_member_name
check_backup_container_meta
check_webmail_value_domain

echo "=== tree: $pass passed, $fail failed ==="
[ "$fail" -eq 0 ]
