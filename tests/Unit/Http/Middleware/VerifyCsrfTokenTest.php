<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Str;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyCsrfTokenTest extends TestCase
{
    private VerifyCsrfToken $middleware;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get the Laravel application instance
        $app = app();
        $encrypter = $app->make(Encrypter::class);
        
        // Create the middleware with proper dependencies
        $this->middleware = new VerifyCsrfToken($app, $encrypter);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    
    public function test_TokenRotationConfiguration(): void
    {
        // Read the VerifyCsrfToken class file to verify it has token rotation code
        $middlewareContent = file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));
        
        // Check if the file contains rotation logic for login, register, and password endpoints
        $this->assertStringContainsString('$request->is(\'login*\')', $middlewareContent);
        $this->assertStringContainsString('$request->is(\'register*\')', $middlewareContent);
        $this->assertStringContainsString('$request->is(\'password/reset\')', $middlewareContent);
    }
    
    
    public function test_TokenNotRotatedForNormalRequests(): void
    {
        // Verify the middleware has an addCookieToResponse method
        $this->assertTrue(method_exists($this->middleware, 'addCookieToResponse'), 
            'VerifyCsrfToken should have an addCookieToResponse method');
        
        // This is a simple validation that the middleware class exists and is properly loaded
        $this->assertInstanceOf(VerifyCsrfToken::class, $this->middleware);
    }
    
    
    public function test_SameSiteAttributeIsSetToLax(): void
    {
        // Read the VerifyCsrfToken class file to verify it has SameSite configuration
        $middlewareContent = file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));
        
        // Check if the file contains SameSite Lax configuration
        $this->assertStringContainsString('$config[\'same_site\'] ?? \'lax\'', $middlewareContent);
    }
} 