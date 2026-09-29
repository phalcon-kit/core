# PhalconKit Core Agent Guide

This file guides AI agents working inside `phalcon-kit/core`. Keep changes small,
verify them with the local Composer scripts, and do not rewrite generated output
unless the task explicitly asks for regenerated docs.

## Repository Shape

- This is a PHP 8.5 library package for Phalcon 5.22.x, published as
  `phalcon-kit/core`.
- Runtime code lives in `src/` under the `PhalconKit\` namespace.
- Unit tests live in `tests/Unit/`.
- Package resources live in `resources/`; reusable agent skills are stored in
  `resources/skills/`.
- `docs/classes/`, `docs/functions/`, and `docs/mkdocs_menu.yml` are generated
  API documentation from phpDocumentor. Do not hand-edit generated API files for
  normal documentation changes.

## Working Rules

- Start by checking `git status --short` and keep unrelated dirty files intact.
- Use `rg` or `rg --files` for search.
- Follow existing provider, module, config, and test patterns before adding a
  new abstraction.
- Prefer focused edits over broad formatting or cleanup.
- Add or update tests when runtime behavior changes. Documentation-only changes
  normally do not need PHP tests.
- When adding or changing public/protected framework-facing classes, methods,
  interfaces, traits, providers, config contracts, or exceptions, add useful
  PHPDoc/docblocks. Document the purpose, important parameters, return value,
  thrown exceptions, DI/config expectations, side effects, and extension notes
  a downstream user would need. Private helpers can stay lightly documented
  when their intent is obvious.
- Update `CHANGELOG.md` under the current unreleased version section when a
  change affects public behavior, compatibility, security posture, tooling, or
  maintainer workflow.
- Keep `ROADMAP.md` focused on active, schedulable release blocks. Move shipped
  outcomes to `CHANGELOG.md` and durable usage notes to the relevant guide or
  shipped skill; do not leave completed historical blocks in the roadmap.
- Keep unresolved design questions in `guides/to-be-discussed.md` until the
  expected behavior, compatibility risk, and validation plan are concrete.
- Keep public package behavior stable unless the task explicitly asks for a
  breaking change.

## User Documentation

- Write README and guides for application developers using PhalconKit. Lead with
  runnable setup, concrete tasks, requests, and expected results.
- Describe current behavior; keep release/upgrade history out of the main user
  journey. Keep upgrade instructions in `guides/migrations/`, using the shared
  Applies To / Before You Start / Changes To Apply / Verify / Rollback / Related
  Guides structure. Support boundaries belong in support/security policies.
- Verify actual parameter names, response envelopes, field policies, permissions,
  and database effects before presenting an example as runnable.
- Use only neutral synthetic examples. Never copy private project identifiers,
  organization names, domains, credentials, schemas, or proprietary workflows.
- Treat `guides/` as canonical narrative content and synchronize it into the
  Docs repository with working links/navigation; align the App README.

## Validation

Use the smallest relevant checks for the change:

- `composer phpunit` for unit tests.
- `composer psalm` for Psalm static analysis.
- `composer psalm:taint` for Psalm taint analysis.
- `composer phpcs` for coding-standard checks.
- `composer skeleton` for package skeleton validation.
- `composer docs` only when intentionally regenerating phpDocumentor output.

For markdown-only agent documentation changes, run `git diff --check` and a
targeted path/reference search. Avoid running tools that rewrite tracked files
unless the user requested that cleanup.

## AI And Skills

- Skills shipped by this package belong under `resources/skills/<skill-name>/`.
- Each skill folder must contain exactly one `SKILL.md` with valid YAML
  frontmatter containing `name` and `description`.
- Skill names use lowercase letters, digits, and hyphens.
- Keep `SKILL.md` concise. Put only operational instructions an agent needs
  while doing the work.
- Put detailed framework usage recipes in `references/` and link them from the
  skill body so agents load them only when needed.
- Treat skills as privileged instructions. Review skills before mounting them
  in local or hosted agent runtimes, and require explicit approval for write,
  network, deployment, or destructive actions.
