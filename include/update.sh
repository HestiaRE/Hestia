#!/bin/bash
# Update building blocks: conditions and actions behind one dispatcher, so a manifest entry is data.
# Boundary: a guard reports, an action writes. No upd_act_* in the smoke.
# One root, $HESTIA. The release is unpacked over the install tree before any of this runs.
#
# Trust: a manifest ships in the release tarball and is reviewed like any other file here, so an
# entry may name any path. path_delete and file_copy are therefore checked for shape, never for
# location. A deny list would read as protection it cannot give: the next path is one line away.

UPDATE_ROOT="${HESTIA:-/usr/local/hestia}"
# Higher than any version this project will carry: "every manifest, whatever the box runs".
UPD_VERSION_MAX=99999

# shellcheck source=/usr/local/hestia/include/main.sh
source "$UPDATE_ROOT/include/main.sh"
# shellcheck source=/usr/local/hestia/include/sysreg.sh
source "$UPDATE_ROOT/include/sysreg.sh"

#----------------------------------------------------------#
# Conditions #
#----------------------------------------------------------#
# rc 0 true, rc 1 false, rc 2 entry is wrong. True and false silent: most are legitimately false.
# Box decides the value, tree decides the name. Unknown in hestia.conf is a box fact, unknown in the
# registry is a typo.

# Anchored, never sourced: key names are prefixes of each other, and a config is not a script.
# $1 goes into the expression unquoted on purpose: every caller passes a name the registry validated,
# or the literal VERSION. A name that is not in the registry never reaches here.
upd_key_value() {
	local conf="$UPDATE_ROOT/conf/hestia.conf"
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
	local tok toks=()
	upd_key_known "$1" || return 2
	[ "$(sysreg_tokens "$1")" = yes ] || {
		echo "update: $1 is not a token list - key_has_token does not apply" >&2
		return 2
	}
	IFS=, read -ra toks <<< "$(upd_key_value "$1")"
	for tok in "${toks[@]}"; do [ "$tok" = "$2" ] && return 0; done
	return 1
}

# The other direction, and the most common shape of update work there is: we ship something the box
# does not have yet. path_exists cannot say it, and the vocabulary has no negation.
upd_cond_path_absent() {
	[ -n "$1" ] || {
		echo "update: path_absent needs a path" >&2
		return 2
	}
	if [ -e "$1" ] || [ -L "$1" ]; then return 1; fi
	return 0
}

# File, directory or symlink in one type.
upd_cond_path_exists() {
	[ -n "$1" ] || {
		echo "update: path_exists needs a path" >&2
		return 2
	}
	[ -e "$1" ] || [ -L "$1" ]
}

# True while an account or the box default names a language the panel no longer ships (#1160). Read
# from languages.json, which the overlay has replaced, so it holds before the dropped catalogs are deleted.
upd_cond_language_unlisted() {
	local out
	out=$(languages_unlisted) || {
		echo "update: languages.json is unusable - language_unlisted cannot decide" >&2
		return 2
	}
	[ -n "$out" ]
}

# locale_missing NAME, answered by locale -a: a line in /etc/locale.gen says nothing about the archive.
upd_cond_locale_missing() {
	[ -n "$1" ] || {
		echo "update: locale_missing needs a name" >&2
		return 2
	}
	! locale_present "$1"
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
	local src="$UPDATE_ROOT/$1"
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

# A shipped file the operator may edit (every target of h-change-sys-service-config, and the exim
# template the addons set macros in). Copying the tree version over it destroys their work, so a
# change arrives as a patch. Three-way on purpose: rc 0 to do, rc 1 done, rc 2 the context is gone.
# That last one must not read as "done": it is the case where someone edited exactly this block.
upd_cond_file_patch_pending() {
	local pf="$UPDATE_ROOT/$1" tgt="$2"
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: file_patch_pending needs a tree-relative patch and a target" >&2
		return 2
	}
	[ -f "$pf" ] || {
		echo "update: $1 is not a file in this tree" >&2
		return 2
	}
	command -v patch > /dev/null 2>&1 || {
		echo "update: patch(1) is missing, so no patch entry can decide anything" >&2
		return 2
	}
	# Not rc 1, and not file_differs' rc 0 either: a patch cannot create the file the way a copy
	# can, so an absent target is a state this cannot decide, not one it can repair or dismiss.
	# An entry for an optional component gates that with its own condition instead.
	[ -f "$tgt" ] || {
		echo "update: $tgt does not exist, so $1 can neither apply nor be shown to have applied" >&2
		return 2
	}
	# -F0: no fuzz. A hunk that only roughly matches is drift, not a hit.
	patch --dry-run -F0 -s "$tgt" < "$pf" > /dev/null 2>&1 && return 0
	patch --dry-run -F0 -s -R "$tgt" < "$pf" > /dev/null 2>&1 && return 1
	echo "update: neither $1 nor its reverse applies to $tgt - the file changed where the patch touches it" >&2
	return 2
}

