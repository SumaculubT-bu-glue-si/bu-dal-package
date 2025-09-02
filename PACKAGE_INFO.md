# Laravel GraphQL DAL Package Information

## Package Overview

**Name:** yourcompany/laravel-graphql-dal  
**Version:** 1.0.0  
**Description:** A Laravel package for Data Access Layer and GraphQL integration  
**License:** MIT  
**PHP Version:** ^8.2  
**Laravel Version:** ^10.0|^11.0|^12.0

## What This Package Provides

### 1. Data Access Layer (DAL)

- **DatabaseManager**: Centralized database connection management
- **TransactionManager**: Dedicated transaction handling
- **BaseRepository**: Abstract repository pattern implementation
- **Specific Repositories**: Asset, Employee, Location, Project, User, AuditPlan, AuditAsset, AuditAssignment, CorrectiveAction, CorrectiveActionAssignment repositories

### 2. GraphQL Integration

- **GraphQL Schema**: Complete schema definition for all models
- **Query Resolvers**: Asset, Location, Project queries
- **Mutation Resolvers**: Asset mutations (create, update, delete, bulk operations)
- **Type Definitions**: All model types with relationships

### 3. Models

- **Asset**: IT asset management with comprehensive fields
- **Employee**: Employee information and asset assignments
- **Location**: Physical locations for assets
- **Project**: Project management
- **User**: User management
- **AuditPlan**: Audit planning and management
- **AuditAsset**: Asset audit tracking
- **AuditAssignment**: Audit task assignments
- **CorrectiveAction**: Corrective action management
- **CorrectiveActionAssignment**: Corrective action assignments
- **AuditLog**: Audit logging and history

### 4. Database Migrations

- Complete database schema for all models
- Proper foreign key relationships
- Indexes for performance optimization
- Support for MySQL, PostgreSQL, SQLite

### 5. Testing

- **Unit Tests**: Repository method testing
- **Feature Tests**: GraphQL query and mutation testing
- **Test Factories**: Model factories for testing
- **Test Base Class**: Custom test case for package testing

## Installation

```bash
composer require yourcompany/laravel-graphql-dal:dev-main
```

## Configuration

1. **Publish Configuration:**

```bash
php artisan vendor:publish --provider="YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider"
```

2. **Run Migrations:**

```bash
php artisan migrate
```

3. **Configure GraphQL:**
   Add to `config/graphql.php`:

```php
'schema' => [
    'register' => [
        'default' => \YourCompany\GraphQLDAL\GraphQL\Schema\GraphQLDALSchema::class,
    ],
],
```

## Usage Examples

### Repository Usage

```php
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;

$assetRepo = app(AssetRepository::class);

// Create asset
$asset = $assetRepo->create([
    'asset_id' => 'ASSET-001',
    'type' => 'Laptop',
    'hostname' => 'LAPTOP-001',
    'status' => '利用中'
]);

// Find asset
$asset = $assetRepo->find(1);

// Update asset
$asset = $assetRepo->update(1, ['status' => '保管中']);

// Delete asset
$assetRepo->delete(1);
```

### GraphQL Usage

```graphql
# Query assets
query {
  assets(first: 10, type: "Laptop") {
    data {
      id
      asset_id
      type
      hostname
      status
    }
  }
}

# Create asset
mutation {
  upsertAsset(
    asset: {
      asset_id: "NEW-ASSET-001"
      type: "Laptop"
      hostname: "NEW-LAPTOP"
      status: "利用中"
    }
  ) {
    id
    asset_id
    type
    hostname
    status
  }
}
```

## File Structure

```
laravel-graphql-dal-package/
├── src/
│   ├── Database/
│   │   ├── DatabaseManager.php
│   │   ├── TransactionManager.php
│   │   └── Repositories/
│   │       ├── BaseRepository.php
│   │       ├── AssetRepository.php
│   │       ├── EmployeeRepository.php
│   │       ├── LocationRepository.php
│   │       ├── ProjectRepository.php
│   │       ├── UserRepository.php
│   │       ├── AuditPlanRepository.php
│   │       ├── AuditAssetRepository.php
│   │       ├── AuditAssignmentRepository.php
│   │       ├── CorrectiveActionRepository.php
│   │       └── CorrectiveActionAssignmentRepository.php
│   ├── Models/
│   │   ├── Asset.php
│   │   ├── Employee.php
│   │   ├── Location.php
│   │   ├── Project.php
│   │   ├── User.php
│   │   ├── AuditPlan.php
│   │   ├── AuditAsset.php
│   │   ├── AuditAssignment.php
│   │   ├── CorrectiveAction.php
│   │   ├── CorrectiveActionAssignment.php
│   │   └── AuditLog.php
│   ├── GraphQL/
│   │   ├── Queries/
│   │   │   ├── AssetQueries.php
│   │   │   ├── LocationQueries.php
│   │   │   └── ProjectQueries.php
│   │   ├── Mutations/
│   │   │   └── AssetMutations.php
│   │   └── Schema/
│   │       └── GraphQLDALSchema.php
│   ├── Exceptions/
│   │   ├── DatabaseException.php
│   │   ├── RepositoryException.php
│   │   └── GraphQLException.php
│   └── Providers/
│       └── GraphQLDALServiceProvider.php
├── config/
│   ├── graphql-dal.php
│   └── database-dal.php
├── database/
│   ├── migrations/
│   └── factories/
├── graphql/
│   └── schema.graphql
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── TestCase.php
├── examples/
│   ├── usage.php
│   └── config-example.php
├── composer.json
├── README.md
├── install.php
├── deploy.ps1
├── run-tests.php
├── dev-setup.php
├── validate.php
└── PACKAGE_INFO.md
```

## Dependencies

### Required

- **PHP:** ^8.2
- **Laravel:** ^10.0|^11.0|^12.0
- **illuminate/support:** ^10.0|^11.0|^12.0
- **illuminate/database:** ^10.0|^11.0|^12.0
- **rebing/graphql-laravel:** ^9.6

### Development

- **PHPUnit:** For testing
- **Orchestra Testbench:** Laravel testing utilities
- **Mockery:** Mocking framework
- **Faker:** Fake data generation

## Testing

```bash
# Run all tests
php run-tests.php

# Run specific test
php vendor/bin/phpunit tests/Unit/AssetRepositoryTest.php

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage/
```

## Development

```bash
# Setup development environment
php dev-setup.php

# Validate package
php validate.php

# Deploy to Laravel project
php deploy.ps1
```

## Support

For issues, questions, or contributions, please refer to the package documentation or contact the development team.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
