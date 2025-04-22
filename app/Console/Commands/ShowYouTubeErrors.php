<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ShowYouTubeErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youtube:errors {--lines=50 : Number of lines to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show recent YouTube-related errors from the log files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->error("Log file not found: {$logPath}");
            return 1;
        }
        
        $lines = (int) $this->option('lines');
        if ($lines <= 0) {
            $lines = 50;
        }
        
        $this->info("Searching for YouTube-related errors in log files...");
        
        // First, try to use grep if available
        $output = [];
        $returnCode = -1;
        
        exec("which grep", $output, $returnCode);
        if ($returnCode === 0) {
            $command = "grep -i -A 3 -B 1 'youtube\\|upload\\|error' {$logPath} | tail -n {$lines}";
            exec($command, $output, $returnCode);
            
            if (!empty($output)) {
                $this->info("Found " . count($output) . " matching lines:");
                $this->line("");
                foreach ($output as $line) {
                    if (stripos($line, 'error') !== false) {
                        $this->error($line);
                    } elseif (stripos($line, 'warning') !== false) {
                        $this->warn($line);
                    } elseif (stripos($line, 'info') !== false) {
                        $this->info($line);
                    } else {
                        $this->line($line);
                    }
                }
                return 0;
            }
        }
        
        // Fallback to PHP implementation if grep is not available or found no results
        if (empty($output)) {
            $this->info("Using PHP to search for YouTube errors...");
            
            $log = File::get($logPath);
            $logLines = explode("\n", $log);
            $totalLines = count($logLines);
            
            $matchingLines = [];
            for ($i = 0; $i < $totalLines; $i++) {
                $line = $logLines[$i];
                if (
                    stripos($line, 'youtube') !== false ||
                    stripos($line, 'upload') !== false ||
                    stripos($line, 'error') !== false
                ) {
                    // Add context: 1 line before and 3 lines after
                    if ($i > 0) {
                        $matchingLines[] = $logLines[$i - 1];
                    }
                    
                    $matchingLines[] = $line;
                    
                    for ($j = 1; $j <= 3 && $i + $j < $totalLines; $j++) {
                        $matchingLines[] = $logLines[$i + $j];
                    }
                    
                    $matchingLines[] = "--------------------";
                }
            }
            
            // Show only the last $lines lines
            $matchingLines = array_slice($matchingLines, -$lines);
            
            if (!empty($matchingLines)) {
                $this->info("Found " . count($matchingLines) . " matching lines:");
                $this->line("");
                foreach ($matchingLines as $line) {
                    if (stripos($line, 'error') !== false) {
                        $this->error($line);
                    } elseif (stripos($line, 'warning') !== false) {
                        $this->warn($line);
                    } elseif (stripos($line, 'info') !== false) {
                        $this->info($line);
                    } else {
                        $this->line($line);
                    }
                }
            } else {
                $this->warn("No YouTube-related errors found in the logs.");
            }
        }
        
        return 0;
    }
} 