# Base Tenant - AI Agent Context

> This document provides complete context for AI agents working with projects that use the `base/tenant` package. Read this before making any changes to understand the architecture, available APIs, and conventions.

## Package Overview

**base/tenant** is a multi-tenant SaaS foundation package for Laravel 12+ with Livewire 4 and Flux UI Pro. It provides tenancy, granular permissions, database-driven navigation, feature flags, authentication, Stripe subscriptions, activity logging, notifications, invitations, and user/account management out of the box.

**Tech stack:** PHP 8.4, Laravel 12, Livewire 4, Flux UI Pro, Tailwind CSS 3, Laravel Cashier (Stripe), spatie/laravel-permission 6.

**Read this first:** never scope a query by hand with `session('current_account_id')`. Use `Tenant::current()`, and put `BelongsToAccount` on tenant-owned models so the scope is automatic.

## Architecture

### Multi-Tenancy Model

- **Account-based tenancy.** `TenantManager` (facade `Tenant`) holds the active account for the whole request, job or command. The session is one of several resolvers, never the source of truth.
- Resolver chain, in order: domain/subdomain → Sanctum token → session → authenticated user.
- Each `User` has a primary `account_id` and can belong to several accounts via the `account_user` pivot.
- `BelongsToAccount` adds a global scope and stamps `account_id` on create. `acrossAccounts()` opts out; `forAccount($account)` targets another one.
- The tenant travels into queued jobs (stamped in the payload, restored by the worker), into cache keys (`Tenant::cacheKey()`) and into broadcast channel names (`Tenant::channel()`).
- **Admin users** (`is_admin = true`) bypass every gate check through `Gate::before`.
- Roles and permissions are account-scoped through spatie's teams feature, with `account_id` as the team key.

### Key Design Patterns

- **Service layer**: Business logic in `src/Services/` (not in controllers or Livewire components)
- **Morphable models**: Settings and ActivityLog use polymorphic relationships (`settingable`, `causer`, `subject`)
- **Extensible models**: Host app can override User, Account, Role, UserInvite via `config('base-tenant.models.*')`
- **Feature gates**: plan features with per-account overrides via the `Feature` facade and the `HasFeature` middleware
- **Database-resolved permissions**: roles and permissions are read from the database and scoped to the account in context, so they answer the same in requests, jobs, commands and API calls
- **Declared, stored navigation**: menus are declared in code, synced to the database, and overridable per account
- **Policies over inline checks**: authorization lives in `src/Policies/`, not in `if` statements inside components

## Database Schema

### Core Tables

**users**
```
id (UUID), name, email, password, account_id (FK nullable), is_admin (bool),
locale, currency, decimal_places, decimals_separator, thousands_separator,
timezone, date_format, time_format, hour_format,
email_verified_at, must_change_password, last_account_id,
two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at,
daily_notification_summary (nullable - null=inherit account),
accessed_at, deleted_at
```

**accounts**
```
id (UUID), name, email, phone, address, city, state, country, postal_code, vat,
user_id (FK - owner), active (bool),
force_password_change (nullable - null=inherit global),
daily_notification_summary (bool, default true), onboarded_at,
stripe_id, pm_type, pm_last_four, trial_ends_at, deleted_at
```

**roles**
```
id, key (unique), name, is_system (bool)
```

**role_user** (pivot)
```
user_id, role_id, account_id (nullable - for account-scoped roles)
```

**account_user** (pivot)
```
account_id, user_id, timestamps
```

### Feature Tables

**user_invites**
```
id (UUID), email, account_id (FK), role_id (FK), invited_by (FK user),
token (unique), expires_at, accepted_at, timestamps
```

**activity_log**
```
id (UUID), account_id, causer_type/causer_id (morph), subject_type/subject_id (morph),
action, description, old_values (JSON), new_values (JSON),
ip_address, user_agent, timestamps
```

**settings**
```
id (UUID), settingable_type/settingable_id (morph),
group, key, value (text), type (string/integer/boolean/float/json/array),
timestamps (unique: settingable + group + key)
```

**notifications** (Laravel standard)
```
id (UUID), type, notifiable_type/notifiable_id (morph),
data (JSON), read_at, timestamps
```

**subscriptions** / **subscription_items** (Laravel Cashier standard)

## Models

### User (`Base\Tenant\Models\User`)

**Relationships:**
- `account(): BelongsTo` - Primary account
- `accounts(): BelongsToMany` - All accounts (with timestamps)
- `roles(): BelongsToMany` - All roles

