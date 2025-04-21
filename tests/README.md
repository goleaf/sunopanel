# Testing Guide for SunoPanel

This directory contains tests for controllers, routes, models, and Blade views. Tests are organized into Feature and Unit tests following Laravel's testing conventions.

## Running Tests

To run all tests:

```bash
php artisan test
```

To run specific test files:

```bash
php artisan test --filter=DownloadControllerTest
php artisan test --filter=BladeViewTest
```

To run a specific test method:

```bash
php artisan test --filter=DownloadControllerTest::test_index_displays_downloads
```

## Testing Structure

### Feature Tests

Feature tests verify that application's components function correctly together:

- **Controller Tests**: Verify controller methods respond correctly to requests
  - `DownloadControllerTest.php`
  - `TrackControllerTest.php`
  - `PlaylistControllerTest.php`

- **Route Tests**: Ensure routes are registered and accessible
  - `RouteTest.php`

- **Blade View Tests**: Check that views are properly rendered with expected data
  - `BladeViewTest.php`

### Unit Tests

Unit tests focus on individual components in isolation:

- **Model Factory Tests**: Ensure model factories create valid instances
  - `ModelFactoryTest.php`

## Testing Strategy

The tests in this repository follow these principles:

1. **Database Reset**: Most tests use the `RefreshDatabase` trait to ensure a clean database for each test.

2. **Factory Usage**: Model factories are used to generate test data.

3. **Assertions**: Tests verify:
   - HTTP response status codes
   - Correct views are returned
   - Expected variables are passed to views 
   - Database records are created/updated/deleted correctly
   - Relationships between models work properly

4. **Storage Faking**: File uploads and storage are tested using Laravel's `Storage::fake()`.

## Common Testing Patterns

### Controller Testing

Controller tests typically:
1. Set up the required data
2. Send a request to an endpoint
3. Assert the response status and content
4. Verify database changes when applicable

### View Testing

View tests:
1. Create necessary model instances
2. Visit a route that renders a view
3. Assert the response contains expected content
4. Verify view variables are correctly passed

### Route Testing

Route tests ensure:
1. Routes return appropriate status codes
2. Non-existent routes return 404s
3. Method constraints are enforced

## Adding New Tests

When adding new tests:

1. Create a new test class in the appropriate directory
2. Extend the base `TestCase` class
3. Use the `RefreshDatabase` trait if database access is needed
4. Follow the naming convention: `test_[method_name]_[expected_behavior]`

## Test Refactoring Commands

This project includes several custom Artisan commands for refactoring tests:

```bash
# Convert PHPUnit docblocks to attributes
php artisan tests:convert-docblocks

# Add type hints and return types to test methods
php artisan tests:add-types

# Standardize test method naming conventions
php artisan tests:standardize

# Fix skipped and incomplete tests
php artisan tests:cleanup-skipped --fix

# Fix remaining format issues in Livewire tests
php artisan tests:fix-remaining
```

These commands can also be run using Composer shortcuts:

```bash
# Convert PHPUnit docblocks to attributes
composer test:convert-comments

# Add type hints and return types to test methods
composer test:add-types

# Run all test refactoring commands
composer test:refactor-all
```

## Best Practices for Writing Tests

When writing new tests, follow these guidelines:

1. Use PHP 8.2+ attributes instead of docblock annotations:
   ```php
   #[\PHPUnit\Framework\Attributes\Test]
   public function test_your_feature(): void
   {
       // Your test code here
   }
   ```

2. Always add strict typing and return types:
   ```php
   <?php
   
   declare(strict_types=1);
   
   namespace Tests\Feature;
   
   use Tests\TestCase;
   
   class YourTest extends TestCase
   {
       #[\PHPUnit\Framework\Attributes\Test]
       public function test_your_feature(): void
       {
           // Your test code here
       }
   }
   ```

3. Use snake_case for test method names starting with `test_`.

4. Use data providers for testing similar functionality with different inputs:
   ```php
   #[\PHPUnit\Framework\Attributes\Test]
   #[\PHPUnit\Framework\Attributes\DataProvider('provideTestCases')]
   public function test_with_various_inputs(string $input, string $expected): void
   {
       $this->assertEquals($expected, process($input));
   }
   
   public static function provideTestCases(): array
   {
       return [
           ['input1', 'expected1'],
           ['input2', 'expected2'],
       ];
   }
   ```

5. Group related tests into their own test classes following the Laravel directory structure. 