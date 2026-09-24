# Retained Feature Contracts

Core supplies Phalcon extensions and reusable application components. This guide
describes the storage and extension contracts behind the retained features, so an
application can decide which pieces to adopt and which workflows to implement.

The [fresh database baseline](database-migrations.md) installs all 29 retained
tables together, including their foreign-key dependencies. The table groups below
describe feature use; they are not independent migration bundles. Existing
applications own their schemas, migrations, model classes, and data.

## Common Model And Service Contract

Use the normal bootstrap and providers to register `config`, `models`,
`modelsManager`, `modelsMetadata`, the database connection, `modelsCache`,
`security`, and `helper`. Identity-aware operations also need `identity` and its
configured request, JWT, and session services. See
[model initialization](models-and-eager-loading.md#base-model-services-and-initialization)
for manually assembled containers.

Replace a Core model through the application config:

```php
'models' => [
    \PhalconKit\Models\User::class => \App\Models\User::class,
    \PhalconKit\Models\Oauth2::class => \App\Models\Oauth2::class,
    \PhalconKit\Models\Audit::class => \App\Models\Audit::class,
],
```

A replacement extends `PhalconKit\Mvc\Model` and implements the matching
`PhalconKit\Models\Interfaces\*Interface`. It can extend its own generated
abstract; inheriting the concrete Core model is not required. The interfaces
currently include generated accessors and Core model operations, so check the
complete interface when adopting a different schema. A class name mapping alone
does not establish compatibility.

`models->getUser()` and the other typed getters validate the model contract and
cache an instance. Treat that instance as a lookup prototype. For a new record,
create a fresh instance of the resolved class; do not assign data to the shared
prototype:

```php
$userClass = $this->models->getUser()::class;
$user = new $userClass();
```

Set mappings before resolving models and keep them stable for the container's
lifetime. Changing the map does not refresh previously cached instances or
Phalcon's initialized relationship metadata.

Mapping applies where code uses the `models` resolver. It does not rewrite a
direct `CoreModel::findFirst()` call or the class names in generated relationship
definitions. Application-generated relationships should target the application's
concrete models, including intermediate models for many-to-many relations.
Preserve the aliases consumed by Core. Review permissions for the actual
controller and model classes; a mapping is not an access grant.

## Feature Requirements

| Feature | Core storage involved | Application responsibility |
| --- | --- | --- |
| Login, password reset, current identity | `user`; the default current-user lookup also loads role/group/type relations | Compatible user model, identity configuration, reset delivery, registration and account activation rules |
| Role/group/type membership | `role`, `group`, `type`, `user_role`, `user_group`, `user_type`; other association tables when their relations are used | Membership data, named permission features, tenant/row conditions, and grant/revoke workflows |
| OAuth identity linking | `oauth2`, `user` | Provider credentials/state handling, mapped OAuth model, and account-linking policy |
| Database-backed session records | `session`, `user` when the app uses them | A custom identity persistence implementation and credential rotation/revocation policy |
| Stored templates and email records | `template`, `email`; `email_file` and `file` for attachments | Template selection/rendering, recipients, delivery, retries, and recording delivery state |
| File metadata and associations | `file`, `file_relation`; `email_file` for email links | Upload validation, storage paths, ownership, download authorization, and blob cleanup |
| Audit trail | `audit`; `audit_detail` when detail recording is enabled | Opt-in audit configuration, compatible mapped models, and sensitive-field/retention policy |
| Stored settings | `setting` | Key/value interpretation, lookup scope, caching, and authorization |

The `feature`, `user_feature`, `role_feature`, and `group_feature` models provide
stored associations. Their presence does not automatically grant a named feature
to an identity. Core's configured permission features and role inheritance are
defined under `permissions`; applications bridge stored grants into their policy
when needed. The same distinction applies to stored role-to-role associations
and configuration-based role inheritance.

## Identity, Registration, And Reset

The default identity manager resolves the configured user model. Its current-user
lookup eager-loads `RoleList`, `GroupList`, and `TypeList`; preserve these aliases
or override that lookup deliberately. Login checks the user's password hash and
deleted state. Password reset uses the user's `resetToken` storage and write
connection for a single-use transaction, then persists through model hooks.

Registration, invitations, activation, and verification are application workflows.
Core's auth controller does not provide a complete registration endpoint. Create
users through application validation and permission rules, and assign initial
memberships explicitly. No account or role is seeded by the fresh baseline.

Override `sendPasswordResetNotification()` to deliver reset links. The default
hook does not send email. Keep the reset response independent of whether an
account exists, and never return reset tokens in API responses. See
[Identity And Permissions](identity-and-permissions.md) and
[Security Hardening](security-hardening.md).

The default identity payload lives in the configured PHP session service, or in
the JWT claim when `identity.stateless` is enabled. It does not automatically use
the `session` table. Applications implementing database sessions override the
identity storage methods and must clear user/ACL caches when replacing or removing
the payload. They also own equivalent session/token rotation and revocation.

## OAuth Model Substitution

OAuth lookup and creation use the configured `Oauth2` model. Its `findFirst()`
must return an `Oauth2Interface` record or `null` when absent. A cancelled lookup
or incompatible result raises `ServiceException` before account creation. New
records use a fresh model instance, and updates use the returned record.

Normal model validation and save hooks remain active. Model errors are retained
in the result's `messages`; failed persistence does not establish a new identity.
An unlinked record can be stored, but login requires a linked, active local user.
Core does not automatically create a local account from an OAuth profile.

Provider tokens and profile metadata are sensitive application data. The model
stores the supplied token values; application encryption and response exposure
policies must be configured separately. Retain sufficient token storage width
when mapping to an existing application table.

## Templates, Email, Files, And Settings

These Core models provide schema accessors, validation, relationships, and generic
REST extension points. Saving an `Email` record does not send mail; saving a
`File` record does not upload a blob; saving a `Setting` does not update the DI
configuration. The `mailer` transport and filesystem services are separate from
these database records.

Applications implement the workflow joining those pieces. For example, select
and render a template, send through the configured mailer, then record the result
using the application's email model. Define transaction/retry boundaries around
external side effects: rolling back SQL cannot unsend mail or remove an uploaded
object automatically.

Core's `TemplateController` removes the default soft-delete query condition.
If an application must hide deleted templates, explicitly restore that policy
in its controller. Restrict fields and actions before exposing any of these
generic controllers publicly; see [REST APIs](rest-api.md).

## Audit, Nested Writes, And Ownership

Audit recording is opt-in through the model's `blameable.auditEnabled` option.
The default blameable initialization resolves mapped user, audit, and audit-detail
classes; explicit behavior class options take precedence. Detail recording can
be disabled independently. Install the corresponding tables before enabling it.

Use the [relationship assignment contract](models-and-eager-loading.md) for
nested payloads, allowed relations, ownership checks, and transaction behavior.
Model mappings do not validate that a related ID belongs to the current tenant.
Keep tenant/ownership conditions, writable fields, and response exposure rules in
the application policy and cover both allowed and denied paths.

For consumer acceptance, exercise mapped models through real feature calls,
not only `get*Class()` assertions: login/reset and session renewal, OAuth
lookup/create/failure, permission filters, nested writes/eager loading, audit,
and the application's file/email workflow. Use disposable schemas and synthetic
credentials; preserve existing migration history.
