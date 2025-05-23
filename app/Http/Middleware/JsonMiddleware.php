<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JsonMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Set Accept header to application/json for AJAX or JSON requests
        if ($request->isJson() || $request->ajax()) {
            $request->headers->set('Accept', 'application/json');
        }
        
        return $next($request);
    }
} 