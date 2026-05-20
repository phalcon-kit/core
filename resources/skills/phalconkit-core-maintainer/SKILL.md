---
name: phalconkit-core-maintainer
description: Use when modifying, reviewing, documenting, or testing the phalcon-kit/core package itself, including source providers, bootstrap config, generated docs, Composer scripts, package resources, and reusable PhalconKit skills.
---

# PhalconKit Core Maintainer

Use this skill when working inside the `phalcon-kit/core` package.

## Repository Map

- Runtime source: `src/`
- Tests: `tests/Unit/`
- Migrations and package resources: `resources/`
- Reusable AI skills: `resources/skills/`
- Generated API docs: `docs/classes/`, `docs/functions/`,
  `docs/mkdocs_menu.yml`
- Human docs and repo guidance: root markdown files such as `README.md`,
  `AI.md`, and `AGENTS.md`

## Core Pattern Reference

Read `references/core-conventions.md` when changing framework behavior,
reviewing a contribution, or documenting how users should build on PhalconKit.

Read `references/skill-coverage.md` when improving the reusable skills,
README, `AI.md`, or other AI-facing documentation.

## Working Rules

1. Start with `git status --short` and preserve unrelated dirty files.
2. Use `rg` or `rg --files` to find existing patterns.
3. Keep runtime changes scoped to the requested behavior.
4. Match existing namespace, provider, bootstrap config, module, model, and test
   conventions.
5. Do not hand-edit generated API docs unless the task explicitly asks for a
   generated-doc patch. Prefer `composer docs` when regeneration is intended.
6. Documentation-only changes should not modify PHP runtime files.

## Common Validation

- `composer phpunit` runs unit tests.
- `composer psalm` runs Psalm with taint analysis.
- `composer phpcs` checks coding standards.
- `composer skeleton` validates package skeleton rules.
- `git diff --check` catches whitespace errors.

Run the smallest relevant set. Do not run fixers or generators that rewrite
tracked files unless the user asked for that change.

## Skill Maintenance

When adding or changing a reusable skill:

- Put it in `resources/skills/<skill-name>/`.
- Use lowercase hyphenated skill names.
- Include exactly one `SKILL.md`.
- Keep frontmatter to `name` and `description` unless another runtime requires
  more fields.
- Keep instructions concise and operational.
- Do not include secrets, customer data, large generated files, or broad
  tutorials in the skill bundle.
