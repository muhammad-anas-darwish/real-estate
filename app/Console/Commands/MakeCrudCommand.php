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
                            {--submodule= : SubModule name (optional)}
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
        $subModuleName = $this->option('submodule') ? $this->normalizeModuleName($this->option('submodule')) : null;
        $withCache = $this->option('cache') || ! $this->option('no-cache');

        if (empty($moduleName)) {
            $this->error('Module name is required!');
            $this->info('Usage: php artisan make:crud ModelName --module=ModuleName [--submodule=SubModuleName]');

            return;
        }

        // Check if module exists
        if (! $this->moduleExists($moduleName)) {
            $this->error("Module '{$moduleName}' does not exist!");
            $this->info("Please create the module first using: php artisan make:module {$moduleName}");

            return;
        }

        $this->info("Creating CRUD for {$modelName} in module {$moduleName}".($subModuleName ? " > {$subModuleName}" : ''));

        $this->createModel($modelName, $moduleName, $subModuleName);
        $this->createDTO($modelName, $moduleName, $subModuleName);
        $this->createController($modelName, $moduleName, $subModuleName);
        $this->createRequests($modelName, $moduleName, $subModuleName);
        $this->createResource($modelName, $moduleName, $subModuleName);
        $this->createService($modelName, $moduleName, $subModuleName, $withCache);
        $this->createFactory($modelName, $moduleName, $subModuleName);
        $this->createSeeder($modelName, $moduleName, $subModuleName);
        $this->createMigration($modelName, $moduleName);
        $this->addRoutesToModule($modelName, $moduleName, $subModuleName);

        $this->info("CRUD files for {$modelName} created successfully!");
    }

    protected function moduleExists($moduleName)
    {
        return $this->files->isDirectory(base_path("Modules/{$moduleName}"));
    }

    protected function createModel($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('Model');
        $path = $this->getFilePath($modelName, 'Entities', $moduleName, $subModuleName, "{$modelName}.php");

        $factoryNamespace = "Modules\\{$moduleName}\\Database\\Factories";
        $factoryClass = "{$modelName}Factory";

        $namespace = $this->getNamespace('Entities', $moduleName, $subModuleName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $modelName,
            '{{ table }}' => Str::snake(Str::plural($modelName)),
            '{{ factoryNamespace }}' => $factoryNamespace,
            '{{ factoryClass }}' => $factoryClass,
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createDTO($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('DTO');
        $path = $this->getFilePath($modelName, 'DTOs', $moduleName, $subModuleName, "{$modelName}DTO.php");

        $namespace = $this->getNamespace('DTOs', $moduleName, $subModuleName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => "{$modelName}DTO",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createController($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('Controller');
        $path = $this->getFilePath($modelName, 'Http/Controllers', $moduleName, $subModuleName, "{$modelName}Controller.php");

        $lowerModel = Str::camel($modelName);
        $pluralModel = Str::plural($lowerModel);

        $baseNamespace = $this->getNamespace('', $moduleName, $subModuleName);
        $namespace = $this->getNamespace('Http\\Controllers', $moduleName, $subModuleName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => "{$modelName}Controller",
            '{{ model }}' => $modelName,
            '{{ modelVariable }}' => $lowerModel,
            '{{ modelPlural }}' => $pluralModel,
            '{{ modelNamespace }}' => $this->getNamespace('Entities', $moduleName, $subModuleName)."\\{$modelName}",
            '{{ dtoNamespace }}' => $this->getNamespace('DTOs', $moduleName, $subModuleName)."\\{$modelName}DTO",
            '{{ resourceNamespace }}' => $this->getNamespace('Http\\Resources', $moduleName, $subModuleName)."\\{$modelName}Resource",
            '{{ serviceNamespace }}' => $this->getNamespace('Services', $moduleName, $subModuleName)."\\{$modelName}Service",
            '{{ requestNamespace }}' => $this->getNamespace('Http\\Requests', $moduleName, $subModuleName),
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createRequests($modelName, $moduleName, $subModuleName = null)
    {
        $namespace = $this->getNamespace('Http\\Requests', $moduleName, $subModuleName);

        $storeStub = $this->getStub('StoreRequest');
        $storePath = $this->getFilePath($modelName, 'Http/Requests', $moduleName, $subModuleName, "Store{$modelName}Request.php");

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => "Store{$modelName}Request",
        ];

        $this->createFile($storePath, $storeStub, $replacements);

        $updateStub = $this->getStub('UpdateRequest');
        $updatePath = $this->getFilePath($modelName, 'Http/Requests', $moduleName, $subModuleName, "Update{$modelName}Request.php");

        $replacements['{{ class }}'] = "Update{$modelName}Request";
        $this->createFile($updatePath, $updateStub, $replacements);
    }

    protected function createResource($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('Resource');
        $path = $this->getFilePath($modelName, 'Http/Resources', $moduleName, $subModuleName, "{$modelName}Resource.php");

        $namespace = $this->getNamespace('Http\\Resources', $moduleName, $subModuleName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => "{$modelName}Resource",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createService($modelName, $moduleName, $subModuleName = null, $withCache = true)
    {
        $stubType = $withCache ? 'ServiceWithCache' : 'Service';
        $stub = $this->getStub($stubType);
        $path = $this->getFilePath($modelName, 'Services', $moduleName, $subModuleName, "{$modelName}Service.php");

        $namespace = $this->getNamespace('Services', $moduleName, $subModuleName);

        $replacements = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => "{$modelName}Service",
            '{{ model }}' => $modelName,
            '{{ modelNamespace }}' => $this->getNamespace('Entities', $moduleName, $subModuleName)."\\{$modelName}",
            '{{ dtoNamespace }}' => $this->getNamespace('DTOs', $moduleName, $subModuleName)."\\{$modelName}DTO",
            '{{ modelVariable }}' => Str::camel($modelName),
            '{{ cachePrefix }}' => Str::snake(Str::plural($modelName)),
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createFactory($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('Factory');
        $path = base_path("Modules/{$moduleName}/Database/Factories/{$modelName}Factory.php");

        // Determine model namespace based on submodule
        if ($subModuleName) {
            $modelNamespace = "Modules\\{$moduleName}\\SubModules\\{$subModuleName}\\Entities\\{$modelName}";
        } else {
            $modelNamespace = "Modules\\{$moduleName}\\Entities\\{$modelName}";
        }

        $replacements = [
            '{{ namespace }}' => "Modules\\{$moduleName}\\Database\\Factories",
            '{{ class }}' => "{$modelName}Factory",
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ modelName }}' => $modelName,
            '{{ enumNamespace }}' => "Modules\\{$moduleName}\\Enums\\{$modelName}TypeEnum",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createSeeder($modelName, $moduleName, $subModuleName = null)
    {
        $stub = $this->getStub('Seeder');
        $path = base_path("Modules/{$moduleName}/Database/Seeders/{$modelName}Seeder.php");

        // Determine model namespace based on submodule
        if ($subModuleName) {
            $modelNamespace = "Modules\\{$moduleName}\\SubModules\\{$subModuleName}\\Entities\\{$modelName}";
        } else {
            $modelNamespace = "Modules\\{$moduleName}\\Entities\\{$modelName}";
        }

        $replacements = [
            '{{ namespace }}' => "Modules\\{$moduleName}\\Database\\Seeders",
            '{{ class }}' => "{$modelName}Seeder",
            '{{ model }}' => $modelName,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ factoryNamespace }}' => "Modules\\{$moduleName}\\Database\\Factories\\{$modelName}Factory",
            '{{ seederName }}' => Str::snake(Str::plural($modelName)).'_seeder',
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createMigration($modelName, $moduleName)
    {
        $migrationPath = base_path("Modules/{$moduleName}/Database/Migrations/");

        if (! $this->files->exists($migrationPath)) {
            $this->makeDirectory($migrationPath);
        }

        $stub = $this->getStub('Migration');
        $timestamp = date('Y_m_d_His');
        $tableName = Str::snake(Str::plural($modelName));
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $path = $migrationPath.$fileName;

        $replacements = [
            '{{ class }}' => 'Create'.Str::studly($tableName).'Table',
            '{{ table }}' => $tableName,
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function addRoutesToModule($modelName, $moduleName, $subModuleName = null)
    {
        $routesPath = base_path("Modules/{$moduleName}/Routes/api.php");

        if (! $this->files->exists($routesPath)) {
            $this->error("Routes file not found: {$routesPath}");

            return;
        }

        $content = $this->files->get($routesPath);
        $controllerName = "{$modelName}Controller";
        $modelPlural = Str::kebab(Str::plural($modelName));

        $controllerNamespace = $this->getNamespace('Http\\Controllers', $moduleName, $subModuleName)."\\{$controllerName}";

        $routesTemplate = <<<EOT


// {$modelName} Routes
Route::apiResource('{$modelPlural}', \\{$controllerNamespace}::class);
EOT;

        // Check if routes already exist
        if (! str_contains($content, "Route::apiResource('{$modelPlural}'")) {
            $content = rtrim($content).$routesTemplate;
            $this->files->put($routesPath, $content);
            $this->info("Added routes to: {$routesPath}");
        } else {
            $this->warn("Routes already exist for {$modelName}");
        }
    }

    protected function getNamespace($subPath, $moduleName, $subModuleName = null)
    {
        $namespace = "Modules\\{$moduleName}";

        if ($subModuleName) {
            $namespace .= "\\SubModules\\{$subModuleName}";
        }

        if (! empty($subPath)) {
            $namespace .= '\\'.str_replace('/', '\\', $subPath);
        }

        return $namespace;
    }

    protected function getFilePath($modelName, $subPath, $moduleName, $subModuleName, $fileName)
    {
        $basePath = base_path("Modules/{$moduleName}");

        if ($subModuleName) {
            $basePath .= "/SubModules/{$subModuleName}";
        }

        return "{$basePath}/{$subPath}/{$fileName}";
    }

    protected function getStub($type)
    {
        $stubPath = __DIR__."/stubs/crud/{$type}.stub";

        if (! $this->files->exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }

        return $this->files->get($stubPath);
    }

    protected function createFile($path, $stub, $replacements)
    {
        if ($this->fileExists($path)) {
            $this->warn("File already exists: {$path}");

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

        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true, true);
        }
    }

    protected function normalizeModuleName($moduleName): string
    {
        if (empty($moduleName)) {
            return '';
        }

        $normalized = str_replace(['/', '\\\\', '\\'], '\\', $moduleName);

        return trim($normalized, '\\');
    }
}
