<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivateResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Public storage assets explicitly opt out of the web middleware group.
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
