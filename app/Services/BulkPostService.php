<?php

namespace App\Services;

use App\Repositories\Contracts\SocialMediaRepositoryInterface;
use App\Services\Embedding\EmbeddingOrchestrator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for bulk post creation (Buffer-style bulk composer)
 * Implements Sprint 2.2: Bulk Compose
 *
 * Features:
 * - Create multiple posts from template
 * - AI-powered content variations
 * - Automatic scheduling across accounts
 * - CSV/Excel import support
 * - Bulk editing and deletion
 */
class BulkPostService
{
    protected SocialMediaRepositoryInterface $socialMediaRepo;
    protected PublishingQueueService $queueService;
    protected EmbeddingOrchestrator $embeddingService;

    public function __construct(
        SocialMediaRepositoryInterface $socialMediaRepo,
        PublishingQueueService $queueService,
        EmbeddingOrchestrator $embeddingService
    ) {
        $this->socialMediaRepo = $socialMediaRepo;
        $this->queueService = $queueService;
        $this->embeddingService = $embeddingService;
    }

    /**
     * Create multiple posts from a template
     *
     * @param string $orgId
     * @param array $template Base post data
     * @param array $accounts Target social accounts
     * @param array $options Additional options (schedule, variations, etc.)
     * @return array Created posts with scheduling info
     */
    public function createBulkPosts(
        string $orgId,
        array $template,
        array $accounts,
        array $options = []
    ): array {
        $createdPosts = [];
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($accounts as $accountId) {
                try {
                    // Generate content variation if AI is enabled
                    $content = $template['content'];
                    if ($options['use_ai_variations'] ?? false) {
                        $content = $this->generateContentVariation(
                            $template['content'],
                            $accountId,
                            $options['variation_style'] ?? 'moderate'
                        );
                    }

                    // Create the post
                    $postId = Str::uuid()->toString();
                    $post = $this->createSocialPost($orgId, $postId, [
                        'content' => $content,
                        'platform' => $template['platform'] ?? 'facebook',
                        'post_type' => $template['post_type'] ?? 'text',
                        'media_urls' => $template['media_urls'] ?? [],
                        'hashtags' => $template['hashtags'] ?? [],
                        'social_account_id' => $accountId
                    ]);

                    // Schedule to queue if requested
                    $scheduledFor = null;
                    if ($options['auto_schedule'] ?? false) {
                        $scheduledFor = $this->queueService->getNextAvailableSlot($accountId);
                        if ($scheduledFor) {
                            $this->queueService->schedulePost($postId, $accountId, $scheduledFor);
                        }
                    }

                    $createdPosts[] = [
                        'post_id' => $postId,
                        'social_account_id' => $accountId,
                        'content' => $content,
                        'scheduled_for' => $scheduledFor?->format('Y-m-d H:i:s'),
                        'status' => 'created'
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'social_account_id' => $accountId,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Failed to create bulk post', [
                        'account_id' => $accountId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'created' => count($createdPosts),
                'failed' => count($errors),
                'posts' => $createdPosts,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk post creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create multiple posts from CSV data
     *
     * @param string $orgId
     * @param array $csvData Array of post data from CSV
     * @param array $options
     * @return array
     */
    public function createFromCSV(string $orgId, array $csvData, array $options = []): array
    {
        $createdPosts = [];
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($csvData as $index => $row) {
                try {
                    // Validate required fields
                    if (!isset($row['content']) || !isset($row['social_account_id'])) {
                        throw new \InvalidArgumentException('Missing required fields: content, social_account_id');
                    }

                    $postId = Str::uuid()->toString();
                    $post = $this->createSocialPost($orgId, $postId, [
                        'content' => $row['content'],
                        'platform' => $row['platform'] ?? 'facebook',
                        'post_type' => $row['post_type'] ?? 'text',
                        'media_urls' => isset($row['media_urls']) ? json_decode($row['media_urls'], true) : [],
                        'hashtags' => isset($row['hashtags']) ? explode(',', $row['hashtags']) : [],
                        'social_account_id' => $row['social_account_id']
                    ]);

                    // Schedule if date provided
                    if (isset($row['scheduled_for']) && !empty($row['scheduled_for'])) {
                        try {
                            $scheduledFor = new \DateTime($row['scheduled_for']);
                            $this->queueService->schedulePost(
                                $postId,
                                $row['social_account_id'],
                                $scheduledFor
                            );
                        } catch (\Exception $e) {
                            Log::warning('Invalid schedule date in CSV', [
                                'row' => $index,
                                'date' => $row['scheduled_for']
                            ]);
                        }
                    }

                    $createdPosts[] = [
                        'row' => $index + 1,
                        'post_id' => $postId,
                        'status' => 'created'
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'processed' => count($csvData),
                'created' => count($createdPosts),
                'failed' => count($errors),
                'posts' => $createdPosts,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update multiple posts at once
     *
     * @param array $postIds
     * @param array $updates
     * @return array
     */
    public function bulkUpdate(array $postIds, array $updates): array
    {
        $updated = [];
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($postIds as $postId) {
                try {
                    DB::table('cmis.social_posts')
                        ->where('post_id', $postId)
                        ->update(array_merge($updates, ['updated_at' => now()]));

                    $updated[] = $postId;

                } catch (\Exception $e) {
                    $errors[] = [
                        'post_id' => $postId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'updated' => count($updated),
                'failed' => count($errors),
                'post_ids' => $updated,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete multiple posts
     *
     * @param array $postIds
     * @return array
     */
    public function bulkDelete(array $postIds): array
    {
        $deleted = [];
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($postIds as $postId) {
                try {
                    // Only delete draft or scheduled posts, not published ones
                    $result = DB::table('cmis.social_posts')
                        ->where('post_id', $postId)
                        ->whereIn('status', ['draft', 'scheduled', 'queued'])
                        ->delete();

                    if ($result > 0) {
                        $deleted[] = $postId;
                    } else {
                        $errors[] = [
                            'post_id' => $postId,
                            'error' => 'Post not found or already published'
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'post_id' => $postId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return [
                'success' => true,
                'deleted' => count($deleted),
                'failed' => count($errors),
                'post_ids' => $deleted,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate AI-powered content variation
     *
     * @param string $originalContent
     * @param string $accountId
     * @param string $style moderate|creative|conservative
     * @return string
     */
    protected function generateContentVariation(
        string $originalContent,
        string $accountId,
        string $style = 'moderate'
    ): string {
        try {
            // For now, return variations based on style
            // In production, this would call Gemini API for AI generation

            switch ($style) {
                case 'creative':
                    // Add emojis, exclamations
                    return $originalContent . ' 🎉';

                case 'conservative':
                    // Keep it professional
                    return $originalContent;

                case 'moderate':
                default:
                    // Slight variation
                    return $originalContent;
            }

            // TODO: Implement actual AI variation using Gemini
            // $prompt = "Rewrite this social media post with a {$style} tone: {$originalContent}";
            // return $this->callGeminiAPI($prompt);

        } catch (\Exception $e) {
            Log::warning('AI variation failed, using original content', [
                'error' => $e->getMessage()
            ]);
            return $originalContent;
        }
    }

    /**
     * Create a social media post record
     *
     * @param string $orgId
     * @param string $postId
     * @param array $data
     * @return object
     */
    protected function createSocialPost(string $orgId, string $postId, array $data): object
    {
        DB::table('cmis.social_posts')->insert([
            'post_id' => $postId,
            'org_id' => $orgId,
            'social_account_id' => $data['social_account_id'],
            'platform' => $data['platform'],
            'post_type' => $data['post_type'],
            'content' => $data['content'],
            'media_urls' => json_encode($data['media_urls']),
            'hashtags' => json_encode($data['hashtags']),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return (object) [
            'post_id' => $postId,
            'status' => 'created'
        ];
    }

    /**
     * Get template suggestions based on similar past posts
     *
     * @param string $topic
     * @param string $platform
     * @param int $limit
     * @return Collection
     */
    public function getTemplateSuggestions(
        string $topic,
        string $platform = 'facebook',
        int $limit = 5
    ): Collection {
        try {
            // Generate embedding for topic
            $embedding = $this->embeddingService->generateEmbedding($topic);

            if (!$embedding) {
                return collect([]);
            }

            // Search for similar posts (this would use semantic search in production)
            $results = DB::table('cmis.social_posts')
                ->where('platform', $platform)
                ->where('status', 'published')
                ->orderByRaw('engagement_rate DESC NULLS LAST')
                ->limit($limit)
                ->get();

            return collect($results);

        } catch (\Exception $e) {
            Log::error('Failed to get template suggestions', [
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }
}
