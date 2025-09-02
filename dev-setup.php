<?php

/**
 * Development setup script for Laravel GraphQL DAL Package
 * 
 * This script helps set up the development environment for the package
 */

echo "🛠️  Laravel GraphQL DAL Package Development Setup\n";
echo "================================================\n\n";

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

// Check if the package is installed
if (!file_exists('vendor/yourcompany/laravel-graphql-dal')) {
    echo "❌ Error: Package not installed.\n";
    echo "Please install the package first using: composer require yourcompany/laravel-graphql-dal:dev-main\n";
    exit(1);
}

echo "✅ Package installed\n";

// Check if development dependencies are installed
$composerJson = json_decode(file_get_contents('composer.json'), true);
$devDependencies = $composerJson['require-dev'] ?? [];

$requiredDevDeps = [
    'phpunit/phpunit' => 'PHPUnit for testing',
    'orchestra/testbench' => 'Laravel testing utilities',
    'mockery/mockery' => 'Mocking framework',
    'fakerphp/faker' => 'Fake data generator'
];

$missingDeps = [];
foreach ($requiredDevDeps as $dep => $description) {
    if (!isset($devDependencies[$dep])) {
        $missingDeps[$dep] = $description;
    }
}

if (!empty($missingDeps)) {
    echo "⚠️  Missing development dependencies:\n";
    foreach ($missingDeps as $dep => $description) {
        echo "   - {$dep}: {$description}\n";
    }
    echo "\nPlease install them using:\n";
    echo "composer require --dev " . implode(' ', array_keys($missingDeps)) . "\n\n";
} else {
    echo "✅ All development dependencies installed\n";
}

// Check if test database is configured
$envFile = '.env.testing';
if (!file_exists($envFile)) {
    echo "⚠️  .env.testing not found, creating from .env\n";

    if (file_exists('.env')) {
        copy('.env', '.env.testing');
        echo "✅ .env.testing created\n";
    } else {
        echo "❌ Error: .env file not found\n";
        echo "Please create a .env file with your database configuration.\n";
        exit(1);
    }
} else {
    echo "✅ .env.testing found\n";
}

// Check if test database configuration is correct
$envContent = file_get_contents('.env.testing');
if (strpos($envContent, 'DB_CONNECTION=sqlite') === false) {
    echo "⚠️  Test database not configured for SQLite\n";
    echo "For testing, it's recommended to use SQLite in-memory database.\n";
    echo "Please add the following to your .env.testing file:\n";
    echo "DB_CONNECTION=sqlite\n";
    echo "DB_DATABASE=:memory:\n\n";
} else {
    echo "✅ Test database configured for SQLite\n";
}

// Check if PHPUnit configuration exists
if (!file_exists('phpunit.xml')) {
    echo "⚠️  phpunit.xml not found\n";
    echo "Please create a phpunit.xml file with the following content:\n";
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<phpunit xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
    echo "         xsi:noNamespaceSchemaLocation=\"vendor/phpunit/phpunit/phpunit.xsd\"\n";
    echo "         bootstrap=\"vendor/autoload.php\"\n";
    echo "         colors=\"true\">\n";
    echo "    <testsuites>\n";
    echo "        <testsuite name=\"Package\">\n";
    echo "            <directory>vendor/yourcompany/laravel-graphql-dal/tests</directory>\n";
    echo "        </testsuite>\n";
    echo "    </testsuites>\n";
    echo "    <php>\n";
    echo "        <env name=\"APP_ENV\" value=\"testing\"/>\n";
    echo "        <env name=\"DB_CONNECTION\" value=\"sqlite\"/>\n";
    echo "        <env name=\"DB_DATABASE\" value=\":memory:\"/>\n";
    echo "    </php>\n";
    echo "</phpunit>\n\n";
} else {
    echo "✅ phpunit.xml found\n";
}

// Check if the package service provider is registered
$appConfig = file_get_contents('config/app.php');
if (strpos($appConfig, 'YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider') === false) {
    echo "⚠️  Service provider not registered\n";
    echo "Please add the following to your config/app.php providers array:\n";
    echo "YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider::class,\n\n";
} else {
    echo "✅ Service provider registered\n";
}

// Check if GraphQL configuration exists
if (!file_exists('config/graphql.php')) {
    echo "⚠️  GraphQL configuration not found\n";
    echo "Please ensure you have rebing/graphql-laravel installed and configured\n";
} else {
    echo "✅ GraphQL configuration found\n";
}

// Check if database configuration exists
if (!file_exists('config/database.php')) {
    echo "❌ Error: Database configuration not found\n";
    exit(1);
} else {
    echo "✅ Database configuration found\n";
}

echo "\n📋 Development Environment Setup Complete!\n";
echo "==========================================\n\n";

echo "🔧 Available Commands:\n";
echo "- php run-tests.php (run all package tests)\n";
echo "- php vendor/bin/phpunit (run tests with PHPUnit)\n";
echo "- php artisan tinker (interactive shell for testing)\n";
echo "- php artisan migrate (run database migrations)\n";
echo "- php artisan config:clear (clear configuration cache)\n";

echo "\n🧪 Testing:\n";
echo "- Unit tests: Test individual repository methods\n";
echo "- Feature tests: Test GraphQL queries and mutations\n";
echo "- Integration tests: Test database operations\n";

echo "\n📚 Documentation:\n";
echo "- README.md: Package overview and installation\n";
echo "- examples/: Usage examples and configuration\n";
echo "- tests/: Test examples and patterns\n";

echo "\n🎉 Development setup completed!\n";
echo "You can now start developing and testing the package.\n";
