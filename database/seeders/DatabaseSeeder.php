<?php

declare(strict_types=1);

namespace Database\Seeders;

use Base\Tenant\Database\Seeders\AdminUserSeeder;
use Base\Tenant\Database\Seeders\InitialLoadSeeder;
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
            InitialLoadSeeder::class,

            // A staff account to sign in with.
            AdminUserSeeder::class,
        ]);

        // Add your own seeders below.
    }
}
