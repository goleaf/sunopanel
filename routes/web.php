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
use App\Http\Livewire\TrackPlay;
use App\Http\Livewire\TrackUpload;
use App\Http\Livewire\SystemStats;

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

// Track routes
Route::get('/tracks', Tracks::class)->name('tracks.index');
Route::get('/tracks/create', TrackCreate::class)->name('tracks.create');
Route::get('/tracks/{id}/play', TrackPlay::class)->name('tracks.play');
Route::get('/tracks/bulk-upload', TrackUpload::class)->name('tracks.bulk-upload');

// Genre routes
Route::get('/genres', Genres::class)->name('genres.index');
Route::get('/genres/create', GenreCreate::class)->name('genres.create');

// Playlist routes
Route::get('/playlists', Playlists::class)->name('playlists.index');
Route::get('playlists/create', PlaylistForm::class)->name('playlists.create');
Route::get('playlists/{playlist}/edit', PlaylistForm::class)->name('playlists.edit');
Route::get('playlists/{playlist}', PlaylistShow::class)->name('playlists.show');
Route::get('playlists/{playlist}/add-tracks', PlaylistAddTracks::class)->name('playlists.add-tracks');
