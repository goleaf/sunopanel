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
            // YouTube Analytics Fields
            $table->bigInteger('youtube_view_count')->nullable()->after('youtube_uploaded_at');
            $table->bigInteger('youtube_like_count')->nullable()->after('youtube_view_count');
            $table->bigInteger('youtube_dislike_count')->nullable()->after('youtube_like_count');
            $table->bigInteger('youtube_comment_count')->nullable()->after('youtube_dislike_count');
            $table->bigInteger('youtube_favorite_count')->nullable()->after('youtube_comment_count');
            $table->string('youtube_duration')->nullable()->after('youtube_favorite_count');
            $table->string('youtube_definition')->nullable()->after('youtube_duration'); // HD, SD
            $table->string('youtube_caption')->nullable()->after('youtube_definition'); // true, false
            $table->string('youtube_licensed_content')->nullable()->after('youtube_caption'); // true, false
            $table->string('youtube_privacy_status')->nullable()->after('youtube_licensed_content'); // public, unlisted, private
            $table->timestamp('youtube_published_at')->nullable()->after('youtube_privacy_status');
            $table->timestamp('youtube_analytics_updated_at')->nullable()->after('youtube_published_at');
            
            // Add indexes for analytics queries
            $table->index('youtube_view_count');
            $table->index('youtube_like_count');
            $table->index('youtube_analytics_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropIndex(['youtube_view_count']);
            $table->dropIndex(['youtube_like_count']);
            $table->dropIndex(['youtube_analytics_updated_at']);
            
            $table->dropColumn([
                'youtube_view_count',
                'youtube_like_count',
                'youtube_dislike_count',
                'youtube_comment_count',
                'youtube_favorite_count',
                'youtube_duration',
                'youtube_definition',
                'youtube_caption',
                'youtube_licensed_content',
                'youtube_privacy_status',
                'youtube_published_at',
                'youtube_analytics_updated_at',
            ]);
        });
    }
};
