<?php

use Base\Tenant\Models\Account;
use Base\Tenant\Models\Permission;
use Base\Tenant\Models\Role;
use Base\Tenant\Models\User;
use Base\Tenant\Models\UserInvite;
use Base\Tenant\Tenancy\Resolvers\ApiTokenTenantResolver;
use Base\Tenant\Tenancy\Resolvers\DomainTenantResolver;
use Base\Tenant\Tenancy\Resolvers\SessionTenantResolver;
use Base\Tenant\Tenancy\Resolvers\UserTenantResolver;

return [

    /*
    |--------------------------------------------------------------------------
    | Base Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Base Tenant package including multi-team
    | support, subscription settings, and customizable role definitions.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Multi-Team Support
    |--------------------------------------------------------------------------
    |
    | Enable or disable multi-team functionality. When enabled, users can
    | belong to multiple accounts/teams.
    |
    */

    'multi_team' => env('BASE_TENANT_MULTI_TEAM', false),

    /*
    |--------------------------------------------------------------------------
    | Installation State
    |--------------------------------------------------------------------------
    |
    | Tracks how far this project has moved from depending on the package to
    | owning the code outright. The commands read it and refuse to run out of
    | order; you should not normally edit it by hand.
    |
    |   fresh       Nothing installed yet
    |   installed   Running on the package
    |   scaffolded  The code has been copied into the application, which now
    |               owns routes, views and components. The package is still
    |               present but stands down.
    |   ejected     The package has been removed
    |
    */

    'installation_state' => 'installed',

    /*
    |--------------------------------------------------------------------------
    | Tenancy
    |--------------------------------------------------------------------------
    |
    | How the active account is resolved for a request, and what tenant-scoped
    | queries do when no account could be resolved.
    |
    | The resolvers run in order until one returns an account.
    |
    | on_missing_tenant:
    |   auto  - unfiltered in console and queue work, no results over HTTP
    |   allow - unfiltered everywhere (only for single-tenant installs)
    |   deny  - no results anywhere without an account in context
    |
    */

    'tenancy' => [

        'resolvers' => [
            DomainTenantResolver::class,
            ApiTokenTenantResolver::class,
            SessionTenantResolver::class,
            UserTenantResolver::class,
        ],

        'central_domains' => array_filter(
            explode(',', (string) env('BASE_TENANT_CENTRAL_DOMAINS', ''))
        ),

        'on_missing_tenant' => env('BASE_TENANT_ON_MISSING_TENANT', 'auto'),

        'propagate_to_queue' => env('BASE_TENANT_PROPAGATE_TO_QUEUE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Home URL
    |--------------------------------------------------------------------------
    |
    | The route name to redirect users to after login.
    |
    */

    'home_url' => env('BASE_TENANT_HOME_URL', 'base-tenant.dashboard'),

    /*
    |--------------------------------------------------------------------------
    | Subscription Settings
    |--------------------------------------------------------------------------
    |
    | Configure Stripe subscription behavior including default products,
    | prices, and checkout flow URLs.
    |
    */

    'subscription' => [
        'enabled' => env('BASE_TENANT_SUBSCRIPTION_ENABLED', true),
        'default_product' => env('BASE_TENANT_SUBSCRIPTION_DEFAULT_PRODUCT', null),
        'default_price' => env('BASE_TENANT_SUBSCRIPTION_DEFAULT_PRICE', null),
        'success_url' => env('BASE_TENANT_SUBSCRIPTION_SUCCESS_URL', 'base-tenant.checkout.success'),
        'cancel_url' => env('BASE_TENANT_SUBSCRIPTION_CANCEL_URL', 'base-tenant.checkout.cancel'),
        'trial_days' => (int) env('BASE_TENANT_SUBSCRIPTION_TRIAL_DAYS', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Feature Gates
    |--------------------------------------------------------------------------
    |
    | Define the features and limits available for each subscription plan.
    | Numeric values: -1 = unlimited, 0 = disabled, >0 = limit.
    | Boolean values: true = enabled, false = disabled.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'stripe_price_id' => null,
            'features' => [
                'max_users' => 3,
                'max_projects' => 1,
                'api_access' => false,
                'export' => false,
                'priority_support' => false,
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'stripe_price_id' => env('STRIPE_STARTER_PRICE_ID'),
            'features' => [
                'max_users' => 10,
                'max_projects' => 5,
                'api_access' => true,
                'export' => true,
                'priority_support' => false,
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
            'features' => [
                'max_users' => -1,
                'max_projects' => -1,
                'api_access' => true,
                'export' => true,
                'priority_support' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for the notification system.
    |
    */

    'notifications' => [
        'enabled' => env('BASE_TENANT_NOTIFICATIONS_ENABLED', true),

        // Channels
        'channels' => ['database'], // Future: 'mail', 'broadcast'

        // UI Settings
        'polling_interval' => env('BASE_TENANT_NOTIFICATIONS_POLLING_INTERVAL', 30), // seconds
        'dropdown_limit' => 10, // notifications in dropdown
        'per_page' => 25, // notifications per page in full view

        // Notification Categories (enable/disable)
        'categories' => [
            'project.settings' => true,
            'project.access' => true,
            'project.milestones' => true,
            'quota.warnings' => true,
            'translation.activity' => true,
            'ai.usage' => true,
        ],

        // Priority Thresholds
        'ai_usage_threshold' => 50, // autofills per hour to trigger notification
        'quota_warning_percent' => 80, // % of quota to trigger warning
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    |
    | Configuration for the activity log / audit trail system.
    |
    */

    'activity_log' => [
        'enabled' => env('BASE_TENANT_ACTIVITY_LOG_ENABLED', true),
        'retention_days' => env('BASE_TENANT_ACTIVITY_LOG_RETENTION', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Catalogue
    |--------------------------------------------------------------------------
    |
    | Every granular ability the application knows about, grouped for the
    | role editor. Add your own groups here; `k2labs-base:sync-roles` writes
    | them to the database.
    |
    | Labels come from the `base-tenant::permissions` translation file, keyed
    | by the permission name.
    |
    */

    'permissions_guard' => env('BASE_TENANT_PERMISSIONS_GUARD', 'web'),

    'permissions' => [

        'users' => [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.impersonate',
        ],

        'roles' => [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ],

        'accounts' => [
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.delete',
            'accounts.billing',
        ],

        'invitations' => [
            'invitations.view',
            'invitations.create',
            'invitations.revoke',
        ],

        'activity' => [
            'activity.view',
        ],

        'settings' => [
            'settings.view',
            'settings.update',
        ],

        'menus' => [
            'menus.view',
            'menus.update',
        ],

        'features' => [
            'features.view',
            'features.update',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensible Roles Configuration
    |--------------------------------------------------------------------------
    |
    | Define your application's roles. System roles are predefined by the
    | package, while custom roles can be added by your application.
    |
    | Each role should have:
    | - key: Unique identifier, also the name spatie/laravel-permission uses
    | - name: Display name
    | - is_system: Whether it's a system role (true) or custom (false)
    | - permissions: Array of permission names, '*' for all, or entries with
    |   a wildcard such as 'users.*'
    |
    | Roles declared here are global: every account can assign them. Accounts
    | may also define their own roles through the role editor.
    |
    */

    'roles' => [
        'system' => [
            [
                'key' => 'administrator',
                'name' => 'Administrator',
                'is_system' => true,
                'permissions' => '*',
            ],
            [
                'key' => 'administrator-finance',
                'name' => 'Administrator Finance',
                'is_system' => true,
                'permissions' => [
                    'accounts.view',
                    'accounts.billing',
                    'users.view',
                    'activity.view',
                ],
            ],
            [
                'key' => 'administrator-tech',
                'name' => 'Administrator Tech',
                'is_system' => true,
                'permissions' => [
                    'users.*',
                    'roles.*',
                    'accounts.view',
                    'accounts.update',
                    'activity.view',
                    'settings.*',
                    'menus.*',
                    'features.*',
                ],
            ],
        ],
        'customer' => [
            [
                'key' => 'customer-admin',
                'name' => 'Customer Admin',
                'is_system' => false,
                'permissions' => [
                    'users.*',
                    'roles.*',
                    'invitations.*',
                    'accounts.view',
                    'accounts.update',
                    'accounts.billing',
                    'activity.view',
                    'settings.*',
                    'menus.*',
                    'features.view',
                ],
            ],
            [
                'key' => 'customer-user',
                'name' => 'Customer User',
                'is_system' => false,
                'permissions' => [
                    'users.view',
                    'accounts.view',
                    'settings.view',
                    'menus.view',
                    'features.view',
                ],
            ],
            [
                'key' => 'customer-viewer',
                'name' => 'Customer Viewer',
                'is_system' => false,
                'permissions' => [
                    'accounts.view',
                    'menus.view',
                ],
            ],
            [
                'key' => 'customer-finance',
                'name' => 'Customer Finance',
                'is_system' => false,
                'permissions' => [
                    'accounts.view',
                    'accounts.billing',
                    'users.view',
                    'activity.view',
                ],
            ],
        ],
        // Add your custom roles here
        'custom' => [
            // Example:
            // [
            //     'key' => 'custom-role',
            //     'name' => 'Custom Role',
            //     'is_system' => false,
            //     'permissions' => ['users.view'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | If you need to extend the package models in your application, you can
    | specify your custom models here. The package will use these instead
    | of the default models.
    |
    */

    'models' => [
        'user' => env('BASE_TENANT_USER_MODEL', \App\Models\User::class),
        'account' => env('BASE_TENANT_ACCOUNT_MODEL', Account::class),
        'role' => env('BASE_TENANT_ROLE_MODEL', Role::class),
        'permission' => env('BASE_TENANT_PERMISSION_MODEL', Permission::class),
        'user_invite' => env('BASE_TENANT_USER_INVITE_MODEL', UserInvite::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    |
    | Blade layouts the package's Livewire components render into. Point these
    | at your own layouts to keep the package pages inside your chrome.
    |
    */

    'layouts' => [
        'app' => env('BASE_TENANT_LAYOUT_APP', 'base-tenant::layouts.app'),
        'guest' => env('BASE_TENANT_LAYOUT_GUEST', 'base-tenant::layouts.guest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Menus
    |--------------------------------------------------------------------------
    |
    | Menus are declared in code and stored in the database, where each account
    | can override ordering, labels and visibility.
    |
    */

    'menu' => [
        'enabled' => env('BASE_TENANT_MENU_ENABLED', true),

        // Cache resolved trees per account, role set and locale.
        'cache' => [
            'enabled' => env('BASE_TENANT_MENU_CACHE', true),
            'ttl' => (int) env('BASE_TENANT_MENU_CACHE_TTL', 3600),
        ],

        // Menus the package registers out of the box.
        'default_menus' => ['main', 'settings'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure routing behavior for the package.
    |
    */

    'routes' => [
        'enabled' => env('BASE_TENANT_ROUTES_ENABLED', true),
        'prefix' => env('BASE_TENANT_ROUTES_PREFIX', ''),
        'middleware' => ['web'],
        'auth_middleware' => ['web', 'auth', 'verified', 'base-tenant.subscription'],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the package's UI behavior and theme settings.
    |
    */

    'ui' => [
        'brand_name' => env('APP_NAME', 'Laravel'),
        'brand_logo' => env('BASE_TENANT_BRAND_LOGO', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Password Change
    |--------------------------------------------------------------------------
    |
    | When enabled, newly created users will be required to change their
    | password upon first login. This improves security by ensuring
    | administrators don't know the final passwords of users they create.
    |
    | - enabled: Enable/disable the feature (default: false)
    | - send_welcome_email: Send email with temporary credentials (default: true)
    |
    */

    'force_password_change' => [
        'enabled' => env('BASE_TENANT_FORCE_PASSWORD_CHANGE', false),
        'send_welcome_email' => env('BASE_TENANT_SEND_WELCOME_EMAIL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    |
    | Configure the user invitation system. When enabled, administrators can
    | invite users to join an account via email.
    |
    */

    'invitations' => [
        'enabled' => env('BASE_TENANT_INVITATIONS_ENABLED', true),
        'expires_in_days' => env('BASE_TENANT_INVITATIONS_EXPIRES', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Generic key-value settings system for accounts and users.
    |
    */

    'settings' => [
        'enabled' => env('BASE_TENANT_SETTINGS_ENABLED', true),
    ],

];
