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
        // Only modify the existing table if it exists but doesn't have the position column
        if (Schema::hasTable('playlist_track') && !Schema::hasColumn('playlist_track', 'position')) {
            Schema::table('playlist_track', function (Blueprint $table) {
                $table->integer('position')->default(0)->after('track_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('playlist_track') && Schema::hasColumn('playlist_track', 'position')) {
            Schema::table('playlist_track', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }
}; 