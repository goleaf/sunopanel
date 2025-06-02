# Suno Genre Import Guide

## Overview

The Genre Import functionality allows you to import tracks from Suno by specific genre using their tag-based search API. This feature is perfect for building genre-specific music collections.

## Features

- **Genre-based Search**: Import tracks by specific genres like "Spanish Pop", "City Pop", "Jazz", etc.
- **Preset Genres**: Quick-select buttons for popular genres
- **Duplicate Prevention**: Automatically skips tracks that already exist in your database
- **Progress Tracking**: Real-time progress monitoring with session-based tracking
- **Pagination Support**: Import multiple pages of results
- **Ranking Options**: Sort results by relevance, trending, play count, etc.
- **Dry Run Mode**: Preview what would be imported without creating tracks
- **Auto Processing**: Optionally start downloading tracks immediately after import

## How to Use

### Via Web Interface

1. Navigate to the Import Dashboard (`/import`)
2. Click on the "Genre Import" tab
3. Either:
   - Click one of the preset genre buttons (Spanish Pop, City Pop, Jazz, etc.)
   - Or manually enter a genre in the text field
4. Configure import settings:
   - **Rank By**: How to sort results (Most Relevant, Trending, etc.)
   - **Size per Page**: Number of tracks per page (1-100)
   - **Pages**: Number of pages to fetch (1-10)
   - **Start Index**: Starting index for pagination
5. Optional settings:
   - **Dry Run**: Preview without importing
   - **Auto Process**: Start downloading immediately
6. Click "Start Genre Import"

### Via Command Line

```bash
# Basic genre import
php artisan import:suno-genre --genre="Spanish Pop"

# With options
php artisan import:suno-genre \
    --genre="City Pop" \
    --size=50 \
    --pages=2 \
    --rank-by=trending \
    --process \
    --dry-run

# Available options
--genre=          # Genre to search for (required)
--from-index=0    # Starting index for pagination
--size=20         # Number of tracks per request (1-100)
--pages=1         # Number of pages to fetch (1-10)
--rank-by=        # Ranking method (most_relevant, trending, etc.)
--process         # Auto-start processing imported tracks
--dry-run         # Preview without creating tracks
--session-id=     # Session ID for progress tracking
```

## API Configuration

The genre import uses Suno's search API with the `tag_song` search type:

```
POST https://studio-api.prod.suno.com/api/search/
```

### Payload Structure

```json
{
  "search_queries": [
    {
      "name": "tag_song",
      "search_type": "tag_song", 
      "term": "Spanish%20Pop",
      "from_index": 0,
      "rank_by": "most_relevant"
    }
  ]
}
```

## Popular Genres

The interface includes preset buttons for these popular genres:

- **Spanish Pop** - Spanish pop music
- **City Pop** - Japanese city pop genre
- **Lo-Fi** - Lo-fi hip hop and chill music
- **Jazz** - Jazz music in all styles
- **Electronic** - Electronic and EDM music
- **Rock** - Rock music
- **Hip Hop** - Hip hop and rap music
- **Classical** - Classical music

## Ranking Options

- **Most Relevant** - Best match for the genre term
- **Trending** - Currently trending tracks in the genre
- **Most Recent** - Newest tracks first
- **Upvote Count** - Highest rated tracks
- **Play Count** - Most played tracks
- **By Hour/Day/Week/Month** - Time-based trending
- **All Time** - All-time popular tracks

## Error Handling

The system includes comprehensive error handling:

- **Rate Limiting**: Prevents too many requests (3 per 10 minutes)
- **Validation**: Ensures all required fields are provided
- **API Errors**: Handles API failures gracefully
- **Duplicate Detection**: Skips existing tracks automatically
- **Progress Recovery**: Can resume interrupted imports

## Token Management

**Important**: The Suno API requires a valid bearer token. If you encounter 401 Unauthorized errors:

1. Log into Suno.com in your browser
2. Open browser developer tools (F12)
3. Go to Network tab and make a search request
4. Copy the `authorization` header value
5. Update the `BEARER_TOKEN` constant in `app/Console/Commands/ImportSunoGenre.php`

## Examples

### Import Spanish Pop Music

```bash
# Import 50 Spanish Pop tracks, ranked by trending
php artisan import:suno-genre \
    --genre="Spanish Pop" \
    --size=50 \
    --rank-by=trending \
    --process
```

### Preview City Pop Import

```bash
# Dry run to see what would be imported
php artisan import:suno-genre \
    --genre="City Pop" \
    --size=20 \
    --dry-run
```

### Large Genre Collection

```bash
# Import multiple pages of jazz music
php artisan import:suno-genre \
    --genre="Jazz" \
    --size=100 \
    --pages=5 \
    --rank-by=upvote_count \
    --process
```

## Integration with Existing System

The genre import integrates seamlessly with the existing SunoPanel system:

- **Track Model**: Creates standard Track records
- **Genre System**: Automatically creates and assigns genres
- **Processing Queue**: Uses existing ProcessTrack jobs
- **Progress Tracking**: Uses the same session-based system
- **Import Dashboard**: Unified interface with other import methods

## Troubleshooting

### Common Issues

1. **401 Unauthorized**: Token expired, needs refresh
2. **No tracks found**: Genre term might be too specific
3. **Rate limited**: Wait 10 minutes between requests
4. **Validation errors**: Check required fields

### Logs

Check Laravel logs for detailed error information:

```bash
tail -f storage/logs/laravel.log
```

## Future Enhancements

Potential improvements for the genre import system:

- **Batch Genre Import**: Import multiple genres at once
- **Genre Discovery**: Suggest related genres
- **Smart Filtering**: Filter by duration, language, etc.
- **Scheduled Imports**: Automatically import new tracks for genres
- **Genre Analytics**: Track import success rates by genre 