<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\AI\AiQuotaService;

/**
 * AI Quota API Controller
 *
 * Provides API endpoints for checking and managing AI usage quotas.
 */
class AiQuotaController extends Controller
{
    use ApiResponse;

    /**
     * AI Quota Service
     *
     * @var AiQuotaService
     */
    protected AiQuotaService $quotaService;

    /**
     * Constructor
     *
     * @param AiQuotaService $quotaService
     */
    public function __construct(AiQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get current user's quota status
     *
     * GET /api/ai/quota
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $status = $this->quotaService->getQuotaStatus(
            $user->org_id,
            $user->id
        );

        return $this->success($status, 'Retrieved successfully');
    }

    /**
     * Get quota status for specific service
     *
     * GET /api/ai/quota/{service}
     *
     * @param Request $request
     * @param string $service
     * @return JsonResponse
     */
    public function show(Request $request, string $service): JsonResponse
    {
        $user = $request->user();

        $status = $this->quotaService->getQuotaStatus(
            $user->org_id,
            $user->id
        );

        if (!isset($status[$service])) {
            return response()->json([
                'error' => 'invalid_service',
                'message' => "Unknown AI service: {$service}",
            ], 404);
        }

        return response()->json($status[$service]);
    }

    /**
     * Get usage history
     *
     * GET /api/ai/usage
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get optional filters
        $service = $request->query('service');
        $days = $request->query('days', 30);

        $query = \DB::table('cmis_ai.usage_tracking')
            ->where('org_id', $user->org_id)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');

        if ($service) {
            $query->where('ai_service', $service);
        }

        $usage = $query->limit(100)->get();

        return response()->json([
            'usage' => $usage,
            'period' => "{$days} days",
        ]);
    }

    /**
     * Get usage statistics
     *
     * GET /api/ai/stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->query('period', 'monthly'); // daily, weekly, monthly

        $stats = \DB::table('cmis_ai.usage_summary')
            ->where('org_id', $user->org_id)
            ->where('period_type', $period)
            ->orderBy('summary_date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'stats' => $stats,
            'period' => $period,
        ]);
    }

    /**
     * Check if quota is available (without consuming it)
     *
     * POST /api/ai/check-quota
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkQuota(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string|in:gpt,embeddings,image_gen',
            'amount' => 'sometimes|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $service = $request->input('service');
        $amount = $request->input('amount', 1);

        try {
            $available = $this->quotaService->checkQuota(
                $user->org_id,
                $user->id,
                $service,
                $amount
            );

            return response()->json([
                'available' => $available,
                'service' => $service,
                'amount' => $amount,
            ]);

        } catch (\App\Exceptions\QuotaExceededException $e) {
            return response()->json([
                'available' => false,
                'error' => 'quota_exceeded',
                'message' => $e->getMessage(),
                'quota_type' => $e->getQuotaType(),
                'upgrade_url' => route('billing.upgrade'),
            ], 429);
        }
    }

    /**
     * Get quota recommendations
     *
     * GET /api/ai/recommendations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();

        $status = $this->quotaService->getQuotaStatus(
            $user->org_id,
            $user->id
        );

        $recommendations = [];

        // Analyze usage patterns and make recommendations
        foreach ($status as $service => $quota) {
            $dailyPercentage = $quota['daily']['percentage'];
            $monthlyPercentage = $quota['monthly']['percentage'];

            if ($dailyPercentage > 80 || $monthlyPercentage > 80) {
                $recommendations[] = [
                    'service' => $service,
                    'severity' => 'high',
                    'message' => "You're using {$monthlyPercentage}% of your monthly {$service} quota. Consider upgrading.",
                    'action' => 'upgrade',
                    'suggested_tier' => $this->suggestTier($quota['tier']),
                ];
            } elseif ($dailyPercentage > 50 || $monthlyPercentage > 50) {
                $recommendations[] = [
                    'service' => $service,
                    'severity' => 'medium',
                    'message' => "You've used {$monthlyPercentage}% of your {$service} quota this month.",
                    'action' => 'monitor',
                ];
            }
        }

        return response()->json([
            'recommendations' => $recommendations,
            'current_tier' => $user->organization->subscription_tier ?? 'free',
        ]);
    }

    /**
     * Suggest next tier based on current usage
     *
     * @param string $currentTier
     * @return string
     */
    protected function suggestTier(string $currentTier): string
    {
        $tierProgression = [
            'free' => 'pro',
            'pro' => 'enterprise',
            'enterprise' => 'enterprise', // Already at top
        ];

        return $tierProgression[$currentTier] ?? 'pro';
    }
}
