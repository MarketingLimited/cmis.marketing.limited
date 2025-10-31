<?php

namespace App\Services\Social;

use App\Models\Integration;
use App\Models\SocialPost;
use App\Models\SocialPostMetric;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use Throwable;

class InstagramSyncService
{
    public function syncAllActive(): int
    {
        $integrations = Integration::query()
            ->where('platform', 'instagram')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            Log::warning('No active Instagram integrations found for sync.');
            return 0;
        }

        $processed = 0;

        foreach ($integrations as $integration) {
            try {
                if (!empty($integration->org_id)) {
                    DB::statement("SELECT set_config('app.current_org_id', '{$integration->org_id}', true);");
                }

                if (empty($integration->access_token) || empty($integration->account_id)) {
                    Log::warning('Instagram integration missing credentials.', [
                        'integration_id' => $integration->integration_id ?? null,
                        'org_id' => $integration->org_id ?? null,
                        'account_id' => $integration->account_id ?? null,
                    ]);
                    continue;
                }

                $this->syncIntegrationByAccountId($integration);
                $processed++;

            } catch (Throwable $exception) {
                Log::error('Failed to sync Instagram integration.', [
                    'integration_id' => $integration->integration_id ?? null,
                    'org_id' => $integration->org_id ?? null,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    protected function syncIntegrationByAccountId(Integration $integration): void
    {
        $accountId = $integration->account_id;
        if (!$accountId) {
            throw new \RuntimeException('Missing account_id for integration.');
        }

        Log::info('Starting sync via account_id', [
            'org_id' => $integration->org_id,
            'account_id' => $accountId,
        ]);

        $mediaItems = $this->fetchAccountMedia($integration);

        foreach ($mediaItems as $media) {
            $postInsights = $this->fetchMediaInsights($integration, $media['id'], $media['media_type'] ?? null);
            $this->storePost($integration, $media, $postInsights);
        }

        Log::info('Completed sync for account_id', [
            'account_id' => $accountId,
            'media_count' => count($mediaItems),
        ]);
    }

    protected function fetchAccountMedia(Integration $integration): array
    {
        $fields = [
            'id','caption','media_type','media_url','thumbnail_url','permalink','timestamp','like_count','comments_count'
        ];

        $limit = 100;
        $endpoint = sprintf('%s/media', $integration->account_id);
        $params = ['fields' => implode(',', $fields), 'limit' => $limit];

        $response = $this->get($integration, $endpoint, $params);
        $this->throwIfFailed($response, 'Unable to fetch Instagram media.');

        $media = $response->json('data', []);

        foreach ($media as &$item) {
            if (($item['media_type'] ?? '') === 'CAROUSEL_ALBUM') {
                $children = $this->get($integration, sprintf('%s/children', $item['id']), [
                    'fields' => 'id,media_type,media_url,thumbnail_url'
                ])->json('data', []);
                $item['children_media'] = $children;
            }

            if (($item['media_type'] ?? '') === 'VIDEO' || ($item['media_type'] ?? '') === 'REEL') {
                $item['video_url'] = $item['media_url'] ?? null;
            }
        }

        return $media;
    }

    protected function fetchMediaInsights(Integration $integration, string $mediaId, ?string $mediaType = null): array
    {
        if ($mediaType === 'REEL') {
            $metrics = [
                'reach', 'likes', 'comments', 'saved', 'shares',
                'ig_reels_avg_watch_time', 'ig_reels_video_view_total_time'
            ];
        } else {
            $metrics = ['reach', 'likes', 'comments', 'saved', 'shares', 'total_interactions'];
        }

        $response = $this->get($integration, sprintf('%s/insights', $mediaId), [
            'metric' => implode(',', $metrics)
        ]);

        if ($response->failed()) {
            Log::warning('Unable to fetch Instagram media insights.', [
                'media_id' => $mediaId,
                'body' => $response->body(),
            ]);
            return [];
        }

        $insights = [];
        foreach ($response->json('data', []) as $metric) {
            $name = $metric['name'] ?? null;
            if (!$name) continue;
            $values = $metric['values'] ?? [];
            $latest = Arr::last($values);
            $insights[$name] = is_array($latest) ? ($latest['value'] ?? null) : $latest;
        }

        return $insights;
    }

    protected function storePost(Integration $integration, array $media, array $insights): void
    {
        try {
            SocialPost::updateOrCreate(
                [
                    'post_external_id' => $media['id'],
                    'integration_id' => $integration->integration_id
                ],
                [
                    'org_id' => $integration->org_id,
                    'caption' => $media['caption'] ?? null,
                    'media_type' => $media['media_type'] ?? null,
                    'media_url' => $media['media_url'] ?? null,
                    'video_url' => $media['video_url'] ?? null,
                    'thumbnail_url' => $media['thumbnail_url'] ?? null,
                    'children_media' => $media['children_media'] ?? null,
                    'permalink' => $media['permalink'] ?? null,
                    'posted_at' => isset($media['timestamp']) ? Carbon::parse($media['timestamp']) : null,
                    'like_count' => $media['like_count'] ?? null,
                    'comments_count' => $media['comments_count'] ?? null,
                    'metrics' => $insights,
                ]
            );
        } catch (Throwable $e) {
            Log::error('Failed to store Instagram post', [
                'error' => $e->getMessage(),
                'media_id' => $media['id'] ?? null,
            ]);
        }
    }

    protected function get(Integration $integration, string $endpoint, array $params = []): Response
    {
        $url = $this->buildUrl($endpoint);
        return Http::withToken($integration->access_token)
            ->acceptJson()
            ->timeout(60)
            ->retry(3, 500)
            ->get($url, $params);
    }

    protected function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http')) return $endpoint;
        $base = rtrim((string) config('services.instagram.base_url', 'https://graph.facebook.com/v24.0/'), '/');
        return $base.'/'.ltrim($endpoint, '/');
    }

    protected function throwIfFailed(Response $response, string $message): void
    {
        if ($response->failed()) {
            throw new \RuntimeException($message.' '.$response->body());
        }
    }
}
