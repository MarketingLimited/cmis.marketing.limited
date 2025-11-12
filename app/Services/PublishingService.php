<?php

namespace App\Services;

use App\Models\Creative\ContentItem;
use App\Models\Core\Integration;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for handling the scheduling and publishing of content items.
 */
class PublishingService
{
    /**
     * Finds and publishes all content items that are due.
     * This method is intended to be called by a scheduled task (e.g., every minute).
     *
     * @return void
     */
    public function publishDueItems(): void
    {
        // Get all content items scheduled for publishing up to the current time
        // that are in 'scheduled' status.
        $itemsToPublish = ContentItem::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with('channel.integration') // Eager load relationships
            ->get();

        if ($itemsToPublish->isEmpty()) {
            return; // Nothing to do
        }

        Log::info("Found {$itemsToPublish->count()} content items due for publishing.");

        foreach ($itemsToPublish as $item) {
            $this->publishItem($item);
        }
    }

    /**
     * Publishes a single content item.
     *
     * @param ContentItem $item
     * @return void
     */
    public function publishItem(ContentItem $item): void
    {
        // Ensure the item has a channel and an active integration
        if (!$item->channel || !$item->channel->integration || !$item->channel->integration->is_active) {
            $item->update(['status' => 'failed']);
            Log::error("Publishing failed for item [{$item->item_id}]: No active integration found.");
            return;
        }

        $integration = $item->channel->integration;
        $platform = $integration->platform;

        try {
            $item->update(['status' => 'publishing']);

            $connector = ConnectorFactory::make($platform);
            $externalId = $connector->publishPost($integration, $item);

            // Update the item to 'published' status and store the external post ID
            $item->update([
                'status' => 'published',
                // Storing external reference in a generic way might need a dedicated field
                // For now, let's assume it can be added to the 'brief' JSONB field or similar.
                // 'external_id' => $externalId,
            ]);

            Log::info("Successfully published item [{$item->item_id}] to {$platform}. External ID: {$externalId}");

        } catch (\Exception $e) {
            $item->update(['status' => 'failed']);
            Log::error("Publishing failed for item [{$item->item_id}] to {$platform}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
