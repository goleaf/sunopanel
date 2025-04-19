# SunoPanel - Music Management System

A modern, Laravel-based application for managing music tracks, genres, and playlists. SunoPanel provides a clean interface for organizing your music collection with an emphasis on Bubblegum Bass and other electronic music genres.

## Features

- **Track Management**: Add, edit, delete, and play music tracks with complete metadata
- **Genre Organization**: Categorize tracks by multiple genres with automatic detection
- **Playlist Creation**: Build custom playlists from your favorite tracks
- **Modern UI**: Clean, responsive interface built with TailwindCSS and DaisyUI
- **Real-time Playback**: Stream and play tracks directly from the browser

## Technology Stack

- **Backend**: Laravel 12.x
- **Frontend**: 
  - TailwindCSS 4.x
  - Alpine.js
  - DaisyUI
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **PHP**: 8.2+
- **Tooling**: Vite, NPM

## Requirements

- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/goleaf/sunopanel.git
   cd sunopanel
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Copy the environment file:
   ```
   cp .env.example .env
   ```

4. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sunopanel
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Generate application key:
   ```
   php artisan key:generate
   ```

6. Run database migrations and seed data:
   ```
   php artisan migrate --seed
   ```

7. Install Node.js dependencies and build assets:
   ```
   npm install
   npm run build
   ```

8. Link storage for media files:
   ```
   php artisan storage:link
   ```

## Quick Start

1. Use the convenient development script that starts all required services:
   ```
   composer dev
   ```
   
   Or start the development server manually:
   ```
   php artisan serve
   ```

2. Access the application at http://localhost:8000

## Key Features and Routes

- **Dashboard** (`/dashboard`): System statistics and overview
- **Tracks** (`/tracks`): Manage all music tracks
  - Play tracks directly with the built-in player
  - View detailed metadata and genres
- **Genres** (`/genres`): Browse and manage music genres
  - Create playlists from entire genres
  - View all tracks within a genre
- **Playlists** (`/playlists`): Create and manage custom playlists
  - Add/remove tracks from playlists
  - Play entire playlists sequentially

## Project Structure

- `app/Models`: Core data models (Track, Genre, Playlist)
- `app/Http/Controllers`: Request handling and business logic
- `resources/views`: Blade templates and UI components
- `database/migrations`: Database structure definitions
- `database/seeders`: Sample data generation

## Testing

Run the test suite with:
```
composer test
```

Or manually:
```
php artisan test
```

## Seeded Data

The application comes with pre-seeded tracks featuring Bubblegum Bass music,
providing a ready-to-use experience with sample content to explore the system's features.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

[MIT License](LICENSE) 