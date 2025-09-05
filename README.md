# BU DAL Package

A comprehensive Laravel package that provides a complete Data Access Layer (DAL) with GraphQL and REST API support for business applications. This package extracts database operations, repository patterns, and API functionality into a reusable component.

## 🚀 Features

- **📊 Database Management**: Advanced database connection management with MySQL support
- **🏗️ Repository Pattern**: Clean separation of data access logic with BaseRepository
- **🔍 GraphQL Integration**: Complete GraphQL API with queries, mutations, and schema
- **🌐 REST API**: Comprehensive REST API endpoints (1,500+ lines) for all models
- **🔄 Transaction Management**: Automatic transaction handling with rollback support
- **📋 Audit System**: Complete audit tracking and notification system
- **📦 Asset Management**: Full asset lifecycle management with status tracking
- **👥 Employee Management**: Employee and user management system
- **🏢 Location & Project Management**: Multi-location and project support
- **📧 Email Notifications**: Automated email system for audits and corrective actions
- **⚡ Console Commands**: Artisan commands for system maintenance and testing
- **🛡️ CORS Middleware**: Built-in CORS support for GraphQL endpoints

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- MySQL 5.7 or higher (XAMPP compatible)
- Composer
- Nuwave Lighthouse (for GraphQL)

## 🛠️ Installation

### 🚀 Quick Installation (Recommended)

For a complete setup with all necessary files, use the automated installer:

```bash
# 1. Install the package
composer require bu/dal-package:dev-main

# 2. Run the automated installer
php artisan dal:install
```

The installer will automatically:

- ✅ Publish all package files (config, migrations, GraphQL schema, email templates, views, controllers)
- ✅ Install and configure Lighthouse
- ✅ Update Lighthouse configuration with correct namespaces
- ✅ Add DAL configuration to .env.example
- ✅ Provide next steps instructions

### 📋 Manual Installation

If you prefer manual installation:

#### Step 1: Install via Composer

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

#### Step 2: Install Lighthouse (GraphQL)

```bash
composer require nuwave/lighthouse
```

#### Step 3: Publish All Package Files

```bash
# Publish everything at once
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-all"

# Or publish individually:
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-config"
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-migrations"
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-graphql"
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-email-templates"
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-views"
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-controllers"
```

#### Step 4: Configure Environment (.env)

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=your_password

# GraphQL Configuration
LIGHTHOUSE_SCHEMA_PATH=graphql/schema.graphql

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# DAL Package Configuration
DAL_DEFAULT_CONNECTION=mysql
DAL_CACHE_ENABLED=true
DAL_CACHE_TTL=3600
DAL_GRAPHQL_ENABLED=true
DAL_LOGGING_ENABLED=true
DAL_LOG_LEVEL=info
```

#### Step 5: Run Migrations

```bash
php artisan migrate
```

#### Step 6: Configure Lighthouse (GraphQL)

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
],
```

## 📁 Package Structure

```
bu-dal-package/
├── src/
│   ├── Console/Commands/          # Artisan commands
│   │   ├── SendAuditReminders.php
│   │   ├── SendCorrectiveActionReminders.php
│   │   ├── TestAuditPlanAccess.php
│   │   └── TestAuditSystem.php
│   ├── Database/
│   │   ├── DatabaseManager.php   # Database connection manager
│   │   └── Repositories/         # Repository pattern implementation
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
│   ├── Exceptions/               # Custom exception classes
│   │   ├── DatabaseException.php
│   │   ├── GraphQLException.php
│   │   ├── RepositoryException.php
│   │   └── TransactionException.php
│   ├── GraphQL/
│   │   ├── Mutations/            # GraphQL mutations
│   │   │   ├── AssetMutations.php
│   │   │   ├── AuditPlanMutations.php
│   │   │   ├── CreateAuditPlan.php
│   │   │   ├── UpdateAuditPlan.php
│   │   │   ├── EmployeeMutations.php
│   │   │   ├── LocationMutations.php
│   │   │   ├── ProjectMutations.php
│   │   │   ├── UserMutations.php
│   │   │   ├── CorrectiveActionMutations.php
│   │   │   ├── CorrectiveActionAssignmentMutations.php
│   │   │   ├── UpdateAuditAsset.php
│   │   │   └── UpdateAuditAssignment.php
│   │   └── Queries/              # GraphQL queries
│   │       ├── AssetQueries.php
│   │       ├── AuditAssetQueries.php
│   │       ├── AuditAssignmentQueries.php
│   │       ├── AuditPlanQueries.php
│   │       ├── CorrectiveActionQueries.php
│   │       ├── EmployeeQueries.php
│   │       ├── LocationQueries.php
│   │       ├── ProjectQueries.php
│   │       └── UserQueries.php
│   ├── Http/Middleware/          # HTTP middleware
│   │   └── GraphQLCors.php
│   ├── Mail/                     # Email templates
│   │   ├── AuditAccessEmail.php
│   │   ├── AuditPlanNotificationEmail.php
│   │   ├── AuditReminderEmail.php
│   │   ├── ConsolidatedCorrectiveActionEmail.php
│   │   └── CorrectiveActionNotificationEmail.php
│   ├── Models/                   # Eloquent models
│   │   ├── Asset.php
│   │   ├── AuditAsset.php
│   │   ├── AuditAssignment.php
│   │   ├── AuditLog.php
│   │   ├── AuditPlan.php
│   │   ├── CorrectiveAction.php
│   │   ├── CorrectiveActionAssignment.php
│   │   ├── Employee.php
│   │   ├── Location.php
│   │   ├── Project.php
│   │   └── User.php
│   ├── Providers/
│   │   └── DALServiceProvider.php # Laravel service provider
│   ├── Routes/
│   │   └── api.php               # API routes (1,500+ lines)
│   └── Services/                 # Business logic services
│       ├── AuditNotificationService.php
│       └── CorrectiveActionNotificationService.php
├── database/migrations/          # Database migrations (32 files)
├── graphql/
│   └── schema.graphql            # GraphQL schema
├── config/
│   └── dal.php                   # Package configuration
└── tests/                        # Unit and feature tests
```