# Files that sit one per account and domain, so the path is a pattern; true when one of them carries a bit outside
# the mode. Expanded with compgen, not a for-loop, so a pattern that matches nothing is simply no match.
upd_cond_file_mode_wider() {
	local f m
	[ -n "$1" ] && [[ "$2" =~ ^[0-7]{3,4}$ ]] || {
		echo "update: file_mode_wider needs a path pattern and an octal mode" >&2
		return 2
	}
	while read -r f; do
		[ -n "$f" ] || continue
		m=$(stat -L -c '%a' "$f" 2> /dev/null) || continue
		(((8#$m & ~8#$2) != 0)) && return 0
	done < <(compgen -G "$1")
	return 1
}

# For a file the box generates: no tree source to compare against, so the marker is what the OLD
# version wrote. That is what makes this false once the file has been rewritten. The path may be a
# pattern, for a file that sits once per PHP version; true when one of the matches carries the marker.
upd_cond_file_contains() {
	local f
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: file_contains needs a path and a value" >&2
		return 2
	}
	while read -r f; do
		[ -f "$f" ] || continue
		[ -r "$f" ] || {
			echo "update: $f exists but cannot be read, so file_contains cannot decide" >&2
			return 2
		}
		grep -qF -- "$2" "$f" && return 0
	done < <(compgen -G "$1")
	return 1
}

# The complement of file_contains, for an entry that has to ADD a marker rather than replace one.
# A missing file reads as false, as it does there: no content, nothing to be asked about.
upd_cond_file_lacks() {
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: file_lacks needs a path and a value" >&2
		return 2
	}
	[ -f "$1" ] || return 1
	[ -r "$1" ] || {
		echo "update: $1 exists but cannot be read, so file_lacks cannot decide" >&2
		return 2
	}
	grep -qF -- "$2" "$1" && return 1
	return 0
}

# Absent marker is false: not installed here, and an update installs nothing. An empty pin is the
# tree being wrong, never a box fact, so it must not read as "nothing to do".
upd_cond_pin_differs() {
	local pin
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: pin_differs needs a manifest key and a marker path" >&2
		return 2
	}
	pin=$(manifest_get ".software_versions.$1")
	[ -n "$pin" ] && [ "$pin" != null ] || {
		echo "update: share/manifest.json carries no software_versions.$1" >&2
		return 2
	}
	[ -f "$2" ] || return 1
	[ "$(cat "$2" 2> /dev/null)" != "$pin" ]
}

# Versions from h-list-sys-php, not from /etc/php, which also holds versions no customer runs. A
# comma list because the action repairs a set, and a lister that cannot answer is rc 2, not a zero.
upd_cond_php_ext_missing() {
	local v e out rc exts=()
	[ -n "$1" ] || {
		echo "update: php_ext_missing needs one extension name or a comma list" >&2
		return 2
	}
	IFS=, read -ra exts <<< "$1"
	out=$("$UPDATE_ROOT/bin/h-list-sys-php" plain 2> /dev/null)
	rc=$?
	[ "$rc" -eq 0 ] || {
		echo "update: php_ext_missing could not ask h-list-sys-php (rc $rc)" >&2
		return 2
	}
	while read -r v; do
		[ -n "$v" ] || continue
		for e in "${exts[@]}"; do
			[ -n "$e" ] || continue
			upd_cond_package_installed "php$v-$e" || return 0
		done
	done <<< "$out"
	return 1
}

