# Laravel GraphQL DAL Package Makefile
# Common tasks for package development and maintenance

.PHONY: help install test validate dev-setup deploy clean

# Default target
help:
	@echo "Laravel GraphQL DAL Package - Available Commands:"
	@echo ""
	@echo "  install     - Install package dependencies"
	@echo "  test        - Run all tests"
	@echo "  validate    - Validate package structure and configuration"
	@echo "  dev-setup   - Setup development environment"
	@echo "  deploy      - Deploy package to Laravel project"
	@echo "  clean       - Clean temporary files and caches"
	@echo "  docs        - Generate documentation"
	@echo "  format      - Format code using PHP CS Fixer"
	@echo "  lint        - Lint code using PHP CS Fixer"
	@echo ""

# Install dependencies
install:
	@echo "Installing package dependencies..."
	composer install
	@echo "✅ Dependencies installed"

# Run tests
test:
	@echo "Running package tests..."
	php run-tests.php
	@echo "✅ Tests completed"

# Validate package
validate:
	@echo "Validating package structure..."
	php validate.php
	@echo "✅ Validation completed"

# Setup development environment
dev-setup:
	@echo "Setting up development environment..."
	php dev-setup.php
	@echo "✅ Development environment setup completed"

# Deploy package
deploy:
	@echo "Deploying package..."
	php deploy.ps1
	@echo "✅ Deployment completed"

# Clean temporary files
clean:
	@echo "Cleaning temporary files..."
	rm -rf vendor/
	rm -rf coverage/
	rm -rf cache/
	rm -rf logs/
	rm -f composer.lock
	rm -f .phpunit.result.cache
	rm -f .phpunit.cache
	@echo "✅ Cleanup completed"

# Generate documentation
docs:
	@echo "Generating documentation..."
	@echo "Documentation is available in:"
	@echo "  - README.md"
	@echo "  - PACKAGE_INFO.md"
	@echo "  - CHANGELOG.md"
	@echo "  - CONTRIBUTING.md"
	@echo "  - examples/"
	@echo "✅ Documentation available"

# Format code (requires PHP CS Fixer)
format:
	@echo "Formatting code..."
	@if command -v php-cs-fixer >/dev/null 2>&1; then \
		php-cs-fixer fix src/ --rules=@PSR12; \
		php-cs-fixer fix tests/ --rules=@PSR12; \
		echo "✅ Code formatted"; \
	else \
		echo "⚠️  PHP CS Fixer not found. Install with: composer require --dev friendsofphp/php-cs-fixer"; \
	fi

# Lint code (requires PHP CS Fixer)
lint:
	@echo "Linting code..."
	@if command -v php-cs-fixer >/dev/null 2>&1; then \
		php-cs-fixer fix src/ --rules=@PSR12 --dry-run --diff; \
		php-cs-fixer fix tests/ --rules=@PSR12 --dry-run --diff; \
		echo "✅ Code linting completed"; \
	else \
		echo "⚠️  PHP CS Fixer not found. Install with: composer require --dev friendsofphp/php-cs-fixer"; \
	fi

# Run specific test
test-unit:
	@echo "Running unit tests..."
	php vendor/bin/phpunit tests/Unit/
	@echo "✅ Unit tests completed"

test-feature:
	@echo "Running feature tests..."
	php vendor/bin/phpunit tests/Feature/
	@echo "✅ Feature tests completed"

# Run tests with coverage
test-coverage:
	@echo "Running tests with coverage..."
	php vendor/bin/phpunit --coverage-html coverage/
	@echo "✅ Coverage report generated in coverage/"

# Check code quality
quality:
	@echo "Checking code quality..."
	@echo "Running tests..."
	php run-tests.php
	@echo "Validating package..."
	php validate.php
	@echo "✅ Code quality check completed"

# Full development setup
dev:
	@echo "Setting up full development environment..."
	make install
	make dev-setup
	make test
	make validate
	@echo "✅ Full development setup completed"

# Release preparation
release-prep:
	@echo "Preparing for release..."
	make clean
	make install
	make test
	make validate
	make docs
	@echo "✅ Release preparation completed"

# Quick development cycle
dev-cycle:
	@echo "Running development cycle..."
	make test
	make validate
	@echo "✅ Development cycle completed"
