<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    /**
     * Display the import dashboard.
     */
    public function index()
    {
        $stats = [
            'total_tracks' => Track::count(),
            'total_genres' => Genre::count(),
            'pending_tracks' => Track::where('status', 'pending')->count(),
            'processing_tracks' => Track::where('status', 'processing')->count(),
            'completed_tracks' => Track::where('status', 'completed')->count(),
            'failed_tracks' => Track::where('status', 'failed')->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return view('import.index', compact('stats'));
    }

    /**
     * Execute JSON import command.
     */
    public function importJson(Request $request)
    {
        $request->validate([
            'source_type' => 'required|in:file,url',
            'json_file' => 'required_if:source_type,file|file|mimes:json,txt',
            'json_url' => 'required_if:source_type,url|url',
            'format' => 'required|in:auto,pipe,object,array',
            'limit' => 'nullable|integer|min:1|max:1000',
            'skip' => 'nullable|integer|min:0',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        $sessionId = uniqid('import_json_');
        Cache::put("import_progress_{$sessionId}", [
            'status' => 'starting',
            'progress' => 0,
            'message' => 'Initializing JSON import...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
        ], 3600);

        // Build command
        $command = 'import:json';
        $params = [];

        if ($request->source_type === 'file') {
            $file = $request->file('json_file');
            $path = $file->store('imports', 'local');
            $params['source'] = storage_path("app/{$path}");
        } else {
            $params['source'] = $request->json_url;
        }

        if ($request->format !== 'auto') {
            $params['--format'] = $request->format;
        }

        if ($request->limit) {
            $params['--limit'] = $request->limit;
        }

        if ($request->skip) {
            $params['--skip'] = $request->skip;
        }

        if ($request->dry_run) {
            $params['--dry-run'] = true;
        }

        if ($request->process) {
            $params['--process'] = true;
        }

        // Add session ID for progress tracking
        $params['--session-id'] = $sessionId;

        try {
            // Run command in background
            $this->runCommandAsync($command, $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'JSON import started successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('JSON import failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start JSON import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute Suno Discover import command.
     */
    public function importSunoDiscover(Request $request)
    {
        $request->validate([
            'section' => 'required|in:trending_songs,new_songs,popular_songs',
            'page_size' => 'required|integer|min:1|max:100',
            'pages' => 'required|integer|min:1|max:10',
            'start_index' => 'nullable|integer|min:0',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        $sessionId = uniqid('import_discover_');
        Cache::put("import_progress_{$sessionId}", [
            'status' => 'starting',
            'progress' => 0,
            'message' => 'Initializing Suno Discover import...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
        ], 3600);

        $params = [
            '--section' => $request->section,
            '--page-size' => $request->page_size,
            '--pages' => $request->pages,
            '--session-id' => $sessionId,
        ];

        if ($request->start_index) {
            $params['--start-index'] = $request->start_index;
        }

        if ($request->dry_run) {
            $params['--dry-run'] = true;
        }

        if ($request->process) {
            $params['--process'] = true;
        }

        try {
            $this->runCommandAsync('import:suno-discover', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Suno Discover import started successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Suno Discover import failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start Suno Discover import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute Suno Search import command.
     */
    public function importSunoSearch(Request $request)
    {
        $request->validate([
            'term' => 'nullable|string|max:255',
            'size' => 'required|integer|min:1|max:100',
            'pages' => 'required|integer|min:1|max:10',
            'rank_by' => 'required|in:upvote_count,play_count,dislike_count,trending,most_recent,most_relevant,by_hour,by_day,by_week,by_month,all_time,default',
            'instrumental' => 'boolean',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        $sessionId = uniqid('import_search_');
        Cache::put("import_progress_{$sessionId}", [
            'status' => 'starting',
            'progress' => 0,
            'message' => 'Initializing Suno Search import...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
        ], 3600);

        $params = [
            '--size' => $request->size,
            '--pages' => $request->pages,
            '--rank-by' => $request->rank_by,
            '--session-id' => $sessionId,
        ];

        if ($request->term) {
            $params['--term'] = $request->term;
        }

        if ($request->instrumental) {
            $params['--instrumental'] = 'true';
        }

        if ($request->dry_run) {
            $params['--dry-run'] = true;
        }

        if ($request->process) {
            $params['--process'] = true;
        }

        try {
            $this->runCommandAsync('import:suno-search', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Suno Search import started successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Suno Search import failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start Suno Search import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute Suno All (unified) import command.
     */
    public function importSunoAll(Request $request)
    {
        $request->validate([
            'sources' => 'required|array|min:1',
            'sources.*' => 'in:discover,search,json',
            'json_file' => 'required_if:sources,json|file|mimes:json,txt',
            'json_url' => 'nullable|url',
            'discover_pages' => 'nullable|integer|min:1|max:10',
            'discover_size' => 'nullable|integer|min:1|max:100',
            'search_pages' => 'nullable|integer|min:1|max:10',
            'search_size' => 'nullable|integer|min:1|max:100',
            'search_term' => 'nullable|string|max:255',
            'search_rank' => 'nullable|in:upvote_count,play_count,dislike_count,trending,most_recent,most_relevant,by_hour,by_day,by_week,by_month,all_time,default',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        $sessionId = uniqid('import_all_');
        Cache::put("import_progress_{$sessionId}", [
            'status' => 'starting',
            'progress' => 0,
            'message' => 'Initializing unified import...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
        ], 3600);

        $params = [
            '--sources' => implode(',', $request->sources),
            '--session-id' => $sessionId,
        ];

        if (in_array('json', $request->sources)) {
            if ($request->hasFile('json_file')) {
                $file = $request->file('json_file');
                $path = $file->store('imports', 'local');
                $params['--json-file'] = storage_path("app/{$path}");
            } elseif ($request->json_url) {
                $params['--json-url'] = $request->json_url;
            }
        }

        if ($request->discover_pages) {
            $params['--discover-pages'] = $request->discover_pages;
        }

        if ($request->discover_size) {
            $params['--discover-size'] = $request->discover_size;
        }

        if ($request->search_pages) {
            $params['--search-pages'] = $request->search_pages;
        }

        if ($request->search_size) {
            $params['--search-size'] = $request->search_size;
        }

        if ($request->search_term) {
            $params['--search-term'] = $request->search_term;
        }

        if ($request->search_rank) {
            $params['--search-rank'] = $request->search_rank;
        }

        if ($request->dry_run) {
            $params['--dry-run'] = true;
        }

        if ($request->process) {
            $params['--process'] = true;
        }

        try {
            $this->runCommandAsync('import:suno-all', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Unified import started successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Unified import failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start unified import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import progress for real-time updates.
     */
    public function getProgress($sessionId)
    {
        $progress = Cache::get("import_progress_{$sessionId}");
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or expired'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress
        ]);
    }

    /**
     * Get current system statistics.
     */
    public function getStats()
    {
        $stats = [
            'total_tracks' => Track::count(),
            'total_genres' => Genre::count(),
            'pending_tracks' => Track::where('status', 'pending')->count(),
            'processing_tracks' => Track::where('status', 'processing')->count(),
            'completed_tracks' => Track::where('status', 'completed')->count(),
            'failed_tracks' => Track::where('status', 'failed')->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Run artisan command asynchronously.
     */
    private function runCommandAsync(string $command, array $params, string $sessionId)
    {
        // Update progress to indicate command is starting
        Cache::put("import_progress_{$sessionId}", [
            'status' => 'running',
            'progress' => 5,
            'message' => 'Command started, processing...',
            'imported' => 0,
            'failed' => 0,
            'total' => 0,
        ], 3600);

        // Run command in background using Laravel's queue system
        dispatch(function () use ($command, $params, $sessionId) {
            try {
                $exitCode = Artisan::call($command, $params);
                
                Cache::put("import_progress_{$sessionId}", [
                    'status' => $exitCode === 0 ? 'completed' : 'failed',
                    'progress' => 100,
                    'message' => $exitCode === 0 ? 'Import completed successfully' : 'Import failed',
                    'imported' => 0, // Will be updated by the command itself
                    'failed' => 0,
                    'total' => 0,
                    'exit_code' => $exitCode,
                ], 3600);
            } catch (\Exception $e) {
                Cache::put("import_progress_{$sessionId}", [
                    'status' => 'failed',
                    'progress' => 100,
                    'message' => 'Import failed: ' . $e->getMessage(),
                    'imported' => 0,
                    'failed' => 0,
                    'total' => 0,
                    'error' => $e->getMessage(),
                ], 3600);
            }
        });
    }
} 