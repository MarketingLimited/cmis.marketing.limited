<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Integration;
use App\Models\Social\SocialPost;
use App\Models\Social\BrandKnowledgeConfig;
use App\Services\Social\HistoricalContentService;
use App\Services\Social\KnowledgeBaseConversionService;
use App\Services\Social\BrandDNAAnalysisService;
use App\Jobs\Social\ImportHistoricalPostsJob;
use App\Jobs\Social\AnalyzeHistoricalPostJob;
use App\Jobs\Social\BatchAnalyzePostsJob;
use App\Jobs\Social\BuildKnowledgeBaseJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Historical Content Controller
 *
 * Manages historical social content import, analysis, and knowledge base operations.
 */
class HistoricalContentController extends Controller
{
    use ApiResponse;

    private HistoricalContentService $historicalService;
    private KnowledgeBaseConversionService $kbService;
    private BrandDNAAnalysisService $brandDNAService;

    public function __construct(
        HistoricalContentService $historicalService,
        KnowledgeBaseConversionService $kbService,
        BrandDNAAnalysisService $brandDNAService
    ) {
        $this->historicalService = $historicalService;
        $this->kbService = $kbService;
        $this->brandDNAService = $brandDNAService;
    }

    /**
     * Get all historical posts for a profile group
     *
     * GET /api/social/historical
     */
    public function index(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'nullable|uuid',
            'platform' => 'nullable|string',
            'is_analyzed' => 'nullable|boolean',
            'is_in_kb' => 'nullable|boolean',
            'min_success_score' => 'nullable|numeric|min:0|max:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $org;

        $query = SocialPost::where('org_id', $orgId)
            ->where('is_historical', true)
            ->with(['mediaAssets', 'profileGroup']);

        if ($request->filled('profile_group_id')) {
            $query->where('profile_group_id', $request->profile_group_id);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('is_analyzed')) {
            $query->where('is_analyzed', $request->is_analyzed);
        }

        if ($request->filled('is_in_kb')) {
            $query->where('is_in_knowledge_base', $request->is_in_kb);
        }

        // Only filter by min_success_score if explicitly provided and > 0
        // Note: success_score is NULL for unanalyzed posts, and NULL >= 0 is false in SQL
        if ($request->filled('min_success_score') && floatval($request->min_success_score) > 0) {
            $query->where('success_score', '>=', $request->min_success_score);
        }

        $perPage = $request->get('per_page', 20);
        $posts = $query->orderBy('published_at', 'desc')->paginate($perPage);

        return $this->paginated($posts, 'Historical posts retrieved successfully');
    }

    /**
     * Get a single historical post
     *
     * GET /api/social/historical/{id}
     */
    public function show(Request $request, string $org, string $id)
    {
        $orgId = $org;

        $post = SocialPost::where('org_id', $orgId)
            ->where('id', $id)
            ->where('is_historical', true)
            ->with(['mediaAssets', 'brandKnowledgeDimensions', 'profileGroup'])
            ->first();

        if (!$post) {
            return $this->notFound('Historical post not found');
        }

        return $this->success($post, 'Historical post retrieved successfully');
    }

    /**
     * Import historical posts from a platform integration
     *
     * POST /orgs/{org}/social/history/api/import
     */
    public function import(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'integration_id' => 'required|uuid',
            'limit' => 'nullable|integer|min:1|max:500',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'auto_analyze' => 'nullable|boolean',
            'async' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Get org_id from route parameter (not session)
        $orgId = $org;

        \Illuminate\Support\Facades\Log::debug('Historical import request', [
            'org_id' => $orgId,
            'integration_id' => $request->integration_id,
        ]);

        $integration = Integration::where('org_id', $orgId)
            ->where('integration_id', $request->integration_id)
            ->first();

        if (!$integration) {
            \Illuminate\Support\Facades\Log::warning('Integration not found', [
                'org_id' => $orgId,
                'integration_id' => $request->integration_id,
                'existing_integrations' => Integration::where('org_id', $orgId)->pluck('integration_id')->toArray(),
            ]);
            return $this->notFound('Integration not found');
        }

        $options = [
            'limit' => $request->get('limit', 100),
            'start_date' => $request->get('start_date', now()->subMonths(6)),
            'end_date' => $request->get('end_date', now()),
            'auto_analyze' => $request->get('auto_analyze', true),
        ];

        // Dispatch async job or run synchronously
        if ($request->get('async', true)) {
            ImportHistoricalPostsJob::dispatch(
                $integration->integration_id,
                $options,
                auth()->id()
            );

            return $this->success([
                'message' => 'Import job dispatched',
                'integration_id' => $integration->integration_id,
            ], 'Historical import started');
        } else {
            $result = $this->historicalService->importFromPlatform($integration, $options);
            return $this->success($result, 'Historical posts imported successfully');
        }
    }

