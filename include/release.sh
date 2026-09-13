#!/bin/bash
# Where a release comes from, for everything that runs on an installed box: update.sh and
# h-change-sys-release. install.sh cannot use this (it resolves a release before this tree exists),
# so its copy stays, and the smoke holds the two mirror literals against each other.
#
# There are no channels. Development happens against a private Gitea, a release is pushed to the
# public repo, and RELEASE_BRANCH says which tag a box follows: `release` for the newest one, or a
# `vX.Y.Z` pin. Nothing here knows about beta or nightly, because nothing produces them.

# main.sh sources sysreg.sh through $HESTIA, so the variable has to exist before it is read, not only
# inside the path here. Sourced without hestia.env, the old form loaded a main.sh that then looked for
# /include/sysreg.sh and left every sysreg_* function undefined.
HESTIA="${HESTIA:-/usr/local/hestia}"
# shellcheck source=/usr/local/hestia/include/main.sh
[ -n "${HESTIA_RELEASE_MIRROR:-}" ] || source "$HESTIA/include/main.sh"

RELEASE_REPO="HestiaRE/Hestia"
RELEASE_API="https://api.github.com/repos/$RELEASE_REPO"
RELEASE_DL="https://github.com/$RELEASE_REPO/releases/download"
# The mirror literal lives in include/main.sh and is used from there, so an update adds no third
# home for it. A box installed from a private Gitea keeps its own source, see release_source_load.
RELEASE_SOURCE_CONF="${CONF_DIR:-/etc/hestia}/source.conf"

# A private Gitea release is a different build and never falls back to the public mirror, the same
# rule install.sh follows. Absent file means the public repo, which is the normal case.
release_source_load() {
	RELEASE_SOURCE=github
	RELEASE_REPO_URL=""
	RELEASE_TOKEN=""
	[ -f "$RELEASE_SOURCE_CONF" ] || return 0
	RELEASE_SOURCE=$(sed -n 's/^HESTIARE_SOURCE="\(.*\)"$/\1/p' "$RELEASE_SOURCE_CONF" | head -1)
	RELEASE_REPO_URL=$(sed -n 's/^HESTIARE_REPO_URL="\(.*\)"$/\1/p' "$RELEASE_SOURCE_CONF" | head -1)
	RELEASE_TOKEN=$(sed -n 's/^HESTIARE_TOKEN="\(.*\)"$/\1/p' "$RELEASE_SOURCE_CONF" | head -1)
	RELEASE_SOURCE="${RELEASE_SOURCE:-github}"
	return 0
}

# $1 = api|dl, $2 = path below it, rest = curl arguments. Bounded, because an unroutable host
# otherwise costs minutes before the first error surfaces.
release_get() {
	local kind="$1" path="$2" primary mirror
	shift 2
	case "$kind" in
		api) primary="$RELEASE_API" mirror="$HESTIA_RELEASE_MIRROR/api" ;;
		dl) primary="$RELEASE_DL" mirror="$HESTIA_RELEASE_MIRROR/raw" ;;
		*) return 1 ;;
	esac
	curl -fsSL --connect-timeout 15 --max-time 600 "$@" "$primary$path" && return 0
	[ -n "${HESTIA_RELEASE_MIRROR:-}" ] || return 1
	curl -fsSL --connect-timeout 15 --max-time 600 "$@" "$mirror$path"
}

# The newest tag the configured source offers, or empty. Empty is "could not ask", never "there is
# none": a caller that treats it as "nothing to do" would silently stop updating.
release_latest_tag() {
	local out
	release_source_load
	if [ "$RELEASE_SOURCE" = gitea ] && [ -n "$RELEASE_REPO_URL" ]; then
		out=$(curl -fsSL --connect-timeout 15 --max-time 600 \
			${RELEASE_TOKEN:+-H "Authorization: token $RELEASE_TOKEN"} \
			"$RELEASE_REPO_URL/releases/latest" 2> /dev/null | jq -r '.tag_name // empty')
	else
		out=$(release_get api /releases/latest 2> /dev/null | jq -r '.tag_name // empty')
	fi
	[ "$out" = null ] && out=""
	printf '%s\n' "$out"
}

# Does the source carry this tag. Used to refuse a pin nobody can fetch, before it is written.
release_tag_exists() {
	local tag="$1" out
	[ -n "$tag" ] || return 1
	release_source_load
	if [ "$RELEASE_SOURCE" = gitea ] && [ -n "$RELEASE_REPO_URL" ]; then
		out=$(curl -fsSL --connect-timeout 15 --max-time 600 \
			${RELEASE_TOKEN:+-H "Authorization: token $RELEASE_TOKEN"} \
			"$RELEASE_REPO_URL/releases/tags/$tag" 2> /dev/null | jq -r '.tag_name // empty')
	else
		out=$(release_get api "/releases/tags/$tag" 2> /dev/null | jq -r '.tag_name // empty')
	fi
	[ -n "$out" ] && [ "$out" != null ]
}

# What this box is supposed to run, from RELEASE_BRANCH. Empty means the question could not be
# answered; the caller says so and stops rather than guessing a version.
release_target_tag() {
	local want
	want=$(sed -n "s/^RELEASE_BRANCH='\(.*\)'\$/\1/p" "${HESTIA:-/usr/local/hestia}/conf/hestia.conf" | head -1)
	case "${want:-release}" in
		release) release_latest_tag ;;
		v[0-9]*) printf '%s\n' "$want" ;;
		*)
			echo "release: RELEASE_BRANCH carries '$want' - expected 'release' or a vX.Y.Z tag" >&2
			return 1
			;;
	esac
}
