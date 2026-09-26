# share/updates

One JSON file per release, read by `h-list-sys-updates` and, during a run, by the executor. The file
says what a box coming from an older version has to catch up on. Nothing in here is shell: conditions
and actions are named building blocks from `include/update.sh`, reviewed like any other code.

## File

```json
{
  "version": "0.19.0",
  "entries": [ ... ]
}
```

The file name is the version with `.json` appended and **without** the `v` (`0.21.json`); name and
`version` field must agree, and a guard checks it. Files are merged and sorted over the whole set,
never per file, and by version, never alphabetically (`0.10` after `0.9` is the trap).

## Entry

```json
{
  "id": "redis-key",
  "description": "Redis had no key of its own; carry the state over from the artefact",
  "conditions": [
    { "type": "key_empty", "name": "REDIS_SYSTEM" },
    { "type": "package_installed", "name": "redis-server" }
  ],
  "action": { "type": "key_set", "name": "REDIS_SYSTEM", "value": "redis" },
  "paths": ["/etc/hestia/conf/hestia.conf"],
  "reversible": true
}
```

The full identity is `version/id` (`0.19.0/redis-key`). Ids are unique inside a file, are never
reused, and an entry never moves between files: an identity is a promise, not a label.

| Field | |
|---|---|
| `id` | letters, digits, `.`, `_`, `-` |
| `description` | one line, shown in the list and the log |
| `conditions` | all of them must hold, at least one is required |
| `action` | exactly one |
| `paths` | what the action writes, the basis of the per-entry backup |
| `reversible` | `true` only if putting files back undoes it |
| `after` | optional, an identity this entry must not run before |

## One action per entry

"First A, then B" is two entries and an `after`, never one entry with two actions. That keeps the
reversibility of an entry the reversibility of its action, with nothing to fold, and it keeps a
mixed pair honest: `file_copy` plus `service_restart` as one entry would be irreversible as a whole
and land behind the point of no return, although the copy could have run in front of it.

`reversible` may be declared **more** pessimistic than the action type allows, never more optimistic.
An entry that has to run behind an irreversible one declares itself `false`, and that is the way to
write a pair whose natural order is irreversible first (`package_remove`, then `key_clear`).

## Order

Reversible entries first, so the point of no return falls as late as it can; within that, `after`
before version before id. A reversible entry may not wait for an irreversible one: it would sit
behind the line and lose the rollback it claims.

## Three rules that are easy to get wrong

1. **A condition must be false once the action succeeded.** That is what makes a repeat after a
   failed run converge, and the executor warns when an entry does not negate its own condition. An
   action that cannot negate it needs a second condition that does.
2. **The list is derived once, before the run.** An entry whose condition only becomes true because
   another entry ran is not in the list at all, and `after` will not save it: `after` orders what is
   already in the run, it does not add anything to it.
3. **A dependency outside the run counts as met.** The entry it names either ran in an earlier update
   or its own condition says it is unnecessary.

Unknown key in a condition: the key names come from `share/hestia/sys-keys.json`, and a name that is
not in it is a typo, not a box fact. A key that is simply empty or absent on the box is the box fact,
and that is what `key_empty` is for.

## Types

Conditions: `key_empty`, `key_is`, `key_has_token`, `path_exists`, `command_exists`,
`path_absent`, `package_installed`, `file_differs`, `file_contains`, `file_lacks`,
`pin_differs`, `php_ext_missing`, `dir_has_secret_value`, `file_patch_pending`,
`file_mode_wider`, `language_unlisted`. Actions:
`key_set`, `key_clear`, `token_add`, `token_remove`, `file_copy`, `path_delete`, `dir_clear`,
`function_call`, `package_install`, `package_remove`, `service_restart`.

Fields per type: `name` and `value` for the key types, `name` for a command, package, service or a
PHP extension, `source` (tree-relative) and `target` for `file_patch_pending`, `path` for
`path_exists`, `path_absent`, `path_delete` and `dir_clear`, `path` and `value` for `file_contains`,
`file_lacks` and `file_mode_wider`, `name` (a key under `software_versions` in `share/manifest.json`) and `path` (the
marker file the component wrote) for `pin_differs`, `source` (tree-relative) and `target` for
`file_differs` and `file_copy` (`mode` optional), `function` for `function_call` (only names in
`UPDATE_CALLABLE`).

## A file the operator may edit is patched, never copied

`file_copy` replaces the target. For a file the operator is invited to edit that is data loss, and
the invitation is real: every target of `h-change-sys-service-config` is a textarea in the panel
(`exim4.conf.template`, eight `dovecot/conf.d/*`, `apache2.conf`, `jail.local`, `sshd_config`, the
hestia crontab). The exim template carries edits of ours on top - the addons uncomment their macros
in place - so copying the tree version over it switches rspamd, sieve and the spam thresholds off
without a word.

