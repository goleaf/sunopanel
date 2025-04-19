# SunoPanel Update Tasks

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
- [ ] Update audio player components for better UX
- [ ] Standardize button styles across all components

### Section Pages
- [ ] Review and update track-related views:
  - [ ] `tracks/index.blade.php`
  - [ ] `tracks/show.blade.php`
  - [ ] `tracks/create.blade.php`
  - [ ] `tracks/edit.blade.php`
- [ ] Review and update genre-related views
- [ ] Review and update playlist-related views

## Functionality Improvements
- [ ] Add responsive design improvements for mobile views
- [ ] Implement better audio player controls
- [ ] Improve search functionality UI
- [ ] Add bulk actions for tracks and playlists
- [ ] Implement better error handling in forms

## Code Cleanup
- [ ] Remove unused components and views
- [ ] Standardize naming conventions across all components
- [ ] Ensure all components follow Laravel and Tailwind best practices
- [ ] Optimize stylesheets and scripts for performance

## Critical Fixes Needed
- [ ] Fix undefined variable $genres in playlists/add-tracks.blade.php view
- [ ] Fix failing tests in PlaylistControllerTest:
  - [ ] Update PlaylistController@addTracks to properly pass $availableTracks instead of $tracks
  - [ ] Fix test_add_tracks_to_playlist test with correct variable names
- [ ] Fix failing tests in PlaylistRoutesTest:
  - [ ] Update create and update methods to use 'title' field instead of 'name'
  - [ ] Fix inconsistency between 'name' and 'title' fields in Playlist model
- [ ] Fix PlaylistController@createFromGenre method to use 'title' field instead of 'name'
- [ ] Update PlaylistController validation rules to require 'title' instead of 'name'
- [ ] Update all playlist form templates to use 'title' field instead of 'name'
- [ ] Fix routing issues in PlaylistController that cause redirect test failures

## Database & Model Updates
- [ ] Update Playlist model fields:
  - [ ] Consolidate 'name' and 'title' fields to use only 'title'
  - [ ] Create migration to remove redundant columns from playlists table
- [ ] Update test factories to use consistent field names
- [ ] Update form request validation for playlist creation and editing  