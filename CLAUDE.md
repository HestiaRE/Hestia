# CLAUDE.md – HestiaRE Development Instructions

> Load this file first. Then read `CODEMAP.json` to identify relevant files
> before opening anything else. Never read the entire codebase blindly.

---

## WHAT IS HESTIARE

HestiaRE (Refined Edition) is a lean, official derivative of HestiaCP.
Author is an original HestiaCP co-founder. This is a personal professional
tool, not a community project, not commercial.

Targets (all first-class, equal priority): Debian 12, Debian 13, Ubuntu 24.04 LTS,
Ubuntu 26.04 LTS. Every feature must work on all four; test on the VM fleet.
Scale: ~300 domains, ~30 customers, ~15-20 servers.

Tagline: "Rethink. Rebuild. Reboot."

---

## GROUND RULES

These are absolute. Never deviate, never re-suggest rejected items.

**Never re-introduce:**
- bind9, vsftpd, Web Terminal, REST API, SpamAssassin, Software Installer

**Never suggest:**
- PHP frameworks of any kind
- Docker for HestiaRE itself
- External repos beyond: MariaDB repo, Sury PHP
- Node.js on the Gitea Act Runner host
- `ALL=(ALL) NOPASSWD:ALL` sudo rules

**Always prefer:**
- OS repos over external repos (challenge external first)
- Modular, individually removable components
- Minimal explicit sudo rules per command
- Conservative approach over clever approach

