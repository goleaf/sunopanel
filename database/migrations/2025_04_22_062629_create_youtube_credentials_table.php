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
        Schema::create('youtube_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->bigInteger('token_created_at')->nullable();
            $table->integer('token_expires_in')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->boolean('use_oauth')->default(true);
            $table->string('user_email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_credentials');
    }
};
