#!/bin/bash
# HestiaRE updater: find the release, secure what gets overwritten, unpack it, derive the plan from the
# new tree, hand it to the executor. A newer updater in the tarball takes over once, before anything changes.
#
# Usage: update.sh [--check]

# No set -u: main.sh reads $user unset, as every h-* command expects.
umask 0022

HESTIA="${HESTIA:-/usr/local/hestia}"
# The library shipped with THIS updater, since after the handover $HESTIA is still the old tree. Absent only
# on a recovery run from /root, then the installed one; absent after a handover is a broken unpack.
UPD_SELF_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" 2> /dev/null && pwd)
UPD_LIB="$UPD_SELF_DIR/include/release.sh"
if [ ! -r "$UPD_LIB" ]; then
	if [ -n "${HESTIA_UPDATE_HANDOVER:-}" ]; then
		echo "update: handed over to ${HESTIA_UPDATE_HANDOVER}, but ${UPD_LIB} is missing - refusing to run against another tree's library" >&2
		exit 1
	fi
	UPD_LIB="$HESTIA/include/release.sh"
fi
# shellcheck source=/usr/local/hestia/include/release.sh
source "$UPD_LIB"

CHECK_ONLY=no
[ "${1:-}" = "--check" ] && CHECK_ONLY=yes

# A box below this is reinstalled, not updated. After the handover the target's own literal decides.
UPDATE_MIN_VERSION='v0.21'

tree_version() { cat "$HESTIA/VERSION" 2> /dev/null; }
status_version() { sed -n "s/^VERSION='\(.*\)'\$/\1/p" "$HESTIA/conf/hestia.conf" | head -1; }

say() { printf '%s\n' "$*"; }
die() {
	printf '%s\n' "$*" >&2
	exit 1
}

# HESTIA_RELEASE_URL is a test mechanism and stops at the download: --check and the panel flag keep asking
# the real source. The tarball's own VERSION names the target, so the downgrade check sits further down.
OVERRIDE=$(release_override_url)
TREE=$(tree_version)
if [ -n "$OVERRIDE" ]; then
	TARGET=""
	say "Installed: ${TREE:-unknown}   Target: the tarball at $OVERRIDE"
else
	TARGET=$(release_target_tag) || die "update: cannot tell which release to follow"
	[ -n "$TARGET" ] || die "update: the release source did not answer - no version was guessed, nothing was touched"
	say "Installed: ${TREE:-unknown}   Target: $TARGET"
fi

# version_ge carries the v, the form tag, tree and this literal are all written in. Unreadable loses.
version_ge "$TREE" "$UPDATE_MIN_VERSION" \
	|| die "update: this box says ${TREE:-nothing}, and the lower bound is $UPDATE_MIN_VERSION - it is reinstalled, not updated. Nothing was touched."
[ -z "$TARGET" ] \
	|| version_ge "$TARGET" "$TREE" \
	|| die "update: $TARGET is older than the installed $TREE - an update never goes backwards. Nothing was touched."

# The panel reads the flag --check writes, and a test tarball has no business touching it.
[ -z "$OVERRIDE" ] || [ "$CHECK_ONLY" = no ] \
	|| die "update: --check asks the release source, and this run was handed a fixed tarball. Nothing was touched."

# Before the early exit, or a flag set once would keep the banner up forever. Empty, not "no": absent and
# empty are one state.
if [ "$CHECK_ONLY" = yes ]; then
	_flag=$(sed -n "s/^UPDATE_AVAILABLE='\(.*\)'\$/\1/p" "$HESTIA/conf/hestia.conf" | head -1)
	if [ "$TARGET" = "$TREE" ]; then
		[ -z "$_flag" ] || "$HESTIA/bin/h-change-sys-config-value" UPDATE_AVAILABLE "" > /dev/null 2>&1
		say "--check: nothing newer than $TREE"
	else
		[ "$_flag" = yes ] || "$HESTIA/bin/h-change-sys-config-value" UPDATE_AVAILABLE yes > /dev/null 2>&1
		say "--check: would update to $TARGET"
	fi
	exit 0
fi

