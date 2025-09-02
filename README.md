# Laravel GraphQL DAL Package

A comprehensive Laravel package that provides a GraphQL Data Access Layer (DAL) with database management, repository pattern, and GraphQL integration for asset management systems.

## Features

- **Database Management**: Advanced database connection management with failover support
- **Transaction Management**: Robust transaction handling with savepoint support
- **Repository Pattern**: Clean abstraction layer for database operations
- **GraphQL Integration**: Complete GraphQL API with queries, mutations, and schema
- **Asset Management**: Full asset lifecycle management with audit capabilities
- **Employee Management**: Employee tracking and asset assignments
- **Location Management**: Multi-location asset tracking
- **Audit System**: Comprehensive audit trails and corrective actions
- **MySQL Support**: Optimized for MySQL via XAMPP

## Installation

### Via Composer

```bash
composer require your-company/laravel-graphql-dal
```

### Manual Installation

1. Clone or download this package to your Laravel project
2. Add the package to your `composer.json`:

```json
{
  "require": {
    "your-company/laravel-graphql-dal": "dev-main"
  },
  "repositories": [
    {
      "type": "path",
      "url": "./laravel-graphql-dal-package"
    }
  ]
}
```

3. Run `composer install`

## Configuration

### Publish Configuration Files

```bash
php artisan vendor:publish --tag=graphql-dal-config
```

This will publish:

- `config/graphql-dal.php` - Main package configuration
- `config/database-dal.php` - Database-specific configuration

### Publish Migrations

```bash
php artisan vendor:publish --tag=graphql-dal-migrations
```

### Publish GraphQL Schema

```bash
php artisan vendor:publish --tag=graphql-dal-schema
```

### Environment Variables

Add these to your `.env` file:

```env
# Database Configuration (MySQL via XAMPP)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=

# GraphQL DAL Configuration
GRAPHQL_DAL_CACHE_ENABLED=false
GRAPHQL_DAL_LOGGING_ENABLED=false
GRAPHQL_DAL_CONNECTION_POOLING=true
GRAPHQL_DAL_MAX_CONNECTIONS=10
```

## Usage

### Database Operations

The package provides a clean repository pattern for database operations:

```php
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;

class YourService
{
    public function __construct(
        private AssetRepository $assetRepository
    ) {}

    public function createAsset(array $data)
    {
        return $this->assetRepository->create($data);
    }

    public function findAssetByAssetId(string $assetId)
    {
        return $this->assetRepository->findByAssetId($assetId);
    }

    public function getAssetsByLocation(string $location)
    {
        return $this->assetRepository->getByLocation($location);
    }
}
```

### Transaction Management

```php
use YourCompany\GraphQLDAL\Database\TransactionManager;

class YourService
{
    public function __construct(
        private TransactionManager $transactionManager
    ) {}

    public function complexOperation()
    {
        return $this->transactionManager->transaction(function () {
            // Your database operations here
            // Automatic rollback on failure
        });
    }
}
```

### GraphQL API

The package provides a complete GraphQL API. Access it at `/graphql` endpoint.

#### Example Queries

```graphql
# Get all assets with filtering
query GetAssets($type: String, $statuses: [String]) {
  assets(type: $type, statuses: $statuses) {
    data {
      id
      asset_id
      type
      hostname
      manufacturer
      model
      location
      status
      employee {
        name
        email
      }
    }
    paginatorInfo {
      total
      currentPage
      lastPage
    }
  }
}

# Get locations
query GetLocations {
  locations {
    id
    name
    address
    city
    status
  }
}
```

#### Example Mutations

```graphql
# Create or update an asset
mutation UpsertAsset($asset: AssetInput!) {
  upsertAsset(asset: $asset) {
    id
    asset_id
    type
    hostname
    manufacturer
    model
    location
    status
  }
}

# Bulk upsert assets
mutation BulkUpsertAssets($assets: [AssetInput!]!) {
  bulkUpsertAssets(assets: $assets) {
    id
    asset_id
    type
  }
}
```

## Models

The package includes the following models:

- **Asset**: IT asset management with full lifecycle tracking
- **Employee**: Employee information and asset assignments
- **Location**: Physical locations for assets
- **Project**: Project management
- **User**: User authentication
- **AuditPlan**: Audit planning and management
- **AuditAsset**: Individual asset audit records
- **AuditAssignment**: Auditor assignments
- **CorrectiveAction**: Corrective action tracking
- **CorrectiveActionAssignment**: Action assignments
- **AuditLog**: Comprehensive audit logging

## Repositories

Each model has a corresponding repository with methods for:

- Basic CRUD operations
- Advanced filtering and searching
- Bulk operations
- Statistics and reporting
- Relationship management

## Database Schema

The package includes migrations for all tables:

- `assets` - Main asset table
- `employees` - Employee information
- `locations` - Location data
- `projects` - Project information
- `users` - User accounts
- `audit_plans` - Audit planning
- `audit_assets` - Asset audit records
- `audit_assignments` - Auditor assignments
- `corrective_actions` - Corrective actions
- `corrective_action_assignments` - Action assignments
- `audit_logs` - Audit trail

## Testing

Run the package tests:

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support and questions, please open an issue in the repository.

## Changelog

### v1.0.0

- Initial release
- Complete asset management system
- GraphQL API integration
- Repository pattern implementation
- Transaction management
- Audit system
- MySQL support via XAMPP
