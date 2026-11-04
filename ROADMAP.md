# Roadmap

This is the active roadmap for the maintained **Core 4.x** line on `master`.
Shipped outcomes belong in [CHANGELOG.md](CHANGELOG.md); application usage
belongs in the [user guide](guides/README.md).
Published releases are preserved by signed tags under the [release policy](guides/release.md).

## Scaffold Output And TypeScript Contracts

Status: Next batch.

- Verify scaffold output ownership, regeneration safety, and generated-interface
  expectations against application-owned models.
- Review TypeScript generation examples and consumer expectations without adding
  a new abstraction or hand-editing generated output.

Open design questions and possible removals remain in
[To Be Discussed](guides/to-be-discussed.md) until behavior, compatibility risk,
and validation are concrete. Further model retirement, framework upgrades,
optional integration packaging, and license-header cleanup are separate work.
