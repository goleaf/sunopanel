<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportSunoAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:suno-all 
                            {--sources=discover,search : Comma-separated list of sources (discover,search,json)}
                            {--json-file= : Path to JSON file (required if json source is included)}
                            {--json-url= : URL to JSON data (alternative to json-file)}
                            {--discover-pages=1 : Number of pages to fetch from discover API}
                            {--discover-size=20 : Page size for discover API}
                            {--search-pages=1 : Number of pages to fetch from search API}
                            {--search-size=20 : Page size for search API}
                            {--search-term= : Search term for search API (empty for all)}
                            {--search-rank=most_relevant : Ranking for search API}
                            {--process : Automatically start processing imported tracks}
                            {--dry-run : Preview import without creating tracks}
                            {--session-id= : Session ID for progress tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tracks from multiple Suno sources (discover API, search API, JSON files)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sources = array_map('trim', explode(',', $this->option('sources')));
        $dryRun = $this->option('dry-run');
        $autoProcess = $this->option('process');
        $sessionId = $this->option('session-id');

        $this->info("Starting multi-source Suno import");
        $this->info("Sources: " . implode(', ', $sources));
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tracks will be created');
        }

        $this->updateProgress($sessionId, 10, 'Starting unified import...');

        $totalImported = 0;
        $totalFailed = 0;
        $totalSources = count($sources);

        try {
            foreach ($sources as $index => $source) {
                $this->newLine();
                $this->info("=== Processing source: {$source} ===");
                
                $sourceProgress = 10 + (($index / $totalSources) * 80);
                $this->updateProgress($sessionId, (int)$sourceProgress, "Processing source: {$source}...", 'running', $totalImported, $totalFailed);
                
                $result = $this->processSource($source, $dryRun, $autoProcess, $sessionId);
                
                if ($result['success']) {
                    $totalImported += $result['imported'];
                    $this->info("✅ {$source}: Imported {$result['imported']} tracks");
                } else {
                    $totalFailed++;
                    $this->error("❌ {$source}: Failed - {$result['error']}");
                }
            }

            $this->newLine();
            $this->info("=== SUMMARY ===");
            if ($dryRun) {
                $this->info("DRY RUN: Would import {$totalImported} tracks total");
                $this->updateProgress($sessionId, 100, "Dry run completed: Would import {$totalImported} tracks", 'completed', $totalImported, $totalFailed);
            } else {
                $this->info("Total imported: {$totalImported} tracks");
                $this->info("Failed sources: {$totalFailed}");
                $this->updateProgress($sessionId, 100, "Unified import completed: {$totalImported} tracks imported", 'completed', $totalImported, $totalFailed);
            }
            
            return $totalFailed > 0 ? 1 : 0;
        } catch (Exception $e) {
            $this->error("Failed to complete multi-source import: " . $e->getMessage());
            $this->updateProgress($sessionId, 100, 'Import failed: ' . $e->getMessage(), 'failed', $totalImported, $totalFailed);
            Log::error("Failed to complete multi-source Suno import", [
                'sources' => $sources,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Process a single source.
     *
     * @param string $source
     * @param bool $dryRun
     * @param bool $autoProcess
     * @param string|null $sessionId
     * @return array
     */
    protected function processSource(string $source, bool $dryRun, bool $autoProcess, ?string $sessionId = null): array
    {
        try {
            switch ($source) {
                case 'discover':
                    return $this->processDiscoverSource($dryRun, $autoProcess, $sessionId);
                
                case 'search':
                    return $this->processSearchSource($dryRun, $autoProcess, $sessionId);
                
                case 'json':
                    return $this->processJsonSource($dryRun, $autoProcess, $sessionId);
                
                default:
                    return [
                        'success' => false,
                        'imported' => 0,
                        'error' => "Unknown source: {$source}"
                    ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'imported' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process discover source.
     *
     * @param bool $dryRun
     * @param bool $autoProcess
     * @return array
     */
    protected function processDiscoverSource(bool $dryRun, bool $autoProcess): array
    {
        $pages = (int) $this->option('discover-pages');
        $size = (int) $this->option('discover-size');

        $command = [
            '--pages' => $pages,
            '--page-size' => $size,
        ];

        if ($dryRun) {
            $command['--dry-run'] = true;
        }

        if ($autoProcess) {
            $command['--process'] = true;
        }

        $exitCode = Artisan::call('import:suno-discover', $command);
        
        if ($exitCode === 0) {
            // Parse output to get imported count (this is a simplified approach)
            $output = Artisan::output();
            preg_match('/Imported: (\d+)/', $output, $matches);
            $imported = isset($matches[1]) ? (int) $matches[1] : 0;
            
            return [
                'success' => true,
                'imported' => $imported,
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'imported' => 0,
                'error' => 'Command failed with exit code: ' . $exitCode
            ];
        }
    }

    /**
     * Process search source.
     *
     * @param bool $dryRun
     * @param bool $autoProcess
     * @return array
     */
    protected function processSearchSource(bool $dryRun, bool $autoProcess): array
    {
        $pages = (int) $this->option('search-pages');
        $size = (int) $this->option('search-size');
        $term = $this->option('search-term') ?? '';
        $rank = $this->option('search-rank');

        $command = [
            '--pages' => $pages,
            '--size' => $size,
            '--rank-by' => $rank,
        ];

        if (!empty($term)) {
            $command['--term'] = $term;
        }

        if ($dryRun) {
            $command['--dry-run'] = true;
        }

        if ($autoProcess) {
            $command['--process'] = true;
        }

        $exitCode = Artisan::call('import:suno-search', $command);
        
        if ($exitCode === 0) {
            // Parse output to get imported count
            $output = Artisan::output();
            preg_match('/Imported: (\d+)/', $output, $matches);
            $imported = isset($matches[1]) ? (int) $matches[1] : 0;
            
            return [
                'success' => true,
                'imported' => $imported,
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'imported' => 0,
                'error' => 'Command failed with exit code: ' . $exitCode
            ];
        }
    }

    /**
     * Process JSON source.
     *
     * @param bool $dryRun
     * @param bool $autoProcess
     * @return array
     */
    protected function processJsonSource(bool $dryRun, bool $autoProcess): array
    {
        $jsonFile = $this->option('json-file');
        $jsonUrl = $this->option('json-url');

        if (empty($jsonFile) && empty($jsonUrl)) {
            return [
                'success' => false,
                'imported' => 0,
                'error' => 'JSON source requires either --json-file or --json-url option'
            ];
        }

        $source = $jsonFile ?: $jsonUrl;

        $command = [
            'source' => $source,
            '--format' => 'pipe',
        ];

        if ($dryRun) {
            $command['--dry-run'] = true;
        }

        if ($autoProcess) {
            $command['--process'] = true;
        }

        $exitCode = Artisan::call('import:json', $command);
        
        if ($exitCode === 0) {
            // Parse output to get imported count
            $output = Artisan::output();
            preg_match('/Successfully imported (\d+)/', $output, $matches);
            $imported = isset($matches[1]) ? (int) $matches[1] : 0;
            
            return [
                'success' => true,
                'imported' => $imported,
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'imported' => 0,
                'error' => 'Command failed with exit code: ' . $exitCode
            ];
        }
    }
} 