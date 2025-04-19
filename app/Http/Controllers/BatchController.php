<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Batch\BatchService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

final class BatchController extends Controller
{
    public function __construct(
        private readonly BatchService $batchService
    ) {
    }

    /**
     * Display a listing of all batches
     */
    public function index(): View
    {
        // Get batch data from service
        $batchData = $this->batchService->getBatchData();
        
        return view('batch.index', [
            'tracks' => $batchData['tracks'],
            'genres' => $batchData['genres'],
            'playlists' => $batchData['playlists']
        ]);
    }

    /**
     * Show the batch import form with examples and tab data
     */
    public function import(): RedirectResponse
    {
        Log::info('Accessed batch import page');
        return redirect()->route('dashboard')->with('success', 'Import feature temporarily disabled');
    }

    /**
     * Process batch import form
     */
    public function processImport(Request $request): RedirectResponse
    {
        Log::info('Starting batch import process');
        return redirect()->route('dashboard')->with('success', 'Import process temporarily disabled');
    }

    /**
     * Import tracks in batch
     */
    public function importTracks(Request $request): RedirectResponse
    {
        // Delegate to service
        $result = $this->batchService->importTracks($request);
        
        $message = $result 
            ? 'Tracks imported successfully' 
            : 'Track import temporarily disabled';
            
        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Import playlists in batch
     */
    public function importPlaylists(Request $request): RedirectResponse
    {
        // Delegate to service
        $result = $this->batchService->importPlaylists($request);
        
        $message = $result 
            ? 'Playlists imported successfully' 
            : 'Playlist import temporarily disabled';
            
        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Import genres in batch
     */
    public function importGenres(Request $request): RedirectResponse
    {
        // Delegate to service
        $result = $this->batchService->importGenres($request);
        
        $message = $result 
            ? 'Genres imported successfully' 
            : 'Genre import temporarily disabled';
            
        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Show batch actions page
     */
    public function actions(): RedirectResponse
    {
        Log::info('Accessing batch actions page');
        return redirect()->route('dashboard')->with('success', 'Batch actions temporarily disabled');
    }

    /**
     * Process batch actions
     */
    public function processActions(Request $request): RedirectResponse
    {
        Log::info('Processing batch actions');
        return redirect()->route('dashboard')->with('success', 'Batch actions temporarily disabled');
    }

    /**
     * Assign genres to tracks in batch
     */
    public function assignGenres(Request $request): RedirectResponse
    {
        // Delegate to service
        $result = $this->batchService->assignGenres($request);
        
        $message = $result 
            ? 'Genres assigned successfully' 
            : 'Genre assignment temporarily disabled';
            
        return redirect()->route('dashboard')->with('success', $message);
    }

    /**
     * Add tracks to playlist in batch
     */
    public function addToPlaylist(Request $request): RedirectResponse
    {
        // Delegate to service
        $result = $this->batchService->addToPlaylist($request);
        
        $message = $result 
            ? 'Tracks added to playlist successfully' 
            : 'Adding tracks to playlist temporarily disabled';
            
        return redirect()->route('dashboard')->with('success', $message);
    }
} 