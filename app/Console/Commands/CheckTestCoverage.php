<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class CheckTestCoverage extends Command
{
    protected $signature = 'tests:coverage 
                            {--path= : Path to check test coverage for (default: app/)}
                            {--exclude= : Comma-separated list of directories to exclude}';
    
    protected $description = 'Check test coverage of application code';

    // Mapping of source directories to test directories
    protected array $directoryMap = [
        'app/Http/Controllers' => 'tests/Feature/Http/Controllers',
        'app/Http/Requests' => 'tests/Unit/Http/Requests',
        'app/Models' => 'tests/Unit/Models',
        'app/Providers' => 'tests/Unit/Providers',
        'app/Services' => 'tests/Unit/Services',
    ];

    public function handle(): int
    {
        $path = $this->option('path') ?: 'app/';
        $exclude = $this->parseExcludeOption();
        
        $this->info("Checking test coverage for classes in {$path}...");

        $sourceFiles = $this->getSourceFiles($path, $exclude);
        $untested = [];
        $tested = [];

        foreach ($sourceFiles as $file) {
            $sourceClass = $this->getFullyQualifiedClassName($file);
            if (!$sourceClass || $this->shouldSkipClass($sourceClass)) {
                continue;
            }

            $testPath = $this->getTestFilePath($file);
            if (!$testPath) {
                continue;
            }

            if (File::exists($testPath)) {
                $tested[] = [
                    'class' => $sourceClass,
                    'test' => $testPath,
                ];
            } else {
                $untested[] = [
                    'class' => $sourceClass,
                    'path' => $file->getPathname(),
                ];
            }
        }

        $this->displayResults($tested, $untested);
        return Command::SUCCESS;
    }

    protected function parseExcludeOption(): array
    {
        $exclude = $this->option('exclude');
        return $exclude ? explode(',', $exclude) : [];
    }

    protected function getSourceFiles(string $path, array $exclude): array
    {
        $files = File::allFiles(base_path($path));
        
        return array_filter($files, function (SplFileInfo $file) use ($exclude) {
            if ($file->getExtension() !== 'php') {
                return false;
            }
            
            foreach ($exclude as $excludePath) {
                if (Str::startsWith($file->getRelativePath(), $excludePath)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    protected function getFullyQualifiedClassName(SplFileInfo $file): ?string
    {
        $content = $file->getContents();
        
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? null;
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? null;
        
        if (!$namespace || !$className) {
            return null;
        }
        
        return "{$namespace}\\{$className}";
    }

    protected function shouldSkipClass(string $className): bool
    {
        // Skip abstract classes, interfaces and traits
        if (!class_exists($className)) {
            return true;
        }
        
        $reflection = new \ReflectionClass($className);
        return $reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait();
    }

    protected function getTestFilePath(SplFileInfo $file): ?string
    {
        $relativePath = $file->getRelativePath();
        $filename = $file->getFilename();
        $testFilename = str_replace('.php', 'Test.php', $filename);
        
        foreach ($this->directoryMap as $sourceDir => $testDir) {
            if (Str::startsWith($relativePath, $sourceDir)) {
                $remainingPath = Str::after($relativePath, $sourceDir);
                $testPath = $testDir . $remainingPath;
                return base_path("{$testPath}/{$testFilename}");
            }
        }
        
        return null;
    }

    protected function displayResults(array $tested, array $untested): void
    {
        $totalClasses = count($tested) + count($untested);
        $coverage = $totalClasses > 0 ? round(count($tested) / $totalClasses * 100, 2) : 0;
        
        $this->info("Test Coverage: {$coverage}% ({$totalClasses} classes, " . count($tested) . " tested, " . count($untested) . " untested)");
        
        if (!empty($untested)) {
            $this->table(
                ['Class', 'Path'],
                array_map(function ($item) {
                    return [
                        $item['class'],
                        $item['path']
                    ];
                }, $untested)
            );
            
            $this->info("Hint: Run 'php artisan tests:generate-stubs' to create test stubs for these classes.");
        }
    }
} 