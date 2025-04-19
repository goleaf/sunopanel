# SunoPanel Commands Documentation

This document provides an overview of custom artisan commands available in the SunoPanel application.

## PSR-12 Linting Command

The PSR-12 linting command helps ensure all PHP files in the project follow the PSR-12 coding standards.

### Usage

To check if your code follows PSR-12 standards without making changes:

```bash
php artisan lint:psr12
```

To automatically fix PSR-12 issues in your code:

```bash
php artisan lint:psr12 --fix
```

This command uses Laravel Pint under the hood, which is a code style fixer based on PHP-CS-Fixer.

## Database Optimization Command

The database optimization command helps improve database performance by adding missing indexes and optimizing tables.

### Usage

To run database optimization:

```bash
php artisan db:optimize
```

This command:

1. Adds missing indexes to commonly queried columns:
   - `tracks.title` and `tracks.unique_id`
   - `genres.name` and `genres.slug`
   - `playlists.title`
   - Foreign key indexes on pivot tables

2. Runs the MySQL `OPTIMIZE TABLE` command on all tables in the database.

### When to Use

Run this command:
- After major data imports
- After bulk track uploads
- Periodically in production to maintain optimal performance
- After schema changes or migrations

## Bulk Track Upload Guidelines

When uploading tracks in bulk, follow these guidelines:

1. Format each track on a separate line with fields separated by pipes (`|`):
   ```
   Track Title|Audio URL|Image URL|Genres|Duration (optional)
   ```

2. Example:
   ```
   Awesome Track|https://example.com/audio.mp3|https://example.com/image.jpg|Electronic, Dance|3:45
   Another Great Song|https://example.com/song.mp3|https://example.com/cover.jpg|Rock, Alternative
   ```

3. Requirements:
   - Track title must be unique
   - Audio and image URLs must be valid URLs
   - Genres can be comma-separated
   - Duration is optional (default: "3:00")

4. Error handling:
   - The system validates each line individually
   - Successfully processed tracks are saved even if some lines have errors
   - Errors are reported in the session with line numbers for easy fixing

## Testing Commands

SunoPanel includes several commands to help improve the quality of tests.

### Converting PHPUnit Doc-Comments to Attributes

PHPUnit 12 deprecates the use of doc-comments for test annotations and recommends using attributes instead. This command helps convert existing doc-comments to the new attribute syntax:

```bash
php artisan test:convert-comments
```

To see what would be changed without making actual changes:

```bash
php artisan test:convert-comments --dry-run
```

### Generating Test Stubs

When adding new classes to the application, you can quickly generate test stubs using:

```bash
# Generate a feature test (default)
php artisan test:generate-stub "App\Services\MyService"

# Generate a unit test
php artisan test:generate-stub "App\Services\MyService" --unit

# Overwrite an existing test
php artisan test:generate-stub "App\Services\MyService" --force
```

The command will:
1. Create the appropriate directory structure
2. Generate test methods for all public methods in the class
3. Set up the appropriate namespace and imports

### Fixing Test Coding Style

To maintain consistent coding style in tests, use:

```bash
# Fix style issues in test files
php artisan test:style

# Check for style issues without fixing them
php artisan test:style --check

# Show detailed output
php artisan test:style --details
```

## Composer Scripts

SunoPanel includes several Composer scripts for common development tasks:

```bash
# Run tests
composer test

# Fix coding style issues
composer lint

# Optimize database tables
composer db:optimize

# Fix test style issues
composer test:style

# Check test style without fixing
composer test:style:check

# Convert PHPUnit doc-comments to attributes
composer test:convert-comments

# Run all tests and style checks
composer test:all
``` 