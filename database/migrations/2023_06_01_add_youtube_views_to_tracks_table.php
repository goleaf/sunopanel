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
            $table->unsignedBigInteger('youtube_views')->nullable()->after('youtube_uploaded_at');
            $table->timestamp('youtube_stats_updated_at')->nullable()->after('youtube_views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['youtube_views', 'youtube_stats_updated_at']);
        });
    }
}; 