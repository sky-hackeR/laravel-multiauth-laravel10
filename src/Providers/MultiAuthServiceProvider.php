<?php

namespace SkyHackeR\MultiAuth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use SkyHackeR\MultiAuth\Console\Commands\MultiAuthInstallCommand;

class MultiAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MultiAuthInstallCommand::class,
            ]);
        }

        $this->registerIdentityRoutes();
    }

    protected function registerIdentityRoutes()
    {
        $routePath = base_path('routes');
        if (is_dir($routePath)) {
            foreach (scandir($routePath) as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($name, ['web', 'api', 'console', 'channels', '.', '..'])) {
                    Route::middleware('web')->group($routePath . '/' . $file);
                }
            }
        }
    }
}