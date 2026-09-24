# Historical Core Schema

`1.0.0/` is the historical complete Core database schema. It is preserved
unchanged for migration history and maintainer regression checks. It includes
legacy catalog/CMS tables removed from the Core 4.x PHP runtime, and its foreign
keys explicitly reference the `phalcon_kit` database.

This directory is not yet a minimal or portable Core 4.0 fresh-install recipe.
The maintainer `bin/migration-*.sh` scripts still target it. Do not use those
scripts to remove application tables when upgrading Core.

Applications own their existing migration history and data. See
[Upgrading To Core 4.0](../../guides/upgrading-4.0.md#existing-data-and-migration-history)
for the current boundaries and remaining release gates.
