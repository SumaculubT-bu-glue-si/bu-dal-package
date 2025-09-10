# Business Unit Server Package

A Laravel package that provides server functionality for the Business Unit client application, including asset management, audit planning, and corrective action tracking.

## Requirements

- PHP ^8.1
- Laravel ^10.0
- MySQL/PostgreSQL database
- Composer

## Creating a New Project with this Package

### Step 1: Create a new Laravel project

```bash
composer create-project laravel/laravel your-project-name
cd your-project-name
```

### Step 2: Add the package repository

Add this to your `composer.json`:

```json
{
  "minimum-stability": "dev",
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/izuminaoki2025/bu-dal-package.git"
    }
  ],
  "require": {
    "bu/server": "dev-main"
  }
}
```

### Step 3: Install required packages

```bash
composer require bu/server:dev-main
composer require doctrine/dbal
composer require nuwave/lighthouse
composer require laravel/sanctum
```

### Step 4: Copy Required Files

1. Create required directories:

```bash
mkdir graphql
```

2. Copy these files from server-package to your project:

```bash
# GraphQL Schema
cp vendor/bu/server/graphql/schema.graphql ./graphql/

# Configuration files
cp vendor/bu/server/config/lighthouse.php ./config/

# Configure kernel
cp vendor/bu/server/src/Http/Kernel.php ./app/Http/

# Add routes/api
cp vendor/bu/server/routes/api.php ./routes/

# Configure route dependency
cp vendor/bu/server/config/app.php ./bootstrap/
```

### Step 3: Configure Environment

Set up your `.env` file with the required configurations:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=studio_db
DB_USERNAME=root
DB_PASSWORD=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=sumaculub_t@bu.glue-si.com
MAIL_PASSWORD=trbwodxdampcrecs
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gmail.com
MAIL_FROM_NAME="Asset Management System"
```

## Configuration

### Middleware Setup

1. Create these middleware classes in `app/Http/Middleware/`:

```bash
php artisan make:middleware Authenticate
php artisan make:middleware RedirectIfAuthenticated
php artisan make:middleware TrimStrings
php artisan make:middleware TrustProxies
php artisan make:middleware VerifyCsrfToken
```

2. Note: The following middleware classes are provided by Laravel framework, so you don't need to create them:

- `\Illuminate\Auth\Middleware\AuthenticateWithBasicAuth`
- `\Illuminate\Auth\Middleware\AuthenticateSession`
- `\Illuminate\Http\Middleware\SetCacheHeaders`
- `\Illuminate\Auth\Middleware\Authorize`
- `\Illuminate\Auth\Middleware\RequirePassword`
- `\Illuminate\Routing\Middleware\ValidateSignature`
- `\Illuminate\Routing\Middleware\ThrottleRequests`
- `\Illuminate\Auth\Middleware\EnsureEmailIsVerified`

### CORS Configuration

To work with the client application, add the following to your `config/cors.php`:

```php
<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];

```

## Route Provider Configuration

1. Create or update `app/Providers/RouteServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
```

This provider configures:

- Rate limiting for API routes (60 requests per minute per IP)
- API routes with the 'api' middleware and 'api' prefix
- Web routes with the 'web' middleware

## Final Setup Steps

1. Run migrations:

```bash
php artisan migrate
```

2. Clear all caches:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

3. Start the server:

```bash
php artisan serve --host=0.0.0.0
```

## Usage

The package provides:

1. GraphQL API endpoints at `/graphql`
2. REST API endpoints at `/api`
3. Authentication endpoints
4. File storage handling
5. Audit system
6. Email notifications

### REST API Endpoints

The package provides the following REST API endpoints:

#### Assets

- `GET /api/assets` - List all assets
- `GET /api/assets/{id}` - Get asset details
- `POST /api/assets` - Create new asset
- `PUT /api/assets/{id}` - Update asset
- `DELETE /api/assets/{id}` - Delete asset
- `GET /api/assets/type/{type}` - Get assets by type

#### Audits

- `GET /api/audits/plans` - List audit plans
- `POST /api/audits/plans` - Create audit plan
- `GET /api/audits/plans/{id}` - Get audit plan details
- `PUT /api/audits/plans/{id}` - Update audit plan
- `GET /api/audits/assignments` - List audit assignments

#### Corrective Actions

- `GET /api/corrective-actions` - List corrective actions
- `POST /api/corrective-actions` - Create corrective action
- `GET /api/corrective-actions/{id}` - Get corrective action details
- `PUT /api/corrective-actions/{id}` - Update corrective action
- `GET /api/corrective-actions/{id}/assignments` - List assignments

## Client Integration

Update your client application's `.env` file to point to your new server:

```env
NEXT_PUBLIC_API_URL=http://your-server-url/graphql
```

## Available Commands

```bash
# Send audit reminders
php artisan server:audits:send-reminders

# Send corrective action reminders
php artisan server:corrective-actions:send-reminders
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT)

```

```
