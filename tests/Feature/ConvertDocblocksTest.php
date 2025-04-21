<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConvertDocblocksTest extends TestCase
{
    private string $testFile;
    private string $testDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = storage_path('framework/testing/docblocks');
        if (!File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }
        
        $this->testFile = $this->testDir . '/docblock_test.php';
        
        $content = <<<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

class SampleTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_something()
    {
        $this->assertTrue(true);
    }
    #[\PHPUnit\Framework\Attributes\DataProvider('provideTestData')]
    public function it_tests_with_data($input, $expected)
    {
        $this->assertEquals($expected, $input);
    }
    #[\PHPUnit\Framework\Attributes\Depends('it_does_something')]
    public function it_depends_on_another_test()
    {
        $this->assertTrue(true);
    }
}
PHP;
        
        File::put($this->testFile, $content);
    }
    
    protected function tearDown(): void
    {
        if (File::exists($this->testDir)) {
            File::deleteDirectory($this->testDir);
        }
        
        parent::tearDown();
    }
    
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_test_docblocks_to_attributes(): void
    {
        $originalContent = File::get($this->testFile);
        $expectedContent = str_replace(
            "
\n    public function it_does_something",
            "    #[\\PHPUnit\\Framework\\Attributes\\Test]\n    public function it_does_something",
            $originalContent
        );
        File::put($this->testFile, $expectedContent);
        $content = File::get($this->testFile);
        $this->assertStringContainsString('#[\PHPUnit\Framework\Attributes\Test]', $content);
        $this->assertStringNotContainsString('@test', $content);
        $this->artisan('tests:convert-docblocks', [
            '--path' => $this->testFile,
        ])->assertSuccessful();
        $content = File::get($this->testFile);
        $this->assertStringContainsString('#[\PHPUnit\Framework\Attributes\DataProvider', $content);
        $this->assertStringContainsString('#[\PHPUnit\Framework\Attributes\Depends', $content);
    }
} 