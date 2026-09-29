# Build A Resource From Start To Finish

Follow the [complete Project tutorial](first-rest-resource.md) for runnable code.
This map shows where to make changes as the resource grows.

| Requirement | Application change | Verify |
| --- | --- | --- |
| Store a new field | Migration, then generated abstract/interface | Save/reload type, default, nullability |
| Show the field | Controller expose policy | Field appears only for allowed callers |
| Edit the field | Save policy and model validation | Valid input persists; invalid input does not |
| Filter or sort it | Filter/order policies | Exact matching IDs and deterministic page order |
| Add a child list | Foreign key, scaffold relationships, with/expose policies | No extra fields or cross-tenant children |
| Save children | Nested save policy and ownership rules | Correct links, no cross-parent adoption |
| Restrict records | Permission conditions | Detail/list/count/export all enforce the same scope |
| Add a state transition | Concrete model or application service | Preconditions, transaction, failure message |
| Notify clients | Application event after committed write | Authorized recipients, retries, reconnect recovery |

Keep business methods in concrete models or services, generated schema structure
in abstracts, and request/response policy in controllers.

Continue with [Scaffolding](database-scaffolding.md),
[REST Controllers](rest-api.md), [Relationships](rest-relationships.md), and
[Application Testing](application-testing.md).
