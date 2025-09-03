# BU DAL Package

A comprehensive Laravel package that provides a Data Access Layer (DAL) with GraphQL and REST API support for business applications. This package extracts database operations, repository patterns, and API functionality into a reusable component.

## Features

- **Database Management**: Advanced database connection management with MySQL support
- **Repository Pattern**: Clean separation of data access logic
- **GraphQL Integration**: Complete GraphQL API with queries, mutations, and schema
- **REST API**: Automatic REST API endpoints for all models
- **Transaction Management**: Automatic transaction handling with rollback support
- **Audit System**: Comprehensive audit tracking and notification system
- **Asset Management**: Complete asset lifecycle management
- **Employee Management**: Employee and user management system
- **Location & Project Management**: Multi-location and project support

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- MySQL 5.7 or higher
- Composer

## Installation

### Step 1: Install via Composer

Add the repository to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/izuminaoki2025/bu-dal-package.git"
    }
  ],
  "require": {
    "bu/dal-package": "dev-main"
  }
}
```

Then run:

```bash
composer require bu/dal-package:dev-main
```

Or install directly:

```bash
composer require bu/dal-package:dev-main --repository='{"type":"vcs","url":"https://github.com/izuminaoki2025/bu-dal-package.git"}'
```

### Alternative: Manual Installation

1. Clone the repository:

```bash
git clone https://github.com/izuminaoki2025/bu-dal-package.git
```

2. Add to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./bu-dal-package"
    }
  ],
  "require": {
    "bu/dal-package": "*"
  }
}
```

3. Run `composer install`

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-config"
```

### Step 3: Configure Environment Variables

Add these to your `.env` file:

```env
# Database Configuration (Required)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=your_password

# DAL Package Configuration (Optional)
DAL_DEFAULT_CONNECTION=mysql
DAL_CACHE_ENABLED=false
DAL_GRAPHQL_ENABLED=true
DAL_LOGGING_ENABLED=true
```

### Step 4: Publish and Run Migrations

```bash
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-migrations"
php artisan migrate
```

### Step 5: Publish GraphQL Schema

```bash
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-graphql"
```

### Step 6: Configure Lighthouse (GraphQL)

```bash
php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider" --tag="lighthouse-config"
```

Update `config/lighthouse.php` to include the package namespaces:

```php
'namespaces' => [
    'models' => ['App', 'App\\Models', 'Bu\\DAL\\Models'],
    'queries' => ['App\\GraphQL\\Queries', 'Bu\\DAL\\GraphQL\\Queries'],
    'mutations' => ['App\\GraphQL\\Mutations', 'Bu\\DAL\\GraphQL\\Mutations'],
    'subscriptions' => ['App\\GraphQL\\Subscriptions', 'Bu\\DAL\\GraphQL\\Subscriptions'],
    'types' => ['App\\GraphQL\\Types', 'Bu\\DAL\\GraphQL\\Types'],
    'interfaces' => ['App\\GraphQL\\Interfaces', 'Bu\\DAL\\GraphQL\\Interfaces'],
    'unions' => ['App\\GraphQL\\Unions', 'Bu\\DAL\\GraphQL\\Unions'],
    'scalars' => ['App\\GraphQL\\Scalars', 'Bu\\DAL\\GraphQL\\Scalars'],
    'directives' => ['App\\GraphQL\\Directives', 'Bu\\DAL\\GraphQL\\Directives'],
    'validators' => ['App\\GraphQL\\Validators', 'Bu\\DAL\\GraphQL\\Validators'],
],
```

### Step 7: Update Lighthouse Configuration

Update `config/lighthouse.php` to set the GraphQL endpoint:

```php
'route' => [
    'uri' => '/api/graphql',
],
```

### Step 8: Clear Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Usage

### REST API Endpoints

The package automatically provides these REST API endpoints:

- `GET /api/locations` - Get all locations
- `GET /api/employees` - Get all employees
- `GET /api/assets` - Get all assets
- `GET /api/projects` - Get all projects
- `GET /api/audit-plans` - Get all audit plans
- `GET /api/audit-assets` - Get all audit assets
- `GET /api/audit-assignments` - Get all audit assignments
- `GET /api/corrective-actions` - Get all corrective actions

### GraphQL API

Access the GraphQL playground at: `http://your-app.com/api/graphql`

#### Example Queries

```graphql
# Get all assets with pagination
query {
  assets(first: 10) {
    data {
      id
      asset_id
      type
      hostname
      location
      status
      employee {
        name
        email
      }
    }
    paginatorInfo {
      currentPage
      lastPage
      total
    }
  }
}

# Get employees by location
query {
  employees(where: { location: { eq: "Tokyo" } }) {
    data {
      id
      employee_id
      name
      email
      location
    }
  }
}

# Get locations
query {
  locations {
    data {
      id
      name
      address
      status
    }
  }
}
```

#### Example Mutations

```graphql
# Create/Update an asset
mutation {
  upsertAsset(
    asset: {
      asset_id: "PC001"
      type: "laptop"
      hostname: "workstation-01"
      manufacturer: "Dell"
      model: "Latitude 5520"
      location: "Tokyo"
      status: "利用中"
    }
  ) {
    id
    asset_id
    type
    hostname
  }
}

# Bulk upsert assets
mutation {
  bulkUpsertAssets(
    assets: [
      { asset_id: "PC002", type: "desktop", hostname: "workstation-02" }
      { asset_id: "PC003", type: "laptop", hostname: "workstation-03" }
    ]
  ) {
    id
    asset_id
  }
}

# Create an employee
mutation {
  upsertEmployee(
    employee: {
      employee_id: "EMP001"
      name: "John Doe"
      email: "john.doe@company.com"
      location: "Tokyo"
    }
  ) {
    id
    employee_id
    name
  }
}
```