**Every loop over a file pattern guards the empty case.** With no match the shell hands the body the
PATTERN, and the body then works on a path that does not exist - three times already (#826, #1031,
#1033). So `[ -e "$x" ] || continue` is the FIRST line of the body, and the word list is quoted up to
the pattern itself (`"$dir"/*`, never `$dir/*`). `for x in $*` globs as well; write `"$@"`.
`.gitea/tools/lint-shell.sh` derives the set from the code and fails on an unguarded loop. Not
nullglob: it is not function-local, an abort through `check_result` never restores it, and it would
make a pattern borne by a VALUE vanish silently instead of being wrong.

---

## COMMENT STYLE

Comments are terse. Nobody reads a wall of them. A comment earns its place by
explaining **why** the code must be this way; the **how** is the code's job, so
don't narrate it. Condense what the code *does*, keep why it *must*.

**No em-dashes (ground rule).** Never use an em-dash (`—`) or en-dash (`–`) in code
or comments; use a plain ASCII hyphen (`-`) or restructure. A hyphen reads the same
in every editor and terminal and avoids the 3-byte-per-character width surprise.
Prose docs and the translations under `web/locale/` are exempt. This is a cosmetic
convention, not security-relevant and not smoke-enforced (the check was dropped);
sweep for it opportunistically in code/comment cleanup rounds over the panel and CLI
(`bin/`, `web/` minus `web/locale/`).

**The header carries the file, the code carries itself (#1176).**
- **Header:** the directives below plus at most two or three lines saying what the command is for. No history.
- **Inline:** nothing that tells a story - no issue numbers, no measurements, no derivation, no "first we tried".
  At most one line with a *why* where the code would otherwise look arbitrary. What falls out of the code
  this way lives in `git log`/`blame` and the issues.
- **Guard comments stay**, one line each: a *why not otherwise* at a spot someone would otherwise "fix"
  (sftp `\[` because of Tcl, ftp with a bare address, a variable name that is a dispatch contract), and a
  line naming a file this one has to keep in step with.
- **No section banners.** The upstream `#---# Variables & Functions #---#` blocks go; a blank line separates.
- **Never restate the code.** Above `pm.max_children = 8` the only thing worth writing is *why 8*.
- **Line width: up to 120 columns.** One wide line beats three narrow ones.

**Do NOT touch (these are API/tooling, not prose):**
- Header directives parsed by `include/help.sh` for `--help` and `check_args`: `# info:`, `# options:`,
  `# example:`, `# labels:`, `# caller:` (every `bin/h-*` must keep a non-empty `# info:`). `# options:`
  is the usage an empty call prints, so it has to match the arguments the code reads. Machinery gets
  `# labels: internal` plus `# caller: <who>`; `h-search-command` leaves it out.
- `# shellcheck disable=…` / `# shellcheck source=…`, editor modelines,
  license/attribution headers from the upstream heritage.

**A comment change is its own commit**, never inside a change of behaviour, and its hash goes into
`.git-blame-ignore-revs` so blame keeps pointing at the commit that made the code.

**Verification (mechanical):** `shfmt -mn` of every touched shell file is identical before and after. A
line pattern like `^\s*#` is not enough: it passes a deleted `#` line inside a heredoc, and that is
content. A comment inside an awk/sed string makes the comparison differ - a false alarm, checked by hand.
Each run carries its control: delete one heredoc line on purpose, `shfmt -mn` must differ.
PHP under `web/`: the token stream without comments and whitespace is identical before and after. `php -w`
alone is not enough, a removed comment leaves a space behind (measured), so compare the tokens:
```
php -r 'foreach (token_get_all(file_get_contents($argv[1])) as $t) { if (is_array($t) && in_array($t[0],
  [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE])) continue; echo is_array($t) ? token_name($t[0])." ".$t[1] : $t, "\n"; }' F
```
Same control: delete one heredoc or inline-HTML line, the stream must differ. ini and Caddy files have no
such tool, so there and only there the proof is the line pattern: every added or removed line matches
`^\s*(;|#|//)`. Plus `json.tool` on JSON and a smoke run.

---

## ARCHITECTURE

### Paths
```
/usr/local/hestia/     install root (bin, web, conf, data, modules)
/etc/hestia/           instance config (outside git, survives updates)
/home/$user/              user data (HestiaCP compatible)
```

### CLI conventions
```
h-*    HestiaRE commands (renamed from v-* in Issue #22)
```

- There are no v-* aliases (removed in #1176). Upstream changes are reimplemented, never
  cherry-picked, so nothing needs the old names. Own aliases belong outside `$HESTIA/bin` (e.g.
  `/usr/local/bin`), and `--help` does not work under them. Every v-* symlink in `$HESTIA/bin` is a
  leftover: `configure_hestia` removes all of them, `h-check-sys-smoke` fails on any, the 0.24 update
  entry fires only while `v-add-user` exists.
- Removal verb is `h-delete-*` across the board (upstream `v-delete-*` parity).

### Panel webserver
Caddy (OS repo, port 8083) — replaces hestia-nginx.
PHP: Sury 8.3, isolated FPM pool — replaces hestia-php.

### Always installed components
php multi (Sury 5.6–8.4), mariadb (ext repo), phpmyadmin (OS), caddy (OS),
iptables, fail2ban (OS), ipset, composer (system-wide), wp-cli (system-wide)

**The web model decides the web-server packages, not just who serves.** apache-only means no
nginx on the box; nginx-only and both install nginx. Mail-only gets nginx through the mailfront
model (#193): webmail vhosts and ACME termination, but an EMPTY WEB_SYSTEM - customer web is
absent, every web command's is_system_enabled guard refuses, and WEBMAIL_FRONT carries the nginx
for the webmail chain (webmail_front in include/main.sh).

### Standard profile adds
apache2 (OS only — no Sury apache2 repo),
exim4 (OS), dovecot (OS), rspamd (OS), roundcube + password plugin (OS)

nginx acts as reverse proxy in front of apache2 for customer vhosts - in the both model.
In apache-only there is no nginx at all.

### Minimal profile
Standard install minus apache2 and mail stack.

### Optional (h-add-*/h-delete-* commands)
proftpd, clamav, postgresql, redis, opensearch, docker-proxy, filemanager

---

## REPOSITORY STRUCTURE

### Branches
```
main              protected, release-ready only, PR required
dev               integration branch, PR required (Admin can push directly)
feature/N-desc    your working branch, N = Gitea issue number
upstream/hestiacp HestiaCP snapshot, READ ONLY, never modify
```

### Key files
```
install.sh        bootstrap: prereqs, fetch release, run wizard, hand off to h-install-hestia
include/wizard.sh    interactive wizard (manifest-driven) → writes /etc/hestia/install.conf
include/helper.sh    installer helpers: hestia_apt, load_os_profile, seed_hestia_etc
sbin/h-install-hestia non-interactive installer (reads install.conf, COMPONENT_*-gated)
sbin/hestia       umbrella: hestia install|configure|update|uninstall|status
VERSION           the version this tree is; committed before each tag, release.yml compares
CODEMAP.json      component map — read before exploring the codebase
CLAUDE.md         this file
```

### Directories (HestiaCP origin, being refined)
```
bin/              CLI commands (h-*)
include/          shared bash function libraries
share/            install-time service configs + assets (absorbed the old install/ tree, #119)
web/              panel UI (plain PHP, no framework)
src/              frontend assets
conf/             service configuration templates
```

---

## WORKFLOW — EVERY TASK

1. Read `CODEMAP.json` → identify relevant files only
2. Create branch: `git checkout -b feature/N-short-desc`
3. Make changes, commit with: `[#N] type: description`
   — larger changes also add a `CHANGELOG.md` entry (Unreleased section) in the same PR
4. **Blast-radius check** (below) — mandatory whenever an existing function, command signature,
   shared variable or config VALUE was touched
5. Push: `git push origin feature/N-short-desc`
6. Open PR to `dev` (host + API call in `CLAUDE.local.md`)
7. Stop. Do not merge. Author reviews and merges.

**Never push to `dev`, `main`, or `upstream/hestiacp` directly.**

### Blast-radius check (before every PR that touches existing code)

Changing something that already existed — especially inherited from HestiaCP — is only safe once you
know who else uses it. The issue scope is *not* the change's scope. Enumerate consumers **across the
whole tree**, `bin/` + `include/` + `web/` + `share/` + `install.sh`, not just the files in the diff:

- **A shared function**: every caller. A grep of the defining file alone is not an audit — it is how
  four live callers of a "dead" helper get missed.
- **A command signature or argument**: every caller, including `web/` PHP (`exec(HESTIA_CMD . ...)`)
  and `share/` configs (fail2ban actions, systemd units, cron pipes).
- **A config VALUE, not just a key**: every comparison against the old literal. Renaming
  `FIREWALL_SYSTEM` from `iptables` to `nftables` left six sites comparing against the old string; the
  panel's firewall row then fell through to `systemctl` and **destroyed the live ruleset**.
- **A validator or guard**: hardening one makes previously-dead checks fire. Find the callers that were
  silently passing before, and fix them in the same PR rather than exempting them.
- **A MOVED file: grep the bare name, not the old path.** A path pattern only finds references that
  spell the separator. `sbin/` cost seven live call sites written `"$BIN/hestia-php-confd"` — a
  variable, so `bin/hestia-php-confd` matched none of them and a fresh install died. `func/ -> include/`
  then cost `.php-cs-fixer.dist.php`, which writes `__DIR__ . "/func"` with no trailing slash, so the
  formatter silently refused to run. Same shape twice: sweep the **basename on its own**, then every
  variable that could prefix it, and only then the full path.
- **`h-install-hestia` is a first-class caller of `h-*`** (deliberately — one code path, never an
  installer copy that drifts). So every guard must also make sense at **install time**, against a
  half-built box: the state it rejects may be exactly what an earlier install stage just produced.
  `h-add-web-php`'s "already installed" abort keyed on the fpm binary, but the panel PHP is installed
  before the multi-PHP loop re-runs it for the full extension set — every fresh install on all four
  targets died there. Existence checks belong in the update path; a fresh install starts empty.

Prefer a **static sweep over sampling**: run the check against every call site, not a handful of
commands that happen to come to mind. Sampling is what let those five commands through the first time.
Verify empirically on a VM afterwards — including the failure path, not just the success path.

Anything that survived only by luck (a fallback that happened to be right, a check that never ran)
is a finding: fix it or record it, do not leave it silent.

### Guard review: how can the reference set shrink unnoticed?

Ask this of **every new or edited guard**, before it ships. A guard compares something against a
reference set; if that set can quietly become smaller, the guard goes green by looking at less —
which is worse than no guard, because it is trusted. Four cases have already occurred:

- an **empty key set** persisted as the registry, so every later repair agreed with nothing
- **`grep -c`** printing its count at exit 1, so a fallback `echo 0` doubled the output and `0 != 0`
- two **hard-wired service directories**, so moving a template out of them dropped it from coverage
- a recount keyed on the **local IP** while the records store the NAT address, so it counted 0 on
  every NAT'd box — and "correcting" a healthy counter to that 0 is real damage

Concretely: derive the set from the data (find the files, read the key list) rather than listing it;
prefer deciding by content over deciding by path, since a rename silently changes a path; and make
an empty or zero reference set fail rather than pass. State in the guard's comment what it does
**not** cover, so a three-quarter guard is not mistaken for a whole one.

**A checker that reports "nothing to check" is a suspicion, not a pass.** Three times now the symptom
was identical - a green line that meant nothing had been looked at: the shfmt gate comparing against
a base it never read, the shell gate answering a change to the installer with "no changed shell
files" because `sbin/` was outside its predicate, and a smoke probe counting 31 untouched files from
a trace that never ran. Where a checker can legitimately have nothing to do, it must say how much it
looked at, and a run that looked at nothing must fail. Where a second hand-kept list describes the
same surface, something has to hold the two together - a list nobody measures is the next gap.

### Every negative proof needs a positive control in the same run

**Showing that something does NOT happen is worthless without showing that something CAN happen.**
"Refused", "not logged", "not written" and "never reached the log" look identical to "the probe
never arrived": a login that silently failed, a request that died at the CSRF wall, a branch
sitting inside a gate that was false, a log channel that writes nowhere. Three green-for-the-wrong-
reason measurements in one day came from exactly this.

So every such run carries a control that MUST come out positive, and the negative result only
counts when the control did:

- smuggle a value that must appear (a bandwidth field the form never offers, and it lands)
- write a marker through the same channel you are grepping (a log line that must show up)
- flip the same switch the legitimate way once, and see the record change

If the control fails, the verdict is "not verifiable", never "clean". A guard that cannot show
its own reach is a guard nobody should trust.

### Fresh-install verification (installer / firewall / fail2ban changes)

Any change to `h-install-hestia`, `include/fail2ban.sh`, the firewall renderer, or the service configs they
apply must be verified against a **genuinely fresh from-scratch install**, not only a re-run of the apply
step on an already-populated box. Re-running on a box that already has domains, proftpd, a whitelist, etc.
is what hid two separate breaks: the v0.12.2 template-include gap, and the installer aborting in the
fail2ban stage (a jail on a not-yet-existent log, and `grep` on an absent `excludes.conf` under
`set -eo pipefail`). Both were green on a re-run because the missing state existed by then. A smoke run
after the fact is not a substitute: by then the logs and files exist. If a full reinstall is not possible
in the moment, at minimum reproduce the empty starting state (no domains, addon not yet installed, config
file absent) before trusting the result.

### Commit message format
```
[#N] type: short description

type: fix | feat | refactor | remove | docs | test
```

### PR

Open the PR against `dev` — never merge it yourself; the author reviews and merges.
The remote host, the exact API call, use of TOKEN and the test-VM fleet live in
`CLAUDE.local.md` (untracked, so the personal host stays off the public GitHub mirror).

### Before every minor release

- **Merge `dev` into `main` FIRST, then tag.** The tag hangs off that merge commit, so a tag cut
  before it silently ships the previous state. This has now cost two releases: `v0.13.1` went out
  byte-identical to its predecessor and the fleet was reinstalled with the very bug it was meant to
  fix, and `v0.16.0` missed a security fix by twelve hours. Nothing that runs after the tag repairs
  it - the guard at the end of `.gitea/workflows/mirror-on-release.yml` only makes it loud. Say it
  out loud *before* the release, not in a PR footnote.
- **Bump `VERSION` in a commit before the tag, onto the commit the tag will point at.** The tree
  carries its own version instead of having one stamped at build time, so `release.yml` compares
  the two and refuses a release whose `VERSION` disagrees with the tag; an empty file is refused
  as well, because two empty strings compare just fine. Anything public is a hand step: bump the
  file, merge, tag, and create the release on `main` in Gitea by hand.
- **A public version is `vX.Y`, an internal build of the minor being worked towards is `vX.Y-devN`.**
  There are no public dot releases: a hotfix becomes the next minor rather than a third component
  that on every release so far stood at `.0` and carried no information. The `Bump dev build`
  workflow is the only thing that cuts an internal build: it writes `VERSION` on `dev` and cuts the
  release in one go, so the two cannot come apart, and it takes that form and nothing else, so a
  mistyped tag cannot become a release that looks public. The suffix has to sort ABOVE `X.Y`,
  because that is what puts `share/updates/X.Y.json` in scope; `sort -V` does that for anything a
  hyphen appends, so `dev` is chosen for saying what it is, not for the ordering. Above the minor
  also means a box that ran a `-devN` refuses the later public `X.Y` as a downgrade - right for a
  test box, and unavoidable for any form that reaches the manifest.
- **Remove the internal `vX.Y-devN` releases and their tags** as part of preparing the minor. They
  are steps towards it, never mirrored, and a box that ran one refuses the public `X.Y` as a
  downgrade, so they outlive their purpose the moment the minor exists.
- Consolidate the `CHANGELOG.md` Unreleased section into the new minor (point releases stay
  inside the cycle they belong to). Archive the uncondensed text on `docs` under
  `full-changes/CHANGELOG_v0-N.md` before condensing; target density is ~120 lines per section.
- **Recompute the PROVENANCE manifests** against the current `upstream/hestiacp` snapshot, and
  reseed `source_type` from the fresh numbers. `verbatim`/`derived` is a bucketing of the measured
  `pct`, so it goes stale exactly when the numbers do — it drifted 80 entries out of step once
  (#551) because nobody re-derived it. `eigenbau` is the one curated value; a recompute never
  touches it.
- **Check the upstream pins in `share/manifest.json`** (`tachyon` + `tachyon_sha256` + `tachyon_plugins`, `wp_cli`):
  is there a newer release, and does the pinned one still verify? Bump version + sha256 together
  and refresh the last-verified date in the `$comment`. This is the ONLY patch channel these
  components have — wp-cli has no OS package at all, and Tachyon's fork can go quiet without
  anyone noticing unless the date here forces the question (#237, #584).
- **Check `php_supported` against Sury**: has a new PHP gone GA? The wizard offers only the
  intersection of Sury's packages and this list (#688) — a missing bump means the new version
  is silently never offered, an eager bump offers a beta to customers. Decide here, per release.
- **Check `share/updates/` against the update lower bound**: a manifest at or below the bound can
  never apply again, because no box below the bound is accepted, and since #1093 it is not read at
  all. It may therefore simply stay. If one is removed anyway, it is removed *together with an entry
  that removes it on a box*: the overlay never deletes and the discovery is a glob, so dropping the
  file from the tree alone leaves it in place everywhere. Deliberately a question on this list and
  not code — a cleaner with write access to the install tree is the opposite of what the artefact
  guards are for. Ask it here, decide per release.
  **A RENAME is a removal and asks the same question.** Renaming `0.21.0.json` to `0.21.json` left
  both on every box that already carried the old one (measured on 10.5.5.24), and the old one was
  passed over only because `0.21.0` sorts *above* the target `0.21-dev1` — it looked like a future
  version, not a stale one, and `0.21.0 <= 0.22` puts it back in scope at the next minor. Nothing
  re-runs, because the conditions are false by then, but a second copy of the same entries is there
  to be read against a vocabulary that may have moved on.

---

## HESTIACP COMPATIBILITY

This is non-negotiable and permanent:
- Keep `/home/$user/web|mail|conf|backup` paths
- Keep `h-*` command signatures exactly (renamed from v-*, arguments unchanged)
- Keep backup format bidirectional forever

When reimplementing HestiaCP functionality:
- Read the original in `upstream/hestiacp` branch first
- Reimplement clean for HestiaRE, do not copy entangled code verbatim

**Never cherry-pick.** Every adoption is a reimplementation, including isolated bugfixes.
Challenge each upstream change on its own: what does the *diff* actually do (not the changelog
title), do we already have it or something better, and is it an improvement worth the regression
risk? Compare per function, not per file. The PROVENANCE manifests say which of our files still
track upstream closely — that is orientation for the comparison, never a merge plan.

---

## CODEMAP

Before exploring files, read `CODEMAP.json` in the repo root.
It maps components to their entry points and related files.
If a component you need is missing from the map, note it — the map
should be updated as part of the feature branch.

---

## DEBIAN 13 NOTE

HestiaCP merged deb13 support into main (June 2026).
Dovecot 2.4 has breaking changes vs 2.3.
Always check `upstream/hestiacp` for deb13-specific handling before
implementing mail-related features.

---

## WHAT NOT TO DO

- Do not run `apt upgrade` or modify system packages unless the task requires it
- Do not create files outside the repo without explicit instruction
- Do not open PRs to `main` — always target `dev`
- Do not modify `upstream/hestiacp` branch
- Do not add external repos without flagging it first
- Do not suggest or implement a REST API