**Key Methods:**
- `createPrimaryAccountAndSetRole(?name, role='customer-admin')` - Creates account, sets relations, assigns role
- `addRole(string $role): bool` - Attach role by key
- `hasRole(string $role): bool` - Check role (session-cached)
- `hasAnyRole(array|string $roles): bool` - Check any role
- `authorizeRoles(array|string $roles): bool` - Abort 401 if no role
- `storeRolesSession()` - Refresh cached roles (account-scoped if applicable)
- `determineDefaultAccount(): string` - Priority: last_account_id > account_id > first account
- `shouldReceiveDailySummary(): bool` - Cascading: user preference > account default
- `hasTwoFactorEnabled(): bool`
- `enableTwoFactorAuthentication(string $secret): void`
- `canImpersonate(): bool` - True for system admins
- `canBeImpersonated(): bool` - True for non-admins
- `applyTimeZone(mixed $dateTime): string` - Format with user timezone
- `applyDateFormat(?string $date): string`
- `applyCurrencyFormat(float $amount, int $decimals): string`
- `initials: string` (accessor) - User initials from name

**Traits:** HasFactory, HasSettings, HasUuids, Impersonate, Notifiable, SoftDeletes

### Account (`Base\Tenant\Models\Account`)

**Relationships:**
- `users(): BelongsToMany` - All members
- `owner(): BelongsTo` - Account owner (via user_id)

**Key Methods:**
- `stripeEmail(): ?string` - Owner email with fallback to account email
- `stripeName(): ?string` - Account name
- `hasActiveSubscription(): bool`
- `planCan(string $feature): bool` - Check feature access
- `planLimit(string $feature): int` - Get feature limit
- `isWithinPlanLimit(string $feature, int $currentUsage): bool`
- `getPlanName(): string`

**Traits:** Billable (Cashier), HasFactory, HasSettings, HasUuids, SoftDeletes

### Role (`Base\Tenant\Models\Role`)
Simple model with `key`, `name`, `is_system` fields.

### UserInvite (`Base\Tenant\Models\UserInvite`)
- `account(): BelongsTo`
- `role(): BelongsTo`
- `inviter(): BelongsTo` (User)
- `isExpired(): bool`
- `isAccepted(): bool`

### ActivityLog (`Base\Tenant\Models\ActivityLog`)
- `causer(): MorphTo` (who did it)
- `subject(): MorphTo` (what was affected)
- Casts: old_values/new_values as array

### Setting (`Base\Tenant\Models\Setting`)
- `settingable(): MorphTo` (User or Account)
- `castValue()` - Auto-cast by type field

## Services

### FeatureService
```php
FeatureService::accountCan(Account $account, string $feature): bool
FeatureService::getLimit(Account $account, string $feature): int  // -1 = unlimited
FeatureService::isWithinLimit(Account $account, string $feature, int $usage): bool
FeatureService::getAccountFeatures(Account $account): array
FeatureService::getAccountPlanName(Account $account): string
FeatureService::getPlanForAccount(Account $account): string  // 'free'|'starter'|'professional'
```

Plan resolution: matches `stripe_price_id` from active subscription against `config('base-tenant.plans.*.stripe_price_id')`. Falls back to `'free'`.

### ActivityLogService
```php
ActivityLogService::log(
    Model $subject, string $action, string $description,
    ?array $oldValues, ?array $newValues,
    ?Model $causer, ?string $accountId
): ActivityLog

ActivityLogService::getForAccount(
    string $accountId, int $perPage = 25,
    ?string $search, ?string $action, ?string $causerId
): LengthAwarePaginator

ActivityLogService::pruneOlderThan(int $days = 90): int
```

Automatically filters sensitive fields: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`, `stripe_*`.

### InvitationService
```php
InvitationService::send(string $email, string $accountId, string $roleId, User $inviter): UserInvite
InvitationService::resend(UserInvite $invite): UserInvite
InvitationService::accept(UserInvite $invite, User $user): void
InvitationService::revoke(UserInvite $invite): void
InvitationService::getPendingForAccount(string $accountId): Collection
```

### NotificationService
```php
NotificationService::notifyProjectUsers(Model $project, Notification $notification, ?User $except): void
NotificationService::notifySpecificUsers(Collection $users, Notification $notification): void
NotificationService::getUnreadCount(User $user): int
NotificationService::markAsRead(User $user, string $notificationId): void
NotificationService::markAllAsRead(User $user): void
```

### SettingService
```php
SettingService::get(Model $model, string $key, string $group = 'general', mixed $default = null): mixed
SettingService::resolve(string $key, string $group = 'general', mixed $default = null): mixed
```

`resolve()` follows cascade: authenticated user setting > account setting > default value.

## Traits (for use in host app models)

### LogsActivity
Add to any model to auto-log created/updated/deleted events:
```php
use Base\Tenant\Traits\LogsActivity;

