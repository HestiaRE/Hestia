#!/bin/bash
# HestiaRE updater: find the release, secure what gets overwritten, unpack it, derive the plan from
# the new tree, hand it to the executor. One root, $HESTIA.
#
# The self-update is finished before anything on the box changes. update.sh ships inside the release
# tarball, so the tarball this run needs anyway carries the newer updater: it is fetched, verified,
# and if it differs from the running file this process exec's into it, once, with the tarball handed
# over so it is not fetched twice. No second fetch path, no swap in the middle of the work.
#
# Usage: update.sh [--check]

# No `set -u` here: main.sh reads $user before it is set (main.sh:115), the way every h-* command
# expects. A lone nounset in the one script that sources it aborts before anything runs.
umask 0022

HESTIA="${HESTIA:-/usr/local/hestia}"
# shellcheck source=/usr/local/hestia/include/release.sh
source "$HESTIA/include/release.sh"

CHECK_ONLY=no
[ "${1:-}" = "--check" ] && CHECK_ONLY=yes

# A box below this is reinstalled, not updated: no release under it carried an updater, so there is
# no run this one could be finishing. After the handover the target's own literal decides, which is
# the second look at the question and the reason no manifest field repeats it.
UPDATE_MIN_VERSION='v0.19.0'

tree_version() { cat "$HESTIA/VERSION" 2> /dev/null; }
status_version() { sed -n "s/^VERSION='\(.*\)'\$/\1/p" "$HESTIA/conf/hestia.conf" | head -1; }

say() { printf '%s\n' "$*"; }
die() {
	printf '%s\n' "$*" >&2
	exit 1
}

#----------------------------------------------------------#
#                   Find, and hand over                    #
#----------------------------------------------------------#

# HESTIA_RELEASE_URL hands this run a tarball instead of a tag. It is a test mechanism and stops at
# the download: the resolver below, --check and the panel flag keep asking the real source, so a test
# build can never leave a box claiming an update is waiting. What the tarball is gets read from its
# own VERSION after unpacking, which is why the downgrade refusal sits further down in this mode.
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

# version_ge comes from main.sh through release.sh and carries the `v`, so the tag, the tree and this
# literal are compared in the form they are all written in. An unreadable version loses.
version_ge "$TREE" "$UPDATE_MIN_VERSION" \
	|| die "update: this box says ${TREE:-nothing}, and the lower bound is $UPDATE_MIN_VERSION - it is reinstalled, not updated. Nothing was touched."
[ -z "$TARGET" ] \
	|| version_ge "$TARGET" "$TREE" \
	|| die "update: $TARGET is older than the installed $TREE - an update never goes backwards. Nothing was touched."

# --check answers "is something newer published", and an override was handed a fixed file instead of
# an answer. Refused rather than quietly reporting on the real source: the flag it writes is read by
# the panel, and a test run has no business touching it.
[ -z "$OVERRIDE" ] || [ "$CHECK_ONLY" = no ] \
	|| die "update: --check asks the release source, and this run was handed a fixed tarball. Nothing was touched."

# --check is also the writer of the panel flag, and it sits BEFORE the early exit: behind it the
# "nothing to do" branch leaves, and a key set once would keep the banner up forever. It is the one
# place that already knows the answer. Empty, not "no": absent and empty are one state everywhere.
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

#----------------------------------------------------------#
#                      Is there work                       #
#----------------------------------------------------------#

STATUS=$(status_version)
if [ -n "$TARGET" ] && [ "$TARGET" = "$TREE" ]; then
	# Leaving is only right when the last run also finished: a status behind the tree means a plan
	# was never worked off.
	if [ "$STATUS" = "$TREE" ] && [ "$("$HESTIA/bin/h-list-sys-updates" json "$TREE" 2> /dev/null | jq -r '.count // 0')" = 0 ]; then
		say "Already on $TREE, nothing to do."
		exit 0
	fi
	say "[ ! ] tree is $TREE but the status says ${STATUS:-<empty>} - finishing that run instead of fetching"
