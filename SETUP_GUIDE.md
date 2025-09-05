# Setup Guide for New Laravel Server with bu-dal-package

This guide shows you how to set up a new Laravel server project using the bu-dal-package.

## 🚀 Quick Setup

### 1. Create New Laravel Project

```bash
composer create-project laravel/laravel my-server
cd my-server
```

### 2. Install Dependencies

```bash
# Install Lighthouse for GraphQL
composer require nuwave/lighthouse

# Install the bu-dal-package
composer require bu/dal-package:dev-main
```

### 3. Configure Environment (.env)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=your_password

# GraphQL Configuration
LIGHTHOUSE_SCHEMA_PATH=graphql/schema.graphql
```

### 4. Publish Package Files

```bash
# Publish configuration
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-config"

# Publish migrations
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-migrations"

# Publish GraphQL schema
php artisan vendor:publish --provider="Bu\DAL\Providers\DALServiceProvider" --tag="dal-graphql"
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Configure Lighthouse

```bash
php artisan vendor:publish --provider="Nuwave\Lighthouse\LighthouseServiceProvider" --tag="lighthouse-config"
```

Update `config/lighthouse.php`:

```php
'route' => [
    'uri' => '/api/graphql',
],
```

## 🔧 Additional Setup for Full Functionality

### Copy Complex Routes from Your Current Server

The package provides basic CRUD routes, but for full functionality, you need to copy the complex business logic routes from your current server:

1. **Copy the entire `server/routes/api.php`** to your new server's `routes/api.php`
2. **Copy middleware** from `server/app/Http/Middleware/` to your new server
3. **Copy mail classes** from `server/app/Mail/` to your new server
4. **Copy any custom controllers** from `server/app/Http/Controllers/`

### Required Files to Copy:

```
server/app/Http/Middleware/GraphQLCors.php → my-server/app/Http/Middleware/GraphQLCors.php
server/app/Mail/AuditAccessEmail.php → my-server/app/Mail/AuditAccessEmail.php
server/routes/api.php → my-server/routes/api.php (replace the basic one)
```

### Copy Models (if needed)

If your current server has custom model logic, copy them:

```
server/app/Models/ → my-server/app/Models/
```

### Copy GraphQL Resolvers (if needed)

If your current server has custom GraphQL resolvers, copy them:

```
server/app/GraphQL/ → my-server/app/GraphQL/
```

## ✅ Testing Your Setup

### 1. Test Basic Package Functionality

```bash
# Test basic API endpoints
curl http://localhost:8000/api/locations
curl http://localhost:8000/api/assets
curl http://localhost:8000/api/employees
```

### 2. Test GraphQL

```bash
# Test GraphQL introspection
curl -X POST http://localhost:8000/api/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ __schema { types { name } } }"}'
```

### 3. Test Complex Routes (after copying)

```bash
# Test employee audit access
curl -X POST http://localhost:8000/api/employee-audits/request-access \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "audit_plan_id": "1"}'
```

## 🎯 What the Package Provides

### ✅ Included:

- Database migrations
- Models and repositories
- Basic CRUD API endpoints
- GraphQL schema
- Database management utilities
- Transaction support

### ❌ Not Included (copy from your current server):

- Complex business logic routes
- Custom middleware
- Mail classes
- Custom GraphQL resolvers
- Business-specific controllers

## 🚨 Important Notes

1. **Database**: The package defaults to MySQL - make sure your server uses MySQL too
2. **Routes**: The package provides basic routes, but you need to copy complex ones from your current server
3. **Models**: Package models are basic - copy your custom models if needed
4. **GraphQL**: Package schema points to `App\GraphQL` resolvers - make sure they exist

## 🔍 Troubleshooting

### Common Issues:

1. **GraphQL endpoint not found**

   - Ensure Lighthouse is properly configured
   - Check that the route is set to `/api/graphql`

2. **Missing resolvers**

   - Copy your GraphQL resolvers from the current server
   - Or update the package schema to use package resolvers

3. **Missing routes**

   - Copy the complex routes from your current server
   - The package only provides basic CRUD routes

4. **Database connection issues**
   - Verify your `.env` database configuration
   - Ensure MySQL is running

## 📝 Next Steps

After setup:

1. Test all endpoints
2. Verify GraphQL functionality
3. Test your client application
4. Deploy to production

The package provides the foundation, but you'll need to copy the business logic from your current server for full functionality.
