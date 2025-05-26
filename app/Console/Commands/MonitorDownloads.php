<?php

namespace App\Console\Commands;

use App\Models\Track;
use App\Jobs\ProcessTrack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MonitorDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:downloads 
                            {--fix : Automatically fix found issues}
                            {--retry-failed : Retry failed tracks}
                            {--restart-stuck : Restart stuck processing tracks}
                            {--check-files : Verify file existence}
                            {--watch : Continuously monitor (runs every 30 seconds)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor download progress and fix issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fix = $this->option('fix');
        $retryFailed = $this->option('retry-failed');
        $restartStuck = $this->option('restart-stuck');
        $checkFiles = $this->option('check-files');
        $watch = $this->option('watch');

        if ($watch) {
            $this->info('👀 Starting continuous monitoring (Ctrl+C to stop)...');
            $this->newLine();
            
            while (true) {
                $this->runMonitoring($fix, $retryFailed, $restartStuck, $checkFiles);
                $this->line('⏰ Waiting 30 seconds before next check...');
                sleep(30);
                $this->newLine();
            }
        } else {
            return $this->runMonitoring($fix, $retryFailed, $restartStuck, $checkFiles);
        }
    }

    /**
     * Run a single monitoring cycle.
     */
    private function runMonitoring(bool $fix, bool $retryFailed, bool $restartStuck, bool $checkFiles): int
    {
        $this->info('🔍 Monitoring download status at ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        $issues = [];
        $fixes = [];

        // 1. Check overall track status
        $this->info('📊 Track Status Overview:');
        $statusCounts = Track::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], collect($statusCounts)->map(function($count, $status) {
            return [$status, $count];
        })->toArray());

        // 2. Check queue status
        $this->info('🔄 Queue Status:');
        $queueJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        $this->table(['Queue Type', 'Count'], [
            ['Pending Jobs', $queueJobs],
            ['Failed Jobs', $failedJobs],
        ]);

        // 3. Check for failed tracks
        $failedTracks = Track::where('status', 'failed')->get();
        if ($failedTracks->count() > 0) {
            $issues[] = "Found {$failedTracks->count()} failed tracks";
            $this->warn("⚠️ Failed Tracks ({$failedTracks->count()}):");
            
            foreach ($failedTracks->take(10) as $track) {
                $this->line("  • ID: {$track->id} | {$track->title} | Error: " . Str::limit($track->error_message, 50));
            }
            
            if ($failedTracks->count() > 10) {
                $this->line("  ... and " . ($failedTracks->count() - 10) . " more");
            }

            if ($retryFailed || $fix) {
                $retryCount = 0;
                foreach ($failedTracks as $track) {
                    if ($this->shouldRetryTrack($track)) {
                        $track->update([
                            'status' => 'pending',
                            'progress' => 0,
                            'error_message' => null,
                        ]);
                        ProcessTrack::dispatch($track);
                        $retryCount++;
                    }
                }
                $fixes[] = "Retried {$retryCount} failed tracks";
            }
        }

        // 4. Check for stuck processing tracks
        $stuckTracks = Track::where('status', 'processing')
            ->where('updated_at', '<', now()->subHours(1))
            ->get();

        if ($stuckTracks->count() > 0) {
            $issues[] = "Found {$stuckTracks->count()} stuck processing tracks";
            $this->warn("⚠️ Stuck Processing Tracks ({$stuckTracks->count()}):");
            
            foreach ($stuckTracks->take(5) as $track) {
                $this->line("  • ID: {$track->id} | {$track->title} | Stuck since: {$track->updated_at->diffForHumans()}");
            }

            if ($restartStuck || $fix) {
                foreach ($stuckTracks as $track) {
                    $track->update([
                        'status' => 'pending',
                        'progress' => 0,
                    ]);
                    ProcessTrack::dispatch($track);
                }
                $fixes[] = "Restarted {$stuckTracks->count()} stuck tracks";
            }
        }

        // 5. Check for tracks without files
        if ($checkFiles) {
            $this->info('📁 Checking file existence...');
            $missingFiles = $this->checkFileExistence($fix);
            if (!empty($missingFiles)) {
                $issues = array_merge($issues, $missingFiles);
            }
        }

        // 6. Check for pending tracks not in queue
        $pendingTracks = Track::where('status', 'pending')->count();
        if ($pendingTracks > 0 && $queueJobs == 0) {
            $issues[] = "Found {$pendingTracks} pending tracks but no queue jobs";
            
            if ($fix) {
                $tracksToQueue = Track::where('status', 'pending')->take(50)->get();
                foreach ($tracksToQueue as $track) {
                    ProcessTrack::dispatch($track);
                }
                $fixes[] = "Queued {$tracksToQueue->count()} pending tracks";
            }
        }

        // 7. Check queue workers
        $this->info('👷 Queue Workers Status:');
        $workers = $this->checkQueueWorkers();
        if (empty($workers)) {
            $issues[] = "No queue workers are running";
            $this->error("❌ No queue workers detected!");
        } else {
            $this->info("✅ Found " . count($workers) . " queue worker(s) running");
            foreach ($workers as $worker) {
                $this->line("  • {$worker}");
            }
        }

        // 8. Check for URL validation issues
        $invalidUrls = Track::where(function($query) {
            $query->where('mp3_url', 'not like', '%suno.ai%')
                  ->orWhere('image_url', 'not like', '%suno.ai%');
        })->count();

        if ($invalidUrls > 0) {
            $issues[] = "Found {$invalidUrls} tracks with invalid URLs";
            if ($fix) {
                Track::where(function($query) {
                    $query->where('mp3_url', 'not like', '%suno.ai%')
                          ->orWhere('image_url', 'not like', '%suno.ai%');
                })->delete();
                $fixes[] = "Deleted {$invalidUrls} tracks with invalid URLs";
            }
        }

        // Summary
        $this->newLine();
        if (empty($issues)) {
            $this->info('✅ No issues found! All downloads are working properly.');
        } else {
            $this->warn('⚠️ Issues Found:');
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
        }

        if (!empty($fixes)) {
            $this->info('🔧 Fixes Applied:');
            foreach ($fixes as $fix) {
                $this->line("  • {$fix}");
            }
        }

        if (!empty($issues) && !$fix && !$retryFailed && !$restartStuck) {
            $this->newLine();
            $this->comment('💡 Use --fix to automatically resolve issues, or use specific flags like --retry-failed, --restart-stuck');
        }

        return Command::SUCCESS;
    }

    /**
     * Check if a track should be retried.
     */
    private function shouldRetryTrack(Track $track): bool
    {
        // Don't retry tracks that failed due to invalid URLs
        if (Str::contains($track->error_message, ['invalid URL', 'not a Suno.ai URL', '404'])) {
            return false;
        }

        // Don't retry tracks that have been retried too many times recently
        if ($track->updated_at > now()->subMinutes(30)) {
            return false;
        }

        return true;
    }

    /**
     * Check file existence for completed tracks.
     */
    private function checkFileExistence(bool $fix): array
    {
        $issues = [];
        
        $completedTracks = Track::where('status', 'completed')
            ->whereNotNull('mp3_path')
            ->whereNotNull('image_path')
            ->whereNotNull('mp4_path')
            ->take(100) // Check a sample to avoid overwhelming
            ->get();

        $missingFiles = 0;
        $tracksToReprocess = [];

        foreach ($completedTracks as $track) {
            $mp3Exists = Storage::disk('public')->exists($track->mp3_path);
            $imageExists = Storage::disk('public')->exists($track->image_path);
            $mp4Exists = Storage::disk('public')->exists($track->mp4_path);

            if (!$mp3Exists || !$imageExists || !$mp4Exists) {
                $missingFiles++;
                $tracksToReprocess[] = $track;
                
                if ($missingFiles <= 5) { // Show details for first 5
                    $missing = [];
                    if (!$mp3Exists) $missing[] = 'MP3';
                    if (!$imageExists) $missing[] = 'Image';
                    if (!$mp4Exists) $missing[] = 'MP4';
                    
                    $this->line("  • ID: {$track->id} | {$track->title} | Missing: " . implode(', ', $missing));
                }
            }
        }

        if ($missingFiles > 0) {
            $issues[] = "Found {$missingFiles} completed tracks with missing files";
            
            if ($fix && !empty($tracksToReprocess)) {
                foreach ($tracksToReprocess as $track) {
                    $track->update([
                        'status' => 'pending',
                        'progress' => 0,
                        'mp3_path' => null,
                        'image_path' => null,
                        'mp4_path' => null,
                    ]);
                    ProcessTrack::dispatch($track);
                }
                $issues[] = "Requeued {$missingFiles} tracks for reprocessing";
            }
        }

        return $issues;
    }

    /**
     * Check if queue workers are running.
     */
    private function checkQueueWorkers(): array
    {
        $workers = [];
        
        // Check for running queue worker processes
        $output = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    // Extract relevant info from ps output
                    if (preg_match('/php artisan queue:work\s+(\w+)/', $line, $matches)) {
                        $workers[] = "Queue worker: {$matches[1]}";
                    }
                }
            }
        }

        return $workers;
    }

    /**
     * Test API connectivity.
     */
    private function testApiConnectivity(): bool
    {
        try {
            $response = Http::timeout(10)->get('https://studio-api.suno.ai/api/feed/', [
                'page' => 0,
                'page_size' => 1,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("API connectivity test failed: " . $e->getMessage());
            return false;
        }
    }
}