STATUS=$(status_version)
if [ -n "$TARGET" ] && [ "$TARGET" = "$TREE" ]; then
	# Leaving is only right when the last run also finished.
	if [ "$STATUS" = "$TREE" ] && [ "$("$HESTIA/bin/h-list-sys-updates" json "$TREE" 2> /dev/null | jq -r '.count // 0')" = 0 ]; then
		say "Already on $TREE, nothing to do."
		exit 0
	fi
	if [ "$STATUS" = "$TREE" ]; then
		say "[ ! ] $TREE still has update entries pending - running $TARGET again to work them off"
	else
		say "[ ! ] tree is $TREE but the status says ${STATUS:-<empty>} - running $TARGET again to finish that run"
	fi
fi

RUNDIR="${HESTIA_UPDATE_RUNDIR:-/root/hestiare-update/$(date '+%Y-%m-%d_%H-%M-%S')_${TREE:-unknown}_${TARGET:-override}}"
mkdir -p "$RUNDIR" || die "update: cannot create $RUNDIR"
say "[ * ] Run directory: $RUNDIR"

TARBALL="${HESTIA_UPDATE_TARBALL:-$RUNDIR/hestiare-${TARGET:-override}.tar.gz}"
if [ ! -s "$TARBALL" ]; then
	release_fetch_asset "" "$TARBALL" "$TARGET" \
		|| die "update: could not fetch the release tarball"
	[ -s "$TARBALL" ] || die "update: the downloaded tarball is empty"
fi

# A missing checksum is not an error, a wrong one is. By value, not sha256sum -c: an override arrives under
# another file name. More than one line is refused rather than matched.
if release_fetch_asset .sha256 "$RUNDIR/sha256" "$TARGET" 2> /dev/null \
	&& [ -s "$RUNDIR/sha256" ]; then
	_lines=$(awk 'NF{n++} END{print n+0}' "$RUNDIR/sha256")
	[ "$_lines" = 1 ] || die "update: the published sha256 lists $_lines files - refusing to guess which one"
	_want=$(awk 'NF{print $1; exit}' "$RUNDIR/sha256")
	_have=$(sha256sum "$TARBALL" | awk '{print $1}')
	if [ -n "$_want" ] && [ "$_want" = "$_have" ]; then
		say "[ * ] Checksum verified"
	else
		die "update: the tarball does not match its published sha256 - refusing to unpack it"
	fi
else
	say "[ ! ] ${TARGET:-this tarball} publishes no checksum - the tree version is the only check"
fi

# The root comes from the tarball, never its name: a release carries hestiare-<tag>/, a repo archive hestiare/.
NEWROOT=$(tar tzf "$TARBALL" | cut -d/ -f1 | sort -u)
if [ -z "$NEWROOT" ] || [ "$(printf '%s\n' "$NEWROOT" | wc -l)" != 1 ]; then
	die "update: the tarball has no single root directory, it holds: ${NEWROOT:-nothing}"
fi
tar xzf "$TARBALL" -C "$RUNDIR" || die "update: could not unpack the tarball"
NEWTREE="$RUNDIR/$NEWROOT"
[ -d "$NEWTREE" ] || die "update: the tarball listed $NEWROOT but unpacked no such directory"
GOT=$(cat "$NEWTREE/VERSION" 2> /dev/null)
if [ -n "$TARGET" ]; then
	[ "$GOT" = "$TARGET" ] || die "update: asked for $TARGET, the tarball says '${GOT:-nothing}' - refusing"
else
	# The override names no tag, so the tarball's VERSION is the target. Still before the first change.
	[ -n "$GOT" ] || die "update: the tarball carries no VERSION, so there is nothing to call this run - refusing"
	version_ge "$GOT" "$TREE" \
		|| die "update: the tarball is $GOT, older than the installed $TREE - an update never goes backwards. Nothing was touched."
	TARGET="$GOT"
	say "[ * ] The tarball says it is $TARGET"
fi

# Nothing on the box has changed yet. The mark keeps this process from handing over to itself again.
if [ -z "${HESTIA_UPDATE_HANDOVER:-}" ] && [ -f "$NEWTREE/update.sh" ] \
	&& ! cmp -s "$NEWTREE/update.sh" "$0"; then
	say "[ * ] $TARGET carries a different updater - handing over to it before anything is changed"
	export HESTIA_UPDATE_HANDOVER="$TARGET" HESTIA_UPDATE_TARBALL="$TARBALL" HESTIA_UPDATE_RUNDIR="$RUNDIR"
	exec bash "$NEWTREE/update.sh" "$@"
