<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
// Add explicit dashboard route for tests
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.show');

// Track routes
Route::resource('tracks', TrackController::class);
Route::get('tracks/{id}/play', [TrackController::class, 'play'])->name('tracks.play');
Route::post('tracks/bulk-upload', [TrackController::class, 'processBulkUpload'])->name('tracks.bulk-upload');

// Genre routes
Route::resource('genres', GenreController::class);

// Playlist routes
Route::resource('playlists', PlaylistController::class);
Route::get('playlists/{playlist}/add-tracks', [PlaylistController::class, 'addTracks'])->name('playlists.add-tracks');
Route::post('playlists/{playlist}/tracks', [PlaylistController::class, 'storeTracks'])->name('playlists.store-tracks');
Route::delete('playlists/{playlist}/tracks/{track}', [PlaylistController::class, 'removeTrack'])->name('playlists.remove-track');
Route::post('genres/{genre}/create-playlist', [PlaylistController::class, 'createFromGenre'])->name('playlists.create-from-genre');

// System Stats
Route::get('/system-stats', [DashboardController::class, 'systemStats'])->name('system.stats');