fi

#----------------------------------------------------------#
#                    Fetch and verify                      #
#----------------------------------------------------------#

RUNDIR="${HESTIA_UPDATE_RUNDIR:-/root/hestiare-update/$(date '+%Y-%m-%d_%H-%M-%S')_${TREE:-unknown}_${TARGET:-override}}"
mkdir -p "$RUNDIR" || die "update: cannot create $RUNDIR"
say "[ * ] Run directory: $RUNDIR"

TARBALL="${HESTIA_UPDATE_TARBALL:-$RUNDIR/hestiare-${TARGET:-override}.tar.gz}"
if [ ! -s "$TARBALL" ]; then
	release_fetch_asset "" "$TARBALL" "$TARGET" \
		|| die "update: could not fetch the release tarball"
	[ -s "$TARBALL" ] || die "update: the downloaded tarball is empty"
fi

# A missing checksum is not an error, a wrong one is. Compared by value rather than with
# `sha256sum -c`, which also insists on the file name the publisher wrote: the same bytes fetched
# through an override arrive under a different name. More than one entry is refused instead of
# passing on the strength of whichever line happened to match.
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

# The root directory comes from the tarball itself, never from its name: the release asset carries
# hestiare-<tag>/, an archive built straight from the repository carries hestiare/. Exactly one entry,
# or this is not a release tarball and nothing below it would be true.
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
	# The override asked for no tag, so the tarball's own VERSION is what this run goes to. The
	# downgrade refusal happens here for that reason, and it is still before the first change to the
	# box: only this run directory has been written so far.
	[ -n "$GOT" ] || die "update: the tarball carries no VERSION, so there is nothing to call this run - refusing"
	version_ge "$GOT" "$TREE" \
		|| die "update: the tarball is $GOT, older than the installed $TREE - an update never goes backwards. Nothing was touched."
	TARGET="$GOT"
	say "[ * ] The tarball says it is $TARGET"
fi

# The handover. Nothing on the box has changed yet: only this run directory was written. The mark
# says this process already IS the newer updater, so it cannot hand over to itself again.
if [ -z "${HESTIA_UPDATE_HANDOVER:-}" ] && [ -f "$NEWTREE/update.sh" ] \
	&& ! cmp -s "$NEWTREE/update.sh" "$0"; then
	say "[ * ] $TARGET carries a different updater - handing over to it before anything is changed"
	export HESTIA_UPDATE_HANDOVER="$TARGET" HESTIA_UPDATE_TARBALL="$TARBALL" HESTIA_UPDATE_RUNDIR="$RUNDIR"
	exec bash "$NEWTREE/update.sh" "$@"
fi

#----------------------------------------------------------#
#                        Secure                            #
#----------------------------------------------------------#

say "[ * ] Securing the current tree"
tar czf "$RUNDIR/install-root.tar.gz" -C "$(dirname "$HESTIA")" "$(basename "$HESTIA")" \
	|| die "update: could not write the backup of $HESTIA - nothing has been changed yet"
cp -a "$HESTIA/conf/hestia.conf" "$RUNDIR/hestia.conf" || die "update: could not secure hestia.conf"

# Written before the overlay, so it exists even if that step is the one that fails. The executor
# names exactly this path.
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

#----------------------------------------------------------#
#                    Unpack and derive                     #
#----------------------------------------------------------#

say "[ * ] Stopping the panel and unpacking $TARGET over $HESTIA"
systemctl stop caddy hestia-php 2> /dev/null || true
cp -r "$NEWTREE/." "$HESTIA/" || die "update: the overlay failed - put the tree back with $RUNDIR/rollback.sh"

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

#----------------------------------------------------------#
#                        Play it                           #
#----------------------------------------------------------#

"$HESTIA/sbin/h-update-hestia" "$RUNDIR/update.conf"
