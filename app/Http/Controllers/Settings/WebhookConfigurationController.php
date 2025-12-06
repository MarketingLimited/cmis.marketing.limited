<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Webhook\WebhookConfiguration;
use App\Models\Webhook\WebhookDeliveryLog;
use App\Services\Webhook\WebhookForwardingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * WebhookConfigurationController
 *
 * Handles webhook configuration management for organizations.
 * Allows users to configure callback URLs, verify tokens, and
 * subscribe to specific event types.
 */
class WebhookConfigurationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WebhookForwardingService $webhookService
    ) {}

    /**
     * Display webhook configurations index page
     */
    public function index(Request $request, string $org): View
    {
        $webhooks = WebhookConfiguration::where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('settings.webhooks.index', [
            'webhooks' => $webhooks,
            'eventTypes' => WebhookConfiguration::EVENT_TYPES,
            'platforms' => WebhookConfiguration::PLATFORMS,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show create webhook form
     */
    public function create(Request $request, string $org): View
    {
        return view('settings.webhooks.create', [
            'eventTypes' => WebhookConfiguration::EVENT_TYPES,
            'platforms' => WebhookConfiguration::PLATFORMS,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Store a new webhook configuration
     */
    public function store(Request $request, string $org): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'callback_url' => ['required', 'url', 'max:500'],
            'platform' => ['nullable', 'string', Rule::in(array_keys(WebhookConfiguration::PLATFORMS))],
            'subscribed_events' => ['nullable', 'array'],
            'subscribed_events.*' => ['string', Rule::in(array_keys(WebhookConfiguration::EVENT_TYPES))],
            'timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:60'],
            'max_retries' => ['nullable', 'integer', 'min:0', 'max:10'],
            'custom_headers' => ['nullable', 'array'],
        ]);

        $orgId = $org;

        // Check limit (e.g., max 10 webhooks per org)
        $count = WebhookConfiguration::where('org_id', $orgId)->count();
        if ($count >= 10) {
            return $this->error(__('webhooks.max_limit_reached'), 422);
        }

        $webhook = WebhookConfiguration::create([
            'org_id' => $orgId,
            'name' => $validated['name'],
            'callback_url' => $validated['callback_url'],
            'verify_token' => WebhookConfiguration::generateVerifyToken(),
            'secret_key' => WebhookConfiguration::generateSecretKey(),
            'platform' => $validated['platform'] ?? null,
            'subscribed_events' => $validated['subscribed_events'] ?? null,
            'timeout_seconds' => $validated['timeout_seconds'] ?? 30,
            'max_retries' => $validated['max_retries'] ?? 3,
            'custom_headers' => $validated['custom_headers'] ?? null,
            'is_active' => false, // Must verify first
            'is_verified' => false,
            'created_by' => Auth::id(),
        ]);

        Log::info('Webhook configuration created', [
            'webhook_id' => $webhook->id,
            'org_id' => $orgId,
            'name' => $webhook->name,
        ]);

        return $this->created([
            'webhook' => $webhook,
            'verify_token' => $webhook->verify_token,
            'secret_key' => $webhook->secret_key,
        ], __('webhooks.created'));
    }

    /**
     * Show webhook configuration details
     */
    public function show(Request $request, string $org, string $webhook): View
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $recentLogs = $webhookModel->deliveryLogs()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('settings.webhooks.show', [
            'webhook' => $webhookModel,
            'recentLogs' => $recentLogs,
            'eventTypes' => WebhookConfiguration::EVENT_TYPES,
            'platforms' => WebhookConfiguration::PLATFORMS,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show edit webhook form
     */
    public function edit(Request $request, string $org, string $webhook): View
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        return view('settings.webhooks.edit', [
            'webhook' => $webhookModel,
            'eventTypes' => WebhookConfiguration::EVENT_TYPES,
            'platforms' => WebhookConfiguration::PLATFORMS,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Update webhook configuration
     */
    public function update(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'callback_url' => ['sometimes', 'url', 'max:500'],
            'platform' => ['nullable', 'string', Rule::in(array_keys(WebhookConfiguration::PLATFORMS))],
            'subscribed_events' => ['nullable', 'array'],
            'subscribed_events.*' => ['string', Rule::in(array_keys(WebhookConfiguration::EVENT_TYPES))],
            'timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:60'],
            'max_retries' => ['nullable', 'integer', 'min:0', 'max:10'],
            'custom_headers' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // If callback URL changed, require re-verification
        if (isset($validated['callback_url']) && $validated['callback_url'] !== $webhookModel->callback_url) {
            $validated['is_verified'] = false;
            $validated['verified_at'] = null;
            $validated['is_active'] = false;
        }

        // Can only activate if verified
        if (isset($validated['is_active']) && $validated['is_active'] && !$webhookModel->is_verified) {
            return $this->error(__('webhooks.must_verify_first'), 422);
        }

        $validated['updated_by'] = Auth::id();

        $webhookModel->update($validated);

        Log::info('Webhook configuration updated', [
            'webhook_id' => $webhookModel->id,
            'changes' => array_keys($validated),
        ]);

        return $this->success($webhookModel, __('webhooks.updated'));
    }

    /**
     * Delete webhook configuration
     */
    public function destroy(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $webhookModel->delete();

        Log::info('Webhook configuration deleted', [
            'webhook_id' => $webhook,
            'org_id' => $org,
        ]);

        return $this->deleted(__('webhooks.deleted'));
    }

    /**
     * Verify webhook endpoint
     */
    public function verify(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $result = $this->webhookService->verifyEndpoint($webhookModel);

        if ($result['success']) {
            return $this->success([
                'is_verified' => true,
                'verified_at' => $webhookModel->fresh()->verified_at,
            ], $result['message']);
        }

        return $this->error($result['message'], 422);
    }

    /**
     * Test webhook with a sample event
     */
    public function test(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        if (!$webhookModel->is_verified) {
            return $this->error(__('webhooks.must_verify_first'), 422);
        }

        $result = $this->webhookService->testWebhook($webhookModel);

        if ($result['success']) {
            return $this->success([
                'response_time_ms' => $result['response_time_ms'],
            ], $result['message']);
        }

        return $this->error($result['message'], 422);
    }

    /**
     * Regenerate verify token
     */
    public function regenerateVerifyToken(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $newToken = $webhookModel->regenerateVerifyToken();

        Log::info('Webhook verify token regenerated', [
            'webhook_id' => $webhook,
        ]);

        return $this->success([
            'verify_token' => $newToken,
            'is_verified' => false,
        ], __('webhooks.token_regenerated'));
    }

    /**
     * Regenerate secret key
     */
    public function regenerateSecretKey(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $newSecret = $webhookModel->regenerateSecretKey();

        Log::info('Webhook secret key regenerated', [
            'webhook_id' => $webhook,
        ]);

        return $this->success([
            'secret_key' => $newSecret,
        ], __('webhooks.secret_regenerated'));
    }

    /**
     * Toggle webhook active status
     */
    public function toggleActive(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        if (!$webhookModel->is_verified && !$webhookModel->is_active) {
            return $this->error(__('webhooks.must_verify_first'), 422);
        }

        $webhookModel->update([
            'is_active' => !$webhookModel->is_active,
            'updated_by' => Auth::id(),
        ]);

        $message = $webhookModel->is_active
            ? __('webhooks.activated')
            : __('webhooks.deactivated');

        return $this->success([
            'is_active' => $webhookModel->is_active,
        ], $message);
    }

    /**
     * Get delivery logs for a webhook
     */
    public function logs(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $query = $webhookModel->deliveryLogs()
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate($request->input('per_page', 20));

        return $this->paginated($logs, __('webhooks.logs_retrieved'));
    }

    /**
     * Retry a failed delivery
     */
    public function retryDelivery(Request $request, string $org, string $webhook, string $log): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $logModel = WebhookDeliveryLog::where('webhook_config_id', $webhookModel->id)
            ->findOrFail($log);

        if (!$logModel->canRetry()) {
            return $this->error(__('webhooks.cannot_retry'), 422);
        }

        // Queue for retry
        $logModel->update([
            'status' => WebhookDeliveryLog::STATUS_PENDING,
            'next_retry_at' => null,
        ]);

        return $this->success(null, __('webhooks.retry_queued'));
    }

    /**
     * Get webhook statistics
     */
    public function stats(Request $request, string $org, string $webhook): JsonResponse
    {
        $webhookModel = WebhookConfiguration::where('org_id', $org)
            ->findOrFail($webhook);

        $last24h = now()->subHours(24);
        $last7d = now()->subDays(7);

        $stats = [
            'total_deliveries' => $webhookModel->success_count + $webhookModel->failure_count,
            'success_count' => $webhookModel->success_count,
            'failure_count' => $webhookModel->failure_count,
            'success_rate' => $webhookModel->success_rate,
            'last_24h' => [
                'total' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last24h)->count(),
                'success' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last24h)
                    ->where('status', WebhookDeliveryLog::STATUS_SUCCESS)->count(),
                'failed' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last24h)
                    ->where('status', WebhookDeliveryLog::STATUS_FAILED)->count(),
            ],
            'last_7d' => [
                'total' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last7d)->count(),
                'success' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last7d)
                    ->where('status', WebhookDeliveryLog::STATUS_SUCCESS)->count(),
                'failed' => $webhookModel->deliveryLogs()->where('created_at', '>=', $last7d)
                    ->where('status', WebhookDeliveryLog::STATUS_FAILED)->count(),
            ],
            'last_triggered_at' => $webhookModel->last_triggered_at,
            'last_success_at' => $webhookModel->last_success_at,
            'last_failure_at' => $webhookModel->last_failure_at,
            'last_error' => $webhookModel->last_error,
        ];

        return $this->success($stats);
    }
}
