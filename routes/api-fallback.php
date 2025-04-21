<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\GenreController;

/*
|--------------------------------------------------------------------------
| API Fallback Routes
|--------------------------------------------------------------------------
|
| These routes handle direct API-style calls that are used in tests and
| may still be used by external scripts. They're kept for backward 
| compatibility but new code should use the Livewire components.
|
*/

// Playlist API routes
Route::delete('/playlists/{playlist}/tracks/{track}', [PlaylistController::class, 'removeTrack'])
    ->name('playlists.remove-track');
Route::post('/genres/{genre}/playlists', [PlaylistController::class, 'createFromGenre'])
    ->name('playlists.create-from-genre.api');
Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy'])
    ->name('playlists.destroy.api');

// Track API routes
Route::post('/tracks', [TrackController::class, 'store'])
    ->name('tracks.store.api');
Route::put('/tracks/{track}', [TrackController::class, 'update'])
    ->name('tracks.update.api');
Route::delete('/tracks/{track}', [TrackController::class, 'destroy'])
    ->name('tracks.destroy.api');

// Genre API routes
Route::post('/genres', [GenreController::class, 'store'])
    ->name('genres.store.api');
Route::put('/genres/{genre}', [GenreController::class, 'update'])
    ->name('genres.update.api');
Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])
    ->name('genres.destroy.api'); 