<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Core\Integration;
use App\Models\Leads\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * LinkedIn Webhook Controller
 *
 * Handles incoming webhooks from LinkedIn Marketing API
 * - Lead Gen Form submissions
 * - Campaign notifications
 * - Ad account updates
 *
 * SECURITY: All webhooks MUST pass signature verification
 *
 * @see https://docs.microsoft.com/en-us/linkedin/marketing/integrations/ads/lead-gen-forms
 */
class LinkedInWebhookController extends Controller
{
    use ApiResponse;

    /**
     * Handle Lead Gen Form submission webhook
     *
     * LinkedIn sends this when a user submits a Lead Gen Form
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleLeadGenForm(Request $request): JsonResponse
    {
        try {
            // Verify LinkedIn signature
            if (!$this->verifyLinkedInSignature($request)) {
                Log::warning('LinkedIn webhook signature verification failed', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);

                return $this->unauthorized('Invalid webhook signature');
            }

            $payload = $request->all();

            Log::info('LinkedIn Lead Gen Form webhook received', [
                'payload' => $payload,
            ]);

            // Extract lead data from webhook
            $leadData = $this->extractLeadData($payload);

            if (empty($leadData)) {
                Log::warning('LinkedIn webhook received but no lead data found', [
                    'payload' => $payload,
                ]);

                return $this->success(null, 'Webhook received but no actionable data');
            }

            // Find integration by form ID or account ID
            $integration = $this->findIntegrationByFormId($leadData['form_id'] ?? null);

            if (!$integration) {
                Log::warning('LinkedIn webhook received but no matching integration found', [
                    'form_id' => $leadData['form_id'] ?? null,
                ]);

                return $this->success(null, 'Webhook received but no matching integration');
            }

            // Initialize RLS context for database operations
            DB::statement(
                'SELECT cmis.init_transaction_context(?, ?)',
                [config('cmis.system_user_id'), $integration->org_id]
            );

            // Create lead in CMIS
            $lead = $this->createLead($integration, $leadData);

            Log::info('LinkedIn Lead Gen Form submission processed', [
                'lead_id' => $lead->id,
                'integration_id' => $integration->integration_id,
                'form_id' => $leadData['form_id'] ?? null,
            ]);

            // Trigger CRM sync if configured
            if (config('services.linkedin.auto_sync_crm')) {
                // Dispatch job to sync lead to CRM
                // SyncLeadToCRMJob::dispatch($lead);
            }

            return $this->success([
                'lead_id' => $lead->id,
                'processed' => true,
            ], 'Lead Gen Form submission processed successfully');

        } catch (\Exception $e) {
            Log::error('LinkedIn webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Return 200 to LinkedIn to prevent retries for processing errors
            return $this->success(null, 'Webhook received');
        }
    }

    /**
     * Handle campaign notification webhooks
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCampaignNotification(Request $request): JsonResponse
    {
        try {
            // Verify LinkedIn signature
            if (!$this->verifyLinkedInSignature($request)) {
                Log::warning('LinkedIn campaign webhook signature verification failed');
                return $this->unauthorized('Invalid webhook signature');
            }

            $payload = $request->all();

            Log::info('LinkedIn campaign notification received', [
                'payload' => $payload,
            ]);

            // TODO: Process campaign notifications
            // - Campaign status changes
            // - Budget alerts
            // - Performance alerts

            return $this->success(null, 'Campaign notification processed');

        } catch (\Exception $e) {
            Log::error('LinkedIn campaign webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->success(null, 'Webhook received');
        }
    }

    /**
     * Verify LinkedIn webhook signature
     *
     * CRITICAL: This prevents unauthorized webhook calls
     *
     * LinkedIn signs webhooks with HMAC-SHA256
     *
     * @param Request $request
     * @return bool
     */
    protected function verifyLinkedInSignature(Request $request): bool
    {
        $signature = $request->header('X-LinkedIn-Signature');

        if (empty($signature)) {
            Log::warning('LinkedIn webhook missing signature header');
            return false;
        }

        $webhookSecret = config('services.linkedin.webhook_secret');

        if (empty($webhookSecret)) {
            Log::error('LinkedIn webhook secret not configured');
            return false;
        }

        // Get raw request body
        $payload = $request->getContent();

        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Constant-time comparison to prevent timing attacks
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('LinkedIn webhook signature mismatch', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Extract lead data from LinkedIn webhook payload
     *
     * @param array $payload
     * @return array
     */
    protected function extractLeadData(array $payload): array
    {
        // LinkedIn Lead Gen Form webhook structure:
        // {
        //   "eventType": "LEAD_GEN_FORM_RESPONSE",
        //   "leadGenFormUrn": "urn:li:leadGenForm:12345",
        //   "leadGenFormResponseUrn": "urn:li:leadGenFormResponse:67890",
        //   "submittedAt": 1620000000000,
        //   "leadData": {
        //     "FIRST_NAME": "John",
        //     "LAST_NAME": "Doe",
        //     "EMAIL": "john@example.com",
        //     ...
        //   }
        // }

        if ($payload['eventType'] !== 'LEAD_GEN_FORM_RESPONSE') {
            return [];
        }

        $leadData = $payload['leadData'] ?? [];

        return [
            'form_id' => $this->extractUrnId($payload['leadGenFormUrn'] ?? ''),
            'response_id' => $this->extractUrnId($payload['leadGenFormResponseUrn'] ?? ''),
            'submitted_at' => isset($payload['submittedAt'])
                ? \Carbon\Carbon::createFromTimestampMs($payload['submittedAt'])
                : now(),
            'first_name' => $leadData['FIRST_NAME'] ?? null,
            'last_name' => $leadData['LAST_NAME'] ?? null,
            'email' => $leadData['EMAIL'] ?? null,
            'phone' => $leadData['PHONE'] ?? null,
            'company' => $leadData['COMPANY'] ?? null,
            'job_title' => $leadData['JOB_TITLE'] ?? null,
            'raw_data' => $leadData,
        ];
    }

    /**
     * Extract numeric ID from LinkedIn URN
     *
     * Example: "urn:li:leadGenForm:12345" => "12345"
     *
     * @param string $urn
     * @return string|null
     */
    protected function extractUrnId(string $urn): ?string
    {
        if (empty($urn)) {
            return null;
        }

        $parts = explode(':', $urn);
        return end($parts) ?: null;
    }

    /**
     * Find integration by Lead Gen Form ID
     *
     * @param string|null $formId
     * @return Integration|null
     */
    protected function findIntegrationByFormId(?string $formId): ?Integration
    {
        if (empty($formId)) {
            return null;
        }

        // Find integration that has this form ID in metadata
        return Integration::where('platform', 'linkedin')
            ->where('is_active', true)
            ->whereRaw("metadata->>'lead_gen_forms' @> ?", [json_encode([$formId])])
            ->first();
    }

    /**
     * Create lead in CMIS system
     *
     * @param Integration $integration
     * @param array $leadData
     * @return Lead
     */
    protected function createLead(Integration $integration, array $leadData): Lead
    {
        // Create lead with RLS context already initialized
        $lead = Lead::create([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'source' => 'linkedin_lead_gen',
            'platform_lead_id' => $leadData['response_id'],
            'first_name' => $leadData['first_name'],
            'last_name' => $leadData['last_name'],
            'email' => $leadData['email'],
            'phone' => $leadData['phone'],
            'company' => $leadData['company'],
            'job_title' => $leadData['job_title'],
            'status' => 'new',
            'submitted_at' => $leadData['submitted_at'],
            'metadata' => [
                'form_id' => $leadData['form_id'],
                'raw_response' => $leadData['raw_data'],
                'webhook_received_at' => now()->toIso8601String(),
            ],
        ]);

        // Fire event for downstream processing
        // event(new LinkedInLeadGenerated($lead));

        return $lead;
    }

    /**
     * Webhook verification endpoint (for LinkedIn to verify webhook URL)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        // LinkedIn sends a challenge parameter for verification
        $challenge = $request->input('challenge');

        if (empty($challenge)) {
            return $this->error('Missing challenge parameter', 400);
        }

        return response()->json([
            'challenge' => $challenge,
        ]);
    }
}
