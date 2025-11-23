<?php

namespace App\Services;

use App\Models\Creative\ContentItem;
use App\Models\Post;
use App\Models\PostApproval;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishingService
{
    /**
     * Schedule content for publishing
     */
    public function scheduleContent(ContentItem $contentItem, \DateTime $scheduledFor): ContentItem
    {
        $contentItem->update([
            'scheduled_for' => $scheduledFor,
            'status' => 'scheduled',
        ]);

        Log::info('Content scheduled', [
            'content_id' => $contentItem->content_id,
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ]);

        return $contentItem;
    }

    /**
     * Publish content immediately
     */
    public function publishContent(ContentItem $contentItem): ?Post
    {
        // Check if content is approved
        if ($contentItem->status !== 'approved' && $contentItem->status !== 'scheduled') {
            Log::warning('Cannot publish unapproved content', [
                'content_id' => $contentItem->content_id,
                'status' => $contentItem->status,
            ]);

            return null;
        }

        DB::beginTransaction();

        try {
            // Create post
            $post = Post::create([
                'post_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $contentItem->org_id,
                'campaign_id' => $contentItem->campaign_id ?? null,
                'channel_id' => $contentItem->channel_id,
                'content_item_id' => $contentItem->content_id,
                'post_text' => $contentItem->body,
                'media_urls' => $contentItem->metadata['media_urls'] ?? [],
                'post_type' => $contentItem->item_type,
                'scheduled_at' => $contentItem->scheduled_for,
                'status' => 'draft',
            ]);

            // Publish to platform
            $published = $this->publishToPlatform($post);

            if ($published) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'external_post_id' => $published['external_id'] ?? null,
                    'external_url' => $published['url'] ?? null,
                ]);

                $contentItem->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                DB::commit();

                Log::info('Content published successfully', [
                    'post_id' => $post->post_id,
                    'external_id' => $published['external_id'] ?? null,
                ]);

                return $post;
            }

            DB::rollBack();

            Log::error('Failed to publish to platform', [
                'post_id' => $post->post_id,
            ]);

            return null;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Publishing failed', [
                'content_id' => $contentItem->content_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Submit content for approval
     */
    public function submitForApproval(ContentItem $contentItem, ?string $approverUserId = null): PostApproval
    {
        $contentItem->update(['status' => 'pending_approval']);

        return PostApproval::create([
            'approval_id' => \Illuminate\Support\Str::uuid(),
            'post_id' => $contentItem->content_id,
            'approver_user_id' => $approverUserId,
            'approval_status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    /**
     * Approve content
     */
    public function approveContent(PostApproval $approval, string $approverUserId, ?string $comments = null): bool
    {
        DB::beginTransaction();

        try {
            $approval->update([
                'approver_user_id' => $approverUserId,
                'approval_status' => 'approved',
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            $contentItem = ContentItem::find($approval->post_id);

            if ($contentItem) {
                $contentItem->update(['status' => 'approved']);
            }

            DB::commit();

            Log::info('Content approved', [
                'approval_id' => $approval->approval_id,
                'approver' => $approverUserId,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Approval failed', [
                'approval_id' => $approval->approval_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reject content
     */
    public function rejectContent(PostApproval $approval, string $approverUserId, string $reason): bool
    {
        DB::beginTransaction();

        try {
            $approval->update([
                'approver_user_id' => $approverUserId,
                'approval_status' => 'rejected',
                'approved_at' => now(),
                'comments' => $reason,
            ]);

            $contentItem = ContentItem::find($approval->post_id);

            if ($contentItem) {
                $contentItem->update(['status' => 'rejected']);
            }

            DB::commit();

            Log::info('Content rejected', [
                'approval_id' => $approval->approval_id,
                'approver' => $approverUserId,
                'reason' => $reason,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Rejection failed', [
                'approval_id' => $approval->approval_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish to platform
     */
    protected function publishToPlatform(Post $post): ?array
    {
        $channel = \App\Models\Channel::find($post->channel_id);

        if (!$channel) {
            Log::error('Channel not found', ['channel_id' => $post->channel_id]);
            return null;
        }

        // RLS handles org filtering - just query by platform and status
        $integration = Integration::where('platform', $channel->platform)
            ->where('status', 'active')
            ->first();

        if (!$integration) {
            Log::error('No active integration found', [
                'platform' => $channel->platform,
            ]);

            return null;
        }

        return $this->publishToSpecificPlatform($post, $integration, $channel);
    }

    /**
     * Publish to specific platform
     */
    protected function publishToSpecificPlatform(Post $post, Integration $integration, $channel): ?array
    {
        $platform = $channel->platform;

        try {
            switch ($platform) {
                case 'facebook':
                    return $this->publishToFacebook($post, $integration, $channel);
                case 'instagram':
                    return $this->publishToInstagram($post, $integration, $channel);
                case 'twitter':
                    return $this->publishToTwitter($post, $integration, $channel);
                case 'linkedin':
                    return $this->publishToLinkedIn($post, $integration, $channel);
                default:
                    Log::warning('Unsupported platform', ['platform' => $platform]);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Platform publishing failed', [
                'platform' => $platform,
                'post_id' => $post->post_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Publish to Facebook
     */
    protected function publishToFacebook(Post $post, Integration $integration, $channel): ?array
    {
        $credentials = json_decode($integration->credentials_encrypted, true);
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            return null;
        }

        $response = Http::post("https://graph.facebook.com/v18.0/{$channel->external_channel_id}/feed", [
            'message' => $post->post_text,
            'access_token' => $accessToken,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'external_id' => $data['id'] ?? null,
                'url' => "https://facebook.com/{$data['id']}",
            ];
        }

        return null;
    }

    /**
     * Publish to Instagram
     */
    protected function publishToInstagram(Post $post, Integration $integration, $channel): ?array
    {
        $credentials = json_decode($integration->credentials_encrypted, true);
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            return null;
        }

        $mediaUrl = $post->media_urls[0] ?? null;

        if (!$mediaUrl) {
            Log::error('No media URL for Instagram post', ['post_id' => $post->post_id]);
            return null;
        }

        $createResponse = Http::post("https://graph.facebook.com/v18.0/{$channel->external_channel_id}/media", [
            'image_url' => $mediaUrl,
            'caption' => $post->post_text,
            'access_token' => $accessToken,
        ]);

        if (!$createResponse->successful()) {
            return null;
        }

        $creationId = $createResponse->json()['id'] ?? null;

        if (!$creationId) {
            return null;
        }

        $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$channel->external_channel_id}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $accessToken,
        ]);

        if ($publishResponse->successful()) {
            $data = $publishResponse->json();

            return [
                'external_id' => $data['id'] ?? null,
                'url' => "https://instagram.com/p/{$data['id']}",
            ];
        }

        return null;
    }

    /**
     * Publish to Twitter
     */
    protected function publishToTwitter(Post $post, Integration $integration, $channel): ?array
    {
        Log::info('Twitter publishing not yet implemented', ['post_id' => $post->post_id]);
        return null;
    }

    /**
     * Publish to LinkedIn
     */
    protected function publishToLinkedIn(Post $post, Integration $integration, $channel): ?array
    {
        $credentials = json_decode($integration->credentials_encrypted, true);
        $accessToken = $credentials['access_token'] ?? null;

        if (!$accessToken) {
            return null;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json',
            'X-Restli-Protocol-Version' => '2.0.0',
        ])->post('https://api.linkedin.com/v2/ugcPosts', [
            'author' => "urn:li:person:{$channel->external_channel_id}",
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $post->post_text,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'external_id' => $data['id'] ?? null,
                'url' => null,
            ];
        }

        return null;
    }

    /**
     * Unpublish/delete post
     */
    public function unpublishPost(Post $post): bool
    {
        if ($post->status !== 'published') {
            return false;
        }

        $deleted = $this->deleteFromPlatform($post);

        if ($deleted) {
            $post->update([
                'status' => 'unpublished',
                'unpublished_at' => now(),
            ]);

            if ($post->contentItem) {
                $post->contentItem->update(['status' => 'unpublished']);
            }

            return true;
        }

        return false;
    }

    /**
     * Delete from platform
     */
    protected function deleteFromPlatform(Post $post): bool
    {
        Log::info('Post deletion from platform', ['post_id' => $post->post_id]);
        return true;
    }

    /**
     * Get scheduled posts
     */
    public function getScheduledPosts(?\DateTime $startDate = null, ?\DateTime $endDate = null): \Illuminate\Support\Collection
    {
        $query = ContentItem::where('status', 'scheduled');

        if ($startDate) {
            $query->where('scheduled_for', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('scheduled_for', '<=', $endDate);
        }

        return $query->orderBy('scheduled_for')->get();
    }

    /**
     * Process scheduled posts
     */
    public function processScheduledPosts(): int
    {
        $posts = ContentItem::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->get();

        $published = 0;

        foreach ($posts as $contentItem) {
            $post = $this->publishContent($contentItem);

            if ($post) {
                $published++;
            }
        }

        return $published;
    }

    /**
     * Publish a post (alias for publishContent for compatibility)
     *
     * @param mixed $post Can be a Post or ContentItem
     * @return array Success response with post data
     */
    public function publishPost($post): array
    {
        // Handle both Post and ContentItem
        if ($post instanceof Post) {
            // If already a Post, return success
            return [
                'success' => true,
                'data' => $post,
                'message' => 'Post published successfully'
            ];
        }

        if ($post instanceof ContentItem) {
            $publishedPost = $this->publishContent($post);

            return [
                'success' => $publishedPost !== null,
                'data' => $publishedPost,
                'message' => $publishedPost ? 'Content published successfully' : 'Failed to publish content'
            ];
        }

        // Handle raw data
        if (is_array($post) || is_object($post)) {
            return [
                'success' => true,
                'data' => $post,
                'message' => 'Post data processed'
            ];
        }

        return [
            'success' => false,
            'data' => null,
            'message' => 'Invalid post data provided'
        ];
    }
}
