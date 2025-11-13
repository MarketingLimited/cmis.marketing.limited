<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for Twitter/X API v2.
 * Handles tweets, messages, comments, and ads.
 */
class TwitterConnector extends AbstractConnector
{
    protected string $platform = 'twitter';
    protected string $baseUrl = 'https://api.twitter.com';
    protected string $apiVersion = '2';

    // ========================================
    // Authentication & Connection
    // ========================================

    public function getAuthUrl(array $options = []): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => config('services.twitter.client_id'),
            'redirect_uri' => config('services.twitter.redirect_uri'),
            'scope' => implode(' ', [
                'tweet.read',
                'tweet.write',
                'users.read',
                'follows.read',
                'offline.access',
                'dm.read',
                'dm.write',
            ]),
            'state' => $options['state'] ?? bin2hex(random_bytes(16)),
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain',
        ];

        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $response = \Http::asForm()->post('https://api.twitter.com/2/oauth2/token', [
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'client_id' => config('services.twitter.client_id'),
            'redirect_uri' => config('services.twitter.redirect_uri'),
            'code_verifier' => 'challenge',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get Twitter access token: ' . $response->body());
        }

        $tokens = $response->json();
        $accessToken = $tokens['access_token'];
        $refreshToken = $tokens['refresh_token'] ?? null;
        $expiresIn = $tokens['expires_in'] ?? 7200;

        // Get user info
        $userInfo = \Http::withToken($accessToken)
            ->get('https://api.twitter.com/2/users/me')
            ->json()['data'] ?? [];

        $integration = Integration::updateOrCreate(
            [
                'org_id' => $options['org_id'],
                'platform' => 'twitter',
                'external_account_id' => $userInfo['id'],
            ],
            [
                'access_token' => encrypt($accessToken),
                'refresh_token' => $refreshToken ? encrypt($refreshToken) : null,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'is_active' => true,
                'settings' => [
                    'username' => $userInfo['username'] ?? null,
                    'name' => $userInfo['name'] ?? null,
                ],
            ]
        );

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        try {
            \Http::asForm()->post('https://api.twitter.com/2/oauth2/revoke', [
                'token' => decrypt($integration->access_token),
                'client_id' => config('services.twitter.client_id'),
            ]);
        } catch (\Exception $e) {
            // Continue
        }

        $integration->update([
            'is_active' => false,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);

        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        if (!$integration->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $response = \Http::asForm()->post('https://api.twitter.com/2/oauth2/token', [
            'refresh_token' => decrypt($integration->refresh_token),
            'grant_type' => 'refresh_token',
            'client_id' => config('services.twitter.client_id'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        $tokens = $response->json();

        $integration->update([
            'access_token' => encrypt($tokens['access_token']),
            'refresh_token' => isset($tokens['refresh_token']) ? encrypt($tokens['refresh_token']) : $integration->refresh_token,
            'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 7200),
        ]);

        return $integration->fresh();
    }

    // ========================================
    // Sync Operations
    // ========================================

    public function syncCampaigns(Integration $integration, array $options = []): Collection
    {
        // Twitter Ads API
        return collect();
    }

    public function syncPosts(Integration $integration, array $options = []): Collection
    {
        $userId = $integration->external_account_id;
        $posts = collect();

        $response = $this->makeRequest($integration, 'GET', "/2/users/{$userId}/tweets", [
            'max_results' => 100,
            'tweet.fields' => 'created_at,public_metrics,entities',
        ]);

        foreach ($response['data'] ?? [] as $tweet) {
            $posts->push($this->storePost($integration, $tweet));
        }

        $this->logSync($integration, 'posts', $posts->count());

        return $posts;
    }

    public function syncComments(Integration $integration, array $options = []): Collection
    {
        // Twitter replies are treated as tweets
        return collect();
    }

    public function syncMessages(Integration $integration, array $options = []): Collection
    {
        $messages = collect();

        $response = $this->makeRequest($integration, 'GET', '/2/dm_events', [
            'max_results' => 100,
            'dm_event.fields' => 'id,text,created_at,sender_id,recipient_id',
        ]);

        foreach ($response['data'] ?? [] as $dm) {
            $messages->push($this->storeMessage($integration, $dm));
        }

        $this->logSync($integration, 'messages', $messages->count());

        return $messages;
    }

    public function getAccountMetrics(Integration $integration): Collection
    {
        $userId = $integration->external_account_id;

        $response = $this->makeRequest($integration, 'GET', "/2/users/{$userId}", [
            'user.fields' => 'public_metrics',
        ]);

        return collect($response['data']['public_metrics'] ?? []);
    }

    // ========================================
    // Publishing & Scheduling
    // ========================================

    public function publishPost(Integration $integration, ContentItem $item): string
    {
        $response = $this->makeRequest($integration, 'POST', '/2/tweets', [
            'text' => $item->content,
        ]);

        return $response['data']['id'];
    }

    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string
    {
        // Twitter API doesn't support native scheduling
        throw new \Exception('Twitter does not support native scheduling via API');
    }

    // ========================================
    // Messaging & Engagement
    // ========================================

    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array
    {
        $response = $this->makeRequest($integration, 'POST', '/2/dm_conversations/with/' . $conversationId . '/messages', [
            'text' => $messageText,
        ]);

        return [
            'success' => true,
            'message_id' => $response['data']['dm_event_id'] ?? null,
        ];
    }

    public function replyToComment(Integration $integration, string $commentId, string $replyText): array
    {
        $response = $this->makeRequest($integration, 'POST', '/2/tweets', [
            'text' => $replyText,
            'reply' => ['in_reply_to_tweet_id' => $commentId],
        ]);

        return [
            'success' => true,
            'comment_id' => $response['data']['id'] ?? null,
        ];
    }

    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool
    {
        $userId = $integration->external_account_id;

        $this->makeRequest($integration, 'PUT', "/2/tweets/{$commentId}/hidden", [
            'hidden' => $hide,
        ]);

        return true;
    }

    public function deleteComment(Integration $integration, string $commentId): bool
    {
        $this->makeRequest($integration, 'DELETE', "/2/tweets/{$commentId}");

        return true;
    }

    public function likeComment(Integration $integration, string $commentId): bool
    {
        $userId = $integration->external_account_id;

        $this->makeRequest($integration, 'POST', "/2/users/{$userId}/likes", [
            'tweet_id' => $commentId,
        ]);

        return true;
    }

    // ========================================
    // Ad Campaign Management
    // ========================================

    public function createAdCampaign(Integration $integration, array $campaignData): array
    {
        // Twitter Ads API requires separate authentication
        return ['success' => false, 'error' => 'Not implemented'];
    }

    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array
    {
        return ['success' => false, 'error' => 'Not implemented'];
    }

    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection
    {
        return collect();
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function storePost(Integration $integration, array $tweet): int
    {
        return $this->storeData('cmis_social.social_posts', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'platform_post_id' => $tweet['id'],
            'post_type' => 'tweet',
            'content' => $tweet['text'],
            'published_at' => isset($tweet['created_at']) ? Carbon::parse($tweet['created_at']) : now(),
            'metrics' => json_encode($tweet['public_metrics'] ?? []),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ], ['platform_post_id' => $tweet['id']]);
    }

    private function storeMessage(Integration $integration, array $dm): int
    {
        return $this->storeData('cmis_social.social_messages', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'platform_message_id' => $dm['id'],
            'conversation_id' => $dm['dm_conversation_id'] ?? null,
            'message_text' => $dm['text'],
            'sender_id' => $dm['sender_id'] ?? null,
            'is_from_page' => ($dm['sender_id'] ?? null) === $integration->external_account_id,
            'status' => 'received',
            'created_at' => isset($dm['created_at']) ? Carbon::parse($dm['created_at']) : now(),
        ], ['platform_message_id' => $dm['id']]);
    }
}
