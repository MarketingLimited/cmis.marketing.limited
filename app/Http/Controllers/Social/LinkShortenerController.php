<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class LinkShortenerController extends Controller
{
    use ApiResponse;

    /**
     * Shorten a URL using Bit.ly API (or TinyURL as fallback)
     */
    public function shorten(Request $request, $orgId)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        try {
            $longUrl = $validated['url'];

            // Cache shortened URLs for 24 hours
            $cacheKey = 'short_url_' . md5($longUrl);

            $shortUrl = Cache::remember($cacheKey, 86400, function () use ($longUrl) {
                // Try Bit.ly first
                $bitlyToken = config('services.bitly.access_token');

                if ($bitlyToken) {
                    return $this->shortenWithBitly($longUrl, $bitlyToken);
                }

                // Fallback to TinyURL (no API key required)
                return $this->shortenWithTinyURL($longUrl);
            });

            return $this->success([
                'original_url' => $longUrl,
                'short_url' => $shortUrl,
            ], 'URL shortened successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to shorten URL: ' . $e->getMessage());
        }
    }

    /**
     * Shorten URL using Bit.ly API
     */
    private function shortenWithBitly($url, $token)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post('https://api-ssl.bitly.com/v4/shorten', [
            'long_url' => $url,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Bit.ly API request failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['link'] ?? $url;
    }

    /**
     * Shorten URL using TinyURL (free, no API key required)
     */
    private function shortenWithTinyURL($url)
    {
        $response = Http::get('https://tinyurl.com/api-create.php', [
            'url' => $url,
        ]);

        if (!$response->successful()) {
            // Return original URL if shortening fails
            return $url;
        }

        return $response->body();
    }

    /**
     * Get link statistics (if using Bit.ly)
     */
    public function stats(Request $request, $orgId, $shortUrl)
    {
        try {
            $bitlyToken = config('services.bitly.access_token');

            if (!$bitlyToken) {
                return $this->error('Link statistics not available (Bit.ly not configured)');
            }

            // Extract Bit.ly link ID from short URL
            $linkId = str_replace(['https://bit.ly/', 'http://bit.ly/'], '', $shortUrl);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $bitlyToken,
            ])->get("https://api-ssl.bitly.com/v4/bitlinks/{$linkId}/clicks/summary");

            if (!$response->successful()) {
                return $this->error('Failed to get link statistics');
            }

            $data = $response->json();

            return $this->success([
                'total_clicks' => $data['total_clicks'] ?? 0,
                'link' => $shortUrl,
            ], 'Link statistics retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get link statistics: ' . $e->getMessage());
        }
    }
}
