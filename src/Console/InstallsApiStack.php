<?php

namespace Turksabsw\MogemBreeze\Console

use Illuminate\Filesystem\Filesystem;

trait InstallsApiStack
{
    /**
     * Install the API Breeze stack.
     *
     * @return int|null
     */
    protected function installApiStack()
    {
        $this->runCommands(['php artisan install:api']);

        $files = new Filesystem;

        // Controllers...
        $files->ensureDirectoryExists(base_path('modules/auth/app/Http/Controllers/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Controllers/Auth', base_path('modules/auth/app/Http/Controllers/Auth'));

        // Middleware...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Middleware', base_path('modules/auth/app/Http/Middleware'));

        $this->installMiddlewareAliases([
            'verified' => '\App\Http\Middleware\EnsureEmailIsVerified::class',
        ]);

        $this->installMiddleware([
            '\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class',
        ], 'api', 'prepend');

        // Requests...
        $files->ensureDirectoryExists(base_path('modules/auth/app/Http/Requests/Auth'));
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Http/Requests/Auth', base_path('modules/auth/app/Http/Requests/Auth'));

        // Providers...
        $files->copyDirectory(__DIR__.'/../../stubs/api/app/Providers', base_path('modules/auth/app/Providers'));

        // Routes...
        copy(__DIR__.'/../../stubs/api/routes/api.php', base_path('modules/auth/routes/api.php'));
        copy(__DIR__.'/../../stubs/api/routes/web.php', base_path('modules/auth/routes/web.php'));
        copy(__DIR__.'/../../stubs/api/routes/auth.php', base_path('modules/auth/routes/auth.php'));

        // Configuration...
        $files->copyDirectory(__DIR__.'/../../stubs/api/config', base_path('modules/auth/config'));

        // Environment...
        if (! $files->exists(base_path('.env'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }

        file_put_contents(
            base_path('.env'),
            preg_replace('/APP_URL=(.*)/', 'APP_URL=http://localhost:8000'.PHP_EOL.'FRONTEND_URL=http://localhost:3000', file_get_contents(base_path('.env')))
        );

        // Tests...
        if (! $this->installTests()) {
            return 1;
        }

        $files->delete(base_path('tests/Feature/Auth/PasswordConfirmationTest.php'));

        // Cleaning...
        $this->removeScaffoldingUnnecessaryForApis();

        $this->components->info('Breeze scaffolding installed successfully.');
    }

    /**
     * Remove any application scaffolding that isn't needed for APIs.
     *
     * @return void
     */
    protected function removeScaffoldingUnnecessaryForApis()
    {
        $files = new Filesystem;

        // Remove frontend related files...
        $files->delete(base_path('modules/auth/package.json'));
        $files->delete(base_path('modules/auth/vite.config.js'));
        $files->delete(base_path('modules/auth/tailwind.config.js'));
        $files->delete(base_path('modules/auth/postcss.config.js'));

        // Remove Laravel "welcome" view...
        $files->delete(base_path('modules/auth/resources/views/welcome.blade.php'));
        $files->put(base_path('modules/auth/resources/views/.gitkeep'), PHP_EOL);

        // Remove CSS and JavaScript directories...
        $files->deleteDirectory(base_path('modules/auth/resources/css'));
        $files->deleteDirectory(base_path('modules/auth/resources/js'));

        // Remove the "node_modules" directory and associated lock files...
        static::flushNodeModules();
    }
}
