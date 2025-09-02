<?php

/**
 * Example usage of Laravel GraphQL DAL Package
 * 
 * This file demonstrates how to use the package in your Laravel application
 */

require_once __DIR__ . '/../vendor/autoload.php';

use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;
use YourCompany\GraphQLDAL\Database\Repositories\EmployeeRepository;
use YourCompany\GraphQLDAL\Database\Repositories\LocationRepository;
use YourCompany\GraphQLDAL\Database\Repositories\ProjectRepository;
use YourCompany\GraphQLDAL\Database\Repositories\UserRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditPlanRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditAssetRepository;
use YourCompany\GraphQLDAL\Database\Repositories\AuditAssignmentRepository;
use YourCompany\GraphQLDAL\Database\Repositories\CorrectiveActionRepository;
use YourCompany\GraphQLDAL\Database\Repositories\CorrectiveActionAssignmentRepository;
use YourCompany\GraphQLDAL\Database\DatabaseManager;
use YourCompany\GraphQLDAL\Database\TransactionManager;

// Example 1: Basic Repository Usage
echo "=== Example 1: Basic Repository Usage ===\n";

// Get repository instances (these would be injected in real Laravel app)
$assetRepo = app(AssetRepository::class);
$employeeRepo = app(EmployeeRepository::class);
$locationRepo = app(LocationRepository::class);

// Create a new asset
$assetData = [
    'asset_id' => 'EXAMPLE-001',
    'type' => 'Laptop',
    'hostname' => 'EXAMPLE-LAPTOP',
    'manufacturer' => 'Dell',
    'model' => 'Latitude 5520',
    'status' => '利用中',
    'location' => 'Office A',
    'user_id' => 1
];

try {
    $asset = $assetRepo->create($assetData);
    echo "✅ Asset created: {$asset->asset_id}\n";
} catch (Exception $e) {
    echo "❌ Error creating asset: {$e->getMessage()}\n";
}

// Find an asset
try {
    $foundAsset = $assetRepo->find(1);
    if ($foundAsset) {
        echo "✅ Asset found: {$foundAsset->asset_id}\n";
    } else {
        echo "ℹ️  No asset found with ID 1\n";
    }
} catch (Exception $e) {
    echo "❌ Error finding asset: {$e->getMessage()}\n";
}

// Example 2: Transaction Management
echo "\n=== Example 2: Transaction Management ===\n";

$transactionManager = app(TransactionManager::class);

try {
    $result = $transactionManager->transaction(function () use ($assetRepo, $employeeRepo) {
        // Create multiple assets in a transaction
        $asset1 = $assetRepo->create([
            'asset_id' => 'TRANSACTION-001',
            'type' => 'Desktop',
            'hostname' => 'TRANSACTION-DESKTOP-1',
            'status' => '利用中'
        ]);

        $asset2 = $assetRepo->create([
            'asset_id' => 'TRANSACTION-002',
            'type' => 'Monitor',
            'hostname' => 'TRANSACTION-MONITOR-1',
            'status' => '利用中'
        ]);

        return [$asset1, $asset2];
    });

    echo "✅ Transaction completed successfully\n";
    echo "Created assets: " . count($result) . "\n";
} catch (Exception $e) {
    echo "❌ Transaction failed: {$e->getMessage()}\n";
}

// Example 3: Advanced Asset Repository Methods
echo "\n=== Example 3: Advanced Asset Repository Methods ===\n";

try {
    // Get assets with filters
    $criteria = [
        'type' => 'Laptop',
        'status' => '利用中'
    ];

    $assets = $assetRepo->getByCriteria($criteria);
    echo "✅ Found {$assets->count()} assets matching criteria\n";

    // Get assets by location
    $locationAssets = $assetRepo->getByLocation('Office A');
    echo "✅ Found {$locationAssets->count()} assets in Office A\n";

    // Search assets
    $searchResults = $assetRepo->search('Laptop');
    echo "✅ Found {$searchResults->count()} assets matching 'Laptop'\n";

    // Upsert an asset
    $upsertData = [
        'asset_id' => 'UPSERT-001',
        'type' => 'Laptop',
        'hostname' => 'UPSERT-LAPTOP',
        'status' => '利用中',
        'manufacturer' => 'HP'
    ];

    $upsertedAsset = $assetRepo->upsertByAssetId($upsertData);
    echo "✅ Asset upserted: {$upsertedAsset->asset_id}\n";
} catch (Exception $e) {
    echo "❌ Error in advanced operations: {$e->getMessage()}\n";
}

// Example 4: GraphQL Integration
echo "\n=== Example 4: GraphQL Integration ===\n";

echo "The package provides GraphQL resolvers for:\n";
echo "- Asset queries and mutations\n";
echo "- Employee queries\n";
echo "- Location queries\n";
echo "- Project queries\n";
echo "- User queries\n";
echo "- Audit plan queries\n";
echo "- Audit asset queries\n";
echo "- Audit assignment queries\n";
echo "- Corrective action queries\n";
echo "- Corrective action assignment queries\n\n";

echo "Example GraphQL queries:\n";
echo "```graphql\n";
echo "query {\n";
echo "  assets(first: 10, type: \"Laptop\") {\n";
echo "    data {\n";
echo "      id\n";
echo "      asset_id\n";
echo "      type\n";
echo "      hostname\n";
echo "      status\n";
echo "    }\n";
echo "  }\n";
echo "}\n";
echo "```\n\n";

echo "```graphql\n";
echo "mutation {\n";
echo "  upsertAsset(asset: {\n";
echo "    asset_id: \"NEW-ASSET-001\"\n";
echo "    type: \"Laptop\"\n";
echo "    hostname: \"NEW-LAPTOP\"\n";
echo "    status: \"利用中\"\n";
echo "  }) {\n";
echo "    id\n";
echo "    asset_id\n";
echo "    type\n";
echo "    hostname\n";
echo "    status\n";
echo "  }\n";
echo "}\n";
echo "```\n\n";

// Example 5: Error Handling
echo "=== Example 5: Error Handling ===\n";

try {
    // Try to create an asset with invalid data
    $invalidAsset = $assetRepo->create([
        'asset_id' => '', // Invalid: empty asset_id
        'type' => 'Laptop'
    ]);
} catch (Exception $e) {
    echo "✅ Caught expected error: {$e->getMessage()}\n";
}

echo "\n🎉 Examples completed!\n";
echo "For more information, see the README.md file.\n";
