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

### Security

- **Web domain, SSL and FTP commands are stricter** (#1176). An FTP or SFTP account whose home passes through a link
  is refused, Let's Encrypt validation no longer writes into the customer's docroot, and a domain or alias name is
  held to what a vhost can carry.
- **Stats, HTTP auth, redirect, cache and WordPress commands are stricter** (#1176).
- **The Sury and Docker repository keys are checked against a pinned fingerprint** (#1176).
- **User commands are stricter** (#1176). A suspended account no longer gets in by SSH key, a password reset no
  longer logs the user in past 2FA and the address list, and a login names a suspension only after the right password.

### Removed

- **The Let's Encrypt queue, `h-add-web-domain-ssl-preset` and `h-update-web-domain-traff`** (#1176). An update
  deletes them from the box.
- **`public_shtml` and a separate SSL docroot** (#1176): https serves `public_html` like http.
- **`h-update-web-templates`** (#1176).
- **`share/php-fpm/multiphp.tpl`** (#1176), unused.

### Fixed

- **Let's Encrypt on web and mail domains** (#1176): an SSL domain with aliases, a renewal of an already validated
  name, a webmail name without DNS, and mail-only boxes. A renewed mail certificate is readable by exim again.
- **Renaming a web domain** (#1176) moves its aliases, logs and configs and refuses what it cannot carry along.
- **Template, proxy, backend and PHP changes on a web domain** (#1176) keep the record and the vhost in step, and the
  suspend and offline pages show over https as well.
- **Web model switch, ProFTPD, Docker, Redis and PHP commands** (#1176): a switch carries phpMyAdmin, bot rate
  limiting and the file manager to the new front and takes them back on a rollback, FTP behind NAT and its fail2ban
  jail follow the addon, and removing Docker no longer strands customers who still use it.
- **User suspend, delete and counters** (#1176): suspending a user no longer locks another customer whose name starts
  with it, deleting one ends its processes, and the suspended and IP counters stay right.

## v0.24 (2026-10-06)

The command audit covers files, cron, databases, the firewall and mail, the `v-*` names are gone, a mail password
change no longer gets the user's other devices banned, and FireHOL lists can be picked from their catalogue.

### Added

- **A mail password change gives the other devices of the network time to catch up** (#1155). For 12 hours
  dovecot failures of that account from that network do not count towards a ban, for one hour no exim failure from
  it does, and a ban the network already has is lifted; a global IPv6 address stands for its /64. Panel, Roundcube
  and Tachyon set it, `h-add-mail-account-grace` by hand, `MAIL_PW_GRACE` / `MAIL_PW_GRACE_SMTP` set the minutes.
- **FireHOL blocklists can be picked from the live catalogue** (#510). The ticked lists become one IP list with one
  DROP rule, combined lists hide what they already contain, and a list FireHOL stops serving is skipped on refresh
  instead of freezing the set.
- **CrowdSec protects new web domains by default** (#1176) wherever its nginx bouncer runs; off per domain.

### Security

- **The server config editor read and wrote more than it offers** (#1176). It handed out the SSH host keys, the
  MariaDB root password and customer records, and its writer copied any file as root into a service config. Both
  now work on one set of files derived from the installed services, and a config the service rejects is never
  applied.
- **Database names stay within their account** (#1176). A database, database user or account whose names lie in
  another account's is refused when created, moved or restored. Also: the server status page escapes what it shows,
  and a database download is readable by the panel only.
- **The dovecot jail never banned on Debian 13 and Ubuntu 26.04** (#1171). The stock filter does not know dovecot
  2.4; our own reads 2.3 and 2.4 and counts a failure once.
- **A DROP rule over an IP list could lock out the admin network** (#510). FireHOL Level 1 contains the private
  ranges; such a rule now leaves loopback and them alone, and an update re-renders a box that has one.
- **Paths are compared as whole components** (#1176): `/home/ab` no longer passes for customer `a` in the fs
  commands. Customer directory names reaching records and configs are held to a closed set, and record parsing
  refuses to bind a reserved control name like `user` or `crontab`.
- **Mail account, domain and system commands are stricter** (#1176), and Tachyon webmail serves only what belongs to
  the browser.

### Changed

- **Tachyon is 4.3.1** (#1194) on a fresh install; an existing box gets it by running `h-add-sys-tachyon`, which also
  replaces the plugins whose version moved.

### Removed

- **The `v-*` command names** (#1176). Call `h-*` with the same arguments, or create aliases outside
  `/usr/local/hestia/bin`; a `v-*` symlink left there fails the smoke check. An update removes the shipped ones, and
  a restore of a pre-1.9 HestiaCP archive names cron jobs that still call them.
- **Sixteen commands without a caller** (#1176): twelve file commands from HestiaCP's app installer and the
  VestaCP file manager, `h-add-cron-restart-job`, `h-delete-cron-restart-job` and `h-check-mail-account-hash`. An
  update deletes them from the box.

### Fixed

- **Commands that reported success without having done it** (#1176). Firewall changes the ruleset refuses are
  rolled back, IPv6 and network bans render, a shrunken IP list is kept, a manual ban survives a fail2ban restart.
  A database that cannot be dropped keeps its record, a suspended PostgreSQL database stays closed, an unreachable
  host fails after 10 seconds. Suspended mailboxes stay suspended through a rebuild, IDN mail domains can be
  suspended and moved, one domain's spam settings no longer decide for another's recipients, removing rspamd no
  longer breaks logins, deleting the mail queue deletes it, and mail addons and Roundcube can be removed and added
  again.
- **Adding a mail domain left its webmail unreachable until some later reload** (#1172), like every restart after a
  command that read a customer record: the record's `SHELL='nologin'` broke the web freeze check.
- **A restore broke off at the mail and home archives when `BACKUP_TEMP` was set** (#1176), and read parts of a
  PostgreSQL password hash as record keys. Record helpers now read a record field by field.
- **The fail2ban page of the config editor could never save** (#1176); the first save creates `jail.local`, and one
  fail2ban cannot run on is rolled back.
- Smaller ones: mail through an authenticated SMTP relay was not DKIM-signed, moving a message from Spam to Trash
  trained it as ham, ManageSieve listened on every address, and a webmail alias change switched every domain to the
  default client (#1176).

## v0.23 (2026-09-27)

The panel keeps eight languages and speaks German completely, a customer's databases can share a user or add a
read-only one, ionCube joins the addons, and every PHP version gets one extension set.

### Added

- **A MySQL database can share a user with another database of the customer, and carry a second one** (#725),
  full or read-only, carried by suspend, rebuild, backup and restore.
- **ionCube loader as an installer addon** (#1069), pinned per architecture, for every customer PHP but 8.0.
- **The installer asks for the system locale, and the panel's language follows it** (#1164).

### Security

- **A customer could take over another customer's database user** (#725), because the prefix does not keep names
  apart; a database restored without a password hash got a user without a password.
- **The SMTP relay password was readable by every account** (#1142), and **mail certificate keys were
  world-readable** (#1135).

### Changed

- **The panel speaks eight languages, from text sources** (#1160); English and German are maintained, 33 others
  were dropped and their accounts moved to English.
- **Sharing a domain is the owner's switch on the web domain** (#751).
- **Every PHP version gets one extension set** (#1069), sized for WordPress, Nextcloud and Magento.
- **The smoke checks the box; checks on the shipped code run in CI** (#1147), with `nginx -t` / `apache2ctl -t`.

### Fixed

- **The panel showed English on Debian 13 and both Ubuntu releases** (#1157), and **an update to v0.22 stopped
  halfway** (#1132).
- **An OS-PHP box left customer PHP unhardened** (#1069), and **saving the PHP page overwrote every php.ini on the
  box** (#1144).
- **An SMTP relay login with `\` or `` ` `` made the mail backup unrestorable** (#1143), and **an account created
  under `umask 077` was broken without an error** (#1134).
- Smaller ones: phpMyAdmin or Roundcube answered 500 on a PHP 8.5 panel (#1149), removing a PHP version could take
  them along (#1069), a package rename and three more panel actions failed under sudo's path (#1158), and
  counters drifted after an aborted restore (#1137).

## v0.22 (2026-09-24)

Panel and webmail share one out-of-office notice, the panel can sit behind a web domain, every command
answers `--help`, and the spam path loses three defects inherited from HestiaCP.

### Added

- **With Sieve, panel and webmail share one out-of-office notice** (#784); without Sieve exim answers as before.
- **A web domain can carry the panel** (#878), so the panel port can close.
- **Every command answers `--help`** (#657), and **the clock is stepped once a day** (#1098).

### Changed

- **A remote PostgreSQL host has to speak TLS** (#1098, #980), unless its line carries `TLS='no'`.
- **The exim autoreply follows RFC 3834** (#784), **the update lower bound is `v0.21`** (#1111), and **Tachyon is
  4.2.5** (#1125).

### Fixed

- **A sender could keep spam out of the spam folder with an `X-Spam-Status` of their own** (#1121), the first spam
  to a new mailbox waited for its first ordinary mail (#1127), a restored PostgreSQL database left the customer
  without rights (#1113), the Roundcube password change failed on every box (#878), and a sieve redirect failed SPF
  (#1095).

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