# True when a file in the directory still carries a registry-secret with a real value. The mask and an
# empty value do not count, so a store the masking emitter filled reads as clean and the entry stops
# firing on a box that has nothing to purge. Key names come from the registry, never a list here; an
# unreadable registry is rc 2, because "found no secret" must not stand in for "could not look".
upd_cond_dir_has_secret_value() {
	local k keys
	[ -n "$1" ] || {
		echo "update: dir_has_secret_value needs a path" >&2
		return 2
	}
	keys=$(sysreg_secret_keys) || {
		echo "update: dir_has_secret_value cannot read the key registry" >&2
		return 2
	}
	[ -n "$keys" ] || {
		echo "update: the registry marks no key secret - dir_has_secret_value has nothing to look for" >&2
		return 2
	}
	[ -d "$1" ] || return 1
	while read -r k; do
		[ -n "$k" ] || continue
		# A quoted value holding at least one character that is neither a quote nor an asterisk:
		# that rules out both the mask and the empty string in one expression.
		grep -rqE "$k\|s:[0-9]+:\"[^\"]*[^*\"][^\"]*\"" "$1" 2> /dev/null && return 0
	done <<< "$keys"
	return 1
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
		# dir_clear sits with path_delete: both make a path cease to exist.
		path_delete | dir_clear | package_remove | service_restart | function_call) echo no ;;
		*) return 1 ;;
	esac
}

# The line between a vocabulary and arbitrary code. deploy_hestia_sudoers and login_defs_guard are
# deliberately absent (#948): their targets are not copies of a tree file, so no condition could go
# false after them. The smoke reports their drift and names the command instead.
UPDATE_CALLABLE=(proc_hardening_apply customer_php_limit_apply panel_session_cleanup_apply
	php_db_drivers_apply tachyon_pin_apply sieve_lmtp_apply exim_lmtp_apply cron_update_check_apply
	cron_locale_apply system_repair_cron_write sieve_redirect_apply sieve_vacation_apply fail2ban_panel_action_apply
	exim_autoreply_apply exim_spam_header_apply mail_ssl_modes_apply smtp_relay_modes_apply
	php_versions_configure_apply php_modules_apply php_cli_pcntl_apply ioncube_pin_apply language_fallback_apply
	panel_locale_apply fail2ban_mail_grace_apply firewall_keep_private_apply v_aliases_remove_apply
	cron_record_review_apply exim_one_domain_apply)

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
	local src="$UPDATE_ROOT/$1" dst="$2" mode="${3:-}" tmp prev
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

# The directory stays with its owner and mode: deleting it locks the panel out, nobody recreates it.
# The rc reads the result, not find's opinion of a file that vanished under it.
upd_act_dir_clear() {
	[ -d "$1" ] || return 0
	find "$1" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} + 2> /dev/null
	[ -z "$(find "$1" -mindepth 1 -maxdepth 1 -print -quit 2> /dev/null)" ]
}

