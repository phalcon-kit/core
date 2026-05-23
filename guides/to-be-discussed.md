# To Be Discussed

This file tracks design questions that are worth revisiting, but should not
change public behavior until there is a concrete application need, migration
plan, and test coverage.

## REST Grouped Count Totals

Status: Open

Area: REST controllers, aggregate query responses

Context:

- `count()` can return either a scalar total or a grouped count result when a
  `group` clause is present.
- A grouped count result is useful for bucket counts, but callers sometimes
  also want a total count in the same response.
- Summing grouped buckets is not always the same as counting unique root
  records, especially when grouping by joined relations where one root record
  can appear in multiple buckets.

Current stance:

- Do not change `count()` to return a mixed structured payload such as
  `['groups' => ..., 'total' => ...]`; that would break callers and blur native
  Phalcon return semantics.
- Do not automatically sum grouped counts and label that value as a total.
- If this is added later, prefer explicit names and opt-in behavior.

Possible future shape:

- `groupedCount`: grouped count result.
- `totalCount`: separate ungrouped count query using the same filters.
- `bucketTotal`: sum of grouped buckets, explicitly named so it is not confused
  with a unique root-record total.

Discussion triggers:

- A real application needs both grouped bucket counts and total counts in one
  response.
- Desired behavior is clear for joined-group cases.
- The performance cost of an extra count query is acceptable or configurable.

## Stateless Session Option

Status: Open

Area: session provider, identity, API-only applications

Context:

- Some REST/API applications authenticate with JWTs, bearer tokens, signed
  requests, or another stateless mechanism and do not want PHP session cookies,
  session files, or server-side session state.
- The current session provider always builds a Phalcon session manager and
  starts it, defaulting to the stream adapter when no adapter is configured.
- Phalcon's `Noop` adapter is useful, but a true stateless mode may also need
  to skip `start()`, avoid `Set-Cookie` headers, and define how session reads
  and writes behave.
- Existing consumers can rely on sessions for identity fallback, locale
  persistence, flash messages, OAuth2 state, CSRF/security behavior, session
  bags, and impersonation flows.

Current stance:

- Do not silently make `Noop` the default or disable sessions globally; that
  would break existing session-backed applications.
- Do not treat "stateless" as only a storage-adapter choice until cookie,
  startup, and session-dependent service behavior are defined.
- If added later, it should be an explicit opt-in configuration with clear
  migration notes for services that require session state.

Possible future shape:

- `session.stateless: true` or `session.enabled: false` as an explicit config
  flag.
- A no-state session registration strategy, such as a `Noop` manager that does
  not start, a null session service compatible with the expected interface, or
  a clear exception when session-dependent code is used.
- Recommended stateless API config, for example JWT identity without
  `identity.sessionFallback`, locale mode without session persistence, and an
  OAuth2 state strategy that does not depend on PHP sessions.
- Tests proving no session files are written, no session cookie is emitted, and
  known session consumers fail clearly or use documented fallbacks.

Discussion triggers:

- A real API application needs to run without server-side session state.
- Desired behavior is clear for OAuth2 state, flash messages, locale,
  session bags, impersonation, and security/CSRF helpers.
- Security review accepts the logout, replay, token revocation, and state
  validation tradeoffs for the stateless path.

## Entry Template

- Status:
- Area:
- Context:
- Current stance:
- Possible future shape:
- Discussion triggers:
