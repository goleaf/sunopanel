<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make existing columns nullable
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('audio_url')->nullable()->change();
            $table->string('image_url')->nullable()->change();
        });

        // Add new columns as aliases
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('url')->nullable()->after('audio_url');
            $table->string('cover_image')->nullable()->after('image_url');
        });

        // Copy data from old columns to new columns
        DB::statement('UPDATE tracks SET url = audio_url, cover_image = image_url');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the alias columns
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['url', 'cover_image']);
        });

        // Make original columns required again
        Schema::table('tracks', function (Blueprint $table) {
            $table->string('audio_url')->nullable(false)->change();
            $table->string('image_url')->nullable(false)->change();
        });
    }
};
