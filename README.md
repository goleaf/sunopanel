# SunoPanel - Music Management System

A Laravel-based application for managing music tracks, genres, and playlists.

## Features

- **Tracks Management**: Add, edit, and delete music tracks with metadata
- **Genres Organization**: Categorize tracks by genres
- **Playlists**: Create and manage custom playlists

## Requirements

- PHP 8.1+
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Node.js & NPM (for frontend assets)

## Installation

1. Clone the repository:
   ```
   git clone https://your-repository-url.git
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

4. Generate application key:
   ```
   php artisan key:generate
   ```

5. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sunopanel
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. Install Node.js dependencies and build assets:
   ```
   npm install
   npm run dev
   ```

7. Initialize the application (migrations, seeds, storage setup):
   ```
   php artisan app:initialize
   ```

## Quick Start

1. Run the development server:
   ```
   php artisan serve
   ```

2. Access the application at http://localhost:8000

3. Navigate to the Dashboard to see system stats

## Key Routes

- `/dashboard` - Main dashboard with system statistics
- `/tracks` - Manage music tracks
- `/genres` - Manage music genres
- `/playlists` - Create and edit playlists

## Testing

Run the test suite:
```
php artisan test
```

## Seeded Data

The application comes with pre-seeded tracks featuring Bubblegum Bass music.

## License

[MIT License](LICENSE) 