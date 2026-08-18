# Base Tenant Starter Kit

A Laravel starter kit for multi-tenant B2B SaaS. It gives you accounts, granular
permissions, database-driven navigation, feature flags, invitations, an audit
trail and Stripe billing on the first commit.

```bash
laravel new my-app --using=k2/base-tenant-kit
```

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
| **Auth** | Login, registration, password reset, email verification, 2FA with recovery codes, audited impersonation |
| **Billing** | Laravel Cashier with plan-based feature gates |
| **Activity** | Account-scoped audit trail with sensitive-field filtering |
| **Notifications** | Database notifications with a bell, polling and a daily digest |
| **Test kit** | `assertTenantIsolated`, `assertJobCarriesTenant`, `assertPermissionIsAccountScoped` |

### The modules

Each one has a switch. A product that wants no files and no webhooks does not
carry their tables.

| Module | What it gives you |
|---|---|
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
| **GDPR** | Personal data export, scheduled purge, versioned terms acceptance |
| **Pre-sale** | Closed registration, landing page, waiting list, founding seats |

Every management screen is on one table pattern: search, sort, density and page
size in the URL, sticky headers, designed empty states, loading skeletons, dark
mode throughout.

Stack: Laravel 13, Livewire 4, Flux UI (free tier), Tailwind 4, Pest.

## Installation

Creating a project runs `php artisan kit:install`, which asks two things.

### 1. How the project should relate to the package

**As a dependency** *(recommended)* — the tenancy, permission and navigation code
lives in `base/tenant`. You get fixes and features with `composer update`. You can
take ownership later, at any time, without redoing anything.

**As your own code** — everything is copied into the project and the package is
removed. Nothing depends on us, and no updates arrive. This is how Laravel's own
starter kits work, and it is a one-way door.

If you are unsure, choose the dependency. It is the reversible option.

```bash
php artisan kit:install --dependency   # skip the question
php artisan kit:install --own          # skip it the other way
```

### 2. The database

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

### 3. Whether you want Flux UI Pro

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
`vendor/base/tenant/docs/USAGE.md`.

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
`vendor/base/tenant/docs/SCAFFOLD-EJECT.md`.

## Local development of the kit

The kit resolves `base/tenant` from a sibling checkout:

```json
"repositories": {
    "base/tenant": { "type": "path", "url": "../base-tenant" }
}
```

Clone both side by side. Before publishing to Packagist, drop that block so the
package resolves from there instead.

## Documentation

Everything about the package itself is in `vendor/base/tenant/docs/`:

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
