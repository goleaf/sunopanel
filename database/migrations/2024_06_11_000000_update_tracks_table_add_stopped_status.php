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
        // Create a new table with the updated status enum
        Schema::create('tracks_new', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('mp3_url');
            $table->string('image_url');
            $table->string('mp3_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('mp4_path')->nullable();
            $table->text('genres_string')->nullable();
            $table->string('status')->default('pending');
            $table->integer('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Copy the data from the old table to the new one
        DB::statement('INSERT INTO tracks_new SELECT * FROM tracks');

        // Drop the old table
        Schema::drop('tracks');

        // Rename the new table to the original name
        Schema::rename('tracks_new', 'tracks');

        // Recreate the genre_track relationship table
        Schema::table('genre_track', function (Blueprint $table) {
            // Ensure foreign key constraints are correctly set up
            if (!Schema::hasColumn('genre_track', 'track_id')) {
                $table->foreignId('track_id')->constrained()->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to undo this operation as we're just adding a possible value
        // to the status field which is now a string instead of enum in SQLite
    }
}; 