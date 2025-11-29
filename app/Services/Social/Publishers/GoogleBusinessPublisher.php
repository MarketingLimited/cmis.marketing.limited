<?php

namespace App\Services\Social\Publishers;

use Illuminate\Support\Facades\Http;

/**
 * Publisher for Google Business Profile.
 */
class GoogleBusinessPublisher extends AbstractPublisher
{
    protected const API_BASE = 'https://mybusiness.googleapis.com/v4/';

    /**
     * Publish content to Google Business Profile.
     */
    public function publish(string $content, array $media, array $options = []): array
    {
        $selectedAssets = $this->getSelectedAssets();
        $selectedLocations = $selectedAssets['locations'] ?? [];

        if (empty($selectedLocations)) {
            return $this->failure('No Google Business location selected. Configure in Settings > Platform Connections > Google > Assets.');
        }

        $locationId = $selectedLocations[0];

        try {
            $postData = $this->buildPostData($content, $media, $options);

            $response = Http::withToken($this->getAccessToken())
                ->timeout(60)
                ->post(self::API_BASE . "{$locationId}/localPosts", $postData);

            if ($response->successful()) {
                $postId = $response->json('name');
                return $this->success($postId, $response->json('searchUrl'));
            }

            return $this->failure($response->json('error.message', 'Failed to publish to Google Business'));
        } catch (\Exception $e) {
            $this->logError('Google Business publish failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }
    }

    /**
     * Build the post data structure.
     */
    protected function buildPostData(string $content, array $media, array $options): array
    {
        $postData = [
            'languageCode' => 'en',
            'summary' => $content,
            'topicType' => $options['post_type'] ?? 'STANDARD',
        ];

        // Add media if available
        if (!empty($media)) {
            $firstMedia = $media[0];
            $mediaUrl = $firstMedia['url'] ?? $firstMedia['preview_url'] ?? null;
            if ($mediaUrl) {
                $postData['media'] = [
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl' => $mediaUrl,
                ];
            }
        }

        // Add CTA if specified
        if (!empty($options['cta_type']) && !empty($options['cta_url'])) {
            $postData['callToAction'] = [
                'actionType' => $options['cta_type'],
                'url' => $options['cta_url'],
            ];
        }

        // Add event details for event posts
        if (($options['post_type'] ?? '') === 'EVENT') {
            $postData['event'] = [
                'title' => $options['event_title'] ?? $content,
                'schedule' => [
                    'startDate' => $this->parseDate($options['event_start_date'] ?? null),
                    'startTime' => $this->parseTime($options['event_start_time'] ?? null),
                    'endDate' => $this->parseDate($options['event_end_date'] ?? null),
                    'endTime' => $this->parseTime($options['event_end_time'] ?? null),
                ],
            ];
        }

        // Add offer details for offer posts
        if (($options['post_type'] ?? '') === 'OFFER') {
            $postData['offer'] = [
                'couponCode' => $options['offer_coupon_code'] ?? null,
                'redeemOnlineUrl' => $options['offer_redeem_url'] ?? null,
                'termsConditions' => $options['offer_terms_conditions'] ?? null,
            ];
        }

        return $postData;
    }

    /**
     * Parse date string to Google API format.
     */
    protected function parseDate(?string $date): ?array
    {
        if (!$date) {
            return null;
        }

        $parsed = \Carbon\Carbon::parse($date);
        return [
            'year' => $parsed->year,
            'month' => $parsed->month,
            'day' => $parsed->day,
        ];
    }

    /**
     * Parse time string to Google API format.
     */
    protected function parseTime(?string $time): ?array
    {
        if (!$time) {
            return null;
        }

        $parsed = \Carbon\Carbon::parse($time);
        return [
            'hours' => $parsed->hour,
            'minutes' => $parsed->minute,
        ];
    }
}
