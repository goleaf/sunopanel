<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite we need to create a new table without the slug column and copy data
        // This is because SQLite doesn't support dropping columns directly
        
        // Only proceed if slug column exists
        if (Schema::hasColumn('tracks', 'slug')) {
            // Get all column names except 'slug'
            $columns = collect(DB::select('PRAGMA table_info(tracks)'))
                ->pluck('name')
                ->filter(function ($column) {
                    return $column !== 'slug';
                })
                ->implode(', ');
            
            // Create new table without slug
            DB::statement("CREATE TABLE tracks_new AS SELECT id, {$columns} FROM tracks");
            
            // Drop old table
            DB::statement('DROP TABLE tracks');
            
            // Rename new table to tracks
            DB::statement('ALTER TABLE tracks_new RENAME TO tracks');
            
            // Recreate any indexes and primary keys
            DB::statement('CREATE INDEX tracks_suno_id_index ON tracks (suno_id)');
            DB::statement('CREATE UNIQUE INDEX tracks_id_unique ON tracks (id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Since we're rebuilding the table structure, a down migration would be complex
        // and potentially destructive. We'll leave it as a no-op.
        // If the slug column is needed again, a separate migration should be created.
    }
};
