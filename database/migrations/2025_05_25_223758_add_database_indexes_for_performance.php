<?php

declare(strict_types=1);

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
            // Index for status filtering (most common query)
            if (!$this->indexExists('tracks', 'tracks_status_index')) {
                $table->index('status', 'tracks_status_index');
            }
            
            // Index for YouTube video ID filtering (uploaded/not uploaded)
            if (!$this->indexExists('tracks', 'tracks_youtube_video_id_index')) {
                $table->index('youtube_video_id', 'tracks_youtube_video_id_index');
            }
            
            // Index for YouTube enabled filtering
            if (!$this->indexExists('tracks', 'tracks_youtube_enabled_index')) {
                $table->index('youtube_enabled', 'tracks_youtube_enabled_index');
            }
            
            // Index for created_at ordering
            if (!$this->indexExists('tracks', 'tracks_created_at_index')) {
                $table->index('created_at', 'tracks_created_at_index');
            }
            
            // Index for title searching
            if (!$this->indexExists('tracks', 'tracks_title_index')) {
                $table->index('title', 'tracks_title_index');
            }
            
            // Index for YouTube upload date
            if (!$this->indexExists('tracks', 'tracks_youtube_uploaded_at_index')) {
                $table->index('youtube_uploaded_at', 'tracks_youtube_uploaded_at_index');
            }
            
            // Composite index for status + created_at (common sorting pattern)
            if (!$this->indexExists('tracks', 'tracks_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'tracks_status_created_at_index');
            }
            
            // Composite index for YouTube filtering + status
            if (!$this->indexExists('tracks', 'tracks_youtube_status_index')) {
                $table->index(['youtube_video_id', 'status'], 'tracks_youtube_status_index');
            }
            
            // Composite index for YouTube enabled + status
            if (!$this->indexExists('tracks', 'tracks_youtube_enabled_status_index')) {
                $table->index(['youtube_enabled', 'status'], 'tracks_youtube_enabled_status_index');
            }
        });

        Schema::table('genres', function (Blueprint $table) {
            // Index for name searching and ordering
            if (!$this->indexExists('genres', 'genres_name_index')) {
                $table->index('name', 'genres_name_index');
            }
            
            // Index for genre_id if it exists
            if (Schema::hasColumn('genres', 'genre_id')) {
                if (!$this->indexExists('genres', 'genres_genre_id_index')) {
                    $table->index('genre_id', 'genres_genre_id_index');
                }
            }
        });

        Schema::table('genre_track', function (Blueprint $table) {
            // Individual indexes for foreign keys (if not already present)
            try {
                if (!$this->indexExists('genre_track', 'genre_track_genre_id_index')) {
                    $table->index('genre_id', 'genre_track_genre_id_index');
                }
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
            
            try {
                if (!$this->indexExists('genre_track', 'genre_track_track_id_index')) {
                    $table->index('track_id', 'genre_track_track_id_index');
                }
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        });

        // Add indexes to settings table if it exists
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                // Index for key lookups (most common query)
                try {
                    if (!$this->indexExists('settings', 'settings_key_index')) {
                        $table->index('key', 'settings_key_index');
                    }
                } catch (\Exception $e) {
                    // Index might already exist, continue
                }
                
                // Index for type filtering
                try {
                    if (!$this->indexExists('settings', 'settings_type_index')) {
                        $table->index('type', 'settings_type_index');
                    }
                } catch (\Exception $e) {
                    // Index might already exist, continue
                }
            });
        }

        // Add indexes to youtube_accounts table if it exists
        if (Schema::hasTable('youtube_accounts')) {
            Schema::table('youtube_accounts', function (Blueprint $table) {
                // Index for active account lookup
                if (!$this->indexExists('youtube_accounts', 'youtube_accounts_is_active_index')) {
                    $table->index('is_active', 'youtube_accounts_is_active_index');
                }
                
                // Index for last used ordering
                if (!$this->indexExists('youtube_accounts', 'youtube_accounts_last_used_at_index')) {
                    $table->index('last_used_at', 'youtube_accounts_last_used_at_index');
                }
            });
        }

        // Add indexes to youtube_credentials table if it exists
        if (Schema::hasTable('youtube_credentials')) {
            Schema::table('youtube_credentials', function (Blueprint $table) {
                // Index for OAuth usage
                if (!$this->indexExists('youtube_credentials', 'youtube_credentials_use_oauth_index')) {
                    $table->index('use_oauth', 'youtube_credentials_use_oauth_index');
                }
                
                // Index for user email lookup
                if (!$this->indexExists('youtube_credentials', 'youtube_credentials_user_email_index')) {
                    $table->index('user_email', 'youtube_credentials_user_email_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropIndex('tracks_status_index');
            $table->dropIndex('tracks_youtube_video_id_index');
            $table->dropIndex('tracks_youtube_enabled_index');
            $table->dropIndex('tracks_created_at_index');
            $table->dropIndex('tracks_title_index');
            $table->dropIndex('tracks_youtube_uploaded_at_index');
            $table->dropIndex('tracks_status_created_at_index');
            $table->dropIndex('tracks_youtube_status_index');
            $table->dropIndex('tracks_youtube_enabled_status_index');
        });

        Schema::table('genres', function (Blueprint $table) {
            $table->dropIndex('genres_name_index');
            if (Schema::hasColumn('genres', 'genre_id')) {
                $table->dropIndex('genres_genre_id_index');
            }
        });

        Schema::table('genre_track', function (Blueprint $table) {
            if ($this->indexExists('genre_track', 'genre_track_genre_id_index')) {
                $table->dropIndex('genre_track_genre_id_index');
            }
            if ($this->indexExists('genre_track', 'genre_track_track_id_index')) {
                $table->dropIndex('genre_track_track_id_index');
            }
        });

        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if ($this->indexExists('settings', 'settings_key_index')) {
                    $table->dropIndex('settings_key_index');
                }
                $table->dropIndex('settings_type_index');
            });
        }

        if (Schema::hasTable('youtube_accounts')) {
            Schema::table('youtube_accounts', function (Blueprint $table) {
                if ($this->indexExists('youtube_accounts', 'youtube_accounts_is_active_index')) {
                    $table->dropIndex('youtube_accounts_is_active_index');
                }
                $table->dropIndex('youtube_accounts_last_used_at_index');
            });
        }

        if (Schema::hasTable('youtube_credentials')) {
            Schema::table('youtube_credentials', function (Blueprint $table) {
                $table->dropIndex('youtube_credentials_use_oauth_index');
                if ($this->indexExists('youtube_credentials', 'youtube_credentials_user_email_index')) {
                    $table->dropIndex('youtube_credentials_user_email_index');
                }
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            // For SQLite, we'll use a simple try-catch approach
            // since Doctrine schema manager is not available in Laravel 12
            $connection = Schema::getConnection();
            
            if ($connection->getDriverName() === 'sqlite') {
                // For SQLite, check if index exists by querying sqlite_master
                $result = $connection->select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name=? AND tbl_name=?",
                    [$index, $table]
                );
                return count($result) > 0;
            }
            
            // For other databases, we'll assume the index doesn't exist
            // and let the migration handle any errors
            return false;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }
};
