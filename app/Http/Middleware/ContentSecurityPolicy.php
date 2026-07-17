<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; "
             . "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net; "
             . "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com; "
             . "img-src 'self' data: https://akrdvoozcqxriiiarmfo.supabase.co https://*.supabase.co; "
             . "connect-src 'self' https://generativelanguage.googleapis.com https://api.groq.com; "
             . "frame-src 'self'; "
             . "object-src 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
