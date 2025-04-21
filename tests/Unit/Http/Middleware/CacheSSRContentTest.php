<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CacheSSRContent;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CacheSSRContentTest extends TestCase
{
    private CacheSSRContent $middleware;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CacheSSRContent();
        Cache::flush();
    }
    
    
    public function test_NonGetRequestsAreNotCached(): void
    {
        $request = $this->createRequest('POST', 'dashboard');
        $response = $this->runMiddleware($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-SSR-Cache'));
    }
    
    
    public function test_AuthenticatedRequestsAreNotCached(): void
    {
        $request = $this->createRequest('GET', 'dashboard');
        $request->setUserResolver(fn() => (object)['id' => 1]);
        $response = $this->runMiddleware($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-SSR-Cache'));
    }
    
    
    public function test_NonCachableRoutesAreNotCached(): void
    {
        $request = $this->createRequest('GET', 'some-other-route');
        $response = $this->runMiddleware($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-SSR-Cache'));
    }
    
    
    public function test_CachableRoutesAreCached(): void
    {
        $request = $this->createRequest('GET', 'dashboard');
        $response = $this->runMiddleware($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('MISS', $response->headers->get('X-SSR-Cache'));
        
        // Second request should be a cache hit
        $secondResponse = $this->runMiddleware($request);
        $this->assertEquals('HIT', $secondResponse->headers->get('X-SSR-Cache'));
    }
    
    private function createRequest(string $method, string $routeName): Request
    {
        $request = Request::create('/test', $method);
        $route = new Route([$method], '/test', ['as' => $routeName]);
        $request->setRouteResolver(fn() => $route);
        return $request;
    }
    
    private function runMiddleware(Request $request): Response
    {
        return $this->middleware->handle($request, function () {
            return new Response('content', 200, ['Content-Type' => 'text/html']);
        });
    }
} 