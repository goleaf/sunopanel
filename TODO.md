# TODO List

## Requirements
- [x] Parse song information from textarea with format: title.mp3|mp3_url|image_url|genres
- [x] Download MP3 files and images
- [x] Create MP4 files using MP3 audio and static image
- [x] Process genres (create if not exists)
- [x] Run processing in background with progress bar
- [x] Simple UI with 3 menu items: Add / Songs / Genres
- [x] Add option to process tracks immediately and check for failures
- [x] Upload videos to YouTube with username/password authentication
- [x] Upload MP4 files directly from track view page to YouTube

## Implementation Tasks
- [x] Create database migrations for tracks and genres
- [x] Create models for Track and Genre
- [x] Create controllers for form input, tracks listing, and genres
- [x] Create background job for file processing
- [x] Implement progress tracking for background jobs
- [x] Create views for Add, Songs, and Genres pages
- [x] Install and configure required dependencies for MP4 creation
- [x] Implement UI with TailwindCSS and DaisyUI
- [x] Create Genre model with relationships
- [x] Create Genre controller with CRUD operations
- [x] Create Genre views (index, create, edit, show)
- [x] Implement immediate job processing with error checking
- [x] Implement grid view for tracks with toggle between table and grid view
- [x] Implement parallel processing for 10 tracks simultaneously
- [x] Create direct video upload controller and views
- [x] Implement YouTube upload with username/password authentication
- [x] Add YouTube upload form to track view page
- [x] Implement genre-based playlist creation and video organization

## Technical Requirements
- [x] MP3 and image download from external URLs
- [x] Convert MP3 + image to MP4
- [x] Background job processing
- [x] Progress bar for long-running tasks
- [x] Immediate processing with error feedback
- [x] Parallel processing of track jobs for increased throughput
- [x] YouTube video upload using username/password from .env
- [x] YouTube playlist management based on track genres

## Refactoring Tasks
- [x] Improve tracks index page layout with better stats display
- [x] Enhance search interface for better usability
- [x] Make track avatars smaller in tables for better use of space
- [x] Maintain consistent avatar sizing across different views
- [x] Implement parallel queue processing (10 workers)
- [ ] Improve mobile responsiveness of track displays
- [ ] Enhance error feedback in track processing

## YouTube Upload

- [x] Create YouTube direct upload script using Python
- [x] Add client secrets generation script
- [x] Integrate scripts with Laravel services
- [x] Create SimpleYouTubeUploader service
- [x] Update YouTubeServiceProvider to install scripts
- [x] Add test command for YouTube uploads

## Remaining Tasks

- [x] Test the YouTube upload functionality
- [x] Implement proper error handling for failed uploads
- [x] Add support for OAuth-based authentication
- [ ] Add playlist management UI
- [ ] Complete playlist integration
- [x] Implement proper token refresh mechanism