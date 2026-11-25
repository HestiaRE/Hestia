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

_Nothing yet._

## v0.39 (2026-11-25)

The upstream 1.10.10 round closes two ways from a customer account to root, SPF and DMARC become editable on the zone
page, and an admin can keep notes on a user.

### Added

- **SPF and DMARC are edited on the zone page** (#1439). Each row takes a value of its own, kept as an exception the
  next sync writes; an SPF that does not authorise the box is saved with a warning naming what it leaves out.
- **A DNS move can be finished, and www can follow the apex in place of a CNAME** (#1453, #1454). `h-sync-dns USER
  finish` drops the old addresses from the SPF where the new one stays covered; `follow` with `cname` switches the
  www include on and removes the CNAME or ALIAS that would win over it.
- **Authentication-Results carries rspamd's SPF and DMARC** (#1455) beside exim's iprev and DKIM, in one header.
- **Admin notes on a user** (#1427), on the six main lists, never shown to the customer. They travel with the backup,
  where the customer can read them, so they hold no secrets.
- **Deleting a user can take the backups along** (#1477, upstream 1.10.10). `h-delete-user USER [RESTART] yes`, or
  the checkbox in the delete dialog, removes every archive and the restic repository; a remote target is best effort,
  and what stays is named in the system log. The default keeps them.

### Security

- **A customer could run commands as root through their stats folder** (#1464, upstream 1.10.10). Only month folders
  are listed now, and the sed that reads them runs as the customer.
- **A customer could get rights on another database, up to MariaDB root** (#1466, #1472, upstream 1.10.10). A grant
  named its database with `_` and `%` as wildcards, so `mys` with database `l` held rights on `mysql`. Grants name the
  database exactly now, the update replaces the old ones, and a new user name has at least three characters with
  `mys`, `m_s` and `mysq` reserved; the installer checks the admin name the same way.
- **nginx answers without TLS 0-RTT** (#1468, CVE-2026-132779), which nginx 1.21.4 to 1.31.9 lets an attacker turn
  into CPU load. The update switches the shipped line off.
- **Tachyon 4.8.0** (#1484, GHSA-3w5p-7qc3-89gm): a crafted link could send IMAP commands as the user.
- **A DES or MD5 login hash is replaced at the next correct panel password** (#1478, upstream 1.10.10). Only a very
  old restored archive brings one; upstream only warns.

### Fixed

- **A queued restore or backup that aborted stayed in the queue** (#1474, upstream 1.10.10), ran again on every pass
  and blocked every other one of that customer. Every exit the shell sees removes it now.
- **A read receipt went out directly instead of through the domain's relay** (#1475, upstream 1.10.10), and was lost
  where port 25 is blocked. Mail with SMTP AUTH and an empty sender takes the login's domain now.
- **One broken php-fpm pool took every site of its PHP version down** (#1470, upstream #6040). A restart tests the
  configuration first and leaves a failing version running.
- **DNS sync** (#1439, #1442, #1450, #1451, #1452): a server move keeps the old box in an SPF of `a mx` and names the
  exceptions it skips, the zone page shows the provider's SPF and DMARC wherever they differ, "Apply" on a www that
  mirrors the apex takes the apex along, and an added record of a deleted domain can be removed from the provider.
- Smaller: doveadm reads compressed mail on dovecot 2.4 (#1479, upstream 1.10.10), a web domain of a suspended user is
  no longer unsuspended on its own (#1480), the server page reads the OS from `/etc/os-release` (#1481, upstream
  #6050), and the wizard refuses an admin name the system already uses (#1482).

## v0.38 (2026-11-23)

The DNS provider sync follows an IP change, issues wildcard certificates and takes a customer's own records, and a
package a mirror does not serve no longer ends an install.

### Added

- **Own DNS records on the zone page** (#1428): a TXT or CNAME of the customer's, or one adopted from the provider,
  compared and written like a derived one; single rows can be applied, an SPF normalised.
- **Wildcard certificates through the DNS provider** (#1422), over DNS-01 where an account holds the zone.
- **An IP change and a server move carry the DNS records along** (#1419), through `h-sync-dns USER|all OLD=NEW` with
  a preview; a restore offers the move on the DNS tab.

### Changed

- **The derived SPF is `v=spf1 a mx ~all`** (#1428); an older SPF with addresses keeps working.
- **Updates start from v0.30** (#1433).

### Fixed

- **One package a mirror did not serve ended the whole install** (#1418). A missing PHP module is left out with a
  warning until `h-add-web-php VERSION` brings it.
- **A docker customer lost the own daemon socket after a reboot** (#1430): `useradd` sorted `/etc/subuid`.
- Smaller: disk counters stayed too high after a delete until night (#1417).

## v0.37 (2026-11-21)

DNS exceptions and orphans complete the provider sync, the operator's templates move to `/etc/hestia/templates`, and
an update ends with the smoke test.

- **Added:** DNS exceptions and orphans (#1333).
- **Security:** Adminer 6.3.1 and Tachyon 4.7.0 (#1414).
- **Changed:** the operator's templates live in `/etc/hestia/templates` (#1411); an update ends with the smoke test
  (#1405).
- **Fixed:** project quota where dracut builds the initramfs (#1329), and smaller ones (#1401, #1403, #1408).

## v0.36 (2026-11-19)

CrowdSec honours the whitelist and a domain's switch, the IP page checks reverse DNS, a customer's container reaches
PostgreSQL over the socket, and the operator docs move to `docs/`.

### Added

- **CrowdSec scenarios can be switched off one by one** (#1381): they still detect and alert, but ban nobody.
- **The IP page shows the reverse DNS of every address** (#1372), with a daily banner while one does not match.
- **The operator docs live in `docs/`** (#1390), with a guide to the per-customer Docker model.

### Changed

- **A customer's container logs in to PostgreSQL over the socket by password** (#1391); a role with an md5 hash needs
  a new one.

### Fixed

- **CrowdSec banned whitelisted addresses and domains switched off** (#1381).
- **One hand-edited whitelist line stopped every firewall change** (#1384); it is left out and named.
- Smaller: Sieve `reject` answered spam's forged sender (#1371), a paniclog line after a fresh install (#1376),
  `h-change-user-password` asks twice (#1386), `h-add-sys-docker` names a failed repository load (#1392), and the 2FA
  reset page showed no message (#1396).

## v0.35 (2026-11-17)

Forwards and autoreplies live in Sieve and no longer bounce to a forged sender, a full mailbox is refused in the SMTP
session, and a catch-all stays in its own domain.

### Changed

- **A forward is a redirect in the mailbox's Sieve script** (#1337), so a refusing target no longer bounces to the
  forged sender of spam, the backscatter that got a box listed. Four addresses at most; the update moves existing
  forwards. exim no longer answers autoreplies, the vacation rules in Sieve do.
- **A full mailbox is refused in the SMTP session** (#1337) with 552, through dovecot's quota-status.
- **A catch-all goes to an account of its own domain** (#1337); a restore drops an external one and says so.

### Fixed

- **A busy sending host was refused with 550 for every mail to the box** (#1366): exim's send limit counted inbound
  mail too. Inherited from upstream.
- **fail2ban stopped watching exim's log after a rotation on a quiet box** (#1362) without python3-pyinotify.
- Smaller ones: the panel's autoreply switch wiped a filter set switched off in the webmail, and vacation answers could
  be deferred once the box sent 200 an hour on dovecot 2.4 (both #1337).

## v0.34 (2026-11-15)

Sieve on every mail box, root work in a customer's home that a swapped link can no longer redirect, and the findings
after HestiaCP 1.10.7.

### Added

- **NetAPI honeypot list in the blocklist picker, and a source link for every list** (#1351, upstream #5974).
- **Show button on masked password fields** (#1350, upstream #5959): login, password reset and the DNS provider key.

### Security

- **A customer could take over files outside their home** (#1348, upstream GHSA-hc82-cqjf-hcpg,
  GHSA-pfj7-fhx8-q4qx): a directory swapped for a link redirected root's work. root now checks where it is first.
- **On a box whose admin is not called `admin`, the panel's rules for that account did not apply** (#1349, upstream
  GHSA-p4vq-6v6h-cc74). They follow the configured `ROOT_USER` now.
- **A secret handed over as a /tmp file is read only from root or the caller** (#1350, upstream GHSA-vxpc-8vm2-8rv3).

### Changed

- **Sieve comes with mail** (#1337): delivery through LMTP, vacation rules and webmail filters on every mail box; the
  update adds it, `h-delete-sys-sieve` is gone.

### Fixed

- **apache2 and nginx stayed down after a reboot on a static IPv6 address** (#1344): the address was still tentative
  when they bound it. `net.ipv6.ip_nonlocal_bind` lets them.
- **A Let's Encrypt rate limit held the installer for hours** (#1346); the waits are bounded.
- **An http auth password counted only its first 8 characters** (#1350). New and changed ones are sha-512.
- Smaller ones: a login with a sha-512 hash carrying `rounds=` always failed (#1350, upstream #5956), adding an
  existing mail domain switched its spam rejection on (#1350, upstream #5950), and "log in as" from the search results
  followed a path off the panel (#1350, upstream #5957).

## v0.33 (2026-11-14)

DNS provider accounts that compare and write a zone, forwards that no longer send backscatter, and boxes with
several public addresses.

### Added

- **DNS provider accounts** (#1332/#1333, part of #863). An InterNetX AutoDNS account compares each zone with what
  HestiaRE derives and writes a section after a preview, never a record HestiaRE does not derive.

### Security

- **Tachyon 4.6.0** (#1341): a folder name rendered as HTML in two confirm dialogs, and `javascript:` and similar
  links in a message body kept their href.

### Changed

- **The derived DMARC record is `p=none`** (#1333), and **new domains default to the IP the box reaches the internet
  from** (#1330).

### Removed

- **The update manifests at or below the lower bound** (#1339).

### Fixed

- **A refused forward bounced to the original sender** (#1336), forged in spam; the backscatter got a box listed at
  Spamhaus CSS. It bounces into devnull now.
- **A second public IP next to an internal NAT address got the NAT on the wrong record** (#1330), and the firewall
  opened only sshd's `Port`, not a port in a `ListenAddress`.
- Smaller ones: the primary-IP guard of `h-delete-sys-ip` (#1330), `webmail.<domain>` listed without webmail (#1333),
  and a resolver failure that held a zone "unknown" for an hour (#1332).

## v0.32 (2026-11-12)

Automatic updates on Debian and for Sury PHP, record loops that start every record from empty, and the findings
after HestiaCP 1.10.6.

### Security

- **A Debian box received no automatic updates, not even security ones** (#1292). unattended-upgrades runs now with
  Debian's own configuration, and patches Sury PHP as well (upstream #5876); nginx.org and MariaDB repos stay manual.
- **Two record-loop leaks** (#1323): the subdomain ownership check, and a mail account written with another one's
  hash.

### Changed

- **apt keeps a conffile HestiaRE edited instead of asking** (#1292), so unattended-upgrades no longer skips such a
  package; the package's version lands as `.dpkg-dist`.
- **The domain and mail account lists validate a user's records in one PHP start** (#1317, upstream #5726/#5890).

### Fixed

- **A record read in a loop inherited the previous record's value for a key it lacks** (#1321/#1323): wrong document
  roots and PHP versions in lists and backups, doubled user counters.
- **proftpd did not start on Ubuntu 26.04 after a Let's Encrypt host certificate** (#1318, upstream #5778); the key
  files stay `root:mail` now.
- Smaller ones: a `www.<name>` rename no command could reach (#1318, upstream #4958), database backups without events
  and localhost grants (#1318, upstream #5923), and three inherited papercuts (#1317, #1318).

## v0.31 (2026-11-09)

The DNS records HestiaRE needs, the MariaDB client under its own names, and record writes that fail loudly.

### Added

- **A DNS tab that lists the records HestiaRE needs, per zone** (#1307/#1308, part of #863). No DNS server: the zone
  comes from public DNS, and each section gives its records as rows and zone file lines. Admin only;
  `h-list-dns-zones` and `h-list-dns-zone` give the same on the command line.

### Changed

- **Debian 13 and Ubuntu 26.04 LTS are the primary targets** (#1300); Debian 12 and Ubuntu 24.04 LTS are best effort.
- **HestiaRE calls the MariaDB client by its own names** (#1302), and `mariadb-client-compat` keeps `mysql` and
  friends for whoever types them.
- **The restore's consent questions take `a` for all following sections** (#1305).

### Fixed

- **A record that could not be updated passed with rc 0** (#1312); the command stops with E_UPDATE now.
- **A restore from HestiaCP put every web domain on the PHP version of the first one** (#1305).
- Smaller ones: the server backup left the webmail databases out on MariaDB 11.x without Recommends (#1302), and
  `h-restart-system` and `h-add-backup-host` took DELAY and PATH unchecked (#1311).

## v0.30 (2026-11-06)

The HestiaCP 1.10.6 round, webmail that shows only the server's own verdicts, ECDSA certificates and a DKIM
selector per mail domain.

### Added

- **New certificates get an ECDSA P-256 key** (#1247, upstream #5629); a renewal keeps the type a domain has.
- **A mail domain can sign under its own DKIM selector** (#1248, upstream #5760), kept by a restore on both sides.
- **`h-nft` changes the live firewall by hand** (#1273); a render, restart or reboot restores the configured state.
- **Webmail shows the spam score of every scanned mail** (#1295).

### Security

- **Webmail could show a DKIM, spam or virus verdict the sender wrote** (#1288). exim drops incoming verdict
  headers and writes its own.
- **Restore and web rebuild refuse a domain whose web or mail directory is not root's own** (#1289, upstream #5796).
- **Adminer 6.3.0 and Tachyon 4.5.0** (#1288): Adminer's login CSRF check, Tachyon's DKIM and keyword display.

### Fixed

- **exim's check for an IP-like HELO never fired** (#1291, upstream #5862).
- **The panel could not issue a Let's Encrypt certificate for a mail domain** (#1284).
- Smaller ones: edit buttons for a suspended customer (#1290, upstream #5835), quotes in a cron command (#1290,
  upstream #5892), and a Unicode SAN for an IDN self-signed certificate (#1247, upstream #5814).

## v0.29 (2026-10-25)

Renumbering a box in one command, IPv6 chosen per web domain and per firewall rule, and a reboot that brings back
the bans and blocklists of the moment.

### Added

- **`h-move-sys-ip IP NEW_IP` moves everything on one address to another** (#1265): web domains, webmail, mail
  source addresses and firewall records, suspended domains included; before, each domain had to be moved alone.
- **A firewall rule for any source can be limited to IPv4 or IPv6** (#544).
- **A web domain on a dual-stack box can leave the IPv6 off** (#1266), `h-change-web-domain-ip6 USER DOMAIN none` or
  "No IPv6" in the panel; mail and webmail follow it.

### Fixed

- **A reboot brought back lifted bans and outdated CrowdSec decisions and blocklists** (#1260/#1263). The sets now
  boot empty and are filled from the current state before the network comes up.
- **An unban fail2ban runs itself hung for a minute** (#1259, upstream #5699), at every stop or reboot with an
  active ban; a panel unban of a panel-login ban never reached fail2ban.
- **Deleting a customer or domain failed while the web server would not restart** (#1265/#1275), the normal state
  in the middle of an IP change; a restart failure after a delete is a warning now.
- **Moving a web domain to another customer broke off halfway** (#1270), and changing its IP left mail source
  address and webmail behind (#1274).
- Smaller ones: the mail DNS page listed the wrong addresses (#1279), a MAIL exclusion `*:account` excluded nothing
  (#1279), a v6-only web domain saved in the panel got an IPv4 (#1271), a failed config test left no log line
  (#1277), and a malformed CSRF token crashed Adminer (#1279).

## v0.28 (2026-10-23)

A second login name per mailbox, a web domain rename that keeps what hangs on the domain, the upstream round
after HestiaCP 1.10.5, and backup names, exclusions and selections that mean exactly what they say.

### Added

- **A mailbox can carry a second login name** (#1238) for IMAP, POP3 and SMTP, kept by hand in
  `/etc/dovecot/login-aliases`; dovecot 2.4 only.

### Security

- **Tachyon 4.4.7, Adminer 6.1.2 and Alpine.js 3.17.5** (#1243). Tachyon ends the server-side session on logout and
  closes a backup restore without an admin session.

### Changed

- **Renaming a web domain takes SSL, FTP accounts, document roots, statistics and WordPress along** (#1197).
- **A box behind NAT learns its public IPv4 from `ip.hestiare.com`** (#1215), and a new web domain's folders start
  at 751 (#1237/#1246, upstream #5730).

### Removed

- **`h-add-user-wp-cli` and `h-add-user-composer`** (#1251, upstream #5787); the system-wide copies serve.

### Fixed

- **Backup names and exclusions** (#1245/#1246, upstream #5754): a name starting with `-`, an exclusion that left
  out more or nothing, and a restore selecting `a.com` that also took `aXcom`.
- **A web domain `mail.<d>` or `webmail.<d>` beside the mail domain `<d>` broke both** (#1246, upstream #5829); the
  pair is refused now.
- Smaller ones (#1197/#1235/#1246): rename and IDN edge cases, Let's Encrypt staging, apache-only on Debian 12, and
  four unescaped panel outputs.

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
