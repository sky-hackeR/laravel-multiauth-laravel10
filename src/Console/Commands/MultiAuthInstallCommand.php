<?php

namespace SkyHackeR\MultiAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MultiAuthInstallCommand extends Command
{
    protected $signature = 'laravel-multi-auth:install {name} {--f|force}';
    protected $description = 'Generate a professional modular multi-auth scaffold';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lower = Str::lower($name);

        $this->info("🚀 SkyHackeR Engine: Building '{$name}' Identity...");

        // 1. Generate Files
        $this->generateModel($name);
        $this->generateMigration($name, Str::plural($lower));
        $this->generateControllers($name, $lower);
        $this->generateMiddleware($name, $lower);
        $this->generateViews($name, $lower);
        $this->generateRoutes($name, $lower);

        // 2. Physical File Injections (The Surgery)
        $this->updateAuthConfig($name, $lower);
        $this->registerMiddlewareInKernel($name);

        $this->info("✅ '{$name}' Identity is now physically registered and ready!");
    }

    /**
     * Physical injection into Kernel.php
     */
    protected function registerMiddlewareInKernel($name)
    {
        $kernelPath = app_path('Http/Kernel.php');
        if (!File::exists($kernelPath)) {
            $this->error("Kernel.php not found at {$kernelPath}");
            return;
        }

        $content = File::get($kernelPath);
        $lower = strtolower($name);
        $alias = "auth.{$lower}";
        $class = "\\App\\Http\\Middleware\\RedirectIf{$name}::class";

        // Check if alias already exists to prevent duplicates
        if (!str_contains($content, "'{$alias}'")) {
            // Locate the end of the middlewareAliases array
            // We look for a common default entry to anchor our injection
            $search = "'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,";
            
            if (str_contains($content, $search)) {
                $replace = $search . "\n        '{$alias}' => {$class},";
                $content = str_replace($search, $replace, $content);
                File::put($kernelPath, $content);
                $this->info("✅ Physically added '{$alias}' to Kernel.php");
            } else {
                $this->warn("⚠️ Could not find anchor in Kernel.php. Please add '{$alias}' manually.");
            }
        }
    }

    protected function updateAuthConfig($name, $lower)
    {
        $path = config_path('auth.php');
        $config = File::get($path);

        // Inject Guard
        $guard = "'{$lower}' => [ 'driver' => 'session', 'provider' => '{$lower}s' ],";
        if (!Str::contains($config, "'{$lower}' =>")) {
            $config = str_replace("'guards' => [", "'guards' => [\n        " . $guard, $config);
        }

        // Inject Provider
        $provider = "'{$lower}s' => [ 'driver' => 'eloquent', 'model' => App\Models\\{$name}::class ],";
        if (!Str::contains($config, "'{$lower}s' =>")) {
            $config = str_replace("'providers' => [", "'providers' => [\n        " . $provider, $config);
        }

        File::put($path, $config);
    }

    // --- Scaffolding Methods ---

    protected function generateControllers($name, $lower) {
        $path = app_path("Http/Controllers/{$name}/Auth");
        File::ensureDirectoryExists($path);
        foreach (['LoginController', 'RegisterController', 'ForgotPasswordController', 'ResetPasswordController'] as $file) {
            $this->copyStub("Controllers/{$file}.stub", "{$path}/{$file}.php", $name, $lower);
        }
    }

    protected function generateViews($name, $lower) {
        $base = resource_path("views/{$lower}");
        File::ensureDirectoryExists("{$base}/auth/passwords");
        File::ensureDirectoryExists("{$base}/layout");
        $viewMap = [
            'views/home.stub' => 'home.blade.php',
            'views/layout/app.stub' => 'layout/app.blade.php',
            'views/auth/login.stub' => 'auth/login.blade.php',
            'views/auth/register.stub' => 'auth/register.blade.php',
            'views/auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
            'views/auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        ];
        foreach ($viewMap as $stub => $file) { $this->copyStub($stub, "{$base}/{$file}", $name, $lower); }
    }

    protected function generateModel($name) { 
        $this->copyStub("Model.stub", app_path("Models/{$name}.php"), $name); 
    }

    protected function generateMiddleware($name, $lower) { 
        $this->copyStub("Middleware.stub", app_path("Http/Middleware/RedirectIf{$name}.php"), $name, $lower); 
    }

    protected function generateRoutes($name, $lower) { 
        $this->copyStub("routes.stub", base_path("routes/{$lower}.php"), $name, $lower); 
    }

    protected function generateMigration($name, $plural) {
        $file = date('Y_m_d_His') . "_create_{$plural}_table.php";
        $content = str_replace(['{{TableName}}', '{{ClassName}}'], [$plural, $name], File::get(__DIR__ . '/../../stubs/Migration.stub'));
        File::put(database_path("migrations/{$file}"), $content);
    }

    private function copyStub($stubName, $targetPath, $name, $lower = '') {
        $content = File::get(__DIR__ . "/../../stubs/{$stubName}");
        $content = str_replace(['{{Name}}', '{{LowerName}}'], [$name, $lower], $content);
        File::put($targetPath, $content);
    }
}