<?php

/**
 * Installation script for Laravel GraphQL DAL Package
 * 
 * This script helps set up the package in a Laravel application
 */

echo "Laravel GraphQL DAL Package Installation\n";
echo "========================================\n\n";

// Check if we're in a Laravel project
if (!file_exists('artisan')) {
    echo "❌ Error: This doesn't appear to be a Laravel project.\n";
    echo "Please run this script from the root of your Laravel application.\n";
    exit(1);
}

echo "✅ Laravel project detected\n";

// Check if composer.json exists
if (!file_exists('composer.json')) {
    echo "❌ Error: composer.json not found\n";
    exit(1);
}

echo "✅ composer.json found\n";

// Check if the package is already installed
$composerJson = json_decode(file_get_contents('composer.json'), true);
$packageName = 'yourcompany/laravel-graphql-dal';

if (isset($composerJson['require'][$packageName])) {
    echo "✅ Package already installed\n";
} else {
    echo "📦 Adding package to composer.json...\n";

    if (!isset($composerJson['require'])) {
        $composerJson['require'] = [];
    }

    $composerJson['require'][$packageName] = 'dev-main';

    file_put_contents('composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "✅ Package added to composer.json\n";
}

// Check if service provider is registered
$configApp = 'config/app.php';
if (file_exists($configApp)) {
    $appConfig = file_get_contents($configApp);
    if (strpos($appConfig, 'YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider') !== false) {
        echo "✅ Service provider already registered\n";
    } else {
        echo "⚠️  Service provider not found in config/app.php\n";
        echo "Please add the following to your config/app.php providers array:\n";
        echo "YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider::class,\n\n";
    }
} else {
    echo "⚠️  config/app.php not found\n";
}

// Check if GraphQL config exists
if (file_exists('config/graphql.php')) {
    echo "✅ GraphQL configuration found\n";
} else {
    echo "⚠️  GraphQL configuration not found\n";
    echo "Please ensure you have rebing/graphql-laravel installed and configured\n";
}

// Check if database configuration exists
if (file_exists('config/database.php')) {
    echo "✅ Database configuration found\n";
} else {
    echo "❌ Database configuration not found\n";
    exit(1);
}

echo "\n📋 Next Steps:\n";
echo "1. Run: composer install\n";
echo "2. Run: php artisan vendor:publish --provider=\"YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider\"\n";
echo "3. Run: php artisan migrate\n";
echo "4. Configure your .env file with database settings\n";
echo "5. Test the GraphQL endpoint at /graphql\n\n";

echo "🎉 Installation script completed!\n";
