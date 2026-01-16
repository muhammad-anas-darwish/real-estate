<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Module name}';

    protected $description = 'Create a new module with complete structure';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $moduleName = $this->argument('name');

        $this->info("Creating module: {$moduleName}");

        // Create main module structure
        $this->createModuleStructure($moduleName);
        $this->createModuleProvider($moduleName);
        $this->createRouteFile($moduleName);
        $this->createLangFiles($moduleName);

        // Register module provider
        $this->registerModuleProvider($moduleName);

        $this->info("Module {$moduleName} created successfully!");
        $this->info("Module path: " . base_path("Modules/{$moduleName}"));
    }

    protected function createModuleStructure($moduleName)
    {
        $basePath = base_path("Modules/{$moduleName}");

        $directories = [
            'SubModules',
            'Routes',
            'Database/Migrations',
            'Database/Seeders',
            'Lang/en',
            'Lang/ar',
            'Providers',
        ];

        foreach ($directories as $directory) {
            $path = "{$basePath}/{$directory}";

            if (!$this->files->isDirectory($path)) {
                $this->files->makeDirectory($path, 0755, true, true);
                $this->info("Created: {$path}");

                // Create .gitkeep files to preserve empty directories
                $this->files->put("{$path}/.gitkeep", '');
            }
        }
    }

    protected function createModuleProvider($moduleName)
    {
        $stub = $this->getStub('ModuleProvider');
        $path = base_path("Modules/{$moduleName}/Providers/{$moduleName}ServiceProvider.php");

        $replacements = [
            '{{ namespace }}' => "Modules\\{$moduleName}\\Providers",
            '{{ module }}' => $moduleName,
            '{{ class }}' => "{$moduleName}ServiceProvider",
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createRouteFile($moduleName)
    {
        $stub = $this->getStub('ModuleRoutes');
        $path = base_path("Modules/{$moduleName}/Routes/api.php");

        $replacements = [
            '{{ module }}' => $moduleName,
        ];

        $this->createFile($path, $stub, $replacements);
    }

    protected function createLangFiles($moduleName)
    {
        $languages = ['en', 'ar'];

        foreach ($languages as $lang) {
            $stub = $this->getStub('ModuleLang');
            $path = base_path("Modules/{$moduleName}/Lang/{$lang}/{$moduleName}.php");

            $replacements = [
                '{{ module }}' => $moduleName,
            ];

            $this->createFile($path, $stub, $replacements);
        }
    }

    protected function registerModuleProvider($moduleName)
    {
        $configPath = config_path('app.php');

        if (!$this->files->exists($configPath)) {
            $this->warn("config/app.php not found. Please register the provider manually:");
            $this->line("Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider::class");
            return;
        }

        $content = $this->files->get($configPath);
        $providerClass = "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider::class";

        // Check if provider is already registered
        if (str_contains($content, $providerClass)) {
            $this->info("Provider already registered in config/app.php");
            return;
        }

        // Find the providers array and add the module provider
        $pattern = '/\'providers\'\s*=>\s*\[/';
        $replacement = "'providers' => [\n        {$providerClass},";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content, 1);
            $this->files->put($configPath, $content);
            $this->info("Registered module provider in config/app.php");
        } else {
            $this->warn("Could not automatically register provider. Please add manually:");
            $this->line($providerClass);
        }
    }

    protected function getStub($type)
    {
        $stubs = [
            'ModuleProvider' => '<?php

namespace {{ namespace }};

use Illuminate\Support\ServiceProvider;

class {{ class }} extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . \'/../Routes/api.php\');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . \'/../Database/Migrations\');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . \'/../Lang\', \'{{ module }}\');
    }
}',
            'ModuleRoutes' => '<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {{ module }} Module API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for {{ module }} module.
|
*/

Route::middleware([\'api\'])->prefix(\'api\')->group(function () {
    // Add your module routes here
});',
            'ModuleLang' => '<?php

return [
    // Add your translations here
];',
        ];

        if (!isset($stubs[$type])) {
            throw new \Exception("Stub type not found: {$type}");
        }

        return $stubs[$type];
    }

    protected function createFile($path, $stub, $replacements)
    {
        if ($this->files->exists($path)) {
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

    protected function makeDirectory($path)
    {
        $dir = dirname($path);

        if (!$this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir, 0755, true, true);
        }
    }
}
