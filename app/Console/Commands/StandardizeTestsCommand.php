<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class StandardizeTestsCommand extends Command
{
    protected $signature = 'tests:standardize {--path=tests : The path to search for test files}';
    protected $description = 'Standardize test naming conventions and organization';

    public function handle(): int
    {
        $path = $this->option('path');
        $this->info("Standardizing tests in {$path}");

        $files = $this->getPhpFiles($path);
        
        $updatedCount = 0;
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            
            // Replace test method names: testSomething() -> test_something()
            $content = preg_replace(
                '/public function test([A-Z]\w+)\(\)/m',
                'public function test_' . strtolower('$1') . '()',
                $content
            );
            
            // Convert camelCase test names to snake_case (test_someThing -> test_some_thing)
            $content = preg_replace_callback(
                '/public function (test_[a-z]+[A-Z]\w*)\(\)/m',
                function ($matches) {
                    return 'public function ' . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $matches[1])) . '()';
                },
                $content
            );
            
            // Ensure test methods have the void return type
            $content = preg_replace(
                '/public function (test_\w+)\(\)(?!:)/m',
                'public function $1(): void',
                $content
            );
            
            // Only write if changes were made
            if ($content !== $originalContent) {
                File::put($file, $content);
                $this->info("Standardized {$file}");
                $updatedCount++;
            }
        }
        
        $this->info("Standardized {$updatedCount} test files.");
        
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