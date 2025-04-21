<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Before dropping the column, make sure all name values are copied to title if title is empty
        if (Schema::hasColumn('playlists', 'name')) {
            $playlists = DB::table('playlists')
                ->whereNull('title')
                ->whereNotNull('name')
                ->get();

            foreach ($playlists as $playlist) {
                DB::table('playlists')
                    ->where('id', $playlist->id)
                    ->update(['title' => $playlist->name]);
            }

            Schema::table('playlists', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->string('name')->nullable()->after('title');
        });

        // Copy title values to name
        DB::table('playlists')
            ->whereNotNull('title')
            ->update(['name' => DB::raw('title')]);
    }
};
