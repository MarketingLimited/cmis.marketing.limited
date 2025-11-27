<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use App\Models\Social\MediaAsset;
use App\Models\Social\BrandKnowledgeConfig;
use App\Models\Integration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Historical Content Service
 *
 * Orchestrates the import of historical social posts from connected platforms,
 * triggers analysis (success detection, visual analysis, brand DNA extraction),
 * and builds the brand knowledge base.
 */
class HistoricalContentService
{
    private SuccessPostDetectionService $successDetection;
    private VisualAnalysisService $visualAnalysis;
    private BrandDNAAnalysisService $brandDNAAnalysis;

    public function __construct(
        SuccessPostDetectionService $successDetection,
        VisualAnalysisService $visualAnalysis,
        BrandDNAAnalysisService $brandDNAAnalysis
    ) {
        $this->successDetection = $successDetection;
        $this->visualAnalysis = $visualAnalysis;
        $this->brandDNAAnalysis = $brandDNAAnalysis;
    }

    /**
     * Import historical posts from a platform integration
     */
    public function importFromPlatform(
        Integration $integration,
        array $options = []
    ): array {
        $limit = $options['limit'] ?? 100;
        $startDate = $options['start_date'] ?? now()->subMonths(6);
        $endDate = $options['end_date'] ?? now();
        $includeMetrics = $options['include_metrics'] ?? true;
        $autoAnalyze = $options['auto_analyze'] ?? true;

        try {
            // Get platform service
            $platformService = $this->getPlatformService($integration);

            // Fetch historical posts from platform API
            $platformPosts = $platformService->fetchHistoricalPosts([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'limit' => $limit,
                'include_metrics' => $includeMetrics,
            ]);

            $imported = [];
            $successCount = 0;

            DB::beginTransaction();

            foreach ($platformPosts as $platformPost) {
                try {
                    // Create SocialPost record
                    $post = $this->createHistoricalPost($integration, $platformPost);

                    // Import media assets
                    if (!empty($platformPost['media'])) {
                        $this->importMediaAssets($post, $platformPost['media']);
                    }

                    // Trigger analysis if enabled
                    if ($autoAnalyze) {
                        $this->analyzeHistoricalPost($post);
                    }

                    $imported[] = $post;

                    if ($post->success_label === SocialPost::SUCCESS_HIGH_PERFORMER) {
                        $successCount++;
                    }

                } catch (\Exception $e) {
                    Log::warning('Failed to import post', [
                        'platform_post_id' => $platformPost['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Update knowledge base config stats
            if ($integration->profile_group_id) {
                $this->updateKnowledgeBaseStats(
                    $integration->org_id,
                    $integration->profile_group_id,
                    count($imported),
                    $successCount
                );
            }

            DB::commit();

            return [
                'success' => true,
                'imported_count' => count($imported),
                'success_posts' => $successCount,
                'posts' => $imported,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Historical import failed', [
                'integration_id' => $integration->integration_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create historical post record from platform data
     */
    private function createHistoricalPost(Integration $integration, array $platformPost): SocialPost
    {
        return SocialPost::create([
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'profile_group_id' => $integration->profile_group_id,
            'platform' => $integration->platform,
            'provider' => $integration->platform,
            'account_id' => $platformPost['account_id'] ?? null,
            'account_username' => $platformPost['account_username'] ?? null,
            'post_external_id' => $platformPost['id'],
            'permalink' => $platformPost['permalink'] ?? null,
            'content' => $platformPost['message'] ?? $platformPost['caption'] ?? '',
            'media' => $platformPost['media'] ?? [],
            'post_type' => $platformPost['type'] ?? 'post',
            'status' => SocialPost::STATUS_PUBLISHED,
            'published_at' => $platformPost['created_time'] ?? now(),
            'source' => SocialPost::SOURCE_IMPORTED,
            'is_historical' => true,
            'is_schedulable' => false,
            'is_editable' => false,
            'is_analyzed' => false,
            'is_in_knowledge_base' => false,
            'platform_metrics' => $platformPost['metrics'] ?? null,
            'metadata' => [
                'import_timestamp' => now()->toIso8601String(),
                'platform_data' => $platformPost['raw_data'] ?? [],
            ],
        ]);
    }

    /**
     * Import media assets for a post
     */
    private function importMediaAssets(SocialPost $post, array $mediaData): Collection
    {
        $assets = collect();
        $position = 0;

        foreach ($mediaData as $media) {
            try {
                $asset = MediaAsset::create([
                    'org_id' => $post->org_id,
                    'post_id' => $post->id,
                    'media_type' => $media['type'] ?? 'image',
                    'original_url' => $media['url'] ?? null,
                    'file_name' => $media['filename'] ?? null,
                    'mime_type' => $media['mime_type'] ?? null,
                    'width' => $media['width'] ?? null,
                    'height' => $media['height'] ?? null,
                    'position' => $position++,
                    'is_analyzed' => false,
                    'analysis_status' => 'pending',
                    'metadata' => [
                        'platform_media_id' => $media['id'] ?? null,
                    ],
                ]);

                $assets->push($asset);

            } catch (\Exception $e) {
                Log::warning('Failed to import media asset', [
                    'post_id' => $post->id,
                    'media_url' => $media['url'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $assets;
    }

    /**
     * Analyze a historical post (success, visual, brand DNA)
     */
    public function analyzeHistoricalPost(SocialPost $post): array
    {
        $results = [
            'success_analysis' => null,
            'visual_analysis' => [],
            'brand_dna' => null,
            'dimensions_stored' => 0,
        ];

        try {
            // 1. Success post detection
            if ($post->platform_metrics) {
                $successAnalysis = $this->successDetection->analyzePost($post);
                $post->update([
                    'success_score' => $successAnalysis['success_score'],
                    'success_label' => $successAnalysis['success_label'],
                    'success_hypothesis' => $successAnalysis['success_hypothesis'],
                ]);
                $results['success_analysis'] = $successAnalysis;
            }

            // 2. Visual analysis for media assets
            $mediaAssets = MediaAsset::where('post_id', $post->id)
                ->where('is_analyzed', false)
                ->get();

            foreach ($mediaAssets as $asset) {
                try {
                    $visualResult = $this->visualAnalysis->analyzeMediaAsset($asset);
                    $results['visual_analysis'][] = [
                        'asset_id' => $asset->asset_id,
                        'analysis' => $visualResult,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Visual analysis failed for asset', [
                        'asset_id' => $asset->asset_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 3. Brand DNA extraction
            if ($post->content) {
                $brandDNA = $this->brandDNAAnalysis->analyzePost($post);
                $results['brand_dna'] = $brandDNA;

                // Store dimensions as records
                $dimensionsStored = $this->brandDNAAnalysis->storeDimensionsAsRecords(
                    $post,
                    $brandDNA['dimensions'],
                    $post->profile_group_id
                );
                $results['dimensions_stored'] = $dimensionsStored;

                // Update knowledge base config
                if ($post->profile_group_id) {
                    $config = BrandKnowledgeConfig::where('org_id', $post->org_id)
                        ->where('profile_group_id', $post->profile_group_id)
                        ->first();

                    if ($config) {
                        $config->incrementAnalysisStats(1, $dimensionsStored);
                    }
                }
            }

            // Mark post as analyzed
            $post->markAsAnalyzed();

            return $results;

        } catch (\Exception $e) {
            Log::error('Historical post analysis failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Batch analyze historical posts
     */
    public function batchAnalyze(Collection $posts, array $options = []): array
    {
        $maxConcurrent = $options['max_concurrent'] ?? 5;
        $delayBetween = $options['delay_ms'] ?? 2000; // 2 seconds for rate limiting

        $results = [
            'total' => $posts->count(),
            'analyzed' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($posts as $post) {
            // Skip if already analyzed
            if ($post->is_analyzed) {
                $results['skipped']++;
                continue;
            }

            try {
                $this->analyzeHistoricalPost($post);
                $results['analyzed']++;

                // Rate limiting delay
                usleep($delayBetween * 1000);

            } catch (\Exception $e) {
                $results['failed']++;
                Log::warning("Batch analysis failed for post {$post->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Update knowledge base config stats after import
     */
    private function updateKnowledgeBaseStats(
        string $orgId,
        string $profileGroupId,
        int $postsCount,
        int $successPostsCount
    ): void {
        $config = BrandKnowledgeConfig::firstOrCreate(
            [
                'org_id' => $orgId,
                'profile_group_id' => $profileGroupId,
            ],
            [
                'auto_build_enabled' => true,
                'auto_build_min_posts' => 50,
                'auto_build_min_days' => 7,
                'auto_analyze_new_posts' => true,
                'total_posts_imported' => 0,
                'total_success_posts' => 0,
            ]
        );

        $config->incrementImportStats($postsCount, $successPostsCount);
    }

    /**
     * Get platform service for integration
     */
    private function getPlatformService(Integration $integration)
    {
        // This would return the appropriate platform service
        // For now, return a mock implementation
        return new class($integration) {
            private $integration;

            public function __construct($integration)
            {
                $this->integration = $integration;
            }

            public function fetchHistoricalPosts(array $options): array
            {
                // Mock implementation - would call actual platform API
                // Instagram, Facebook, Twitter, LinkedIn, TikTok, etc.
                return [];
            }
        };
    }

    /**
     * Get import progress for a profile group
     */
    public function getImportProgress(string $orgId, string $profileGroupId): array
    {
        $config = BrandKnowledgeConfig::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->first();

        if (!$config) {
            return [
                'total_imported' => 0,
                'total_analyzed' => 0,
                'pending_analysis' => 0,
                'can_auto_build' => false,
            ];
        }

        $pendingAnalysis = SocialPost::where('org_id', $orgId)
            ->where('profile_group_id', $profileGroupId)
            ->where('is_historical', true)
            ->where('is_analyzed', false)
            ->count();

        return [
            'total_imported' => $config->total_posts_imported,
            'total_analyzed' => $config->total_posts_analyzed,
            'total_success_posts' => $config->total_success_posts,
            'total_dimensions' => $config->total_dimensions_extracted,
            'pending_analysis' => $pendingAnalysis,
            'can_auto_build' => $config->canAutoBuild(),
            'first_import_at' => $config->first_import_at,
            'last_import_at' => $config->last_import_at,
            'last_analysis_at' => $config->last_analysis_at,
            'kb_built_at' => $config->kb_built_at,
        ];
    }

    /**
     * Trigger auto-build for ready profile groups
     */
    public function triggerAutoBuildForReady(string $orgId): array
    {
        $readyConfigs = BrandKnowledgeConfig::where('org_id', $orgId)
            ->readyForAutoBuild()
            ->whereNull('kb_built_at')
            ->get();

        $results = [];

        foreach ($readyConfigs as $config) {
            try {
                // Mark KB as built
                $config->markKnowledgeBaseBuilt();

                // Consolidate core DNA
                $coreDNA = $this->brandDNAAnalysis->consolidateCoreDNA(
                    $orgId,
                    $config->profile_group_id
                );

                $results[] = [
                    'profile_group_id' => $config->profile_group_id,
                    'success' => true,
                    'core_dna_count' => count($coreDNA),
                    'total_posts' => $config->total_posts_imported,
                    'total_dimensions' => $config->total_dimensions_extracted,
                ];

            } catch (\Exception $e) {
                Log::error('Auto-build failed', [
                    'config_id' => $config->config_id,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'profile_group_id' => $config->profile_group_id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Re-analyze posts (e.g., after algorithm improvements)
     */
    public function reAnalyzePosts(Collection $posts, array $analysisTypes = []): array
    {
        $defaultTypes = ['success', 'visual', 'brand_dna'];
        $types = !empty($analysisTypes) ? $analysisTypes : $defaultTypes;

        $results = [
            'total' => $posts->count(),
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($posts as $post) {
            try {
                if (in_array('success', $types) && $post->platform_metrics) {
                    $successAnalysis = $this->successDetection->analyzePost($post);
                    $post->update([
                        'success_score' => $successAnalysis['success_score'],
                        'success_label' => $successAnalysis['success_label'],
                        'success_hypothesis' => $successAnalysis['success_hypothesis'],
                    ]);
                }

                if (in_array('visual', $types)) {
                    $mediaAssets = MediaAsset::where('post_id', $post->id)->get();
                    foreach ($mediaAssets as $asset) {
                        $this->visualAnalysis->analyzeMediaAsset($asset);
                    }
                }

                if (in_array('brand_dna', $types) && $post->content) {
                    $brandDNA = $this->brandDNAAnalysis->analyzePost($post);
                    $this->brandDNAAnalysis->storeDimensionsAsRecords(
                        $post,
                        $brandDNA['dimensions'],
                        $post->profile_group_id
                    );
                }

                $results['success']++;

                // Rate limiting
                usleep(2000000); // 2 seconds

            } catch (\Exception $e) {
                $results['failed']++;
                Log::warning("Re-analysis failed for post {$post->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
