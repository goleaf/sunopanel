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
            if (Schema::hasColumn('tracks', 'url')) {
                $table->dropColumn('url');
            }
            
            if (Schema::hasColumn('tracks', 'cover_image')) {
                $table->dropColumn('cover_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('tracks', 'url')) {
                $table->string('url')->nullable()->after('audio_url');
            }
            
            if (!Schema::hasColumn('tracks', 'cover_image')) {
                $table->string('cover_image')->nullable()->after('image_url');
            }
        });
    }
}; 