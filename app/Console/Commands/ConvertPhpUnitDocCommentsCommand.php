<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class ConvertPhpUnitDocCommentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:convert-comments {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert PHPUnit doc-comments to attributes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning test files for PHPUnit doc-comments...');
        
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made');
        }
        
        $finder = new Finder();
        $finder->files()
            ->in(base_path('tests'))
            ->name('*.php')
            ->contains('/@test\b/');
        
        $count = 0;
        
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $content = File::get($path);
            
            // Process each file
            $newContent = $this->processFile($content);
            
            if ($content !== $newContent) {
                $count++;
                $this->line("Found doc-comments in: {$path}");
                
                if (!$dryRun) {
                    File::put($path, $newContent);
                    $this->info("Updated: {$path}");
                }
            }
        }
        
        if ($count === 0) {
            $this->info('No PHPUnit doc-comments found that need conversion.');
        } else {
            $this->info("Found {$count} files with PHPUnit doc-comments.");
            if (!$dryRun) {
                $this->info('All files have been updated successfully.');
            } else {
                $this->warn('Run the command without --dry-run to apply changes.');
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Process a file to convert PHPUnit doc-comments to attributes.
     *
     * @param string $content The file content
     * @return string The processed content
     */
    private function processFile(string $content): string
    {
        // Regex to find test methods with doc-comments
        $pattern = '/(\/\*\*[\s\*]+@test[\s\*].*?\*\/)\s+public\s+function\s+(\w+)\(/s';
        
        return preg_replace_callback(
            $pattern,
            function ($matches) {
                // Extract additional flags like @depends if present
                $docBlock = $matches[1];
                $additionalAttributes = [];
                
                if (preg_match('/@depends\s+(\w+)/', $docBlock, $dependsMatch)) {
                    $additionalAttributes[] = "depends('{$dependsMatch[1]}')";
                }
                
                if (preg_match('/@dataProvider\s+(\w+)/', $docBlock, $dataProviderMatch)) {
                    $additionalAttributes[] = "dataProvider('{$dataProviderMatch[1]}')";
                }
                
                if (preg_match('/@group\s+(\w+)/', $docBlock, $groupMatch)) {
                    $additionalAttributes[] = "group('{$groupMatch[1]}')";
                }
                
                // Create the attribute string
                $attributes = '#[Test';
                if (!empty($additionalAttributes)) {
                    $attributes .= ', ' . implode(', ', $additionalAttributes);
                }
                $attributes .= ']';
                
                // Return the new method declaration with attributes
                return "{$attributes}\npublic function {$matches[2]}(";
            },
            $content
        );
    }
} 