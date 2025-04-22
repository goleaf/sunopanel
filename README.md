<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development/)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# SunoPanel

A Laravel application to manage and process tracks, converting MP3 files and images into MP4 videos.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Redis server for queue processing
- FFmpeg (required for MP3 to MP4 conversion)

## FFmpeg Installation

### Ubuntu/Debian
```bash
sudo apt update
sudo apt install ffmpeg
```

### CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install ffmpeg ffmpeg-devel
```

### MacOS
```bash
brew install ffmpeg
```

### Windows
Download from [FFmpeg's official website](https://ffmpeg.org/download.html) or install via Chocolatey:
```bash
choco install ffmpeg
```

## Installation

1. Clone the repository
2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Create a symbolic link for storage:
```bash
php artisan storage:link
```

5. Create the SQLite database:
```bash
touch database/database.sqlite
```

6. Run migrations:
```bash
php artisan migrate
```

7. Start the queue worker:
```bash
php artisan queue:work
```

8. Build assets:
```bash
npm run dev
```

## Usage

1. Visit the "Add" page to input track information
2. Format should be: `title.mp3|mp3_url|image_url|genres`
3. Queue will process tracks in the background
4. View processing status on the "Songs" page

## Features

- Process multiple tracks from text input
- Download MP3 files and images
- Convert MP3 and images to MP4 using FFmpeg
- Track processing status with progress bars
- Organize tracks by genres
- Dark/light theme toggle

## YouTube Video Uploads

SunoPanel supports uploading videos to YouTube directly from the application. There are two authentication methods supported:

### 1. OAuth Authentication (Recommended)

Using OAuth is the recommended method for YouTube uploads as it's more secure and reliable:

1. Create a project in the [Google Cloud Console](https://console.cloud.google.com/)
2. Enable the YouTube Data API v3
3. Create OAuth 2.0 credentials (Web application type)
4. Configure your `.env` file with the following values:
   ```
   YOUTUBE_CLIENT_ID=your_client_id
   YOUTUBE_CLIENT_SECRET=your_client_secret
   YOUTUBE_REDIRECT_URI=https://your-app.com/youtube-auth
   YOUTUBE_USE_OAUTH=true
   YOUTUBE_USE_SIMPLE=false
   ```
5. Open your application and navigate to the YouTube authentication page
6. Follow the OAuth flow to authorize the application
7. The access and refresh tokens will be stored automatically

### Using the Provided Credentials

If you're using the credentials provided in the setup instructions:

1. Add the following to your `.env` file:
   ```
   YOUTUBE_CLIENT_ID=<client_id_provided_in_setup>
   YOUTUBE_CLIENT_SECRET=<client_secret_provided_in_setup>
   YOUTUBE_REDIRECT_URI=https://sunopanel.prus.dev/youtube-auth
   YOUTUBE_USE_OAUTH=true
   YOUTUBE_USE_SIMPLE=false
   ```

2. Navigate to your application's YouTube settings page at `/youtube/status`
3. Click on "Authenticate with YouTube API" 
4. Google will prompt you to authorize the application
5. After authorization, you'll be redirected back to your application
6. You can now upload videos to YouTube from your tracks

### Uploading Videos to YouTube

1. Navigate to the "Songs" page in your application
2. Find the track you want to upload to YouTube
3. Click the "Upload to YouTube" button
4. Fill in the video details (title, description, tags, etc.)
5. Click "Upload to YouTube"
6. The video will be uploaded in the background
7. You can check the upload status on the "YouTube Uploads" page

### Troubleshooting

If you encounter issues with YouTube uploads:

1. Check that your Google Cloud project has the YouTube Data API v3 enabled
2. Verify that your OAuth credentials are correctly configured in the `.env` file
3. Ensure your application has been authorized with the correct scopes
4. Check the application logs for detailed error messages
5. Try re-authenticating with YouTube by clicking "Authenticate with YouTube API" again
6. Ensure the redirect URI in Google Cloud Console matches exactly with your application's callback URL
7. Check that you have the Google API Client package installed:
   ```
   composer require google/apiclient
   ```

For more information, see the [YouTube Data API documentation](https://developers.google.com/youtube/v3/getting-started).

# YouTube Uploads

SunoPanel now supports direct YouTube uploads using browser automation. This approach uses Selenium to control a browser and upload videos directly to YouTube using your username and password.

## Installation

1. Make sure you have Python 3 and pip installed
2. Run the installation command:
   ```bash
   php artisan youtube:install
   ```
3. Set your YouTube credentials in the `.env` file:
   ```
   YOUTUBE_EMAIL=your.email@gmail.com
   YOUTUBE_PASSWORD=your_password
   ```

## Testing the Uploader

To test if everything is set up correctly, run:
```bash
php artisan youtube:diagnostics
```

To upload a test video:
```bash
php artisan youtube:test-upload
```

## Troubleshooting

If you encounter issues with YouTube uploads:

1. Run the diagnostics command to check your configuration:
   ```bash
   php artisan youtube:diagnostics
   ```

2. Make sure the browser automation dependencies are installed:
   ```bash
   pip3 install selenium webdriver-manager pyvirtualdisplay
   ```

3. Check that you have Chrome or Firefox installed on your server

4. For headless servers, you may need to install:
   ```bash
   apt-get install xvfb
   ```

5. View upload errors in the logs:
   ```bash
   php artisan youtube:errors
   ```
