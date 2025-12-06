<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Social Platform Data Service
 *
 * Provides platform-specific data such as post types, locations,
 * trending hashtags, and other platform features.
 */
class SocialPlatformDataService
{
    /**
     * Get available post types for all platforms
     *
     * @return array Post types by platform
     */
    public function getPostTypes(): array
    {
        return [
            'facebook' => [
                ['value' => 'feed', 'label' => __('publish.post_types.facebook.feed'), 'icon' => 'fa-newspaper'],
                ['value' => 'reel', 'label' => __('publish.post_types.facebook.reel'), 'icon' => 'fa-video'],
                ['value' => 'story', 'label' => __('publish.post_types.facebook.story'), 'icon' => 'fa-circle'],
            ],
            'instagram' => [
                ['value' => 'feed', 'label' => __('publish.post_types.instagram.feed'), 'icon' => 'fa-image'],
                ['value' => 'reel', 'label' => __('publish.post_types.instagram.reel'), 'icon' => 'fa-video'],
                ['value' => 'story', 'label' => __('publish.post_types.instagram.story'), 'icon' => 'fa-circle'],
                ['value' => 'carousel', 'label' => __('publish.post_types.instagram.carousel'), 'icon' => 'fa-images'],
            ],
            'twitter' => [
                ['value' => 'tweet', 'label' => __('publish.post_types.twitter.tweet'), 'icon' => 'fa-comment'],
                ['value' => 'thread', 'label' => __('publish.post_types.twitter.thread'), 'icon' => 'fa-list'],
            ],
            'linkedin' => [
                ['value' => 'post', 'label' => __('publish.post_types.linkedin.post'), 'icon' => 'fa-file-alt'],
                ['value' => 'article', 'label' => __('publish.post_types.linkedin.article'), 'icon' => 'fa-newspaper'],
            ],
            'tiktok' => [
                ['value' => 'video', 'label' => __('publish.post_types.tiktok.video'), 'icon' => 'fa-video'],
                ['value' => 'photo', 'label' => __('publish.post_types.tiktok.photo'), 'icon' => 'fa-image'],
            ],
            'google_business' => [
                ['value' => 'post', 'label' => __('publish.post_types.google_business.post'), 'icon' => 'fa-newspaper'],
                ['value' => 'offer', 'label' => __('publish.post_types.google_business.offer'), 'icon' => 'fa-tag'],
                ['value' => 'event', 'label' => __('publish.post_types.google_business.event'), 'icon' => 'fa-calendar'],
            ],
        ];
    }

