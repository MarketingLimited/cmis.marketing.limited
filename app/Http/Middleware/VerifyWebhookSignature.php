<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    /**
     * Platform-specific webhook secrets
     * Loaded from config/services.php for better configuration management
     *
     * @var array
     */
    private array $secrets = [];

    public function __construct()
    {
        $this->secrets = [
            'meta' => config('services.meta.webhook_secret'),
            'google' => config('services.google.webhook_secret'),
            'tiktok' => config('services.tiktok.webhook_secret'),
            'linkedin' => config('services.linkedin.webhook_secret'),
            'twitter' => config('services.twitter.webhook_secret'),
            'snapchat' => config('services.snapchat.webhook_secret'),
        ];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $platform  The platform name (meta, google, tiktok, etc.)
     */
    public function handle(Request $request, Closure $next, string $platform): Response
    {
        // Get the secret for this platform
        $secret = $this->secrets[$platform] ?? null;

        if (!$secret) {
            Log::error("No webhook secret configured for platform: {$platform}");
            return response()->json(['error' => 'Webhook configuration error'], 500);
        }

        // Verify signature based on platform
        $isValid = match ($platform) {
            'meta' => $this->verifyMetaSignature($request, $secret),
            'google' => $this->verifyGoogleSignature($request, $secret),
            'tiktok' => $this->verifyTikTokSignature($request, $secret),
            'linkedin' => $this->verifyLinkedInSignature($request, $secret),
            'twitter' => $this->verifyTwitterSignature($request, $secret),
            'snapchat' => $this->verifySnapchatSignature($request, $secret),
            default => false,
        };

        if (!$isValid) {
            Log::warning("Invalid webhook signature", [
                'platform' => $platform,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
                'message' => 'Webhook signature verification failed'
            ], 401);
        }

        return $next($request);
    }

    /**
     * Verify Meta (Facebook/Instagram) webhook signature
     * https://developers.facebook.com/docs/graph-api/webhooks/getting-started
     */
    private function verifyMetaSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Google Ads webhook signature
     */
    private function verifyGoogleSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Goog-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify TikTok webhook signature
     * https://business-api.tiktok.com/portal/docs?id=1739584002655234
     */
    private function verifyTikTokSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-TikTok-Signature');

        if (!$signature) {
            return false;
        }

        $timestamp = $request->header('X-TikTok-Timestamp');
        $payload = $request->getContent();

        // TikTok signs: timestamp + payload
        $data = $timestamp . $payload;
        $expectedSignature = hash_hmac('sha256', $data, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify LinkedIn webhook signature
     */
    private function verifyLinkedInSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-LinkedIn-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Twitter/X webhook signature
     * https://developer.twitter.com/en/docs/twitter-api/enterprise/account-activity-api/guides/securing-webhooks
     */
    private function verifyTwitterSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Twitter-Webhooks-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Snapchat webhook signature
     */
    private function verifySnapchatSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-Snap-Signature');

        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
