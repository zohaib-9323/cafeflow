<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Strict CSP in production only: the Vite dev server needs inline/HMR access locally.
        if (app()->isProduction() && ! $request->is('docs/api')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net",
                "img-src 'self' data: blob:",
                "connect-src 'self'",
                "form-action 'self' https://checkout.stripe.com",
                "frame-ancestors 'self'",
                "base-uri 'self'",
            ]));
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
