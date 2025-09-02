#!/bin/bash

# Deployment script for Laravel GraphQL DAL Package
# This script helps deploy the package to a Laravel application

set -e

echo "🚀 Laravel GraphQL DAL Package Deployment"
echo "=========================================="
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project."
    echo "Please run this script from the root of your Laravel application."
    exit 1
fi

echo "✅ Laravel project detected"

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found"
    exit 1
fi

echo "✅ composer.json found"

# Check if the package is already installed
if grep -q "yourcompany/laravel-graphql-dal" composer.json; then
    echo "✅ Package already installed"
else
    echo "📦 Adding package to composer.json..."
    
    # Add the package to composer.json
    composer require yourcompany/laravel-graphql-dal:dev-main
    
    echo "✅ Package added to composer.json"
fi

# Check if service provider is registered
if grep -q "YourCompany\\\\GraphQLDAL\\\\Providers\\\\GraphQLDALServiceProvider" config/app.php; then
    echo "✅ Service provider already registered"
else
    echo "⚠️  Service provider not found in config/app.php"
    echo "Please add the following to your config/app.php providers array:"
    echo "YourCompany\\GraphQLDAL\\Providers\\GraphQLDALServiceProvider::class,"
    echo ""
fi

# Check if GraphQL config exists
if [ -f "config/graphql.php" ]; then
    echo "✅ GraphQL configuration found"
else
    echo "⚠️  GraphQL configuration not found"
    echo "Please ensure you have rebing/graphql-laravel installed and configured"
fi

# Check if database configuration exists
if [ -f "config/database.php" ]; then
    echo "✅ Database configuration found"
else
    echo "❌ Database configuration not found"
    exit 1
fi

# Publish package configuration
echo "📋 Publishing package configuration..."
php artisan vendor:publish --provider="YourCompany\GraphQLDAL\Providers\GraphQLDALServiceProvider" --force

echo "✅ Package configuration published"

# Run migrations
echo "🗄️  Running migrations..."
php artisan migrate --force

echo "✅ Migrations completed"

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Caches cleared"

# Check if .env file exists
if [ -f ".env" ]; then
    echo "✅ .env file found"
    
    # Check if database configuration is set
    if grep -q "DB_CONNECTION=" .env; then
        echo "✅ Database configuration found in .env"
    else
        echo "⚠️  Database configuration not found in .env"
        echo "Please add the following to your .env file:"
        echo "DB_CONNECTION=mysql"
        echo "DB_HOST=127.0.0.1"
        echo "DB_PORT=3306"
        echo "DB_DATABASE=your_database_name"
        echo "DB_USERNAME=your_username"
        echo "DB_PASSWORD=your_password"
        echo ""
    fi
else
    echo "⚠️  .env file not found"
    echo "Please create a .env file with your database configuration"
fi

echo ""
echo "🎉 Deployment completed successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Configure your .env file with database settings"
echo "2. Test the GraphQL endpoint at /graphql"
echo "3. Check the package documentation for usage examples"
echo ""
echo "🔗 Useful commands:"
echo "- php artisan graphql:playground (if available)"
echo "- php artisan route:list | grep graphql"
echo "- php artisan tinker (to test repositories)"
echo ""
