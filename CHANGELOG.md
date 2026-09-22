# Changelog

All notable changes to the starter kit are documented here. It is versioned
alongside `k2labs/base-tenant`.

## [3.0.3] - 2026-09-22

Requires `k2labs/base-tenant` ^3.0.3.

### Fixed

- `database/seeders/DatabaseSeeder.php` named the package's seeders outright,
  so `php artisan db:seed` was a fatal in any project that had run
  `k2labs-base:eject`: the classes had moved into the project's own namespace.
  It resolves either, which means a freshly migrated database still leaves you
  with something to sign in with, before and after taking ownership of the code.

## [3.0.2] - 2026-09-21

Requires `k2labs/base-tenant` ^3.0.2.

### Fixed

- `composer.json` shipped a `path` repository pointing at
  `~/Projects/base-tenant`. The kit is installed as the root package, so
  Composer honours that block and every install without that directory on disk
  failed with *the `url` supplied for the path repository does not exist*.
  Projects created from the kit now resolve `k2labs/base-tenant` from Packagist,
  and working against a local checkout is set up in the generated project
  instead — see [Creating a project from a local checkout](README.md#creating-a-project-from-a-local-checkout).
- `minimum-stability` is `stable`: a new project should not prefer development
  versions of its dependencies.
- A new project had no `database/database.sqlite`, so `kit:install` stopped on
  *Could not connect: Database file at path [database/database.sqlite] does not
  exist* before it could set anything up. `post-create-project-cmd` creates the
  file, as Laravel's own skeleton does.
- `composer.lock` was ignored only through `.git/info/exclude`, which is local
  to one clone. It is in `.gitignore` now: a lock committed here would be
  mirrored into every project created from a checkout, pinning
  `k2labs/base-tenant` to the absolute path of whoever generated it.
- `database/migrations/` ships again (empty, with a `.gitkeep`), so a new
  project has the directory Laravel expects.
- The README still documented the pre-Packagist install, with a local clone of
  both repositories as the only way in. `laravel new --using=k2labs/starter-kit`
  and `composer create-project k2labs/starter-kit` are what it documents now.

## [3.0.1] - 2026-09-20

Requires `k2labs/base-tenant` ^3.0.1.

> Broken: both 3.0.0 and 3.0.1 carry the `path` repository described above, so
> `composer install` fails in a project created from them unless
> `~/Projects/base-tenant` exists. Removing the `repositories` block from the
> project's `composer.json` fixes an install already made.

## [3.0.0] - 2026-09-19

First release, published on Packagist.