## 🎯 Available Models

| Model                          | Description         | Key Features                                   |
| ------------------------------ | ------------------- | ---------------------------------------------- |
| **Asset**                      | Asset management    | Status tracking, location, employee assignment |
| **Employee**                   | Employee management | Email, location, project assignments           |
| **Location**                   | Location management | Multi-location support, visibility controls    |
| **Project**                    | Project management  | Project assignments, ordering                  |
| **User**                       | User authentication | Basic user management                          |
| **AuditPlan**                  | Audit planning      | Comprehensive audit workflow                   |
| **AuditAsset**                 | Asset auditing      | Status tracking, auditor notes                 |
| **AuditAssignment**            | Auditor assignments | Assignment management                          |
| **AuditLog**                   | Audit logging       | Complete audit trail                           |
| **CorrectiveAction**           | Corrective actions  | Action tracking, assignments                   |
| **CorrectiveActionAssignment** | Action assignments  | Employee assignments                           |

## 🔧 GraphQL API

### Queries

- `assets` - List all assets with filtering and pagination
- `employees` - List all employees
- `locations` - List all locations
- `projects` - List all projects
- `auditPlans` - List all audit plans
- `auditAssets` - List audit assets
- `auditAssignments` - List audit assignments
- `correctiveActions` - List corrective actions

### Mutations

#### Asset Management

- `upsertAsset` - Create or update asset
- `bulkUpsertAssets` - Bulk create/update assets
- `deleteAsset` - Delete asset

#### Employee Management

- `createEmployee` - Create employee
- `updateEmployee` - Update employee
- `upsertEmployee` - Create or update employee
- `bulkUpsertEmployees` - Bulk create/update employees
- `deleteEmployee` - Delete employee

#### Audit Management

- `createAuditPlan` - Create audit plan
- `updateAuditPlan` - Update audit plan
- `updateAuditAsset` - Update audit asset status
- `completeAuditAssignment` - Complete audit assignment

#### Corrective Actions

- `createCorrectiveAction` - Create corrective action
- `updateCorrectiveAction` - Update corrective action
- `assignCorrectiveAction` - Assign corrective action to employee
- `updateCorrectiveActionAssignmentStatus` - Update assignment status

## 🌐 REST API Endpoints

The package automatically registers comprehensive REST API endpoints:

### Asset Management

- `GET /api/assets` - List assets
- `POST /api/assets` - Create asset
- `PUT /api/assets/{id}` - Update asset
- `DELETE /api/assets/{id}` - Delete asset

### Employee Management

- `GET /api/employees` - List employees
- `POST /api/employees` - Create employee
- `PUT /api/employees/{id}` - Update employee
- `DELETE /api/employees/{id}` - Delete employee

### Audit System

- `GET /api/audit-plans` - List audit plans
- `POST /api/audit-plans` - Create audit plan
- `PUT /api/audit-plans/{id}` - Update audit plan
- `GET /api/employee-audits/access/{token}` - Employee audit access
- `POST /api/employee-audits/update-asset/{token}` - Update asset status

### Corrective Actions

- `GET /api/corrective-actions` - List corrective actions
- `POST /api/corrective-actions` - Create corrective action
- `PUT /api/corrective-actions/{id}` - Update corrective action
- `POST /api/corrective-actions/assign` - Assign corrective action

## ⚡ Console Commands

```bash
# Send audit reminders
php artisan audits:send-reminders

# Send corrective action reminders
php artisan corrective-actions:send-reminders

# Test audit plan access
php artisan audits:test-access {audit_plan_id} {employee_email}

# Test audit system
php artisan audits:test-system
```

