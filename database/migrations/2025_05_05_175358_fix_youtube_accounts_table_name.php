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
        // Rename table if it exists with the wrong name
        if (Schema::hasTable('you_tube_accounts') && !Schema::hasTable('youtube_accounts')) {
            Schema::rename('you_tube_accounts', 'youtube_accounts');
        } else if (!Schema::hasTable('youtube_accounts')) {
            // Create the table if it doesn't exist
            Schema::create('youtube_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Display name for the account');
                $table->string('email')->nullable()->comment('Email associated with this account');
                $table->string('channel_id')->nullable()->comment('YouTube channel ID');
                $table->string('channel_name')->nullable()->comment('YouTube channel name');
                $table->string('access_token', 2048)->nullable()->comment('OAuth access token');
                $table->string('refresh_token', 2048)->nullable()->comment('OAuth refresh token');
                $table->timestamp('token_expires_at')->nullable()->comment('When the access token expires');
                $table->json('account_info')->nullable()->comment('Additional account information');
                $table->boolean('is_active')->default(false)->comment('Whether this is the currently active account');
                $table->timestamp('last_used_at')->nullable()->comment('When this account was last used');
                $table->timestamps();
                
                // Indexes
                $table->index('is_active');
                $table->unique('email');
                $table->unique('channel_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to do anything here, as we don't want to drop the table
    }
};
