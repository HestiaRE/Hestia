# HestiaRE — Troubleshooting & Install Recovery

Recovery strategy for a failed or interrupted installation.

## Decision (issue #61)

HestiaRE deliberately uses **fail-clear + resume**, not automatic rollback:

- **Stop clearly** on the first error (`set -eo pipefail` + an `ERR` trap that
  prints the failing exit code/line and the tail of the install log).
- **Resume by re-running.** Each install stage writes a sentinel
  (`/etc/hestia/.done.<stage>`); a stage whose sentinel exists is skipped. So
  re-running the installer continues from the failed stage — no flag needed.
- **No automatic rollback.** Auto-undoing package installs, service setup and
  DB initialisation is itself error-prone and, for an internal tool at this
  scale, not worth the complexity/risk. Recovery is: read the error, fix the
  cause, re-run.

## When an install fails

1. **Read the error.** `h-install-hestia` prints the failing stage/line and the
   last lines of the log. Full log:

   ```
   /var/log/hestia/install.log
   ```

2. **Fix the root cause** (e.g. network/APT mirror hiccup, a missing package, a
   wrong value in `/etc/hestia/install.conf`).

3. **Re-run the installer** — completed stages are skipped automatically:

   ```bash
   h-install-hestia        # or:  hestia install
   ```

## Stages and their sentinels

Run order, each guarded by `/etc/hestia/.done.<stage>`:

| Stage              | Sentinel                     |
|--------------------|------------------------------|
| base               | `/etc/hestia/.done.base`     |
| panel              | `/etc/hestia/.done.panel`    |
| web                | `/etc/hestia/.done.web`      |
| db                 | `/etc/hestia/.done.db`       |
| mail (if present)  | `/etc/hestia/.done.mail`     |
| security           | `/etc/hestia/.done.security` |
| addons             | `/etc/hestia/.done.addons`   |
| configure          | `/etc/hestia/.done.configure`|

`init_hestia_structure`, `install_tools` and `finalize_install` are idempotent
and run every time.

### Force a single stage to run again

Delete its sentinel, then re-run the installer:

```bash
rm -f /etc/hestia/.done.web
h-install-hestia
```

### Start over from scratch

```bash
rm -f /etc/hestia/.done.*
h-install-hestia
```

Stages are written to be idempotent (`already installed? return 0` checks,
`.done.*` guards), so re-running is safe.

## Regenerate the install recipe

If the problem is a wrong choice in the recipe (`/etc/hestia/install.conf`),
re-run the wizard, then the installer:

```bash
hestia configure --force      # on an installed box, --force is required
hestia install                # or:  h-install-hestia
```

`--force` is not a formality. The wizard **replaces** `install.conf` and never reads
the existing one, so every answer you do not give again is lost. And because each
`.done.*` stage marker is the sha256 of that file, a rewrite invalidates all of them:
the next `hestia install` runs every stage again, not just the one you wanted to fix.
Without `--force` the wizard refuses and says the same thing. When you are
re-installing from scratch instead, `bash install.sh --force` carries the flag through.

## When an update fails

`hestia update` works in one run directory under `/root/hestiare-update/`, named after the time and
the two versions. Nothing prunes it: what a run wrote stays until you remove it.

The run directory holds the tarball it fetched, `install-root.tar.gz` (the whole install tree as it
was), `hestia.conf`, the plan as `update.conf`, the log, and `rollback.sh`.

**What the backup covers, and what it does not.** It covers the install tree and the panel config.
It does **not** cover packages, system users, databases, `/home`, or anything under `/var`. An entry
that installs a package or restarts a service is not undone by putting files back, which is why the
run draws a line: the moment it reaches the first such entry it writes `point-of-no-return` into the
run directory, and `rollback.sh` refuses from there.

Before the line:

    bash /root/hestiare-update/<run>/rollback.sh

After it, the way out is forward:

    hestia update

The plan is derived again on every run, so entries that already took effect fall out through their
own condition and only the rest is worked off.

**A stopped run is visible.** The release is unpacked before the plan runs, and the status version is
written only when the plan is done, so a tree that is newer than `VERSION` in `hestia.conf` means a
run did not finish. `h-check-sys-smoke` says so, with the command that finishes it.

## Common causes

- **APT mirror / network errors during "base packages".** Transient — re-run.
  `APT::Acquire::Retries "3"` is configured to absorb brief failures.
- **External repo signing-key issues (Sury, MariaDB).** Check connectivity to
  `packages.sury.org` / `dlm.mariadb.com`, then re-run.
- **Recipe values.** Inspect `/etc/hestia/install.conf` (`COMPONENT_*`,
  `INSTALL_OS`, `INSTALL_PROFILE`); regenerate via the wizard if wrong.
