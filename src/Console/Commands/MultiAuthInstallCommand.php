<?php

namespace SkyHackeR\MultiAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MultiAuthInstallCommand extends Command
{
    protected $signature = 'laravel-multi-auth:install {name} {--f|force}';
    protected $description = 'Generate a professional modular multi-auth scaffold with dual middleware injection';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lower = Str::lower($name);

        $this->info("🚀 SkyHackeR Engine: Building '{$name}' Identity...");

        // 1. Generate Files
        $this->generateModel($name, $lower); 
        $this->generateMigration($name, Str::plural($lower));
        $this->generateControllers($name, $lower);
        
        // Generates both RedirectIf and RedirectIfNot middleware files
        $this->generateMiddleware($name, $lower);
        
        $this->generateViews($name, $lower);
        $this->generateRoutes($name, $lower);

        // 2. Physical File Injections
        $this->updateAuthConfig($name, $lower);
        
        // Registers both 'auth.name' and 'guest.name' aliases in Kernel.php
        $this->registerMiddlewareInKernel($name);

        $this->info("✅ '{$name}' Identity is now physically registered and ready!");
    }

    /**
     * Physically injects TWO middleware aliases into Kernel.php using Regex
     */
    protected function registerMiddlewareInKernel($name)
    {
        $kernelPath = app_path('Http/Kernel.php');
        if (!File::exists($kernelPath)) return;

        $content = File::get($kernelPath);
        $lower = strtolower($name);
        
        $authAlias = "auth.{$lower}";
        $guestAlias = "guest.{$lower}";
        
        // Ensure names match the files generated in generateMiddleware()
        $authClass = "\\App\\Http\\Middleware\\RedirectIfNot{$name}::class";
        $guestClass = "\\App\\Http\\Middleware\\RedirectIf{$name}::class";

        if (str_contains($content, "'{$authAlias}'")) return;

        // Matches 'protected $middlewareAliases = [' OR 'protected $routeMiddleware = ['
        $pattern = '/(protected\s+\$(middlewareAliases|routeMiddleware)\s+=\s+\[)/';
        
        if (preg_match($pattern, $content)) {
            // Inject both aliases at once for route protection and guest redirection
            $replace = "$1\n        '{$authAlias}' => {$authClass},\n        '{$guestAlias}' => {$guestClass},";
            $content = preg_replace($pattern, $replace, $content);
            
            File::put($kernelPath, $content);
            $this->info("✅ Added '{$authAlias}' and '{$guestAlias}' to Kernel.php");
        }
    }

    /**
     * Physically injects Guards and Providers into config/auth.php
     */
    protected function updateAuthConfig($name, $lower)
    {
        $path = config_path('auth.php');
        if (!File::exists($path)) return;

        $content = File::get($path);

        // Inject Guard
        if (!str_contains($content, "'{$lower}' =>")) {
            $guardPattern = "/('guards'\s*=>\s*\[)/";
            $guardStub = "$1\n        '{$lower}' => [\n            'driver' => 'session',\n            'provider' => '{$lower}s',\n        ],";
            $content = preg_replace($guardPattern, $guardStub, $content);
        }

        // Inject Provider
        if (!str_contains($content, "'{$lower}s' =>")) {
            $providerPattern = "/('providers'\s*=>\s*\[)/";
            $providerStub = "$1\n        '{$lower}s' => [\n            'driver' => 'eloquent',\n            'model' => App\Models\\{$name}::class,\n        ],";
            $content = preg_replace($providerPattern, $providerStub, $content);
        }

        File::put($path, $content);
        $this->info("✅ Updated config/auth.php with '{$lower}' guard.");
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
            'views/home.stub'                 => 'home.blade.php',
            'views/layout/auth.stub'          => 'layout/auth.blade.php', // Renamed to auth per request
            'views/auth/login.stub'           => 'auth/login.blade.php',
            'views/auth/register.stub'        => 'auth/register.blade.php',
            'views/auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
            'views/auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        ];

        foreach ($viewMap as $stub => $file) { 
            $this->copyViewStub($stub, "{$base}/{$file}", $name, $lower); 
        }
    }

    protected function generateModel($name, $lower) { 
        $this->copyStub("Model.stub", app_path("Models/{$name}.php"), $name, $lower); 
    }

    protected function generateMiddleware($name, $lower) { 
        $path = app_path('Http/Middleware');
        File::ensureDirectoryExists($path);
        
        // 1. Guest Middleware: RedirectIfAdmin (Redirects to /admin/home if already logged in)
        $this->copyStub("Middleware.stub", "{$path}/RedirectIf{$name}.php", $name, $lower); 
        
        // 2. Auth Middleware: RedirectIfNotAdmin (Redirects to /admin/login if NOT logged in)
        $this->copyStub("MiddlewareAuthenticated.stub", "{$path}/RedirectIfNot{$name}.php", $name, $lower); 
    }

    protected function generateRoutes($name, $lower) { 
        $this->copyStub("routes.stub", base_path("routes/{$lower}.php"), $name, $lower); 
    }

    protected function generateMigration($name, $plural) {
        $file = date('Y_m_d_His') . "_create_{$plural}_table.php";
        $content = File::get(__DIR__ . '/../../stubs/Migration.stub');
        $content = str_replace(['{{TableName}}', '{{ClassName}}'], [$plural, $name], $content);
        File::put(database_path("migrations/{$file}"), $content);
    }

    /**
     * Helper for standard stubs (Controllers, Models, Routes)
     */
    private function copyStub($stubName, $targetPath, $name, $lower = '') {
        $content = File::get(__DIR__ . "/../../stubs/{$stubName}");
        $content = str_replace(['{{Name}}', '{{LowerName}}'], [$name, $lower], $content);
        File::put($targetPath, $content);
    }

    /**
     * Helper for View stubs using the {{guard}} placeholder
     */
    private function copyViewStub($stubName, $targetPath, $name, $lower) { 
        $content = File::get(__DIR__ . "/../../stubs/{$stubName}");
        // Replaces {{guard}} with 'admin' and {{Name}} with 'Admin'
        $content = str_replace(['{{Name}}', '{{guard}}'], [$name, $lower], $content);
        File::put($targetPath, $content);
    }
}