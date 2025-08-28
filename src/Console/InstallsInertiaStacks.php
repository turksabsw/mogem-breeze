<?php

namespace Turksabsw\MogemBreeze\Console;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait InstallsInertiaStacks
{
    /**
     * Install the Inertia Vue Breeze stack.
     *
     * @return int|null
     */
    protected function installInertiaVueStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^2.0', 'laravel/sanctum:^4.0', 'tightenco/ziggy:^2.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@inertiajs/vue3' => '^2.0.0',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-vue' => '^6.0.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.2.1',
                'vue' => '^3.4.0',
            ] + $packages;
        });

        if ($this->option('typescript')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    'typescript' => '^5.6.3',
                    'vue-tsc' => '^2.0.24',
                ] + $packages;
            });
        }

        if ($this->option('eslint')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    'eslint' => '^8.57.0',
                    'eslint-plugin-vue' => '^9.23.0',
                    '@rushstack/eslint-patch' => '^1.8.0',
                    '@vue/eslint-config-prettier' => '^9.0.0',
                    'prettier' => '^3.3.0',
                    'prettier-plugin-organize-imports' => '^4.0.0',
                    'prettier-plugin-tailwindcss' => '^0.6.5',
                ] + $packages;
            });

            if ($this->option('typescript')) {
                $this->updateNodePackages(function ($packages) {
                    return [
                        '@vue/eslint-config-typescript' => '^13.0.0',
                    ] + $packages;
                });

                $this->updateNodeScripts(function ($scripts) {
                    return $scripts + [
                        'lint' => 'eslint resources/js --ext .js,.ts,.vue --ignore-path .gitignore --fix',
                    ];
                });

                copy(__DIR__.'/../../stubs/inertia-vue-ts/.eslintrc.cjs', base_path('modules/auth/.eslintrc.cjs'));
            } else {
                $this->updateNodeScripts(function ($scripts) {
                    return $scripts + [
                        'lint' => 'eslint resources/js --ext .js,.vue --ignore-path .gitignore --fix',
                    ];
                });

                copy(__DIR__.'/../../stubs/inertia-vue/.eslintrc.cjs', base_path('modules/auth/.eslintrc.cjs'));
            }

            copy(__DIR__.'/../../stubs/inertia-common/.prettierrc', base_path('modules/auth/.prettierrc'));
        }

        // Providers...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Providers', base_path('modules/auth/app/Providers'));

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Http/Controllers', base_path('modules/auth/app/Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', base_path('modules/auth/app/Http/Requests'));

        // Middleware...
        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Middleware'));
        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', base_path('modules/auth/app/Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-vue/resources/views/app.blade.php', base_path('modules/auth/resources/views/app.blade.php'));

        @unlink(base_path('modules/auth/resources/views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Components'));
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Pages'));

        if ($this->option('typescript')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Components', base_path('modules/auth/resources/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Layouts', base_path('modules/auth/resources/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/Pages', base_path('modules/auth/resources/js/Pages'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/types', base_path('modules/auth/resources/js/types'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Components', base_path('modules/auth/resources/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Layouts', base_path('modules/auth/resources/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-vue/resources/js/Pages', base_path('modules/auth/resources/js/Pages'));
        }

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('modules/auth/resources/js'))
                ->name('*.vue')
                ->notName('Welcome.vue')
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        if ($this->option('pest')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/pest-tests/Feature', base_path('modules/auth/tests/Feature'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/tests/Feature', base_path('modules/auth/tests/Feature'));
        }

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('modules/auth/routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('modules/auth/routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', base_path('modules/auth/resources/css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('modules/auth/postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('modules/auth/tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-vue/vite.config.js', base_path('modules/auth/vite.config.js'));

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-vue-ts/tsconfig.json', base_path('modules/auth/tsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/app.ts', base_path('modules/auth/resources/js/app.ts'));

            if (file_exists(base_path('modules/auth/resources/js/app.js'))) {
                unlink(base_path('modules/auth/resources/js/app.js'));
            }

            if (file_exists(base_path('modules/auth/resources/js/bootstrap.js'))) {
                rename(base_path('modules/auth/resources/js/bootstrap.js'), base_path('modules/auth/resources/js/bootstrap.ts'));
            }

            $this->replaceInFile('"vite build', '"vue-tsc && vite build', base_path('modules/auth/package.json'));
            $this->replaceInFile('.js', '.ts', base_path('modules/auth/vite.config.js'));
            $this->replaceInFile('.js', '.ts', base_path('modules/auth/resources/views/app.blade.php'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('modules/auth/jsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-vue/resources/js/app.js', base_path('modules/auth/resources/js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installInertiaVueSsrStack();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('modules/auth/pnpm-lock.yaml'))) {
            $this->runCommands(['cd modules/auth && pnpm install', 'cd modules/auth && pnpm run build']);
        } elseif (file_exists(base_path('modules/auth/yarn.lock'))) {
            $this->runCommands(['cd modules/auth && yarn install', 'cd modules/auth && yarn run build']);
        } elseif (file_exists(base_path('modules/auth/bun.lock')) || file_exists(base_path('modules/auth/bun.lockb'))) {
            $this->runCommands(['cd modules/auth && bun install', 'cd modules/auth && bun run build']);
        } else {
            $this->runCommands(['cd modules/auth && npm install', 'cd modules/auth && npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Install the Inertia Vue SSR stack into the application.
     *
     * @return void
     */
    protected function installInertiaVueSsrStack()
    {
        $this->updateNodePackages(function ($packages) {
            return [
                '@vue/server-renderer' => '^3.4.0',
            ] + $packages;
        });

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-vue-ts/resources/js/ssr.ts', base_path('modules/auth/resources/js/ssr.ts'));
            $this->replaceInFile("input: 'resources/js/app.ts',", "input: 'resources/js/app.ts',".PHP_EOL."            ssr: 'resources/js/ssr.ts',", base_path('modules/auth/vite.config.js'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-vue/resources/js/ssr.js', base_path('modules/auth/resources/js/ssr.js'));
            $this->replaceInFile("input: 'resources/js/app.js',", "input: 'resources/js/app.js',".PHP_EOL."            ssr: 'resources/js/ssr.js',", base_path('modules/auth/vite.config.js'));
        }

        $this->configureZiggyForSsr();

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('modules/auth/package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('modules/auth/.gitignore'));
    }

    /**
     * Install the Inertia React Breeze stack.
     *
     * @return int|null
     */
    protected function installInertiaReactStack()
    {
        // Install Inertia...
        if (! $this->requireComposerPackages(['inertiajs/inertia-laravel:^2.0', 'laravel/sanctum:^4.0', 'tightenco/ziggy:^2.0'])) {
            return 1;
        }

        // NPM Packages...
        $this->updateNodePackages(function ($packages) {
            return [
                '@headlessui/react' => '^2.0.0',
                '@inertiajs/react' => '^2.0.0',
                '@tailwindcss/forms' => '^0.5.3',
                '@vitejs/plugin-react' => '^4.2.0',
                'autoprefixer' => '^10.4.12',
                'postcss' => '^8.4.31',
                'tailwindcss' => '^3.2.1',
                'react' => '^18.2.0',
                'react-dom' => '^18.2.0',
            ] + $packages;
        });

        if ($this->option('typescript')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    '@types/node' => '^18.13.0',
                    '@types/react' => '^18.0.28',
                    '@types/react-dom' => '^18.0.10',
                    'typescript' => '^5.0.2',
                ] + $packages;
            });
        }

        if ($this->option('eslint')) {
            $this->updateNodePackages(function ($packages) {
                return [
                    'eslint' => '^8.57.0',
                    'eslint-plugin-react' => '^7.34.4',
                    'eslint-plugin-react-hooks' => '^4.6.2',
                    'eslint-plugin-prettier' => '^5.1.3',
                    'eslint-config-prettier' => '^9.1.0',
                    'prettier' => '^3.3.0',
                    'prettier-plugin-organize-imports' => '^4.0.0',
                    'prettier-plugin-tailwindcss' => '^0.6.5',
                ] + $packages;
            });

            if ($this->option('typescript')) {
                $this->updateNodePackages(function ($packages) {
                    return [
                        '@typescript-eslint/eslint-plugin' => '^7.16.0',
                        '@typescript-eslint/parser' => '^7.16.0',
                    ] + $packages;
                });

                $this->updateNodeScripts(function ($scripts) {
                    return $scripts + [
                        'lint' => 'eslint resources/js --ext .js,.jsx,.ts,.tsx --ignore-path .gitignore --fix',
                    ];
                });

                copy(__DIR__.'/../../stubs/inertia-react-ts/.eslintrc.json', base_path('modules/auth/.eslintrc.json'));
            } else {
                $this->updateNodeScripts(function ($scripts) {
                    return $scripts + [
                        'lint' => 'eslint resources/js --ext .js,.jsx --ignore-path .gitignore --fix',
                    ];
                });

                copy(__DIR__.'/../../stubs/inertia-react/.eslintrc.json', base_path('modules/auth/.eslintrc.json'));
            }

            copy(__DIR__.'/../../stubs/inertia-common/.prettierrc', base_path('modules/auth/.prettierrc'));
        }

        // Providers...
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Providers', base_path('modules/auth/app/Providers'));

        // Controllers...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Controllers'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/app/Http/Controllers', base_path('modules/auth/app/Http/Controllers'));

        // Requests...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Requests'));
        (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/default/app/Http/Requests', base_path('modules/auth/app/Http/Requests'));

        // Middleware...
        $this->installMiddleware([
            '\App\Http\Middleware\HandleInertiaRequests::class',
            '\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class',
        ]);

        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/app/Http/Middleware'));
        copy(__DIR__.'/../../stubs/inertia-common/app/Http/Middleware/HandleInertiaRequests.php', base_path('modules/auth/app/Http/Middleware/HandleInertiaRequests.php'));

        // Views...
        copy(__DIR__.'/../../stubs/inertia-react/resources/views/app.blade.php', base_path('modules/auth/resources/views/app.blade.php'));

        @unlink(base_path('modules/auth/resources/views/welcome.blade.php'));

        // Components + Pages...
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Components'));
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Layouts'));
        (new Filesystem)->ensureDirectoryExists(base_path('modules/auth/resources/js/Pages'));

        if ($this->option('typescript')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Components', base_path('modules/auth/resources/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Layouts', base_path('modules/auth/resources/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/Pages', base_path('modules/auth/resources/js/Pages'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react-ts/resources/js/types', base_path('modules/auth/resources/js/types'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Components', base_path('modules/auth/resources/js/Components'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Layouts', base_path('modules/auth/resources/js/Layouts'));
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-react/resources/js/Pages', base_path('modules/auth/resources/js/Pages'));
        }

        if (! $this->option('dark')) {
            $this->removeDarkClasses((new Finder)
                ->in(base_path('modules/auth/resources/js'))
                ->name(['*.jsx', '*.tsx'])
                ->notName(['Welcome.jsx', 'Welcome.tsx'])
            );
        }

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        if ($this->option('pest')) {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/pest-tests/Feature', base_path('modules/auth/tests/Feature'));
        } else {
            (new Filesystem)->copyDirectory(__DIR__.'/../../stubs/inertia-common/tests/Feature', base_path('modules/auth/tests/Feature'));
        }

        // Routes...
        copy(__DIR__.'/../../stubs/inertia-common/routes/web.php', base_path('modules/auth/routes/web.php'));
        copy(__DIR__.'/../../stubs/inertia-common/routes/auth.php', base_path('modules/auth/routes/auth.php'));

        // Tailwind / Vite...
        copy(__DIR__.'/../../stubs/default/resources/css/app.css', base_path('modules/auth/resources/css/app.css'));
        copy(__DIR__.'/../../stubs/default/postcss.config.js', base_path('modules/auth/postcss.config.js'));
        copy(__DIR__.'/../../stubs/inertia-common/tailwind.config.js', base_path('modules/auth/tailwind.config.js'));
        copy(__DIR__.'/../../stubs/inertia-react/vite.config.js', base_path('modules/auth/vite.config.js'));

        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-react-ts/tsconfig.json', base_path('modules/auth/tsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/app.tsx', base_path('modules/auth/resources/js/app.tsx'));

            if (file_exists(base_path('modules/auth/resources/js/bootstrap.js'))) {
                rename(base_path('modules/auth/resources/js/bootstrap.js'), base_path('modules/auth/resources/js/bootstrap.ts'));
            }

            $this->replaceInFile('"vite build', '"tsc && vite build', base_path('modules/auth/package.json'));
            $this->replaceInFile('.jsx', '.tsx', base_path('modules/auth/vite.config.js'));
            $this->replaceInFile('.jsx', '.tsx', base_path('modules/auth/resources/views/app.blade.php'));
            $this->replaceInFile('.vue', '.tsx', base_path('modules/auth/tailwind.config.js'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-common/jsconfig.json', base_path('modules/auth/jsconfig.json'));
            copy(__DIR__.'/../../stubs/inertia-react/resources/js/app.jsx', base_path('modules/auth/resources/js/app.jsx'));

            $this->replaceInFile('.vue', '.jsx', base_path('modules/auth/tailwind.config.js'));
        }

        if (file_exists(base_path('modules/auth/resources/js/app.js'))) {
            unlink(base_path('modules/auth/resources/js/app.js'));
        }

        if ($this->option('ssr')) {
            $this->installInertiaReactSsrStack();
        }

        $this->components->info('Installing and building Node dependencies.');

        if (file_exists(base_path('modules/auth/pnpm-lock.yaml'))) {
            $this->runCommands(['cd modules/auth && pnpm install', 'cd modules/auth && pnpm run build']);
        } elseif (file_exists(base_path('modules/auth/yarn.lock'))) {
            $this->runCommands(['cd modules/auth && yarn install', 'cd modules/auth && yarn run build']);
        } elseif (file_exists(base_path('modules/auth/bun.lockb')) || file_exists(base_path('modules/auth/bun.lock'))) {
            $this->runCommands(['cd modules/auth && bun install', 'cd modules/auth && bun run build']);
        } elseif (file_exists(base_path('modules/auth/deno.lock'))) {
            $this->runCommands(['cd modules/auth && deno install', 'cd modules/auth && deno task build']);
        } else {
            $this->runCommands(['cd modules/auth && npm install', 'cd modules/auth && npm run build']);
        }

        $this->line('');
        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Install the Inertia React SSR stack into the application.
     *
     * @return void
     */
    protected function installInertiaReactSsrStack()
    {
        if ($this->option('typescript')) {
            copy(__DIR__.'/../../stubs/inertia-react-ts/resources/js/ssr.tsx', base_path('modules/auth/resources/js/ssr.tsx'));
            $this->replaceInFile("input: 'resources/js/app.tsx',", "input: 'resources/js/app.tsx',".PHP_EOL."            ssr: 'resources/js/ssr.tsx',", base_path('modules/auth/vite.config.js'));
            $this->configureReactHydrateRootForSsr(base_path('modules/auth/resources/js/app.tsx'));
        } else {
            copy(__DIR__.'/../../stubs/inertia-react/resources/js/ssr.jsx', base_path('modules/auth/resources/js/ssr.jsx'));
            $this->replaceInFile("input: 'resources/js/app.jsx',", "input: 'resources/js/app.jsx',".PHP_EOL."            ssr: 'resources/js/ssr.jsx',", base_path('modules/auth/vite.config.js'));
            $this->configureReactHydrateRootForSsr(base_path('modules/auth/resources/js/app.jsx'));
        }

        $this->configureZiggyForSsr();

        $this->replaceInFile('vite build', 'vite build && vite build --ssr', base_path('modules/auth/package.json'));
        $this->replaceInFile('/node_modules', '/bootstrap/ssr'.PHP_EOL.'/node_modules', base_path('modules/auth/.gitignore'));
    }

    /**
     * Configure the application JavaScript file to utilize hydrateRoot for SSR.
     *
     * @param  string  $path
     * @return void
     */
    protected function configureReactHydrateRootForSsr($path)
    {
        $this->replaceInFile(
            <<<'EOT'
            import { createRoot } from 'react-dom/client';
            EOT,
            <<<'EOT'
            import { createRoot, hydrateRoot } from 'react-dom/client';
            EOT,
            $path
        );

        $this->replaceInFile(
            <<<'EOT'
                    const root = createRoot(el);

                    root.render(<App {...props} />);
            EOT,
            <<<'EOT'
                    if (import.meta.env.SSR) {
                        hydrateRoot(el, <App {...props} />);
                        return;
                    }

                    createRoot(el).render(<App {...props} />);
            EOT,
            $path
        );
    }

    /**
     * Configure Ziggy for SSR.
     *
     * @return void
     */
    protected function configureZiggyForSsr()
    {
        $this->replaceInFile(
            <<<'EOT'
            use Inertia\Middleware;
            EOT,
            <<<'EOT'
            use Inertia\Middleware;
            use Tighten\Ziggy\Ziggy;
            EOT,
            base_path('modules/auth/app/Http/Middleware/HandleInertiaRequests.php')
        );

        $this->replaceInFile(
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
            EOT,
            <<<'EOT'
                        'auth' => [
                            'user' => $request->user(),
                        ],
                        'ziggy' => fn () => [
                            ...(new Ziggy)->toArray(),
                            'location' => $request->url(),
                        ],
            EOT,
            base_path('modules/auth/app/Http/Middleware/HandleInertiaRequests.php')
        );

        if ($this->option('typescript')) {
            $this->replaceInFile(
                <<<'EOT'
                export interface User {
                EOT,
                <<<'EOT'
                import { Config } from 'ziggy-js';

                export interface User {
                EOT,
                base_path('modules/auth/resources/js/types/index.d.ts')
            );

            $this->replaceInFile(
                <<<'EOT'
                    auth: {
                        user: User;
                    };
                EOT,
                <<<'EOT'
                    auth: {
                        user: User;
                    };
                    ziggy: Config & { location: string };
                EOT,
                base_path('modules/auth/resources/js/types/index.d.ts')
            );
        }
    }
}
