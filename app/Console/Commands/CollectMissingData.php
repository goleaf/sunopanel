<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Models\Genre;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CollectMissingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:missing-data {--fix : Fix found issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect information about missing data and optionally fix issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Collecting missing data and checking system integrity...');
        $this->newLine();

        $issues = [];
        $fixMode = $this->option('fix');

        // Check tracks
        $this->info('📊 Checking tracks...');
        $trackIssues = $this->checkTracks($fixMode);
        $issues = array_merge($issues, $trackIssues);

        // Check genres
        $this->info('🎵 Checking genres...');
        $genreIssues = $this->checkGenres($fixMode);
        $issues = array_merge($issues, $genreIssues);

        // Check settings
        $this->info('⚙️ Checking settings...');
        $settingIssues = $this->checkSettings($fixMode);
        $issues = array_merge($issues, $settingIssues);

        // Check queue system
        $this->info('🔄 Checking queue system...');
        $queueIssues = $this->checkQueueSystem($fixMode);
        $issues = array_merge($issues, $queueIssues);

        // Check file system
        $this->info('📁 Checking file system...');
        $fileIssues = $this->checkFileSystem($fixMode);
        $issues = array_merge($issues, $fileIssues);

        // Summary
        $this->newLine();
        if (empty($issues)) {
            $this->info('✅ No issues found! System is healthy.');
        } else {
            $this->warn('⚠️ Found ' . count($issues) . ' issue(s):');
            foreach ($issues as $issue) {
                $this->line('  • ' . $issue);
            }
            
            if (!$fixMode) {
                $this->newLine();
                $this->comment('💡 Run with --fix to automatically resolve fixable issues');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Check tracks for issues.
     */
    private function checkTracks(bool $fix = false): array
    {
        $issues = [];

        // Check for tracks with invalid URLs
        $invalidUrlTracks = Track::where(function($query) {
            $query->where('mp3_url', 'not like', '%suno.ai%')
                  ->orWhere('image_url', 'not like', '%suno.ai%');
        })->count();

        if ($invalidUrlTracks > 0) {
            $issues[] = "Found {$invalidUrlTracks} tracks with invalid URLs";
            if ($fix) {
                Track::where(function($query) {
                    $query->where('mp3_url', 'not like', '%suno.ai%')
                          ->orWhere('image_url', 'not like', '%suno.ai%');
                })->delete();
                $this->info("  ✅ Deleted {$invalidUrlTracks} tracks with invalid URLs");
            }
        }

        // Check for tracks without suno_id
        $noSunoId = Track::whereNull('suno_id')->count();
        if ($noSunoId > 0) {
            $issues[] = "Found {$noSunoId} tracks without suno_id";
            if ($fix) {
                Track::whereNull('suno_id')->chunk(100, function($tracks) {
                    foreach ($tracks as $track) {
                        // Extract suno_id from URL
                        if (preg_match('/https:\/\/cdn[0-9]?\.suno\.ai\/([a-f0-9-]{36})\./', $track->mp3_url, $matches)) {
                            $track->update(['suno_id' => $matches[1]]);
                        }
                    }
                });
                $this->info("  ✅ Updated suno_id for tracks where possible");
            }
        }

        // Check for tracks with missing genre relationships
        $tracksWithGenreString = Track::whereNotNull('genres_string')
            ->where('genres_string', '!=', '')
            ->whereDoesntHave('genres')
            ->count();

        if ($tracksWithGenreString > 0) {
            $issues[] = "Found {$tracksWithGenreString} tracks with genre strings but no genre relationships";
            if ($fix) {
                // This would require running the genre processing logic
                $this->info("  ⚠️ Genre relationship fixing requires manual processing");
            }
        }

        // Check track status distribution
        $statusCounts = Track::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], collect($statusCounts)->map(function($count, $status) {
            return [$status, $count];
        })->toArray());

        return $issues;
    }

    /**
     * Check genres for issues.
     */
    private function checkGenres(bool $fix = false): array
    {
        $issues = [];

        // Check for duplicate genre names
        $duplicateGenres = Genre::selectRaw('name, COUNT(*) as count')
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        if ($duplicateGenres->count() > 0) {
            $issues[] = "Found {$duplicateGenres->count()} duplicate genre names";
            if ($fix) {
                foreach ($duplicateGenres as $duplicate) {
                    $genres = Genre::where('name', $duplicate->name)->get();
                    $keepGenre = $genres->first();
                    $duplicates = $genres->skip(1);
                    
                    foreach ($duplicates as $duplicateGenre) {
                        // Move track relationships to the kept genre
                        DB::table('track_genre')
                            ->where('genre_id', $duplicateGenre->id)
                            ->update(['genre_id' => $keepGenre->id]);
                        
                        $duplicateGenre->delete();
                    }
                }
                $this->info("  ✅ Merged duplicate genres");
            }
        }

        // Check for genres without tracks
        $emptyGenres = Genre::whereDoesntHave('tracks')->count();
        if ($emptyGenres > 0) {
            $issues[] = "Found {$emptyGenres} genres without any tracks";
            if ($fix) {
                Genre::whereDoesntHave('tracks')->delete();
                $this->info("  ✅ Deleted {$emptyGenres} empty genres");
            }
        }

        return $issues;
    }

    /**
     * Check settings for issues.
     */
    private function checkSettings(bool $fix = false): array
    {
        $issues = [];

        // Check for missing essential settings
        $essentialSettings = [
            'youtube_upload_visibility' => 'public',
            'youtube_column_visible' => true,
            'global_filter' => 'all',
            'tracks_per_page' => 50,
            'auto_process_tracks' => true,
        ];

        foreach ($essentialSettings as $key => $defaultValue) {
            if (!Setting::where('key', $key)->exists()) {
                $issues[] = "Missing essential setting: {$key}";
                if ($fix) {
                    Setting::create([
                        'key' => $key,
                        'value' => is_bool($defaultValue) ? ($defaultValue ? '1' : '0') : (string)$defaultValue,
                        'type' => is_bool($defaultValue) ? 'boolean' : (is_int($defaultValue) ? 'integer' : 'string'),
                    ]);
                    $this->info("  ✅ Created missing setting: {$key}");
                }
            }
        }

        return $issues;
    }

    /**
     * Check queue system for issues.
     */
    private function checkQueueSystem(bool $fix = false): array
    {
        $issues = [];

        // Check for stale jobs
        $staleJobs = DB::table('jobs')->count();
        if ($staleJobs > 0) {
            $issues[] = "Found {$staleJobs} jobs in queue";
            
            // Check if these are for non-existent tracks
            $jobsWithMissingTracks = 0;
            DB::table('jobs')->chunk(100, function($jobs) use (&$jobsWithMissingTracks) {
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    if (isset($payload['data']['command'])) {
                        // Extract track ID from serialized command
                        if (preg_match('/i:(\d+);/', $payload['data']['command'], $matches)) {
                            $trackId = (int)$matches[1];
                            if (!Track::where('id', $trackId)->exists()) {
                                $jobsWithMissingTracks++;
                            }
                        }
                    }
                }
            });

            if ($jobsWithMissingTracks > 0) {
                $issues[] = "Found {$jobsWithMissingTracks} jobs for non-existent tracks";
                if ($fix) {
                    // Clear all jobs for safety since we cleaned up the tracks
                    DB::table('jobs')->truncate();
                    $this->info("  ✅ Cleared all stale jobs");
                }
            }
        }

        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 0) {
            $issues[] = "Found {$failedJobs} failed jobs";
            if ($fix) {
                DB::table('failed_jobs')->truncate();
                $this->info("  ✅ Cleared {$failedJobs} failed jobs");
            }
        }

        return $issues;
    }

    /**
     * Check file system for issues.
     */
    private function checkFileSystem(bool $fix = false): array
    {
        $issues = [];

        // Check storage directories
        $requiredDirs = ['mp3', 'images', 'videos'];
        foreach ($requiredDirs as $dir) {
            if (!Storage::disk('public')->exists($dir)) {
                $issues[] = "Missing storage directory: {$dir}";
                if ($fix) {
                    Storage::disk('public')->makeDirectory($dir);
                    $this->info("  ✅ Created directory: {$dir}");
                }
            }
        }

        // Check for orphaned files (files not referenced by any track)
        $this->info('  📁 Checking for orphaned files...');
        
        $referencedFiles = Track::whereNotNull('mp3_path')
            ->orWhereNotNull('image_path')
            ->orWhereNotNull('mp4_path')
            ->get(['mp3_path', 'image_path', 'mp4_path'])
            ->flatMap(function($track) {
                return array_filter([$track->mp3_path, $track->image_path, $track->mp4_path]);
            })
            ->unique()
            ->values();

        $orphanedCount = 0;
        foreach ($requiredDirs as $dir) {
            $files = Storage::disk('public')->files($dir);
            foreach ($files as $file) {
                if (!$referencedFiles->contains($file)) {
                    $orphanedCount++;
                    if ($fix && $orphanedCount <= 10) { // Limit to prevent accidental mass deletion
                        Storage::disk('public')->delete($file);
                    }
                }
            }
        }

        if ($orphanedCount > 0) {
            $issues[] = "Found {$orphanedCount} orphaned files";
            if ($fix && $orphanedCount <= 10) {
                $this->info("  ✅ Deleted {$orphanedCount} orphaned files");
            } elseif ($orphanedCount > 10) {
                $this->warn("  ⚠️ Too many orphaned files ({$orphanedCount}) - manual review recommended");
            }
        }

        return $issues;
    }
}