    /**
     * Analyze a historical post
     *
     * POST /api/social/historical/{id}/analyze
     */
    public function analyze(Request $request, string $id)
    {
        $orgId = $request->route('org');

        $post = SocialPost::where('org_id', $orgId)
            ->where('id', $id)
            ->where('is_historical', true)
            ->first();

        if (!$post) {
            return $this->notFound('Historical post not found');
        }

        $async = $request->get('async', true);

        if ($async) {
            AnalyzeHistoricalPostJob::dispatch($post->id);
            return $this->success([
                'message' => 'Analysis job dispatched',
                'post_id' => $post->id,
            ], 'Post analysis started');
        } else {
            $result = $this->historicalService->analyzeHistoricalPost($post);
            return $this->success($result, 'Post analyzed successfully');
        }
    }

    /**
     * Batch analyze historical posts
     *
     * POST /api/social/historical/batch-analyze
     */
    public function batchAnalyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'nullable|array',
            'post_ids.*' => 'uuid',
            'profile_group_id' => 'nullable|uuid',
            'batch_size' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        if ($request->filled('post_ids')) {
            // Analyze specific posts
            $postIds = $request->post_ids;
        } else {
            // Analyze all pending posts for profile group
            $query = SocialPost::where('org_id', $orgId)
                ->where('is_historical', true)
                ->where('is_analyzed', false);

            if ($request->filled('profile_group_id')) {
                $query->where('profile_group_id', $request->profile_group_id);
            }

            $postIds = $query->pluck('id')->toArray();
        }

        $batchSize = $request->get('batch_size', 50);
        $batches = array_chunk($postIds, $batchSize);

        foreach ($batches as $batch) {
            BatchAnalyzePostsJob::dispatch($orgId, $batch);
        }

