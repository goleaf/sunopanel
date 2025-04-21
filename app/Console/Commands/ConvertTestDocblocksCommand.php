<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class ConvertTestDocblocksCommand extends Command
{
    protected $signature = 'tests:convert-docblocks {--path=tests : The path to search for test files}';
    protected $description = 'Convert PHPUnit docblocks to PHP 8 attributes in test files';

    public function handle(): int
    {
        $path = $this->option('path');
        $this->info("Converting PHPUnit docblocks to attributes in {$path}");

        $files = $this->getPhpFiles($path);
        
        $convertedCount = 0;
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            
            // Convert @test annotations to #[Test] attributes
            $content = preg_replace(
                '/\/\*\*\s*\n\s*\*\s*@test\s*.*\n\s*\*\/\s*\n\s*public function\s+(\w+)/m',
                '#[\PHPUnit\Framework\Attributes\Test]' . "\n    public function $1",
                $content
            );
            
            // Convert @depends annotations to #[Depends] attributes
            $content = preg_replace(
                '/\/\*\*\s*\n\s*\*\s*@depends\s+(\w+).*\n\s*\*\/\s*\n\s*public function\s+(\w+)/m',
                '#[\PHPUnit\Framework\Attributes\Depends(\'' . "$1')]" . "\n    public function $2",
                $content
            );
            
            // Convert @dataProvider annotations to #[DataProvider] attributes
            $content = preg_replace(
                '/\/\*\*\s*\n\s*\*\s*@dataProvider\s+(\w+).*\n\s*\*\/\s*\n\s*public function\s+(\w+)/m',
                '#[\PHPUnit\Framework\Attributes\DataProvider(\'' . "$1')]" . "\n    public function $2",
                $content
            );
            
            // Convert test_ prefixed methods to attributes if not already attributed
            $content = preg_replace(
                '/(public function test_\w+\(.*\))/m',
                '#[\PHPUnit\Framework\Attributes\Test]' . "\n    $1",
                $content
            );
            
            // Only write if changes were made
            if ($content !== $originalContent) {
                File::put($file, $content);
                $this->info("Converted {$file}");
                $convertedCount++;
            }
        }
        
        $this->info("Converted {$convertedCount} test files.");
        
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