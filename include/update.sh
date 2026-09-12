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
# TWO ROOTS. $HESTIA keeps its usual meaning everywhere here - the box's install root, and that is the
# right root for every write: "$HESTIA/conf/..." reaches the instance data wherever it lives.
# What needs a second name is this code's OWN location. Every h-* bootstraps through
# `source /etc/hestia/hestia.env`, which pins HESTIA to /usr/local/hestia, so a command started from an
# extracted tarball would load the INSTALLED library, not its own - the wrong way round for the one job
# this library has. UPDATE_TREE is that second name, derived from the file's own path rather than from a
# caller who could get it wrong. The two roots are the same after the overlay and differ before it:
# phase 3 derives the entry list from the extracted tree while the box is still untouched, and that run
# must read the NEW registry and the NEW templates while writing nothing at all.
UPDATE_TREE="${UPDATE_TREE:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

# SOURCING - the list below is the whole of it, and a smoke check holds the code against it. A library
# that runs from a foreign tree against an unknown box is exactly where an accidental `source` of
# something box-local would go unnoticed, so the set is small, named, and measured rather than trusted.
UPDATE_SOURCE_ALLOW="include/main.sh include/sysreg.sh"

# shellcheck source=/usr/local/hestia/include/main.sh
source "$UPDATE_TREE/include/main.sh"
# shellcheck source=/usr/local/hestia/include/sysreg.sh
source "$UPDATE_TREE/include/sysreg.sh"
# The registry travels with the tree, never with the box: the names a manifest may reference are the
# ones its own release knows.
SYSREG_FILE="${SYSREG_FILE:-$UPDATE_TREE/share/hestia/sys-keys.json}"

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
	local src="$UPDATE_TREE/$1"
	[ -n "$1" ] && [ -n "$2" ] || {
		echo "update: file_differs needs a tree-relative source and a target" >&2
		return 2
	}
	[ -f "$src" ] || {
		echo "update: $1 is not a file in $UPDATE_TREE" >&2
		return 2
	}
	[ -f "$2" ] || return 0
	! cmp -s "$src" "$2"
}
