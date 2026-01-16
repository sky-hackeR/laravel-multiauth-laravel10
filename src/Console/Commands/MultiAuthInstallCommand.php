<?php

namespace SkyHackeR\MultiAuth\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MultiAuthInstallCommand extends Command
{
    protected $signature = 'laravel-multi-auth:install {name} {--f|force}';
    protected $description = 'Generate a modular multi-auth scaffold';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lower = Str::lower($name);

        $this->info("🚀 SkyHackeR Engine: Building '{$name}' Identity...");

        $this->generateModel($name);
        $this->generateMigration($name, Str::plural($lower));
        $this->generateControllers($name, $lower);
        $this->generateMiddleware($name, $lower);
        $this->generateViews($name, $lower);
        $this->generateRoutes($name, $lower);
        $this->updateAuthConfig($name, $lower);

        $this->info("✅ All components for '{$name}' created successfully!");
    }

    protected function updateAuthConfig($name, $lower)
    {
        $path = config_path('auth.php');
        $config = File::get($path);

        $guard = "'{$lower}' => [ 'driver' => 'session', 'provider' => '{$lower}s' ],";
        if (!Str::contains($config, "'{$lower}' =>")) {
            $config = str_replace("'guards' => [", "'guards' => [\n        " . $guard, $config);
        }

        $provider = "'{$lower}s' => [ 'driver' => 'eloquent', 'model' => App\Models\\{$name}::class ],";
        if (!Str::contains($config, "'{$lower}s' =>")) {
            $config = str_replace("'providers' => [", "'providers' => [\n        " . $provider, $config);
        }

        File::put($path, $config);
    }

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

        foreach ($viewMap as $stub => $file) {
            $this->copyStub($stub, "{$base}/{$file}", $name, $lower);
        }
    }

    protected function generateModel($name) {
        $this->copyStub("Model.stub", app_path("Models/{$name}.php"), $name);
    }

    protected function generateMigration($name, $plural) {
        $file = date('Y_m_d_His') . "_create_{$plural}_table.php";
        $content = str_replace(['{{TableName}}', '{{ClassName}}'], [$plural, $name], File::get(__DIR__ . '/../../stubs/Migration.stub'));
        File::put(database_path("migrations/{$file}"), $content);
    }

    protected function generateMiddleware($name, $lower) {
        $this->copyStub("Middleware.stub", app_path("Http/Middleware/RedirectIf{$name}.php"), $name, $lower);
    }

    protected function generateRoutes($name, $lower) {
        $this->copyStub("routes.stub", base_path("routes/{$lower}.php"), $name, $lower);
    }

    private function copyStub($stubName, $targetPath, $name, $lower = '') {
        $content = File::get(__DIR__ . "/../../stubs/{$stubName}");
        $content = str_replace(['{{Name}}', '{{LowerName}}'], [$name, $lower], $content);
        File::put($targetPath, $content);
    }
}