fi

say "[ * ] Securing the current tree"
tar czf "$RUNDIR/install-root.tar.gz" -C "$(dirname "$HESTIA")" "$(basename "$HESTIA")" \
	|| die "update: could not write the backup of $HESTIA - nothing has been changed yet"
cp -a "$HESTIA/conf/hestia.conf" "$RUNDIR/hestia.conf" || die "update: could not secure hestia.conf"

# Before the overlay, so it exists if that step is the one that fails. The executor names this path.
cat > "$RUNDIR/rollback.sh" << ROLLBACK
#!/bin/bash
# Put back what this run replaced. Refuses once the run passed its point of no return, because from
# there files are not the whole story any more.
set -e
RUN="\$(cd "\$(dirname "\$0")" && pwd)"
if [ -f "\$RUN/point-of-no-return" ]; then
	echo "This run passed the point of no return - putting files back would not undo it."
	echo "Go forward instead: hestia update"
	exit 1
fi
systemctl stop caddy hestia-php 2> /dev/null || true
rm -rf "$HESTIA"
tar xzf "\$RUN/install-root.tar.gz" -C "$(dirname "$HESTIA")"
cp -a "\$RUN/hestia.conf" "$HESTIA/conf/hestia.conf"
# The per-entry paths. An action may write outside $HESTIA (the systemd units of the proc hardening
# and the PHP limit are the near cases), and the tree tarball does not know those files. Without
# this an entry calling itself reversible would not be.
if [ -d "\$RUN/paths" ] && [ -n "\$(ls -A "\$RUN/paths" 2> /dev/null)" ]; then
	echo "Putting back \$(find "\$RUN/paths" -type f -o -type l | wc -l) saved path(s) outside the tree."
	cp -a "\$RUN/paths/." /
fi
systemctl start caddy hestia-php 2> /dev/null || true
echo "Restored from \$RUN. The status version is \$(sed -n "s/^VERSION='\(.*\)'\$/\1/p" "$HESTIA/conf/hestia.conf" | head -1)."
# Said out loud, because it is the one thing this cannot do: a path that did not exist before the run
# has no copy here, so a file an entry created stays where it is.
echo "A file an entry created did not exist before and is still there; only saved paths came back."
ROLLBACK
chmod 700 "$RUNDIR/rollback.sh"

say "[ * ] Stopping the panel and unpacking $TARGET over $HESTIA"
# Webmail runs behind the panel Caddy too, so a run that stops after this point must not leave it down.
trap 'systemctl start caddy hestia-php 2> /dev/null || true' EXIT
systemctl stop caddy hestia-php 2> /dev/null || true
cp -r "$NEWTREE/." "$HESTIA/" \
	|| die "update: the overlay failed, the panel restarts on a mixed tree - put the tree back with $RUNDIR/rollback.sh"

# A pin of 'release' would resolve to an older public tag, and the nightly --check would refuse.
# Same pin the installer writes.
if [ -n "$OVERRIDE" ]; then
	if "$HESTIA/bin/h-change-sys-config-value" RELEASE_BRANCH "$TARGET" > /dev/null 2>&1; then
		say "[ * ] Pinned RELEASE_BRANCH to $TARGET"
	else
		say "[ ! ] could not pin RELEASE_BRANCH to $TARGET, so the nightly check will refuse until it is set"
	fi
fi

say "[ * ] Deriving the plan from the new tree"
"$HESTIA/bin/h-list-sys-updates" json "$TARGET" > "$RUNDIR/update.conf" 2> "$RUNDIR/derive.err" || {
	say "$(cat "$RUNDIR/derive.err")" >&2
	die "update: the manifests of $TARGET are not sound - back: bash $RUNDIR/rollback.sh"
}

# After the derivation: only now is it known which paths an entry touches.
mkdir -p "$RUNDIR/paths"
while read -r _p; do
	[ -n "$_p" ] && [ -e "$_p" ] || continue
	mkdir -p "$RUNDIR/paths$(dirname "$_p")"
	cp -a "$_p" "$RUNDIR/paths$_p" 2> /dev/null || true
done < <(jq -r '.entries[].paths[]?' "$RUNDIR/update.conf" 2> /dev/null | sort -u)

"$HESTIA/sbin/h-update-hestia" "$RUNDIR/update.conf"
