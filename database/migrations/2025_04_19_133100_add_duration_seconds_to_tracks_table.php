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
        Schema::table('tracks', function (Blueprint $table) {
            $table->integer('duration_seconds')->nullable()->after('duration')
                ->comment('Duration in seconds for more precise calculations');
        });

        // Update existing records
        $tracks = DB::table('tracks')->get();
        foreach ($tracks as $track) {
            if (! empty($track->duration)) {
                $durationSeconds = parseDuration($track->duration);

                DB::table('tracks')
                    ->where('id', $track->id)
                    ->update(['duration_seconds' => $durationSeconds]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }
};
