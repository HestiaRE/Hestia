# Changelog

All notable HestiaRE changes are documented here, starting from the fork
point — a HestiaCP 1.9.6 snapshot, kept read-only in the `upstream/hestiacp`
branch (upstream's own history was dropped from this file with #307).

Maintenance rule: every larger change adds an entry to the Unreleased
section as part of its PR. Only whole minors get a section - point releases are
interim builds within a cycle and their changes appear under the minor they
belong to. On release, the section gets that version number and a new Unreleased
opens above it.

## Unreleased

### Fixed

- **An update to v0.22 stopped halfway, and every v0.22 box reported its update manifests as unsound** (#1132).
  An entry in `0.22.json` waited for one in `0.21.json`, which the raised lower bound no longer reads, so the
  derivation refused the whole set: the update had already stopped the panel and unpacked the tree and ended
  on its rollback hint. The wait is gone; every box the update accepts is at v0.21 already.
  A box installed without mail (the nomail profile) failed the same check a second way: the two exim patch
  entries tested the stock Debian template, which is not ours. They now ask for `MAIL_SYSTEM` first, and
  the check stops at the first false condition, as the plan always did.
- **Mail certificate keys were world-readable, and the smoke stood red on every box with one** (#1135).
  Each mail SSL operation ran `chmod -R 0644` over `/etc/exim4/ssl/*`, which follows the links and set the key
  of every domain on the box to 644 (only the 750 directory kept them private), and handed every link to the
  user at hand. The files are now 640, exim reads them through the `mail` group, and only the domain's own
  links are touched, in all four places that did it. An update entry re-modes the keys already on a box,
  through the new condition `file_mode_wider`.
- **An account created under a restrictive umask was broken without an error** (#1134). Under `umask 077`
  `h-add-user` left `conf/` and `web/` at 700: IMAP answered `UNAVAILABLE`, every web domain 403, all with
  rc 0. The modes are now set, as `h-rebuild-user` sets them, which also repairs such an account.
  `h-add-web-domain` stops and removes the half-built folder when the directories cannot be created, and
  the webmail logs are 640 like the domain logs.

## v0.22 (2026-09-24)

Panel and webmail share one out-of-office notice, the panel can sit behind a web domain, every command
answers `--help`, and the spam path loses three defects inherited from HestiaCP.

### Added

- **With Sieve, panel and webmail share one out-of-office notice** (#784). The panel writes the
  vacation rule into the mailbox's active script, in the format of the webmail that owns it (Roundcube
  or Tachyon), and shows what the customer set there; exim no longer answers alongside, so a sender
  gets one reply instead of two. A hand-written script, or one with more than one vacation rule, is
  shown and left alone. Installing or removing the Sieve addon moves existing notices across, and a
  backup carries the notice in the form HestiaCP reads. Without Sieve exim answers as before.
- **A web domain can carry the panel** (#878). The `panel` template proxies the https side of a domain
  to the panel on loopback, so the panel port can be closed to the outside. Opt-in, set by an admin,
  nginx models only. The panel sees the real client address through it (login log, fail2ban, IP
  allow-list, session pin), and the login jail bans on 80/443 as well as on the panel port.
- **Every command answers `--help`** (#657). It prints usage, description and examples from the
  command's header and does nothing else, also for the commands that run straight away when called
  bare. About a hundred headers described other arguments than the code reads; they say what it does
  now, and eight commands that took required arguments unchecked refuse a short call.
- **The clock is stepped once a day at 07:00** (#1098). A suspend-mode snapshot costs the guest a few
  seconds every night; `ntpsec-ntpdate` is a base package now, and an update installs it.

### Changed

- **A remote PostgreSQL host has to speak TLS with a certificate this box trusts** (#1098, #980). Only
  the MariaDB client enforced the rule so far; libpq fell back to plaintext without a word. A local
  host is unchanged. **A remote host registered without TLS stops connecting after the update** until
  `TLS='no'` is added to its line in `conf/pgsql.conf`; `h-add-database-host` takes it as a ninth argument.
- **The exim autoreply follows RFC 3834** (#784). No answer to bounces, system senders, list mail,
  `Auto-Submitted` mail or spam, and one answer per sender a week instead of one per message.
- **The update lower bound is `v0.21`** (#1111). A box below it is reinstalled rather than updated.
  `0.20.0.json` and `0.21.json` are no longer read and stay in the tree.
- **Tachyon 4.2.5** (#1125): delivery receipts also report delays, PDF attachments preview again.
  Reaches a fresh install; an existing box keeps its version until `h-add-sys-tachyon` runs.
- **Adding or removing a web domain or webmail from the panel reloads the web server** (#878). The
  restart cut the very request that asked for it.

### Fixed

- **A sender could keep spam out of the spam folder with an `X-Spam-Status` of their own** (#1121). The
  spam folder and the subject tag read only the first `X-Spam-Status`, so a `No` sent along stood in
  front of rspamd's verdict, and a `Yes` moved ham into the spam folder. Incoming `X-Spam-*` headers
  are dropped before the scan; an update patches the exim template.
- **The first spam to a new mailbox waited for its first ordinary mail** (#1127). exim writes the
  quota file through the mailbox's `tmp/`, which only the first inbox delivery created, so the spam
  folder delivery deferred until then and bounced if nothing else came; every retry also tagged the
  subject again (`*** SPAM *** *** SPAM ***`). A new mailbox has `cur/new/tmp` from the start, a mail
  rebuild adds them to an existing one, and an update copies the corrected system filter.
- **A restored PostgreSQL database left the customer without rights to their own data** (#1113). The
  dump carries no owner or grants and the import runs as the admin role, so every table belonged to
  `postgres`. The objects go to the role the record names now; `h-change-database-user` had the same
  hole. `h-rebuild-databases` repairs a database a restore already broke.
- **The Roundcube password change failed on every box** (#878). `/reset/mail/` refused any request
  carrying `X-Forwarded-For`, which the panel's Caddy sets on every request.
- **A sieve redirect failed SPF at its target** (#1095). It went out with the original sender, so the
  SRS rewrite never applied; it is sent from the mailbox address now.
- **PHP ran on UTC whatever the box's time zone** (#1098). The installer matched a translated label of
  `timedatectl`; `h-change-sys-timezone` run once fixes an installed box, and it uses timedatectl now
  instead of an inherited typo's hand-written path.
- **Database hosts off the defaults** (#1098). A non-default port was left out of the status report,
  the pgsql graph and the MariaDB restore import, whose admin password also sat on its command line;
  a remote pgsql host whose admin has no database of the same name could not be added.
- **Two install-time probes depended on the language of the box** (#1098). The Sury conflict
  resolution and the wizard's MariaDB probe read apt in the C locale now.
- Smaller ones: a panel certificate set in the panel only took effect at the next restart (#878), and
  removing webmail left the nginx logs of its vhost behind in the nginx-before-apache model (#1122).

## v0.21 (2026-09-22)

Plus-subaddressing reaches sieve, the version scheme drops its third component, and the update path
loses six defects that only the first real runs could find.

### Added

- **Plus-subaddressing: `john+tag@domain` is delivered to `john`** (#596), with the suffix reaching
  sieve as `envelope :detail`. With the sieve addon exim hands local mail to dovecot over LMTP instead
  of a `dovecot-lda` pipe, whose command line could never carry the tainted recipient. An update
  installs `dovecot-lmtpd` and patches the exim template.
- **A shipped config the operator may edit is patched, not copied** (#1083). `file_patch_pending`
  answers in three: apply, already applied, or loud because somebody edited exactly those lines.

### Changed

- **A version is `vX.Y`, an internal build `vX.Y-devN`** (#1106). A hotfix becomes the next minor.
- **A manifest at or below the update lower bound is no longer read** (#1093), so a copy an earlier
  release left on a box cannot reach a plan.
- **The release mirror is `hestiare.com`** (#1100); a `HESTIARE_MIRROR` of the box's own is kept.

### Removed

- **The `dir_not_empty` condition** (#1076). Its only entry took the sharper `dir_has_secret_value`.

### Fixed

- **A box updated from a handed-in tarball mailed a failure every night** (#1104). The nightly check
  read the installed tree as a downgrade; the update pins the version as the install already did.
- **The nightly backup of a customer with a PostgreSQL database failed on a non-English box** (#1096).
  psql's German row footer survived a filter on `row` and broke the size arithmetic. Both cron files
  HestiaRE writes pin `LC_ALL=C` now.
- **The space a backup needs could be summed from numbers nobody measured** (#1099): a box with both
  database engines dropped every database, an empty `du` counted as zero. A failed measurement fails
  the run.
- **The daily update check never ran, so the panel banner never appeared** (#1091). sudo refused the
  crontab line; the check moved into `bin/` as `h-check-sys-update`.
- **The updater ran the new release against the old library** (#1080), so the checksum fetch failed
  and the tarball was unpacked unverified.
- **The session purge would have fired on every future update** (#1081); it asks for a real secret
  value in a session now, not a store that is merely non-empty.

## v0.20.0 (2026-09-17)

The first release a box can be updated *into*, with `share/updates/0.20.0.json` as the first manifest.

### Added

- **An update path with a vocabulary of its own** (#1076). Conditions and actions describe the
  catching-up as data, and every entry goes false after its own action, so a repeated run converges.
- **`h-delete-user-sessions`** (#1059); password and role changes end a user's panel sessions.
- **An internal point release is one manual action** (#1045).

### Security

- **An empty user argument was read as "admin"** (#1067). Not reachable from the panel.
- **Two admin pages accepted a POST without the CSRF token** (#1066, upstream #5440).
- **The panel's session files no longer carry a secret's value** (#976); `PHPMYADMIN_KEY` travels as a
  mask.

### Changed

- **Tachyon moves to 4.2.4** (#846), from 3.2.2, with signed release assets.
- **A key with a closed set is normalised, anything outside it refused** (#1055).
- **An install from a handed-in tarball pins itself to that version** (#1052).
- **The tree carries its own version, and only a release cut from `main` is mirrored** (#1045).

### Removed

- **The private-source scaffolding** (#1045); `source.conf` stays for `HESTIARE_MIRROR`.

### Fixed

- **Customer PHP versions came out without a database driver** (#1070), and apt's failure was never
  collected.
- **Renaming a web domain could delete one of its aliases** (#1064).
- **The certificate's common name came out wrong on Debian 13 and Ubuntu 26.04** (#1064, upstream
  #5585), which cost a SAN-less certificate its dovecot copy.
- **A demoted admin kept every admin route until logout** (#1059, upstream #5706).
- **Two policy defects around suspension** (#1055/#1057, upstream #5711).
- Smaller ones: a malformed netmask (#1066, upstream #5044), an endless phpMyAdmin redirect (#1058,
  upstream #5663), a whole-user restic restore that restored nothing (upstream #5709), awstats stopping
  a suspended domain's log rotation (upstream #5684), a stale session cleanup path (#976), and a
  for-loop over a file pattern running on the pattern itself (#1035).

## v0.19.0 (2026-09-13)

Closes the update chapter: a box can fetch, verify and apply a release on its own, and `hestia.conf`
became one described surface on the way.

### Added

- **`hestia update` does the whole run** (#946/#947/#948/#949). It asks which tag this box follows,
  fetches and verifies the tarball, secures the install tree into one run directory, unpacks, derives
  from the **new** tree what the box still has to catch up on, and works that list off. Reversible
  entries run first; from the first irreversible one the rollback script refuses. The updater ships
  inside the tarball, so one query answers both "is there a newer release" and "is there a newer
  updater". The lower bound is **v0.19.0**, a literal in `update.sh`, so the copy that decides is the
  one being installed; a target older than the installed version is refused too. A daily check sets
  `UPDATE_AVAILABLE` and the panel banner names the command. No automatic update.

- **One registry for the system keys of `hestia.conf`** (#932/#943/#944), with class, default and the
  permitted values. Operator keys are filled by the repair and never touched by an update; system keys
  are the consequence of a command. The repair runs on a schedule from outside the file it repairs
  (#1006), which nothing had called at all.

### Security

- **Eleven commands stopped executing `hestia.conf` as shell** (#955). A plain `source` ran a word in
  an operator value as a command, and the writer's `sed` deepened it: an `&` pasted the old value into
  the new one and left the quotes unbalanced.

### Changed

- **The recipe freezes** (#945). After the wizard nothing writes `install.conf` again; every runtime
  reader asks the status instead. With it came one rule for what an add command says when there is
  nothing to do, across 30 commands: already there is success, and the install keeps going instead of
  leaving half a box behind.

- **The panel session store moved out of the install root** (#974), so an update tarball cannot carry
  sessions with it, and the panel reads the exit code of every command it writes with (#957).

### Removed

- **`reapply_outside_tree`** (#948), which re-applied seven things after every update: three were
  one-time migrations, two belong to the release that changes the unit, and the last two had nothing
  to compare against. The smoke reports their drift now.

### Fixed

- **A mail domain without a single account made every backup of that user fail** (#1033), and a token
  list was split by something that also expanded it (#1031): both are the same shape, a pattern the
  shell leaves standing or expands against the working directory.

- **`www.<domain>` took over another customer's vhost** (#925). The prefix was stripped from one
  variable and not the other, so the other customer's logs were chowned and their conf.d link replaced.

- Smaller ones: "Securing MariaDB" ran unchecked so a box could finish an install unsecured (#998),
  the installer started its services instead of restarting them so dovecot never read its new
  configuration (#1001), an sshd `Subsystem` line after a `Match` block is inert and its guard called
  it fine (#1017), a second installer run cost the box port 443 (#994), and a dozen smaller ones
  around consent, record encoding, the webmail front and the hestia crontab.

## v0.18.0 (2026-09-01)

IPv6 became a family per field instead of a parallel world, and the install presets stopped
pretending to be each other.

### Added

- **IPv6 is first-class, and never presupposed** (#602). Every place that held an address holds a
  family: records, firewall, panel, mail, backup targets. A v4-only box behaves exactly as before,
  because the second family is only ever added where one exists.
- **A panel session survives an address rotation it did not ask for** (#894). A client whose prefix
  changes mid-session is no longer logged out as if its session had been stolen.
- **Customer PHP has a CPU cap against the rest of the box** (#212). One slice for all customer FPM
  pools, so a runaway site cannot starve the panel or the mail stack.
- **Project quota is base behaviour** (#211), armed by the installer where the filesystem supports it.
- **`install.sh --port=<n>`** for an unattended install on a non-default panel port (#730), and the
  smoke checks the record grammar (#866).

### Changed

- **The panel PHP is the distribution's default, not a Sury pin** (#191), so the panel stops
  depending on a third-party repo for the one thing that must always come up.
- **The presets stopped overlapping** (#192/#193/#850): `mailonly` installs a mail front instead of
  a crippled web stack, `nomail` can actually send, and `standard` and `compact` differ again.

### Removed

- **The cPanel and DirectAdmin importers** (#877), VestaCP-era code nothing here could support, the
  per-user cgroup limits replaced by the slice (#212), the `csv` output format (#861), and two
  record keys nothing reads (#865).

### Fixed

- **The box handed its own system mail to a stranger** (#333). Cron reports went to whoever owned
  the address the box had been told to use.
- **A nomail box had no MTA at all, and every notification was lost silently** (#872).
- **A rejected database import counted as a successful one** (#880).
- **The panel port never reached the panel** (#730), **a v6 login was neither logged nor bannable**
  (#888), **an installer re-run emptied `hestia.conf`**, and **every Sury-mode install died on a
  PHP-8.5 box** (#857).
- Smaller ones: the admin's IP counter grew with every deleted customer IP (#866), and the wizard's
  pre-questions were described in the manifest and hard-coded at the same time (#886).

## v0.17.0 (2026-08-27)

_The backup cycle: restic as an addon, differential backups, remote targets that fail loudly, and a restore that reports before it writes._

### Added

- **restic is an addon, and the customer's package decides the mode** (#217/#240). A restic backup
  deduplicates against previous snapshots instead of writing a full archive every night; the
  repository and its retention are configured once for the box.
- **Differential backups** (#342): a `.diff.` member carries only what changed against the last full
  archive, and the restore resolves the chain.
- **Remote targets fail loudly, keep more, and can hold the only copy** (#240). An unreachable
  target is an error with a name, not a silent skip.
- **Every customer's archives live in their own folder** (#240), so one customer's listing cannot
  show another's, and **the box's own state has its own backup**: instance config, panel
  certificates, firewall state.
- **A restore reads, asks and reports before it writes** (#240). It lists what the archive holds,
  refuses what this box cannot serve, and names every part that did not come back.
- **Sieve is reachable for customers, and moving mail to Spam trains the filter** (#331), plus a
  demo mode that hides destructive actions (#772).

### Security

- **The restore trusts nothing it did not write** (#240, GHSA-2xw3): a crafted archive could inject
  shell into the executed restore queue.
- **Record values stopped being shell-expanded on their way to the panel** (#386, GHSA-8w7m/w3mx),
  and a failing `mktemp` no longer lets the panel write world-readable files into the filesystem root.

### Changed

- **The shell lint gate covers everything that is shell** (#477), in two tiers, judging regressions
  rather than inherited debt.

### Fixed

- **A restore under a different customer name deleted the source customer's database** (#240). The
  remap wrote the new name into the record while the delete path still had the old one.
- **Suspending a web domain switched its forced HTTPS and HSTS off for good** (#240), because the
  suspend template had overwritten the record, and unsuspending could not bring it back.
- **logrotate had failed on every target since install day** (#331): one stanza with a missing
  brace, so nothing rotated.
- **Removing sieve destroyed the mail server on two of four targets** (#331).
- Smaller ones: a PostgreSQL database came back from a restore with its password destroyed, a
  restored web domain now keeps fields this code has never heard of, an unreachable remote target
  failed anonymously and the mail saying so never left the box, every failed panel login wrote a
  crypt error into the FPM log (#438), and two backups in the same second replaced each other.

## v0.16.0 (2026-08-18)

_Webmail replaced, WordPress as a domain option, and the read side of the panel hardened._

### Added

- **WordPress as a panel-managed web-domain option** (#682): a checkbox installs a complete
  WordPress as the customer through the pinned wp-cli, with core update and delete behind a typed
  confirmation. Unticking detaches; the site keeps running.
- **Both webmailers at once, chosen per mail domain** (#584).
- **A user's hosting package travels with its backup** (#663) and is recreated where it is missing.

### Security

- **A validator character class let `|` through into a `bash`-executed queue line** (#393): nine
  validators wrote `[-|\.|_[:alnum:]]`, where `|` is a member, so a backup name could reach root's
  queue as a shell pipe from the panel's download form.
- **Panel-set passwords no longer land in cleartext in auth.log** (#693/#694): secrets travel
  through 0600 tempfiles, and the smoke run asserts it per box.
- **Four panel gates decided permissively when their input was missing** (#578): an unreadable
  config left every policy key absent, and absent read as allowed.
- **A customer could set a control the policy had taken away** (#649): handlers read POST keys the
  form never rendered.

### Changed

- **SnappyMail is replaced by Tachyon, its fork** (#584): upstream is dormant, with no
  security-patch channel for an internet-facing login. Plugins are sha256-pinned release assets.
- **Composer comes from the OS package** (#237), and wp-cli is a verified manifest pin instead of a
  moving build address.
- **`func/` is `include/`, packages live under `/etc/hestia/`, and what the panel must not reach
  moved to `sbin/`** (#4/#663/#209) - a directory boundary instead of a 213-name list.
- **PHP has a format contract again** (#647): PSR-12 with tabs. The panel forms were reordered
  around what people actually change (#621/#239), and MariaDB defaults to 11.8 (#656).

### Removed

- **The custom preset** (#195), **the Backblaze B2 backend** (#696) and **`migrate_data_layout`**
  (#663), whose moves no supported update could still reference.

### Fixed

- **Adding a subdomain under an SSL domain rendered certificate-less SSL vhosts and took nginx and
  apache down** (#683): a record parse leaked the parent's keys into the add command's namespace.
- **23 panel pages died instead of showing an empty list when a CLI call failed** (#578).
- **The system configuration repair never ran** (#654): `command not found`, logged as executed.
  Working, it seeded 25 absent keys.
- **The panel never took over its own Let's Encrypt certificate** (#656).
- **phpMyAdmin dragged apache2 onto boxes that have none** (#656), which then bound *:80.
- Smaller inherited ones: seven calls reached `sbin/` through `$BIN` (#209), every rebuild on
  apache-only wrote to `/etc/nginx` (#642), the FPM pools pinned a locale none of the targets
  generates (#239), and the wizard offered a pre-release PHP the installer then refused (#688).

## v0.15.0 (2026-08-13)

Closes the template restructuring (#219) and the Docker series (#389/#566), and takes the
read side of the object accessors with it.

### Added

- **Docker per customer, from the daemon to the domain** (#389/#566/#592/#618/#619): a
  companion account running a rootless daemon on its own loopback /24, a per-domain switch that
  makes the front proxy to the container, per-customer separation as rendered nft rules, and the
  resource cap on the companion's systemd slice.
- **HTTP/3 as a per-domain switch** (#613), offered only where nginx has `http_v3`;
  **suspension, offline and proxy caching render from `share/`** on any template (#586/#587);
  **panel uids come from a dedicated band** (#388); **DNSBL management from the CLI** (#555).

### Changed

- **A template is one file, and a domain has one vhost config** (#593): the `.tpl`/`.stpl`
  divergence class is gone, and a HestiaCP two-file backup restores as one merged vhost.
- **The PHP version is its own field** (#591): `BACKEND` carries only the pool profile, and a
  restore aborts before the first write when the archived version is not installed.
- **`templates/` holds only what somebody chooses** (#588/#589/#590); every write maps legacy
  values through `accept_web_template` with their side effects.
- **The web model decides the install scope** (#639): apache-only means no nginx on the box;
  mail-only keeps one for webmail and ACME, an exception carried by the model.
- **An install stage is only skipped for the answers it ran with** (#636).

### Removed

- **The DNS leftovers** (#619): templates, counters and records, out of packages and listings.
  The DKIM record view stays.

### Fixed

- **Object reads matched a domain as a regular expression** (#594): with `a.b.com` and
  `aXb.com` on one box, reads and writes could land on the other's record. Literal now, across
  nine accessors and 54 call sites.
- **HSTS did nothing on an apache front** (#638): the fragment carried nginx syntax and no
  apache template included it.
- **A dead SnappyMail mirror produced a green install with no webmail** (#573): an unbounded
  download and no `set -e`.
- **Backup retention could delete another user's archives** (#556); restic restored only the
  first of a multi-object selection (#555); a failing CLI call took the login page down (#575);
  the panel served its own includes over HTTP (#554) and trusted a client-controlled realip
  header (#553).
- Smaller inherited ones: an alias owned by another customer was never refused (#601), a user
  named after a service died at `groupadd` (#625), a stale LE account key failed forever
  (#555), the ip domain counter drifted per backup-restore cycle (#599), a domain acting as a
  regex could delete another customer's cache zone (#583), and a missing template wrote a
  silent 0-byte vhost (#586).

## v0.14.0 (2026-08-06)

The firewall round: one nftables table, fail2ban as a removable addon, and CrowdSec as a
three-way model.

### Added

- **The firewall renders as one nftables `inet` table** (#495/#481), IPv4 and IPv6 together, behind
  a seam that keeps the invariants when the renderer changes - with layer-7 jails so a fail2ban-only
  box has real web coverage, curated IP blocklists on a systemd timer, and smoke guards on the
  datapath, because a running daemon is not a loaded ruleset.
- **fail2ban is a removable addon** (#497) and **the model is switchable at runtime** (#498):
  fail2ban only, CrowdSec only, both, or neither.
- **The whitelist is a first-class object** (#495/#496) - `excludes.conf` only suppressed *new* bans
  and left existing ones in place. Panel surfaces for jail status, ban lists and manual bans came
  with it (#496/#527/#482), and IPv4/IPv6 parity throughout (#496/#536/#545/#548).

### Security

- **The webmail loopback listeners are restricted to the connecting UID** (#507), and the webmail
  vhost overwrites the client-IP headers it forwards (#515), so a client cannot spoof the address a
  ban is written for.
- **`source_conf` no longer executes code smuggled into a config key** (GHSA-xffx-jj33-p2px class).
- **Ten panel controllers checked CSRF before the role** (#496) - both guards worked, but the order
  decides which one an attacker gets to probe. CrowdSec's credential files are 0600 (#494).

### Fixed

- **Restarting the firewall from the panel destroyed the ruleset** (#496): the service row fell
  through to `systemctl restart nftables`, which loads the distro ruleset over ours.
- **CrowdSec was never installed by a fresh install** (#186): the gate read config keys the
  installer shell cannot see - `wcv` writes, it does not export.
- **fail2ban had been installing a config it could not start** (#496), a fresh install aborted
  silently in its stage (#520), a restart wiped the persistent banlist (#496), and the firewall
  broke IPv6 by dropping ICMPv6 (#534).
- **`is_format_valid` failed silently when a name matched no variable** (#496) - it validates by
  variable name, so a typo passed everything.
- Smaller ones: a live web-model switch left the fail2ban web jails watching the old log dir (#537),
  fail2ban and CrowdSec doubled up in the combined model (#542), and the panel answered plain HTTP
  with a bare 400 (#547).

### Changed

- **`iptables` and `ipset` are no longer installed** (#548) - nothing has called either since the
  nftables renderer landed - and `FIREWALL_SYSTEM` reads `nftables` (#495), because the value names
  the backend.
- **Adding, renaming or restoring a web domain tells fail2ban about its log** (#496), and CrowdSec
  is one three-way model question instead of two (#186).

### Removed

- **The mysqld jail** (#496) - 3306 is not in the shipped ruleset - and the UA-based `web-badbots`
  jail (#531), because user-agents are trivially forged.

## v0.13.0 (2026-08-03)

### Added

- **CrowdSec** (#186) - an nginx-gated, removable addon in four layers (local decisions, CAPI,
  a fleet mesh, and an L3 feeder), offered in the wizard as one three-way choice.
- **Server-native web bot rate-limiting** (#482) - `include/botpolicy.sh`, nginx `limit_req` or
  apache `mod_qos`, independent of CrowdSec so a box without it still throttles bots.
- **Shell lint gate** (#477), check-only, two tiers - both judging regressions rather than the
  ~240 inherited findings.

### Security

- The user editor blocks a non-`ROOT_USER` admin from modifying the `ROOT_USER` account on the
  POST path, not only in the view.
- Panel notifications are HTML-sanitized before storage (upstream #5548 / GHSA-3g4r-pfpf-8697).
- Restore scheduling no longer lets an argument inject into the executed restore queue.
- The admin debug panel escapes its variable output (upstream #5550).

### Fixed

- An optional component could end up flagged on with its package absent (#480).
- `h-list-sys-php` listed the isolated panel FPM pool as a pseudo-version `hestia` (#464).
- The web-model switch rolls back with `reload-or-restart`, so a failure cannot leave the box with
  no web server (#120, #466).
- Directory listing works under nginx-only (#468) - it only ever flipped apache's `Options Indexes`.
- A `"` or `\` in a certificate issuer broke the mail-SSL JSON (#471, upstream #5524).
- A disabled bot family left dangling zone references, breaking `nginx -t` box-wide (#482).
- Re-adding CrowdSec no longer fails on its saved state (#186).

### Changed

- PROVENANCE recomputed for all three folders against `upstream/hestiacp@ca19b9f`.

### Removed

- The orphaned bind9/named and vsftpd server-config views (#471) - both permanent ground-rule
  removals, and the vsftpd one called a command that does not exist.
- 36 app-specific web templates (72 files) seeded by the removed Software/App Installer.

## v0.12.0 (2026-07-30)

Covers everything since v0.11.0. The headline: the web-serving model is no longer
fixed at install — a **live switch** moves a running server between nginx-only, both,
and apache-only, as a first-class maintenance operation (freeze, snapshot, rollback,
crash recovery). Alongside it, two reference layers land: `STRUCTURE.md` for structural
divergence and per-folder `PROVENANCE.json` for per-file upstream heritage.

### Added

- Live web-serving model switch (#120): `h-add-sys-nginx`, `h-add-sys-apache2`,
  `h-delete-sys-nginx`, `h-delete-sys-apache2` change a running server between
  nginx-only / both / apache-only (previously fixed at install). Four thin commands over
  one shared core (`include/web-model.sh`); the model is derived from the configured
  component set. Runs as a maintenance operation: an exclusive freeze serializes domain
  ops and defers reloads (h-restart-web/-proxy/-service, apache logrotate, LE renewal)
  for the flip; snapshot + rollback (with a crash sentinel + `--recover`) means no
  total-loss window; the departing webserver is stopped+disabled (or `--purge`d), and
  `mod_remoteip` is toggled with the model. Backups gain a web-model marker and a
  cross-model restore prints visible warnings. Delete commands refuse on a dirty config
  unless `--force` (which still logs the overridden checks); a full nginx<->apache swap
  is a deliberate two-step through a serving `both`. Fleet-verified: all four transition
  directions serve HTTP/HTTPS + PHP, a switched box is byte-identical to a fresh install
  of the target model, and crash recovery restores cleanly.
- `STRUCTURE.md` (repo root): a structural-divergence reference mapping each
  major difference from HestiaCP to its follow-on implications (panel Caddy/Sury,
  the system-user split, protected downloads, webmail loopback, FileManager and
  SFTP-jail rebuilds, `/etc/hestia`, permanent removals). Registered in
  `CODEMAP.json` `_meta.reference_docs`. Living doc: keep current with each
  structural change. (#451)
- Per-folder `PROVENANCE.json` (`bin/`, `web/`, `share/`): per-file heritage vs
  HestiaCP - `source_type` (verbatim/derived/cherry-pick/eigenbau), `upstream_path`,
  `upstream_ref` last reconciled, and a RAW churn divergence percentage (triage, not
  truth: it overstates because the `v-*`->`h-*` rename and `install/`->`share/` reorg
  count as churn). Complements `VENDORED.json` (third-party, excluded here) and
  `STRUCTURE.md` (subsystem narrative). Recompute is a manual, occasional job on
  cherry-pick/reintegration - no smoke guard. share/ is best-effort: the #119
  install->share reorg breaks 1:1 paths, so 81 files are flagged for manual origin
  confirmation. (#459)

### Fixed

- Directory listing (`h-change-web-domain-dirlist`) survived no vhost rebuild (#456).
  It flipped apache `Options -Indexes`/`+Indexes` straight in the generated vhost with
  no `web.conf` key, so any `h-rebuild-web-domain(s)` reset it to the template default
  and lost the setting silently. Now persisted as a `DIR_LIST` key and re-applied by the
  rebuild self-heal (mirroring `SSL_FORCE`). Verified on apache-only and both models.
- nginx `suspended.{tpl,stpl}` and `php-fpm/*` templates logged to a hardcoded
  `/var/log/nginx/domains` while the managed dir is `/var/log/$WEB_SYSTEM`; in the
  `both` model that was the wrong directory. Now use `%web_system%` like `default.tpl`
  (#120).

## v0.11.0 (2026-07-28)

The headline: the **file manager** is rebuilt per customer with the kernel UID as the isolation
boundary, ClamAV and ProFTPD join the modular addons, and impersonation stops carrying admin
privilege.

### Added

- **File manager rebuilt per customer** (#218/#419), replacing FileGator and its SFTP-loopback
  connector: a per-customer php-fpm pool running *as the customer*, behind Panel-Caddy
  `forward_auth`, enabled per user from Edit User. TinyFileManager is vendored and stripped of every
  external CDN reference, which a `--check` gate enforces mechanically (#434).
- **ClamAV and ProFTPD become modular addons** (#123). ClamAV arms the exim `CLAMD` macro only once
  clamd answers on the socket, because `defer_ok` is fail-open and an armed-but-blind macro would
  pass mail unscanned. ProFTPD brings its curated config live; it sat orphaned under `install/`, so
  the distro default had been in effect all along.
- **The SFTP jail is rebuilt on `pam_namespace`** (#413): a per-session private tmpfs at the
  fidelity path, one rule serving both panel users and domain-FTP sub-accounts, whose home sits deep
  under `web/<domain>` where chroot cannot follow. No persistent state.
### Security

- **Impersonation drops admin privilege** (#438). `userContext` stayed `"admin"` through a "login
  as" session, leaving all 161 admin-only gates reachable by a same-origin script. It is the
  effective role now, with a durable `adminContext` holding the real one and the session id
  regenerated at both transitions.
- **The file-manager media handler ran customer script under the panel session** (#218/#432).
  `?media=` typed the response from `finfo` sniffing, so an `evil.svg` executed - an admin's session
  included. The type comes from an extension allowlist now, everything outside it is an attachment.
- **The GHSA advisories against the 1.9.6 fork point are fixed** (#386): admin takeover through a
  gate comparing against an undefined `$ROOT_USER`, SQL injection via the database password, root
  RCE via `eval` in the object search commands, and hardened cron parsing. Five further advisories
  were verified as not applicable.

### Changed

- The system removal verb is unified: `h-remove-sys-*` -> `h-delete-sys-*` (#123).
- SSH access is narrowed from two sides (#412/#413): shells become a curated allowlist intersected
  with `/etc/shells`, and an `AllowUsers` line is co-maintained per account, validated with
  `sshd -t` and re-commented rather than left active and empty.
- Vendored Adminer 5.4.4 -> 5.5.0 (#350), vendored because every target distro ships a CVE-affected
  version.

### Removed

- The `install/` tree is dissolved and `HESTIA_INSTALL_DIR` retired (#119).
- The shared `www.conf` PHP-FPM pool (#397) - no domain used it for serving, but the apache catch-all
  *executed* unclaimed `.php` as the `caddy` user unconfined. Such requests are denied now and
  re-granted per vhost.

### Fixed

- **Every panel file download was broken on the Caddy-fronted panel** (#441/#443). They emitted
  `X-Accel-Redirect`, which Caddy served as the `caddy` user, which cannot read customer-owned
  files. They stream through PHP now, hardened for GB scale.
- `update_user_value()` silently dropped a key on the **last line** of `user.conf` (#433): it
  deleted the line, then inserted before the same number, which is past EOF after the delete.
- Smaller inherited ones: Roundcube's HTTP 500 on a missing `dom` extension (#402), the MariaDB
  install aborting on Ubuntu 26.04 through its enforced `mariadbd` AppArmor profile (#387), the
  AV/spam columns showing a green check regardless of the actual system (#123), and a stray `||`
  that left Panel-Caddy serving the distro default on fresh installs.

## v0.10.0 (2026-07-19)

The headline is platform reach: Ubuntu 24.04 and 26.04 join Debian 12 and 13 as first-class targets,
and every change is verified on all four from here on.

### Added

- **Ubuntu 24.04 and 26.04 as first-class targets.** Reaching parity drove a round of installer, mail
  and sudo hardening specific to that baseline - the `libzip`, dhparam, sudo-rs and dovecot 2.4
  entries below.
- **Webmail is delivered through the Panel-Caddy** instead of the customer web stack (#205).
  Roundcube and SnappyMail each get a dedicated `caddy` FPM pool on a loopback listener, and
  per-domain `webmail.<domain>` vhosts reverse-proxy to it - so the `caddy`-owned data dirs are never
  touched by `www-data`, which was the root cause of the old SnappyMail "Permission denied!".
- **MariaDB and PostgreSQL become standalone, removable components** (#121), each owning its full
  lifecycle and host registration. MariaDB gains in-place version switching (#207) with a forced
  logical dump as a hard precondition; downgrades are refused, because a newer-format datadir cannot
  be reopened. PostgreSQL removal refuses while customer databases exist.
- Adminer as the PostgreSQL web UI, an optional addon (#350, #365) serving a single sha256-pinned
  vendored file - repo-vendored because every OS `adminer` ships a CVE-affected version. It replaces
  the dead phpPgAdmin link in the panel; phpMyAdmin is untouched.
- Fully unattended install via `-a`/`--auto` (#198), for scripted test-VM provisioning.

### Security

- **All hestia sudo grants were dead on Ubuntu 26** (#363). `/etc/sudoers.d/hestia` opened with
  `Defaults:root !requiretty`, and Ubuntu 26 ships sudo-rs, which rejects the entire file over that
  obsolete option - silently dropping the grant every privileged panel action relies on.
- **The rspamd controller socket was reachable by the panel's app pools** (#341). The grant was
  `usermod -aG _rspamd caddy`, and the phpMyAdmin/Adminer/Roundcube pools run as `caddy` too, so they
  inherited it and could reach the controller API past `forward_auth`. A dedicated group is granted
  to the Caddy *process* through systemd, which FPM workers do not inherit.
- Adminer logins are restricted to the local server (#356): the vendored login-servers plugin
  replaces the free-text "Server" field with a fixed dropdown.

### Changed

- `DB_SYSTEM` is seeded empty and composed from actually-registered database hosts instead of being
  hard-seeded to `mysql` (#121) - adding the first host of a type is what enables it, so the old
  membership guards were circular. A behaviour change on a contract many consumers read.
- The panel FPM's curated extension set gained `intl`, `phar` and `exif` (#205) - without `intl`
  Roundcube fatals on login, without `phar` SnappyMail's change-password plugin blanks.

### Removed

- Dead phpPgAdmin plumbing (#365), superseded by Adminer but never cleaned up: the app templates, an
  unused FPM pool, the `DB_PGA_*` fields and the panel's broken links.

### Fixed

- **Every IMAP/POP3 login was dead on a fresh dovecot 2.4 install**, so Debian 13 and Ubuntu 26
  (#376). `default_login_user = dovecot` - upstream heritage, harmless on 2.3 - could not reach the
  auth socket in the login chroot. The smoke test gained IMAP/SMTP banner checks.
- **Choosing the OS-repo MariaDB silently installed the external MariaDB.org build** on deb13 and
  ub26 (#226): the `__os__` sentinel resolved to a bare version that the installer then matched to
  the external repo.
- phpMyAdmin and Adminer were broken under the isolated panel PHP (#227, #229) - the curated conf.d
  carried only the panel-UI extensions, so phpMyAdmin died on `ctype_alpha()`.
- Installer robustness across all four targets (#347): `/etc/ssl/dhparam.pem` moves to the base stage,
  since nginx and dovecot are both fatal without it, and `libzip` is resolved per release.
- Smaller inherited ones: three latent SnappyMail defects that together broke password changes
  (#234), an inverted `WEBMAIL_SYSTEM` cleanup condition (#234), the Roundcube logrotate fragment
  that nothing ever copied while fail2ban tailed the unrotated log (#234), and an over-quota
  dovecot-lda delivery that bounced instead of deferring (#343).

## v0.9.0 (2026-07-13)

Covers everything since v0.8.0, including the quick tags v0.8.1–v0.8.3.

### Added

- rspamd and sieve are modular addons (#122): `h-add`/`h-remove-sys-rspamd` and
  `-sieve` install, wire and purge each service; the installer just invokes them
  per recipe. First functional sieve support — ManageSieve on 4190, per-account
  scripts inside the maildir, clean local delivery via dovecot-lda so scripts
  run at delivery (spam keeps exim's direct `.Spam` path)
- rspamd controller web UI embedded in the panel at `/list/rspamd/` (iframe,
  admin-only), gated by Caddy `forward_auth` + a group-restricted unix socket
  instead of TCP localhost; a home-grown override gives it a dark-theme match in
  the same-origin iframe (#301, #319)
- Per-domain spam tuning for customers: mark/reject thresholds and an optional
  subject tag, plus a sender whitelist/blacklist, editable in the panel and via
  `h-*-mail-domain-spam-*`; values live in `mail.conf`, mirrored to per-domain
  exim files read per message (no reload), bounded by `POLICY_SPAM_*` for
  non-admins (#318, #330)

### Changed / Rebuilt

- Panel PHP CLI (`hestia-php`) now loads its own curated extension set from
  `/etc/php/hestia/cli/conf.d` (built by `hestia-php-confd` alongside the FPM
  set), isolated from the customer conf.d of the same PHP version (#281)
- Panel password generator uses a typeable-anywhere character set (no AltGr/dead
  keys, no confusable I/l/1/O/0, 1–3 symbols), so generated passwords survive
  being typed by hand e.g. over VNC (#316)
- rspamd scan worker moved from TCP `127.0.0.1:11333` to a group-restricted unix
  socket (`/run/rspamd/normal.sock`, mode 0660, group `_rspamd`), so local shell
  users can no longer read the rule/score config or submit scan jobs (#321)

### Removed

- Dead DNS feature plumbing (#283): the last `DNS_SYSTEM`-guarded blocks and
  every call to non-existent `h-*-dns` commands across mail/letsencrypt/webmail,
  backups, cpanel import and search; the DNS_SYSTEM/DNS_CLUSTER/DNSSEC keys leave
  `h-list-sys-config`. Kept: the DKIM-DNS record display and the
  HestiaCP-compatible dns.conf/dns/ schema so backups stay bidirectional

### Fixed

- Debian 13 mail stack: local delivery deferred for every message (#329) — the
  dovecot-2.4 mail-account commands wrote the maildir path into the passwd home
  field while exim's appendfile expects the user home. The passwd format is now
  identical on all platforms (home in field 5) and dovecot 2.4 derives the
  maildir from home. Also fixes the `sssl_server_cert_file` typo that produced
  broken dovecot-2.4 per-domain SSL configs

## v0.8.0 (2026-07-11) — cumulative changes since the fork

Everything below shipped incrementally across v0.1.x–v0.8.0. From here on,
entries are grouped per release.

### Removed (vs. HestiaCP)

- DNS server: bind9 and the entire DNS zone management (#58, #213)
- REST API subsystem (#146)
- Web Terminal (#59)
- vsftpd — proftpd remains available as the optional FTP server (#213)
- SpamAssassin — replaced by rspamd, see Added (#284, #299)
- Software Installer ("Quick Install Apps")
- Bundled `hestia-nginx`/`hestia-php` services — the panel now runs on
  OS-repo Caddy and a dedicated Sury PHP-FPM pool, see Changed (#24, #25)
- Legacy hestia package auto-update subsystem (#128) and the dead
  `include/upgrade.sh` (#197)
- Composer dependencies in the panel — the few remaining libraries are
  vendored (#56)
- Node.js build chain for panel assets — native ESM modules, vendored
  Alpine.js, prebuilt CSS (#248)
- `hestiamail` system user (#214)
- Dead ballast sweeps: bind9/named/vsftpd remnants (#213),
  spamassassin/spamd remnants incl. their panel editor pages (#284),
  unused installer data (#119), stale calls to removed DNS commands in
  domain/user lifecycle scripts — errored on every run (#213)

### Changed / Rebuilt

- All CLI commands renamed `v-*` → `h-*`; `v-*` kept as compatibility
  symlinks to ease upstream cherry-picks (#22, #23). HestiaCP compatibility
  is preserved permanently: `/home/$user` layout, command signatures,
  bidirectional backup format
- Panel webserver: Caddy from the OS repo on port 8083 replaces
  hestia-nginx (#24); panel PHP runs in an isolated, pinned Sury FPM pool
  with its own `conf.d` extension set and own `php.ini`, guarded against
  deletion, switchable via `h-change-sys-panel-php` (#25, #250, #272)
- System user model reworked: `hestiaweb` → `hestia`, app pools run as
  `caddy`; phpMyAdmin/phpPgAdmin are served via the Panel-Caddy (#214)
- Installer rebuilt from scratch: monolithic upstream scripts → Makefile
  (#26) → just (#102) → pure-bash two-stage installer — an interactive,
  manifest-driven wizard writes `/etc/hestia/install.conf`, the
  non-interactive `h-install-hestia` consumes it, `COMPONENT_*`-gated and
  idempotent, with fail-clear + resume recovery (#61, #106, #112)
- Instance state moved out of the install root to `/etc/hestia` (config,
  user data, component state) so it survives updates; `data/` dissolved
  (#30, #31, #129, #152, #156)
- `install.conf` doubles as live component state, maintained by
  `h-add-*`/`h-delete-*` commands (#103)
- Package sources moved to OS repos: nginx (#53), Roundcube (#54),
  phpMyAdmin (#55); only two external repos remain (Sury PHP, MariaDB)
- Build & release: tag → CI → curl-able source tarball; no .deb packages,
  no apt repo, no compiled binaries
- Web/proxy port model per install profile — nginx as reverse proxy in
  front of apache2 for customer vhosts (#247)
- phpMyAdmin SSO reimplemented without the REST API: local one-time token
  handoff (#145)
- exim: one dsearch-untainted 4.95+ template for all targets (fixes tainted
  local delivery on exim ≥ 4.96), moved to `share/exim/` (#299)
- Curated config assets live in `share/` — `install/` is legacy and being
  dissolved (#119); upgrade version pins folded into `share/manifest.json`
  (#288)
- Panel rebranding: brand tokens, recolored default/flat/dark themes, new
  dark-tonal and green themes, new logo, header wordmark with trailing R
  (#259, #260, #261, #269, #297)

### Added

- Interactive install wizard (whiptail, manifest-driven) with install
  profiles standard/minimal (#106)
- Post-install smoke test `h-check-sys-smoke` (#221)
- rspamd integration: exim wiring via `variant=rspamd` (exim keeps decision
  authority, per-domain toggles unchanged), curated `local.d` set, Bayes
  learning on an always-present hard-capped Redis companion (64 MB,
  volatile-ttl), Spam→`.Spam` foldering via exim router (#299)
- Redis lifecycle commands `h-add-sys-redis`/`h-delete-sys-redis` honoring
  the rspamd companion contract (promote/demote instead of uninstall)
  (#121)
- Per-mail-domain SMTP relay excludes: `bypass_smtp_relay` router delivers
  listed recipient domains directly via DNS/MX past the relay, managed by
  `h-add`/`h-delete`/`h-list-mail-domain-relay-exclude` (#304) and editable
  in the panel's mail domain settings below the relay credentials (#306)
- `hestia` umbrella command: `hestia install|configure|update|uninstall|status`
- Repo tooling & docs: `CODEMAP.json`, `PATHS.md`, `TROUBLESHOOTING.md`,
  `VENDORED.json`, upstream sync/vendor-update scripts in `share/upstream/`
  (#248)
