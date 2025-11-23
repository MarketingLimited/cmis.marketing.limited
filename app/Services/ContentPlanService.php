<?php

namespace App\Services;

use App\Models\Creative\ContentPlan;
use App\Models\Campaign;
use App\Jobs\GenerateAIContent;
use App\Services\AIService;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Content Plan Service
 *
 * Manages content plan creation, updates, and AI content generation
 */
class ContentPlanService
{
    public function __construct(
        protected CacheService $cache,
        protected AIService $aiService
    ) {}

    /**
     * Create a new content plan
     */
    public function create(array $data): ContentPlan
    {
        // Validate campaign exists
        $campaign = Campaign::findOrFail($data['campaign_id']);

        $contentPlan = ContentPlan::create([
            'org_id' => $campaign->org_id,
            'campaign_id' => $data['campaign_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'content_type' => $data['content_type'],
            'strategy' => $data['strategy'] ?? null,
            'key_messages' => $data['key_messages'] ?? null,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        // Invalidate campaign cache
        $this->cache->invalidateCampaign($campaign->campaign_id);

        Log::info('Content plan created', [
            'content_plan_id' => $contentPlan->plan_id,
            'campaign_id' => $campaign->campaign_id,
        ]);

        return $contentPlan;
    }

    /**
     * Update a content plan
     */
    public function update(ContentPlan $contentPlan, array $data): ContentPlan
    {
        $contentPlan->update($data);

        // Invalidate caches
        $this->cache->invalidate("content_plan:{$contentPlan->plan_id}:*");
        if ($contentPlan->campaign_id) {
            $this->cache->invalidateCampaign($contentPlan->campaign_id);
        }

        Log::info('Content plan updated', [
            'content_plan_id' => $contentPlan->plan_id,
        ]);

        return $contentPlan->fresh();
    }

    /**
     * Delete a content plan
     */
    public function delete(ContentPlan $contentPlan): bool
    {
        $contentPlanId = $contentPlan->plan_id;
        $campaignId = $contentPlan->campaign_id;

        $deleted = $contentPlan->delete();

        if ($deleted) {
            // Invalidate caches
            $this->cache->invalidate("content_plan:{$contentPlanId}:*");
            if ($campaignId) {
                $this->cache->invalidateCampaign($campaignId);
            }

            Log::info('Content plan deleted', [
                'content_plan_id' => $contentPlanId,
            ]);
        }

        return $deleted;
    }

    /**
     * Generate AI content for a content plan
     */
    public function generateContent(ContentPlan $contentPlan, ?string $prompt = null, array $options = []): array
    {
        // Build prompt from content plan if not provided
        if (!$prompt) {
            $prompt = $this->buildPromptFromContentPlan($contentPlan);
        }

        // Update status
        $contentPlan->update(['status' => 'generating']);

        // Dispatch async job for content generation
        GenerateAIContent::dispatch(
            $contentPlan->plan_id,
            $prompt,
            $contentPlan->content_type,
            $options
        );

        Log::info('Content generation started', [
            'content_plan_id' => $contentPlan->plan_id,
            'content_type' => $contentPlan->content_type,
        ]);

        return [
            'content_plan_id' => $contentPlan->plan_id,
            'status' => 'generating',
            'message' => 'Content generation has been queued. Check back in a few moments.',
        ];
    }

    /**
     * Generate content synchronously (for testing or immediate needs)
     */
    public function generateContentSync(ContentPlan $contentPlan, ?string $prompt = null, array $options = []): ?string
    {
        // Build prompt from content plan if not provided
        if (!$prompt) {
            $prompt = $this->buildPromptFromContentPlan($contentPlan);
        }

        try {
            $contentPlan->update(['status' => 'generating']);

            // Generate content using AI service
            $result = $this->aiService->generate(
                $prompt,
                $contentPlan->content_type,
                array_merge($options, [
                    'content_type' => $contentPlan->content_type,
                    'target_platforms' => $contentPlan->target_platforms,
                    'tone' => $contentPlan->tone,
                ])
            );

            if ($result && isset($result['content'])) {
                $contentPlan->update([
                    'generated_content' => $result['content'],
                    'ai_metadata' => [
                        'generated_at' => now()->toISOString(),
                        'model' => $result['model'] ?? 'unknown',
                        'tokens' => $result['tokens'] ?? 0,
                    ],
                    'status' => 'generated',
                ]);

                Log::info('Content generated successfully', [
                    'content_plan_id' => $contentPlan->plan_id,
                ]);

                return $result['content'];
            }

            $contentPlan->update(['status' => 'failed']);
            return null;

        } catch (\Exception $e) {
            Log::error('Content generation failed', [
                'content_plan_id' => $contentPlan->plan_id,
                'error' => $e->getMessage(),
            ]);

            $contentPlan->update(['status' => 'failed']);
            return null;
        }
    }

    /**
     * Get content plan with cached result
     */
    public function get(string $contentPlanId): ?ContentPlan
    {
        $cacheKey = "content_plan:{$contentPlanId}";

        return $this->cache->remember(
            $cacheKey,
            CacheService::TTL_SHORT,
            function () use ($contentPlanId) {
                return ContentPlan::with(['campaign'])->find($contentPlanId);
            }
        );
    }

    /**
     * List content plans for a campaign
     */
    public function listByCampaign(string $campaignId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = ContentPlan::where('campaign_id', $campaignId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        return $query->latest()->get();
    }

    /**
     * Build prompt from content plan details
     */
    protected function buildPromptFromContentPlan(ContentPlan $contentPlan): string
    {
        $campaign = $contentPlan->campaign;

        $prompt = "Generate {$contentPlan->content_type} content for the following campaign:\n\n";

        // Campaign details
        if ($campaign) {
            $prompt .= "Campaign: {$campaign->name}\n";
            if ($campaign->description) {
                $prompt .= "Description: {$campaign->description}\n";
            }
        }

        // Content plan details
        $prompt .= "Content Plan: {$contentPlan->name}\n";
        if ($contentPlan->description) {
            $prompt .= "Brief: {$contentPlan->description}\n";
        }

        // Target platforms
        if ($contentPlan->target_platforms) {
            $platforms = is_array($contentPlan->target_platforms)
                ? implode(', ', $contentPlan->target_platforms)
                : $contentPlan->target_platforms;
            $prompt .= "Target Platforms: {$platforms}\n";
        }

        // Tone and style
        if ($contentPlan->tone) {
            $prompt .= "Tone: {$contentPlan->tone}\n";
        }

        // Key messages
        if ($contentPlan->key_messages) {
            $messages = is_array($contentPlan->key_messages)
                ? implode(', ', $contentPlan->key_messages)
                : $contentPlan->key_messages;
            $prompt .= "Key Messages: {$messages}\n";
        }

        $prompt .= "\nGenerate the content:";

        return $prompt;
    }

    /**
     * Approve a content plan
     */
    public function approve(ContentPlan $contentPlan): ContentPlan
    {
        $contentPlan->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->cache->invalidate("content_plan:{$contentPlan->plan_id}:*");

        Log::info('Content plan approved', [
            'content_plan_id' => $contentPlan->plan_id,
            'approved_by' => auth()->id(),
        ]);

        return $contentPlan->fresh();
    }

    /**
     * Reject a content plan
     */
    public function reject(ContentPlan $contentPlan, string $reason = null): ContentPlan
    {
        $contentPlan->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        $this->cache->invalidate("content_plan:{$contentPlan->plan_id}:*");

        Log::info('Content plan rejected', [
            'content_plan_id' => $contentPlan->plan_id,
            'rejected_by' => auth()->id(),
            'reason' => $reason,
        ]);

        return $contentPlan->fresh();
    }

    /**
     * Publish a content plan
     */
    public function publish(ContentPlan $contentPlan): ContentPlan
    {
        $contentPlan->update([
            'status' => 'published',
            'published_by' => auth()->id(),
            'published_at' => now(),
        ]);

        $this->cache->invalidate("content_plan:{$contentPlan->plan_id}:*");

        Log::info('Content plan published', [
            'content_plan_id' => $contentPlan->plan_id,
            'published_by' => auth()->id(),
        ]);

        return $contentPlan->fresh();
    }

    /**
     * Get content plan statistics (automatically filtered by RLS)
     */
    public function getStats(): array
    {
        // Use user ID for cache key since RLS filters by current org context
        $cacheKey = "user:" . auth()->id() . ":content_plan_stats";

        return $this->cache->remember(
            $cacheKey,
            CacheService::TTL_MEDIUM,
            function () {
                return [
                    'total' => ContentPlan::count(),
                    'by_status' => ContentPlan::query()
                        ->select('status', \DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status')
                        ->toArray(),
                    'by_type' => ContentPlan::query()
                        ->select('content_type', \DB::raw('COUNT(*) as count'))
                        ->groupBy('content_type')
                        ->pluck('count', 'content_type')
                        ->toArray(),
                ];
            }
        );
    }
}
