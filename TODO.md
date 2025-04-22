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
- [x] Fix email/password authentication method
- [x] Improve OAuth integration for direct uploads
- [x] Update YouTube upload documentation

## YouTube Upload Improvements

## Current Issues
- The direct upload approach using browser automation (`youtube-direct-upload`) is failing due to changes in Google's login process
- Selenium-based automation is unreliable for YouTube login due to:
  - Frequent changes to Google's authentication flow
  - Anti-automation measures by Google
  - Captchas and 2FA challenges

## Action Plan
1. Implement proper OAuth authentication for YouTube uploads
   - Create a Google Cloud project and enable YouTube Data API v3
   - Configure OAuth consent screen and credentials
   - Update the SimpleYouTubeUploader to use OAuth instead of browser automation
   
2. Implement YouTube API client with refresh token support
   - Store refresh tokens securely in the database
   - Handle token refresh automatically
   - Implement proper error handling and retry logic

3. Create a web interface for YouTube account connection
   - Add routes for OAuth flow
   - Create a connection wizard UI
   - Display connection status

## Tasks
- [ ] Set up Google Cloud project with YouTube Data API v3
- [ ] Create OAuth credentials for YouTube API
- [ ] Update SimpleYouTubeUploader to use YouTube Data API
- [ ] Implement token storage and refresh mechanism
- [ ] Update job queue system for YouTube uploads
- [ ] Create web UI for connecting YouTube accounts
- [ ] Update test scripts and diagnostic tools

## Remaining Tasks

- [x] Test the YouTube upload functionality
- [x] Implement proper error handling for failed uploads
- [x] Add support for OAuth-based authentication
- [x] Add playlist management UI
- [x] Complete playlist integration
- [x] Implement proper token refresh mechanism