    /**
     * Search for locations using Facebook Places API
     *
     * @param string $orgId Organization UUID
     * @param string $query Search query
     * @return array Location results
     * @throws \Exception
     */
    public function searchLocations(string $orgId, string $query): array
    {
        try {
            // Get Meta connection for access token
            $connection = PlatformConnection::where('org_id', $orgId)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                throw new \Exception('No active Meta connection found. Please connect your Facebook/Instagram account first.');
            }

            $accessToken = $connection->access_token;

            // Search Facebook Places API
            $response = Http::timeout(15)->get('https://graph.facebook.com/v21.0/search', [
                'type' => 'place',
                'q' => $query,
                'fields' => 'id,name,location,category_list',
                'limit' => 10,
                'access_token' => $accessToken,
            ]);

            if (!$response->successful()) {
                $error = $response->json('error.message', 'Failed to search locations');
                Log::warning('Facebook Places search failed', [
                    'query' => $query,
                    'error' => $error,
                ]);
                throw new \Exception($error);
            }

            $places = $response->json('data', []);

            // Format results for autocomplete
            return array_map(function ($place) {
                return $this->formatLocation($place);
            }, $places);

        } catch (\Exception $e) {
            Log::error('Location search error', [
                'org_id' => $orgId,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Format a location from Facebook Places API
     *
     * @param array $place
     * @return array
     */
    protected function formatLocation(array $place): array
    {
        $location = $place['location'] ?? [];
        $categories = $place['category_list'] ?? [];
        $categoryName = !empty($categories) ? $categories[0]['name'] ?? '' : '';

        // Build address string
        $addressParts = array_filter([
            $location['city'] ?? '',
            $location['state'] ?? '',
            $location['country'] ?? '',
        ]);
        $address = implode(', ', $addressParts);

        return [
            'id' => $place['id'],
            'name' => $place['name'],
            'address' => $address,
            'category' => $categoryName,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
        ];
    }

    /**
     * Get trending hashtags for a platform
     *
     * @param string $platform Platform name
     * @param string|null $orgId Organization ID for API access
     * @return array Trending hashtags with metadata
     */
    public function getTrendingHashtags(string $platform, ?string $orgId = null): array
    {
        $cacheKey = "trending_hashtags_{$platform}_" . ($orgId ?? 'default');

        // Try to get cached data (cache for 1 hour)
        return Cache::remember($cacheKey, 3600, function () use ($platform, $orgId) {
            // Try to fetch real trending data if we have API access
            if ($orgId) {
                try {
                    $trendingData = $this->fetchTrendingFromApi($platform, $orgId);
                    if (!empty($trendingData)) {
                        return $trendingData;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch trending hashtags from API', [
                        'platform' => $platform,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fall back to curated industry hashtags
            return $this->getDefaultTrendingHashtags($platform);
        });
    }

    /**
     * Fetch trending hashtags from platform API
     *
     * @param string $platform
     * @param string $orgId
     * @return array
     */
    protected function fetchTrendingFromApi(string $platform, string $orgId): array
    {
        $connection = PlatformConnection::where('org_id', $orgId)
            ->where('platform', $platform)
            ->where('status', 'active')
            ->first();

        if (!$connection) {
            return [];
        }

        return match ($platform) {
            'twitter' => $this->fetchTwitterTrends($connection),
            'tiktok' => $this->fetchTikTokTrends($connection),
            default => [],
        };
    }

    /**
     * Fetch Twitter/X trending topics
     */
    protected function fetchTwitterTrends(PlatformConnection $connection): array
    {
        try {
            $accessToken = decrypt($connection->access_token);

            // Twitter API v2 - Get trending topics (requires elevated access)
            $response = Http::withToken($accessToken)
                ->get('https://api.twitter.com/2/trends/by/woeid/1'); // WOEID 1 = Worldwide

            if ($response->successful()) {
                $data = $response->json();
                $hashtags = [];

                foreach ($data['data'] ?? [] as $trend) {
                    if (str_starts_with($trend['name'] ?? '', '#')) {
                        $hashtags[] = [
                            'tag' => $trend['name'],
                            'volume' => $trend['tweet_count'] ?? null,
                            'trending' => true,
                        ];
                    }
                }

                if (count($hashtags) > 0) {
                    return array_slice($hashtags, 0, 20);
                }
            }
        } catch (\Exception $e) {
            Log::debug('Twitter trends fetch failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Fetch TikTok trending hashtags
     */
    protected function fetchTikTokTrends(PlatformConnection $connection): array
    {
        try {
            $accessToken = decrypt($connection->access_token);

            // TikTok Research API - Trending hashtags
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('https://open.tiktokapis.com/v2/research/hashtag/list/', [
                    'fields' => ['id', 'name', 'video_count'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $hashtags = [];

                foreach ($data['data']['hashtags'] ?? [] as $hashtag) {
                    $hashtags[] = [
                        'tag' => '#' . $hashtag['name'],
                        'volume' => $hashtag['video_count'] ?? null,
                        'trending' => true,
                    ];
                }

                if (count($hashtags) > 0) {
                    return array_slice($hashtags, 0, 20);
                }
            }
        } catch (\Exception $e) {
            Log::debug('TikTok trends fetch failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get default trending hashtags by platform
     *
     * @param string $platform
     * @return array
     */
    protected function getDefaultTrendingHashtags(string $platform): array
    {
        $defaults = [
            'instagram' => [
                ['tag' => '#viral', 'volume' => null, 'trending' => false],
                ['tag' => '#instagood', 'volume' => null, 'trending' => false],
                ['tag' => '#photooftheday', 'volume' => null, 'trending' => false],
                ['tag' => '#love', 'volume' => null, 'trending' => false],
                ['tag' => '#fashion', 'volume' => null, 'trending' => false],
                ['tag' => '#marketing', 'volume' => null, 'trending' => false],
                ['tag' => '#photography', 'volume' => null, 'trending' => false],
                ['tag' => '#art', 'volume' => null, 'trending' => false],
                ['tag' => '#reels', 'volume' => null, 'trending' => false],
                ['tag' => '#explore', 'volume' => null, 'trending' => false],
            ],
            'twitter' => [
                ['tag' => '#trending', 'volume' => null, 'trending' => false],
                ['tag' => '#breaking', 'volume' => null, 'trending' => false],
                ['tag' => '#news', 'volume' => null, 'trending' => false],
                ['tag' => '#tech', 'volume' => null, 'trending' => false],
                ['tag' => '#business', 'volume' => null, 'trending' => false],
                ['tag' => '#innovation', 'volume' => null, 'trending' => false],
                ['tag' => '#digital', 'volume' => null, 'trending' => false],
                ['tag' => '#AI', 'volume' => null, 'trending' => false],
            ],
            'tiktok' => [
                ['tag' => '#fyp', 'volume' => null, 'trending' => false],
                ['tag' => '#viral', 'volume' => null, 'trending' => false],
                ['tag' => '#trending', 'volume' => null, 'trending' => false],
                ['tag' => '#foryou', 'volume' => null, 'trending' => false],
                ['tag' => '#challenge', 'volume' => null, 'trending' => false],
                ['tag' => '#duet', 'volume' => null, 'trending' => false],
                ['tag' => '#trend', 'volume' => null, 'trending' => false],
                ['tag' => '#tiktok', 'volume' => null, 'trending' => false],
            ],
            'linkedin' => [
                ['tag' => '#leadership', 'volume' => null, 'trending' => false],
                ['tag' => '#business', 'volume' => null, 'trending' => false],
                ['tag' => '#innovation', 'volume' => null, 'trending' => false],
                ['tag' => '#networking', 'volume' => null, 'trending' => false],
                ['tag' => '#career', 'volume' => null, 'trending' => false],
                ['tag' => '#professional', 'volume' => null, 'trending' => false],
                ['tag' => '#growth', 'volume' => null, 'trending' => false],
                ['tag' => '#success', 'volume' => null, 'trending' => false],
            ],
            'facebook' => [
                ['tag' => '#community', 'volume' => null, 'trending' => false],
                ['tag' => '#smallbusiness', 'volume' => null, 'trending' => false],
                ['tag' => '#local', 'volume' => null, 'trending' => false],
                ['tag' => '#events', 'volume' => null, 'trending' => false],
                ['tag' => '#support', 'volume' => null, 'trending' => false],
                ['tag' => '#together', 'volume' => null, 'trending' => false],
                ['tag' => '#family', 'volume' => null, 'trending' => false],
                ['tag' => '#friends', 'volume' => null, 'trending' => false],
            ],
            'tumblr' => [
                ['tag' => '#aesthetic', 'volume' => null, 'trending' => false],
                ['tag' => '#art', 'volume' => null, 'trending' => false],
                ['tag' => '#photography', 'volume' => null, 'trending' => false],
                ['tag' => '#creative', 'volume' => null, 'trending' => false],
                ['tag' => '#original', 'volume' => null, 'trending' => false],
                ['tag' => '#artists', 'volume' => null, 'trending' => false],
            ],
        ];

        return $defaults[$platform] ?? [
            ['tag' => '#trending', 'volume' => null, 'trending' => false],
            ['tag' => '#viral', 'volume' => null, 'trending' => false],
        ];
    }

    /**
     * Get simple hashtag list for backward compatibility
     *
     * @param string $platform
     * @param string|null $orgId
     * @return array Simple array of hashtag strings
     */
    public function getTrendingHashtagsList(string $platform, ?string $orgId = null): array
    {
        $hashtags = $this->getTrendingHashtags($platform, $orgId);

        return array_map(function ($item) {
            return is_array($item) ? $item['tag'] : $item;
        }, $hashtags);
    }

    /**
     * Get platform-specific content limits
     *
     * @param string $platform
     * @return array Content limits
     */
    public function getContentLimits(string $platform): array
    {
        return [
            'facebook' => [
                'caption_max' => 63206,
                'hashtags_max' => 30,
                'media_max' => 10,
                'video_max_size_mb' => 4000,
                'video_max_duration_sec' => 240 * 60, // 240 minutes
            ],
            'instagram' => [
                'caption_max' => 2200,
                'hashtags_max' => 30,
                'mentions_max' => 20,
                'media_max' => 10, // Carousel
                'video_max_size_mb' => 100,
                'video_max_duration_sec' => 60, // Feed: 60s, Reels: 90s
                'collaborators_max' => 3,
            ],
            'twitter' => [
                'text_max' => 280,
                'hashtags_max' => 2, // Recommended
                'mentions_max' => 50,
                'media_max' => 4,
                'video_max_size_mb' => 512,
                'video_max_duration_sec' => 140,
            ],
            'linkedin' => [
                'text_max' => 3000,
                'hashtags_max' => 3, // Recommended
                'media_max' => 9,
                'video_max_size_mb' => 5000,
                'video_max_duration_sec' => 600,
                'article_max' => 125000,
            ],
            'tiktok' => [
                'caption_max' => 2200,
                'hashtags_max' => 20,
                'video_max_size_mb' => 4000,
                'video_min_duration_sec' => 3,
                'video_max_duration_sec' => 600,
            ],
            'google_business' => [
                'post_max' => 1500,
                'cta_button_max' => 1, // One CTA per post
                'media_max' => 10,
            ],
        ];
    }

    /**
     * Get platform-specific media requirements
     *
     * @param string $platform
     * @return array Media requirements
     */
    public function getMediaRequirements(string $platform): array
    {
        return [
            'facebook' => [
                'image' => [
                    'min_width' => 600,
                    'min_height' => 315,
                    'aspect_ratio' => '1.91:1',
                    'formats' => ['jpg', 'png'],
                ],
                'video' => [
                    'min_width' => 600,
                    'aspect_ratios' => ['16:9', '9:16', '1:1', '4:5'],
                    'formats' => ['mp4', 'mov'],
                ],
            ],
            'instagram' => [
                'feed' => [
                    'aspect_ratios' => ['1:1', '4:5', '1.91:1'],
                    'min_width' => 1080,
                    'formats' => ['jpg', 'png'],
                ],
                'story' => [
                    'aspect_ratio' => '9:16',
                    'width' => 1080,
                    'height' => 1920,
                ],
                'reel' => [
                    'aspect_ratio' => '9:16',
                    'min_width' => 1080,
                    'duration' => '15-90s',
                ],
            ],
            'twitter' => [
                'image' => [
                    'min_width' => 600,
                    'aspect_ratios' => ['16:9', '1:1'],
                    'formats' => ['jpg', 'png', 'gif', 'webp'],
                ],
                'video' => [
                    'aspect_ratios' => ['16:9', '1:1'],
                    'formats' => ['mp4', 'mov'],
                ],
            ],
        ];
    }

    /**
     * Get best posting times for a platform (generic recommendations)
     *
     * @param string $platform
     * @return array Best times array
     */
    public function getBestPostingTimes(string $platform): array
    {
        // Generic best posting times based on industry research
        $bestTimes = [
            'facebook' => [
                ['day' => 'Monday', 'times' => ['09:00', '13:00', '15:00']],
                ['day' => 'Tuesday', 'times' => ['09:00', '13:00', '15:00']],
                ['day' => 'Wednesday', 'times' => ['09:00', '11:00', '13:00']],
                ['day' => 'Thursday', 'times' => ['09:00', '12:00', '15:00']],
                ['day' => 'Friday', 'times' => ['09:00', '13:00']],
            ],
            'instagram' => [
                ['day' => 'Monday', 'times' => ['06:00', '12:00', '19:00']],
                ['day' => 'Tuesday', 'times' => ['06:00', '12:00', '19:00']],
                ['day' => 'Wednesday', 'times' => ['07:00', '12:00', '19:00']],
                ['day' => 'Thursday', 'times' => ['07:00', '12:00', '19:00']],
                ['day' => 'Friday', 'times' => ['07:00', '12:00', '17:00']],
            ],
            'twitter' => [
                ['day' => 'Monday', 'times' => ['08:00', '12:00', '17:00', '21:00']],
                ['day' => 'Tuesday', 'times' => ['08:00', '12:00', '17:00']],
                ['day' => 'Wednesday', 'times' => ['08:00', '12:00', '17:00']],
                ['day' => 'Thursday', 'times' => ['08:00', '12:00', '17:00']],
                ['day' => 'Friday', 'times' => ['08:00', '12:00', '16:00']],
            ],
            'linkedin' => [
                ['day' => 'Monday', 'times' => ['08:00', '12:00', '17:00']],
                ['day' => 'Tuesday', 'times' => ['08:00', '10:00', '12:00']],
                ['day' => 'Wednesday', 'times' => ['08:00', '10:00', '12:00']],
                ['day' => 'Thursday', 'times' => ['08:00', '10:00']],
                ['day' => 'Friday', 'times' => ['08:00', '09:00']],
            ],
            'tiktok' => [
                ['day' => 'Monday', 'times' => ['06:00', '12:00', '19:00']],
                ['day' => 'Tuesday', 'times' => ['06:00', '12:00', '19:00']],
                ['day' => 'Wednesday', 'times' => ['07:00', '12:00', '19:00']],
                ['day' => 'Thursday', 'times' => ['07:00', '12:00', '19:00']],
                ['day' => 'Friday', 'times' => ['07:00', '16:00', '21:00']],
                ['day' => 'Saturday', 'times' => ['09:00', '12:00', '19:00']],
                ['day' => 'Sunday', 'times' => ['09:00', '12:00', '19:00']],
            ],
        ];

        return $bestTimes[$platform] ?? [];
    }

    /**
     * Validate platform-specific content
     *
     * @param string $platform
     * @param array $content Content to validate
     * @return array Validation result
     */
    public function validateContent(string $platform, array $content): array
    {
        $limits = $this->getContentLimits($platform);
        $errors = [];

        // Validate caption length
        if (isset($content['caption']) && isset($limits['caption_max'])) {
            $captionLength = mb_strlen($content['caption']);
            if ($captionLength > $limits['caption_max']) {
                $errors[] = sprintf(
                    __('publish.errors.caption_too_long'),
                    $captionLength,
                    $limits['caption_max']
                );
            }
        }

        // Validate hashtag count
        if (isset($content['caption']) && isset($limits['hashtags_max'])) {
            preg_match_all('/#\w+/', $content['caption'], $hashtags);
            $hashtagCount = count($hashtags[0]);
            if ($hashtagCount > $limits['hashtags_max']) {
                $errors[] = sprintf(
                    __('publish.errors.too_many_hashtags'),
                    $hashtagCount,
                    $limits['hashtags_max']
                );
            }
        }

        // Validate media count
        if (isset($content['media']) && isset($limits['media_max'])) {
            $mediaCount = count($content['media']);
            if ($mediaCount > $limits['media_max']) {
                $errors[] = sprintf(
                    __('publish.errors.too_many_media'),
                    $mediaCount,
                    $limits['media_max']
                );
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
