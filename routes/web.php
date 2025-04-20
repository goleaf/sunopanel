<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Genres;
use App\Http\Livewire\Tracks;
use App\Http\Livewire\Playlists;
use App\Http\Livewire\PlaylistForm;
use App\Http\Livewire\PlaylistShow;
use App\Http\Livewire\PlaylistAddTracks;

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
Route::get('/dashboard', Dashboard::class)->middleware(['auth', 'verified'])->name('dashboard');

// Track routes
Route::get('/tracks', Tracks::class)->middleware(['auth', 'verified'])->name('tracks.index');
Route::get('tracks/{id}/play', [TrackController::class, 'play'])->name('tracks.play');
Route::post('tracks/bulk-upload', [TrackController::class, 'processBulkUpload'])->name('tracks.bulk-upload');

// Genre routes
Route::get('/genres', Genres::class)->middleware(['auth', 'verified'])->name('genres.index');

// Playlist routes
Route::get('/playlists', Playlists::class)->middleware(['auth', 'verified'])->name('playlists.index');
Route::get('playlists/create', PlaylistForm::class)->middleware(['auth', 'verified'])->name('playlists.create');
Route::get('playlists/{playlist}/edit', PlaylistForm::class)->middleware(['auth', 'verified'])->name('playlists.edit');
Route::get('playlists/{playlist}', PlaylistShow::class)->middleware(['auth', 'verified'])->name('playlists.show');
Route::get('playlists/{playlist}/add-tracks', PlaylistAddTracks::class)->middleware(['auth', 'verified'])->name('playlists.add-tracks');
// Keep these routes for now
Route::post('playlists', [PlaylistController::class, 'store'])->name('playlists.store');
Route::put('playlists/{playlist}', [PlaylistController::class, 'update'])->name('playlists.update');
Route::delete('playlists/{playlist}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');
Route::post('playlists/{playlist}/tracks', [PlaylistController::class, 'storeTracks'])->name('playlists.store-tracks');
Route::delete('playlists/{playlist}/tracks/{track}', [PlaylistController::class, 'removeTrack'])->name('playlists.remove-track');
Route::post('genres/{genre}/create-playlist', [PlaylistController::class, 'createFromGenre'])->name('playlists.create-from-genre');

// System Stats
Route::get('/system-stats', [DashboardController::class, 'systemStats'])->name('system.stats');

// Test Routes
Route::get('/test-notification', [TestController::class, 'testNotification'])->name('test.notification');
Route::get('/flash-message/{type}', [TestController::class, 'setFlashMessage'])->name('test.flash');
Route::get('/json-notification/{type}', [TestController::class, 'testJsonResponse'])->name('test.json');