class Project extends Model
{
    use LogsActivity;
}
```

### HasSettings
Add morphable key-value settings to any model:
```php
$user->setSetting('theme', 'dark', 'string', 'preferences');
$user->getSetting('theme', 'preferences', 'light');
$user->getSettingsByGroup('preferences');
$user->setManySettings([
    ['key' => 'theme', 'value' => 'dark', 'type' => 'string'],
    ['key' => 'sidebar', 'value' => true, 'type' => 'boolean'],
], 'preferences');
```

### HasExtensibleRoles (static)
Used in seeders/commands to sync roles from config:
```php
use Base\Tenant\Traits\HasExtensibleRoles;

class MySeeder extends Seeder
{
    use HasExtensibleRoles;

    public function run(): void
    {
        static::syncRolesToDatabase();
    }
}
```

## Middleware

| Alias | Class | Behavior |
|---|---|---|
| `base-tenant.subscription` | HasSubscription | Requires active subscription. Bypasses: disabled config, admin users, non-production without Stripe configured |
| `base-tenant.no-subscription` | DoesNotHaveSubscription | Guards checkout route. Redirects to dashboard if subscribed, admin, or non-production without Stripe |
| `base-tenant.locale` | SetLocale | Sets app locale from user preference or session |
| `base-tenant.password-changed` | EnsurePasswordChanged | Forces password change if `must_change_password` flag set |
| `base-tenant.account-context` | SetAccountContext | Sets `current_account_id` in session (auto-added to web group) |
| `base-tenant.feature` | HasFeature | Feature gate: `Route::get(...)->middleware('base-tenant.feature:api_access')` |

## Routes

### Authentication (prefix configurable)
```
GET  /login                    base-tenant.login           guest
GET  /register                 base-tenant.register        guest
GET  /forgot-password          base-tenant.password.request guest
GET  /reset-password/{token}   base-tenant.password.reset  guest
GET  /two-factor-challenge     base-tenant.two-factor.challenge guest
POST /logout                   base-tenant.logout          auth
GET  /verify-email             base-tenant.verification.notice auth
GET  /verify-email/{id}/{hash} base-tenant.verification.verify auth, signed
GET  /confirm-password         base-tenant.password.confirm auth
GET  /password/change          base-tenant.password.change  auth
```

### Protected (auth + verified + subscription)
```
GET  /dashboard       base-tenant.dashboard
GET  /profile         base-tenant.profile
GET  /upgrade         base-tenant.upgrade
GET  /users           base-tenant.users.index
GET  /users/create    base-tenant.users.create
GET  /users/{user}/edit base-tenant.users.edit
GET  /accounts        base-tenant.accounts.index
GET  /accounts/create base-tenant.accounts.create
GET  /accounts/{account}/edit base-tenant.accounts.edit
GET  /invitations     base-tenant.invitations.index
GET  /activity        base-tenant.activity
GET  /notifications   base-tenant.notifications.index
POST /notifications/{id}/read    base-tenant.notifications.mark-as-read
POST /notifications/mark-all-read base-tenant.notifications.mark-all-read
GET  /impersonate/leave          impersonate.leave
```

### Subscriptions (when enabled)
```
GET  /checkout          base-tenant.checkout         auth, verified, no-subscription
GET  /checkout/success  base-tenant.checkout.success  auth, verified, subscription
GET  /checkout/cancel   base-tenant.checkout.cancel   auth, verified, subscription
GET  /billing           base-tenant.billing           auth, verified, subscription
```

### Webhooks (Laravel Cashier)
```
POST /stripe/webhook    cashier.webhook  (auto-registered by Cashier)
```

### Public
```
GET  /invitations/accept/{token}  base-tenant.invitations.accept
```

## Livewire Components

All registered with `base-tenant.` prefix:

| Component | Route | Purpose |
|---|---|---|
| `base-tenant.user-manager` | `/users` | User CRUD with search, delete, impersonate |
| `base-tenant.edit-user` | `/users/create`, `/users/{user}/edit` | User form with preferences, roles |
| `base-tenant.account-manager` | `/accounts` | Account CRUD with search |
| `base-tenant.edit-account` | `/accounts/create`, `/accounts/{account}/edit` | Account form with owner, settings |
| `base-tenant.invitation-manager` | `/invitations` | Send, resend, revoke invitations |
| `base-tenant.activity-log` | `/activity` | Audit trail with filters |
| `base-tenant.preferences` | embedded | User locale, currency, date preferences |
| `base-tenant.account-switcher` | embedded | Multi-team account switching |
| `base-tenant.notification-bell` | embedded | Notification dropdown with polling |
| `base-tenant.notifications.index` | `/notifications` | Full notifications page |
| `base-tenant.two-factor-authentication` | embedded | 2FA setup/disable |
| `base-tenant.two-factor-challenge` | `/two-factor-challenge` | 2FA verification |
| `base-tenant.profile.update-profile-information-form` | embedded | Name, email, phone update |
| `base-tenant.profile.update-password-form` | embedded | Change password |
| `base-tenant.profile.delete-user-form` | embedded | Account deletion |
| `base-tenant.alerts.table` | embedded | Alerts display |
| `base-tenant.forms.login-form` | embedded | Reusable login form |
| `base-tenant.logout` | embedded | Logout action |

## Configuration Reference

### Plan Feature Gates

Define in `config('base-tenant.plans')`:
```php
'plans' => [
    'free' => [
        'name' => 'Free',
        'stripe_price_id' => null,
        'features' => [
            'max_users' => 3,          // numeric limit
            'max_projects' => 1,       // numeric limit
            'api_access' => false,     // boolean gate
            'export' => false,         // boolean gate
            'priority_support' => false,
        ],
    ],
    'professional' => [
        'name' => 'Professional',
        'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
        'features' => [
            'max_users' => -1,         // -1 = unlimited
            'max_projects' => -1,
            'api_access' => true,
            'export' => true,
            'priority_support' => true,
        ],
    ],
],
```

### Custom Roles

Add to `config('base-tenant.roles.custom')`:
```php
'custom' => [
    ['key' => 'project-manager', 'name' => 'Project Manager', 'is_system' => false],
],
```

Then run: `php artisan base-tenant:sync-roles`

### Model Overrides

```php
'models' => [
    'user' => \App\Models\User::class,     // must extend Base\Tenant\Models\User
    'account' => \App\Models\Account::class,
    'role' => \App\Models\Role::class,
    'user_invite' => \App\Models\UserInvite::class,
],
```

## Blade Directives

### Feature Gate
```blade
@feature('api_access')
    {{-- Content visible only if current account has this feature --}}
