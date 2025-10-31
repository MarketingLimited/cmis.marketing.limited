<?php

namespace App\Services\Social;

use App\Models\Integration;
use App\Models\SocialPost;
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
            Log::info('Loaded integration', [
                'integration_id' => $integration->integration_id,
                'org_id' => $integration->org_id,
            ]);

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

    public function syncIntegrationByAccountId(Integration $integration): void
    {
        $endpoint = sprintf('%s/media', $integration->account_id);
        $fields = [
            'id','caption','media_type','media_url','thumbnail_url','permalink','timestamp','like_count','comments_count'
        ];
        $params = ['fields' => implode(',', $fields), 'limit' => 100];

        $response = $this->get($integration, $endpoint, $params);
        $this->throwIfFailed($response, 'Unable to fetch Instagram media.');

        $mediaItems = $response->json('data', []);

        foreach ($mediaItems as $media) {
            $insights = $this->fetchMediaInsights($integration, $media['id'], $media['media_type'] ?? null);
            $this->storePost($integration, $media, $insights);
        }
    }

    protected function storePost(Integration $integration, array $media, array $insights): void
    {
        try {
            $post = SocialPost::updateOrCreate(
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

            $this->storePostMetrics($post, $integration, $insights);
        } catch (Throwable $e) {
            Log::error('Failed to store Instagram post', [
                'error' => $e->getMessage(),
                'media_id' => $media['id'] ?? null,
            ]);
        }
    }

    protected function storePostMetrics(SocialPost $post, Integration $integration, array $insights): void
    {
        foreach ($insights as $metric => $value) {
            Log::info('Recording metric', [
                'metric' => $metric,
                'value' => $value,
                'post_id' => $post->id,
                'external_id' => $post->post_external_id,
            ]);

            DB::table('cmis.social_post_metrics')->insert([
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'post_external_id' => $post->post_external_id,
                'social_post_id' => $post->id,
                'metric' => $metric,
                'value' => $value,
                'fetched_at' => now(),
                'created_at' => now(),
            ]);
        }
    }

    protected function fetchMediaInsights(Integration $integration, string $mediaId, ?string $mediaType = null): array
    {
        $metrics = ['reach', 'likes', 'comments', 'saved', 'shares', 'total_interactions'];
        if ($mediaType === 'REEL') {
            $metrics[] = 'ig_reels_avg_watch_time';
            $metrics[] = 'ig_reels_video_view_total_time';
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