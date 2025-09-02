<?php

/**
 * Test runner for Laravel GraphQL DAL Package
 * 
 * This script runs the package tests
 */

echo "🧪 Laravel GraphQL DAL Package Test Runner\n";
echo "==========================================\n\n";

// Check if we're in a Laravel project
if (!file_exists('artisan')) {
    echo "❌ Error: This doesn't appear to be a Laravel project.\n";
    echo "Please run this script from the root of your Laravel application.\n";
    exit(1);
}

echo "✅ Laravel project detected\n";

// Check if the package is installed
if (!file_exists('vendor/yourcompany/laravel-graphql-dal')) {
    echo "❌ Error: Package not installed.\n";
    echo "Please install the package first using: composer require yourcompany/laravel-graphql-dal:dev-main\n";
    exit(1);
}

echo "✅ Package installed\n";

// Check if PHPUnit is available
if (!file_exists('vendor/bin/phpunit')) {
    echo "❌ Error: PHPUnit not found.\n";
    echo "Please install PHPUnit: composer require --dev phpunit/phpunit\n";
    exit(1);
}

echo "✅ PHPUnit available\n";

// Check if test database is configured
$envFile = '.env.testing';
if (!file_exists($envFile)) {
    echo "⚠️  .env.testing not found, using .env\n";
    $envFile = '.env';
}

if (!file_exists($envFile)) {
    echo "❌ Error: No environment file found.\n";
    echo "Please create a .env file with your database configuration.\n";
    exit(1);
}

echo "✅ Environment file found: {$envFile}\n";

// Run the tests
echo "\n🧪 Running package tests...\n";
echo "============================\n\n";

$testCommand = "vendor/bin/phpunit vendor/yourcompany/laravel-graphql-dal/tests --testdox";

echo "Command: {$testCommand}\n\n";

// Execute the test command
$output = [];
$returnCode = 0;
exec($testCommand . ' 2>&1', $output, $returnCode);

// Display the output
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n";

if ($returnCode === 0) {
    echo "✅ All tests passed!\n";
} else {
    echo "❌ Some tests failed. Return code: {$returnCode}\n";
}

echo "\n📋 Test Summary:\n";
echo "- Unit tests: AssetRepository, EmployeeRepository, etc.\n";
echo "- Feature tests: GraphQL queries and mutations\n";
echo "- Integration tests: Database operations\n";

echo "\n🔗 To run specific tests:\n";
echo "- php vendor/bin/phpunit vendor/yourcompany/laravel-graphql-dal/tests/Unit/AssetRepositoryTest.php\n";
echo "- php vendor/bin/phpunit vendor/yourcompany/laravel-graphql-dal/tests/Feature/GraphQLTest.php\n";

echo "\n🎉 Test runner completed!\n";