# File found, not listed: a second list goes stale.
upd_act_function_call() {
	local fn="$1" src
	if ! declare -F "$fn" > /dev/null 2>&1; then
		src=$(grep -lE "^${fn}\(\) \{" "$UPDATE_ROOT"/include/*.sh 2> /dev/null | head -1)
		[ -n "$src" ] || return 1
		# shellcheck disable=SC1090 # the file is the one that defines the allow-listed name
		source "$src" || return 1
		declare -F "$fn" > /dev/null 2>&1 || return 1
	fi
	"$fn"
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
		key_empty | key_is | key_has_token | path_exists | path_absent | command_exists | package_installed | file_differs)
			"upd_cond_$t" "$@"
			;;
		# A second arm, not a wrapped first one: check_update_dispatcher reads an arm as ONE line ending
		# in ")", so a continuation drops every name before it out of the set it compares.
		file_contains | file_lacks | pin_differs | php_ext_missing | dir_has_secret_value | file_patch_pending | file_mode_wider)
			"upd_cond_$t" "$@"
			;;
		language_unlisted | locale_missing)
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

# Absolute, not only slashes, ".." as a component: a glob let "/..", "//" and relative paths through.
# "/etc/foo..bar" stays allowed. One home, because every deleting action needs the same rule.
_upd_abs_path_ok() {
	local t="$1" _p="$2"
	case "$_p" in /*) ;; *)
		echo "update: $t needs an absolute path, got '$_p'" >&2
		return 1
		;;
	esac
	while [ "${_p%/}" != "$_p" ]; do _p="${_p%/}"; done
	[ -n "$_p" ] || {
		echo "update: $t refuses '$2' - that is the root" >&2
		return 1
	}
	case "/${_p#/}/" in */../*)
		echo "update: $t refuses '$2' - '..' as a path component" >&2
		return 1
		;;
	esac
}

# The one place an entry is validated, and the only one that may run without writing: the derivation
# checks a manifest it must not execute. rc 0 sound, rc 2 the entry is wrong.
upd_action_check() {
	local t="$1" fn
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
			[ -f "$UPDATE_ROOT/$1" ] || {
				echo "update: $1 is not a file in this tree" >&2
				return 2
			}
			;;
		path_delete | dir_clear)
			_upd_abs_path_ok "$t" "${1:-}" || return 2
			;;
		function_call)
			# One name, no arguments: every callable takes none, and a path that looks like it
			# could pass some is worse than one that says it cannot.
			[ $# -eq 1 ] && [ -n "$1" ] || {
				echo "update: function_call needs a name and nothing else" >&2
				return 2
			}
			for fn in "${UPDATE_CALLABLE[@]}"; do [ "$fn" = "$1" ] && break; done
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
# entries with `after`; a bundle would drag a reversible part behind the line for its irreversible half.
# An entry may declare itself less reversible than its action, never more: only the upper bound is a lie.
# Identity is version/id. Nothing here writes: the derivation reads the tree and the box.

UPDATE_DIR="$UPDATE_ROOT/share/updates"

# Entries of the last scan, one JSON object per line. Global so scan, check and plan share one read.
UPD_ENTRIES=()

# The tag carries the v, the manifest never does.
upd_version_norm() { printf '%s\n' "${1#v}"; }

# By version, never by string: 0.10 sorts before 0.9 alphabetically.
upd_version_le() {
	local a b
	a=$(upd_version_norm "$1")
	b=$(upd_version_norm "$2")
	[ "$a" = "$b" ] && return 0
	[ "$(printf '%s\n%s\n' "$a" "$b" | LC_ALL=C sort -V | head -1)" = "$a" ]
}

# No directory means no manifests. That is the state until the first release ships one, not an error.
# A name that is not a version is refused, never skipped: sort -V would quietly sort it out of range
# and the file would be missing from every plan without a word.
# The bound is read from update.sh and not repeated here: it travels in the tarball, so the tree
# being derived from is the one whose bound decides, and two literals would drift.
upd_min_version() {
	local b
	b=$(sed -n 's/^UPDATE_MIN_VERSION=//p' "$UPDATE_ROOT/update.sh" 2> /dev/null | head -1 | tr -d "\"'")
	[ -n "$b" ] || return 1
	printf '%s\n' "$b"
}

upd_manifest_files() {
	local target="$1" f v out="" bound
	[ -d "$UPDATE_DIR" ] || return 0
	# Unreadable is an error, never "read everything": without the bound there is no saying what is
	# still in scope, and guessing would be the wrong half of the question either way.
	bound=$(upd_min_version) || {
		echo "update: no UPDATE_MIN_VERSION in $UPDATE_ROOT/update.sh, so the scope cannot be decided" >&2
		return 2
	}
	for f in "$UPDATE_DIR"/*.json; do
		[ -f "$f" ] || continue
		v=$(basename "$f" .json)
		case "$v" in [0-9]*.[0-9]*) ;; *)
			echo "update: $f is not named after a version" >&2
			return 2
			;;
		esac
		# At or below the bound it can never apply, because no box below the bound is accepted. Not
		# read at all, so a copy left on a box by an earlier release cannot reach the plan either:
		# the overlay of an update never deletes, and this discovery is a glob (#1093).
		upd_version_le "$v" "$bound" && continue
		upd_version_le "$v" "$target" && out="$out$v	$f"$'\n'
	done
	printf '%s' "$out" | LC_ALL=C sort -V | cut -f2
}

# JSON fields to the argv each building block takes. One place, so a renamed field is one edit.
# A missing field becomes an empty argument and the block itself names what it wanted.
UPD_ARGS_JQ='
def argv(t):
  if t=="key_empty" or t=="command_exists" or t=="package_installed" or t=="key_clear"
     or t=="package_install" or t=="package_remove" or t=="service_restart"
     or t=="php_ext_missing" or t=="locale_missing" then [.name // ""]
  elif t=="key_is" or t=="key_has_token" or t=="key_set" or t=="token_add" or t=="token_remove"
    then [.name // "", .value // ""]
  elif t=="path_exists" or t=="path_absent" or t=="path_delete" or t=="dir_clear"
    or t=="dir_has_secret_value" then [.path // ""]
  elif t=="file_contains" or t=="file_lacks" or t=="file_mode_wider" then [.path // "", .value // ""]
  elif t=="pin_differs" then [.name // "", .path // ""]
  elif t=="file_differs" or t=="file_patch_pending"
    then [.source // "", .target // ""]
  elif t=="file_copy" then [.source // "", .target // ""] + (if has("mode") then [.mode] else [] end)
  elif t=="function_call" then [.function // ""]
  else [] end;
argv(.type // "")[]
'

# Evaluating a condition is read-only, and every rc 2 in one comes from the tree (unknown key, value
# outside the vocabulary), never from the box. So this one call serves the CI tree check and the derivation.
# The argv a building block takes, one per line. $2 is a jq selector from our own code, never data.
upd_argv() { jq -r "$2 | $UPD_ARGS_JQ" <<< "$1"; }

upd_entry_check() {
	local entry="$1" ident="$2" msg rc t n i _argv=()
	ident="${ident:-<unnamed>}"
	[ "$(jq -r '.action | type' <<< "$entry")" = object ] || {
		echo "update: $ident: action must be one object with a type" >&2
		return 2
	}
	t=$(jq -r '.action.type // ""' <<< "$entry")
	mapfile -t _argv < <(upd_argv "$entry" .action)
	msg=$(upd_action_check "$t" "${_argv[@]}" 2>&1)
	rc=$?
	[ "$rc" -eq 0 ] || {
		echo "update: $ident: ${msg#update: }" >&2
		return 2
	}
	case "$(jq -r '.reversible | type' <<< "$entry")" in
		boolean) ;;
		*)
			echo "update: $ident: reversible must be true or false" >&2
			return 2
			;;
	esac
	# The upper bound. Claiming less than the action can do is an author's choice, claiming more is a lie.
	if [ "$(jq -r '.reversible' <<< "$entry")" = true ] && [ "$(upd_action_reversible "$t")" != yes ]; then
		echo "update: $ident: reversible is true, but $t can never be taken back by putting files back" >&2
		return 2
	fi
	# Shape before length: jq's length answers for a string and a number too, so a misspelled entry
	# would pass here and die at the first index with a raw jq message instead of a sentence.
	[ "$(jq -r '.conditions | type' <<< "$entry")" = array ] || {
		echo "update: $ident: conditions must be a list" >&2
		return 2
	}
	n=$(jq -r '.conditions | length' <<< "$entry")
	[ "$n" -gt 0 ] || {
		echo "update: $ident: needs at least one condition, so a second run can see it is done" >&2
		return 2
	}
	# Stops at the first false one, as upd_entry_applies does: a gate (key_is MAIL_SYSTEM) is what keeps a
	# patch condition off a box where the file is not ours, the stock exim template of a nomail box (#1132).
	# Not covered: a condition behind a false gate is checked only on the boxes where the gate holds.
	for ((i = 0; i < n; i++)); do
		t=$(jq -r ".conditions[$i].type // \"\"" <<< "$entry")
		mapfile -t _argv < <(upd_argv "$entry" ".conditions[$i]")
		msg=$(upd_condition "$t" "${_argv[@]}" 2>&1)
		rc=$?
		[ "$rc" -eq 2 ] && {
			echo "update: $ident: ${msg#update: }" >&2
			return 2
		}
		[ "$rc" -eq 1 ] && break
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
		jq -e '.entries | type == "array"' "$f" > /dev/null 2>&1 || {
			echo "update: $f has no 'entries' array" >&2
			return 2
		}
		jq -e 'all(.entries[]; (.id? // "") | test("^[A-Za-z0-9][A-Za-z0-9._-]*$"))' "$f" > /dev/null 2>&1 || {
			echo "update: $f has an entry without a usable id (letters, digits, . _ -)" >&2
			return 2
		}
		dup=$(jq -r '.entries[].id' "$f" | LC_ALL=C sort | uniq -d | tr '\n' ' ')
		dup="${dup% }"
		[ -z "$dup" ] || {
			echo "update: $f uses an id twice: $dup" >&2
			return 2
		}
		mapfile -t -O "${#UPD_ENTRIES[@]}" UPD_ENTRIES \
			< <(jq -c --arg v "$v" '.entries[] | . + {version: $v, identity: ($v + "/" + .id)}' "$f")
	done <<< "$files"
	return 0
}

# Every entry once, then the graph. Identities are global, so an id may repeat across files only by a
# rewrite the author cannot make: an entry never moves between files.
upd_check_entries() {
	local e ident dep bad known=" " rc=0
	# No duplicate check here: the identity carries the version, which is the file name, and a
	# directory holds a name once. Inside a file upd_scan already refuses a repeated id.
	for e in "${UPD_ENTRIES[@]}"; do known="$known$(jq -r '.identity' <<< "$e") "; done
	for e in "${UPD_ENTRIES[@]}"; do
		ident=$(jq -r '.identity' <<< "$e")
		upd_entry_check "$e" "$ident" || rc=2
		dep=$(jq -r '.after // ""' <<< "$e")
		[ -n "$dep" ] || continue
		case "$known" in *" $dep "*) ;; *)
			echo "update: $ident: 'after' points at $dep, which no manifest defines" >&2
			rc=2
			continue
			;;
		esac
		# A reversible entry behind an irreversible one would sit after the line and lose its rollback.
		if [ "$(jq -r '.reversible' <<< "$e")" = true ] \
			&& [ "$(upd_entry_field "$dep" .reversible)" != true ]; then
			echo "update: $ident: is reversible but waits for $dep, which is not" >&2
			rc=2
		fi
	done
	[ "$rc" -eq 0 ] || return 2
	bad=$(upd_cycle_find)
	[ -z "$bad" ] || {
		echo "update: 'after' runs in a circle: $bad" >&2
		return 2
	}
	return 0
}

upd_entry_field() {
	local e
	for e in "${UPD_ENTRIES[@]}"; do
		[ "$(jq -r '.identity' <<< "$e")" = "$1" ] && jq -r "$2" <<< "$e" && return 0
	done
	return 1
}

# Peel off what has no unmet dependency; whatever is left is in a circle or waits on one.
upd_cycle_find() {
	local e ident dep left=() next=() done_=" " moved=1
	for e in "${UPD_ENTRIES[@]}"; do left+=("$(jq -r '.identity + "\t" + (.after // "")' <<< "$e")"); done
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
	printf '%s\n' "${out% }"
}

# True only when every condition holds. A false condition is the normal case: it says already done.
upd_entry_applies() {
	local entry="$1" n i t _argv=()
	n=$(jq -r '.conditions | length' <<< "$entry")
	for ((i = 0; i < n; i++)); do
		t=$(jq -r ".conditions[$i].type // \"\"" <<< "$entry")
		mapfile -t _argv < <(upd_argv "$entry" ".conditions[$i]")
		upd_condition "$t" "${_argv[@]}" > /dev/null 2>&1 || return 1
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
		for e in "${pending[@]}"; do open="$open$(jq -r '.identity' <<< "$e") "; done
		cand=""
		for e in "${pending[@]}"; do
			dep=$(jq -r '.after // ""' <<< "$e")
			# Still waiting only if the entry it waits for is in this run and has not been picked yet.
			[ -n "$dep" ] && [[ "$done_" != *" $dep "* ]] && [[ "$open" == *" $dep "* ]] && continue
			cand="$cand$(jq -r '(if .reversible then "0" else "1" end) + "\t" + .version + "\t" + .id' <<< "$e")"$'\n'
		done
		[ -n "$cand" ] || {
			echo "update: 'after' cannot be satisfied for the remaining entries:${open% }" >&2
			return 2
		}
		pick=$(printf '%s' "$cand" | LC_ALL=C sort -t$'\t' -k1,1 -k2,2V -k3,3 | head -1 | cut -f2,3 | tr '\t' '/')
		next=()
		for e in "${pending[@]}"; do
			ident=$(jq -r '.identity' <<< "$e")
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
		version_from: $von,
		version_to: $bis,
		count: length,
		irreversible: [.[] | select(.reversible != true) | .identity],
		entries: .
	}'
}

# Structure of every manifest in the tree, whatever the box carries. rc 0 sound, rc 2 something is wrong.
upd_manifest_check() {
	upd_scan "$UPD_VERSION_MAX" || return 2
	upd_check_entries || return 2
	printf '%s\n' "${#UPD_ENTRIES[@]}"
	return 0
}
