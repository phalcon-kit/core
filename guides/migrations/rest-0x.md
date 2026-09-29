# Migrate 0.x REST Resources

## Applies To

| Starting point | Target | Scope |
| --- | --- | --- |
| 0.x REST controllers using getter-based policies and older generated models | The initializer-based REST API used by current Core | Model generation, controller policy, permissions, query behavior, actions, and client responses |

The REST restructuring began in 1.x. This guide targets the current API rather
than asking you to install an intermediate release. Complete the
[package migration](from-zemit.md) and [Core 4 review](core-4.md) when applicable.

## Before You Start

Complete the [shared preparation](README.md#shared-preparation). Select one
resource and record its list/detail requests, filters, nested payloads, response
keys, error statuses, permissions, and custom actions. Include at least two
identities with different row access.

Inventory controller overrides and generated model customizations. Decide
whether clients will switch at the same time or need an application-owned
compatibility route/response adapter. Preserve existing business rules and data;
changing the framework API does not imply changing your domain model.

## Changes To Apply

### 1. Update Application Wiring

Use current `PhalconKit\` bootstrap, config, modules, providers, and DI contracts.
The application API base controller extends
`PhalconKit\Modules\Api\Controller`. Composer autoloading and module namespaces
resolve application controllers/tasks; do not depend on removed module-local
loaders. See [Application Integration](../application-integration.md).

Keep shared authentication, tenant scope, and response adapters in the
application base controller or a dedicated service. Resource-specific workflows
belong in the resource controller/model/service.

### 2. Generate Schema Layers And Preserve Business Logic

| Existing pattern | Current pattern |
| --- | --- |
| DevTools-generated concrete models with schema and business code mixed together | Generated abstracts/interfaces/enums plus application-owned concrete classes |
| Duplicated relationship definitions | Generated `addDefaultRelationships()` plus intentional app relations |
| Copied schema validation | `genericValidation()` and `addDefaultValidations()` plus app rules |
| Public-property assumptions | Typed/protected generated properties with model accessors and column maps |

Generate into a reviewable checkout against the intended schema. Follow
[Scaffolding](../database-scaffolding.md) for generation/regeneration commands.
Move existing hooks and business methods deliberately; never overwrite them
with generated concrete classes.

Inspect generated relationship aliases, foreign keys, interfaces, enums, nullable
values, and accessor signatures. Keep the concrete model's generated
relationship/validation calls, then compose application hooks with them.
Preserve identity relation aliases when mapping an application User model.

### 3. Replace Policy Getters With Initializers

| Old override/pattern | Current extension point |
| --- | --- |
| `getWhiteList()` | `initializeSaveFields()` / `setSaveFields()` |
| `getSearchWhiteList()` | `initializeSearchFields()` / `setSearchFields()` |
| `getFilterWhiteList()` | `initializeFilterFields()` / `setFilterFields()` |
| `getExpose()` | `initializeExposeFields()` / `setExposeFields()` or an application transformer |
| `getWith()` / `getListWith()` | `initializeWith()` / `setWith()`; select list/detail graph deliberately |
| Indexed `getJoins()` arrays | `initializeJoins()` with stable keyed aliases |
| `getDynamicJoins()` | `initializeDynamicJoins()` / `setDynamicJoins()` |
| `getFind()` SQL mutation | Focused condition/order/group/bind initializers |
| `getListAction()` limit overrides | `initializeLimit()` or `defaultLimit()` and `maxLimit` |
| Controller role arrays | Permission roles/features with explicit component grants |
| Raw permission SQL plus global bind mutation | Named permission-condition blocks with their own bound values |

Before:

```php
public function getWhiteList()
{
    return ['label', 'status'];
}
```

After, in your application controller:

```php
public function initializeSaveFields(): void
{
    $this->setSaveFields(['label', 'status']);
}

public function initializeExposeFields(): void
{
    $this->setExposeFields([false, 'id', 'label', 'status']);
}
```

Keep save, expose, filter, search, and order policies separate. A field's presence
in the schema does not authorize clients to write or query it. Closed exposure
starts with `false`; a plain field list alone does not hide every other field.

Policy initialization must happen before query assembly. Use focused hooks
instead of changing prepared state after `parent::initialize()`. If overriding
framework setters/merge helpers, match their current signatures, including
`array|Phalcon\Support\Collection|null` where accepted. Prefer overriding the
initializer instead of replacing a setter contract.

### 4. Migrate Eager Loading, Joins, And Row Scope

Configure relation paths once, then use `find-with` or `find-first-with` to load
them. Update client and policy aliases from the generated model. Exposed cached
relation keys are lowercase (`tasklist`); relation definitions/selectors retain
names such as `TaskList`. Limit child fields separately from root fields.

Review every related filter with the current [relationship semantics](../rest-relationships.md):

- Related text predicates use correlated existence checks; negative text means
  no qualifying child matches the positive predicate.
- Same-scope AND predicates can require the same child to match all predicates.
- Separate bracketed scopes express independently matching children.
- Root row scope does not automatically authorize every loaded child or joined row.

Move identity/tenant rules into named, bound permission conditions. Use the
[tenant-scope example](../identity-and-permissions.md#restrict-rows) as the shape;
provide your application's own authorized tenant service. Never copy a condition
remover without an equivalent row restriction where one is required.

Controller behavior events can customize a focused phase such as
`rest:afterInitializeConditions`, `rest:afterInitializeOrder`, or
`rest:afterInitializeFind`. Attach a behavior through the relevant permission
feature and verify when it runs. Historical `SkipIdentityCondition` and
`SkipSoftDeleteCondition` uses require review against the explicit
`RemoveDefaultPermissionCondition` and `RemoveDefaultSoftDeleteCondition`
behaviors; these remove restrictions, so they are not a mechanical rename.

For application-calculated sort fields, use a server-owned expression or a
bounded service that calculates, orders, pages, and reloads IDs consistently.
Sorting only an already-fetched page does not sort the full matching set.

### 5. Rebuild Explicit Permissions

Use a `components` map for controller and model grants in each feature/role:

```php
'features' => [
    'readProjects' => [
        'components' => [
            \App\Modules\Api\Controllers\ProjectController::class => [
                'find', 'find-first', 'count',
            ],
            \App\Models\Project::class => ['find', 'count'],
        ],
    ],
],
'roles' => [
    'project-reader' => ['features' => ['readProjects']],
],
```

This is a fragment under `permissions`. Preserve existing role memberships and
verify the resulting access. Grant writes, exports, child model operations,
and custom actions separately. Sum and average require their own model grants.
Keep dash-case controller action names aligned with routes.

Do not turn an old controller-wide wildcard into a new wildcard without reviewing
all newly available actions. Test anonymous access and cross-user/tenant denials.

### 6. Adapt Routes And Client Responses

Choose the target by the old endpoint's actual intent:

| Old intent | Current action | Expected data location |
| --- | --- | --- |
| List without relations | `find` | `view.data` list |
| List with relations | `find-with` | `view.data` list |
| One record without relations | `find-first` | `view.data` object |
| One record with relations | `find-first-with` | `view.data` object |
| Create only | `create` | `view.saved`, `view.mode`, `view.data`; 201 |
| Update existing only | `update` | Same fields; 200; absent/hidden target is 404 |
| Create-or-update | `save` | Same fields; unmatched identity may create |
| Delete/restore | `delete` / `restore` | Operation flag, exposed `data`, messages |
| Application workflow | Application action/service | Explicit application contract |

Some old applications used `get-all` for a relation-bearing list. Current Core's
compatibility `get-all` delegates to plain `find`; do not assume an old URL keeps
its old graph automatically. Review the complete
[current route table](../rest-api.md#routes-and-actions).

The response envelope is `timestamp`, `status`, `code`, `response`, and `view`.
Update clients expecting `view.single` or `view.list`. A client compatibility
adapter can temporarily read `view.data ?? view.single ?? view.list`; constrain
that adapter to the affected resource/version and remove it once callers switch.

If a server adapter is necessary, keep it in an application compatibility layer,
apply it only to JSON resource responses, and cover list/detail/write/batch/error
cases. Do not rewrite raw export attachments. Preserve HTTP status semantics
instead of mapping every response to 200.

List totals are opt-in with `count=1` or named selectors. Review joined/grouped
counts against [Aggregates](../rest-aggregates.md); current single-primary-key
joined counts already infer a distinct root key unless a count column is set.
Do not retain a duplicate old count override without a test proving it is needed.

### 7. Port Save Hooks And Custom Workflows

Review nested save allowlists and relation aliases. Keep identity-derived owner,
tenant, and administrative values server-owned. Configure child ownership for
every model instance and adapt relation input errors as shown in
[Nested Writes](../rest-relationships.md#write-children).

Move import, duplication, status-transition, and multi-model operations into
application services with explicit transaction and retry boundaries. Use generic
REST batches only when partial success is acceptable. Custom actions that build
REST responses return `Phalcon\Http\ResponseInterface`; array task results belong
to the CLI formatter, not an implicit HTTP action contract.

Review `rest:beforeAssign`, `rest:beforeSave`, and `rest:afterSave` event arguments
against the installed source. Preserve model validation for writes performed
outside HTTP. Recheck inherited override signatures instead of copying an old
hook unchanged.

## Verify

For each resource, run the [scenario checklist](../rest-scenarios.md) against the
before/after fixtures. At minimum verify list/detail shape, relation aliases,
filters, page totals, permitted and denied writes, cross-parent nested IDs,
validation messages, mixed batches, and one custom workflow.

Verify persisted rows and ownership, not just the status code. Repeat with
anonymous, normal, and elevated identities. If an adapter remains, run the old
client's contract tests through it as well as the new API tests.

## Rollback

Preserve the old application artifact, generated/concrete model code, lockfile,
and matching clients. Deploy a coordinated rollback if request or response
contracts changed. Keep additive compatibility routes until their callers have
switched. Any schema/data conversion needs a separate recovery plan; regenerating
models does not reverse data changes.

## Related Guides

- [Package migration](from-zemit.md)
- [Core 4 migration](core-4.md)
- [Scaffolding](../database-scaffolding.md)
- [REST handbook](../rest-api.md)
- [Permissions and row scope](../identity-and-permissions.md)
- [Application tests](../application-testing.md)
