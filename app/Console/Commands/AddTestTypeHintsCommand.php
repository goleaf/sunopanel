<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class AddTestTypeHintsCommand extends Command
{
    protected $signature = 'tests:add-types {--path=tests : The path to search for test files}';
    protected $description = 'Add type hints and return types to test methods';

    public function handle(): int
    {
        $path = $this->option('path');
        $this->info("Adding type hints and return types to test methods in {$path}");

        $files = $this->getPhpFiles($path);
        
        $updatedCount = 0;
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            
            // Add declare(strict_types=1) if missing
            if (!str_contains($content, 'declare(strict_types=1)')) {
                $content = preg_replace(
                    '/<\?php\s+/m',
                    "<?php\n\ndeclare(strict_types=1);\n\n",
                    $content
                );
            }
            
            // Add void return type to test methods if missing
            $content = preg_replace(
                '/public function (test\w+|\w+Test)\s*\([^)]*\)\s*(?!:)/m',
                'public function $1($2): void ',
                $content
            );
            
            // Also add void return type to setUp and tearDown methods if missing
            $content = preg_replace(
                '/protected function (setUp|tearDown)\(\)\s*(?!:)/m',
                'protected function $1(): void ',
                $content
            );
            
            // Only write if changes were made
            if ($content !== $originalContent) {
                File::put($file, $content);
                $this->info("Updated {$file}");
                $updatedCount++;
            }
        }
        
        $this->info("Updated {$updatedCount} test files.");
        
        return Command::SUCCESS;
    }
    
    private function getPhpFiles(string $path): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name('*Test.php');
            
        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }
        
        return $files;
    }
} 