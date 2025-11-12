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

    /**
     * Sync advertising campaigns from the external platform.
     *
     * @param Integration $integration
     * @return Collection A collection of synced ad campaigns.
     */
    public function syncCampaigns(Integration $integration): Collection;

    /**
     * Sync social media posts from the external platform.
     *
     * @param Integration $integration
     * @return Collection A collection of synced social posts.
     */
    public function syncPosts(Integration $integration): Collection;

    /**
     * Fetch account-level metrics (e.g., followers, total spend).
     *
     * @param Integration $integration
     * @return Collection A collection of account metrics.
     */
    public function getAccountMetrics(Integration $integration): Collection;

    /**
     * Publish a content item to the platform.
     *
     * @param Integration $integration
     * @param ContentItem $item
     * @return string The external ID of the newly published post.
     */
    public function publishPost(Integration $integration, ContentItem $item): string;
}
