<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Webhook Controller
 *
 * Handles webhooks from WhatsApp Business Platform
 */
class WhatsAppWebhookController extends Controller
{
    use ApiResponse;

    /**
     * Handle WhatsApp webhooks
     *
     * @param Request $request
     * @return JsonResponse|string
     */
    public function handle(Request $request)
    : \Illuminate\Http\JsonResponse {
        // Verify webhook (GET request)
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        // Verify signature for POST requests (CRITICAL SECURITY)
        if (!$this->verifySignature($request)) {
            Log::warning('WhatsApp webhook signature verification failed', [
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);
            return $this->unauthorized('Invalid webhook signature');
        }

        // Handle webhook event (POST request)
        try {
            $data = $request->all();
            Log::info('WhatsApp webhook received', ['data' => $data]);

            foreach ($data['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    if ($change['field'] === 'messages') {
                        $this->processMessage($change['value']);
                    }
                }
            }

            return $this->success(['success' => true], 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error("WhatsApp webhook error: {$e->getMessage()}");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Verify webhook (GET request)
     */
    protected function verify(Request $request)
    : \Illuminate\Http\JsonResponse {
        $mode = $request->input('hub_mode');
        $token = $request->input('hub_verify_token');
        $challenge = $request->input('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.webhook_verify_token')) {
            Log::info('WhatsApp webhook verified');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Process WhatsApp message
     */
    protected function processMessage(array $data): void
    {
        foreach ($data['messages'] ?? [] as $message) {
            $messageId = $message['id'];
            $from = $message['from'];
            $text = $message['text']['body'] ?? '';

            // Find integration
            $phoneNumberId = $data['metadata']['phone_number_id'] ?? null;
            $integration = Integration::where('settings->phone_number_id', $phoneNumberId)
                ->where('platform', 'whatsapp')
                ->where('is_active', true)
                ->first();

            if (!$integration) return;

            // Initialize RLS context for multi-tenancy (CRITICAL)
            DB::statement(
                'SELECT cmis.init_transaction_context(?, ?)',
                [config('cmis.system_user_id'), $integration->org_id]
            );

            // Store message
            DB::table('cmis_social.social_messages')->insert([
                'message_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'whatsapp',
                'platform_message_id' => $messageId,
                'sender_id' => $from,
                'message_text' => $text,
                'direction' => 'inbound',
                'received_at' => now(),
                'created_at' => now(),
            ]);

            Log::info("Stored WhatsApp message {$messageId}");
        }
    }

    /**
     * Verify WhatsApp webhook signature
     *
     * CRITICAL SECURITY: Prevents unauthorized webhook calls
     * Uses same signature method as Meta (HMAC-SHA256)
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (empty($signature)) {
            Log::warning('WhatsApp webhook missing signature header');
            return false;
        }

        $appSecret = config('services.whatsapp.app_secret');

        if (empty($appSecret)) {
            Log::error('WhatsApp app secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }
}
