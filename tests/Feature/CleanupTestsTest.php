<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CleanupTestsTest extends TestCase
{
    private string $testFile;
    private string $testDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary test directory and file
        $this->testDir = storage_path('framework/testing/cleanup');
        if (!File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }
        
        $this->testFile = $this->testDir . '/cleanup_test.php';
        
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Track;  // This import is unused
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth; // This import is unused

class SampleTest extends TestCase
{
    use RefreshDatabase;
    
    // This is a comment that should be removed
    public function test_Something(): void
    {
        $this->assertTrue(true);
        
        // Another comment
        $user = User::factory()->create();
        
        /* This is a multiline comment
           that should be removed */
        $this->actingAs($user);
    }
}
PHP;
        
        File::put($this->testFile, $content);
    }
    
    protected function tearDown(): void
    {
        // Clean up the temporary file and directory
        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        
        parent::tearDown();
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_removes_comments_and_unused_imports(): void
    {
        // Run the command with an absolute path
        $this->artisan('tests:cleanup', [
            '--path' => $this->testFile,
        ])->assertSuccessful();
        
        // Check the updated file content
        $content = File::get($this->testFile);
        
        // Check that unused imports are removed
        $this->assertStringNotContainsString('use App\Models\Track;', $content);
        $this->assertStringNotContainsString('use Illuminate\Support\Facades\Auth;', $content);
        
        // Check that comments are removed
        $this->assertStringNotContainsString('// This is a comment', $content);
        $this->assertStringNotContainsString('// Another comment', $content);
        $this->assertStringNotContainsString('/* This is a multiline comment', $content);
        
        // Check that used imports and actual code remain
        $this->assertStringContainsString('use Tests\TestCase;', $content);
        $this->assertStringContainsString('use App\Models\User;', $content);
        $this->assertStringContainsString('use RefreshDatabase;', $content);
        $this->assertStringContainsString('public function test_Something(): void', $content);
        $this->assertStringContainsString('$this->assertTrue(true);', $content);
        $this->assertStringContainsString('$user = User::factory()->create();', $content);
        $this->assertStringContainsString('$this->actingAs($user);', $content);
    }
} 