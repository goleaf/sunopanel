<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

final class OptimizeDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database tables and add missing indexes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database optimization...');

        // Add missing indexes
        $this->addMissingIndexes();

        // Optimize tables based on database driver
        if ($this->shouldOptimizeTables()) {
            $this->optimizeTables();
        }

        $this->info('Database optimization completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Determine if table optimization should be run.
     */
    private function shouldOptimizeTables(): bool
    {
        $driver = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Only MySQL/MariaDB support OPTIMIZE TABLE
        if ($driver !== 'mysql') {
            $this->warn("Table optimization not supported for {$driver} driver. Skipping...");

            return false;
        }

        return true;
    }

    /**
     * Add missing indexes to database tables.
     */
    private function addMissingIndexes(): void
    {
        $this->info('Adding missing indexes...');

        // Tracks table indexes
        $this->addIndexIfNotExists('tracks', 'title', 'tracks_title_index');
        $this->addIndexIfNotExists('tracks', 'unique_id', 'tracks_unique_id_index');

        // Genres table indexes
        $this->addIndexIfNotExists('genres', 'name', 'genres_name_index');
        $this->addIndexIfNotExists('genres', 'slug', 'genres_slug_index');

        // Playlists table indexes
        $this->addIndexIfNotExists('playlists', 'title', 'playlists_title_index');

        // Pivot table indexes (if not already automatically added)
        $this->addIndexIfNotExists('genre_track', 'track_id', 'genre_track_track_id_index');
        $this->addIndexIfNotExists('playlist_track', 'track_id', 'playlist_track_track_id_index');

        $this->info('All missing indexes have been added.');
    }

    /**
     * Add an index to a table if it doesn't already exist.
     *
     * @param  string  $table  The table name
     * @param  string  $column  The column name
     * @param  string  $indexName  The index name
     */
    private function addIndexIfNotExists(string $table, string $column, string $indexName): void
    {
        try {
            if (Schema::hasTable($table)) {
                // Different methods to check if index exists based on driver
                $driver = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

                $hasIndex = false;

                if ($driver === 'mysql') {
                    // For MySQL, use information_schema
                    $hasIndex = DB::select(
                        'SELECT COUNT(*) as count FROM information_schema.statistics 
                        WHERE table_schema = DATABASE() 
                        AND table_name = ? 
                        AND index_name = ?',
                        [$table, $indexName]
                    )[0]->count > 0;
                } elseif ($driver === 'sqlite') {
                    // For SQLite, use pragma
                    $indexes = DB::select("PRAGMA index_list($table)");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            $hasIndex = true;
                            break;
                        }
                    }
                } else {
                    // Fall back to Laravel's Schema::hasIndex
                    $hasIndex = Schema::hasIndex($table, $indexName);
                }

                if (! $hasIndex) {
                    $this->info("Adding index on {$table}.{$column}");
                    Schema::table($table, function ($table) use ($column) {
                        $table->index($column);
                    });
                } else {
                    $this->line("Index on {$table}.{$column} already exists.");
                }
            }
        } catch (Exception $e) {
            $this->warn("Error adding index on {$table}.{$column}: {$e->getMessage()}");
        }
    }

    /**
     * Optimize database tables (MySQL specific).
     */
    private function optimizeTables(): void
    {
        $this->info('Optimizing database tables...');

        try {
            // Get all tables for MySQL
            $tables = DB::select('SHOW TABLES');

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                $this->info("Optimizing table: {$tableName}");

                // Run optimize on the table
                DB::statement("OPTIMIZE TABLE {$tableName}");
            }

            $this->info('All tables have been optimized.');
        } catch (Exception $e) {
            $this->error("Error optimizing tables: {$e->getMessage()}");
        }
    }
}