@endfeature
```

## Common Patterns for Host Applications

### Working with the Tenant
```php
use Base\Tenant\Facades\Tenant;

Tenant::current();                              // Account|null
Tenant::currentId();                            // string|null
Tenant::set($account, remember: true);          // also writes the session
Tenant::runFor($account, fn () => /* ... */);   // restores the previous account afterwards
Tenant::runWithout(fn () => /* ... */);         // no tenant, for cross-account work
Tenant::eachAccount(fn () => /* ... */);        // scheduled sweeps, one context per account
```

### Checking Permissions and Roles
```php
// Prefer permissions: roles are bundles customers can redefine.
if (auth()->user()->can('invoices.approve')) { ... }
if (auth()->user()->can('update', $invoice)) { ... }

// hasPermission() returns false for unknown permissions instead of throwing.
if (auth()->user()->hasPermission('invoices.approve')) { ... }

// Ask about a specific account.
$user->hasPermissionInAccount($account, 'invoices.approve');
$user->rolesForAccount($account);

// Roles still work, scoped to the account in context.
if (auth()->user()->hasRole('customer-admin')) { ... }
auth()->user()->authorizeRoles('administrator'); // aborts 403
```

### Assigning Roles
```php
// Always inside the account the role belongs to.
Tenant::runFor($account, fn () => $user->assignRole('customer-admin'));
Tenant::runFor($account, fn () => $user->syncRoles(['customer-user']));
```

### Adding Permissions and Roles
Declare them in `config/base-tenant.php`, then run `php artisan base-tenant:sync-roles`.
```php
'permissions' => [
    'invoices' => ['invoices.view', 'invoices.create', 'invoices.approve'],
],
'roles' => [
    'custom' => [
        ['key' => 'billing-manager', 'name' => 'Billing Manager', 'is_system' => false,
         'permissions' => ['invoices.*', 'accounts.billing']],
    ],
],
```
Add labels under `base-tenant::permissions.names.*` so the role editor shows them.

### Adding Menu Entries
```php
use Base\Tenant\Facades\Menu;
use Base\Tenant\Menu\MenuBuilder;

