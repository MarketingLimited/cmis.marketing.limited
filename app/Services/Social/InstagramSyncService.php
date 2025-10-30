<?php

namespace App\Services\Social;

use App\Models\Integration;
use App\Models\SocialAccount;
use App\Models\SocialAccountMetric;
use App\Models\SocialPost;
use App\Models\SocialPostMetric;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Throwable;

class InstagramSyncService
{
    /**
     * Synchronize all active Instagram integrations.
     */
    public function syncAllActive(): int
    {
        $integrations = Integration::query()
            ->platform('instagram')
            ->active()
            ->get();

        $processed = 0;

        foreach ($integrations as $integration) {
            try {
                if (empty($integration->access_token) || empty($integration->account_id)) {
                    Log::warning('Instagram integration missing credentials.', [
                        'integration_id' => $integration->integration_id,
                    ]);
                    continue;
                }

                $this->syncIntegration($integration);
                $processed++;
            } catch (Throwable $exception) {
                Log::error('Failed to sync Instagram integration.', [
                    'integration_id' => $integration->integration_id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Synchronize a specific Instagram integration.
     */
    public function syncIntegration(Integration $integration): void
    {
        $accountProfile = $this->fetchAccountProfile($integration);
        $this->storeAccount($integration, $accountProfile);

        $accountInsights = $this->fetchAccountInsights($integration);
        $this->storeAccountMetrics($integration, $accountProfile, $accountInsights);

        $mediaItems = $this->fetchAccountMedia($integration);

        foreach ($mediaItems as $media) {
            $postInsights = $this->fetchMediaInsights($integration, $media['id']);
            $this->storePost($integration, $media, $postInsights);
        }
    }

    /**
     * Fetch Instagram account profile information.
     */
    protected function fetchAccountProfile(Integration $integration): array
    {
        $fields = config('services.instagram.account_fields', [
            'id',
            'username',
            'name',
            'profile_picture_url',
            'biography',
            'website',
            'followers_count',
            'follows_count',
            'media_count',
            'category_name',
            'is_verified',
        ]);

        $response = $this->get($integration, sprintf('%s', $integration->account_id), [
            'fields' => implode(',', $fields),
        ]);

        $this->throwIfFailed($response, 'Unable to fetch Instagram account profile.');

        return $response->json();
    }

    /**
     * Fetch Instagram account insights.
     */
    protected function fetchAccountInsights(Integration $integration): array
    {
        $metrics = config('services.instagram.account_metrics', ['impressions', 'reach', 'profile_views']);

        if (empty($metrics)) {
            return [];
        }

        $response = $this->get($integration, sprintf('%s/insights', $integration->account_id), [
            'metric' => implode(',', $metrics),
            'period' => 'day',
        ]);

        if ($response->failed()) {
            Log::warning('Unable to fetch Instagram account insights.', [
                'integration_id' => $integration->integration_id,
                'body' => $response->body(),
            ]);

            return [];
        }

        $insights = [];

        foreach ($response->json('data', []) as $metric) {
            $name = $metric['name'] ?? null;

            if (! $name) {
                continue;
            }

            $values = $metric['values'] ?? [];
            $latest = Arr::last($values);

            if (is_array($latest)) {
                if (isset($latest['end_time'])) {
                    $insights['_metric_date'] = Carbon::parse($latest['end_time'])->toDateString();
                }

                $insights[$name] = $latest['value'] ?? null;
            } else {
                $insights[$name] = $latest;
            }
        }

        return $insights;
    }

    /**
     * Fetch account media items with pagination support.
     */
    protected function fetchAccountMedia(Integration $integration): array
    {
        $fields = config('services.instagram.media_fields', [
            'id',
            'caption',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
            'like_count',
            'comments_count',
        ]);

        $limit = (int) config('services.instagram.media_page_size', 50);
        $maxPages = (int) config('services.instagram.media_max_pages', 5);

        $media = [];
        $endpoint = sprintf('%s/media', $integration->account_id);
        $params = [
            'fields' => implode(',', $fields),
            'limit' => $limit,
        ];
        $page = 0;

        while ($endpoint && $page < $maxPages) {
            $response = $this->get($integration, $endpoint, $params);
            $this->throwIfFailed($response, 'Unable to fetch Instagram media.');

            $payload = $response->json();
            $media = array_merge($media, $payload['data'] ?? []);

            $endpoint = Arr::get($payload, 'paging.next');
            $params = [];
            $page++;
        }

        return $media;
    }

    /**
     * Fetch insights for a specific media item.
     */
    protected function fetchMediaInsights(Integration $integration, string $mediaId): array
    {
        $metrics = config('services.instagram.post_insight_metrics', ['impressions', 'reach', 'saved']);

        if (empty($metrics)) {
            return [];
        }

        $response = $this->get($integration, sprintf('%s/insights', $mediaId), [
            'metric' => implode(',', $metrics),
        ]);

        if ($response->failed()) {
            Log::warning('Unable to fetch Instagram media insights.', [
                'integration_id' => $integration->integration_id,
                'media_id' => $mediaId,
                'body' => $response->body(),
            ]);

            return [];
        }

        $insights = [];

        foreach ($response->json('data', []) as $metric) {
            $name = $metric['name'] ?? null;

            if (! $name) {
                continue;
            }

            $values = $metric['values'] ?? [];
            $latest = Arr::last($values);

            if (is_array($latest)) {
                if (isset($latest['end_time'])) {
                    $insights['_metric_date'] = Carbon::parse($latest['end_time'])->toDateString();
                }

                $insights[$name] = $latest['value'] ?? null;
            } else {
                $insights[$name] = $latest;
            }
        }

        return $insights;
    }

    /**
     * Persist the social account data.
     */
    protected function storeAccount(Integration $integration, array $profile): SocialAccount
    {
        return SocialAccount::query()->updateOrCreate(
            [
                'integration_id' => $integration->integration_id,
            ],
            [
                'org_id' => $integration->org_id,
                'account_external_id' => $profile['id'] ?? $integration->account_id,
                'username' => $profile['username'] ?? null,
                'display_name' => $profile['name'] ?? ($profile['username'] ?? null),
                'profile_picture_url' => $profile['profile_picture_url'] ?? null,
                'biography' => $profile['biography'] ?? null,
                'followers_count' => $profile['followers_count'] ?? null,
                'follows_count' => $profile['follows_count'] ?? null,
                'media_count' => $profile['media_count'] ?? null,
                'website' => $profile['website'] ?? null,
                'category' => $profile['category_name'] ?? null,
                'is_verified' => (bool) ($profile['is_verified'] ?? false),
                'fetched_at' => Carbon::now('UTC'),
            ]
        );
    }

    /**
     * Persist the account level metrics.
     */
    protected function storeAccountMetrics(Integration $integration, array $profile, array $insights): void
    {
        $metricDate = $insights['_metric_date'] ?? Carbon::now('UTC')->toDateString();
        unset($insights['_metric_date']);

        SocialAccountMetric::query()->updateOrCreate(
            [
                'integration_id' => $integration->integration_id,
                'period_start' => $metricDate,
                'period_end' => $metricDate,
            ],
            [
                'followers' => $profile['followers_count'] ?? null,
                'reach' => $insights['reach'] ?? null,
                'impressions' => $insights['impressions'] ?? null,
                'profile_views' => $insights['profile_views'] ?? null,
            ]
        );
    }

    /**
     * Persist media posts and metrics.
     */
    protected function storePost(Integration $integration, array $media, array $insights): void
    {
        $postedAt = isset($media['timestamp']) ? Carbon::parse($media['timestamp']) : null;

        $post = SocialPost::query()->updateOrCreate(
            [
                'integration_id' => $integration->integration_id,
                'post_external_id' => $media['id'],
            ],
            [
                'org_id' => $integration->org_id,
                'caption' => $media['caption'] ?? null,
                'media_url' => $media['media_url'] ?? ($media['thumbnail_url'] ?? null),
                'permalink' => $media['permalink'] ?? null,
                'media_type' => $media['media_type'] ?? null,
                'posted_at' => $postedAt,
                'metrics' => [
                    'like_count' => $media['like_count'] ?? null,
                    'comments_count' => $media['comments_count'] ?? null,
                ],
                'fetched_at' => Carbon::now('UTC'),
            ]
        );

        $metricDate = $insights['_metric_date'] ?? Carbon::now('UTC')->toDateString();
        unset($insights['_metric_date']);

        SocialPostMetric::query()->updateOrCreate(
            [
                'integration_id' => $integration->integration_id,
                'post_external_id' => $media['id'],
                'metric_date' => $metricDate,
            ],
            [
                'social_post_id' => $post->getKey(),
                'impressions' => $insights['impressions'] ?? null,
                'reach' => $insights['reach'] ?? null,
                'likes' => $media['like_count'] ?? ($insights['likes'] ?? null),
                'comments' => $media['comments_count'] ?? ($insights['comments'] ?? null),
                'saves' => $insights['saved'] ?? ($insights['saves'] ?? null),
                'shares' => $insights['shares'] ?? null,
            ]
        );
    }

    /**
     * Perform a GET request against the Instagram Graph API.
     */
    protected function get(Integration $integration, string $endpoint, array $params = []): Response
    {
        $url = $this->buildUrl($endpoint);

        return Http::withToken($integration->access_token)
            ->acceptJson()
            ->timeout((int) config('services.instagram.timeout', 30))
            ->retry(
                (int) config('services.instagram.retry_times', 3),
                (int) config('services.instagram.retry_sleep', 500)
            )
            ->get($url, $params);
    }

    /**
     * Build a fully qualified URL for the Instagram API.
     */
    protected function buildUrl(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http')) {
            return $endpoint;
        }

        $base = rtrim((string) config('services.instagram.base_url', 'https://graph.facebook.com/v21.0/'), '/');

        return $base.'/'.ltrim($endpoint, '/');
    }

    /**
     * Throw a runtime exception if the response failed.
     */
    protected function throwIfFailed(Response $response, string $message): void
    {
        if ($response->failed()) {
            throw new \RuntimeException($message.' '.$response->body());
        }
    }
}
