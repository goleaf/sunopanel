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