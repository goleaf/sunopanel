# SunoPanel Update Tasks

## Current Status Assessment
- [x] Fixed all issues related to Playlist title/name consolidation
- [x] All Playlist-related tests now pass 
- [x] Fixed test failures in PlaylistControllerTest and PlaylistRoutesTest
- [x] Created migration to remove redundant name column from playlists table
- [x] Created FormRequest classes for all controller methods
- [x] Added comprehensive tests for all FormRequest classes
- [x] Verified UI framework implementation (TailwindCSS + DaisyUI)
- [x] Confirmed Bootstrap has been completely removed
- [x] Centralized error logging using LoggingService
- [x] Removed direct Log::error calls from controllers
- [ ] Several other test failures still exist in other test classes (TrackRequestTest, GenreControllerTest, etc.)

## Action Plan

### 1. Fix Remaining Test Failures
1. [x] Fix genre capitalization issues:
   - [x] Review and fix Genre model's naming conventions
   - [x] Ensure consistent capitalization in BubblegumBassSeederTest
   - [x] Update Genre model's findOrCreateByName and syncGenres methods
   - [x] Verify that all genre capitalizations are consistent

2. [ ] Fix TrackRequestTest issues:
   - [ ] Investigate session validation errors in TrackRequestTest
   - [ ] Ensure FormRequest validation rules are correctly implemented
   - [ ] Check TrackStoreRequest and TrackUpdateRequest for proper validation
   - [ ] Validate bulk upload functionality

3. [ ] Review all controller validation:
   - [ ] Ensure all controllers use FormRequest classes consistently
   - [ ] Verify validation error handling is consistent

### 2. Clean Up Logger Implementation
1. [x] Fix LoggingMiddleware issues:
   - [x] Create new LoggingMiddleware class to replace the deleted one
   - [x] Ensure it properly integrates with the LoggingService
   - [x] Register the middleware in the Kernel

2. [ ] Standardize logging formats:
   - [ ] Ensure consistent log message format across the application
   - [ ] Remove any remaining direct references to Log facade
   - [ ] Verify LoggingService is properly injected in all controllers

3. [x] Update LoggingServiceProvider:
   - [x] Review and consolidate logging provider registrations
   - [x] Ensure proper binding of LoggingService in service container

### 3. UI/UX Improvements
1. [x] Enhance mobile responsiveness:
   - [x] Improve table display on small screens
   - [x] Optimize form controls for mobile devices
   - [x] Test and refine responsive breakpoints

2. [ ] Implement advanced UI features:
   - [x] Add loading indicators for AJAX operations
   - [ ] Enhance form validation feedback
   - [ ] Add tooltips for improved user guidance

3. [ ] Dashboard improvements:
   - [ ] Create widgets for key statistics
   - [ ] Optimize dashboard layout for all screen sizes

### 4. Code Quality & Performance
1. [ ] Complete PSR-12 compliance:
   - [ ] Review all PHP files for PSR-12 compliance
   - [ ] Fix any formatting inconsistencies
   - [ ] Ensure proper docblocks for all methods

2. [ ] Optimize frontend assets:
   - [ ] Implement PurgeCSS to remove unused styles
   - [ ] Configure Vite for proper asset versioning
   - [ ] Minify production JavaScript

3. [ ] Database optimization:
   - [ ] Review and optimize database queries
   - [ ] Add indexes for frequently queried columns
   - [x] Implement eager loading where appropriate

## Immediate Focus
Based on priority and dependencies, we'll tackle these tasks in the following order:

1. Fix LoggingMiddleware issues
2. Fix genre capitalization in tests and models
3. Resolve TrackRequestTest validation errors
4. Standardize logging formats across the application
5. Address PSR-12 compliance
6. Implement mobile responsiveness improvements
7. Optimize frontend assets

## View Templates to Update

### Main Pages
- [x] Review `dashboard.blade.php` - Main dashboard page
- [x] Review `home.blade.php` - Alternative home page
- [x] Update `dashboard.blade.php` to use more consistent Tailwind/DaisyUI styling
- [x] Update `home.blade.php` to use more consistent Tailwind/DaisyUI styling
- [x] Standardize layout between dashboard and home views

### Layout Pages
- [x] Review and update `layouts/app.blade.php` - Main application layout
- [x] Ensure consistent navbar styling across all pages
- [x] Add theme toggle functionality for light/dark mode

