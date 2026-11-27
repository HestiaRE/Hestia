# Hestia RE Security policy

Welcome and thanks for taking interest in Hestia RE!

We are mostly interested in reports by actual Hestia RE users but all high quality contributions are welcome.

If you believe that you have have discovered a vulnerability in Hestia Control Panel, please let our development team know by sending an email to <info@hestiare.com>

We ask you to include a detailed description of the vulnerability, a list of services involved (e.g. exim, dovecot) and the versions which you've tested, full steps to reproduce the vulnerability, and include your findings and expected results.

Please do not open any public issue on Github or any other social media before the report has been published and a fix has been released.

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| Latest  | :white_check_mark: |

## Separation between customers

Every customer runs PHP in pools of their own user, behind file modes, ACLs and open_basedir. Since v0.41 each customer
has an own PHP-FPM master per PHP version, so no two customers share an OPcache, APCu or anything a site sets for its
own pool, and apache binds each customer vhost's FastCGI handlers to that domain's own pool (from apache 2.4.63 on).
On an older apache (Ubuntu 24.04) a .htaccess can still hand a request to another customer's socket.

Sites under the admin account are privileged: the admin is not in `hestia-users`, so the ACL that closes a customer's
home to the others does not apply to them. Do not host anyone else's site under the admin account.

## Qualifying Vulnerabilities

### Vulnerabilities we really care about

- Remote command execution
- Code/SQL Injection
- Authentication bypass
- Privilege Escalation
- Cross-site scripting (XSS)
- Performing limited admin actions without authorization
- CSRF

### Vulnerabilities we accept

- Open redirects
- Password brute-forcing that circumvents rate limiting

## Non-Qualifying Vulnerabilities

- Theoretical attacks without proof of exploitability
- Attacks that are the result of a third party library should be reported to the library maintainers
- Social engineering
- Reflected file download
- Physical attacks
- Weak SSL/TLS/SSH algorithms or protocols
- Attacks involving physical access to a user’s device, or involving a device or network that’s already seriously compromised (eg man-in-the-middle).
- The user attacks themselves
- anything in `/test/` folder
