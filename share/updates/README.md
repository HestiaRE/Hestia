# share/updates

One JSON file per release, read by `h-list-sys-updates` and, during a run, by the executor. The file
says what a box coming from an older version has to catch up on. Nothing in here is shell: conditions
and actions are named building blocks from `include/update.sh`, reviewed like any other code.

## File

```json
{
  "version": "0.19.0",
  "eintraege": [ ... ]
}
```

The file name is the version with `.json` appended and **without** the `v` (`0.19.0.json`); name and
`version` field must agree, and a guard checks it. Files are merged and sorted over the whole set,
never per file, and by version, never alphabetically (`0.10` after `0.9` is the trap).

## Entry

```json
{
  "id": "redis-key",
  "beschreibung": "Redis hatte bisher keinen Schluessel; Zustand aus dem Artefakt nachtragen",
  "bedingungen": [
    { "typ": "key_empty", "name": "REDIS_SYSTEM" },
    { "typ": "package_installed", "name": "redis-server" }
  ],
  "aktion": { "typ": "key_set", "name": "REDIS_SYSTEM", "wert": "redis" },
  "pfade": ["/etc/hestia/conf/hestia.conf"],
  "umkehrbar": true
}
```

The full identity is `version/id` (`0.19.0/redis-key`). Ids are unique inside a file, are never
reused, and an entry never moves between files: an identity is a promise, not a label.

| Field | |
|---|---|
| `id` | letters, digits, `.`, `_`, `-` |
| `beschreibung` | one line, shown in the list and the log |
| `bedingungen` | all of them must hold, at least one is required |
| `aktion` | exactly one |
| `pfade` | what the action writes, the basis of the per-entry backup |
| `umkehrbar` | `true` only if putting files back undoes it |
| `nach` | optional, an identity this entry must not run before |

## One action per entry

"First A, then B" is two entries and a `nach`, never one entry with two actions. That keeps the
reversibility of an entry the reversibility of its action, with nothing to fold, and it keeps a
mixed pair honest: `file_copy` plus `service_restart` as one entry would be irreversible as a whole
and land behind the point of no return, although the copy could have run in front of it.

`umkehrbar` may be declared **more** pessimistic than the action type allows, never more optimistic.
An entry that has to run behind an irreversible one declares itself `false`, and that is the way to
write a pair whose natural order is irreversible first (`package_remove`, then `key_clear`).

## Order

Reversible entries first, so the point of no return falls as late as it can; within that, `nach`
before version before id. A reversible entry may not wait for an irreversible one: it would sit
behind the line and lose the rollback it claims.

## Three rules that are easy to get wrong

1. **A condition must be false once the action succeeded.** That is what makes a repeat after a
   failed run converge, and the executor warns when an entry does not negate its own condition. An
   action that cannot negate it needs a second condition that does.
2. **The list is derived once, before the run.** An entry whose condition only becomes true because
   another entry ran is not in the list at all, and `nach` will not save it: `nach` orders what is
   already in the run, it does not add anything to it.
3. **A dependency outside the run counts as met.** The entry it names either ran in an earlier update
   or its own condition says it is unnecessary.

Unknown key in a condition: the key names come from `share/hestia/sys-keys.json`, and a name that is
not in it is a typo, not a box fact. A key that is simply empty or absent on the box is the box fact,
and that is what `key_empty` is for.

## Types

Conditions: `key_empty`, `key_is`, `key_has_token`, `path_exists`, `command_exists`,
`package_installed`, `file_differs`. Actions: `key_set`, `key_clear`, `token_add`, `token_remove`,
`file_copy`, `path_delete`, `function_call`, `package_install`, `package_remove`, `service_restart`.

Fields per type: `name` and `wert` for the key types, `name` for a command, package or service,
`pfad` for `path_exists` and `path_delete`, `quelle` (tree-relative) and `ziel` for `file_differs`
and `file_copy` (`modus` optional), `funktion` for `function_call` (only names in `UPDATE_CALLABLE`).

New need means a new building block, never a shell snippet in the JSON.

## Expiry

A file whose version is at or below the update lower bound can never apply again, because no box
below that bound is accepted. It may be deleted, and the release checklist asks the question.
