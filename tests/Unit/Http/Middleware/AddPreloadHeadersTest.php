<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\AddPreloadHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use ReflectionClass;

class AddPreloadHeadersTest extends TestCase
{
    private AddPreloadHeaders $middleware;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AddPreloadHeaders();
    }
    
    public function test_AddsLinkHeadersToHtmlResponses(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('<html><body>Test</body></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
        
        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });
        
        // Verify that Link headers exist
        $linkHeaders = $result->headers->all('link');
        $this->assertNotEmpty($linkHeaders, 'Link headers should be added to HTML responses');
        
        // Convert headers to a string for easier checking
        $allHeaders = implode(' ', $linkHeaders);
        
        // Check headers contain preload and appropriate asset types
        $this->assertStringContainsString('rel=preload', $allHeaders);
    }
    
    public function test_DoesNotAddHeadersToNonHtmlResponses(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('{"data": "test"}', 200, [
            'Content-Type' => 'application/json'
        ]);
        
        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });
        
        // Verify no Link headers were added
        $this->assertEmpty($result->headers->all('link'));
    }
    
    public function test_ContainsAssetTypes(): void
    {
        $request = Request::create('/test', 'GET');
        $response = new Response('<html><body>Test</body></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
        
        $result = $this->middleware->handle($request, function () use ($response) {
            return $response;
        });
        
        // Get Link headers
        $linkHeaders = $result->headers->all('link');
        $allHeaders = implode(' ', $linkHeaders);
        
        // Check that headers contain asset types (script and/or style)
        $hasAssetType = strpos($allHeaders, 'as=script') !== false || 
                       strpos($allHeaders, 'as=style') !== false;
        
        $this->assertTrue($hasAssetType, 'Headers should contain asset type definitions');
    }
} 