// In a service provider's boot(), then: php artisan base-tenant:sync-menus
Menu::register('main', function (MenuBuilder $menu): void {
    $menu->item('invoices')
        ->label('app.navigation.invoices')   // translation key
        ->icon('building-office')
        ->route('invoices.index')
        ->permission('invoices.view')
        ->feature('billing')                 // optional feature flag gate
        ->badge(fn (): int => Invoice::pending()->count())
        ->position(50);
});
```

### Feature Gating in Code
```php
use Base\Tenant\Facades\Feature;

Feature::active('api_access');                          // account in context
Feature::for($account)->active('api_access');
Feature::for($account)->limit('max_users');             // -1 = unlimited
Feature::for($account)->withinLimit('max_users', $count);

// Grant one account something its plan does not include, optionally with an expiry.
Feature::for($account)->set('api_access', true, now()->addMonth());
```
Resolution order: per-account override in the `features` table, then the plan in
`config('base-tenant.plans')`.

### Feature Gating via Middleware
```php
Route::get('/api/data', ApiController::class)
    ->middleware('base-tenant.feature:api_access');
```

### Logging Activity
```php
// Automatic (add trait to model)
class Project extends Model { use LogsActivity; }

// Manual
// The causer and the account are taken from the auth and tenant context.
ActivityLogService::log(
    subject: $project,
    action: 'exported',
    description: "Project {$project->name} exported",
    newValues: ['format' => 'csv'],
);
```

### Settings
```php
// User-level
auth()->user()->setSetting('editor_theme', 'dark', 'string', 'editor');
$theme = auth()->user()->getSetting('editor_theme', 'editor', 'light');

// Account-level
$account->setSetting('default_language', 'es', 'string', 'project');

// Cascading resolution (user > account > default)
$value = SettingService::resolve('editor_theme', 'editor', 'light');
```

### Sending Notifications
```php
use Base\Tenant\Services\NotificationService;

// To all project members except causer
NotificationService::notifyProjectUsers($project, new MyNotification(), auth()->user());

// To specific users
NotificationService::notifySpecificUsers($users, new MyNotification());
```

### Creating Custom Notifications
```php
use Base\Tenant\Notifications\BaseTenantNotification;

class ProjectCreatedNotification extends BaseTenantNotification
{
    public function __construct(Project $project, User $causer)
    {
        $this->title = __('New Project');
        $this->message = __(':name created project :project', [
            'name' => $causer->name,
            'project' => $project->name,
        ]);
        $this->actionUrl = route('projects.show', $project);
        $this->icon = 'folder-plus';
        $this->priority = 'normal';
        $this->category = 'project.created';
        $this->causer = $causer;
    }
}
```

### Scoping Queries to Current Account
Do not filter by hand. Add the trait and let the scope do it:
```php
use Base\Tenant\Traits\BelongsToAccount;

class Project extends Model
{
    use BelongsToAccount;
}

Project::all();                          // only the active account
Project::query()->acrossAccounts();      // every account, admin tooling only
Project::query()->forAccount($account);  // one specific account
Project::create([...]);                  // account_id is stamped automatically
```
Public routes that resolve a record with no tenant in context — an invitation
accepted from an email link, for instance — need `acrossAccounts()`.

Prove it with the shipped assertion:
```php
$this->assertTenantIsolated(Project::class, fn ($account) => Project::factory()->create());
```

### Typed Settings
```php
use Base\Tenant\Facades\Settings;
use Base\Tenant\Settings\SettingsSchema;

class BrandSettings extends SettingsSchema
{
    public static function group(): string { return 'brand'; }

    public string $tone = 'neutral';
    public bool $signOffWithName = true;
}

$brand = Settings::for($account)->get(BrandSettings::class);
$brand->tone = 'playful';
$brand->save();

Settings::current()->get(BrandSettings::class);   // account in context
```

### Extending Models
```php
// app/Models/User.php
namespace App\Models;

use Base\Tenant\Models\User as BaseTenantUser;

