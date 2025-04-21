<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class FixRemainingTestsCommand extends Command
{
    protected $signature = 'tests:fix-remaining';
    protected $description = 'Fix remaining test files that have format issues';

    public function handle(): int
    {
        $this->info("Fixing remaining test files with specific format issues");

        // Convert tests in GenresTest.php
        $this->fixGenresTest();
        
        // Convert tests in PlaylistsTest.php
        $this->fixPlaylistsTest();
        
        // Convert tests in TracksTest.php
        $this->fixTracksTest();
        
        $this->info("Fixed remaining test files with format issues");
        
        return Command::SUCCESS;
    }
    
    private function fixGenresTest(): void
    {
        $filepath = base_path('tests/Feature/Livewire/GenresTest.php');
        if (!File::exists($filepath)) {
            $this->warn("File not found: {$filepath}");
            return;
        }
        
        $content = File::get($filepath);
        
        // Make sure we have strict_types declaration
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $content = preg_replace('/<\?php\s+/m', "<?php\n\ndeclare(strict_types=1);\n\n", $content);
        }
        
        // Replace doc-comment test annotations with attributes
        $testMethods = [
            'the_component_can_render',
            'it_can_load_genres',
            'it_can_search_for_genres',
            'it_can_sort_genres',
            'it_can_paginate_genres',
            'it_can_create_a_new_genre',
            'it_validates_genre_name_uniqueness',
            'it_can_update_a_genre',
            'it_can_delete_a_genre',
            'it_can_cancel_genre_deletion',
            'it_cannot_delete_genre_with_tracks',
        ];
        
        foreach ($testMethods as $method) {
            // Add #[Test] attribute and void return type
            $content = preg_replace(
                '/(\s+)(\/\*\*\s*\n\s*\*\s*@test\s*.*\n\s*\*\/\s*\n\s*)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
            
            // If there's no doc-comment but also no attribute
            $content = preg_replace(
                '/(\s+)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
        }
        
        File::put($filepath, $content);
        $this->info("Fixed {$filepath}");
    }
    
    private function fixPlaylistsTest(): void
    {
        $filepath = base_path('tests/Feature/Livewire/PlaylistsTest.php');
        if (!File::exists($filepath)) {
            $this->warn("File not found: {$filepath}");
            return;
        }
        
        $content = File::get($filepath);
        
        // Make sure we have strict_types declaration
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $content = preg_replace('/<\?php\s+/m', "<?php\n\ndeclare(strict_types=1);\n\n", $content);
        }
        
        // Replace doc-comment test annotations with attributes
        $testMethods = [
            'the_component_can_render',
            'it_can_load_playlists',
            'it_can_search_playlists',
            'it_can_filter_playlists_by_user',
            'it_can_sort_playlists',
            'it_can_paginate_playlists',
            'it_can_create_a_new_playlist',
            'it_validates_playlist_name_uniqueness_for_user',
            'it_can_update_a_playlist',
            'it_can_delete_a_playlist',
            'it_can_cancel_playlist_deletion',
            'it_can_add_tracks_to_a_playlist',
            'it_can_remove_tracks_from_a_playlist',
            'it_can_update_track_positions_in_a_playlist',
        ];
        
        foreach ($testMethods as $method) {
            // Add #[Test] attribute and void return type
            $content = preg_replace(
                '/(\s+)(\/\*\*\s*\n\s*\*\s*@test\s*.*\n\s*\*\/\s*\n\s*)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
            
            // If there's no doc-comment but also no attribute
            $content = preg_replace(
                '/(\s+)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
        }
        
        File::put($filepath, $content);
        $this->info("Fixed {$filepath}");
    }
    
    private function fixTracksTest(): void
    {
        $filepath = base_path('tests/Feature/Livewire/TracksTest.php');
        if (!File::exists($filepath)) {
            $this->warn("File not found: {$filepath}");
            return;
        }
        
        $content = File::get($filepath);
        
        // Make sure we have strict_types declaration
        if (!str_contains($content, 'declare(strict_types=1)')) {
            $content = preg_replace('/<\?php\s+/m', "<?php\n\ndeclare(strict_types=1);\n\n", $content);
        }
        
        // Replace doc-comment test annotations with attributes
        $testMethods = [
            'the_component_can_render',
            'it_can_load_tracks',
            'it_can_search_for_tracks',
            'it_can_filter_tracks_by_genre',
            'it_can_sort_tracks',
            'it_can_delete_a_track',
            'it_can_cancel_track_deletion',
            'it_can_process_bulk_import',
            'it_validates_bulk_import_file',
            'it_can_paginate_tracks',
        ];
        
        foreach ($testMethods as $method) {
            // Add #[Test] attribute and void return type
            $content = preg_replace(
                '/(\s+)(\/\*\*\s*\n\s*\*\s*@test\s*.*\n\s*\*\/\s*\n\s*)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
            
            // If there's no doc-comment but also no attribute
            $content = preg_replace(
                '/(\s+)public function ' . $method . '\(\)(?!:)/m',
                '$1#[\PHPUnit\Framework\Attributes\Test]' . "\n" . '$1public function ' . $method . '(): void',
                $content
            );
        }
        
        File::put($filepath, $content);
        $this->info("Fixed {$filepath}");
    }
} 