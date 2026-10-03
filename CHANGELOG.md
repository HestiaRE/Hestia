# Changelog

All notable HestiaRE changes are documented here, starting from the fork
point - a HestiaCP 1.9.6 snapshot, kept read-only in the `upstream/hestiacp`
branch.

Maintenance rule: every larger change adds an entry to the Unreleased
section as part of its PR. Only public minors get a section - an internal
build `vX.Y-devN` belongs to the minor it leads to. On release, the section
gets that version number and a new Unreleased opens above it. The releases
before v0.20.0 are folded into one section by theme.

## Unreleased

### Added

- **A mail password change gives the other devices of the network time to catch up** (#1155). For 12 hours
  dovecot failures of that account from that network do not count towards a ban, and for one hour no exim failure
  from it does; a global IPv6 address stands for its /64, and a ban the network already has is lifted. The panel,
  Roundcube and Tachyon set it with the client address, `h-add-mail-account-grace` lets an admin set it by hand,
  and `MAIL_PW_GRACE` / `MAIL_PW_GRACE_SMTP` in `hestia.conf` set the minutes (`0` is off).
- **FireHOL blocklists can be picked from the live catalogue** (#510). A new page next to the IP lists shows
  FireHOL's lists with category, entries and age, combined lists first and the 25 largest below; ticking a
  combined list hides what it already contains. The ticked lists become one IP list with one DROP rule, and a
  list FireHOL stops serving is skipped on refresh instead of freezing the set. The catalogue is fetched only on
  the button, never on page load.

### Security

- **The dovecot jail never banned on Debian 13 and Ubuntu 26.04** (#1171). The distribution's filter does not
  know dovecot 2.4's `Login aborted`, so IMAP and POP3 had no protection against password guessing there. Our own
  filter reads 2.3 and 2.4 and counts a failure once; the stock one counted every 2.3 failure twice.
- **A DROP rule over an IP list could lock out the admin network** (#510). FireHOL Level 1, offered since #481,
  contains 10.0.0.0/8, 172.16.0.0/12 and 192.168.0.0/16, so on a server behind NAT it dropped every new connection
  from the LAN, SSH and panel included. Such a rule now leaves loopback and the private ranges alone, as the
  CrowdSec chain already did; an update re-renders the firewall of a box that has one.
- **The server config editor read and wrote more than it offers** (#1176). `h-open-fs-config` admitted any path
  under `/etc` that contained a word like `ssh` or `hestia`, so it handed out the SSH host keys, the MariaDB root
  password in `mysql.conf` and a customer's `user.conf`; `h-change-sys-service-config` copied any file as root into
  a service config, where the editor then showed it. Both now work on one set of files, derived at run time from the
  installed services and compared as the whole path, and the writer reads its source with the caller's rights. A
  failed restart puts the old config back and starts the service on it; a config the service's own test rejects is
  never applied, so the running instance is left untouched, and each outcome says which happened. The editor pages
  for RHEL service names (`httpd`, `exim`, `crond`, `mysqld`) are gone.
- **The fs commands compare whole path components** (#1176). The check was a prefix, so `/home/ab` passed for
  customer `a`; only the customer's own rights, which the commands run under, still stopped the write.
  `h-extract-fs-archive` takes `.tar.zst` and `.tar.gz`, an unknown type ran into an error that returned success.
  Below the backup directory it unpacks only from a restore's own `tmp.*` dir, no longer from a customer's archives.

### Removed

- **The `v-*` command names are gone** (#1176). They were 421 symlinks to the `h-*` commands, kept for picking
  changes from HestiaCP, and every adoption is a reimplementation by now. Call `h-*` with the same arguments, or
  create aliases yourself outside `/usr/local/hestia/bin` (e.g. in `/usr/local/bin`); `--help` does not work under
  them, and any `v-*` symlink left in `/usr/local/hestia/bin` fails the smoke check. An update removes the shipped
  ones from the box (symlinks only). A restore of a HestiaCP archive from before 1.9 names any cron job that still
  calls `/usr/local/hestia/bin/v-*` in its log; such a job is HestiaCP's own system job and is left failing on
  purpose.
- **Twelve file commands without a caller are gone** (#1176): `h-add-fs-archive`, `h-change-fs-file-permission`,
  `h-check-fs-permission`, `h-copy-fs-directory`, `h-copy-fs-file`, `h-delete-fs-file`, `h-get-fs-file-type`,
  `h-list-fs-directory`, `h-move-fs-directory`, `h-move-fs-file`, `h-open-fs-file` and `h-search-fs-object`. They
  served HestiaCP's app installer and the old VestaCP file manager; the File Manager here runs in the customer's own
  pool. An update deletes them from the box.

### Fixed

- **The fail2ban page of the config editor could never save** (#1176). It edits `jail.local`, which belongs to the
  admin and does not exist until somebody writes it, and both the reader and the writer refused a missing file. The
  first save now creates it. A jail.local that fail2ban cannot run on is rolled back before the restart, or after
  it when the server does not answer, since the restart reports success either way.
- **A restore broke off at the mail and home archives when `BACKUP_TEMP` was set** (#1176). The restore unpacks
  from there, and `h-extract-fs-archive` accepted a source only under the home, `/tmp` or `$BACKUP/tmp.*`.
- **Adding a mail domain left its webmail unreachable until some later reload** (#1172), and every restart after
  a command that reads a customer record was skipped the same way. The freeze check ran `$SHELL`, which the record's
  `SHELL='nologin'` had replaced, and read the refusal as a web-model switch in progress.

## v0.23 (2026-09-27)

The panel keeps eight languages and speaks German completely, a customer's databases can share a user or add a
read-only one, ionCube joins the addons, and every PHP version gets one extension set.

### Added

- **A MySQL database can share a user with another database of the customer, and carry a second one** (#725).
  `h-add-database` and `h-change-database-user` take an empty DBPASS to reuse a user the customer already has;
  its password stays in the record that created it, and that database cannot be deleted or moved while another
  still uses the user. `h-change-database-second-user` adds a second user, switches it between full rights and
  read-only (`SELECT, SHOW VIEW`) and changes its password; suspend, rebuild, backup and restore carry it. Add
  and Edit Database offer both in the panel.
- **ionCube loader as an installer addon** (#1069), preselected on standard and compact. The loader is pinned
  with a sha256 per architecture and reaches every customer PHP version but 8.0, for which ionCube ships none;
  `h-add-sys-ioncube` / `h-delete-sys-ioncube` switch it on a live box.
- **The installer asks for the system locale, and the panel's language follows it** (#1164). Leave as it is,
  English, German or `C.UTF-8`, unattended with `--locale=`; it takes effect with the install reboot, and
  `h-change-sys-locale` does the same later. An update never touches the system locale.

### Security

- **A customer could take over another customer's database user** (#725). The customer prefix does not keep
  names apart (`a` with `b_x` and `a_b` with `x` are both `a_b_x`), and the second GRANT reset the first
  user's password. Creating, renaming, moving and restoring a database refuse a name somebody else has.
- **A database restored without a password hash got a user without a password** (#725), which any local
  process could use. It gets a random one now, and an empty DBPASS is no longer taken as a password.
- **The SMTP relay password was readable by every account** (#1142), and **mail certificate keys were
  world-readable** (#1135). The relay files are 640/660 for exim, the keys 640 through the `mail` group, and
  update entries re-mode what a box already has.

### Changed

- **The panel speaks eight languages, from text sources** (#1160). English and German are maintained, and
  German covers the whole panel with one reviewed term for each recurring word; Dutch, French, Spanish,
  Portuguese, Danish and Russian were filled once, best effort. Each language is a `hestia.po` beside its
  `hestia.mo` under the gettext domain `hestia`, kept in step by `.gitea/tools/i18n.sh`.
- **Sharing a domain is the owner's switch on the web domain** (#751). A Share box on Add and Edit Web Domain
  lets other accounts add subdomains of it; "Enforce subdomain ownership" left the panel and stays a
  `hestia.conf` key for the CLI.
- **Every PHP version gets one extension set** (#1069), sized for WordPress, Nextcloud and Magento: `redis`,
  `igbinary` and `mcrypt` are new, `cgi`, `pspell` and `imap` are no longer installed, and the CLI allows
  `pcntl_*`. phpMyAdmin and Roundcube install without recommends, which had pulled the newest Sury PHP onto
  boxes that run none of it.
- **The smoke checks the box; checks on the shipped code run in CI** (#1147). `h-check-sys-smoke` went from
  97 checks to 57, and a new `nginx -t` / `apache2ctl -t` check catches every config the next reload would
  refuse.

### Removed

- **33 panel languages** (#1160). Most covered a third of the panel or less. An update deletes them and moves
  every account on one of them to English; a restore does the same with a note.

### Fixed

- **The panel showed English on Debian 13 and both Ubuntu releases** (#1157). glibc 2.39 and later ignore
  `LANGUAGE` under `C.UTF-8`, and no box had `en_US.UTF-8`; install and update generate it now.
- **An update to v0.22 stopped halfway** (#1132). An entry in `0.22.json` waited for one the raised lower bound
  no longer reads, and a nomail box failed the same check on the stock exim template.
- **On a PHP 8.5 panel, phpMyAdmin or Roundcube answered 500** (#1149). The Roundcube pool's shim for
  `array_first()` reached phpMyAdmin through the shared OPcache; the Roundcube pool runs without it now.
- **An OS-PHP box left customer PHP unhardened** (#1069). singlephp and mailonly never ran `h-add-web-php`, so
  `exec()` was open and uploads stopped at 2 MB; an update configures what it missed. Customer code there that
  relied on `exec()` stops working, as it always did on a Sury box.
- **Saving the PHP page overwrote every php.ini on the box, the panel's own included** (#1144). It edits the
  fpm `php.ini` of one chosen version now.
- **An SMTP relay login with `\` or `` ` `` made the mail backup unrestorable** (#1143). The credentials are
  stored encoded; HestiaCP reads that form literally, so the relay password has to be set again there.
- **An account created under `umask 077` was broken without an error** (#1134): IMAP unavailable, every web
  domain 403. Every command starts from `umask 022` now, and `h-add-user` sets its modes as the rebuild does.
- **Removing a PHP version could take phpMyAdmin and Roundcube with it** (#1069); `h-delete-web-php` refuses.
- **Renaming a package left its customers on a package that no longer existed** (#1158), and three more panel
  actions failed the same way: a call without `$BIN`, which sudo's path does not carry.
- Smaller ones: counters drifted after an aborted restore (#1137), saving unchanged server settings ran the
  relay delete and reloaded the web server (#1140), a failed `tempnam()` ended a password save in a blank page
  (#1158), an apostrophe in a translation or a multi-line restic error broke the panel's scripts (#1160), and
  Add Database never showed the customer prefix (#725).

## v0.22 (2026-09-24)

Panel and webmail share one out-of-office notice, the panel can sit behind a web domain, every command
answers `--help`, and the spam path loses three defects inherited from HestiaCP.

### Added

- **With Sieve, panel and webmail share one out-of-office notice** (#784), written into the mailbox's active
  script in the format of the webmail that owns it; exim no longer answers alongside. Without Sieve exim
  answers as before.
- **A web domain can carry the panel** (#878). The `panel` template proxies to the panel on loopback, so the
  panel port can close; the panel sees the real client address, and the login jail bans on 80/443 as well.
- **Every command answers `--help`** (#657), from a header that now describes what the code reads.
- **The clock is stepped once a day at 07:00** (#1098), against the drift of nightly suspend-mode snapshots.

### Changed

- **A remote PostgreSQL host has to speak TLS** (#1098, #980). One registered without it stops connecting
  until `TLS='no'` is added to its line in `conf/pgsql.conf`.
- **The exim autoreply follows RFC 3834** (#784): no answer to bounces, lists or spam, one per sender a week.
- **The update lower bound is `v0.21`** (#1111), and **Tachyon is 4.2.5** (#1125).

### Fixed

- **A sender could keep spam out of the spam folder with an `X-Spam-Status` of their own** (#1121). Incoming
  `X-Spam-*` headers are dropped before the scan.
- **The first spam to a new mailbox waited for its first ordinary mail** (#1127), and every retry tagged the
  subject again.
- **A restored PostgreSQL database left the customer without rights to their own data** (#1113).
- **The Roundcube password change failed on every box** (#878), and **a sieve redirect failed SPF** (#1095).
- Smaller ones: PHP ran on UTC whatever the time zone, database hosts off the default port were half ignored,
  two install probes depended on the box's language (#1098), a panel certificate took effect only at the next
  restart (#878), and removing webmail left its nginx logs behind (#1122).

## v0.21 (2026-09-22)

Plus-subaddressing reaches sieve, the version scheme drops its third component, and the update path loses
six defects that only the first real runs could find.

### Added

- **Plus-subaddressing: `john+tag@domain` is delivered to `john`** (#596), the suffix reaching sieve as
  `envelope :detail`; with the sieve addon exim delivers over LMTP.
- **A shipped config the operator may edit is patched, not copied** (#1083).

### Changed

- **A version is `vX.Y`, an internal build `vX.Y-devN`** (#1106); a hotfix becomes the next minor. The
  release mirror is `hestiare.com` (#1100), and a manifest at or below the lower bound is no longer read
  (#1093).

### Fixed

- **The nightly backup of a customer with a PostgreSQL database failed on a non-English box** (#1096).
- **The daily update check never ran** (#1091), and **a box updated from a handed-in tarball mailed a failure
  every night** (#1104).
- **The updater ran the new release against the old library** (#1080), so the tarball went unverified. A backup
  could size itself from numbers nobody measured (#1099), and the session purge would have fired on every
  update (#1081).

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


## From the fork to v0.19 (2026-07-11 to 2026-09-13)

What the first releases built, by theme rather than by version: the removals against HestiaCP, a new
installer and panel stack, and the subsystems that were rebuilt rather than inherited.

### Removed against HestiaCP

- **bind9 and the DNS zone management** (#58/#213/#283/#619). The DKIM record view and the HestiaCP-compatible
  `dns.conf` schema stay, so backups remain bidirectional.
- **The REST API** (#146), **the Web Terminal** (#59), **vsftpd** (#213; ProFTPD is an addon), **SpamAssassin**
  (#284, replaced by rspamd) and **the Software Installer** with its 36 app templates.
- **The bundled `hestia-nginx` and `hestia-php`** (#24/#25), the legacy package auto-update (#128), the panel's
  Composer dependencies (#56) and the Node.js build chain for its assets (#248).
- **The cPanel and DirectAdmin importers** (#877), **the Backblaze B2 backend** (#696), `iptables` and `ipset`
  (#548), the custom preset (#195) and the `csv` output format (#861).

### Platform and installer

- **Debian 12 and 13, Ubuntu 24.04 and 26.04 are first-class targets**, and every change is verified on all
  four. Ubuntu 26 brought sudo-rs, which rejected the whole sudoers file over one obsolete option (#363), and
  dovecot 2.4, whose inherited login user could not reach its auth socket (#376/#329).
- **A pure-bash two-stage installer** (#61/#106/#112). A manifest-driven wizard writes
  `/etc/hestia/install.conf`, and the non-interactive `h-install-hestia` works it off, gated per component and
  resumable; unattended with `-a` (#198) and `--port=` (#730). After the wizard the recipe is frozen and every
  runtime reader asks the status (#945), and an add command that finds its work done succeeds.
- **Six presets that no longer overlap** (#192/#193/#850): standard, compact, latest, singlephp, nomail and
  mailonly. The web model decides the web-server packages (#639): apache-only has no nginx on the box.
- **OS packages first** for nginx, Roundcube, phpMyAdmin and Composer (#53/#54/#55/#237); the only external
  repos are Sury PHP and MariaDB.
- **Instance state lives in `/etc/hestia`** (#30/#31/#129/#152/#156), outside the install root. `install/`
  dissolved into `share/` (#119), `func/` is `include/`, and what the panel must not reach sits in `sbin/`
  (#4/#663/#209).
- **The CLI is `h-*`** (#22/#23), with `v-*` symlinks wherever HestiaCP has the command, and the removal verb
  is `h-delete-*` (#123). A release is a tarball cut from a tag: no `.deb`, no apt repo, no compiled binaries.
- **`h-check-sys-smoke`** checks a box after install and on demand (#221).

### Panel

- **Caddy from the OS repo serves the panel on 8083** (#24), with an FPM pool, `conf.d` and `php.ini` of its
  own (#25/#281) on the distribution's PHP (#191). The panel runs as `hestia`, its app pools as `caddy` (#214).
- **Webmail, phpMyAdmin and Adminer are served by the panel's Caddy** from loopback pools (#205/#145/#350),
  phpMyAdmin with a single sign-on that needs no API. Tachyon, a maintained fork of SnappyMail, replaced it
  (#584), and both webmailers can run at once, chosen per mail domain.
- **The file manager runs as the customer** (#218/#419): TinyFileManager in a per-customer pool behind
  `forward_auth`, replacing FileGator and its SFTP loopback.
- **The SFTP jail is built on `pam_namespace`** (#413), and SSH access is an allowlist of shells plus a
  maintained `AllowUsers` line (#412).
- **A rebranded panel** with its own themes and logo (#259/#260/#261/#269/#297), forms ordered around what
  people change (#621/#239), a password generator whose output can be typed anywhere (#316), and a demo mode
  (#772). A session survives an address rotation it did not ask for (#894).

### Web

- **The web model switches live** between nginx-only, both and apache-only (#120), as a maintenance operation
  with freeze, snapshot and rollback. In both, nginx proxies to apache (#247).
- **A template is one file, and a domain has one vhost config** (#593). `templates/` holds only what somebody
  chooses (#588/#589/#590), suspension, offline and proxy caching render from `share/` (#586/#587), and the PHP
  version is a field of its own (#591).
- **Docker per customer** (#389/#566/#592/#618/#619): a rootless daemon on a companion account, a per-domain
  proxy to the container, and nft rules keeping customers apart.
- **WordPress as a web-domain option** (#682) through the pinned wp-cli, **HTTP/3 per domain** where nginx has
  `http_v3` (#613), and **server-native bot rate limiting** (#482).
- **Customer PHP has a CPU cap on one slice** (#212), and **project quota** is armed where the filesystem
  supports it (#211).

### Mail

- **rspamd replaces SpamAssassin** (#299/#122). exim keeps the decision, Bayes learns on a capped Redis
  companion, and spam goes to `.Spam`. Scan worker and controller sit on group-restricted sockets (#321/#341),
  the controller UI is in the panel (#301), and each mail domain has its own thresholds, subject tag and sender
  lists (#318/#330).
- **Sieve is an addon with ManageSieve** (#122/#331), and moving mail to Spam trains the filter.
- **One exim template for all targets** (#299), with per-domain relay excludes (#304/#306). ClamAV and ProFTPD
  are addons (#123); ClamAV is armed only once clamd answers, since a blind scan would pass mail.

### Databases

- **MariaDB and PostgreSQL are standalone, removable components** (#121). MariaDB switches versions in place
  behind a forced dump (#207) and defaults to 11.8 (#656); Adminer is the PostgreSQL UI (#350/#356/#365).
  `DB_SYSTEM` follows the registered hosts.

### Backup and restore

- **restic is an addon, and the customer's package picks the mode** (#217/#240). Differential backups (#342),
  remote targets that fail loudly and can hold the only copy, a folder per customer, and a backup of the box's
  own state (#240).
- **A restore reads, asks and reports before it writes** (#240). It refuses what the box cannot serve, names
  every part that did not come back, and carries the hosting package (#663) and the web model (#120).

### Firewall and IPv6

- **The firewall is one nftables `inet` table** (#495/#481), with curated blocklists on a timer and a whitelist
  that lifts existing bans (#495/#496). fail2ban is a removable addon (#497), CrowdSec one in four layers
  (#186), and the model switches at runtime (#498).
- **IPv6 is a family per field** (#602): records, firewall, panel, mail and backup targets. A v4-only box
  behaves as before.

### Update path and configuration

- **`hestia update` does the whole run** (#946/#947/#948/#949): it fetches and verifies the tarball, secures
  the tree, unpacks, and works off what the box still has to catch up on, as derived by the new tree. The
  updater ships in the tarball, and a daily check raises a panel banner; nothing updates by itself.
- **`hestia.conf` is one described surface** (#932/#943/#944). A registry holds class, default and permitted
  values of every system key, and a scheduled repair fills what is missing (#654/#1006).

### Security

- **The GHSA advisories against the 1.9.6 fork point** (#386): an admin takeover through an undefined
  `$ROOT_USER`, SQL injection through the database password, root RCE through `eval` in the search commands,
  and cron parsing.
- **Impersonation drops admin privilege** (#438): "login as" kept 161 admin-only gates reachable.
- **Nothing executes what a record or config carries.** A crafted backup could inject shell into the restore
  queue (#240, GHSA-2xw3), a validator let `|` into it (#393), record values were shell-expanded on their way
  to the panel (#386, GHSA-8w7m/w3mx), and `hestia.conf` was sourced as shell (#955).
- **The panel's gates fail closed** (#578/#649), a customer file can no longer run script under the panel
  session (#218/#432), panel-set passwords stay out of auth.log (#693/#694), and the webmail listeners accept
  only the panel's UID (#507).

### Fixed

- **`www.<domain>` took over another customer's vhost** (#925), and **object reads matched a domain as a
  regular expression** (#594), so reads and writes could land on another customer's record.
- **Backup retention could delete another user's archives** (#556), and **a restore under a new name deleted
  the source customer's database** (#240).
- **Every panel download was broken behind Caddy** (#441/#443), and **logrotate had failed on every target
  since install day** (#331).
- **The box handed its system mail to a stranger** (#333), **a nomail box had no MTA at all** (#872), and
  **a mail domain without accounts made every backup of its user fail** (#1033).
- **dovecot never read the configuration the installer wrote** (#1001), and **a rejected database import
  counted as a success** (#880).
