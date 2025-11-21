<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\MetaPostsService;
use App\Models\Platform\MetaAccount;
use App\Models\Campaign\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaPostsController extends Controller
{
    public function __construct(
        private MetaPostsService $metaPostsService
    ) {}

    /**
     * Fetch all organic posts from connected Meta accounts
     *
     * @authenticated
     * @group Platform Integration
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            $platform = $request->query('platform', 'all'); // all, facebook, instagram
            $limit = min((int) $request->query('limit', 50), 100);

            $posts = $this->metaPostsService->fetchAllOrganizationPosts(
                $user->org_id,
                $platform,
                $limit
            );

            return response()->json([
                'success' => true,
                'posts' => [
                    'facebook' => $posts['facebook'],
                    'instagram' => $posts['instagram'],
                ],
                'total_count' => $posts['total_count'],
                'platform_counts' => [
                    'facebook' => count($posts['facebook']),
                    'instagram' => count($posts['instagram']),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch Meta posts', [
                'user_id' => $user->id,
                'org_id' => $user->org_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'fetch_failed',
                'message' => 'Failed to fetch posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details for a specific post
     *
     * @authenticated
     * @group Platform Integration
     */
    public function show(Request $request, string $postId): JsonResponse
    {
        $user = auth()->user();

        try {
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            $platform = $request->query('platform', 'facebook');
            $accountId = $request->query('account_id');

            // Get Meta account
            $metaAccount = MetaAccount::where('org_id', $user->org_id)
                ->where('id', $accountId)
                ->firstOrFail();

            $post = $this->metaPostsService->getPostDetails(
                $postId,
                $platform,
                $metaAccount->access_token
            );

            return response()->json([
                'success' => true,
                'post' => $post
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch post details', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'post_not_found',
                'message' => 'Failed to fetch post details'
            ], 404);
        }
    }

    /**
     * Refresh posts cache for an account
     *
     * @authenticated
     * @group Platform Integration
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = auth()->user();

        try {
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            $accountId = $request->input('account_id');

            $metaAccount = MetaAccount::where('org_id', $user->org_id)
                ->where('id', $accountId)
                ->firstOrFail();

            // Clear cache
            if ($metaAccount->page_id) {
                $this->metaPostsService->clearCache($metaAccount->page_id);
            }

            if ($metaAccount->instagram_account_id) {
                $this->metaPostsService->clearCache($metaAccount->instagram_account_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Posts cache cleared. Fetching fresh data...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'refresh_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create ad campaign from existing post (Boost Post)
     *
     * @authenticated
     * @group Platform Integration
     */
    public function boostPost(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'post_id' => 'required|string',
            'platform' => 'required|in:facebook,instagram',
            'account_id' => 'required|uuid|exists:cmis_platform.meta_accounts,id',
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|string',
            'budget' => 'required|numeric|min:10',
            'duration_days' => 'required|integer|min:1|max:90',
            'target_audience' => 'nullable|array',
        ]);

        try {
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            // Get Meta account
            $metaAccount = MetaAccount::where('org_id', $user->org_id)
                ->where('id', $validated['account_id'])
                ->firstOrFail();

            // Get post details
            $post = $this->metaPostsService->getPostDetails(
                $validated['post_id'],
                $validated['platform'],
                $metaAccount->access_token
            );

            // Create campaign from post
            $campaign = Campaign::create([
                'org_id' => $user->org_id,
                'name' => $validated['campaign_name'],
                'objective' => $validated['objective'],
                'status' => 'draft',
                'budget' => $validated['budget'],
                'start_date' => now(),
                'end_date' => now()->addDays($validated['duration_days']),
                'metadata' => [
                    'boosted_post' => true,
                    'original_post_id' => $validated['post_id'],
                    'platform' => $validated['platform'],
                    'account_id' => $validated['account_id'],
                    'post_message' => $post['message'],
                    'post_media_url' => $post['media_url'],
                    'post_permalink' => $post['permalink'],
                    'original_engagement' => $post['engagement'],
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully from post',
                'campaign' => [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                ],
                'redirect_url' => route('campaign.wizard.step', [
                    'session_id' => $campaign->id,
                    'step' => 2 // Skip to targeting step
                ])
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to boost post', [
                'post_id' => $validated['post_id'],
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'boost_failed',
                'message' => 'Failed to create campaign from post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing posts (for quick boost suggestions)
     *
     * @authenticated
     * @group Platform Integration
     */
    public function topPerforming(Request $request): JsonResponse
    {
        $user = auth()->user();

        try {
            DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

            $platform = $request->query('platform', 'all');
            $limit = min((int) $request->query('limit', 10), 50);

            $posts = $this->metaPostsService->fetchAllOrganizationPosts(
                $user->org_id,
                $platform,
                100 // Fetch more to analyze
            );

            // Calculate engagement rate and sort
            $scorePosts = function ($post) {
                $engagement = $post['engagement'];
                $totalEngagement = ($engagement['likes'] ?? 0) +
                                   ($engagement['comments'] ?? 0) * 2 + // Comments weigh more
                                   ($engagement['shares'] ?? 0) * 3; // Shares weigh most

                $impressions = $post['insights']['post_impressions'] ?? $post['insights']['impressions'] ?? 1;
                $engagementRate = ($impressions > 0) ? ($totalEngagement / $impressions) * 100 : 0;

                return [
                    'post' => $post,
                    'score' => $totalEngagement,
                    'engagement_rate' => round($engagementRate, 2)
                ];
            };

            $fbTop = collect($posts['facebook'])
                ->map($scorePosts)
                ->sortByDesc('score')
                ->take($limit)
                ->values();

            $igTop = collect($posts['instagram'])
                ->map($scorePosts)
                ->sortByDesc('score')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'top_posts' => [
                    'facebook' => $fbTop,
                    'instagram' => $igTop,
                ],
                'recommendation' => 'Posts with higher engagement rates are more likely to succeed as ads'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch top performing posts', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'fetch_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
