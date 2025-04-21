<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize-queries {--analyze : Just analyze queries without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optimize database queries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database query optimization...');
        
        $isAnalyzeOnly = $this->option('analyze');
        
        if ($isAnalyzeOnly) {
            $this->info('Running in analysis mode only. No changes will be made.');
        }
        
        // Analyze the queries in the query log
        $this->analyzeQueryLog();
        
        // Check indexes on commonly queried tables
        $this->checkIndexes($isAnalyzeOnly);
        
        // Optimize database tables
        $this->optimizeTables($isAnalyzeOnly);
        
        $this->info('Database query optimization completed!');
        
        return 0;
    }
    
    /**
     * Analyze the query log to find slow or problematic queries.
     */
    private function analyzeQueryLog(): void
    {
        $this->info('Analyzing query log...');
        
        // Enable query logging
        DB::connection()->enableQueryLog();
        
        // Simulate some common queries
        $this->simulateCommonQueries();
        
        // Get the query log
        $queryLog = DB::getQueryLog();
        
        // Analyze the queries
        $problematicQueries = [];
        $suggestedIndexes = [];
        
        foreach ($queryLog as $index => $query) {
            $sql = $query['query'];
            $bindings = $query['bindings'];
            $time = $query['time'];
            
            // Check for slow queries (> 100ms)
            if ($time > 100) {
                $problematicQueries[] = [
                    'sql' => $this->interpolateQuery($sql, $bindings),
                    'time' => $time,
                    'issue' => 'Slow query (>100ms)'
                ];
            }
            
            // Check for queries with 'like' without indexes
            if (preg_match('/where\s+`?(\w+)`?\s+like\s+\?/i', $sql, $matches)) {
                $column = $matches[1];
                $table = $this->extractTableFromQuery($sql);
                
                if ($table && $column) {
                    $suggestedIndexes[$table][] = $column;
                }
            }
            
            // Check for ORDER BY on non-indexed columns
            if (preg_match('/order\s+by\s+`?(\w+)\.?(\w+)?`?/i', $sql, $matches)) {
                $table = $matches[1];
                $column = $matches[2] ?? null;
                
                if ($table && $column) {
                    $suggestedIndexes[$table][] = $column;
                }
            }
        }
        
        // Display problematic queries
        if (count($problematicQueries) > 0) {
            $this->warn('Problematic Queries Found:');
            foreach ($problematicQueries as $query) {
                $this->line("- Query: {$query['sql']}");
                $this->line("  Time: {$query['time']}ms");
                $this->line("  Issue: {$query['issue']}");
                $this->line('');
            }
        } else {
            $this->info('No problematic queries found!');
        }
        
        // Display suggested indexes
        if (count($suggestedIndexes) > 0) {
            $this->warn('Suggested Indexes:');
            foreach ($suggestedIndexes as $table => $columns) {
                $columns = array_unique($columns);
                $this->line("- Table: {$table}");
                $this->line("  Columns: " . implode(', ', $columns));
                $this->line('');
            }
        } else {
            $this->info('No suggested indexes found!');
        }
    }
    
    /**
     * Check indexes on commonly queried tables.
     */
    private function checkIndexes(bool $analyzeOnly): void
    {
        $this->info('Checking indexes on commonly queried tables...');
        
        $tables = ['tracks', 'playlists', 'genres', 'users', 'genre_track', 'playlist_track'];
        $columnsToIndex = [
            'tracks' => ['title', 'artist', 'album', 'created_at', 'updated_at'],
            'playlists' => ['title', 'user_id', 'genre_id', 'created_at', 'updated_at'],
            'genres' => ['name', 'created_at', 'updated_at'],
            'users' => ['name', 'email', 'created_at', 'updated_at'],
            'genre_track' => ['genre_id', 'track_id'],
            'playlist_track' => ['playlist_id', 'track_id', 'position'],
        ];
        
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table {$table} does not exist. Skipping.");
                continue;
            }
            
            $this->info("Checking indexes for table: {$table}");
            
            // Get existing indexes
            $existingIndexes = [];
            $indexesRaw = DB::select("SHOW INDEXES FROM {$table}");
            
            foreach ($indexesRaw as $index) {
                $existingIndexes[] = $index->Column_name;
            }
            $existingIndexes = array_unique($existingIndexes);
            
            $this->line("  Existing indexes: " . implode(', ', $existingIndexes));
            
            // Check for missing indexes
            $missingIndexes = [];
            foreach ($columnsToIndex[$table] ?? [] as $column) {
                if (!in_array($column, $existingIndexes) && Schema::hasColumn($table, $column)) {
                    $missingIndexes[] = $column;
                }
            }
            
            if (count($missingIndexes) > 0) {
                $this->warn("  Missing indexes: " . implode(', ', $missingIndexes));
                
                // Add missing indexes if not in analyze-only mode
                if (!$analyzeOnly) {
                    foreach ($missingIndexes as $column) {
                        $this->info("  Adding index on {$table}.{$column}...");
                        try {
                            Schema::table($table, function ($table) use ($column) {
                                $table->index($column);
                            });
                            $this->info("  Index on {$table}.{$column} added successfully!");
                        } catch (\Exception $e) {
                            $this->error("  Failed to add index on {$table}.{$column}: " . $e->getMessage());
                        }
                    }
                } else {
                    $this->line("  In analyze-only mode. Not adding indexes.");
                }
            } else {
                $this->info("  All required indexes exist!");
            }
        }
    }
    
    /**
     * Optimize database tables.
     */
    private function optimizeTables(bool $analyzeOnly): void
    {
        $this->info('Optimizing database tables...');
        
        $tables = ['tracks', 'playlists', 'genres', 'users', 'genre_track', 'playlist_track'];
        
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table {$table} does not exist. Skipping.");
                continue;
            }
            
            $this->info("Optimizing table: {$table}");
            
            if (!$analyzeOnly) {
                try {
                    DB::statement("OPTIMIZE TABLE {$table}");
                    $this->info("  Table {$table} optimized successfully!");
                } catch (\Exception $e) {
                    // SQLite doesn't support OPTIMIZE TABLE
                    if (DB::connection()->getDriverName() === 'sqlite') {
                        try {
                            DB::statement("VACUUM");
                            $this->info("  Vacuumed SQLite database successfully!");
                        } catch (\Exception $e2) {
                            $this->error("  Failed to vacuum SQLite database: " . $e2->getMessage());
                        }
                    } else {
                        $this->error("  Failed to optimize table {$table}: " . $e->getMessage());
                    }
                }
            } else {
                $this->line("  In analyze-only mode. Not optimizing tables.");
            }
        }
    }
    
    /**
     * Simulate common queries to analyze.
     */
    private function simulateCommonQueries(): void
    {
        try {
            // Tracks listing with search
            DB::table('tracks')
                ->where('title', 'like', '%test%')
                ->orWhere('artist', 'like', '%test%')
                ->orWhere('album', 'like', '%test%')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            // Tracks with genre filter
            DB::table('tracks')
                ->join('genre_track', 'tracks.id', '=', 'genre_track.track_id')
                ->where('genre_track.genre_id', 1)
                ->select('tracks.*')
                ->distinct()
                ->orderBy('tracks.created_at', 'desc')
                ->limit(10)
                ->get();
                
            // Playlists with track count
            DB::table('playlists')
                ->selectRaw('playlists.*, COUNT(playlist_track.track_id) as track_count')
                ->leftJoin('playlist_track', 'playlists.id', '=', 'playlist_track.playlist_id')
                ->groupBy('playlists.id')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $this->warn("Error simulating queries: " . $e->getMessage());
        }
    }
    
    /**
     * Extract table name from SQL query.
     */
    private function extractTableFromQuery(string $sql): ?string
    {
        if (preg_match('/from\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Replace placeholders in a query with their bindings.
     */
    private function interpolateQuery(string $query, array $bindings): string
    {
        return array_reduce($bindings, function ($sql, $binding) {
            return preg_replace('/\?/', is_numeric($binding) ? $binding : "'" . $binding . "'", $sql, 1);
        }, $query);
    }
} 