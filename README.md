# SunoPanel YouTube Upload Commands

SunoPanel includes a command to upload random tracks to YouTube, supporting multiple YouTube accounts.

## YouTube Random Upload Command

Upload a random track to YouTube:

```bash
php artisan youtube:upload-random
```

This command will:
1. Use the active YouTube account 
2. Find a random track that is completed with an MP4 file
3. Upload it to YouTube as a public video
4. Add the video to playlists based on the track's genres

Optional parameters:
```bash
php artisan youtube:upload-random --account=1  # Use a specific account ID
```

## YouTube Account Management

List all YouTube accounts:
```bash
php artisan youtube:accounts
```

Set an account as active:
```bash
php artisan youtube:use-account 1  # Replace 1 with the account ID
```

## Adding New YouTube Accounts

To add a new YouTube account, use the web interface:
```
/youtube/status
```

This page allows you to add and manage multiple YouTube accounts and switch between them.

## Scheduling

The YouTube upload command is scheduled to run automatically once per day:

```php
$schedule->command('youtube:upload-random')->daily();
```

You can modify the schedule to run more frequently by updating the appropriate line in the `app/Console/Kernel.php` file.

## Web Endpoint

There's also a web endpoint for random YouTube uploads:

```
/random-youtube-upload
```

This endpoint selects a random track and redirects to the upload form. 