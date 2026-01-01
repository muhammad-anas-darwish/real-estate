<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud
                            {name : Model name}
                            {--module= : Module name}
                            {--cache : Generate service with cache functionality}
                            {--no-cache : Generate service without cache functionality}';

    protected $description = 'Create complete CRUD (Controller, DTO, Requests, Resource, Service, Model)';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $modelName = $this->argument('name');
        $moduleName = $this->normalizeModuleName($this->option('module'));
        $withCache = $this->option('cache') || !$this->option('no-cache');

        if (empty($moduleName)) {
            $this->error('Module name is required!');
            $this->info('Usage: php artisan make:crud ModelName --module=ModuleName');
            return;
        }

        $this->createModel($modelName, $moduleName);
        $this->createDTO($modelName, $moduleName);
        $this->createController($modelName, $moduleName);
        $this->createRequests($modelName, $moduleName);
        $this->createResource($modelName, $moduleName);
        $this->createService($modelName, $moduleName, $withCache);
        $this->createFactory($modelName, $moduleName);
        $this->createSeeder($modelName, $moduleName);
        $this->createMigration($modelName, $moduleName);
        $this->createRoutes($modelName, $moduleName);
        $this->createProvider($modelName, $moduleName);

        $this->registerModuleProvider($moduleName);

        $this->info("CRUD files for {$modelName} created successfully!");
    }

    protected function createModel($modelName, $moduleName = null)
    {
        $stub = $this->getStub('Model');
        $path = $this->getFilePath($modelName, 'Entities', $moduleName, "{$modelName}.php");

        // Determine factory namespace
        if ($moduleName) {
            $factoryNamespace = "Modules\\{$moduleName}\\Database\\Factories";
            $factoryClass = "{$modelName}Factory";
        } else {
            $factoryNamespace = "Database\\Factories";
            $factoryClass = "{$modelName}Factory";
        }

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}\\Entities" : "App\\Models",
            '{{ class }}' => $modelName,
            '{{ table }}' => Str::snake(Str::plural($modelName)),
            '{{ factoryNamespace }}' => $factoryNamespace,
            '{{ factoryClass }}' => $factoryClass,
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createDTO($modelName, $moduleName = null)
    {
        $stub = $this->getStub('DTO');
        $path = $this->getFilePath($modelName, 'DTOs', $moduleName, "{$modelName}DTO.php");

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}\\DTOs" : "App\\DTOs",
            '{{ class }}' => "{$modelName}DTO",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createController($modelName, $moduleName = null)
    {
        $stub = $this->getStub('Controller');
        $path = $this->getFilePath($modelName, 'Http/Controllers', $moduleName, "{$modelName}Controller.php");

        $lowerModel = Str::camel($modelName);
        $pluralModel = Str::plural($lowerModel);

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}" : "App\\Http\\Controllers",
            '{{ class }}' => "{$modelName}Controller",
            '{{ model }}' => $modelName,
            '{{ modelVariable }}' => $lowerModel,
            '{{ modelPlural }}' => $pluralModel,
            '{{ modelNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Entities\\{$modelName}" : "App\\Models\\{$modelName}",
            '{{ dtoNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\DTOs\\{$modelName}DTO" : "App\\DTOs\\{$modelName}DTO",
            '{{ resourceNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Http\\Resources\\{$modelName}Resource" : "App\\Http\\Resources\\{$modelName}Resource",
            '{{ serviceNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Services\\{$modelName}Service" : "App\\Services\\{$modelName}Service",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createRequests($modelName, $moduleName = null)
    {
        $storeStub = $this->getStub('StoreRequest');
        $storePath = $this->getFilePath($modelName, 'Http/Requests', $moduleName, "Store{$modelName}Request.php");

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}\\Http\\Requests" : "App\\Http\\Requests",
            '{{ class }}' => "Store{$modelName}Request",
        ];

        $this->createFile($storePath, $storeStub, $replacements);

        $updateStub = $this->getStub('UpdateRequest');
        $updatePath = $this->getFilePath($modelName, 'Http/Requests', $moduleName, "Update{$modelName}Request.php");

        $replacements['{{ class }}'] = "Update{$modelName}Request";
        $this->createFile($updatePath, $updateStub, $replacements);
    }

    protected function createResource($modelName, $moduleName = null)
    {
        $stub = $this->getStub('Resource');
        $path = $this->getFilePath($modelName, 'Http/Resources', $moduleName, "{$modelName}Resource.php");

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}\\Http\\Resources" : "App\\Http\\Resources",
            '{{ class }}' => "{$modelName}Resource",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createService($modelName, $moduleName = null, $withCache = true)
    {
        $stubType = $withCache ? 'ServiceWithCache' : 'Service';
        $stub = $this->getStub($stubType);
        $path = $this->getFilePath($modelName, 'Services', $moduleName, "{$modelName}Service.php");

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Modules\\{$moduleName}\\Services" : "App\\Services",
            '{{ class }}' => "{$modelName}Service",
            '{{ model }}' => $modelName,
            '{{ modelNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Entities\\{$modelName}" : "App\\Models\\{$modelName}",
            '{{ dtoNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\DTOs\\{$modelName}DTO" : "App\\DTOs\\{$modelName}DTO",
            '{{ modelVariable }}' => Str::camel($modelName),
            '{{ cachePrefix }}' => Str::snake(Str::plural($modelName)),
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createFactory($modelName, $moduleName = null)
    {
        $stub = $this->getStub('Factory');
        $path = $this->getFactoryPath($modelName, $moduleName);

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Database\\Factories\\Modules\\{$moduleName}\\Entities" : "Database\\Factories",
            '{{ class }}' => "{$modelName}Factory",
            '{{ modelNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Entities\\{$modelName}" : "App\\Models\\{$modelName}",
            '{{ enumNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Enums\\{$modelName}TypeEnum" : "App\\Enums\\{$modelName}TypeEnum",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function getFactoryPath($modelName, $moduleName)
    {
        if ($moduleName) {
            return base_path("Modules/{$moduleName}/Database/factories/{$modelName}Factory.php");
        }

        return database_path("factories/{$modelName}Factory.php");
    }

    protected function getStub($type)
    {
        $stubPath = __DIR__ . "/stubs/crud/{$type}.stub";

        if (!$this->files->exists($stubPath)) {
            // Try to use Laravel's built-in stubs for some types
            switch ($type) {
                case 'Migration':
                    return $this->files->get(base_path('vendor/laravel/framework/src/Illuminate/Database/Migrations/stubs/migration.stub'));
                case 'Seeder':
                    return $this->files->get(base_path('vendor/laravel/framework/src/Illuminate/Database/Console/Seeds/stubs/seeder.stub'));
                default:
                    throw new \Exception("Stub file not found: {$stubPath}");
            }
        }

        return $this->files->get($stubPath);
    }

    protected function registerModuleProvider($moduleName)
{
    $configPath = config_path('app.php');

    if (!$this->files->exists($configPath)) {
        return;
    }

    $content = $this->files->get($configPath);
    $providerClass = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider::class";

    // Check if provider is already registered
    if (!str_contains($content, $providerClass)) {
        // Find the providers array and add the module provider
        $pattern = '/\'providers\' => \[';
        $replacement = "'providers' => [\n        {$providerClass},";

        $content = preg_replace($pattern, $replacement, $content, 1);
        $this->files->put($configPath, $content);
        $this->info("Registered module provider in config/app.php");
    }
}

    protected function createSeeder($modelName, $moduleName = null)
    {
        $stub = $this->getStub('Seeder');
        $path = $this->getSeederPath($modelName, $moduleName);

        $replacements = [
            '{{ namespace }}' => $moduleName ? "Database\\Seeders\\Modules\\{$moduleName}" : "Database\\Seeders",
            '{{ class }}' => "{$modelName}Seeder",
            '{{ model }}' => $modelName,
            '{{ modelNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Entities\\{$modelName}" : "App\\Models\\{$modelName}",
            '{{ factoryNamespace }}' => $moduleName ? "Modules\\{$moduleName}\\Database\\Factories\\{$modelName}Factory" : "Database\\Factories\\{$modelName}Factory",
            '{{ seederName }}' => Str::snake(Str::plural($modelName)) . '_seeder',
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function getSeederPath($modelName, $moduleName)
    {
        if ($moduleName) {
            return base_path("Modules/{$moduleName}/Database/Seeders/{$modelName}Seeder.php");
        }

        return database_path("seeders/{$modelName}Seeder.php");
    }

    protected function createMigration($modelName, $moduleName = null)
    {
        // We're not creating the actual migration file, just the stub/directory structure
        $migrationPath = $moduleName
            ? base_path("Modules/{$moduleName}/Database/Migrations/")
            : database_path("migrations/");

        if (!$this->files->exists($migrationPath)) {
            $this->makeDirectory($migrationPath);
            $this->info("Created migration directory: {$migrationPath}");
        }

        // Create a migration stub file as example
        $stub = $this->getStub('Migration');
        $timestamp = date('Y_m_d_His');
        $tableName = Str::snake(Str::plural($modelName));
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $path = $migrationPath . $fileName;

        $replacements = [
            '{{ class }}' => "Create" . Str::studly($tableName) . "Table",
            '{{ table }}' => $tableName,
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createRoutes($modelName, $moduleName = null)
    {
        // Check if routes file already exists
        $routesPath = $moduleName
            ? base_path("Modules/{$moduleName}/Routes/api.php")
            : base_path("routes/api.php");

        $modelPlural = Str::kebab(Str::plural($modelName));
        $modelSingular = Str::kebab($modelName);

        if ($moduleName) {
            // Create module-specific routes file
            if (!$this->files->exists($routesPath)) {
                $this->createModuleRoutesFile($modelName, $moduleName, $routesPath);
            } else {
                // Add routes to existing file
                $this->addRoutesToFile($modelName, $moduleName, $routesPath);
            }
        } else {
            // Add routes to main api.php
            $this->addRoutesToFile($modelName, $moduleName, $routesPath);
        }
    }

    protected function createModuleRoutesFile($modelName, $moduleName, $routesPath)
    {
        $stub = $this->getStub('Routes');

        $controllerName = "{$modelName}Controller";
        $modelPlural = Str::kebab(Str::plural($modelName));
        $modelSingular = Str::kebab($modelName);

        if ($moduleName) {
            $controllerNamespace = "Modules\\{$moduleName}\\Http\\Controllers\\{$controllerName}";
        } else {
            $controllerNamespace = "App\\Http\\Controllers\\{$controllerName}";
        }

        $replacements = [
            '{{ controller }}' => $controllerNamespace,
            '{{ modelPlural }}' => $modelPlural,
            '{{ modelSingular }}' => $modelSingular,
            '{{ middleware }}' => "['api']",
        ];

        $this->createFile($routesPath, $stub, $replacements);
    }

    protected function addRoutesToFile($modelName, $moduleName, $routesPath)
    {
        if (!$this->files->exists($routesPath)) {
            return;
        }

        $content = $this->files->get($routesPath);
        $controllerName = "{$modelName}Controller";
        $modelPlural = Str::kebab(Str::plural($modelName));
        $modelSingular = Str::kebab($modelName);

        if ($moduleName) {
            $controllerNamespace = "Modules\\{$moduleName}\\Http\\Controllers\\{$controllerName}";
        } else {
            $controllerNamespace = "App\\Http\\Controllers\\{$controllerName}";
        }

        $routesTemplate = <<<EOT

// {$modelName} Routes
Route::apiResource('{$modelPlural}', {$controllerName}::class);
EOT;

        // Check if routes already exist
        if (!str_contains($content, "Route::apiResource('{$modelPlural}'")) {
            // Add routes at the end of the file
            $content = rtrim($content) . $routesTemplate;
            $this->files->put($routesPath, $content);
            $this->info("Added routes to: {$routesPath}");
        }
    }

    protected function createProvider($modelName, $moduleName = null)
    {
        if (!$moduleName) {
            return; // Only create providers for modules
        }

        // Check if provider already exists
        $providerPath = base_path("Modules/{$moduleName}/Providers/{$moduleName}ServiceProvider.php");

        if (!$this->files->exists($providerPath)) {
            $this->createModuleProvider($moduleName, $providerPath);
        }

        // Add route and migration loading to the provider
        $this->updateProvider($moduleName, $providerPath);
    }

    protected function createModuleProvider($moduleName, $providerPath)
    {
        $stub = $this->getStub('Provider');

        $replacements = [
            '{{ namespace }}' => "Modules\\{$moduleName}\\Providers",
            '{{ module }}' => $moduleName,
            '{{ class }}' => "{$moduleName}ServiceProvider",
        ];

        $this->createFile($providerPath, $stub, $replacements);
    }

    protected function updateProvider($moduleName, $providerPath)
    {
        if (!$this->files->exists($providerPath)) {
            return;
        }

        $content = $this->files->get($providerPath);

        // Check if loadRoutesFrom exists
        if (!str_contains($content, 'loadRoutesFrom')) {
            // Add loadRoutesFrom to boot method
            $loadRoutesCode = "\n        \$this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');";
            $content = preg_replace(
                '/(public function boot\(\): void\s*\{)/',
                "$1{$loadRoutesCode}",
                $content
            );
        }

        // Check if loadMigrationsFrom exists
        if (!str_contains($content, 'loadMigrationsFrom')) {
            // Add loadMigrationsFrom to boot method
            $loadMigrationsCode = "\n        \$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');";
            $content = preg_replace(
                '/(public function boot\(\): void\s*\{)/',
                "$1{$loadMigrationsCode}",
                $content
            );
        }

        $this->files->put($providerPath, $content);
        $this->info("Updated provider: {$providerPath}");
    }

    protected function getFilePath($modelName, $subPath, $moduleName, $fileName)
    {
        if ($moduleName) {
            $modulePath = base_path("Modules/{$moduleName}");
            return "{$modulePath}/{$subPath}/{$fileName}";
        }

        return app_path("{$subPath}/{$fileName}");
    }

    protected function createFile($path, $stub, $replacements)
    {
        if ($this->fileExists($path)) {
            return false;
        }

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $this->makeDirectory($path);
        $this->files->put($path, $content);
        $this->info("Created: {$path}");
        return true;
    }

    protected function fileExists(string $path): bool
    {
        return $this->files->exists($path);
    }

    protected function makeDirectory($path)
    {
        $dir = dirname($path);

        if (!$this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true, true);
        }
    }

    protected function normalizeModuleName(string $moduleName): string
    {
        // Replace all types of slashes with backslashes
        $normalized = str_replace(['/', '\\\\', '\\',], '\\', $moduleName);

        // Remove any trailing or leading slashes
        return trim($normalized, '\\');
    }
}