class User extends BaseTenantUser
{
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
```

Update config: `'user' => \App\Models\User::class`

## Artisan Commands

```bash
php artisan base-tenant:install            # Interactive installer
php artisan base-tenant:sync-roles [--show] # Sync permissions and global roles from config
php artisan base-tenant:sync-menus          # Sync menus declared in code
php artisan base-tenant:prune-activity-log  # Delete logs older than retention_days
```

## Environment Variables

| Variable | Default | Purpose |
|---|---|---|
| `BASE_TENANT_MULTI_TEAM` | `false` | Enable multi-team mode |
| `BASE_TENANT_HOME_URL` | `base-tenant.dashboard` | Post-login redirect route |
| `BASE_TENANT_SUBSCRIPTION_ENABLED` | `true` | Enable subscription system |
| `BASE_TENANT_SUBSCRIPTION_DEFAULT_PRODUCT` | `null` | Stripe product ID for checkout |
| `BASE_TENANT_SUBSCRIPTION_DEFAULT_PRICE` | `null` | Stripe price ID for checkout |
| `BASE_TENANT_SUBSCRIPTION_TRIAL_DAYS` | `14` | Trial period days |
| `BASE_TENANT_ACTIVITY_LOG_ENABLED` | `true` | Enable activity logging |
| `BASE_TENANT_ACTIVITY_LOG_RETENTION` | `90` | Days to keep activity logs |
| `BASE_TENANT_NOTIFICATIONS_ENABLED` | `true` | Enable notification system |
| `BASE_TENANT_INVITATIONS_ENABLED` | `true` | Enable invitation system |
| `BASE_TENANT_SETTINGS_ENABLED` | `true` | Enable settings system |
| `BASE_TENANT_FORCE_PASSWORD_CHANGE` | `false` | Force password change on first login |
| `STRIPE_KEY` | - | Stripe publishable key |
| `STRIPE_SECRET` | - | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | - | Stripe webhook signing secret |
| `FLARE_KEY` | - | Flare error tracking key |

## Non-Production Behavior

In `local` and `testing` environments, if Stripe is not fully configured (missing any of: `STRIPE_KEY`, `STRIPE_SECRET`, `DEFAULT_PRODUCT`, `DEFAULT_PRICE`):
- Subscription middleware is bypassed
- Users access the dashboard without subscriptions
- Checkout route still validates and returns 503 if accessed directly

## File Structure

```
base-tenant/
  config/base-tenant.php           # Package configuration
  database/
    factories/                     # AccountFactory, UserFactory
    migrations/                    # 17 migration files
    seeders/                       # BaseTenantSeeder, InitialLoadSeeder, TestUserSeeder
  docs/                            # Documentation
  resources/
    css/app.css                    # Package styles
    lang/{en,es}/                  # Translations
    views/                         # Blade templates
      components/                  # Reusable UI components
      layouts/                     # App and guest layouts
      livewire/                    # Livewire component views
  routes/
    auth.php                       # Authentication routes
    web.php                        # Main application routes
    subscriptions.php              # Stripe checkout/billing routes
  src/
    Console/Commands/              # Artisan commands
    Console/Support/               # Installation helpers
    Http/Controllers/              # HTTP controllers
    Http/Middleware/                # 6 middleware classes
    Livewire/                      # Livewire components
    Models/                        # Eloquent models
    Notifications/                 # Notification classes
    Services/                      # Business logic services
    Traits/                        # Reusable traits
    View/Components/               # View component classes
    helpers.php                    # Global helper functions
    BaseTenantServiceProvider.php  # Service provider
```

## Default Roles

| Key | Name | System | Typical Use |
|---|---|---|---|
| `administrator` | Administrator | Yes | Full system access |
| `administrator-finance` | Administrator Finance | Yes | Financial operations |
| `administrator-tech` | Administrator Tech | Yes | Technical operations |
| `customer-admin` | Customer Admin | No | Account owner/manager |
| `customer-user` | Customer User | No | Regular team member |
| `customer-viewer` | Customer Viewer | No | Read-only access |
| `customer-finance` | Customer Finance | No | Financial data access |

## Translation Keys

All translations use `base-tenant::` namespace:
```php
__('base-tenant::common.save')
__('base-tenant::users.create')
__('base-tenant::subscription.payment_successful')
```

Available translation files: `common`, `users`, `accounts`, `auth`, `app`, `subscription`, `roles`, `mails`, `currencies`, `languages`, `welcome`, `pagination`, `passwords`, `validation`.
