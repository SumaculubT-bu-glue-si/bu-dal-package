<?php

/**
 * Package validation script for Laravel GraphQL DAL Package
 * 
 * This script validates the package structure and configuration
 */

echo "🔍 Laravel GraphQL DAL Package Validation\n";
echo "=========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Check package structure
echo "📁 Checking package structure...\n";

$requiredFiles = [
    'composer.json' => 'Package configuration',
    'src/Providers/GraphQLDALServiceProvider.php' => 'Service provider',
    'src/Database/DatabaseManager.php' => 'Database manager',
    'src/Database/TransactionManager.php' => 'Transaction manager',
    'src/Database/Repositories/BaseRepository.php' => 'Base repository',
    'src/Models/Asset.php' => 'Asset model',
    'src/Models/Employee.php' => 'Employee model',
    'src/Models/Location.php' => 'Location model',
    'src/Models/Project.php' => 'Project model',
    'src/Models/User.php' => 'User model',
    'src/Models/AuditPlan.php' => 'AuditPlan model',
    'src/Models/AuditAsset.php' => 'AuditAsset model',
    'src/Models/AuditAssignment.php' => 'AuditAssignment model',
    'src/Models/CorrectiveAction.php' => 'CorrectiveAction model',
    'src/Models/CorrectiveActionAssignment.php' => 'CorrectiveActionAssignment model',
    'src/Models/AuditLog.php' => 'AuditLog model',
    'src/GraphQL/Queries/AssetQueries.php' => 'Asset GraphQL queries',
    'src/GraphQL/Mutations/AssetMutations.php' => 'Asset GraphQL mutations',
    'src/GraphQL/Queries/LocationQueries.php' => 'Location GraphQL queries',
    'src/GraphQL/Queries/ProjectQueries.php' => 'Project GraphQL queries',
    'src/GraphQL/Schema/GraphQLDALSchema.php' => 'GraphQL schema',
    'config/graphql-dal.php' => 'GraphQL DAL configuration',
    'config/database-dal.php' => 'Database DAL configuration',
    'graphql/schema.graphql' => 'GraphQL schema file',
    'README.md' => 'Package documentation',
    'tests/TestCase.php' => 'Test base class',
    'tests/Unit/AssetRepositoryTest.php' => 'Asset repository tests',
    'tests/Feature/GraphQLTest.php' => 'GraphQL feature tests'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        $success[] = "✅ {$description}: {$file}";
    } else {
        $errors[] = "❌ Missing {$description}: {$file}";
    }
}

// Check required directories
$requiredDirs = [
    'src' => 'Source code directory',
    'src/Database' => 'Database layer directory',
    'src/Database/Repositories' => 'Repositories directory',
    'src/Models' => 'Models directory',
    'src/GraphQL' => 'GraphQL layer directory',
    'src/GraphQL/Queries' => 'GraphQL queries directory',
    'src/GraphQL/Mutations' => 'GraphQL mutations directory',
    'src/GraphQL/Schema' => 'GraphQL schema directory',
    'config' => 'Configuration directory',
    'graphql' => 'GraphQL schema directory',
    'tests' => 'Tests directory',
    'tests/Unit' => 'Unit tests directory',
    'tests/Feature' => 'Feature tests directory',
    'database/migrations' => 'Migrations directory',
    'database/factories' => 'Factories directory',
    'examples' => 'Examples directory'
];

foreach ($requiredDirs as $dir => $description) {
    if (is_dir($dir)) {
        $success[] = "✅ {$description}: {$dir}/";
    } else {
        $errors[] = "❌ Missing {$description}: {$dir}/";
    }
}

// Check composer.json
echo "\n📦 Checking composer.json...\n";

if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);

    if (!$composer) {
        $errors[] = "❌ Invalid composer.json format";
    } else {
        // Check required fields
        $requiredFields = ['name', 'description', 'license', 'authors', 'require', 'autoload'];
        foreach ($requiredFields as $field) {
            if (!isset($composer[$field])) {
                $errors[] = "❌ Missing required field in composer.json: {$field}";
            } else {
                $success[] = "✅ composer.json field: {$field}";
            }
        }

        // Check package name
        if (isset($composer['name']) && $composer['name'] === 'yourcompany/laravel-graphql-dal') {
            $success[] = "✅ Package name: {$composer['name']}";
        } else {
            $warnings[] = "⚠️  Package name should be 'yourcompany/laravel-graphql-dal'";
        }

        // Check required dependencies
        $requiredDeps = [
            'illuminate/support' => 'Laravel support',
            'illuminate/database' => 'Laravel database',
            'rebing/graphql-laravel' => 'GraphQL Laravel'
        ];

        foreach ($requiredDeps as $dep => $description) {
            if (isset($composer['require'][$dep])) {
                $success[] = "✅ Required dependency: {$dep}";
            } else {
                $errors[] = "❌ Missing required dependency: {$dep} ({$description})";
            }
        }
    }
}

