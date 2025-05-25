<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class CleanupMissingFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tracks:cleanup-missing-files 
                            {--dry-run : Show what would be cleaned up without actually doing it}
                            {--fix-paths : Try to fix file paths by searching for files}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up tracks with missing files or fix file paths';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fixPaths = $this->option('fix-paths');

        $this->info('Checking for tracks with missing files...');

        $tracks = Track::whereNotNull('mp4_path')->get();
        $missingFiles = [];
        $fixedFiles = [];

        foreach ($tracks as $track) {
            $filePath = $track->mp4_file_path;
            
            if (!$filePath || !file_exists($filePath)) {
                $missingFiles[] = [
                    'id' => $track->id,
                    'title' => $track->title,
                    'mp4_path' => $track->mp4_path,
                    'expected_path' => $filePath,
                ];

                // Try to fix the path if requested
                if ($fixPaths && $track->mp4_path) {
                    $filename = basename($track->mp4_path);
                    $foundPath = $this->findFileInStorage($filename);
                    
                    if ($foundPath) {
                        $this->info("Found file for track {$track->id}: {$foundPath}");
                        
                        if (!$dryRun) {
                            $track->update(['mp4_path' => $foundPath]);
                            $fixedFiles[] = $track->id;
                        }
                    }
                }
            }
        }

        if (empty($missingFiles)) {
            $this->info('✅ No tracks with missing files found!');
            return 0;
        }

        $this->warn("Found " . count($missingFiles) . " tracks with missing files:");

        $this->table(
            ['ID', 'Title', 'Database Path', 'Expected Full Path'],
            array_map(function ($item) {
                return [
                    $item['id'],
                    substr($item['title'], 0, 30) . (strlen($item['title']) > 30 ? '...' : ''),
                    $item['mp4_path'],
                    substr($item['expected_path'] ?? 'null', -50),
                ];
            }, $missingFiles)
        );

        if ($fixPaths && !empty($fixedFiles)) {
            $this->info("✅ Fixed paths for " . count($fixedFiles) . " tracks");
        }

        if (!$dryRun && !$fixPaths) {
            if ($this->confirm('Do you want to clear the mp4_path for tracks with missing files?')) {
                $clearedCount = 0;
                foreach ($missingFiles as $missing) {
                    $track = Track::find($missing['id']);
                    if ($track) {
                        $track->update(['mp4_path' => null]);
                        $clearedCount++;
                    }
                }
                $this->info("✅ Cleared mp4_path for {$clearedCount} tracks");
            }
        }

        if ($dryRun) {
            $this->info('This was a dry run. Use --fix-paths to attempt path fixes or run without --dry-run to clear paths.');
        }

        return 0;
    }

    /**
     * Find a file in the storage directory.
     */
    private function findFileInStorage(string $filename): ?string
    {
        $directories = ['videos', 'mp4', 'temp'];
        
        foreach ($directories as $dir) {
            $path = "public/{$dir}/{$filename}";
            if (Storage::exists($path)) {
                return "{$dir}/{$filename}";
            }
        }

        return null;
    }
} 