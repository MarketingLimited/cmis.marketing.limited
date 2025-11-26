<?php

namespace App\Services\Social\Twitter;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * X (Twitter) API v2 Publishing Service
 *
 * API: Twitter API v2
 * Pricing: Free ($0), Basic ($100/mo), Pro ($5,000/mo), Enterprise (custom)
 *
 * Supports:
 * - Tweet posting (up to 280 characters, 4,000 for Premium+)
 * - Thread creation (multiple connected tweets)
 * - Media upload (images, videos, GIFs)
 * - Poll creation (2-4 options, 5min-7days)
 * - Reply controls (everyone, mentions, followers)
 * - Quote tweets
 *
 * Authentication: OAuth 2.0 with PKCE
 * Base URL: https://api.twitter.com/2/
 */
class TwitterSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = '2';
    protected string $baseUrl = 'https://api.twitter.com';
    protected string $uploadUrl = 'https://upload.twitter.com/1.1';

    protected function getPlatformName(): string
    {
        return 'twitter';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'tweet';

        return match($postType) {
            'tweet' => $this->publishTweet($content),
            'thread' => $this->publishThread($content),
            'poll' => $this->publishPoll($content),
            default => throw new \Exception("Unsupported Twitter post type: {$postType}"),
        };
    }

    /**
     * Publish single tweet
     */
    protected function publishTweet(array $content): array
    {
        $text = $content['text'] ?? '';
        $mediaFiles = $content['media_files'] ?? [];
        $mediaUrls = $content['media_urls'] ?? [];
        $replySettings = $content['reply_settings'] ?? null; // 'everyone', 'mentionedUsers', 'followers'
        $quoteTweetId = $content['quote_tweet_id'] ?? null;

        $tweetData = [
            'text' => $text,
        ];

        // Upload and attach media
        if (!empty($mediaFiles) || !empty($mediaUrls)) {
            $mediaIds = $this->uploadAllMedia(array_merge($mediaFiles, $mediaUrls));
            $tweetData['media'] = [
                'media_ids' => $mediaIds,
            ];
        }

        // Reply settings
        if ($replySettings) {
            $tweetData['reply_settings'] = $replySettings;
        }

        // Quote tweet
        if ($quoteTweetId) {
            $tweetData['quote_tweet_id'] = $quoteTweetId;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/tweets",
            $tweetData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $tweetId = $response['data']['id'] ?? null;
        $tweetUrl = "https://twitter.com/i/status/{$tweetId}";

        $this->logOperation('publish_tweet', [
            'tweet_id' => $tweetId,
            'has_media' => !empty($mediaIds ?? []),
        ]);

        return [
            'external_id' => $tweetId,
            'url' => $tweetUrl,
            'platform_data' => $response['data'] ?? [],
        ];
    }

    /**
     * Publish thread (multiple connected tweets)
     */
    protected function publishThread(array $content): array
    {
        $tweets = $content['tweets'] ?? [];

        if (empty($tweets)) {
            throw new \InvalidArgumentException('Thread must have at least one tweet');
        }

        $publishedTweets = [];
        $previousTweetId = null;

        foreach ($tweets as $index => $tweetContent) {
            $tweetData = [
                'text' => $tweetContent['text'] ?? '',
            ];

            // Reply to previous tweet to create thread
            if ($previousTweetId) {
                $tweetData['reply'] = [
                    'in_reply_to_tweet_id' => $previousTweetId,
                ];
            }

            // Upload media for this tweet if provided
            if (!empty($tweetContent['media_files'] ?? [])) {
                $mediaIds = $this->uploadAllMedia($tweetContent['media_files']);
                $tweetData['media'] = [
                    'media_ids' => $mediaIds,
                ];
            }

            // Set reply settings on first tweet only
            if ($index === 0 && isset($content['reply_settings'])) {
                $tweetData['reply_settings'] = $content['reply_settings'];
            }

            $response = $this->makeRequest(
                'post',
                "{$this->baseUrl}/{$this->apiVersion}/tweets",
                $tweetData,
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $tweetId = $response['data']['id'] ?? null;
            $publishedTweets[] = [
                'id' => $tweetId,
                'url' => "https://twitter.com/i/status/{$tweetId}",
            ];

            $previousTweetId = $tweetId;

            // Rate limiting: Wait between tweets
            if ($index < count($tweets) - 1) {
                usleep(500000); // 0.5 second delay
            }
        }

        $this->logOperation('publish_thread', [
            'thread_length' => count($publishedTweets),
            'first_tweet_id' => $publishedTweets[0]['id'] ?? null,
        ]);

        return [
            'external_id' => $publishedTweets[0]['id'] ?? null,
            'url' => $publishedTweets[0]['url'] ?? null,
            'platform_data' => [
                'thread' => $publishedTweets,
                'tweet_count' => count($publishedTweets),
            ],
        ];
    }

    /**
     * Publish poll tweet
     */
    protected function publishPoll(array $content): array
    {
        $text = $content['text'] ?? '';
        $options = $content['poll_options'] ?? [];
        $durationMinutes = $content['poll_duration_minutes'] ?? 1440; // Default 24 hours

        if (count($options) < 2 || count($options) > 4) {
            throw new \InvalidArgumentException('Twitter polls must have 2-4 options');
        }

        if ($durationMinutes < 5 || $durationMinutes > 10080) { // 5 min - 7 days
            throw new \InvalidArgumentException('Poll duration must be 5-10,080 minutes (5min-7days)');
        }

        $tweetData = [
            'text' => $text,
            'poll' => [
                'options' => array_map(fn($option) => ['label' => $option], $options),
                'duration_minutes' => $durationMinutes,
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/tweets",
            $tweetData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $tweetId = $response['data']['id'] ?? null;

        return [
            'external_id' => $tweetId,
            'url' => "https://twitter.com/i/status/{$tweetId}",
            'platform_data' => $response['data'] ?? [],
        ];
    }

    /**
     * Upload multiple media files
     */
    protected function uploadAllMedia(array $mediaFiles): array
    {
        $mediaIds = [];

        foreach ($mediaFiles as $mediaFile) {
            $mediaIds[] = $this->uploadMediaFile($mediaFile);
        }

        return $mediaIds;
    }

    /**
     * Upload single media file using v1.1 upload API
     */
    protected function uploadMediaFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Media file not found: {$filePath}");
        }

        $mediaContent = file_get_contents($filePath);
        $mediaType = mime_content_type($filePath);
        $mediaCategory = $this->getMediaCategory($mediaType);

        // Step 1: INIT
        $initResponse = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->post("{$this->uploadUrl}/media/upload.json", [
                'command' => 'INIT',
                'total_bytes' => strlen($mediaContent),
                'media_type' => $mediaType,
                'media_category' => $mediaCategory,
            ]);

        if (!$initResponse->successful()) {
            throw new \Exception('Media upload INIT failed: ' . $initResponse->body());
        }

        $mediaId = $initResponse->json('media_id_string');

        // Step 2: APPEND
        $response = Http::asMultipart()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->post("{$this->uploadUrl}/media/upload.json", [
                [
                    'name' => 'command',
                    'contents' => 'APPEND',
                ],
                [
                    'name' => 'media_id',
                    'contents' => $mediaId,
                ],
                [
                    'name' => 'segment_index',
                    'contents' => '0',
                ],
                [
                    'name' => 'media',
                    'contents' => $mediaContent,
                    'filename' => basename($filePath),
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Media upload APPEND failed: ' . $response->body());
        }

        // Step 3: FINALIZE
        $finalizeResponse = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->post("{$this->uploadUrl}/media/upload.json", [
                'command' => 'FINALIZE',
                'media_id' => $mediaId,
            ]);

        if (!$finalizeResponse->successful()) {
            throw new \Exception('Media upload FINALIZE failed: ' . $finalizeResponse->body());
        }

        // Step 4: Check processing status if needed (for videos)
        $processingInfo = $finalizeResponse->json('processing_info');
        if ($processingInfo) {
            $this->waitForMediaProcessing($mediaId);
        }

        return $mediaId;
    }

    /**
     * Wait for media processing to complete
     */
    protected function waitForMediaProcessing(string $mediaId, int $maxAttempts = 20): void
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $statusResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->get("{$this->uploadUrl}/media/upload.json", [
                'command' => 'STATUS',
                'media_id' => $mediaId,
            ]);

            $processingInfo = $statusResponse->json('processing_info');

            if (!$processingInfo || $processingInfo['state'] === 'succeeded') {
                return;
            }

            if ($processingInfo['state'] === 'failed') {
                throw new \Exception('Media processing failed');
            }

            $checkAfterSecs = $processingInfo['check_after_secs'] ?? 5;
            sleep($checkAfterSecs);

            $attempts++;
        }

        throw new \Exception('Media processing timeout');
    }

    /**
     * Get media category based on MIME type
     */
    protected function getMediaCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/gif')) {
            return 'tweet_gif';
        }

        if (str_starts_with($mimeType, 'image/')) {
            return 'tweet_image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'tweet_video';
        }

        return 'tweet_image';
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // Twitter API v2 doesn't support native scheduling for regular API access
        // Scheduling requires Ads API or third-party tools
        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'queue',
        ];
    }

    public function validateContent(array $content): bool
    {
        $postType = $content['post_type'] ?? 'tweet';

        if ($postType === 'tweet') {
            $this->validateRequiredFields($content, ['text']);
            $this->validateTextLength($content['text'], 280, 'tweet');
        }

        if ($postType === 'thread') {
            if (!isset($content['tweets']) || empty($content['tweets'])) {
                throw new \InvalidArgumentException('Thread must have at least one tweet');
            }

            foreach ($content['tweets'] as $tweet) {
                if (!isset($tweet['text']) || empty($tweet['text'])) {
                    throw new \InvalidArgumentException('Each tweet in thread must have text');
                }
                $this->validateTextLength($tweet['text'], 280, 'tweet');
            }
        }

        if ($postType === 'poll') {
            $this->validateRequiredFields($content, ['text', 'poll_options']);
            $options = $content['poll_options'];

            if (count($options) < 2 || count($options) > 4) {
                throw new \InvalidArgumentException('Poll must have 2-4 options');
            }

            foreach ($options as $option) {
                if (mb_strlen($option) > 25) {
                    throw new \InvalidArgumentException('Poll option too long (max 25 characters)');
                }
            }
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'tweet',
                'label' => 'تغريدة',
                'icon' => 'fa-twitter',
                'description' => 'Single tweet (280 characters)',
            ],
            [
                'value' => 'thread',
                'label' => 'سلسلة',
                'icon' => 'fa-list',
                'description' => 'Connected tweet thread',
            ],
            [
                'value' => 'poll',
                'label' => 'استطلاع',
                'icon' => 'fa-poll',
                'description' => 'Poll with 2-4 options (5min-7days)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'image' => [
                'formats' => ['JPEG', 'PNG', 'WEBP'],
                'max_size_mb' => 5,
                'max_count_per_tweet' => 4,
                'max_pixels' => '8192x8192',
            ],
            'gif' => [
                'formats' => ['GIF'],
                'max_size_mb' => 15,
                'max_count_per_tweet' => 1,
            ],
            'video' => [
                'formats' => ['MP4', 'MOV'],
                'max_size_mb' => 512,
                'max_duration_seconds' => 140,
                'min_duration_seconds' => 0.5,
                'max_count_per_tweet' => 1,
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'tweet' => ['min' => 1, 'max' => 280],
            'thread' => ['min' => 1, 'max' => 280], // Per tweet
            'poll' => [
                'text' => ['min' => 1, 'max' => 280],
                'option' => ['min' => 1, 'max' => 25],
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        return $this->uploadMediaFile($filePath);
    }

    /**
     * Get tweet analytics
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/tweets/{$externalPostId}",
                [
                    'tweet.fields' => 'public_metrics',
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $metrics = $response['data']['public_metrics'] ?? [];

            return [
                'retweets' => $metrics['retweet_count'] ?? 0,
                'replies' => $metrics['reply_count'] ?? 0,
                'likes' => $metrics['like_count'] ?? 0,
                'quotes' => $metrics['quote_count'] ?? 0,
                'impressions' => $metrics['impression_count'] ?? 0,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['tweet_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Delete tweet
     */
    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/tweets/{$externalPostId}",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $this->logOperation('delete', ['tweet_id' => $externalPostId]);
            return true;
        } catch (\Exception $e) {
            $this->logError('delete', $e, ['tweet_id' => $externalPostId]);
            return false;
        }
    }
}
