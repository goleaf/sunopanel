# SunoPanel Update Tasks

## Progress Summary
- [x] Fixed all issues related to Playlist title/name consolidation
- [x] All Playlist-related tests now pass 
- [x] Fixed test failures in PlaylistControllerTest and PlaylistRoutesTest
- [x] Created migration to remove redundant name column from playlists table
- [x] Created FormRequest classes for all controller methods
- [x] Added comprehensive tests for all FormRequest classes
- [ ] Several other test failures still exist in other test classes (TrackRequestTest, GenreControllerTest, etc.)

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
- [ ] Review and update genre-related views
- [ ] Review and update playlist-related views

## Functionality Improvements
- [ ] Add responsive design improvements for mobile views
- [x] Implement better audio player controls
- [ ] Improve search functionality UI
- [ ] Add bulk actions for tracks and playlists
- [ ] Implement better error handling in forms

## Code Cleanup
- [x] Remove unused components and views
- [x] Standardize naming conventions across all components
- [x] Ensure all components follow Laravel and Tailwind best practices
- [ ] Optimize stylesheets and scripts for performance

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
- [ ] Fix genre capitalization in BubblegumBassSeederTest and GenreControllerTest

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
- [x] Create middleware for global exception handling
- [x] Register the service and middleware in the service provider
- [x] Update the exception handler to use our logging service
- [x] Remove error logging from controllers
- [x] Create tests for the logging service
- [ ] Commit the changes to Git  