<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateTestStubsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_runs_successfully(): void
    {
        $tempDir = storage_path('framework/testing/generate_stubs');
        $sourceDir = $tempDir . '/app/Services';
        
        if (!File::exists($sourceDir)) {
            File::makeDirectory($sourceDir, 0755, true);
        }
        
        $sourceFile = $sourceDir . '/TestService.php';
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Services;

class TestService
{
    public function test(): bool
    {
        return true;
    }
}
PHP;
        
        File::put($sourceFile, $content);
        
        try {
            $this->artisan('tests:generate-stubs')
                 ->assertExitCode(0);
        } finally {
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }
} 