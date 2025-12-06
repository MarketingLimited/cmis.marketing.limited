<?php

namespace App\Jobs\Platform;

use App\Jobs\Webhooks\ProcessWebhookJob;
use App\Models\Platform\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessWebhookEventsJob
 *
 * Processes unprocessed webhook events stored in the database.
 * This job provides reliable webhook processing with:
 * - Retry logic with exponential backoff
 * - Event deduplication
 * - Audit trail via WebhookEvent records
 *
 * Runs every minute to pick up new events.
 */
class ProcessWebhookEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum events to process per execution
     */
    public int $limit;

    /**
     * Platform to process (null = all platforms)
     */
    public ?string $platform;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 50, ?string $platform = null)
    {
        $this->limit = $limit;
        $this->platform = $platform;
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("ProcessWebhookEventsJob: Starting", [
            'limit' => $this->limit,
            'platform' => $this->platform ?? 'all',
        ]);

        $query = WebhookEvent::unprocessed()
            ->orderBy('received_at', 'asc')
            ->limit($this->limit);

        if ($this->platform) {
            $query->forPlatform($this->platform);
        }

        $events = $query->get();

        if ($events->isEmpty()) {
            Log::debug("ProcessWebhookEventsJob: No unprocessed events");
            return;
        }

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                $processed++;

                // Check for duplicates
                if ($this->isDuplicate($event)) {
                    $event->markDuplicate($this->findOriginalEventId($event));
                    continue;
                }

                // Mark as processing
                $event->markProcessing();

                // Process based on platform
                $result = $this->processEvent($event);

                if ($result['success']) {
                    $event->markProcessed(
                        $result['org_id'] ?? null,
                        $result['connection_id'] ?? null
                    );
                    $succeeded++;
                } else {
                    $event->markFailed(
                        $result['error'] ?? 'Processing failed',
                        $result['code'] ?? null
                    );
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("ProcessWebhookEventsJob: Event processing failed", [
                    'event_id' => $event->id,
                    'platform' => $event->platform,
                    'error' => $e->getMessage(),
                ]);

                $event->markFailed($e->getMessage());
                $failed++;
            }
        }

        Log::info("ProcessWebhookEventsJob: Completed", [
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);
    }

    /**
     * Check if this event is a duplicate
     */
    protected function isDuplicate(WebhookEvent $event): bool
    {
        if (!$event->external_event_id) {
            return false;
        }

        return WebhookEvent::where('platform', $event->platform)
            ->where('external_event_id', $event->external_event_id)
            ->where('id', '!=', $event->id)
            ->where('status', WebhookEvent::STATUS_PROCESSED)
            ->exists();
    }

    /**
     * Find the original event ID for a duplicate
     */
    protected function findOriginalEventId(WebhookEvent $event): ?string
    {
        $original = WebhookEvent::where('platform', $event->platform)
            ->where('external_event_id', $event->external_event_id)
            ->where('id', '!=', $event->id)
            ->where('status', WebhookEvent::STATUS_PROCESSED)
            ->first();

        return $original?->id;
    }

    /**
     * Process a single webhook event
     */
    protected function processEvent(WebhookEvent $event): array
    {
        // Dispatch to the appropriate platform-specific processor
        return match ($event->platform) {
            'meta' => $this->processMetaEvent($event),
            'google' => $this->processGoogleEvent($event),
            'tiktok' => $this->processTikTokEvent($event),
            'linkedin' => $this->processLinkedInEvent($event),
            'twitter' => $this->processTwitterEvent($event),
            'snapchat' => $this->processSnapchatEvent($event),
            default => ['success' => false, 'error' => 'Unknown platform'],
        };
    }

    /**
     * Process Meta webhook event
     */
    protected function processMetaEvent(WebhookEvent $event): array
    {
        try {
            // Dispatch to existing ProcessWebhookJob if available
            ProcessWebhookJob::dispatch('meta', $event->payload);

            return [
                'success' => true,
                'org_id' => $this->extractOrgIdFromMeta($event),
                'connection_id' => $this->extractConnectionIdFromMeta($event),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Google webhook event
     */
    protected function processGoogleEvent(WebhookEvent $event): array
    {
        try {
            ProcessWebhookJob::dispatch('google', $event->payload);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process TikTok webhook event
     */
    protected function processTikTokEvent(WebhookEvent $event): array
    {
        try {
            ProcessWebhookJob::dispatch('tiktok', $event->payload);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process LinkedIn webhook event
     */
    protected function processLinkedInEvent(WebhookEvent $event): array
    {
        try {
            ProcessWebhookJob::dispatch('linkedin', $event->payload);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Twitter webhook event
     */
    protected function processTwitterEvent(WebhookEvent $event): array
    {
        try {
            ProcessWebhookJob::dispatch('twitter', $event->payload);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Snapchat webhook event
     */
    protected function processSnapchatEvent(WebhookEvent $event): array
    {
        try {
            ProcessWebhookJob::dispatch('snapchat', $event->payload);

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract org_id from Meta webhook payload
     */
    protected function extractOrgIdFromMeta(WebhookEvent $event): ?string
    {
        // Try to find connection based on page_id or ad_account_id in payload
        $payload = $event->payload;
        $pageId = $payload['entry'][0]['id'] ?? null;

        if ($pageId) {
            // Look up connection by platform asset
            $asset = \App\Models\Platform\PlatformAsset::where('external_id', $pageId)
                ->where('platform', 'meta')
                ->first();

            if ($asset) {
                return $asset->org_id;
            }
        }

        return null;
    }

    /**
     * Extract connection_id from Meta webhook payload
     */
    protected function extractConnectionIdFromMeta(WebhookEvent $event): ?string
    {
        $payload = $event->payload;
        $pageId = $payload['entry'][0]['id'] ?? null;

        if ($pageId) {
            $asset = \App\Models\Platform\PlatformAsset::where('external_id', $pageId)
                ->where('platform', 'meta')
                ->first();

            if ($asset) {
                return $asset->connection_id;
            }
        }

        return null;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWebhookEventsJob: Job failed permanently", [
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Determine the tags that should be applied to the job.
     */
    public function tags(): array
    {
        $tags = ['webhook-events'];

        if ($this->platform) {
            $tags[] = 'platform:' . $this->platform;
        }

        return $tags;
    }
}