// Check service provider
echo "\n🔧 Checking service provider...\n";

if (file_exists('src/Providers/GraphQLDALServiceProvider.php')) {
    $providerContent = file_get_contents('src/Providers/GraphQLDALServiceProvider.php');

    if (strpos($providerContent, 'class GraphQLDALServiceProvider extends ServiceProvider') !== false) {
        $success[] = "✅ Service provider class extends ServiceProvider";
    } else {
        $errors[] = "❌ Service provider class doesn't extend ServiceProvider";
    }

    if (strpos($providerContent, 'public function register()') !== false) {
        $success[] = "✅ Service provider has register() method";
    } else {
        $errors[] = "❌ Service provider missing register() method";
    }

    if (strpos($providerContent, 'public function boot()') !== false) {
        $success[] = "✅ Service provider has boot() method";
    } else {
        $errors[] = "❌ Service provider missing boot() method";
    }
}

// Check GraphQL schema
echo "\n🔍 Checking GraphQL schema...\n";

if (file_exists('graphql/schema.graphql')) {
    $schemaContent = file_get_contents('graphql/schema.graphql');

    if (strpos($schemaContent, 'type Asset') !== false) {
        $success[] = "✅ GraphQL schema contains Asset type";
    } else {
        $errors[] = "❌ GraphQL schema missing Asset type";
    }

    if (strpos($schemaContent, 'type Query') !== false) {
        $success[] = "✅ GraphQL schema contains Query type";
    } else {
        $errors[] = "❌ GraphQL schema missing Query type";
    }

    if (strpos($schemaContent, 'type Mutation') !== false) {
        $success[] = "✅ GraphQL schema contains Mutation type";
    } else {
        $errors[] = "❌ GraphQL schema missing Mutation type";
    }
}

// Check migrations
echo "\n🗄️  Checking migrations...\n";

$migrationFiles = glob('database/migrations/*.php');
if (count($migrationFiles) > 0) {
    $success[] = "✅ Found " . count($migrationFiles) . " migration files";

    // Check for specific migration files
    $expectedMigrations = [
        'create_assets_table',
        'create_employees_table',
        'create_locations_table',
        'create_projects_table',
        'create_users_table',
        'create_audit_plans_table',
        'create_audit_assets_table',
        'create_audit_assignments_table',
        'create_corrective_actions_table',
        'create_corrective_action_assignments_table',
        'create_audit_logs_table'
    ];

    foreach ($expectedMigrations as $migration) {
        $found = false;
        foreach ($migrationFiles as $file) {
            if (strpos($file, $migration) !== false) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $success[] = "✅ Migration found: {$migration}";
        } else {
            $warnings[] = "⚠️  Migration not found: {$migration}";
        }
    }
} else {
    $errors[] = "❌ No migration files found";
}

// Check tests
echo "\n🧪 Checking tests...\n";

$testFiles = glob('tests/**/*.php');
if (count($testFiles) > 0) {
    $success[] = "✅ Found " . count($testFiles) . " test files";
} else {
    $errors[] = "❌ No test files found";
}

// Display results
echo "\n📊 Validation Results\n";
echo "====================\n\n";

if (!empty($success)) {
    echo "✅ Successes (" . count($success) . "):\n";
    foreach ($success as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  Warnings (" . count($warnings) . "):\n";
    foreach ($warnings as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ Errors (" . count($errors) . "):\n";
    foreach ($errors as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

// Summary
$totalIssues = count($errors) + count($warnings);
$totalChecks = count($success) + $totalIssues;

echo "📈 Summary:\n";
echo "   Total checks: {$totalChecks}\n";
echo "   Successes: " . count($success) . "\n";
echo "   Warnings: " . count($warnings) . "\n";
echo "   Errors: " . count($errors) . "\n\n";

if (count($errors) === 0) {
    if (count($warnings) === 0) {
        echo "🎉 Package validation passed! All checks successful.\n";
    } else {
        echo "⚠️  Package validation passed with warnings. Please review the warnings above.\n";
    }
} else {
    echo "❌ Package validation failed. Please fix the errors above.\n";
    exit(1);
}

echo "\n🔗 Next steps:\n";
echo "1. Fix any warnings or errors\n";
echo "2. Run tests: php run-tests.php\n";
echo "3. Test installation in a Laravel project\n";
echo "4. Update documentation if needed\n";
