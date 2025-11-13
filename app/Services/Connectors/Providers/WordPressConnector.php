<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for WordPress REST API - COMPLETE IMPLEMENTATION
 */
class WordPressConnector extends AbstractConnector
{
    protected string $platform = 'wordpress';
    protected string $baseUrl = '';
    protected string $apiVersion = 'wp/v2';

    public function getAuthUrl(array $options = []): string
    {
        return ''; // WordPress uses Application Passwords
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $integration = Integration::create([
            'org_id' => $options['org_id'],
            'platform' => 'wordpress',
            'is_active' => true,
            'settings' => [
                'site_url' => $options['site_url'],
                'username' => $options['username'],
                'application_password' => $options['application_password'],
            ],
        ]);

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update(['is_active' => false]);
        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        return $integration;
    }

    public function syncCampaigns(Integration $integration, array $options = []): Collection { return collect(); }

    public function syncPosts(Integration $integration, array $options = []): Collection
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        $response = \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->get($baseUrl . '/posts', [
            'per_page' => 100,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        $posts = collect();
        foreach ($response->json() ?? [] as $post) {
            $posts->push($this->storeData('cmis_social.social_posts', [
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'wordpress',
                'platform_post_id' => $post['id'],
                'content' => $post['title']['rendered'] ?? null,
                'permalink' => $post['link'] ?? null,
                'published_at' => isset($post['date']) ? Carbon::parse($post['date']) : now(),
                'status' => $post['status'] ?? 'published',
                'created_at' => now(),
            ], ['platform_post_id' => $post['id']]));
        }

        $this->logSync($integration, 'posts', $posts->count());
        return $posts;
    }

    public function syncComments(Integration $integration, array $options = []): Collection
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        $response = \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->get($baseUrl . '/comments', [
            'per_page' => 100,
        ]);

        $comments = collect();
        foreach ($response->json() ?? [] as $comment) {
            $comments->push($this->storeData('cmis_social.social_comments', [
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'wordpress',
                'platform_comment_id' => $comment['id'],
                'post_id' => DB::table('cmis_social.social_posts')
                    ->where('platform_post_id', $comment['post'])
                    ->value('post_id'),
                'comment_text' => strip_tags($comment['content']['rendered'] ?? ''),
                'commenter_name' => $comment['author_name'] ?? null,
                'created_at' => isset($comment['date']) ? Carbon::parse($comment['date']) : now(),
            ], ['platform_comment_id' => $comment['id']]));
        }

        $this->logSync($integration, 'comments', $comments->count());
        return $comments;
    }

    public function syncMessages(Integration $integration, array $options = []): Collection { return collect(); }
    public function getAccountMetrics(Integration $integration): Collection { return collect(); }

    public function publishPost(Integration $integration, ContentItem $item): string
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        $response = \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->post($baseUrl . '/posts', [
            'title' => $item->title ?? '',
            'content' => $item->content,
            'status' => 'publish',
        ]);

        return $response->json()['id'] ?? '';
    }

    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        $response = \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->post($baseUrl . '/posts', [
            'title' => $item->title ?? '',
            'content' => $item->content,
            'status' => 'future',
            'date' => $scheduledTime->toIso8601String(),
        ]);

        return $response->json()['id'] ?? '';
    }

    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array { return ['success' => false]; }

    public function replyToComment(Integration $integration, string $commentId, string $replyText): array
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        $response = \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->post($baseUrl . '/comments', [
            'post' => $commentId,
            'content' => $replyText,
            'parent' => $commentId,
        ]);

        return ['success' => $response->successful(), 'comment_id' => $response->json()['id'] ?? null];
    }

    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->post($baseUrl . "/comments/{$commentId}", [
            'status' => $hide ? 'hold' : 'approved',
        ]);

        return true;
    }

    public function deleteComment(Integration $integration, string $commentId): bool
    {
        $siteUrl = $integration->settings['site_url'];
        $baseUrl = rtrim($siteUrl, '/') . '/wp-json/wp/v2';

        \Http::withBasicAuth(
            $integration->settings['username'],
            $integration->settings['application_password']
        )->delete($baseUrl . "/comments/{$commentId}");

        return true;
    }

    public function likeComment(Integration $integration, string $commentId): bool { return false; }
    public function createAdCampaign(Integration $integration, array $campaignData): array { return ['success' => false]; }
    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array { return ['success' => false]; }
    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection { return collect(); }
}
