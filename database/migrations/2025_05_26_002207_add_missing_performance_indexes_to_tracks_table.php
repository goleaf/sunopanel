<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            // Add only missing indexes (check existing ones first)
            
            // Composite indexes for common query patterns (only if not exists)
            $table->index(['status', 'updated_at'], 'tracks_status_updated_at_index');
            
            // YouTube-related indexes (only missing ones)
            $table->index('youtube_views', 'tracks_youtube_views_index');
            
            // Processing-related indexes
            $table->index('progress', 'tracks_progress_index');
            $table->index(['status', 'progress'], 'tracks_status_progress_index');
            
            // File path indexes for existence checks
            $table->index('mp3_path', 'tracks_mp3_path_index');
            $table->index('mp4_path', 'tracks_mp4_path_index');
            $table->index('image_path', 'tracks_image_path_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            // Drop the indexes we added
            $table->dropIndex('tracks_status_updated_at_index');
            $table->dropIndex('tracks_youtube_views_index');
            $table->dropIndex('tracks_progress_index');
            $table->dropIndex('tracks_status_progress_index');
            $table->dropIndex('tracks_mp3_path_index');
            $table->dropIndex('tracks_mp4_path_index');
            $table->dropIndex('tracks_image_path_index');
        });
    }
};
