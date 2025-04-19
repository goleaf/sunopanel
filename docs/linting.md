# Code Linting in SunoPanel

This document provides instructions on how to use the linting tools available in SunoPanel to maintain code quality and consistency.

## PHP Code Linting with PSR-12

SunoPanel follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard for PHP files. To help maintain this standard, we have implemented custom tools based on Laravel Pint.

### Using the Custom Artisan Command

A custom Artisan command has been created to simplify the process of checking and fixing PSR-12 issues:

```bash
# Check for PSR-12 issues without fixing them
php artisan lint:psr12

# Fix PSR-12 issues automatically
php artisan lint:psr12 --fix

# Check a specific path for PSR-12 issues
php artisan lint:psr12 --path=app/Models

# Fix PSR-12 issues in a specific path
php artisan lint:psr12 --fix --path=app/Http/Controllers
```

### Using NPM Scripts

For convenience, NPM scripts have been added to run the linting commands:

```bash
# Check for PSR-12 issues
npm run lint:php

# Fix PSR-12 issues automatically
npm run lint:fix

# Check a specific path
npm run lint:php -- --path=app/Models

# Fix issues in a specific path
npm run lint:fix -- --path=app/Http/Controllers
```

### Configuration

The PSR-12 linting is configured in the `pint.json` file in the root of the project. This file specifies:

- The preset to use (Laravel)
- Directories to exclude from linting

If you need to customize the linting rules, you can modify this file.

## Best Practices

1. **Run linting before committing code**: Always run the linter before committing code to ensure your changes follow the project's coding standards.

2. **Run linting on specific paths**: When working on a specific part of the codebase, run the linter only on the files you've modified to save time.

3. **Add linting to your workflow**: Consider integrating linting into your development workflow, such as running it after saving files or before running tests.

4. **Fix issues incrementally**: If there are many issues to fix, address them incrementally by fixing issues in specific directories one at a time.

## Troubleshooting

If you encounter issues with the linting tools:

1. **Check the Pint configuration**: Ensure the `pint.json` file is properly configured.

2. **Exclude problematic files**: If a specific file consistently causes issues, consider excluding it from linting.

3. **Run Pint directly**: For advanced troubleshooting, you can run Laravel Pint directly:

```bash
./vendor/bin/pint --test
``` 