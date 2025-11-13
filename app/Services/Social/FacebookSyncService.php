<?php

namespace App\Services\Social;

use App\Models\{SocialAccount, SocialPost};
use Carbon\Carbon;

class FacebookSyncService extends AbstractSocialService
{
    protected function getConfiguration(): array
    {
        return [
            'api_base' => 'https://graph.facebook.com',
            'api_version' => 'v18.0',
            'fields' => [
                'page' => 'id,name,username,fan_count,followers_count,picture,link,verification_status',
                'posts' => 'id,message,created_time,permalink_url,likes.summary(true),comments.summary(true),shares',
            ]
        ];
    }

    public function syncAccount(): array
    {
        if (!$this->validateToken()) {
            return ['error' => 'Invalid or expired token'];
        }

        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/me";
        $params = ['fields' => $this->config['fields']['page']];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data)) {
            return ['error' => 'Failed to fetch page data'];
        }

        $account = SocialAccount::updateOrCreate(
            [
                'org_id' => $this->integration->org_id,
                'platform' => 'facebook',
                'platform_account_id' => $data['id']
            ],
            [
                'account_name' => $data['name'],
                'account_username' => $data['username'] ?? null,
                'profile_url' => $data['link'] ?? null,
                'profile_picture_url' => $data['picture']['data']['url'] ?? null,
                'followers_count' => $data['fan_count'] ?? 0,
                'is_verified' => $data['verification_status'] === 'blue_verified',
                'is_active' => true,
            ]
        );

        return [
            'success' => true,
            'account' => $account
        ];
    }

    public function syncPosts($from, $to, $limit = 25): array
    {
        $endpoint = "{$this->config['api_base']}/{$this->config['api_version']}/me/posts";
        $params = [
            'fields' => $this->config['fields']['posts'],
            'limit' => $limit
        ];

        $data = $this->makeRequest('get', $endpoint, $params);

        if (empty($data['data'])) {
            return ['posts' => []];
        }

        $posts = [];

        foreach ($data['data'] as $postData) {
            $timestamp = Carbon::parse($postData['created_time']);

            if ($from && $timestamp->lt($from)) continue;
            if ($to && $timestamp->gt($to)) continue;

            $post = SocialPost::updateOrCreate(
                [
                    'org_id' => $this->integration->org_id,
                    'platform' => 'facebook',
                    'platform_post_id' => $postData['id']
                ],
                [
                    'post_type' => 'status',
                    'content_text' => $postData['message'] ?? null,
                    'post_url' => $postData['permalink_url'] ?? null,
                    'published_at' => $timestamp,
                    'likes_count' => $postData['likes']['summary']['total_count'] ?? 0,
                    'comments_count' => $postData['comments']['summary']['total_count'] ?? 0,
                    'shares_count' => $postData['shares']['count'] ?? 0,
                ]
            );

            $posts[] = $post;
        }

        return ['posts' => $posts];
    }

    public function syncMetrics(array $postIds): array
    {
        // Facebook post metrics via Insights API
        return [];
    }

    public function refreshToken(): bool
    {
        // Facebook token refresh logic
        return true;
    }
}
