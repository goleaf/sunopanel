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
            $table->string('youtube_video_id')->nullable()->after('mp4_path');
            $table->string('youtube_playlist_id')->nullable()->after('youtube_video_id');
            $table->timestamp('youtube_uploaded_at')->nullable()->after('youtube_playlist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn('youtube_video_id');
            $table->dropColumn('youtube_playlist_id');
            $table->dropColumn('youtube_uploaded_at');
        });
    }
}; 