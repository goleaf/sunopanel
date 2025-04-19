<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

final class GenerateTestStubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:generate-stub 
                            {class : The fully qualified class name to generate a test for}
                            {--unit : Generate a unit test instead of a feature test}
                            {--force : Overwrite existing test file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a test stub for a class';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $className = $this->argument('class');
        $isUnit = $this->option('unit');
        $force = $this->option('force');
        
        // Validate the class exists
        if (!class_exists($className)) {
            $this->error("Class {$className} does not exist.");
            return Command::FAILURE;
        }
        
        // Determine test type and directory
        $testType = $isUnit ? 'Unit' : 'Feature';
        $testDir = base_path('tests/' . $testType);
        
        // Create test name and path
        $shortClassName = (new ReflectionClass($className))->getShortName();
        $testClassName = $shortClassName . 'Test';
        
        // Determine namespace and subdirectory based on the class namespace
        $classNamespace = (new ReflectionClass($className))->getNamespaceName();
        $subNamespace = str_replace('App\\', '', $classNamespace);
        $subDir = str_replace('\\', '/', $subNamespace);
        
        if (!empty($subDir)) {
            $testDir .= '/' . $subDir;
            
            // Create directory if it doesn't exist
            if (!File::isDirectory($testDir)) {
                File::makeDirectory($testDir, 0755, true);
                $this->info("Created directory: {$testDir}");
            }
        }
        
        $testPath = $testDir . '/' . $testClassName . '.php';
        
        // Check if test already exists
        if (File::exists($testPath) && !$force) {
            $this->error("Test file already exists: {$testPath}");
            $this->info("Use --force to overwrite the existing test file.");
            return Command::FAILURE;
        }
        
        // Generate test content
        $testContent = $this->generateTestContent($className, $testType, $subNamespace, $shortClassName);
        
        // Write the test file
        File::put($testPath, $testContent);
        $this->info("Generated test file: {$testPath}");
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate the content for the test file.
     *
     * @param string $className The fully qualified class name
     * @param string $testType The test type (Unit or Feature)
     * @param string $subNamespace The sub-namespace
     * @param string $shortClassName The short class name
     * @return string The generated test content
     */
    private function generateTestContent(string $className, string $testType, string $subNamespace, string $shortClassName): string
    {
        // Build the test namespace
        $namespace = 'Tests\\' . $testType;
        if (!empty($subNamespace)) {
            $namespace .= '\\' . $subNamespace;
        }
        
        // Get public methods to test
        $methods = [];
        try {
            $reflectionClass = new ReflectionClass($className);
            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Skip constructor, magic methods, and inherited methods
                if ($method->getDeclaringClass()->getName() !== $className || 
                    $method->isConstructor() || 
                    Str::startsWith($method->getName(), '__')) {
                    continue;
                }
                
                $methods[] = $method->getName();
            }
        } catch (\Exception $e) {
            $this->warn("Could not reflect class methods: " . $e->getMessage());
        }
        
        // Create test method stubs
        $methodTests = '';
        foreach ($methods as $method) {
            $methodTests .= $this->generateTestMethod($method);
        }
        
        // Use appropriate traits based on test type
        $useTraits = $testType === 'Feature' 
            ? "use RefreshDatabase;\n    use WithoutMiddleware;" 
            : "// use HasTestClass;";
        
        $useStatements = $testType === 'Feature' 
            ? "use Illuminate\Foundation\Testing\RefreshDatabase;\nuse Illuminate\Foundation\Testing\WithoutMiddleware;" 
            : "// use Tests\Traits\HasTestClass;";
        
        // Build the full test content
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$className};
use Tests\TestCase;
{$useStatements}

class {$shortClassName}Test extends TestCase
{
    {$useTraits}
    
{$methodTests}
}

PHP;
    }
    
    /**
     * Generate a test method for the given class method.
     *
     * @param string $methodName The method name
     * @return string The generated test method
     */
    private function generateTestMethod(string $methodName): string
    {
        $testMethodName = Str::camel($methodName);
        
        return <<<PHP
    #[Test]
    public function {$testMethodName}(): void
    {
        // Arrange
        
        // Act
        
        // Assert
        \$this->assertTrue(true);
    }

PHP;
    }
} 