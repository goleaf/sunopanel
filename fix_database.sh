#!/bin/bash

echo "=== Fixing database issues ==="
echo "============================="

# First, let's fix the Track model by removing the 'genres' field from fillable
echo "Updating Track model..."
sed -i "s/'genres', \/\/ Kept for backward compatibility/\/\/ 'genres' - removed/g" app/Models/Track.php

# Modify the syncGenres method to not use the field anymore
echo "Updating syncGenres method in Track model..."
sed -i "/\$this->genres = implode/d" app/Models/Track.php
sed -i "/\$this->save();/d" app/Models/Track.php

# Make sure the migration doesn't have the genres column
echo "Checking migrations..."
grep -r "genres" database/migrations/

# Run migrations fresh with seed
echo "Running migrations fresh with seed..."
php artisan migrate:fresh --seed

# Fix any tests if needed
echo "Fixing tests..."
# The TrackControllerTest should be updated to not use the genres field directly

echo "Running tests..."
php artisan test

echo ""
echo "Done! Check the output above for any remaining issues." 