### Components
- [x] Review and update table components:
  - [x] `components/table/table.blade.php`
  - [x] `components/table/cell.blade.php`
  - [x] `components/table/header-cell.blade.php`
  - [x] `components/table/row.blade.php`
- [x] Update audio player components for better UX
- [x] Standardize button styles across all components

### Section Pages
- [x] Review and update track-related views:
  - [x] `tracks/index.blade.php`
  - [x] `tracks/show.blade.php`
  - [x] `tracks/create.blade.php`
  - [x] `tracks/edit.blade.php`
- [x] Review and update genre-related views
- [x] Review and update playlist-related views

## Functionality Improvements
- [ ] Add responsive design improvements for mobile views
  - [x] Basic mobile responsiveness implemented with TailwindCSS
  - [x] Mobile menu implemented
  - [ ] Enhance mobile UX for table views
  - [ ] Improve form controls on smaller screens
- [x] Implement better audio player controls
- [ ] Improve search functionality UI
- [ ] Add bulk actions for tracks and playlists
- [ ] Implement better error handling in forms

## UI/UX Improvements
- [x] Implement TailwindCSS and DaisyUI for consistent styling
- [x] Create custom button component with standardized styling
- [x] Add dark/light theme toggle functionality
- [ ] Enhance table row hover interactions
- [ ] Add loading indicators for AJAX operations
- [ ] Improve form validation feedback
- [ ] Add tooltips for improved user guidance
- [ ] Create dashboard widgets for key statistics
- [ ] Optimize design for tablet-sized screens

## Code Cleanup
- [x] Remove unused components and views
- [x] Standardize naming conventions across all components
- [x] Ensure all components follow Laravel and Tailwind best practices
- [x] Remove all Bootstrap dependencies
- [ ] Optimize stylesheets and scripts for performance
  - [ ] Implement purgeCSS for unused styles
  - [ ] Minify production JavaScript
  - [ ] Implement asset versioning

## Critical Fixes Needed
- [x] Fix undefined variable $genres in playlists/add-tracks.blade.php view
- [x] Fix variable name in playlists/add-tracks.blade.php view: use $tracks instead of $availableTracks
- [x] Fix failing tests in PlaylistControllerTest:
  - [x] Update PlaylistController@addTracks to properly pass $tracks instead of $availableTracks
  - [x] Fix test_add_tracks_to_playlist test with correct variable names
- [x] Fix failing tests in PlaylistRoutesTest:
  - [x] Update create and update methods to use 'title' field instead of 'name'
  - [x] Fix inconsistency between 'name' and 'title' fields in Playlist model
- [x] Fix PlaylistController@createFromGenre method to use 'title' field instead of 'name'
- [x] Update PlaylistController validation rules to require 'title' instead of 'name'
- [x] Update all playlist form templates to use 'title' field instead of 'name'
- [x] Fix routing issues in PlaylistController that cause redirect test failures
- [ ] Fix session validation errors in TrackRequestTest
- [x] Fix genre capitalization in BubblegumBassSeederTest and GenreControllerTest

## Database & Model Updates
- [x] Update Playlist model fields:
  - [x] Consolidate 'name' and 'title' fields to use only 'title'
  - [x] Create migration to remove redundant columns from playlists table
- [x] Update test factories to use consistent field names
- [x] Update form request validation for playlist creation and editing

## Laravel/PHP Code Structure Improvements
- [x] Create dedicated FormRequest classes for controller validation
- [x] Add comprehensive tests for all FormRequest classes
- [x] Make controllers final classes per Laravel standards
- [x] Make model classes final per Laravel standards
- [x] Add strict typing declarations to PHP files: `declare(strict_types=1);`
- [x] Implement proper return type hints for all methods
- [x] Ensure controllers are thin and delegate business logic to services
- [x] Create service classes for complex operations
- [x] Implement proper error handling using try-catch blocks
- [ ] Update all classes to follow PSR-12 coding standards  

## Error Logging Implementation Tasks
- [x] Create a universal error logging service
- [x] Register the service and middleware in the service provider
- [x] Update the exception handler to use our logging service
- [x] Remove error logging from controllers
- [x] Create tests for the logging service
- [x] Commit the changes to Git
- [x] Fix issues with deleted ErrorLoggingMiddleware
- [x] Recreate proper LoggingMiddleware
- [x] Update middleware registration in Kernel  