### Repository Pattern Usage

```php
use Bu\DAL\Database\Repositories\AssetRepository;
use Bu\DAL\Database\Repositories\EmployeeRepository;
use Bu\DAL\Database\Repositories\LocationRepository;

// Asset operations
$assetRepo = app(AssetRepository::class);

// Find asset by asset_id
$asset = $assetRepo->findByAssetId('PC001');

// Upsert asset
$asset = $assetRepo->upsertByAssetId([
    'asset_id' => 'PC001',
    'type' => 'laptop',
    'hostname' => 'workstation-01',
    'location' => 'Tokyo',
    'status' => '利用中'
]);

// Search assets with filters
$assets = $assetRepo->search([
    'type' => 'laptop',
    'statuses' => ['利用中', '保管中'],
    'locations' => ['Tokyo', 'Osaka']
]);

// Employee operations
$employeeRepo = app(EmployeeRepository::class);

// Find employee by employee_id
$employee = $employeeRepo->findByEmployeeId('EMP001');

// Get employees by location
$employees = $employeeRepo->getByLocation('Tokyo');

// Location operations
$locationRepo = app(LocationRepository::class);

// Get all active locations
$locations = $locationRepo->getActive();
```

### Database Manager Usage

```php
use Bu\DAL\Database\DatabaseManager;

$dbManager = app(DatabaseManager::class);

// Execute with transaction
$result = $dbManager->transaction(function() {
    // Your database operations here
    $asset = Asset::create($data);
    $employee = Employee::create($employeeData);

    return $asset;
});

// Test connection
if ($dbManager->testConnection()) {
    echo "Database connection successful!";
}

// Get database info
$info = $dbManager->getDatabaseInfo();
echo "Database size: " . $info['size'];
```

### Audit System Usage

```php
use Bu\DAL\Services\AuditNotificationService;

$auditService = app(AuditNotificationService::class);

// Send audit notifications
$sentCount = $auditService->sendInitialNotifications(
    $auditPlan,
    $auditorIds,
    $locationIds
);

// Get audit statistics
$stats = $auditService->getAuditStatistics($auditPlanId);
```

## Models

The package includes the following models:

- **Asset**: Asset management with full lifecycle tracking
- **Employee**: Employee information and asset assignments
- **Location**: Multi-location support
- **Project**: Project management
- **User**: User authentication and management
- **AuditPlan**: Audit planning and management
- **AuditAsset**: Individual asset audit tracking
- **AuditAssignment**: Auditor-location assignments
- **CorrectiveAction**: Issue tracking and resolution
- **CorrectiveActionAssignment**: Action assignments

## Database Schema

The package creates the following tables:

- `assets` - Asset information
- `employees` - Employee data
- `locations` - Location information
- `projects` - Project data
- `users` - User accounts
- `audit_plans` - Audit planning
- `audit_assets` - Asset audit tracking
- `audit_assignments` - Auditor assignments
- `corrective_actions` - Issue tracking
- `corrective_action_assignments` - Action assignments
- `audit_logs` - Audit logging

## Testing Your Installation

### Test REST API

```bash
# Test locations endpoint
curl http://localhost:8000/api/locations

# Test employees endpoint
curl http://localhost:8000/api/employees

# Test assets endpoint
curl http://localhost:8000/api/assets
```

### Test GraphQL

```bash
# Test GraphQL introspection
curl -X POST http://localhost:8000/api/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ __schema { types { name } } }"}'

# Test assets query
curl -X POST http://localhost:8000/api/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ assets { data { id asset_id } } }"}'
```

### Test Package Services

Create a test route in `routes/web.php`:

```php
Route::get('/test-package', function () {
    try {
        $dbManager = app(\Bu\DAL\Database\DatabaseManager::class);
        $assetRepo = app(\Bu\DAL\Database\Repositories\AssetRepository::class);

        return response()->json([
            'status' => 'success',
            'message' => 'Package is working correctly!',
            'database_manager' => get_class($dbManager),
            'asset_repository' => get_class($assetRepo),
            'timestamp' => now()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => now()
        ], 500);
    }
});
```

Then visit: `http://localhost:8000/test-package`

## Troubleshooting

### Common Issues

1. **GraphQL endpoint not found**

   - Ensure Lighthouse is properly configured
   - Check that the route is set to `/api/graphql`
   - Clear route cache: `php artisan route:clear`

2. **REST API endpoints not working**

   - Ensure the package service provider is registered
   - Check that API routes are enabled in `bootstrap/app.php`
   - Clear route cache: `php artisan route:clear`

3. **Database connection issues**

   - Verify your `.env` database configuration
   - Ensure MySQL is running
   - Test connection: `php artisan tinker` then `DB::connection()->getPdo()`

4. **Migration errors**
   - Ensure database exists
   - Check database permissions
   - Run migrations individually if needed

### Debug Mode

Enable debug mode in your `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Add tests for new functionality
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Submit a pull request

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For support and questions:

- Create an issue in the repository
- Contact the development team
- Check the documentation

## Changelog

### Version 1.0.0

- Initial release
- Complete DAL implementation
- GraphQL API support
- REST API endpoints
- Repository pattern
- Transaction management
- Audit system
- Asset management
- Employee management
- Location and project support
