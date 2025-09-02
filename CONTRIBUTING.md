# Contributing to Laravel GraphQL DAL Package

Thank you for your interest in contributing to the Laravel GraphQL DAL Package! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Testing](#testing)
- [Documentation](#documentation)
- [Issue Reporting](#issue-reporting)
- [Feature Requests](#feature-requests)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Laravel 10.x, 11.x, or 12.x
- MySQL, PostgreSQL, or SQLite
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:

   ```bash
   git clone https://github.com/yourusername/laravel-graphql-dal.git
   cd laravel-graphql-dal
   ```

3. Add the upstream repository:
   ```bash
   git remote add upstream https://github.com/yourcompany/laravel-graphql-dal.git
   ```

## Development Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Setup Development Environment

```bash
php dev-setup.php
```

### 3. Configure Testing

Create a `.env.testing` file:

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### 4. Run Tests

```bash
php run-tests.php
```

## Contributing Guidelines

### Branch Naming

- **Feature branches:** `feature/description-of-feature`
- **Bug fixes:** `bugfix/description-of-bug`
- **Hotfixes:** `hotfix/description-of-hotfix`
- **Documentation:** `docs/description-of-docs`

### Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
type(scope): description

[optional body]

[optional footer(s)]
```

**Types:**

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**

```
feat(repository): add bulk upsert method to AssetRepository
fix(graphql): resolve pagination issue in asset queries
docs(readme): update installation instructions
test(repository): add unit tests for EmployeeRepository
```

### Code Style

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use meaningful variable and method names
- Add PHPDoc comments for all public methods
- Keep methods focused and single-purpose
- Use type hints for all parameters and return types

### File Structure

Maintain the existing package structure:

```
src/
├── Database/
│   ├── DatabaseManager.php
│   ├── TransactionManager.php
│   └── Repositories/
├── Models/
├── GraphQL/
│   ├── Queries/
│   ├── Mutations/
│   └── Schema/
├── Exceptions/
└── Providers/
```

## Pull Request Process

### 1. Create a Feature Branch

```bash
git checkout -b feature/your-feature-name
```

### 2. Make Your Changes

- Write clean, well-documented code
- Add tests for new functionality
- Update documentation as needed
- Ensure all tests pass

### 3. Test Your Changes

```bash
# Run all tests
php run-tests.php

# Run specific tests
php vendor/bin/phpunit tests/Unit/YourTest.php

# Validate package
php validate.php
```

### 4. Commit Your Changes

```bash
git add .
git commit -m "feat(repository): add new method to AssetRepository"
```

### 5. Push to Your Fork

```bash
git push origin feature/your-feature-name
```

### 6. Create a Pull Request

1. Go to your fork on GitHub
2. Click "New Pull Request"
3. Select your feature branch
4. Fill out the PR template
5. Submit the PR

### Pull Request Template

```markdown
## Description

Brief description of the changes

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing

- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] All tests pass
- [ ] Manual testing completed

## Checklist

- [ ] Code follows PSR-12 standards
- [ ] PHPDoc comments added
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] No breaking changes (or documented if necessary)
```

## Testing

### Writing Tests

#### Unit Tests

Test individual repository methods and business logic:

```php
<?php

namespace YourCompany\GraphQLDAL\Tests\Unit;

use YourCompany\GraphQLDAL\Tests\TestCase;
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;

class AssetRepositoryTest extends TestCase
{
    /** @test */
    public function it_can_create_an_asset()
    {
        $assetData = [
            'asset_id' => 'TEST-001',
            'type' => 'Laptop',
            'status' => '利用中'
        ];

        $asset = $this->assetRepository->create($assetData);

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertDatabaseHas('assets', ['asset_id' => 'TEST-001']);
    }
}
```

#### Feature Tests

Test GraphQL queries and mutations:

```php
<?php

namespace YourCompany\GraphQLDAL\Tests\Feature;

use YourCompany\GraphQLDAL\Tests\TestCase;

class GraphQLTest extends TestCase
{
    /** @test */
    public function it_can_query_assets_via_graphql()
    {
        $query = '
            query {
                assets(first: 10) {
                    data {
                        id
                        asset_id
                        type
                    }
                }
            }
        ';

        $response = $this->postGraphQL(['query' => $query]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'assets' => [
                    'data' => [
                        '*' => ['id', 'asset_id', 'type']
                    ]
                ]
            ]
        ]);
    }
}
```

### Running Tests

```bash
# Run all tests
php run-tests.php

# Run specific test suite
php vendor/bin/phpunit tests/Unit/
php vendor/bin/phpunit tests/Feature/

# Run with coverage
php vendor/bin/phpunit --coverage-html coverage/
```

## Documentation

### Updating Documentation

When adding new features or making changes:

1. **README.md**: Update installation and usage instructions
2. **PACKAGE_INFO.md**: Update package information and examples
3. **CHANGELOG.md**: Add entries for new features and fixes
4. **Code Comments**: Add PHPDoc comments for all public methods

### Documentation Standards

- Use clear, concise language
- Provide code examples
- Include error handling examples
- Update all relevant sections
- Test all code examples

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

1. **Environment Information**

   - PHP version
   - Laravel version
   - Package version
   - Database type and version

2. **Steps to Reproduce**

   - Clear, numbered steps
   - Expected behavior
   - Actual behavior

3. **Error Messages**

   - Full error messages
   - Stack traces
   - Log files (if relevant)

4. **Code Examples**
   - Minimal code to reproduce the issue
   - Configuration files (sanitized)

### Feature Requests

When requesting features:

1. **Use Case**

   - Describe the problem you're trying to solve
   - Explain why this feature would be useful

2. **Proposed Solution**

   - Describe your proposed solution
   - Include any design considerations

3. **Alternatives**
   - Describe any alternative solutions you've considered
   - Explain why they don't work for your use case

## Feature Requests

### Before Submitting

1. Check existing issues and pull requests
2. Search the documentation
3. Consider if it fits the package's scope
4. Think about backward compatibility

### Submitting

1. Use the feature request template
2. Provide clear use cases
3. Include examples if possible
4. Consider implementation complexity

## Code Review Process

### For Contributors

1. **Self-Review**: Review your own code before submitting
2. **Test Coverage**: Ensure adequate test coverage
3. **Documentation**: Update relevant documentation
4. **Responsiveness**: Respond to review feedback promptly

### For Maintainers

1. **Timely Reviews**: Review PRs within 48 hours
2. **Constructive Feedback**: Provide helpful, specific feedback
3. **Testing**: Verify that tests pass and coverage is adequate
4. **Documentation**: Check that documentation is updated

## Release Process

### Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] All tests pass
- [ ] Documentation updated
- [ ] Changelog updated
- [ ] Version numbers updated
- [ ] Tag created
- [ ] Release notes written

## Getting Help

### Resources

- [README.md](README.md) - Basic usage and installation
- [PACKAGE_INFO.md](PACKAGE_INFO.md) - Detailed package information
- [Examples](examples/) - Usage examples
- [Tests](tests/) - Test examples and patterns

### Contact

- **Issues**: Use GitHub issues for bugs and feature requests
- **Discussions**: Use GitHub discussions for questions
- **Email**: Contact the maintainers directly for sensitive issues

## Recognition

Contributors will be recognized in:

- **README.md**: Contributor list
- **CHANGELOG.md**: Release notes
- **GitHub**: Contributor statistics

Thank you for contributing to the Laravel GraphQL DAL Package! 🎉
