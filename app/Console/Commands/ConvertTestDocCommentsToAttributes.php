<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final class ConvertTestDocCommentsToAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:convert-attributes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert PHPUnit doc-comment annotations to PHP 8 attributes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Converting PHPUnit doc-comment annotations to PHP 8 attributes...');

        $testDirs = [
            base_path('tests/Feature'),
            base_path('tests/Unit'),
        ];

        $finder = new Finder();
        $finder->files()->in($testDirs)->name('*.php');

        $convertedCount = 0;

        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);

            // Skip files that don't contain test methods or already use attributes
            if (!preg_match('/@test\b/', $content) || str_contains($content, '#[Test]')) {
                continue;
            }

            $this->line("Processing file: {$path}");

            // Convert @test annotations to #[Test] attributes
            $content = preg_replace_callback(
                '/(\s+)\/\*\*\s*\n\s*\*\s*@test\s*.*?\*\/\s*\n\s*public function (\w+)\(/',
                function ($matches) {
                    return "{$matches[1]}#[Test]\n{$matches[1]}public function {$matches[2]}(";
                },
                $content
            );

            file_put_contents($path, $content);
            $convertedCount++;
        }

        if ($convertedCount > 0) {
            $this->info("Converted {$convertedCount} test files from doc-comments to attributes.");
        } else {
            $this->info('No files needed conversion.');
        }

        return Command::SUCCESS;
    }
} 