<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

final class ConvertTestDocCommentsToAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:convert-docblocks
                            {--path= : Path to convert test docblocks (default: tests/)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert PHPUnit doc-comments to attributes in test files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('path') ?: 'tests/';
        
        // Check if the path is absolute
        $fullPath = str_starts_with($path, '/') ? $path : base_path($path);
        
        $this->info("Converting PHPUnit doc-comments to attributes in {$path}...");

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

        $this->info("Converted {$count} test files.");
        return Command::SUCCESS;
    }

    protected function processFile(string $filePath): bool
    {
        $content = File::get($filePath);
        $updated = $this->convertDocBlocksToAttributes($content);
        
        if ($content !== $updated) {
            File::put($filePath, $updated);
            $relativePath = str_replace(base_path() . '/', '', $filePath);
            $this->line("Updated: " . $relativePath);
            return true;
        }
        
        return false;
    }

    protected function convertDocBlocksToAttributes(string $content): string
    {
        // Convert @test docblocks to #[Test] attributes
        $content = preg_replace(
            '/\s+\/\*\*\s+\*\s+@test\s+.*?\*\/\s+public function (\w+)\(/s',
            "\n    #[\\PHPUnit\\Framework\\Attributes\\Test]\n    public function $1(",
            $content
        );

        // Convert @dataProvider docblocks to #[DataProvider] attributes
        $content = preg_replace(
            '/\s+\/\*\*\s+\*\s+@dataProvider\s+(\w+)\s+.*?\*\/\s+public function (\w+)\(/s',
            "\n    #[\\PHPUnit\\Framework\\Attributes\\DataProvider('$1')]\n    public function $2(",
            $content
        );

        // Convert @depends docblocks to #[Depends] attributes
        $content = preg_replace(
            '/\s+\/\*\*\s+\*\s+@depends\s+(\w+)\s+.*?\*\/\s+public function (\w+)\(/s',
            "\n    #[\\PHPUnit\\Framework\\Attributes\\Depends('$1')]\n    public function $2(",
            $content
        );

        return $content;
    }
} 