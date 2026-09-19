<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Sets up a project created from this starter kit.
 *
 * The interesting decision is made here: whether the project keeps `k2labs/base-tenant`
 * as an updatable dependency, or takes ownership of the code and drops the
 * package the way Laravel's own starter kits work.
 */
class InstallKitCommand extends Command
{
    protected $signature = 'kit:install
        {--own : Take ownership of the code and remove the package, without asking}
        {--dependency : Keep the package as a dependency, without asking}';

    protected $description = 'Set up this project and choose how it relates to k2labs/base-tenant';

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Base Tenant starter kit');
        $this->newLine();

        $ownTheCode = $this->resolveMode();

        if ($ownTheCode === null) {
            $this->components->warn('Cancelled.');

            return self::FAILURE;
        }

        if ($this->call('k2labs-base:install') !== self::SUCCESS) {
            $this->components->error('The package installer did not finish. Fix the problem and run `php artisan kit:install` again.');

            return self::FAILURE;
        }

        if ($ownTheCode) {
            return $this->takeOwnership();
        }

        $this->showDependencySummary();

        return self::SUCCESS;
    }

    /**
     * @return bool|null  True to own the code, false to stay on the package,
     *                    null if the user backed out.
     */
    protected function resolveMode(): ?bool
    {
        if ($this->option('own')) {
            return true;
        }

        if ($this->option('dependency')) {
            return false;
        }

        $this->line('  This project can relate to <options=bold>k2labs/base-tenant</> in two ways.');
        $this->newLine();
        $this->line('  <options=bold>As a dependency</> — tenancy, permissions, navigation and billing');
        $this->line('  live in the package. You get fixes and new features with <fg=cyan>composer update</>.');
        $this->line('  You can still take ownership later, at any time.');
        $this->newLine();
        $this->line('  <options=bold>As your own code</> — everything is copied into this project and the');
        $this->line('  package is removed. Nothing depends on us any more, and no updates arrive.');
        $this->line('  This is how Laravel\'s own starter kits work.');
        $this->newLine();

        return match ($this->choice(
            'How should this project use k2labs/base-tenant?',
            [
                'Keep it as a dependency (recommended, reversible)',
                'Copy the code in and remove the package',
                'Cancel',
            ],
            0
        )) {
            'Keep it as a dependency (recommended, reversible)' => false,
            'Copy the code in and remove the package' => true,
            default => null,
        };
    }

    protected function takeOwnership(): int
    {
        $this->newLine();
        $this->components->info('Copying the package into this project…');

        if ($this->call('k2labs-base:scaffold', ['--overwrite' => true]) !== self::SUCCESS) {
            $this->components->error('Scaffolding failed. The package is still installed and working; nothing was lost.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Removing the package…');

        if ($this->call('k2labs-base:eject', ['--force' => true]) !== self::SUCCESS) {
            $this->components->warn('The code was copied but the package was not removed.');
            $this->line('  Run <fg=cyan>php artisan k2labs-base:eject</> when you are ready.');

            return self::SUCCESS;
        }

        $this->showOwnershipSummary();

        return self::SUCCESS;
    }

    protected function showDependencySummary(): void
    {
        $this->newLine();
        $this->components->info('Ready.');
        $this->newLine();
        $this->line('  <options=bold>k2labs/base-tenant</> stays a dependency. To update it:');
        $this->line('    <fg=cyan>composer update k2labs/base-tenant</>');
        $this->line('    <fg=cyan>php artisan migrate</>');
        $this->line('    <fg=cyan>php artisan k2labs-base:sync-roles && php artisan k2labs-base:sync-menus</>');
        $this->newLine();
        $this->line('  To take ownership of the code later:');
        $this->line('    <fg=cyan>php artisan k2labs-base:scaffold --dry-run</>');
        $this->newLine();
        $this->line('  <fg=gray>See vendor/k2labs/base-tenant/docs/SCAFFOLD-EJECT.md</>');
        $this->newLine();
    }

    protected function showOwnershipSummary(): void
    {
        $this->newLine();
        $this->components->info('Ready. The code is yours.');
        $this->newLine();
        $this->line('  Run these once, then start building:');
        $this->line('    <fg=cyan>composer update && composer dump-autoload</>');
        $this->line('    <fg=cyan>php artisan optimize:clear</>');
        $this->newLine();
        $this->line('  <fg=gray>Everything lives in app/, resources/views/tenant/ and routes/tenant/.</>');
        $this->line('  <fg=gray>app/Providers/TenancyServiceProvider.php wires it together.</>');
        $this->newLine();
    }
}
