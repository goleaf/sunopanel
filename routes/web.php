<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

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

// Home routes (Add tracks)
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/process', [HomeController::class, 'process'])->name('home.process');
Route::post('/process-immediate', [HomeController::class, 'processImmediate'])->name('home.process.immediate');

// Tracks routes
Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');
Route::get('/tracks/{track}', [TrackController::class, 'show'])->name('tracks.show');
Route::delete('/tracks/{track}', [TrackController::class, 'destroy'])->name('tracks.destroy');
Route::get('/tracks/{track}/status', [TrackController::class, 'status'])->name('tracks.status');
Route::post('/tracks/{track}/retry', [TrackController::class, 'retry'])->name('tracks.retry');

// Genre routes
Route::resource('genres', GenreController::class);
