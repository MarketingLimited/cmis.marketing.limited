<?php

namespace App\Integrations\Meta;

use App\Integrations\Base\BaseApiClient;

/**
 * Meta (Facebook & Instagram) API Client
 *
 * Provides methods to interact with Facebook Graph API
 * Used by FacebookService and InstagramService
 */
class MetaApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://graph.facebook.com/v19.0';
    protected string $platform = 'meta';

    /**
     * Get Facebook page information
     *
     * @param string $pageId Facebook page ID
     * @param array $fields Fields to retrieve
     * @return array Page data
     */
    public function getPage(string $pageId, array $fields = []): array
    {
        $defaultFields = ['id', 'name', 'about', 'category', 'fan_count', 'followers_count'];
        $fields = empty($fields) ? $defaultFields : $fields;

        return $this->request('get', "/{$pageId}", [
            'fields' => implode(',', $fields)
        ]);
    }

    /**
     * Publish post to Facebook page
     *
     * @param string $pageId Facebook page ID
     * @param array $data Post data
     * @return array Post response with ID
     */
    public function publishPost(string $pageId, array $data): array
    {
        return $this->request('post', "/{$pageId}/feed", [
            'message' => $data['message'] ?? '',
            'link' => $data['link'] ?? null,
            'published' => $data['published'] ?? true,
        ]);
    }

    /**
     * Get post insights/metrics
     *
     * @param string $postId Facebook post ID
     * @param array $metrics Metrics to retrieve
     * @return array Insights data
     */
    public function getPostInsights(string $postId, array $metrics = []): array
    {
        $defaultMetrics = ['post_impressions', 'post_engaged_users', 'post_clicks'];
        $metrics = empty($metrics) ? $defaultMetrics : $metrics;

        return $this->request('get', "/{$postId}/insights", [
            'metric' => implode(',', $metrics)
        ]);
    }

    /**
     * Get Instagram business account
     *
     * @param string $pageId Facebook page ID
     * @return array Instagram account data
     */
    public function getInstagramAccount(string $pageId): array
    {
        return $this->request('get', "/{$pageId}", [
            'fields' => 'instagram_business_account'
        ]);
    }

    /**
     * Publish Instagram media (image/video)
     *
     * @param string $igAccountId Instagram business account ID
     * @param array $data Media data
     * @return array Media creation response
     */
    public function createInstagramMedia(string $igAccountId, array $data): array
    {
        $params = [
            'image_url' => $data['image_url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'caption' => $data['caption'] ?? '',
            'media_type' => $data['media_type'] ?? 'IMAGE',
        ];

        // Create media container
        $container = $this->request('post', "/{$igAccountId}/media", array_filter($params));

        // Publish media
        return $this->request('post', "/{$igAccountId}/media_publish", [
            'creation_id' => $container['id']
        ]);
    }

    /**
     * Get Instagram media insights
     *
     * @param string $mediaId Instagram media ID
     * @param array $metrics Metrics to retrieve
     * @return array Insights data
     */
    public function getInstagramMediaInsights(string $mediaId, array $metrics = []): array
    {
        $defaultMetrics = ['impressions', 'reach', 'engagement', 'saved'];
        $metrics = empty($metrics) ? $defaultMetrics : $metrics;

        return $this->request('get', "/{$mediaId}/insights", [
            'metric' => implode(',', $metrics)
        ]);
    }

    /**
     * Get comments on a post/media
     *
     * @param string $objectId Post or media ID
     * @param int $limit Number of comments to retrieve
     * @return array Comments data
     */
    public function getComments(string $objectId, int $limit = 25): array
    {
        return $this->request('get', "/{$objectId}/comments", [
            'limit' => $limit,
            'fields' => 'id,from,message,created_time,like_count'
        ]);
    }

    /**
     * Reply to a comment
     *
     * @param string $commentId Comment ID
     * @param string $message Reply message
     * @return array Reply response
     */
    public function replyToComment(string $commentId, string $message): array
    {
        return $this->request('post', "/{$commentId}/comments", [
            'message' => $message
        ]);
    }

    /**
     * Delete a comment
     *
     * @param string $commentId Comment ID
     * @return array Deletion response
     */
    public function deleteComment(string $commentId): array
    {
        return $this->request('delete', "/{$commentId}");
    }
}
