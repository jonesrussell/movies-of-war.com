<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add cache headers for storage files (images, posters, etc.)
        if ($request->is('storage/*')) {
            // Check if it's an image file
            $path = $request->path();
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif', 'svg'])) {
                // Long cache for images (1 year) - filenames include hashes/sizes so they're cacheable
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            }
        }

        return $response;
    }
}
