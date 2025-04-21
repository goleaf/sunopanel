<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use Livewire\Component;

final class ApiDocumentation extends Component
{
    public function render()
    {
        $apiEndpoints = [
            [
                'name' => 'Tracks API',
                'description' => 'API endpoints for managing music tracks',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/tracks',
                        'description' => 'Get all tracks with optional filtering',
                        'parameters' => [
                            'search' => 'Search text to filter tracks',
                            'genre_id' => 'Filter by genre ID',
                            'sort' => 'Field to sort by (title, artist, created_at)',
                            'direction' => 'Sort direction (asc, desc)',
                        ],
                        'response' => 'JSON array of tracks with pagination',
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/tracks/{id}',
                        'description' => 'Get a specific track by ID',
                        'parameters' => [],
                        'response' => 'JSON object with track details',
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/tracks',
                        'description' => 'Create a new track',
                        'parameters' => [
                            'title' => 'Track title (required)',
                            'artist' => 'Artist name (required)',
                            'album' => 'Album name',
                            'genre_ids' => 'Array of genre IDs',
                            'url' => 'URL to the audio file (required)',
                            'cover_url' => 'URL to the cover image',
                        ],
                        'response' => 'JSON object with created track details',
                    ],
                    [
                        'method' => 'PUT',
                        'endpoint' => '/tracks/{id}',
                        'description' => 'Update an existing track',
                        'parameters' => [
                            'title' => 'Track title',
                            'artist' => 'Artist name',
                            'album' => 'Album name',
                            'genre_ids' => 'Array of genre IDs',
                            'url' => 'URL to the audio file',
                            'cover_url' => 'URL to the cover image',
                        ],
                        'response' => 'JSON object with updated track details',
                    ],
                    [
                        'method' => 'DELETE',
                        'endpoint' => '/tracks/{id}',
                        'description' => 'Delete a track',
                        'parameters' => [],
                        'response' => 'JSON confirmation message',
                    ],
                ],
            ],
            [
                'name' => 'Playlists API',
                'description' => 'API endpoints for managing playlists',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/playlists',
                        'description' => 'Get all playlists with optional filtering',
                        'parameters' => [
                            'search' => 'Search text to filter playlists',
                            'sort' => 'Field to sort by (title, created_at)',
                            'direction' => 'Sort direction (asc, desc)',
                        ],
                        'response' => 'JSON array of playlists with pagination',
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/playlists/{id}',
                        'description' => 'Get a specific playlist by ID with its tracks',
                        'parameters' => [],
                        'response' => 'JSON object with playlist details and tracks',
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/playlists',
                        'description' => 'Create a new playlist',
                        'parameters' => [
                            'title' => 'Playlist title (required)',
                            'description' => 'Playlist description',
                        ],
                        'response' => 'JSON object with created playlist details',
                    ],
                    [
                        'method' => 'PUT',
                        'endpoint' => '/playlists/{id}',
                        'description' => 'Update an existing playlist',
                        'parameters' => [
                            'title' => 'Playlist title',
                            'description' => 'Playlist description',
                        ],
                        'response' => 'JSON object with updated playlist details',
                    ],
                    [
                        'method' => 'DELETE',
                        'endpoint' => '/playlists/{id}',
                        'description' => 'Delete a playlist',
                        'parameters' => [],
                        'response' => 'JSON confirmation message',
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/playlists/{id}/tracks',
                        'description' => 'Add tracks to a playlist',
                        'parameters' => [
                            'track_ids' => 'Array of track IDs to add (required)',
                        ],
                        'response' => 'JSON object with updated playlist details',
                    ],
                    [
                        'method' => 'DELETE',
                        'endpoint' => '/playlists/{playlist_id}/tracks/{track_id}',
                        'description' => 'Remove a track from a playlist',
                        'parameters' => [],
                        'response' => 'JSON confirmation message',
                    ],
                ],
            ],
            [
                'name' => 'Genres API',
                'description' => 'API endpoints for managing music genres',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/genres',
                        'description' => 'Get all genres',
                        'parameters' => [
                            'search' => 'Search text to filter genres',
                            'sort' => 'Field to sort by (name, created_at)',
                            'direction' => 'Sort direction (asc, desc)',
                        ],
                        'response' => 'JSON array of genres with pagination',
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/genres/{id}',
                        'description' => 'Get a specific genre by ID with related tracks',
                        'parameters' => [],
                        'response' => 'JSON object with genre details and tracks',
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/genres',
                        'description' => 'Create a new genre',
                        'parameters' => [
                            'name' => 'Genre name (required)',
                        ],
                        'response' => 'JSON object with created genre details',
                    ],
                    [
                        'method' => 'PUT',
                        'endpoint' => '/genres/{id}',
                        'description' => 'Update an existing genre',
                        'parameters' => [
                            'name' => 'Genre name (required)',
                        ],
                        'response' => 'JSON object with updated genre details',
                    ],
                    [
                        'method' => 'DELETE',
                        'endpoint' => '/genres/{id}',
                        'description' => 'Delete a genre',
                        'parameters' => [],
                        'response' => 'JSON confirmation message',
                    ],
                ],
            ],
            [
                'name' => 'System Stats API',
                'description' => 'API endpoint for system statistics',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/system-stats',
                        'description' => 'Get system statistics',
                        'parameters' => [],
                        'response' => 'JSON object with system statistics',
                    ],
                ],
            ],
        ];

        return view('livewire.api-documentation', [
            'apiEndpoints' => $apiEndpoints,
        ]);
    }
} 