# Roadmap

This is the active roadmap for the maintained **Core 4.x** line on `master`.
Shipped outcomes belong in [CHANGELOG.md](CHANGELOG.md); application adoption
requirements belong in [Upgrading To Core 4.0](guides/upgrading-4.0.md).
Published releases are preserved by signed tags under the [release policy](guides/release.md).

## Relationship And Resource Contracts

Status: Next documentation and regression batch.

- Document accepted one-to-many and many-to-many payloads, ownership checks,
  transaction boundaries, and eager-loading behavior through small consumer examples.
- Cross-check public/protected PHPDoc against those examples and cover any missing
  behavioral edge cases before changing runtime code.
- Retain deprecated REST aliases until wrapper/SDK consumers have an explicit
  migration and equivalent response-contract tests.

## Scaffold Output And TypeScript Contracts

Status: Follow-up batch after relationship contracts.

- Verify scaffold output ownership, regeneration safety, and generated-interface
  expectations against application-owned models.
- Review TypeScript generation examples and consumer expectations without adding
  a new abstraction or hand-editing generated output.

Open design questions and possible removals remain in
[To Be Discussed](guides/to-be-discussed.md) until behavior, compatibility risk,
and validation are concrete. Further model retirement, framework upgrades,
optional integration packaging, and license-header cleanup are separate work.
