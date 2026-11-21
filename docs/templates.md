# templates.md - the operator's templates

> **The operator's view:** where the templates live that you choose, copy and edit, what an update
> does with them, and what stays shipped. The other operator docs are listed in `docs/README.md`.

**Living doc.** A change to the template places, the lookup order or what an update does with them
updates this file in the same PR.

---

## Two places

| place | who writes it | what it holds |
|---|---|---|
| `/etc/hestia/templates/` | you | your copies, your own templates, your overrides |
| `/usr/local/hestia/share/templates/` | the release | the shipped templates; every update overwrites them |

Never edit under `/usr/local/hestia`: the next update puts the shipped file back. Everything under
`/etc/hestia/templates/` is yours, and no update ever overwrites a file there.

| folder | used for |
|---|---|
| `nginx/` | web templates where nginx serves the domain itself (nginx-only) |
| `proxy/` | proxy templates where nginx stands in front of apache (both); one of your own may skip apache entirely, e.g. an app proxied straight to its port |
| `php/` | PHP pool profiles (`default`, `high`, `small`, your own) |
| `email/` | overrides of the panel's notification mails, per language in `email/<lang>/`; see `email/README.md` |

All four exist on every box, whatever the web model: a `proxy/` on an nginx-only box waits for the
day apache is added. apache and docker templates are shipped only, there is no folder for them here.

## Lookup

By name, `/etc/hestia/templates/<folder>/<name>.tpl` first, then the shipped one. The lists
(`h-list-web-templates`, `-proxy`, `-backend`) and the panel's selects show both together.

- **Your own template:** put `meins.tpl` into the folder, and it can be chosen as `meins`
  (`h-change-web-domain-tpl`, `h-change-web-domain-proxy-tpl`, `h-change-web-domain-backend-tpl`, or the panel).
- **`default` and `panel`** are not copied. A file of that name in your folder overrides the shipped
  one, and from then on a fix to the shipped one no longer reaches the box: you own it.
- **The ready-made ones** (wordpress, laravel and so on, the PHP profiles) are copied into your folder.
  Edit them in place.

## What an update does

- **A copy is missing:** it is copied in again, after every update. To get the current shipped state
  back, delete your copy and update, or copy it from `share/templates/` by hand.
- **A copy exists:** it is never overwritten. A fix the release needs to make to a copy comes as a
  patch in the update manifest and only touches what it names.
- **The policy fragments** (CrowdSec, bot limit) are included, not copied, so they reach your copies
  too. `h-check-sys-smoke` names a template of yours that lacks one as `skip`; the shipped ones
  must have them.

## From an older release

The update to v0.37 moves the old tree (`/usr/local/hestia/templates/` and the proxy templates in
`share/web/nginx/`) over: a file still as shipped is dropped and copied fresh, an edited or own one
moves as it is, an edited `default` or `panel` becomes an override. The update output names each
file it kept. What has no place any more (an edited apache or docker template) lands in
`/etc/hestia/templates/unplaced/`, where nothing reads it.

## Not here (yet)

The suspension pages, the home skeleton and the awstats templates stay shipped under
`/usr/local/hestia/share/web/` (#1413).