## 📧 Email Notifications

The package includes comprehensive email notification system:

- **Audit Access Email** - Employee audit access links
- **Audit Plan Notifications** - Audit plan creation/updates
- **Audit Reminders** - Pending audit reminders
- **Corrective Action Notifications** - Action assignments and updates
- **Consolidated Emails** - Grouped notifications to reduce email spam

## 🔧 Configuration

### Package Configuration (`config/dal.php`)

```php
return [
    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'prefix' => env('DB_PREFIX', ''),
    ],
    'graphql' => [
        'enabled' => env('DAL_GRAPHQL_ENABLED', true),
        'schema_path' => env('DAL_GRAPHQL_SCHEMA_PATH', 'graphql/schema.graphql'),
    ],
    'notifications' => [
        'audit_reminders' => [
            'enabled' => env('DAL_AUDIT_REMINDERS_ENABLED', true),
            'days_before' => env('DAL_AUDIT_REMINDER_DAYS', 3),
        ],
        'corrective_actions' => [
            'enabled' => env('DAL_CORRECTIVE_ACTION_NOTIFICATIONS_ENABLED', true),
        ],
    ],
];
```

## 🧪 Testing

Run the package tests:

```bash
# Run all tests
composer test

# Run specific test suite
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Feature/
```

## 🚀 Usage Examples

### Using Repositories

```php
use Bu\DAL\Database\Repositories\AssetRepository;

// Get asset repository
$assetRepo = app(AssetRepository::class);

// Find asset by ID
$asset = $assetRepo->find(1);

// Find assets by status
$activeAssets = $assetRepo->where('status', 'active')->get();

// Create new asset
$asset = $assetRepo->create([
    'asset_id' => 'ASSET-001',
    'name' => 'Laptop Computer',
    'status' => 'active',
    'location_id' => 1,
]);
```

### Using GraphQL

```graphql
# Query assets
query {
  assets(first: 10) {
    data {
      id
      asset_id
      name
      status
      location {
        name
      }
      employee {
        name
        email
      }
    }
  }
}

# Create audit plan
mutation {
  createAuditPlan(
    name: "Q1 2024 Audit"
    start_date: "2024-01-01"
    due_date: "2024-03-31"
    locations: [1, 2, 3]
    auditors: [1, 2]
  ) {
    id
    name
    status
  }
}
```

### Using REST API

```bash
# Get all assets
curl -X GET http://your-app.com/api/assets

# Create new employee
curl -X POST http://your-app.com/api/employees \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'
```

## 🔄 Migration from Existing Server

If you're migrating from an existing Laravel server, the package now includes all necessary files:

1. **Install the package** in your new Laravel project
2. **Run the automated installer**: `php artisan dal:install`
3. **Configure your database** in `.env` file
4. **Run migrations**: `php artisan migrate`
5. **Configure mail settings** in `.env` file

### ✅ Included Files

The package now includes all necessary files:

```
bu-dal-package/resources/
├── views/
│   ├── emails/
│   │   ├── audit-access.blade.php
│   │   ├── audit-plan-notification.blade.php
│   │   ├── audit-reminder.blade.php
│   │   ├── consolidated-corrective-action.blade.php
│   │   └── corrective-action-notification.blade.php
│   └── graphql-playground.blade.php
└── Http/
    └── Controllers/
        └── Controller.php
```

**No manual copying required!** The `php artisan dal:install` command handles everything automatically.

## 🐛 Troubleshooting

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
   - Ensure MySQL is running (XAMPP)
   - Test connection: `php artisan tinker` then `DB::connection()->getPdo()`

4. **Migration errors**

   - Ensure database exists
   - Check database permissions
   - Run migrations individually if needed

5. **Email notifications not working**
   - Configure mail settings in `.env`
   - Check mail queue configuration
   - **Run `php artisan dal:install` to publish email templates**
   - Verify email templates exist in `resources/views/emails/`
   - Check Laravel logs for "View not found" errors

### Debug Commands

```bash
# Test the audit system
php artisan audits:test-system

# Check package installation
composer show bu/dal-package

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📝 Changelog

### Version 1.0.0

- ✅ Complete Data Access Layer implementation
- ✅ 11 Eloquent models with relationships
- ✅ 32 database migrations
- ✅ 12 GraphQL mutations
- ✅ 9 GraphQL queries
- ✅ 1,500+ lines of REST API routes
- ✅ Repository pattern implementation
- ✅ Email notification system
- ✅ Console commands for maintenance
- ✅ CORS middleware support
- ✅ Comprehensive testing suite

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🆘 Support

For support and questions:

- Create an issue on GitHub
- Check the troubleshooting section
- Review the test files for usage examples

---

**Made with ❤️ for Business Unit applications**
