#!/bin/bash

echo "=== Checking Laravel Routes ==="
echo "=============================="

# List all routes
echo "Listing all defined routes:"
php artisan route:list

# Check for downloads routes that should be removed
echo -e "\n=== Checking for downloads routes (should be removed) ==="
php artisan route:list | grep -i 'downloads\|download.' || echo "Good: No downloads routes found!"

# Check for files routes that should be removed
echo -e "\n=== Checking for files routes (should be removed) ==="
php artisan route:list | grep -i 'files\|file.' | grep -v 'tracks' || echo "Good: No standalone files routes found!"

# Check for tracks routes (should include download functionality)
echo -e "\n=== Checking for tracks routes (should include download functionality) ==="
php artisan route:list | grep -i 'tracks'

# Check for route errors in logs
echo -e "\n=== Checking for route errors in logs ==="
grep -i 'RouteNotFoundException\|route not defined' storage/logs/laravel.log | tail -n 10 || echo "No route errors found in logs!"

# List active controllers
echo -e "\n=== List of controllers to review ==="
find app/Http/Controllers -name "*Controller.php" | sort

echo -e "\nRoute check completed!"
echo "Please review the above information against @main.mdc requirements."
echo "Remember to remove downloads and files routes, and merge functionality into tracks." 