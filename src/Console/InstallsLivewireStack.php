<?php

namespace Turksabsw\MogemBreeze\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

trait InstallsLivewireStack
{
    /**
     * Install the Livewire Breeze stack.
     *
     * @return int|null
     */
    protected function installLivewireStack($functional = false)
    {
        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@tailwindcss/forms' => '^0.5.2',
                'autoprefixer' => '^10.4.2',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.1.0',
            ] + $packages;
        });

        // Install Livewire...
        if (! $this->requireComposerPackages(['livewire/livewire:^3.6.4', 'livewire/volt:^1.7.0'])) {
            return 1;
        }

        // Install Volt...
        (new Process([$this->phpBinary(), 'artisan', 'volt:install'], base_path()))
            ->setTimeout(null)
            ->run();

        // Controllers
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Controllers/Auth'));
        (new Filesystem)->copy(
            __DIR__.'/../../stubs/default/app/Http/Controllers/Auth/VerifyEmailController.php',
            base_path('modules/auth/app/Http/Controllers/Auth/VerifyEmailController.php'),
        );

        // Views...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/views'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire-common/resources/views', base_path('modules/auth/resources/views'));

        // Livewire Components...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/views/livewire'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/'
            .($functional ? 'livewire-functional' : 'livewire')
            .'/resources/views/livewire', base_path('modules/auth/resources/views/livewire'));

        // Views Components...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/views/components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/resources/views/components', base_path('modules/auth/resources/views/components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire-common/resources/views/components', base_path('modules/auth/resources/views/components'));

        // Views Layouts...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/views/layouts'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire-common/resources/views/layouts', base_path('modules/auth/resources/views/layouts'));

        // Components...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/View/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/View/Components', base_path('modules/auth/app/View/Components'));

        // Actions...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Livewire/Actions'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire-common/app/Livewire/Actions', base_path('modules/auth/app/Livewire/Actions'));

        // Forms...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Livewire/Forms'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/livewire-common/app/Livewire/Forms', base_path('modules/auth/app/Livewire/Forms'));

        // Dark mode...
        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('modules/auth/resources/views'))
                ->name('*.blade.php')
                ->notPath('livewire/welcome/navigation.blade.php')
                ->notName('welcome.blade.php')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        copy(__DIR__.'/../../stubs/livewire-common/routes/web.php', base_path('modules/auth/routes/web.php'));
        copy(__DIR__.'/../../stubs/livewire-common/routes/auth.php', base_path('modules/auth/routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/tailwind.config.js', base_path('modules/auth/tailwind.config.js'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('modules/auth/postcss.config.js'));
        copy(__DIR__.'/../../stubs/default/vite.config.js', base_path('modules/auth/vite.config.js'));
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', base_path('modules/auth/resources/css/app.css'));

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('modules/auth/pnpm-lock.yaml'))) {
            $this->runCommands(['cd modules/auth && pnpm install', 'cd modules/auth && pnpm run build']);
        } elseif (file_exists(base_path('modules/auth/yarn.lock'))) {
            $this->runCommands(['cd modules/auth && yarn install', 'cd modules/auth && yarn run build']);
        } elseif (file_exists(base_path('modules/auth/bun.lock')) || file_exists(base_path('modules/auth/bun.lockb'))) {
            $this->runCommands(['cd modules/auth && bun install', 'cd modules/auth && bun run build']);
        } elseif (file_exists(base_path('modules/auth/deno.lock'))) {
            $this->runCommands(['cd modules/auth && deno install', 'cd modules/auth && deno task build']);
        } else {
            $this->runCommands(['cd modules/auth && npm install', 'cd modules/auth && npm run build']);
        }

        $this->components->info('Livewire scaffolding installed successfully.');
    }
}
