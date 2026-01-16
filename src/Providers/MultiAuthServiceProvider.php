<?php

namespace SkyHackeR\MultiAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use SkyHackeR\MultiAuth\Console\Commands\MultiAuthInstallCommand;

class MultiAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MultiAuthInstallCommand::class,
            ]);
        }

        $this->registerIdentityRoutes();
    }

    /**
     * Automatically register custom identity route files.
     */
    protected function registerIdentityRoutes()
    {
        $routePath = base_path('routes');

        if (File::isDirectory($routePath)) {
            // Get all PHP files in the routes directory
            $files = File::files($routePath);

            foreach ($files as $file) {
                $filename = $file->getFilenameWithoutExtension();
                
                // Skip standard Laravel route files
                $standardRoutes = ['web', 'api', 'console', 'channels'];

                if (!in_array($filename, $standardRoutes)) {
                    Route::middleware('web')
                        ->group($file->getPathname());
                }
            }
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        //
    }
}