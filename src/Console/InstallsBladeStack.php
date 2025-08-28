<?php

namespace Turksabsw\MogemBreeze\Console

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallsBladeStack
{
    /**
     * Install the Blade Breeze stack.
     *
     * @return int|null
     */
    protected function installBladeStack()
    {
        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@tailwindcss/forms' => '^0.5.2',
                'alpinejs' => '^3.4.2',
                'autoprefixer' => '^10.4.2',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.1.0',
            ] + $packages;
        });

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(\base_path('modules/auth/app/Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Controllers', \base_path('modules/auth/app/Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(\base_path('modules/auth/app/Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', \base_path('modules/auth/app/Http/Requests'));

        // Views...
        (new Filesystem)->ensureDirectoryExists(\base_path('modules/auth/resources/views'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/resources/views', \base_path('modules/auth/resources/views'));

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(\base_path('modules/auth/resources/views'))
                ->name('*.blade.php')
                ->notPath('livewire/welcome/navigation.blade.php')
                ->notName('welcome.blade.php')
            );
        }

        // Components...
        (new Filesystem)->ensureDirectoryExists(\base_path('modules/auth/app/View/Components'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/View/Components', \base_path('modules/auth/app/View/Components'));

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        // Routes...
        copy(__DIR__.'/../../stubs/default/routes/web.php', \base_path('modules/auth/routes/web.php'));
        copy(__DIR__.'/../../stubs/default/routes/auth.php', \base_path('modules/auth/routes/auth.php'));

        // "Dashboard" Route...
        $this->replaceInFile('/home', '/dashboard', \base_path('modules/auth/resources/views/welcome.blade.php'));
        $this->replaceInFile('Home', 'Dashboard', \base_path('modules/auth/resources/views/welcome.blade.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/tailwind.config.js', \base_path('modules/auth/tailwind.config.js'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', \base_path('modules/auth/postcss.config.js'));
        copy(__DIR__.'/../../stubs/default/vite.config.js', \base_path('modules/auth/vite.config.js'));
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', \base_path('modules/auth/resources/css/app.css'));
        copy(__DIR__.'/../../stubs/default/resources/js/app.js', \base_path('modules/auth/resources/js/app.js'));

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

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }
}
