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
            // Composite indexes for common query patterns
            $table->index(['status', 'created_at'], 'tracks_status_created_at_index');
            $table->index(['status', 'updated_at'], 'tracks_status_updated_at_index');
            $table->index(['youtube_video_id', 'status'], 'tracks_youtube_status_index');
            
            // YouTube-related indexes
            $table->index('youtube_uploaded_at', 'tracks_youtube_uploaded_at_index');
            $table->index('youtube_views', 'tracks_youtube_views_index');
            $table->index('youtube_analytics_updated_at', 'tracks_youtube_analytics_updated_at_index');
            
            // Processing-related indexes
            $table->index('progress', 'tracks_progress_index');
            $table->index(['status', 'progress'], 'tracks_status_progress_index');
            
            // File path indexes for existence checks
            $table->index('mp3_path', 'tracks_mp3_path_index');
            $table->index('mp4_path', 'tracks_mp4_path_index');
            $table->index('image_path', 'tracks_image_path_index');
            
            // Search optimization
            $table->fullText('title', 'tracks_title_fulltext_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            // Drop composite indexes
            $table->dropIndex('tracks_status_created_at_index');
            $table->dropIndex('tracks_status_updated_at_index');
            $table->dropIndex('tracks_youtube_status_index');
            
            // Drop YouTube-related indexes
            $table->dropIndex('tracks_youtube_uploaded_at_index');
            $table->dropIndex('tracks_youtube_views_index');
            $table->dropIndex('tracks_youtube_analytics_updated_at_index');
            
            // Drop processing-related indexes
            $table->dropIndex('tracks_progress_index');
            $table->dropIndex('tracks_status_progress_index');
            
            // Drop file path indexes
            $table->dropIndex('tracks_mp3_path_index');
            $table->dropIndex('tracks_mp4_path_index');
            $table->dropIndex('tracks_image_path_index');
            
            // Drop fulltext index
            $table->dropFullText('tracks_title_fulltext_index');
        });
    }
};
