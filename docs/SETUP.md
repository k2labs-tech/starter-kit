# Base Tenant - Base Tenant Setup Report

> Generated on 2026-07-31 14:32:01

## Configuration Summary

| Setting | Value |
|---|---|
| Multi-Team Mode | No |
| Subscriptions | Disabled |
| Test User Created | Yes |
| Flare Error Tracking | Not configured |
| Flux UI Pro | Not configured |
| Environment | local |

## User Credentials

### Admin (always created)

| | |
|---|---|
| Email | `admin@example.com` |
| Password | `secret123` |
| Role | `administrator` |
| Account | None (system admin) |
### Test User

| | |
|---|---|
| Email | `test@example.com` |
| Password | `password` |
| Role | `customer-admin` |
| Account | Test Company |
## Middleware Reference

| Alias | Class | Purpose |
|---|---|---|
| `base-tenant.subscription` | `HasSubscription` | Requires active subscription (bypassed for admins) |
| `base-tenant.no-subscription` | `DoesNotHaveSubscription` | Only for users without subscription (checkout) |
| `base-tenant.locale` | `SetLocale` | Sets user locale |
| `base-tenant.password-changed` | `EnsurePasswordChanged` | Forces password change on first login |
| `base-tenant.account-context` | `SetAccountContext` | Sets current account in session |
| `base-tenant.feature` | `HasFeature` | Plan feature gate |

## Key Routes

| Route | Name | Middleware |
|---|---|---|
| `/login` | `base-tenant.login` | `guest` |
| `/register` | `base-tenant.register` | `guest` |
| `/dashboard` | `base-tenant.dashboard` | `auth, verified, subscription` |
| `/users` | `base-tenant.users.index` | `auth, verified, subscription` |
| `/accounts` | `base-tenant.accounts.index` | `auth, verified, subscription` |
| `/roles` | `base-tenant.roles.index` | `auth, verified, subscription` |
| `/navigation` | `base-tenant.menus.index` | `auth, verified, subscription` |
| `/checkout` | `base-tenant.checkout` | `auth, verified, no-subscription` |
| `/billing` | `base-tenant.billing` | `auth, verified, subscription` |

## Artisan Commands

```bash
# Sync permissions and roles from config to database
php artisan base-tenant:sync-roles

# Sync the menus declared in code to the database
php artisan base-tenant:sync-menus

# Re-run installation
php artisan base-tenant:install

# Prune old activity log entries
php artisan base-tenant:prune-activity-log
```

## Useful Links

- [Installation Guide](vendor/base/tenant/docs/INSTALLATION.md)
- [Usage Guide](vendor/base/tenant/docs/USAGE.md)
- [Frontend Guide](vendor/base/tenant/docs/FRONTEND.md)
- [Stripe Dashboard](https://dashboard.stripe.com)
- [Stripe CLI Docs](https://docs.stripe.com/stripe-cli)
- [Laravel Cashier Docs](https://laravel.com/docs/billing)
- [Flux UI Docs](https://fluxui.dev/docs)
- [Flare Dashboard](https://flareapp.io)
