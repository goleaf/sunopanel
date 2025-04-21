<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class ConvertLivewireTestsCommand extends Command
{
    protected $signature = 'tests:convert-livewire {--path=tests/Feature/Livewire : The path to search for Livewire test files}';
    protected $description = 'Convert Livewire test method annotations to attributes';

    public function handle(): int
    {
        $path = $this->option('path');
        $this->info("Converting Livewire test methods to use attributes in {$path}");

        $files = $this->getPhpFiles($path);
        
        $convertedCount = 0;
        
        foreach ($files as $file) {
            $content = File::get($file);
            $originalContent = $content;
            
            // Convert docblock test methods to attribute
            $content = preg_replace(
                '/(\s+)\/\*\*\s*\n\s*\*\s*@test\s*.*\n\s*\*\/\s*\n\s*public function\s+(\w+)\(\)\s*(?!:)\s*\{/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function $2(): void {',
                $content
            );
            
            // Add return type to all test methods if they don't have it
            $content = preg_replace(
                '/public function\s+(\w+)\(\)\s*(?!:)\s*\{/m',
                'public function $1(): void {',
                $content
            );
            
            // Add strict_types declaration if missing
            if (!str_contains($content, 'declare(strict_types=1)')) {
                $content = preg_replace(
                    '/<\?php\s+/m',
                    "<?php\n\ndeclare(strict_types=1);\n\n",
                    $content
                );
            }
            
            // Only write if changes were made
            if ($content !== $originalContent) {
                File::put($file, $content);
                $this->info("Converted {$file}");
                $convertedCount++;
            }
        }
        
        $this->info("Converted {$convertedCount} Livewire test files.");
        
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