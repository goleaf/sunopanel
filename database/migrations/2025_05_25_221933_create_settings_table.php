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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'youtube_visibility_filter',
                'value' => 'all',
                'type' => 'string',
                'description' => 'Global filter for YouTube upload visibility: all, uploaded, not_uploaded',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'show_youtube_column',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Whether to show YouTube status column in track listings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
