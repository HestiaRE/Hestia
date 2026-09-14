#!/bin/bash
# Where a release comes from, for anything that runs on an installed box. install.sh keeps its own
# copy because it resolves a release before this tree exists; the smoke holds the two mirror literals
# against each other.
# RELEASE_BRANCH is `release` for the newest tag or a vX.Y.Z pin. There are no channels.

# main.sh reads sysreg.sh through $HESTIA, so the variable has to exist, not just a fallback inside
# one path expression.
HESTIA="${HESTIA:-/usr/local/hestia}"
# Probed by function, not by variable: a variable arrives from the environment too, and an exported
# HESTIA_RELEASE_MIRROR then skipped this source and left version_ge undefined - which made the
# updater's lower-bound check refuse with "this box is too old" instead of saying what was missing.
# shellcheck source=/usr/local/hestia/include/main.sh
declare -F version_ge > /dev/null || source "$HESTIA/include/main.sh"

RELEASE_REPO="HestiaRE/Hestia"
RELEASE_API="https://api.github.com/repos/$RELEASE_REPO"
RELEASE_DL="https://github.com/$RELEASE_REPO/releases/download"

# $1 = api|dl, $2 = path below it, rest = curl arguments. Bounded: an unroutable host otherwise
# costs minutes before the first error.
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

# The test override. One URL, taken as given, instead of resolving a tag against the release source.
# It stops at the download: the tag functions below never consult it, so `--check` keeps asking the
# real source and the panel flag keeps meaning what it says. The token goes into a header, never into
# the URL, where a proxy log would keep it. Nothing writes it down, it lives in the environment only.
release_override_url() { printf '%s' "${HESTIA_RELEASE_URL:-}"; }

# $1 = "" for the tarball or ".sha256" for its checksum, $2 = destination, $3 = the tag (ignored when
# an override is set, because then there is none).
release_fetch_asset() {
	local suffix="$1" dest="$2" tag="$3"
	local -a auth=()
	if [ -n "${HESTIA_RELEASE_URL:-}" ]; then
		[ -n "${HESTIA_RELEASE_TOKEN:-}" ] && auth=(-H "Authorization: token ${HESTIA_RELEASE_TOKEN}")
		curl -fsSL --connect-timeout 15 --max-time 600 "${auth[@]}" "${HESTIA_RELEASE_URL}${suffix}" -o "$dest"
		return
	fi
	release_get dl "/$tag/hestiare-$tag.tar.gz$suffix" -o "$dest"
}

# Empty means "could not ask", never "there is none": a caller that reads it as nothing-to-do would
# stop updating without saying so.
release_latest_tag() {
	local out
	out=$(release_get api /releases/latest 2> /dev/null | jq -r '.tag_name // empty')
	[ "$out" = null ] && out=""
	printf '%s\n' "$out"
}

release_tag_exists() {
	local out
	[ -n "${1:-}" ] || return 1
	out=$(release_get api "/releases/tags/$1" 2> /dev/null | jq -r '.tag_name // empty')
	[ -n "$out" ] && [ "$out" != null ]
}

# What this box should run. Empty means the question stayed unanswered; the caller says so instead of
# guessing a version.
release_target_tag() {
	local want
	want=$(sed -n "s/^RELEASE_BRANCH='\(.*\)'\$/\1/p" "$HESTIA/conf/hestia.conf" | head -1)
	case "${want:-release}" in
		release) release_latest_tag ;;
		v[0-9]*) printf '%s\n' "$want" ;;
		*)
			echo "release: RELEASE_BRANCH carries '$want' - expected 'release' or a vX.Y.Z tag" >&2
			return 1
			;;
	esac
}
