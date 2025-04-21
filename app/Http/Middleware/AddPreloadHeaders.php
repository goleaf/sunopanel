<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddPreloadHeaders
{
    /**
     * Critical assets that should be preloaded for better performance.
     */
    protected array $preloadAssets = [
        '/vendor/livewire/livewire.js' => 'script',
        '/build/assets/app.css' => 'style',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only add preload headers for HTML responses
        if (!$response instanceof Response || !$this->isHtmlResponse($response)) {
            return $response;
        }
        
        // Add Link headers for preloading critical assets
        foreach ($this->preloadAssets as $asset => $type) {
            $header = "<{$asset}>; rel=preload; as={$type}";
            
            // Add CORS attribute if needed
            if ($this->isCrossOrigin($asset)) {
                $header .= '; crossorigin';
            }
            
            // Add Link header
            $response->headers->add(['Link' => $header]);
        }
        
        return $response;
    }
    
    /**
     * Determine if the response is an HTML response.
     */
    protected function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        return $contentType && strpos($contentType, 'text/html') !== false;
    }
    
    /**
     * Determine if the asset is from a different origin and requires crossorigin attribute.
     */
    protected function isCrossOrigin(string $asset): bool
    {
        // Check if the asset is an external URL
        return filter_var($asset, FILTER_VALIDATE_URL) && 
               parse_url($asset, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST);
    }
} 