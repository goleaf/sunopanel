<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    protected function addCookieToResponse($request, $response)
    {
        // Call parent implementation
        $response = parent::addCookieToResponse($request, $response);
        
        // Rotate CSRF token after login, registration, and password changes
        if (
            $request->is('login*') && $request->isMethod('post') ||
            $request->is('register*') && $request->isMethod('post') ||
            $request->is('password/email') && $request->isMethod('post') ||
            $request->is('password/reset') && $request->isMethod('post')
        ) {
            $this->rotate();
        }
        
        // Set SameSite attribute to Lax for better security
        $config = config('session');
        $cookie = $response->headers->getCookies()[0] ?? null;
        
        if (!is_null($cookie)) {
            $response->headers->setCookie(
                new \Symfony\Component\HttpFoundation\Cookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpiresTime(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $config['secure'] ?? false,
                    $config['http_only'] ?? true,
                    false,
                    $config['same_site'] ?? 'lax'
                )
            );
        }
        
        return $response;
    }
} 