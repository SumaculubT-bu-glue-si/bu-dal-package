# Deployment script for Laravel GraphQL DAL Package
# This script helps deploy the package to a Laravel application

Write-Host "🚀 Laravel GraphQL DAL Package Deployment" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

# Check if we're in a Laravel project
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: This doesn't appear to be a Laravel project." -ForegroundColor Red
    Write-Host "Please run this script from the root of your Laravel application." -ForegroundColor Red
    exit 1
}

Write-Host "✅ Laravel project detected" -ForegroundColor Green

# Check if composer.json exists
if (-not (Test-Path "composer.json")) {
    Write-Host "❌ Error: composer.json not found" -ForegroundColor Red
    exit 1
}

Write-Host "✅ composer.json found" -ForegroundColor Green

# Check if the package is already installed
$composerContent = Get-Content "composer.json" -Raw
if ($composerContent -match "yourcompany/laravel-graphql-dal") {
    Write-Host "✅ Package already installed" -ForegroundColor Green
} else {
    Write-Host "📦 Adding package to composer.json..." -ForegroundColor Yellow
    
    # Add the package to composer.json
    composer require yourcompany/laravel-graphql-dal:dev-main
    
    Write-Host "✅ Package added to composer.json" -ForegroundColor Green
}

# Check if service provider is registered
if (Test-Path "config/app.php") {
    $appConfig = Get-Content "config/app.php" -Raw
    if ($appConfig -match "YourCompany\\\\GraphQLDAL\\\\Providers\\\\GraphQLDALServiceProvider") {
        Write-Host "✅ Service provider already registered" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Service provider not found in config/app.php" -ForegroundColor Yellow
        Write-Host "Please add the following to your config/app.php providers array:" -ForegroundColor Yellow
        Write-Host "YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider::class," -ForegroundColor Yellow
        Write-Host ""
    }
} else {
    Write-Host "⚠️  config/app.php not found" -ForegroundColor Yellow
}

# Check if GraphQL config exists
if (Test-Path "config/graphql.php") {
    Write-Host "✅ GraphQL configuration found" -ForegroundColor Green
} else {
    Write-Host "⚠️  GraphQL configuration not found" -ForegroundColor Yellow
    Write-Host "Please ensure you have rebing/graphql-laravel installed and configured" -ForegroundColor Yellow
}

# Check if database configuration exists
if (Test-Path "config/database.php") {
    Write-Host "✅ Database configuration found" -ForegroundColor Green
} else {
    Write-Host "❌ Database configuration not found" -ForegroundColor Red
    exit 1
}

# Publish package configuration
Write-Host "📋 Publishing package configuration..." -ForegroundColor Yellow
php artisan vendor:publish --provider="YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider" --force

Write-Host "✅ Package configuration published" -ForegroundColor Green

# Run migrations
Write-Host "🗄️  Running migrations..." -ForegroundColor Yellow
php artisan migrate --force

Write-Host "✅ Migrations completed" -ForegroundColor Green

# Clear caches
Write-Host "🧹 Clearing caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

Write-Host "✅ Caches cleared" -ForegroundColor Green

# Check if .env file exists
if (Test-Path ".env") {
    Write-Host "✅ .env file found" -ForegroundColor Green
    
    # Check if database configuration is set
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "DB_CONNECTION=") {
        Write-Host "✅ Database configuration found in .env" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Database configuration not found in .env" -ForegroundColor Yellow
        Write-Host "Please add the following to your .env file:" -ForegroundColor Yellow
        Write-Host "DB_CONNECTION=mysql" -ForegroundColor Yellow
        Write-Host "DB_HOST=127.0.0.1" -ForegroundColor Yellow
        Write-Host "DB_PORT=3306" -ForegroundColor Yellow
        Write-Host "DB_DATABASE=your_database_name" -ForegroundColor Yellow
        Write-Host "DB_USERNAME=your_username" -ForegroundColor Yellow
        Write-Host "DB_PASSWORD=your_password" -ForegroundColor Yellow
        Write-Host ""
    }
} else {
    Write-Host "⚠️  .env file not found" -ForegroundColor Yellow
    Write-Host "Please create a .env file with your database configuration" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🎉 Deployment completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Configure your .env file with database settings" -ForegroundColor White
Write-Host "2. Test the GraphQL endpoint at /graphql" -ForegroundColor White
Write-Host "3. Check the package documentation for usage examples" -ForegroundColor White
Write-Host ""
Write-Host "🔗 Useful commands:" -ForegroundColor Cyan
Write-Host "- php artisan graphql:playground (if available)" -ForegroundColor White
Write-Host "- php artisan route:list | grep graphql" -ForegroundColor White
Write-Host "- php artisan tinker (to test repositories)" -ForegroundColor White
Write-Host ""
