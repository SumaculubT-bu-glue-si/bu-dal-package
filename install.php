<?php

/**
 * Simple installation test script for BU DAL Package
 * This script tests basic package functionality without requiring a full Laravel installation
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "BU DAL Package Installation Test\n";
echo "================================\n\n";

// Test 1: Check if classes can be loaded
echo "1. Testing class autoloading...\n";

try {
    $dbManager = new \Bu\DAL\Database\DatabaseManager();
    echo "   ✓ DatabaseManager loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load DatabaseManager: " . $e->getMessage() . "\n";
}

try {
    $assetRepo = new \Bu\DAL\Database\Repositories\AssetRepository(new \Bu\DAL\Models\Asset());
    echo "   ✓ AssetRepository loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load AssetRepository: " . $e->getMessage() . "\n";
}

try {
    $employeeRepo = new \Bu\DAL\Database\Repositories\EmployeeRepository(new \Bu\DAL\Models\Employee());
    echo "   ✓ EmployeeRepository loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load EmployeeRepository: " . $e->getMessage() . "\n";
}

// Test 2: Check if models can be instantiated
echo "\n2. Testing model instantiation...\n";

$models = [
    'Asset' => \Bu\DAL\Models\Asset::class,
    'Employee' => \Bu\DAL\Models\Employee::class,
    'Location' => \Bu\DAL\Models\Location::class,
    'Project' => \Bu\DAL\Models\Project::class,
    'User' => \Bu\DAL\Models\User::class,
    'AuditPlan' => \Bu\DAL\Models\AuditPlan::class,
    'AuditAsset' => \Bu\DAL\Models\AuditAsset::class,
    'AuditAssignment' => \Bu\DAL\Models\AuditAssignment::class,
    'CorrectiveAction' => \Bu\DAL\Models\CorrectiveAction::class,
    'CorrectiveActionAssignment' => \Bu\DAL\Models\CorrectiveActionAssignment::class,
];

foreach ($models as $name => $class) {
    try {
        $model = new $class();
        echo "   ✓ {$name} model instantiated successfully\n";
    } catch (Exception $e) {
        echo "   ✗ Failed to instantiate {$name}: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check if GraphQL classes can be loaded
echo "\n3. Testing GraphQL classes...\n";

$graphqlClasses = [
    'AssetQueries' => \Bu\DAL\GraphQL\Queries\AssetQueries::class,
    'EmployeeQueries' => \Bu\DAL\GraphQL\Queries\EmployeeQueries::class,
    'LocationQueries' => \Bu\DAL\GraphQL\Queries\LocationQueries::class,
    'AssetMutations' => \Bu\DAL\GraphQL\Mutations\AssetMutations::class,
    'EmployeeMutations' => \Bu\DAL\GraphQL\Mutations\EmployeeMutations::class,
    'LocationMutations' => \Bu\DAL\GraphQL\Mutations\LocationMutations::class,
];

foreach ($graphqlClasses as $name => $class) {
    try {
        if (class_exists($class)) {
            echo "   ✓ {$name} class exists\n";
        } else {
            echo "   ✗ {$name} class not found\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Error checking {$name}: " . $e->getMessage() . "\n";
    }
}

// Test 4: Check if configuration files exist
echo "\n4. Testing configuration files...\n";

$configFiles = [
    'composer.json' => __DIR__ . '/composer.json',
    'config/dal.php' => __DIR__ . '/config/dal.php',
    'graphql/schema.graphql' => __DIR__ . '/graphql/schema.graphql',
    'README.md' => __DIR__ . '/README.md',
];

foreach ($configFiles as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ {$name} exists\n";
    } else {
        echo "   ✗ {$name} missing\n";
    }
}

// Test 5: Check if migrations exist
echo "\n5. Testing migration files...\n";

$migrationDir = __DIR__ . '/database/migrations';
if (is_dir($migrationDir)) {
    $migrations = glob($migrationDir . '/*.php');
    echo "   ✓ Found " . count($migrations) . " migration files\n";

    if (count($migrations) > 0) {
        foreach (array_slice($migrations, 0, 3) as $migration) {
            echo "     - " . basename($migration) . "\n";
        }
        if (count($migrations) > 3) {
            echo "     ... and " . (count($migrations) - 3) . " more\n";
        }
    }
} else {
    echo "   ✗ Migration directory not found\n";
}

echo "\nInstallation test completed!\n";
echo "\nTo use this package in a Laravel project:\n";
echo "1. Add it to your composer.json\n";
echo "2. Run: composer install\n";
echo "3. Publish configuration: php artisan vendor:publish --provider=\"Bu\\DAL\\Providers\\DALServiceProvider\"\n";
echo "4. Run migrations: php artisan migrate\n";
echo "5. Configure your .env file with database settings\n";
