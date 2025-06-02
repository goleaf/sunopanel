<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\Genre;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class ImportController extends Controller
{
    public function __construct(
        private readonly ImportService $importService
    ) {}

    /**
     * Display the import dashboard.
     */
    public function index(): View
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
    public function importJson(Request $request): JsonResponse
    {
        // Rate limiting (skip in testing environment unless explicitly enabled)
        if (!app()->environment('testing') || config('app.test_rate_limiting', false)) {
            $key = 'import_json_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many import attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 300); // 5 minutes
        }

        $validator = Validator::make($request->all(), [
            'source_type' => 'required|in:file,url',
            'json_file' => 'required_if:source_type,file|file|mimes:json,txt|max:10240', // 10MB
            'json_url' => 'required_if:source_type,url|url|max:2048',
            'format' => 'required|in:auto,pipe,object,array',
            'field' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:10000',
            'skip' => 'nullable|integer|min:0',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $this->importService->createProgressSession('json');
            
            // Additional security validation
            if ($request->source_type === 'file') {
                $file = $request->file('json_file');
                
                // Validate file size and type
                if (!$this->importService->validateFileSize($file->getSize())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File size exceeds maximum allowed limit (10MB)',
                    ], 422);
                }

                if (!$this->importService->validateFileType($file->getMimeType())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid file type. Only JSON and text files are allowed.',
                    ], 422);
                }

                // Store file securely
                $path = $file->store('imports', 'local');
                
                // Get the full path - handle both real and fake storage
                if (app()->runningUnitTests()) {
                    // In testing, always use Storage facade to get content
                    $content = Storage::disk('local')->get($path);
                    if ($content === null) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to store uploaded file',
                        ], 500);
                    }
                } else {
                    // In production, use real file system
                    $source = storage_path("app/{$path}");
                    
                    // Validate JSON content
                    if (!file_exists($source)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to store uploaded file',
                        ], 500);
                    }
                    
                    $content = file_get_contents($source);
                    if ($content === false) {
                        Storage::disk('local')->delete($path);
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to read uploaded file',
                        ], 500);
                    }
                }
                
                if (!$this->importService->validateJsonFormat($content)) {
                    Storage::disk('local')->delete($path);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid JSON format in uploaded file',
                    ], 422);
                }
                
                // Set source path for command
                $source = app()->runningUnitTests() ? $path : storage_path("app/{$path}");
            } else {
                $source = $request->json_url;
                
                // Validate URL domain for security
                $parsedUrl = parse_url($source);
                if (!$parsedUrl || !isset($parsedUrl['host'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid URL format',
                    ], 422);
                }
            }

            // Build command parameters
            $params = [
                'source' => $source,
                '--session-id' => $sessionId,
            ];

            if ($request->format !== 'auto') {
                $params['--format'] = $request->format;
            }

            if ($request->field) {
                $params['--field'] = $request->field;
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

            // Log import activity
            $this->importService->logImportActivity($sessionId, 'json_import_started', [
                'source_type' => $request->source_type,
                'format' => $request->format,
                'limit' => $request->limit,
                'dry_run' => $request->dry_run,
                'process' => $request->process,
            ]);

            // Run command asynchronously
            $this->runCommandAsync('import:json', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'JSON import started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('JSON import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['json_file']),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start JSON import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute Suno Discover import command.
     */
    public function importSunoDiscover(Request $request): JsonResponse
    {
        // Rate limiting (skip in testing environment unless explicitly enabled)
        if (!app()->environment('testing') || config('app.test_rate_limiting', false)) {
            $key = 'import_discover_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many discover import attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 600); // 10 minutes
        }

        $validator = Validator::make($request->all(), [
            'section' => 'required|in:trending_songs,new_songs,popular_songs',
            'page_size' => 'required|integer|min:1|max:100',
            'pages' => 'required|integer|min:1|max:10',
            'start_index' => 'nullable|integer|min:0',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $this->importService->createProgressSession('discover');

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

            // Log import activity
            $this->importService->logImportActivity($sessionId, 'discover_import_started', [
                'section' => $request->section,
                'page_size' => $request->page_size,
                'pages' => $request->pages,
                'dry_run' => $request->dry_run,
                'process' => $request->process,
            ]);

            $this->runCommandAsync('import:suno-discover', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Suno Discover import started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Suno Discover import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start Suno Discover import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute Suno Search import command.
     */
    public function importSunoSearch(Request $request): JsonResponse
    {
        // Rate limiting (skip in testing environment unless explicitly enabled)
        if (!app()->environment('testing') || config('app.test_rate_limiting', false)) {
            $key = 'import_search_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many search import attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 600); // 10 minutes
        }

        $validator = Validator::make($request->all(), [
            'term' => 'nullable|string|max:255',
            'size' => 'required|integer|min:1|max:100',
            'pages' => 'required|integer|min:1|max:10',
            'rank_by' => 'required|in:upvote_count,play_count,dislike_count,trending,most_recent,most_relevant,by_hour,by_day,by_week,by_month,all_time,default',
            'instrumental' => 'boolean',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $this->importService->createProgressSession('search');

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

            // Log import activity
            $this->importService->logImportActivity($sessionId, 'search_import_started', [
                'term' => $request->term,
                'size' => $request->size,
                'pages' => $request->pages,
                'rank_by' => $request->rank_by,
                'instrumental' => $request->instrumental,
                'dry_run' => $request->dry_run,
                'process' => $request->process,
            ]);

            $this->runCommandAsync('import:suno-search', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Suno Search import started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Suno Search import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start Suno Search import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute Suno Genre import command.
     */
    public function importSunoGenre(Request $request): JsonResponse
    {
        // Rate limiting (skip in testing environment)
        if (!app()->environment('testing')) {
            $key = 'import_genre_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many genre import attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 600); // 10 minutes
        }

        $validator = Validator::make($request->all(), [
            'genre' => 'required|string|max:255',
            'from_index' => 'nullable|integer|min:0',
            'size' => 'required|integer|min:1|max:100',
            'pages' => 'required|integer|min:1|max:10',
            'rank_by' => 'required|in:most_relevant,trending,most_recent,upvote_count,play_count,dislike_count,by_hour,by_day,by_week,by_month,all_time,default',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionId = $this->importService->createProgressSession('genre');

            $params = [
                '--genre' => $request->genre,
                '--size' => $request->size,
                '--pages' => $request->pages,
                '--rank-by' => $request->rank_by,
                '--session-id' => $sessionId,
            ];

            if ($request->from_index) {
                $params['--from-index'] = $request->from_index;
            }

            if ($request->dry_run) {
                $params['--dry-run'] = true;
            }

            if ($request->process) {
                $params['--process'] = true;
            }

            // Log import activity
            $this->importService->logImportActivity($sessionId, 'genre_import_started', [
                'genre' => $request->genre,
                'from_index' => $request->from_index,
                'size' => $request->size,
                'pages' => $request->pages,
                'rank_by' => $request->rank_by,
                'dry_run' => $request->dry_run,
                'process' => $request->process,
            ]);

            $this->runCommandAsync('import:suno-genre', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Suno Genre import started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Suno Genre import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start Suno Genre import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute Suno All (unified) import command.
     */
    public function importSunoAll(Request $request): JsonResponse
    {
        // Rate limiting (skip in testing environment)
        if (!app()->environment('testing')) {
            $key = 'import_all_' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 2)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many unified import attempts. Please try again later.',
                ], 429);
            }

            RateLimiter::hit($key, 900); // 15 minutes
        }

        $validator = Validator::make($request->all(), [
            'sources' => 'required|array|min:1',
            'sources.*' => 'in:discover,search,json',
            'json_file' => 'nullable|file|mimes:json,txt|max:10240',
            'json_url' => 'nullable|url|max:2048',
            'discover_pages' => 'nullable|integer|min:1|max:10',
            'discover_size' => 'nullable|integer|min:1|max:100',
            'search_pages' => 'nullable|integer|min:1|max:10',
            'search_size' => 'nullable|integer|min:1|max:100',
            'search_term' => 'nullable|string|max:255',
            'search_rank' => 'nullable|in:upvote_count,play_count,dislike_count,trending,most_recent,most_relevant,by_hour,by_day,by_week,by_month,all_time,default',
            'dry_run' => 'boolean',
            'process' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Additional validation for json source
        if (in_array('json', $request->sources) && !$request->hasFile('json_file') && !$request->json_url) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['json_file' => ['The json file field is required when json source is selected.']],
            ], 422);
        }

        try {
            $sessionId = $this->importService->createProgressSession('all');

            $params = [
                '--sources' => implode(',', $request->sources),
                '--session-id' => $sessionId,
            ];

            if (in_array('json', $request->sources)) {
                if ($request->hasFile('json_file')) {
                    $file = $request->file('json_file');
                    
                    // Validate file
                    if (!$this->importService->validateFileSize($file->getSize()) ||
                        !$this->importService->validateFileType($file->getMimeType())) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid JSON file',
                        ], 422);
                    }

                    $path = $file->store('imports', 'local');
                    $params['--json-file'] = storage_path("app/{$path}");
                } elseif ($request->json_url) {
                    $params['--json-url'] = $request->json_url;
                }
            }

            // Add optional parameters
            $optionalParams = [
                'discover_pages' => '--discover-pages',
                'discover_size' => '--discover-size',
                'search_pages' => '--search-pages',
                'search_size' => '--search-size',
                'search_term' => '--search-term',
                'search_rank' => '--search-rank',
            ];

            foreach ($optionalParams as $requestKey => $paramKey) {
                if ($request->has($requestKey)) {
                    $params[$paramKey] = $request->input($requestKey);
                }
            }

            if ($request->dry_run) {
                $params['--dry-run'] = true;
            }

            if ($request->process) {
                $params['--process'] = true;
            }

            // Log import activity
            $this->importService->logImportActivity($sessionId, 'unified_import_started', [
                'sources' => $request->sources,
                'dry_run' => $request->dry_run,
                'process' => $request->process,
            ]);

            $this->runCommandAsync('import:suno-all', $params, $sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Unified import started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Unified import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['json_file']),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start unified import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import progress for real-time updates.
     */
    public function getProgress(string $sessionId): JsonResponse
    {
        // Validate session ID format - allow alphanumeric and underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sessionId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session ID format',
            ], 400);
        }

        $progress = Cache::get("import_progress_{$sessionId}");
        
        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found or expired',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Get current system statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_tracks' => Track::count(),
                'total_genres' => Genre::count(),
                'pending_tracks' => Track::where('status', 'pending')->count(),
                'processing_tracks' => Track::where('status', 'processing')->count(),
                'completed_tracks' => Track::where('status', 'completed')->count(),
                'failed_tracks' => Track::where('status', 'failed')->count(),
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'last_updated' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get import stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
            ], 500);
        }
    }

    /**
     * Run artisan command asynchronously.
     */
    private function runCommandAsync(string $command, array $params, string $sessionId): void
    {
        // Update progress to indicate command is starting
        $this->importService->updateProgress($sessionId, [
            'status' => 'running',
            'progress' => 5,
            'message' => 'Command started, processing...',
        ]);

        // For testing, run synchronously to avoid queue issues
        if (app()->environment('testing')) {
            try {
                $exitCode = Artisan::call($command, $params);
                
                $this->importService->updateProgress($sessionId, [
                    'status' => $exitCode === 0 ? 'completed' : 'failed',
                    'progress' => 100,
                    'message' => $exitCode === 0 ? 'Import completed successfully' : 'Import failed',
                    'exit_code' => $exitCode,
                ]);

                // Log completion
                $this->importService->logImportActivity($sessionId, 'import_completed', [
                    'command' => $command,
                    'exit_code' => $exitCode,
                ]);

            } catch (\Exception $e) {
                $this->importService->updateProgress($sessionId, [
                    'status' => 'failed',
                    'progress' => 100,
                    'message' => 'Import failed: ' . $e->getMessage(),
                    'error' => $e->getMessage(),
                ]);

                // Log error
                $this->importService->logImportActivity($sessionId, 'import_failed', [
                    'command' => $command,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // For production, use queue system
            // Capture the import service instance for use in the closure
            $importService = $this->importService;

            // Run command in background using Laravel's queue system
            dispatch(function () use ($command, $params, $sessionId, $importService) {
                try {
                    $exitCode = Artisan::call($command, $params);
                    
                    $importService->updateProgress($sessionId, [
                        'status' => $exitCode === 0 ? 'completed' : 'failed',
                        'progress' => 100,
                        'message' => $exitCode === 0 ? 'Import completed successfully' : 'Import failed',
                        'exit_code' => $exitCode,
                    ]);

                    // Log completion
                    $importService->logImportActivity($sessionId, 'import_completed', [
                        'command' => $command,
                        'exit_code' => $exitCode,
                    ]);

                } catch (\Exception $e) {
                    $importService->updateProgress($sessionId, [
                        'status' => 'failed',
                        'progress' => 100,
                        'message' => 'Import failed: ' . $e->getMessage(),
                        'error' => $e->getMessage(),
                    ]);

                    // Log error
                    $importService->logImportActivity($sessionId, 'import_failed', [
                        'command' => $command,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    }
} 