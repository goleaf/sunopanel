<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

final class GenerateMissingTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:generate-missing {--type=all : Type of tests to generate (all, feature, unit)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test stubs for classes that do not have corresponding tests';

    /**
     * Map of directories to scan and their corresponding test types.
     */
    protected array $directoryMap = [
        'app/Http/Controllers' => 'Feature',
        'app/Http/Requests' => 'Unit',
        'app/Models' => 'Unit',
        'app/Services' => 'Unit',
        'app/Providers' => 'Unit',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scanning for classes without tests...');
        
        $type = $this->option('type');
        $generatedCount = 0;
        
        foreach ($this->directoryMap as $directory => $testType) {
            if ($type !== 'all' && strtolower($type) !== strtolower($testType)) {
                continue;
            }
            
            $this->info("Scanning {$directory}...");
            
            $finder = new Finder();
            $finder->files()->in(base_path($directory))->name('*.php');
            
            foreach ($finder as $file) {
                $className = $this->getClassNameFromFile($file->getRealPath());
                if (!$className) {
                    continue;
                }
                
                $testPath = $this->getTestPath($className, $testType);
                
                if (!File::exists($testPath)) {
                    $this->line("Generating test for: {$className}");
                    $this->generateTestStub($className, $testPath, $testType);
                    $generatedCount++;
                }
            }
        }
        
        if ($generatedCount > 0) {
            $this->info("{$generatedCount} test stubs generated successfully!");
            
            // Run the fix test styles command on the new tests
            $this->call('test:style-fix');
        } else {
            $this->info('No missing tests found.');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get the fully qualified class name from a file.
     */
    protected function getClassNameFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if (preg_match('/namespace\s+([^;]+)/s', $content, $namespaceMatches) && 
            preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*{)/s', $content, $classMatches)) {
            return $namespaceMatches[1] . '\\' . $classMatches[1];
        }
        
        return null;
    }
    
    /**
     * Get the path where the test file should be created.
     */
    protected function getTestPath(string $className, string $testType): string
    {
        $reflection = new ReflectionClass($className);
        $shortName = $reflection->getShortName();
        
        $relativePath = str_replace('App\\', '', $reflection->getNamespaceName());
        $relativePath = str_replace('\\', '/', $relativePath);
        
        return base_path("tests/{$testType}/{$relativePath}/{$shortName}Test.php");
    }
    
    /**
     * Generate a test stub file.
     */
    protected function generateTestStub(string $className, string $testPath, string $testType): void
    {
        $reflection = new ReflectionClass($className);
        $shortName = $reflection->getShortName();
        
        $relativePath = str_replace('App\\', '', $reflection->getNamespaceName());
        $namespacePath = "Tests\\{$testType}\\" . $relativePath;
        
        // Create directory if it doesn't exist
        $directory = dirname($testPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        // Get public methods to test
        $methods = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $className && !$method->isConstructor()) {
                $methods[] = $method->getName();
            }
        }
        
        // Create test methods
        $testMethods = '';
        foreach ($methods as $method) {
            $methodName = Str::camel("test_{$method}");
            $testMethods .= <<<EOT

    #[Test]
    public function {$methodName}(): void
    {
        // Arrange
        
        // Act
        
        // Assert
        \$this->assertTrue(true);
    }

EOT;
        }
        
        $testStub = <<<EOT
<?php

declare(strict_types=1);

namespace {$namespacePath};

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use {$className};

class {$shortName}Test extends TestCase
{
    {$testMethods}
}

EOT;
        
        File::put($testPath, $testStub);
    }
} 