# Base Tenant Starter Kit

A Laravel starter kit for multi-tenant B2B SaaS. Accounts, per-account
permissions, database-driven navigation, feature flags, invitations, an audit
trail, Stripe billing and fifteen capability modules — custom domains,
per-tenant security policies, passwordless sign-in, files, metering, webhooks and
more — on the first commit, before any of your code.

```bash
# Once the kit is published:
laravel new my-app --using=k2labs/starter-kit

# Today, from a local checkout, anywhere on disk — see Installation below:
composer create-project k2labs/starter-kit my-app \
  --repository='{"type":"path","url":"~/Projects/base-tenant-kit","options":{"symlink":false}}' \
  --stability=dev --remove-vcs --no-install --no-scripts
```

> **Proprietary,** and the distribution channel is not settled yet, so the first
> command does not work yet. The second one does.

**Requires** PHP 8.4 · Laravel 13 · Livewire 4 · Flux UI 2.4 (free tier is
enough) · MySQL, PostgreSQL, MariaDB or SQLite

## Contents

- [What you get](#what-you-get)
- [Installation](#installation)
- [After installing](#after-installing)
- [Building on it](#building-on-it)
- [Security](#security)
- [Running it in production](#running-it-in-production)
- [Testing](#testing)
- [Project structure](#project-structure)
- [Taking ownership later](#taking-ownership-later)
- [Creating a project from a local checkout](#creating-a-project-from-a-local-checkout)
- [Documentation](#documentation)
- [Licence](#licence)

## What you get

Everything below is on the first commit, tested and documented.

### The core

| | |
|---|---|
| **Tenancy** | One active account per request, resolved from domain, API token, session or user, and carried into queued jobs, cache keys and broadcast channels |
| **Isolation** | `BelongsToAccount` scopes a model to the account and stamps new records, so a forgotten `where` cannot leak another customer's data |
| **Permissions** | spatie/laravel-permission with `account_id` as the team key. Roles resolve per account, from the database, in requests, jobs, commands and API calls alike |
| **Per-account roles** | A shared catalogue plus roles each tenant defines for itself, edited through a role × permission matrix |
| **Navigation** | Declared in code, stored in the database, reorderable and hideable per account, filtered by permission and feature flag |
| **Feature flags** | Plan features with per-account overrides and expiry |
| **Typed settings** | Schema classes with declared defaults, rendered as a form from the property types |
| **Auth** | Login, registration, password reset, email verification, 2FA with recovery codes, audited impersonation, forced password change |
| **Billing** | Laravel Cashier with plan-based feature gates |
| **Activity** | Account-scoped audit trail with sensitive-field filtering |
| **Notifications** | Database notifications with a bell, polling and a daily digest |
| **Test kit** | `assertTenantIsolated`, `assertJobCarriesTenant`, `assertPermissionIsAccountScoped` |

### The modules

Each one has a switch. A product that wants no files and no webhooks does not
carry their tables.

| Module | What it gives you |
|---|---|
| **Domains** | A subdomain per customer, and domains of their own served only once a TXT record proves they control them |
| **Security policies** | Per account: enforced 2FA with a grace period, allowed email domains, an IP allowlist with a warn-first mode, session timeout |
| **Active sessions** | Where each person is signed in, with remote revocation that works on any session driver |
| **Magic links** | Single-use, short-lived sign-in links that never skip the second factor |
| **Passkeys** | WebAuthn sign-in and registration from the login screen and the profile |
| **Usage metering** | Counters and gauges per account, plan limits enforced under a lock, 402 with an upgrade call to action, hourly reporting to Stripe Billing Meters |
| **Files** | Direct-to-S3 uploads that never pass through PHP, collections with type and size rules, image variants, per-account quota, a media library |
| **Imports and exports** | CSV in and out, automatic column mapping, chunked queued jobs, rejected rows returned as a file to correct and re-upload |
| **Module generator** | `k2labs-base:make-module` writes a whole CRUD vertical: model, migration, factory, policy, screens, both locales, tests and an agent doc |
| **Connections** | A customer's credentials for third parties, encrypted, with nightly health checks |
| **Outbound webhooks** | Signed deliveries with retries and automatic disabling of dead endpoints |
| **Languages** | Locales enabled and disabled at runtime, without a deploy |
| **Social login** | Google, LinkedIn and Microsoft Entra ID, with an anti-takeover rule |
| **Sequences** | Correlative numbering per account, locked, with period resets and formats |
| **Onboarding** | A declarative setup checklist that disappears when it is done |
| **Email suppressions** | A global send guard fed by signed provider webhooks |
| **GDPR** | Personal data export, erasure across every table that holds personal data, scheduled purge, versioned terms acceptance |
| **Pre-sale** | Closed registration, landing page, waiting list, founding seats |

Every management screen is on one table pattern: search, sort, density and page
size in the URL, sticky headers, designed empty states, loading skeletons, dark
mode throughout.

Stack: Laravel 13, Livewire 4, Flux UI (free tier), Tailwind 4, Pest.

## Installation

### Creating the project from a local checkout with Composer

Until the kit is published, a new project is created with Composer straight from
the local checkouts. It needs both repositories on disk:

```bash
git clone git@github.com:k2labs-tech/base-tenant.git ~/Projects/base-tenant
git clone git@github.com:k2labs-tech/starter-kit.git ~/Projects/base-tenant-kit
```

Then, from any directory:

```bash
# 1. Copy the kit into a new project, without installing anything yet
composer create-project k2labs/starter-kit my-app \
  --repository='{"type":"path","url":"~/Projects/base-tenant-kit","options":{"symlink":false}}' \
  --stability=dev --remove-vcs --no-install --no-scripts

cd my-app

# 2. Drop what Composer copied over from the kit checkout
rm -rf vendor node_modules .env

# 3. Install — k2labs/base-tenant is symlinked from ~/Projects/base-tenant
composer install
cp .env.example .env && php artisan key:generate

# 4. Set up the project
php artisan kit:install
```

Step 2 matters: Composer mirrors the kit directory as it is on disk, so without
it the new project would inherit the kit's `vendor/`, `node_modules/` and `.env`.
Why each flag is there is explained in
[Creating a project from a local checkout](#creating-a-project-from-a-local-checkout).

If the checkouts live somewhere other than `~/Projects`, change the `url` in the
command above and the `k2labs/base-tenant` repository in `composer.json`.

### What `kit:install` asks

However the project is created, it ends by running `php artisan kit:install`,
which asks three things.

#### 1. How the project should relate to the package

**As a dependency** *(recommended)* — the tenancy, permission and navigation code
lives in `k2labs/base-tenant`. You get fixes and features with `composer update`. You can
take ownership later, at any time, without redoing anything.

**As your own code** — everything is copied into the project and the package is
removed. Nothing depends on us, and no updates arrive. This is how Laravel's own
starter kits work, and it is a one-way door.

If you are unsure, choose the dependency. It is the reversible option.

```bash
php artisan kit:install --dependency   # skip the question
php artisan kit:install --own          # skip it the other way
```

#### 2. The database

The installer asks for the connection before anything else: driver (sqlite,
mysql, mariadb or pgsql) and then only what that driver needs — file path for
sqlite, host, port, database, user and password for the rest. It **tests the
connection before writing anything** and asks again if it fails, showing the
driver's own error.

The answers go to `.env`, so you can change them later by hand. To be asked
again on an already-working project:

```bash
php artisan k2labs-base:install --database
```

#### 3. Whether you want Flux UI Pro

Optional. **The kit and the package use only free Flux components**, so Pro buys
you nothing unless you want its own components — charts, date pickers, tables,
editors — for your screens. Answer yes and you will be asked for your license,
and `livewire/flux-pro` is added to `composer.json` for you.

The package installer also asks about multi-team mode, Stripe and a test user.

## After installing

```bash
npm install && npm run build
php artisan serve
```

Sign in at `/login` with `admin@example.com` / `secret123`.

### Rebuilding from scratch

```bash
php artisan migrate:fresh --seed
```

That drops everything, re-runs the migrations and seeds the permission
catalogue, the global roles, the product menus and the administrator — a working
application, not an empty schema. `database/seeders/DatabaseSeeder.php` is where
to add your own.

Change the seeded administrator with `BASE_TENANT_ADMIN_EMAIL` and
`BASE_TENANT_ADMIN_PASSWORD` before running it anywhere real.

## Building on it

Scope your models to the tenant:

```php
use Base\Tenant\Traits\BelongsToAccount;

class Invoice extends Model
{
    use BelongsToAccount;
}
```

Declare your permissions in `config/base-tenant.php`, then run
`php artisan k2labs-base:sync-roles`:

```php
'permissions' => [
    'invoices' => ['invoices.view', 'invoices.create', 'invoices.approve'],
],
```

Add navigation from a service provider, then `php artisan k2labs-base:sync-menus`:

```php
Menu::register('main', function (MenuBuilder $menu): void {
    $menu->item('invoices')
        ->label('app.navigation.invoices')
        ->route('invoices.index')
        ->permission('invoices.view')
        ->badge(fn (): int => Invoice::pending()->count());
});
```

Prove the isolation holds:

```php
$this->assertTenantIsolated(Invoice::class, fn ($account) => Invoice::factory()->create());
```

### Or let the generator do all of it

```bash
php artisan k2labs-base:make-module Invoice \
    --fields="reference:string,total:decimal,due_at:date,notes:text:nullable" \
    --pretend
```

Drop `--pretend` and out comes the model, migration, factory, policy, both
Livewire components, both views, both locales, a feature test whose first case is
tenant isolation, and an agent doc -- with the routes and the permission group
appended to the files that already exist.

What it writes is meant to reach production without being rewritten: the model is
tenant-scoped and logged, sorting is whitelisted, and the policy asks about
permissions rather than ownership.

### Reach for what is already there

```php
use Base\Tenant\Facades\{Meter, Transfer, Sequence, Webhook};

Meter::incrementOrFail('invoices.issued');                        // stops at the plan limit
Sequence::next('invoices', format: 'F{year}-{number:5}', period: 'year');
Transfer::export('invoices');
Webhook::dispatch('invoice.issued', ['id' => $invoice->id]);
```

```blade
<livewire:base-tenant.files.uploader :fileable="$invoice" collection="attachments" />
```

The full list, with the reasoning behind each one, is in
`vendor/k2labs/base-tenant/docs/USAGE.md`.

## Security

Each account sets its own rules at `/security`: enforced two-factor with a grace
period, which email domains may be invited, an IP allowlist (off, warn or
enforce) and an idle session timeout. Every default is the permissive one, so
nothing locks anyone out until an administrator asks for it.

The enforcing middleware is **not applied by default**. Add it to the
authenticated route stack in `config/base-tenant.php`:

```php
'routes' => [
    'auth_middleware' => [
        'web', 'auth', 'verified', 'base-tenant.subscription',
        'base-tenant.track-session',
        'base-tenant.two-factor',
        'base-tenant.ip-allowlist',
        'base-tenant.session-timeout',
    ],
],
```

Put them on route groups like this, never in the global middleware stack:
Livewire replays only route middleware on its update requests.

Details in `vendor/k2labs/base-tenant/docs/agents/16-security.md`.

## Running it in production

The package puts its recurring work on the scheduler itself — domain
re-verification, usage reporting to Stripe, credential health checks, session
and audit pruning, the GDPR purge — so the server needs the usual cron entry:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

And a queue worker, because imports, exports, webhooks and notifications are
queued:

```bash
php artisan queue:work
```

In development, `composer dev` runs the server, the queue listener, the log
viewer and Vite together.

Before the first deploy:

- change `BASE_TENANT_ADMIN_EMAIL` and `BASE_TENANT_ADMIN_PASSWORD`;
- set `BASE_TENANT_CENTRAL_DOMAINS` if you use customer subdomains or domains;
- turn off the modules you do not use with their `BASE_TENANT_*_ENABLED` switch.

## Testing

```bash
composer test
```

The suite runs against an in-memory SQLite database (`phpunit.xml`), never the
development one. It ships with `KitSmokeTest`, which proves a fresh project
works end to end — the package boots, routes and permissions are wired, the
navigation renders from the database and tenant data stays put — and
`RoutingTest`. Keep both green as you build on the kit.

For your own models, the package's `TenancyAssertions` trait gives you
`assertTenantIsolated`, `assertJobCarriesTenant` and
`assertPermissionIsAccountScoped`.

## Project structure

The kit is a standard Laravel 13 application; everything multi-tenant lives in
`vendor/k2labs/base-tenant` until you take ownership of it.

| | |
|---|---|
| `app/Console/Commands/InstallKitCommand.php` | `kit:install` — dependency or own code, database, Flux Pro |
| `app/Models/User.php` | Extends the package's user model |
| `config/base-tenant.php` | Permissions, roles, plans, menus and every module switch |
| `database/seeders/DatabaseSeeder.php` | Seeds the package catalogue and the administrator; add your own here |
| `resources/css/app.css` | Imports Flux and the package theme, with the class-based dark variant |
| `routes/web.php` | Your routes — the package registers its own |
| `tests/Feature/KitSmokeTest.php` | The end-to-end check of a fresh project |
| `docs/WEBSITE-SPEC.md` | Specification of the public website |

## Taking ownership later

```bash
php artisan k2labs-base:scaffold --dry-run   # see exactly what would move
php artisan k2labs-base:scaffold             # ~255 files into app/, resources/, routes/
composer dump-autoload
# run your suite, read the diff, then:
php artisan k2labs-base:eject
```

While scaffolded, the package stands down so nothing is registered twice, and you
can still go back by setting `installation_state` to `installed`. Full details in
`vendor/k2labs/base-tenant/docs/SCAFFOLD-EJECT.md`.

## Creating a project from a local checkout

The kit is not published yet, so `composer create-project` reads it from this
checkout. The kit resolves `k2labs/base-tenant` the same way, from an absolute path:

```json
"repositories": {
    "k2labs/base-tenant": { "type": "path", "url": "~/Projects/base-tenant" }
}
```

Composer expands `~`, so the project can be created **anywhere** — it no longer
has to sit next to the two checkouts:

```bash
composer create-project k2labs/starter-kit my-app \
  --repository='{"type":"path","url":"~/Projects/base-tenant-kit","options":{"symlink":false}}' \
  --stability=dev --remove-vcs --no-install --no-scripts

cd my-app
rm -rf vendor node_modules .env     # copied over from the kit checkout
composer install
cp .env.example .env && php artisan key:generate
php artisan kit:install
```

`--stability=dev` because the kit carries no tags, and `symlink: false` because
the kit is a starting point and you want a real copy. The package stays
symlinked through the block above, so edits to `base-tenant` show up in the
project immediately.

The second step exists because Composer mirrors the kit directory as it is on
disk, ignoring `.gitignore` and `archive.exclude`: without it the new project
inherits the kit's `vendor/`, its `node_modules/` and — worse — its `.env`.
`--no-install --no-scripts` keeps `composer install` and the installer from
running until that is cleaned up.

The kit ships without a `composer.lock`: a lock would pin the absolute path of
whoever generated it. Each project resolves its own on the first
`composer install`, and should commit it.

Before publishing, drop the `repositories` block.

## Documentation

Everything about the package itself is in `vendor/k2labs/base-tenant/docs/`:

| | |
|---|---|
| `USAGE.md` | The manual. Every capability with copyable code |
| `INSTALLATION.md` | Installing, configuring and seeding |
| `agents/00-index.md` | For AI agents: one file per capability, with a capability map |
| `FRONTEND.md` | The theme, dark mode and building assets |
| `SCAFFOLD-EJECT.md` | Taking ownership of the code |
| `UPGRADE.md` | Moving between versions |

If an AI agent is going to work on this project, publish the agent docs into it
and point its `CLAUDE.md` at them:

```bash
php artisan k2labs-base:publish-agent-docs
```

Re-runnable after every `composer update`: the package's section sits between
markers and whatever the project wrote around it is left alone.

## Licence

Source-available, not open source. Projects you create from the kit are yours:
modify them, keep them private, sell what you build. What you may not do is
republish the kit itself as a kit. The terms are in [`LICENSE.md`](LICENSE.md).

The package the kit depends on has its own licence, in
`vendor/k2labs/base-tenant/LICENSE.md`.