        return $this->success([
            'total_posts' => count($postIds),
            'batch_count' => count($batches),
            'message' => 'Batch analysis jobs dispatched',
        ], 'Batch analysis started');
    }

    /**
     * Get import progress
     *
     * GET /api/social/historical/progress
     */
    public function getProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');
        $progress = $this->historicalService->getImportProgress(
            $orgId,
            $request->profile_group_id
        );

        return $this->success($progress, 'Import progress retrieved successfully');
    }

    /**
     * Add posts to knowledge base
     *
     * POST /api/social/knowledge-base/add
     */
    public function addToKnowledgeBase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $posts = SocialPost::where('org_id', $orgId)
            ->whereIn('id', $request->post_ids)
            ->get();

        if ($posts->isEmpty()) {
            return $this->notFound('No posts found');
        }

        $result = $this->kbService->addToKnowledgeBase(
            $posts,
            $request->get('notes'),
            auth()->id()
        );

        return $this->success($result, 'Posts added to knowledge base');
    }

    /**
     * Remove posts from knowledge base
     *
     * POST /api/social/knowledge-base/remove
     */
    public function removeFromKnowledgeBase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $posts = SocialPost::where('org_id', $orgId)
            ->whereIn('id', $request->post_ids)
            ->get();

        if ($posts->isEmpty()) {
            return $this->notFound('No posts found');
        }

        $result = $this->kbService->removeFromKnowledgeBase(
            $posts,
            $request->get('reason')
        );

        return $this->success($result, 'Posts removed from knowledge base');
    }

    /**
     * Build knowledge base
     *
     * POST /api/social/knowledge-base/build
     */
    public function buildKnowledgeBase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'strategy' => 'nullable|string|in:quality,balanced,quantity',
            'async' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');
        $strategy = $request->get('strategy', 'balanced');
        $async = $request->get('async', true);

        if ($async) {
            BuildKnowledgeBaseJob::dispatch(
                $orgId,
                $request->profile_group_id,
                ['strategy' => $strategy],
                auth()->id()
            );

            return $this->success([
                'message' => 'KB build job dispatched',
                'profile_group_id' => $request->profile_group_id,
                'strategy' => $strategy,
            ], 'Knowledge base build started');
        } else {
            $result = $this->kbService->buildKnowledgeBase(
                $orgId,
                $request->profile_group_id,
                ['strategy' => $strategy]
            );

            return $this->success($result, 'Knowledge base built successfully');
        }
    }

    /**
     * Get knowledge base summary
     *
     * GET /api/social/knowledge-base/summary
     */
    public function getKBSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');
        $summary = $this->kbService->getKnowledgeBaseSummary(
            $orgId,
            $request->profile_group_id
        );

        return $this->success($summary, 'KB summary retrieved successfully');
    }

    /**
     * Query knowledge base
     *
     * POST /api/social/knowledge-base/query
     */
    public function queryKB(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'objective' => 'nullable|string',
            'tone' => 'nullable|string',
            'platform' => 'nullable|string',
            'min_success_score' => 'nullable|numeric|min:0|max:1',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $criteria = array_filter([
            'objective' => $request->get('objective'),
            'tone' => $request->get('tone'),
            'platform' => $request->get('platform'),
            'min_success_score' => $request->get('min_success_score', 0.7),
            'limit' => $request->get('limit', 20),
        ]);

        $results = $this->kbService->queryKnowledgeBase(
            $orgId,
            $request->profile_group_id,
            $criteria
        );

        return $this->success($results, 'KB query results retrieved');
    }

    /**
     * Get recommendations for campaign objective
     *
     * POST /api/social/knowledge-base/recommendations
     */
    public function getRecommendations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'campaign_objective' => 'required|string',
            'platform' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $recommendations = $this->kbService->getRecommendationsForCampaign(
            $orgId,
            $request->profile_group_id,
            $request->campaign_objective,
            $request->get('platform'),
            $request->get('limit', 10)
        );

        return $this->success($recommendations, 'Recommendations retrieved successfully');
    }

    /**
     * Get brand DNA summary
     *
     * GET /api/social/brand-dna
     */
    public function getBrandDNA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $brandDNA = $this->brandDNAService->getBrandDNASummary(
            $orgId,
            $request->profile_group_id
        );

        return $this->success($brandDNA, 'Brand DNA retrieved successfully');
    }

    /**
     * Get KB configuration
     *
     * GET /api/social/knowledge-base/config
     */
    public function getKBConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $config = BrandKnowledgeConfig::where('org_id', $orgId)
            ->where('profile_group_id', $request->profile_group_id)
            ->first();

        if (!$config) {
            return $this->notFound('KB configuration not found');
        }

        return $this->success($config, 'KB configuration retrieved successfully');
    }

    /**
     * Update KB configuration
     *
     * PUT /api/social/knowledge-base/config
     */
    public function updateKBConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'auto_build_enabled' => 'nullable|boolean',
            'auto_build_min_posts' => 'nullable|integer|min:1',
            'auto_build_min_days' => 'nullable|integer|min:1',
            'auto_analyze_new_posts' => 'nullable|boolean',
            'min_success_percentile' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        $config = BrandKnowledgeConfig::updateOrCreate(
            [
                'org_id' => $orgId,
                'profile_group_id' => $request->profile_group_id,
            ],
            $request->only([
                'auto_build_enabled',
                'auto_build_min_posts',
                'auto_build_min_days',
                'auto_analyze_new_posts',
                'min_success_percentile',
            ])
        );

        return $this->success($config, 'KB configuration updated successfully');
    }

    /**
     * Export knowledge base
     *
     * GET /api/social/knowledge-base/export
     */
    public function exportKB(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_group_id' => 'required|uuid',
            'format' => 'nullable|string|in:json,csv',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');
        $format = $request->get('format', 'json');

        $export = $this->kbService->exportKnowledgeBase(
            $orgId,
            $request->profile_group_id,
            $format
        );

        return $this->success($export, 'KB exported successfully');
    }

    /**
     * Boost a historical post
     *
     * POST /api/posts/{id}/boost
     */
    public function boostPost(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'boost_rule_id' => 'nullable|uuid|exists:pgsql.cmis.boost_rules,boost_rule_id',
            'ad_account_id' => 'required|uuid|exists:pgsql.cmis.ad_accounts,id',
            'budget_amount' => 'required|numeric|min:1',
            'budget_type' => 'required|string|in:daily,lifetime',
            'duration_days' => 'required|integer|min:1|max:30',
            'objective' => 'required|string|in:reach,engagement,traffic,conversions,awareness',
            'audience' => 'nullable|array',
            'audience.type' => 'nullable|string|in:auto,saved,lookalike,custom',
            'audience.locations' => 'nullable|array',
            'audience.age_min' => 'nullable|integer|min:13|max:65',
            'audience.age_max' => 'nullable|integer|min:13|max:65',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        try {
            $post = SocialPost::where('org_id', $orgId)
                ->where('post_id', $id)
                ->whereNotNull('platform_post_id')
                ->firstOrFail();

            // Verify post is from a boostable platform
            if (!in_array($post->platform, ['facebook', 'instagram', 'tiktok', 'snapchat'])) {
                return $this->error('This platform does not support boosting', 400);
            }

            // Create campaign from historical post
            $campaign = \App\Models\Campaign\Campaign::create([
                'org_id' => $orgId,
                'name' => "Boosted: " . substr($post->caption, 0, 50),
                'objective' => $request->objective,
                'status' => 'draft',
                'budget' => $request->budget_amount,
                'start_date' => now(),
                'end_date' => now()->addDays($request->duration_days),
                'metadata' => [
                    'boosted_post' => true,
                    'historical_post_id' => $post->post_id,
                    'platform_post_id' => $post->platform_post_id,
                    'platform' => $post->platform,
                    'boost_rule_id' => $request->boost_rule_id,
                    'audience' => $request->audience,
                    'budget_type' => $request->budget_type,
                    'original_success_score' => $post->success_score,
                ],
            ]);

            // Update post to track boost
            $post->update([
                'metadata' => array_merge($post->metadata ?? [], [
                    'boosted_at' => now()->toIso8601String(),
                    'boost_campaign_id' => $campaign->campaign_id,
                ]),
            ]);

            return $this->created($campaign, 'Post boosted successfully. Campaign created.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Historical post not found or not available for boosting');
        } catch (\Exception $e) {
            return $this->serverError('Failed to boost post: ' . $e->getMessage());
        }
    }

    /**
     * Add historical post to an ad campaign
     *
     * POST /api/posts/{id}/add-to-campaign
     */
    public function addToCampaign(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|uuid|exists:pgsql.cmis.campaigns,campaign_id',
            'creative_type' => 'nullable|string|in:image,video,carousel',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        try {
            $post = SocialPost::where('org_id', $orgId)
                ->where('post_id', $id)
                ->with('mediaAssets')
                ->firstOrFail();

            $campaign = \App\Models\Campaign\Campaign::where('org_id', $orgId)
                ->where('campaign_id', $request->campaign_id)
                ->firstOrFail();

            // Create ad creative from historical post
            $creative = \App\Models\Creative\Creative::create([
                'org_id' => $orgId,
                'campaign_id' => $campaign->campaign_id,
                'name' => "Historical: " . substr($post->caption, 0, 50),
                'type' => $request->get('creative_type', 'image'),
                'status' => 'draft',
                'content' => [
                    'caption' => $post->caption,
                    'hashtags' => $post->hashtags,
                    'media_urls' => $post->mediaAssets->pluck('media_url')->toArray(),
                ],
                'metadata' => [
                    'source' => 'historical_content',
                    'historical_post_id' => $post->post_id,
                    'platform_post_id' => $post->platform_post_id,
                    'platform' => $post->platform,
                    'original_success_score' => $post->success_score,
                    'original_engagement' => $post->engagement_total,
                    'original_metrics' => [
                        'likes' => $post->likes_count,
                        'comments' => $post->comments_count,
                        'shares' => $post->shares_count,
                    ],
                ],
            ]);

            // Update post to track campaign association
            $post->update([
                'metadata' => array_merge($post->metadata ?? [], [
                    'added_to_campaigns' => array_merge(
                        $post->metadata['added_to_campaigns'] ?? [],
                        [$campaign->campaign_id]
                    ),
                ]),
            ]);

            return $this->created($creative, 'Post added to campaign successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Historical post or campaign not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to add post to campaign: ' . $e->getMessage());
        }
    }

    /**
     * Create new campaign from historical post
     *
     * POST /api/posts/{id}/create-campaign
     */
    public function createCampaignFromPost(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|string|in:awareness,traffic,engagement,leads,sales,conversions',
            'budget_amount' => 'required|numeric|min:1',
            'budget_type' => 'required|string|in:daily,lifetime',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'ad_account_id' => 'required|uuid|exists:pgsql.cmis.ad_accounts,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->route('org');

        try {
            $post = SocialPost::where('org_id', $orgId)
                ->where('post_id', $id)
                ->with('mediaAssets')
                ->firstOrFail();

            // Create campaign
            $campaign = \App\Models\Campaign\Campaign::create([
                'org_id' => $orgId,
                'name' => $request->campaign_name,
                'objective' => $request->objective,
                'status' => 'draft',
                'budget' => $request->budget_amount,
                'start_date' => $request->get('start_date', now()),
                'end_date' => $request->end_date,
                'metadata' => [
                    'created_from_historical_post' => true,
                    'historical_post_id' => $post->post_id,
                    'platform' => $post->platform,
                    'budget_type' => $request->budget_type,
                    'ad_account_id' => $request->ad_account_id,
                ],
            ]);

            // Create creative from post
            $creative = \App\Models\Creative\Creative::create([
                'org_id' => $orgId,
                'campaign_id' => $campaign->campaign_id,
                'name' => $post->caption ? substr($post->caption, 0, 50) : 'Historical Post Creative',
                'type' => $post->post_type ?? 'image',
                'status' => 'draft',
                'content' => [
                    'caption' => $post->caption,
                    'hashtags' => $post->hashtags,
                    'media_urls' => $post->mediaAssets->pluck('media_url')->toArray(),
                ],
                'metadata' => [
                    'source' => 'historical_content',
                    'historical_post_id' => $post->post_id,
                    'original_success_score' => $post->success_score,
                ],
            ]);

            // Update post
            $post->update([
                'metadata' => array_merge($post->metadata ?? [], [
                    'campaign_created_at' => now()->toIso8601String(),
                    'campaign_id' => $campaign->campaign_id,
                ]),
            ]);

            return $this->created([
                'campaign' => $campaign,
                'creative' => $creative,
            ], 'Campaign created from historical post successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Historical post not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create campaign: ' . $e->getMessage());
        }
    }

    /**
     * Get available boost rules for a post
     *
     * GET /api/posts/{id}/available-boost-rules
     */
    public function getAvailableBoostRules(Request $request, string $id)
    {
        $orgId = $request->route('org');

        try {
            $post = SocialPost::where('org_id', $orgId)
                ->where('post_id', $id)
                ->firstOrFail();

            // Get active boost rules for this post's profile group
            $boostRules = \App\Models\Platform\BoostRule::where('org_id', $orgId)
                ->where('profile_group_id', $post->profile_group_id)
                ->where('is_active', true)
                ->whereIn('platforms', [$post->platform])
                ->with(['profileGroup', 'adAccount'])
                ->get();

            return $this->success([
                'boost_rules' => $boostRules,
                'post' => $post,
            ], 'Available boost rules retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Historical post not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to get boost rules: ' . $e->getMessage());
        }
    }
}
