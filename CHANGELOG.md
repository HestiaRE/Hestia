# Changelog

All notable HestiaRE changes are documented here, starting from the fork
point - a HestiaCP 1.9.6 snapshot, kept read-only in the `upstream/hestiacp`
branch.

Maintenance rule: every larger change adds an entry to the Unreleased
section as part of its PR. Only public minors get a section - an internal
build `vX.Y-devN` belongs to the minor it leads to. On release, the section
gets that version number and a new Unreleased opens above it. The releases
before v0.20.0 are folded into one section by theme, and v0.24 to v0.27,
the releases of the command audit, into another.

## Unreleased

### Added

- **`h-move-sys-ip IP NEW_IP` moves everything on one address to another** (#1265). It moves every web domain,
  suspended ones included, with its webmail, its mail source addresses and the firewall records, rebuilds them and
  tries every restart, naming the failed ones. Before, a renumbered box meant moving each domain on its own, each move
  failing at the restart while any other domain still pointed at the vanished address, and a suspended domain could
  not be moved at all. An address no longer on any interface is deleted along with it. The single-v4 rename of
  `h-update-sys-ip` takes the same path and now also moves the mail source addresses.
- **A firewall rule for any source can be limited to IPv4 or IPv6** (#544). A rule for 0.0.0.0/0 always covered
  both families, so a port could not be opened on v6 alone, or opened on v6 and restricted to one network on v4.
  The rule forms have an IP version select, the CLI an optional last argument, and the list names the family of
  every rule. A rule with a CIDR or a list keeps the family of its source.
- **A web domain on a dual-stack box can leave the IPv6 off** (#1266). Every domain took the default v6, and a
  rebuild filled an emptied field again, so a domain whose DNS has no AAAA on purpose could not opt out.
  `h-change-web-domain-ip6 USER DOMAIN none` sets `IP6='none'`, an address sets a v6 again. The panel offers
  "No IPv6" when adding and editing. Rebuild, restore and an owner change keep it; mail and webmail of the
  domain follow it. A domain without a v4 cannot drop its v6.

### Fixed

- **An unban fail2ban runs itself no longer hangs for a minute** (#1259, upstream #5699). `h-delete-firewall-ban`
  reported every unban back to fail2ban, which answers no client while it runs that very action, so each one waited
  out the 60 s action timeout: every stop and reboot with an active ban, and a mail password change in the panel
  whenever a device of that network was banned. The fail2ban actions now mark the call, panel and CLI keep the
  callback. Existing boxes get the action files through the update, loaded by a reload.
- **A panel unban of a panel-login ban reaches fail2ban** (#1259). The callback looked for an action named after
  the chain and never found the panel jail's own, so fail2ban kept the ban and set it again at its next start. It
  also missed a jail with more actions than one unless the hestia action came first.
  An unban on WEB no longer reaches the WEBSCAN jails either.
- **A reboot brings back the bans banlist.conf holds, not those of the last render** (#1260). A live ban never
  rewrote the boot ruleset, so after a reboot a lifted ban was enforced again, out of reach of any unban, and a
  ban set by hand since the last render was missing although the panel listed it. The boot ruleset now carries
  no bans; hestia-nftables loads them from banlist.conf right after it, still before the network comes up, and
  a row nft refuses is skipped and logged instead of costing the others. The smoke checks every row against its
  set. The update renders once, so the first reboot after it is already right.
- **A reboot no longer brings back lifted CrowdSec decisions and outdated blocklists** (#1263). The boot ruleset
  carried the CrowdSec and blocklist sets as of the last render, until their feeders next ran: a cleared false
  positive was blocked again for about 45 s, a blocklist was as old as the last render for up to 45 min. These
  sets now boot empty and hestia-nftables fills them from the cache files their feeders last wrote, before the
  network comes up. The smoke compares every such set with its cache file.
- **Deleting a customer or a web domain works while the web server will not restart** (#1265). The delete
  removed records, vhosts and files first and then failed at the restart, so it was reported as failed, the steps
  after the restart were skipped, and `h-delete-user` stopped before the mail domains, databases and the account.
  Every retry failed the same way, which is the normal state in the middle of an IP change. Deleting the vanished
  IP itself stopped there as well, before the firewall dropped its accept rule. A restart failure after a delete is
  now a warning, written to the system log and mailed to the admin as before.
- **Deleting an IP moves the webmail of mail domains that only defaulted to it** (#1265). A mail domain without a
  web domain renders its webmail on the user's first address. After that address was deleted, the vhost kept
  listening on it and the web server no longer started.
- **Moving a web domain to another customer works again** (#1270). `h-change-domain-owner` broke off halfway for
  every web domain: its own suspend pointed the vhost links back into the old home, which the rebuild for the new
  owner then refused to take over. On a box behind NAT it died a second time at the IP counters, and always picked
  a new address. The domain, its SSL, mail and webmail now arrive in one go, an address the new owner can use stays,
  a v6-only domain keeps no v4, and the counters agree.
- **Changing a web domain's IP moves its mail and webmail along** (#1274). On the CLI the outgoing mail address
  and the webmail vhost stayed on the old address, and the webmail vhost broke every web server restart once that
  address was gone. The panel's own second rebuild had hidden it there.
- **Saving a v6-only web domain unchanged in the panel no longer gives it an IPv4** (#1271). Such a domain now
  shows "No IPv4" in the address select. Picking an address adds one as before.
- **Deleting a mail domain while a service will not restart is a warning** (#1275). The SSL and webmail steps
  broke off at their restart and left only an error line on stdout. The delete now restarts at the end, and a
  failed restart reaches the system log, naming the delete.
- **A failed web or proxy config test reaches the system log** (#1277). `h-restart-web` and `h-restart-proxy`
  stop at the config test before any restart, mailed the admin and printed an error, and left no log line.
- **The mail DNS page lists the addresses the domain sends from** (#1279). It showed the customer's first v4 and
  v6 whatever the domain used, so a domain on another address got the wrong A row and SPF entry, and one without a
  v6 an AAAA row it never answers. A domain whose mail goes out through a relay keeps the per-family pick.
- **A MAIL backup exclusion `*:account` leaves that account out on every domain** (#1279), as `*:dir` does for WEB.
  The exclusion list accepted it, but the archive and the restic mode both backed the account up everywhere.
- **A malformed CSRF token no longer crashes Adminer** (#1279). A posted token that was an array or not two numbers
  ended in a PHP Fatal and a 500, which the smoke then reported as a panel log error; it is now an ordinary invalid
  token.
- **The smoke's firewall checks hold on large sets** (#1279). The ban check asked nft for a whole jail set at once,
  which crashed nft from a few hundred bans, and the feed check passed a set whose copy came out empty although its
  cache file held addresses.

## v0.28 (2026-10-23)

A second login name per mailbox, a web domain rename that keeps what hangs on the domain, the upstream round
after HestiaCP 1.10.5, and backup names, exclusions and selections that mean exactly what they say.

### Added

- **A mailbox can carry a second login name** (#1238) for IMAP, POP3 and SMTP. The admin keeps the aliases by hand
  in `/etc/dovecot/login-aliases`; rate limits, fail2ban and the password grace still see the address. Not in any
  backup and no panel, dovecot 2.4 only.

### Security

- **Tachyon 4.4.7, Adminer 6.1.2 and Alpine.js 3.17.5** (#1243). Tachyon 4.4.3 ends the server-side session on
  logout and closes a backup restore that ran without an admin session; Adminer 6.1.1 closes a file write through
  SQLite, a driver the panel's Adminer never lets a login pick. An update brings both to installed boxes.

### Changed

- **Renaming a web domain takes SSL, FTP accounts, custom document roots, statistics and WordPress along**
  (#1197) instead of refusing them. A new Let's Encrypt certificate is requested; if that fails, the domain stays
  renamed without SSL.
- **A box behind NAT learns its public IPv4 from `ip.hestiare.com`** (#1215) instead of the HestiaCP project's
  service, and only a public IPv4 in the answer counts. A later `h-update-sys-ip` that sets the address adds the
  boot entry an install without one could not.
- **A new web domain's folder and `public_html` start at 751** (#1237/#1246, upstream #5730), where a rebuild
  leaves them; the operator key `DOMAINDIR_WRITABLE` is gone.

### Removed

- **`h-add-user-wp-cli` and `h-add-user-composer`** (#1251, upstream #5787). wp-cli is installed system-wide and
  pinned, composer is a system-wide addon that `h-update-sys-composer os` adds to a box without it; the per-user
  copy only shadowed them and went stale. An update removes the commands.

### Fixed

- **A home entry or mail account whose name starts with `-` broke the backup** (#1245, upstream #5754): tar and
  find read it as an option, so a home entry stopped the backup and a mailbox was left out without a word.
- **A backup exclusion could leave out more than it named** (#1245). Mail accounts matched as a prefix of the whole
  entry, domain included, home entries as a pattern, and in restic mode a `domain:path` or `domain:account` entry
  excluded nothing at all.
- **A web domain `mail.<d>` beside the mail domain `<d>` broke both** (#1246, upstream #5829). They shared a
  certificate name and a server name: the mail certificate replaced the web domain's, nginx answered for only one,
  and the mail renewal could read the web certificate. The pair, and `webmail.<d>` likewise, is refused now on
  every account; a restore that would create it stops before writing, and the smoke run reports an old one.
- **A restore that named its objects could take more** (#1246): `a.com` also selected `aXcom` from the archive.
- Smaller ones: a renamed web domain could take a name another of the customer's domains answered to (#1197), the
  statistics of a restored domain could stay unbuilt (#1197), changing an FTP path failed while the site ran
  (#1197), a new WordPress showed the placeholder on apache (#1197), deleting an IDN domain left its statistics
  config (#1197), Let's Encrypt staging failed for an account with a production key (#1197), apache-only on
  Debian 12 could not start apache (#1235), and four panel outputs were printed unescaped (#1246, upstream #5750).

## v0.24 to v0.27 (2026-10-06 to 2026-10-10)

The command audit (#1176): every command was read, run on a box including its failure path, then fixed,
tightened or removed. Alongside it: a grace period after a mail password change, a FireHOL picker, Tachyon 4.4.1,
and the `latest` preset on nginx.org mainline.

### Added

- **A mail password change gives the other devices of the network time to catch up** (#1155): for 12 hours their
  dovecot failures and for one hour their exim failures do not count towards a ban.
- **FireHOL blocklists can be picked from the live catalogue** (#510), as one IP list with one DROP rule.
- **Preset `latest` takes nginx from nginx.org mainline** (#194), with CrowdSec but without its web bouncer.
- **CrowdSec protects new web domains by default** (#1176) wherever its nginx bouncer runs.

### Security

- **Tachyon 4.4.1** (#1194/#1207); 4.4.0 closed nine security issues.
- **The audit** (#1176): the server config editor works only on the files of the installed services, names and
  paths stay within their account, a suspended account no longer logs in by SSH key, a password reset no longer
  skips 2FA, and the Sury and Docker repository keys are pinned by fingerprint.
- **The dovecot jail never banned on Debian 13 and Ubuntu 26.04** (#1171), and **a DROP rule over an IP list could
  lock out the admin network** (#510).

### Changed

- **Updates start from v0.25** (#1176), and **`hestia update` asks before it updates**.
- **https serves `public_html` like http** (#1176); `public_shtml` is gone. **The backup exclusion list is
  stricter** (#1176).

### Removed

- **The `v-*` command names** (#1176); call `h-*` with the same arguments.
- **Commands without a caller or a purpose here** (#1176), among them the app-installer file commands, the cron
  restart jobs, the Let's Encrypt queue and `h-run-cli-cmd`. An update deletes them from the box.

### Fixed

- **Commands that reported success without having done it** (#1176): firewall changes, database drops, service
  starts and stops, restic restores and server backups now fail when they fail.
- **Re-running the installer on a live box broke every customer mail domain** (#1176).
- **Names differing only at a dot were treated as one** (#1176): backup rotation, user deletion, suspension and
  web backup exclusions could reach another customer's objects.
- **Backup and restore** (#1176): a panel restore no longer reruns every five minutes, and `BACKUP_TEMP`, custom
  docroots, FTP accounts and every transport behave.
- **Let's Encrypt, web and mail** (#1172/#1176/#1201/#1210): mail-only hostnames get a trusted certificate,
  renaming a domain moves everything that hangs on it, a model switch carries its add-ons along, a new mail
  domain's webmail answers at once, and IDN mail domains can be suspended and moved.
- Smaller ones (#1176): user and package deletion, firewall bans and the panel port, a failed update leaving the
  panel stopped, `hestia uninstall --purge`, DKIM over an authenticated relay, and ManageSieve on every address.

## v0.23 (2026-09-27)

Eight panel languages with German complete, shared and read-only database users, ionCube as an addon, and one
extension set for every PHP version.

### Added

- **A MySQL database can share a user with another database of the customer, and carry a second one** (#725).
- **ionCube loader as an installer addon** (#1069), and **the installer asks for the system locale** (#1164).

### Security

- **A customer could take over another customer's database user** (#725), **the SMTP relay password was readable
  by every account** (#1142), and **mail certificate keys were world-readable** (#1135).

### Changed

- **Eight panel languages from text sources** (#1160), **sharing a domain is the owner's switch** (#751), **one
  extension set per PHP version** (#1069), and **checks on the shipped code run in CI** (#1147).

### Fixed

- **The panel showed English on Debian 13 and both Ubuntu releases** (#1157), **an update to v0.22 stopped
  halfway** (#1132), **an OS-PHP box left customer PHP unhardened** (#1069), and **saving the PHP page overwrote
  every php.ini** (#1144).
- Smaller ones: an unrestorable mail backup after an SMTP relay login with `\` (#1143), accounts created under
  `umask 077` (#1134), phpMyAdmin or Roundcube on a PHP 8.5 panel (#1149/#1069), panel actions under sudo's path
  (#1158), and counters after an aborted restore (#1137).

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
