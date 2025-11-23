<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * TikTok Webhook Controller
 *
 * Handles webhooks from TikTok For Business
 */
class TikTokWebhookController extends Controller
{
    use ApiResponse;

    /**
     * Handle TikTok webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            Log::info('TikTok webhook received', ['data' => $data]);

            // Verify signature
            $signature = $request->header('X-TikTok-Signature');
            if (!$this->verifySignature($request->getContent(), $signature)) {
                return $this->unauthorized('Invalid signature');
            }

            // Process event
            $event = $data['event'] ?? null;
            switch ($event) {
                case 'comment':
                    $this->processComment($data);
                    break;
                case 'video_update':
                    $this->processVideoUpdate($data);
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error("TikTok webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Process TikTok comment
     */
    protected function processComment(array $data): void
    {
        Log::info("TikTok comment event received", $data);
        // Implementation for TikTok comments
    }

    /**
     * Process TikTok video update
     */
    protected function processVideoUpdate(array $data): void
    {
        Log::info("TikTok video update event received", $data);
        // Implementation for TikTok video updates
    }

    /**
     * Verify TikTok webhook signature
     */
    protected function verifySignature(string $payload, ?string $signature): bool
    {
        if (!$signature) return false;

        $secret = config('services.tiktok.client_secret');
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
