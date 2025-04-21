<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Genres;
use App\Http\Livewire\Tracks;
use App\Http\Livewire\Playlists;
use App\Http\Livewire\PlaylistForm;
use App\Http\Livewire\PlaylistShow;
use App\Http\Livewire\PlaylistAddTracks;
use App\Http\Livewire\GenreCreate;
use App\Http\Livewire\TrackCreate;
use App\Http\Livewire\TrackEdit;
use App\Http\Livewire\TrackPlay;
use App\Http\Livewire\TrackShow;
use App\Http\Livewire\TrackUpload;
use App\Http\Livewire\SystemStats;
use App\Http\Livewire\ApiDocumentation;

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

// Dashboard route
Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/dashboard', Dashboard::class)->name('dashboard');

// System Stats API
Route::get('/system-stats', SystemStats::class)->name('system.stats');

// API Documentation
Route::get('/api-docs', ApiDocumentation::class)->name('api.documentation');

// Track routes
Route::get('/tracks', Tracks::class)->name('tracks.index');
Route::get('/tracks/create', TrackCreate::class)->name('tracks.create');
Route::post('/tracks', Tracks::class)->name('tracks.store');
Route::get('/tracks/{track}', TrackShow::class)->name('tracks.show');
Route::get('/tracks/{track}/edit', TrackEdit::class)->name('tracks.edit');
Route::put('/tracks/{track}', TrackEdit::class)->name('tracks.update');
Route::delete('/tracks/{track}', Tracks::class)->name('tracks.destroy');
Route::get('/tracks/{id}/play', TrackPlay::class)->name('tracks.play');
Route::get('/tracks/bulk-upload', TrackUpload::class)->name('tracks.bulk-upload');
Route::post('/tracks/bulk-upload', TrackUpload::class)->name('tracks.process-bulk-upload');

// Genre routes
Route::get('/genres', Genres::class)->name('genres.index');
Route::get('/genres/create', GenreCreate::class)->name('genres.create');
Route::post('/genres', Genres::class)->name('genres.store');
Route::delete('/genres/{genre}', Genres::class)->name('genres.destroy');

// Playlist routes
Route::get('/playlists', Playlists::class)->name('playlists.index');
Route::get('/playlists/create', PlaylistForm::class)->name('playlists.create');
Route::post('/playlists', PlaylistForm::class)->name('playlists.store');
Route::get('/playlists/{playlist}/edit', PlaylistForm::class)->name('playlists.edit');
Route::put('/playlists/{playlist}', PlaylistForm::class)->name('playlists.update');
Route::get('/playlists/{playlist}', PlaylistShow::class)->name('playlists.show');
Route::delete('/playlists/{playlist}', Playlists::class)->name('playlists.destroy');
Route::get('/playlists/{playlist}/add-tracks', PlaylistAddTracks::class)->name('playlists.add-tracks');
Route::post('/playlists/{playlist}/tracks', PlaylistAddTracks::class)->name('playlists.store-tracks');
Route::delete('/playlists/{playlist}/tracks/{track}', PlaylistShow::class)->name('playlists.remove-track');
Route::post('/genres/{genre}/playlists', Genres::class)->name('playlists.create-from-genre');

// Test route for notifications
Route::get('/test-notification', function() {
    return view('test-notification');
})->name('test.notification');

// Livewire script route for SSR optimization
Route::get('/livewire/livewire.js', function () {
    return response(file_get_contents(public_path('vendor/livewire/livewire.js')), 200, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
    ]);
})->name('livewire.js');

// Offline page for service worker
Route::get('/offline', function () {
    return view('errors.offline');
})->name('offline');
