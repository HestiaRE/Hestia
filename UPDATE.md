# UPDATE.md - updating a box

> `PATHS.md` has the filesystem layout, `STRUCTURE.md` why it diverges from HestiaCP,
> `CODEMAP.json` which file does what. **This file is the operator's view:** what an
> update does, what it promises, and what it does not.

**Living doc.** A change to the update path, the run directory or the manifest format
updates this file in the same PR.

---

## What this is not

HestiaRE ships no `hestia-*` apt packages, so `apt upgrade` never updates the panel. There is also
no automatic update, no trigger in the panel, and no background job that installs anything. The
panel can say that a release is waiting; installing it is a decision a human makes at a shell.

There is no beta and no nightly. Development happens against a private forge, a release is pushed to
the public repo, and a box follows either the newest tag or one it is pinned to.

## The one command

```
hestia update            # find, fetch, verify, secure, unpack, apply
hestia update --check    # say what would happen, change nothing
```

If the tree has no `update.sh` at all, it is fetched by hand the way `install.sh` arrives:

```
curl -fsSL https://raw.githubusercontent.com/HestiaRE/Hestia/<tag>/update.sh -o /root/update.sh
bash /root/update.sh
```

For testing a build that is not a published release, `HESTIA_RELEASE_URL` names a tarball directly and
`HESTIA_RELEASE_TOKEN` authenticates the fetch. It replaces the download and nothing else: which
release a box follows is still answered by the source, `--check` refuses rather than report on one
thing while fetching another, and the version comes from the tarball's own `VERSION`. It lives in the
environment of a single run, is written nowhere and appears in no status output. It is a test
mechanism, not a second channel and not something an operator configures.

## The lower bound

**v0.19.0.** A box below it is reinstalled, not updated, and the fetched updater above will say so
rather than start. No release under v0.19.0 carried an updater, so there is no run one of them could
be finishing and no state this one could reason about.

The bound is a literal in `update.sh`, which means the copy that decides is the one in the release
being installed: the old updater checks it, hands over to the new one, and the new one checks its own
before a single file on the box is touched. A release can therefore raise the bound for itself, and
the smoke refuses a bound above the tree it ships in - that would be a release unable to carry itself
onward.

## Which release a box follows

`RELEASE_BRANCH` in `hestia.conf` is either `release`, the newest tag of the source, or a `vX.Y.Z`
pin. Nothing else is a valid value.

```
h-change-sys-release release      # follow the newest
h-change-sys-release v0.19.0      # stay on exactly this tag
h-change-sys-release              # show the current setting
```

A pin is checked before it is written. A tag nobody carries would stop updates without a word, and a
tag below the installed version could never apply, so the command refuses both rather than storing
them. An update never goes backwards; `update.sh` refuses a downgrade as well, in case the value
arrived some other way.

## What a run does, in order

1. Ask the source which tag this box should run. No answer means no version is guessed and nothing
   is touched. A box below the lower bound, or a target older than the installed version, is refused
   here - before anything is fetched.
2. Nothing newer, last run finished, derived list empty: leave. Nothing else happens.
3. Fetch the tarball into a fresh run directory and verify it. A missing checksum is not an error
   (older releases carry none), a wrong one refuses to unpack.
4. **Hand over to the updater inside that tarball** if it differs from the running one. `update.sh`
   ships in every release, so the tarball this run needs anyway carries the newer updater: one
   query answers both questions, and the handover is finished before anything on the box changes.
5. Secure: the whole install tree as a tarball, `hestia.conf`, and the rollback script.
6. Stop the panel, unpack the release over the install tree.
7. Derive the plan **from the new tree** and secure the paths its entries declare.
8. Work the plan off, entry by entry.
9. Only when the list is done: repair, object registries, status version, clear the panel flag,
   start the panel.

## The run directory

Everything a run does lives in one directory under `/root/hestiare-update/`, named after the time
and the two versions:

```
hestiare-v0.19.0.tar.gz   what was fetched
install-root.tar.gz       the install tree as it was
hestia.conf               the panel config as it was
paths/                    every path an entry declared, as it was
update.conf               the plan that was derived
update.log                what happened, with timestamps
rollback.sh               the way back
point-of-no-return        present once the run passed the line
```

**Nothing prunes it.** Retention is the operator's call; a run directory stays until it is removed.

## The point of no return

Entries are ordered so that everything reversible runs first. The moment a run reaches the first
entry that files cannot undo - a package removed, a service restarted, a path deleted - it writes
`point-of-no-return` into the run directory and says so on the terminal.

Before the line:

```
bash /root/hestiare-update/<run>/rollback.sh
```

It puts back the install tree, `hestia.conf` and every saved path, then starts the panel. After the
line it refuses, and the way out is forward: `hestia update` derives the plan again, so entries that
already took effect fall out through their own condition and only the rest is worked off.

## What the backup covers, and what it does not

It covers the install tree, the panel config, and the paths the entries in this run declared.

It does **not** cover packages, system users, databases, `/home`, or anything under `/var`. A file
an entry created did not exist before the run, so it has no copy in the run directory and stays
where it is. That is the reason the line exists at all.

## A stopped run is visible

The release is unpacked before the plan runs, and the status version is written only when the plan
is done. A tree newer than `VERSION` in `hestia.conf` therefore means a run did not finish, and
`h-check-sys-smoke` says so with the command that finishes it. That difference is the wanted signal,
not drift.

## The panel banner

`update.sh --check` writes `UPDATE_AVAILABLE`, and a daily cron entry asks. The banner appears for
an admin only after the next login, because the panel session is filled at login.

## Manifests

What a box has to catch up on beyond the new files lives in `share/updates/`, one JSON file per
release. The rules for writing one are in `share/updates/README.md`; the short version is that an
entry is data, never shell, it carries exactly one action, and its condition has to be false once
that action succeeded.

The list is derived on every run from the manifests and the state of this box, so a repetition after
a failed run converges instead of replaying what is already done.

```
h-list-sys-updates          # what this box would catch up on
h-list-sys-updates json     # the same as the executor reads it
```
