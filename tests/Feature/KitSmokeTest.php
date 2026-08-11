<?php

declare(strict_types=1);

use App\Models\User;
use Base\Tenant\Database\Seeders\InitialLoadSeeder;
use Base\Tenant\Facades\Menu;
use Base\Tenant\Facades\Tenant;
use Base\Tenant\Models\Account;
use Base\Tenant\Models\Permission;
use Base\Tenant\Models\Role;
use Base\Tenant\Models\UserInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Proves a project created from this kit actually works: the package boots, its
 * routes and permissions are wired, the navigation renders from the database
 * and tenant data stays put.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InitialLoadSeeder::class);

    $this->account = Account::factory()->create(['name' => 'Test Company']);

    $this->owner = User::factory()->create([
        'account_id' => $this->account->getKey(),
        'email' => 'owner@example.com',
    ]);

    $this->owner->accounts()->syncWithoutDetaching([$this->account->getKey()]);

    Tenant::runFor($this->account, fn () => $this->owner->assignRole('customer-admin'));

    Tenant::set($this->account);
});

afterEach(function () {
    Tenant::forget();
});

test('seeding writes the permission catalogue, the global roles and the menus', function () {
    expect(Permission::count())->toBeGreaterThan(20)
        ->and(Role::whereNull('account_id')->pluck('name'))
        ->toContain('administrator', 'customer-admin', 'customer-viewer')
        ->and(Base\Tenant\Models\Menu::whereNull('account_id')->pluck('key'))
        ->toContain('main', 'settings');
});

test('guests are redirected from the dashboard to the login page', function () {
    Tenant::forget();

    $this->get('/dashboard')->assertRedirect('/login');
});

test('the login and register pages render', function () {
    Tenant::forget();

    // Se comprueba `autocomplete`, no `name`: Flux deriva el atributo `name`
    // del `wire:model`, así que el campo sale como `form.email`, y ese mismo
    // nombre es el que usa para localizar el error de credenciales. Forzarlo a
    // `email` dejaría el formulario mudo. `autocomplete` es además lo que
    // gobierna de verdad a los gestores de contraseñas.
    $this->get('/login')->assertOk()
        ->assertSee('autocomplete="username"', false)
        ->assertSee('autocomplete="current-password"', false);

    $this->get('/register')->assertOk();
});

test('an account owner reaches the dashboard', function () {
    $this->actingAs($this->owner)->get('/dashboard')->assertOk();
});

test('the owner holds their role and its permissions inside the account', function () {
    expect($this->owner->rolesForAccount($this->account)->pluck('name')->all())->toBe(['customer-admin'])
        ->and($this->owner->hasPermissionInAccount($this->account, 'users.create'))->toBeTrue()
        ->and($this->owner->hasPermissionInAccount($this->account, 'accounts.delete'))->toBeFalse();
});

test('the owner reaches the screens their permissions unlock', function () {
    $this->actingAs($this->owner)->get('/users')->assertOk();
    $this->actingAs($this->owner)->get('/roles')->assertOk();
    $this->actingAs($this->owner)->get('/invitations')->assertOk();
});

test('a viewer is refused the user manager', function () {
    $viewer = User::factory()->create(['account_id' => $this->account->getKey()]);
    $viewer->accounts()->syncWithoutDetaching([$this->account->getKey()]);

    Tenant::runFor($this->account, fn () => $viewer->syncRoles(['customer-viewer']));

    $this->actingAs($viewer)->get('/users')->assertForbidden();
});

test('the navigation is built from the database and filtered by permission', function () {
    expect(Menu::tree('main', $this->owner)->pluck('key')->all())
        ->toContain('dashboard', 'users', 'invitations');
});

test('tenant data does not leak between accounts', function () {
    $other = Account::factory()->create();

    $theirs = Tenant::runFor($other, fn (): UserInvite => UserInvite::create([
        'email' => 'someone@example.com',
        'token' => Str::random(64),
        'expires_at' => now()->addWeek(),
    ]));

    Tenant::runFor($this->account, function () use ($theirs): void {
        expect(UserInvite::find($theirs->getKey()))->toBeNull();
    });
});
