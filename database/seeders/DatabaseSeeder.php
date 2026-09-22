<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Everything a freshly migrated database needs to be usable.
 *
 * `php artisan migrate:fresh --seed` has to leave you with an application you
 * can sign into: without the permission catalogue, the global roles and the
 * menus, the interface renders empty and nobody can be authorised for anything.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Permissions, global roles and the product menus.
            ...$this->tenantSeeder('InitialLoadSeeder'),

            // A staff account to sign in with.
            ...$this->tenantSeeder('AdminUserSeeder'),
        ]);

        // Add your own seeders below.
    }

    /**
     * The package's seeders live in its namespace while it is a dependency,
     * and in this one once `k2labs-base:eject` has copied them here. Naming
     * either of them outright is what breaks the other: an import of a class
     * that has moved is a fatal on `db:seed`, on a database that then has
     * nothing to sign in with.
     *
     * @return array<int, class-string>
     */
    protected function tenantSeeder(string $seeder): array
    {
        foreach ([__NAMESPACE__, 'Base\\Tenant\\Database\\Seeders'] as $namespace) {
            if (class_exists($class = $namespace.'\\'.$seeder)) {
                return [$class];
            }
        }

        return [];
    }
}
