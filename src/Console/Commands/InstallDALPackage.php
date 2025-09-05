<?php

namespace Bu\DAL\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallDALPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dal:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the BU DAL Package with all necessary files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Installing BU DAL Package...');
        $this->newLine();

        // Publish all package resources
        $this->info('📦 Publishing package resources...');
        $this->call('vendor:publish', [
            '--provider' => 'Bu\DAL\Providers\DALServiceProvider',
            '--tag' => 'dal-all',
            '--force' => $this->option('force')
        ]);

        $this->newLine();

        // Check if Lighthouse is installed
        if (!$this->isLighthouseInstalled()) {
            $this->warn('⚠️  Lighthouse is not installed. Installing it now...');
            $this->call('composer', ['require', 'nuwave/lighthouse']);
        }

        $this->newLine();

        // Publish Lighthouse configuration
        $this->info('🔧 Publishing Lighthouse configuration...');
        $this->call('vendor:publish', [
            '--provider' => 'Nuwave\Lighthouse\LighthouseServiceProvider',
            '--tag' => 'lighthouse-config'
        ]);

        $this->newLine();

        // Update Lighthouse configuration
        $this->info('⚙️  Updating Lighthouse configuration...');
        $this->updateLighthouseConfig();

        $this->newLine();

        // Create .env example for DAL
        $this->info('📝 Creating DAL environment configuration...');
        $this->createDALEnvExample();

        $this->newLine();

        // Final instructions
        $this->info('✅ BU DAL Package installation completed!');
        $this->newLine();

        $this->info('📋 Next steps:');
        $this->line('1. Configure your database in .env file');
        $this->line('2. Run: php artisan migrate');
        $this->line('3. Configure mail settings in .env file');
        $this->line('4. Run: php artisan config:cache');
        $this->line('5. Test the system: php artisan audits:test-system');
        $this->newLine();

        $this->info('🎉 Your Laravel server is ready to use the BU DAL Package!');
    }

    /**
     * Check if Lighthouse is installed
     */
    private function isLighthouseInstalled(): bool
    {
        return class_exists('Nuwave\Lighthouse\LighthouseServiceProvider');
    }

    /**
     * Update Lighthouse configuration
     */
    private function updateLighthouseConfig(): void
    {
        $configPath = config_path('lighthouse.php');

        if (!File::exists($configPath)) {
            $this->warn('Lighthouse config file not found. Please run: php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider"');
            return;
        }

        $config = File::get($configPath);

        // Update namespaces to include DAL package
        $updatedConfig = str_replace(
            "'namespaces' => [",
            "'namespaces' => [
        'models' => ['App', 'App\\Models', 'Bu\\DAL\\Models'],
        'queries' => ['App\\GraphQL\\Queries', 'Bu\\DAL\\GraphQL\\Queries'],
        'mutations' => ['App\\GraphQL\\Mutations', 'Bu\\DAL\\GraphQL\\Mutations'],
        'subscriptions' => ['App\\GraphQL\\Subscriptions', 'Bu\\DAL\\GraphQL\\Subscriptions'],
        'types' => ['App\\GraphQL\\Types', 'Bu\\DAL\\GraphQL\\Types'],
        'interfaces' => ['App\\GraphQL\\Interfaces', 'Bu\\DAL\\GraphQL\\Interfaces'],
        'unions' => ['App\\GraphQL\\Unions', 'Bu\\DAL\\GraphQL\\Unions'],
        'scalars' => ['App\\GraphQL\\Scalars', 'Bu\\DAL\\GraphQL\\Scalars'],
    ],
    'namespaces_old' => [",
            $config
        );

        File::put($configPath, $updatedConfig);
        $this->line('✓ Lighthouse configuration updated');
    }

    /**
     * Create DAL environment configuration example
     */
    private function createDALEnvExample(): void
    {
        $envExample = base_path('.env.example');

        if (File::exists($envExample)) {
            $content = File::get($envExample);

            // Add DAL configuration if not already present
            if (strpos($content, 'DAL_') === false) {
                $dalConfig = "\n# BU DAL Package Configuration\nDAL_DEFAULT_CONNECTION=mysql\nDAL_CACHE_ENABLED=true\nDAL_CACHE_TTL=3600\nDAL_GRAPHQL_ENABLED=true\nDAL_LOGGING_ENABLED=true\nDAL_LOG_LEVEL=info\n";

                File::append($envExample, $dalConfig);
                $this->line('✓ Added DAL configuration to .env.example');
            }
        }
    }
}
