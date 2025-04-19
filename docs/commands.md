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