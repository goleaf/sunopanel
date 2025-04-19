# SunoPanel Testing Guide

This document provides information on how to write, maintain, and run tests for the SunoPanel application.

## Test Tooling

SunoPanel includes several custom commands to help maintain and improve test code quality:

### 1. Test Style Commands

#### Convert Doc-Comments to Attributes

PHPUnit 12 is deprecating metadata in doc-comments. Our command converts these to PHP 8 attributes:

```bash
# Via Artisan
php artisan test:convert-attributes

# Via Composer
composer test:convert-attributes
```

This command converts `@test` doc-comments to `#[Test]` attributes.

#### Fix Test Styles

Apply Laravel Pint code styling rules to test files:

```bash
# Via Artisan
php artisan test:style-fix
# With path option
php artisan test:style-fix --path=tests/Feature

# Via Composer
composer test:fix-style
```

### 2. Generate Missing Tests

Generate test stubs for classes without corresponding tests:

```bash
# Via Artisan
php artisan test:generate-missing
# For specific test type
php artisan test:generate-missing --type=unit
php artisan test:generate-missing --type=feature

# Via Composer
composer test:generate-missing
```

This command scans the application for classes without tests and generates stub test files with placeholders for each public method.

### 3. Lint and Test

Run the linter and tests in sequence:

```bash
# Via Composer
composer test:lint
```

This command first fixes coding style issues with Laravel Pint and then runs the tests.

## Writing Tests

### Best Practices

1. **Use Attributes**: Use PHP 8 attributes instead of doc-comments for test annotations:
   ```php
   #[Test]
   public function testSomething(): void
   {
       // Test code
   }
   ```

2. **Naming Conventions**:
   - Feature tests should be placed in `tests/Feature/`
   - Unit tests should be placed in `tests/Unit/`
   - Test class names should match the tested class name with "Test" suffix
   - Test method names should be descriptive of what they're testing

3. **Arrange-Act-Assert Pattern**:
   ```php
   #[Test]
   public function testSomething(): void
   {
       // Arrange - set up the test
       $model = new Model();
       
       // Act - perform the action
       $result = $model->doSomething();
       
       // Assert - check the result
       $this->assertEquals('expected', $result);
   }
   ```

4. **Maintain Test Isolation**: Each test should be independent and not rely on other tests.

5. **Use Factories**: Use factories to create test data rather than creating models directly.

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=TestClassName

# Run tests with coverage report
php artisan test --coverage

# Run tests and fix code style
composer test:lint
``` 