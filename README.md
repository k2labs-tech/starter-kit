# Base Tenant Starter Kit

A Laravel starter kit for multi-tenant B2B SaaS. It gives you accounts, granular
permissions, database-driven navigation, feature flags, invitations, an audit
trail and Stripe billing on the first commit.

```bash
laravel new my-app --using=k2/base-tenant-kit
```

## What you get

| | |
|---|---|
| **Tenancy** | One active account per request, resolved from domain, API token, session or user, and carried into queued jobs, cache keys and broadcast channels |
| **Isolation** | `BelongsToAccount` scopes a model to the account and stamps new records, so a forgotten `where` cannot leak another customer's data |
| **Permissions** | spatie/laravel-permission with `account_id` as the team key. Roles resolve per account, from the database, in requests, jobs, commands and API calls alike |
| **Per-account roles** | A shared catalogue plus roles each tenant defines for itself, edited through a role × permission matrix |
| **Navigation** | Declared in code, stored in the database, reorderable and hideable per account, filtered by permission and feature flag |
| **Feature flags** | Plan features with per-account overrides and expiry |
| **Auth** | Login, registration, password reset, email verification, 2FA with recovery codes, audited impersonation |
| **Billing** | Laravel Cashier with plan-based feature gates |
| **Test kit** | `assertTenantIsolated`, `assertJobCarriesTenant`, `assertPermissionIsAccountScoped` |

Stack: Laravel 13, Livewire 4, Flux UI, Tailwind 4, Pest.

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
php artisan base-tenant:install --database
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
`php artisan base-tenant:sync-roles`:

```php
'permissions' => [
    'invoices' => ['invoices.view', 'invoices.create', 'invoices.approve'],
],
```

Add navigation from a service provider, then `php artisan base-tenant:sync-menus`:

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

## Taking ownership later

```bash
php artisan base-tenant:scaffold --dry-run   # see exactly what would move
php artisan base-tenant:scaffold             # ~255 files into app/, resources/, routes/
composer dump-autoload
# run your suite, read the diff, then:
php artisan base-tenant:eject
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

Everything about the package itself lives in `vendor/base/tenant/docs/`:
`USAGE.md`, `AI_CONTEXT.md`, `SCAFFOLD-EJECT.md`, `UPGRADE.md`.