Such a change ships as a patch under `share/updates/patches/<version>/`, asked about with
`file_patch_pending` (`source`, the tree-relative patch, and `target`, the file). Context decides, so
no reference copy of the old file is needed, which is what made the alternatives unbuildable: after
the overlay the old tree is gone.

**The applying is a `function_call`, deliberately not a `file_patch` action.** Patching a config
almost always needs a reload only that service knows: exim reads `config.autogenerated`, which nobody
but `update-exim4.conf` writes, so a patched template plus a restart changes exactly nothing. A bare
patch action would be right almost nowhere, and the entry would quietly do half the job.

The condition is deliberately **three-way**, and that is the whole point:

| | |
|---|---|
| the patch applies | rc 0, the entry runs |
| its reverse applies | rc 1, already done, the entry is skipped and stays skipped |
| neither applies | **rc 2**, loud: somebody edited exactly the lines this patch touches |

A two-way condition would report the third case as "nothing to do" and the change would silently
never arrive. `-F0` throughout: a hunk that only roughly matches is drift, not a hit.

**Gate it when the file is not always ours.** On a box without mail the exim template is Debian's stock
file, and the patch condition reads it as edited. So a key condition goes in front (`key_is` `MAIL_SYSTEM`
`exim4`): the check stops at the first false condition, as the plan does, and never reaches the patch.
The price is that a condition behind a false gate is only checked on the boxes where the gate holds.

**Not every shipped file needs this.** A file under `/etc` that the panel does not expose and no
addon rewrites can still be a plain `file_copy`; the patch form is for the ones somebody else owns
a say in. Decide per file, and say which it is in the entry's `description`.

`path_absent` is the plain complement to `path_exists`, and the most common shape update work has:
we ship something the box does not have. The vocabulary carries no negation, so it needs its own name.

The four late conditions exist because a state outside the tree cannot be compared to a file in it.
`file_differs` needs a tree source, so a file the box *generates* is out of its reach:
`file_contains` looks for what the older version wrote instead, and goes false once it is rewritten.
`file_lacks` is its complement, for the entry that has to ADD the marker rather than replace one.
`file_contains` also takes a pattern, as `file_mode_wider` does, for a file that sits once per PHP version; one
match carrying the marker is enough. `file_lacks` stays literal: "one of them lacks it" is a different
question from "it is missing", and no entry has needed it yet.
`pin_differs` reads the pin from the manifest rather than carrying a version of its own. `dir_clear`
empties a directory and leaves it standing, because its owner and mode are part of what it is.
`php_ext_missing` asks `h-list-sys-php` which versions are managed, so the set is derived and not a
second list here; its `name` takes a comma list, because an action that repairs a set has to be
asked about that set. `dir_has_secret_value` asks whether a file in the directory still holds a
registry-secret with a real value: a condition on the directory merely being full describes a normal
steady state and would put its entry in every future plan.
`file_mode_wider` takes a `path` that is a pattern (`/home/*/conf/mail/*/ssl/*`) and a `value` that is
an octal mode, and is true while one matching file carries a bit outside it: for files that sit one
per account or domain, where no single path could be named. A pattern that matches nothing is false.
`language_unlisted` takes no field and is true while an account or the box default names a language
that `web/locale/languages.json` does not list. It reads the list and not the catalog directories: the
overlay has already replaced the list when the plan is derived, while a dropped catalog is still on
disk until its own entry deletes it.

Everything in a manifest is English: field names, type names and the description text, like the
rest of this tree.

New need means a new building block, never a shell snippet in the JSON.

## A published file is frozen

Once a version's manifest has gone out in a release, its entries do not change. The identity
`version/id` is a promise about what ran on a box, and editing a published entry's condition or
action makes two boxes disagree about what "the same" entry did. A correction is a **new entry in the
next version's manifest**, with its own id, whose condition describes the state the earlier entry left
behind.

The file may still be edited while the release carrying it has not reached an installation outside
this project - that window is a courtesy, not the rule, and it closes the moment somebody else runs
it.

## Expiry

A file whose version is at or below the update lower bound can never apply again, because no box
below that bound is accepted. **It is not read**: the discovery skips it, so it cannot reach a plan
and cannot be compared against a vocabulary that has moved on since. The bound itself is read from
`update.sh`, which travels in the tarball, so the tree being derived from is the one that decides.

That is also why such a file may simply stay. If one is removed all the same, it is removed **with
an entry that removes it on a box as well**: the overlay of an update never deletes, and the
discovery is a glob over the directory, so a file dropped from the tree survives on every installed
box. Deleting it in the tree alone changes nothing out there.

Being unread means being unchecked, too: the soundness check no longer looks at such a file. It is
inert either way, but nothing will tell you it has rotted.
