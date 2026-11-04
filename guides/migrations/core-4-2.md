# Core 4.2 Migration

## Applies To

Applications on Core 4.0 or 4.1 moving to `phalcon-kit/core` `^4.2`. Core 4.2
changes the HTTP status of one kind of permission denial and adds scaffold
options; nothing is removed.

## Before You Start

- Record how each client reacts to 401 and 403 responses: which ones sign the
  user out, retry with a refreshed token, or show a "not allowed" message.
- Note whether your config overrides `router.unauthorized` or
  `router.forbidden`.
- Note whether you edited any generated `src/Models/Interfaces/*Interface.php`
  file, and whether you run `ts-scaffold`.

## Changes To Apply

### Denied Requests From Signed-In Users Return 403

A request the permissions deny now goes to `router.forbidden` (HTTP 403 by
default) when a user is signed in, and to `router.unauthorized` (HTTP 401) when
no user is signed in. Before, every denied HTTP request returned 401, because
the choice depended on the number of ACL roles and every request has at least
two.

Update clients that treat every 401 as "sign in again": a 403 now means the
user is signed in but lacks the permission, so keep the session and show a
"not allowed" message. A 401 still means the client must authenticate or
refresh its token.

If you override `router.forbidden`, make sure that route exists in every
module that receives denied requests; it is now used for signed-in users.

CLI tasks are unchanged: a CLI run has no signed-in user, so a denied task
still reports `Error: 401 - Unauthorized`.

### Keep Edited Model Interfaces During Regeneration

`scaffold run --force` rewrites `src/Models/Interfaces/*Interface.php` unless
the new `--no-model-interfaces` option is set. Add it to your regeneration
script next to `--no-models`, as the App 4.2 `scripts/regenerate-models.sh` and
`.ps1` do:

```shell
./bin/phalcon-kit cli scaffold run --force --no-models --no-model-interfaces ...
```

### Point TypeScript Generation At Your Models

`ts-scaffold` now writes into `--directory` (default `./sdk/`) and reads
relationships from `--namespace` (default `PhalconKit`). For your own tables,
pass your root namespace:

```shell
./bin/phalcon-kit cli ts-scaffold run --namespace=App --directory=./sdk/
```

## Verify

- A guest calling an action it may not run receives 401; a signed-in user
  calling an action their roles do not grant receives 403. Check both against
  the client's behavior.
- Edit a model interface, run your regeneration script, and confirm the edit
  survives while the abstract interface is refreshed.
- `composer show phalcon-kit/core` reports 4.2.x.

## Rollback

Require `phalcon-kit/core` `~4.1.4` and run `composer update phalcon-kit/core`.
Remove `--no-model-interfaces` from your regeneration script and `--namespace`
from `ts-scaffold` calls first: Core 4.1 rejects options it does not know.
Clients changed for the 403 status keep working, because they still handle 401.

## Related Guides

- [Identity And Permissions](../identity-and-permissions.md)
- [Requests And Responses](../rest-requests-and-responses.md)
- [Database And Scaffolding](../database-scaffolding.md)
- [CLI Commands](../reference/cli.md)
