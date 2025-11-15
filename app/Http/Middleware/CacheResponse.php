<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Cache HTTP responses for GET requests
 */
class CacheResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Don't cache if user is authenticated and wants fresh data
        if ($request->header('Cache-Control') === 'no-cache') {
            return $next($request);
        }

        // Generate cache key based on URL and user
        $cacheKey = $this->getCacheKey($request);

        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            return response($cachedResponse['content'], $cachedResponse['status'])
                ->withHeaders(array_merge($cachedResponse['headers'], [
                    'X-Cache' => 'HIT',
                    'X-Cache-TTL' => $ttl,
                ]));
        }

        // Get fresh response
        $response = $next($request);

        // Cache successful responses only
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $this->getHeadersToCache($response),
            ], $ttl);

            $response->headers->set('X-Cache', 'MISS');
            $response->headers->set('X-Cache-TTL', $ttl);
        }

        return $response;
    }

    /**
     * Generate cache key for request
     */
    private function getCacheKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        $url = $request->fullUrl();
        $queryParams = $request->query();
        ksort($queryParams);

        return 'http_cache:' . md5($userId . ':' . $url . ':' . json_encode($queryParams));
    }

    /**
     * Get headers that should be cached
     */
    private function getHeadersToCache(Response $response): array
    {
        $headers = [];
        $headersToCache = ['Content-Type', 'Content-Language'];

        foreach ($headersToCache as $header) {
            if ($response->headers->has($header)) {
                $headers[$header] = $response->headers->get($header);
            }
        }

        return $headers;
    }
}
