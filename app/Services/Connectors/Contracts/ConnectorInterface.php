<?php

namespace App\Services\Connectors\Contracts;

use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;

/**
 * Defines the contract for all external platform connectors.
 * Each connector must implement these methods to provide a unified way
 * for the system to interact with different platforms like Meta, Google, etc.
 */
interface ConnectorInterface
{
    // ========================================
    // Authentication & Connection
    // ========================================

    /**
     * Authenticate and connect to the platform using an OAuth authorization code.
     *
     * @param string $authCode The authorization code from the OAuth2 redirect.
     * @param array $options Additional options required for connection.
     * @return Integration The updated Integration model with the stored token.
     */
    public function connect(string $authCode, array $options = []): Integration;

    /**
     * Disconnect from the platform and revoke the access token.
     *
     * @param Integration $integration The integration to disconnect.
     * @return bool True on success.
     */
    public function disconnect(Integration $integration): bool;

    /**
     * Refresh the access token if it has expired.
     *
     * @param Integration $integration
     * @return Integration The updated Integration model.
     */
    public function refreshToken(Integration $integration): Integration;

    // ========================================
    // Sync Operations
    // ========================================

    /**
     * Sync advertising campaigns from the external platform.
     *
     * @param Integration $integration
     * @param array $options Options like date range, filters, etc.
     * @return Collection A collection of synced ad campaigns.
     */
    public function syncCampaigns(Integration $integration, array $options = []): Collection;

    /**
     * Sync social media posts from the external platform.
     *
     * @param Integration $integration
     * @param array $options Options like date range, filters, etc.
     * @return Collection A collection of synced social posts.
     */
    public function syncPosts(Integration $integration, array $options = []): Collection;

    /**
     * Sync comments from posts on the platform.
     *
     * @param Integration $integration
     * @param array $options Options like date range, post_ids, etc.
     * @return Collection A collection of synced comments.
     */
    public function syncComments(Integration $integration, array $options = []): Collection;

    /**
     * Sync messages/DMs from the platform.
     *
     * @param Integration $integration
     * @param array $options Options like date range, conversation filters, etc.
     * @return Collection A collection of synced messages.
     */
    public function syncMessages(Integration $integration, array $options = []): Collection;

    /**
     * Fetch account-level metrics (e.g., followers, total spend).
     *
     * @param Integration $integration
     * @return Collection A collection of account metrics.
     */
    public function getAccountMetrics(Integration $integration): Collection;

    // ========================================
    // Publishing & Scheduling
    // ========================================

    /**
     * Publish a content item to the platform immediately.
     *
     * @param Integration $integration
     * @param ContentItem $item
     * @return string The external ID of the newly published post.
     */
    public function publishPost(Integration $integration, ContentItem $item): string;

    /**
     * Schedule a post to be published at a specific time.
     *
     * @param Integration $integration
     * @param ContentItem $item
     * @param \Carbon\Carbon $scheduledTime
     * @return string The external ID of the scheduled post.
     */
    public function schedulePost(Integration $integration, ContentItem $item, \Carbon\Carbon $scheduledTime): string;

    // ========================================
    // Messaging & Engagement
    // ========================================

    /**
     * Send a reply to a message/DM.
     *
     * @param Integration $integration
     * @param string $conversationId The external conversation/thread ID.
     * @param string $messageText The reply text.
     * @param array $options Additional options (attachments, etc.)
     * @return array Response with message_id and status.
     */
    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array;

    /**
     * Reply to a comment on a post.
     *
     * @param Integration $integration
     * @param string $commentId The external comment ID.
     * @param string $replyText The reply text.
     * @return array Response with comment_id and status.
     */
    public function replyToComment(Integration $integration, string $commentId, string $replyText): array;

    /**
     * Hide/unhide a comment.
     *
     * @param Integration $integration
     * @param string $commentId The external comment ID.
     * @param bool $hide True to hide, false to unhide.
     * @return bool Success status.
     */
    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool;

    /**
     * Delete a comment.
     *
     * @param Integration $integration
     * @param string $commentId The external comment ID.
     * @return bool Success status.
     */
    public function deleteComment(Integration $integration, string $commentId): bool;

    /**
     * Like a comment (as the page/account).
     *
     * @param Integration $integration
     * @param string $commentId The external comment ID.
     * @return bool Success status.
     */
    public function likeComment(Integration $integration, string $commentId): bool;

    // ========================================
    // Ad Campaign Management
    // ========================================

    /**
     * Create a new ad campaign on the platform.
     *
     * @param Integration $integration
     * @param array $campaignData Campaign configuration (name, budget, targeting, etc.)
     * @return array Response with campaign_id and status.
     */
    public function createAdCampaign(Integration $integration, array $campaignData): array;

    /**
     * Update an existing ad campaign.
     *
     * @param Integration $integration
     * @param string $campaignId The external campaign ID.
     * @param array $updates Fields to update.
     * @return array Response with status.
     */
    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array;

    /**
     * Get metrics for a specific ad campaign.
     *
     * @param Integration $integration
     * @param string $campaignId The external campaign ID.
     * @param array $options Date range, metrics to fetch, etc.
     * @return Collection Campaign performance metrics.
     */
    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection;
}
