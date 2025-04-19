<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

class CleanupTests extends Command
{
    protected $signature = 'tests:cleanup
                            {--path= : Path to clean up test files (default: tests/)}';
    
    protected $description = 'Clean up test files by removing commented code and unnecessary imports';

    public function handle(): int
    {
        $path = $this->option('path') ?: 'tests/';
        
        // Check if the path is absolute
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);
        
        $this->info("Cleaning up test files in {$path}...");

        if (!File::exists($fullPath)) {
            $this->error("The path {$fullPath} does not exist.");
            return Command::FAILURE;
        }
        
        if (File::isFile($fullPath)) {
            $this->processFile($fullPath);
            return Command::SUCCESS;
        }

        $testFiles = File::allFiles($fullPath);
        $testFiles = array_filter($testFiles, function (SplFileInfo $file) {
            return $file->getExtension() === 'php';
        });

        $count = 0;
        foreach ($testFiles as $file) {
            $updated = $this->processFile($file->getPathname());
            if ($updated) {
                $count++;
            }
        }

        $this->info("Cleaned up {$count} test files.");
        return Command::SUCCESS;
    }

    protected function processFile(string $filePath): bool
    {
        $content = File::get($filePath);
        $updated = $this->cleanupTestFile($content);
        
        if ($content !== $updated) {
            File::put($filePath, $updated);
            $relativePath = str_replace(base_path() . '/', '', $filePath);
            $this->line("Cleaned: " . $relativePath);
            return true;
        }
        
        return false;
    }

    protected function cleanupTestFile(string $content): string
    {
        // Remove commented out code
        $content = preg_replace('/\s*\/\/\s*.*?(\n|$)/m', "\n", $content);
        $content = preg_replace('/\s*\/\*.*?\*\//s', "\n", $content);
        
        // Remove empty lines - but keep at most 2 consecutive empty lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Remove unused imports
        $content = $this->removeUnusedImports($content);
        
        return $content;
    }

    protected function removeUnusedImports(string $content): string
    {
        // Extract all imports
        preg_match_all('/use\s+([^;]+);/', $content, $matches);
        
        if (empty($matches[1])) {
            return $content;
        }
        
        foreach ($matches[1] as $import) {
            $className = class_basename($import);
            
            // Check if class is used in the content excluding the import statement itself
            $importStatement = "use {$import};";
            $contentWithoutImport = str_replace($importStatement, '', $content);
            
            // If the class name doesn't appear in the content, remove the import
            if (!preg_match('/\b' . preg_quote($className, '/') . '\b/', $contentWithoutImport)) {
                $content = str_replace($importStatement . "\n", "", $content);
            }
        }
        
        return $content;
    }
} 