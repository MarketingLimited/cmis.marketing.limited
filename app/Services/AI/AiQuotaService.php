<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\QuotaExceededException;
use Carbon\Carbon;

/**
 * AI Quota Service
 *
 * Manages AI usage quotas, tracking, and cost control.
 * Prevents API cost overruns and abuse.
 *
 * Features:
 * - Per-user and per-org quotas
 * - Daily and monthly limits
 * - Cost tracking
 * - Automatic reset
 * - Cache for performance
 */
class AiQuotaService
{
    /**
     * Cost per 1K tokens (USD) for different models
     */
    protected array $costPerModel = [
        'gpt-4' => ['input' => 0.03, 'output' => 0.06],
        'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
        'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
        'text-embedding-004' => ['input' => 0.0001, 'output' => 0],
    ];

    /**
     * Check if user/org has quota available (org_id from auth context)
     *
     * @param string|null $userId
     * @param string $aiService 'gpt', 'embeddings', 'image_gen'
     * @param int $requestedAmount Default 1 request
     * @return bool
     * @throws QuotaExceededException
     */
    public function checkQuota(
        ?string $userId,
        string $aiService,
        int $requestedAmount = 1
    ): bool {
        // Get org_id from authenticated user
        $orgId = auth()->user()->org_id ?? auth()->user()->current_org_id;

        // Get quota for this org/user
        $quota = $this->getOrCreateQuota($orgId, $userId, $aiService);

        // Check if quota needs reset
        $this->resetQuotaIfNeeded($quota);

        // Check daily limit
        if ($quota->daily_used + $requestedAmount > $quota->daily_limit) {
            throw new QuotaExceededException(
                "Daily {$aiService} quota exceeded. Limit: {$quota->daily_limit}, Used: {$quota->daily_used}",
                'daily'
            );
        }

        // Check monthly limit
        if ($quota->monthly_used + $requestedAmount > $quota->monthly_limit) {
            throw new QuotaExceededException(
                "Monthly {$aiService} quota exceeded. Limit: {$quota->monthly_limit}, Used: {$quota->monthly_used}",
                'monthly'
            );
        }

        // Check cost limit if set
        if ($quota->cost_limit_monthly && $quota->cost_used_monthly >= $quota->cost_limit_monthly) {
            throw new QuotaExceededException(
                "Monthly cost limit exceeded. Limit: \${$quota->cost_limit_monthly}, Used: \${$quota->cost_used_monthly}",
                'cost'
            );
        }

        return true;
    }

    /**
     * Record AI usage (org_id from auth context)
     *
     * @param string|null $userId
     * @param string $aiService
     * @param string $operation
     * @param array $details [tokens, cost, model, etc]
     * @return void
     */
    public function recordUsage(
        ?string $userId,
        string $aiService,
        string $operation,
        array $details = []
    ): void {
        // Get org_id from authenticated user
        $orgId = auth()->user()->org_id ?? auth()->user()->current_org_id;

        try {
            // 1. Insert tracking record
            $trackingId = \Illuminate\Support\Str::uuid();
            DB::table('cmis_ai.usage_tracking')->insert([
                'id' => $trackingId,
                'org_id' => $orgId,
                'user_id' => $userId,
                'ai_service' => $aiService,
                'operation' => $operation,
                'model_used' => $details['model'] ?? null,
                'tokens_used' => $details['tokens'] ?? null,
                'input_length' => $details['input_length'] ?? null,
                'output_length' => $details['output_length'] ?? null,
                'estimated_cost' => $details['cost'] ?? 0,
                'response_time_ms' => $details['response_time'] ?? null,
                'cached' => $details['cached'] ?? false,
                'status' => $details['status'] ?? 'success',
                'error_message' => $details['error'] ?? null,
                'campaign_id' => $details['campaign_id'] ?? null,
                'content_id' => $details['content_id'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'request_metadata' => json_encode($details['request_metadata'] ?? []),
                'response_metadata' => json_encode($details['response_metadata'] ?? []),
                'created_at' => now(),
            ]);

            // 2. Update quota usage (only if not cached)
            if (!($details['cached'] ?? false)) {
                $this->incrementQuotaUsage($orgId, $userId, $aiService, $details['cost'] ?? 0);
            }

            // 3. Update daily summary (async job would be better)
            $this->updateDailySummary($orgId, $userId, $aiService, $details);

        } catch (\Exception $e) {
            Log::error('Failed to record AI usage', [
                'org_id' => $orgId,
                'user_id' => $userId,
                'service' => $aiService,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - usage tracking failure shouldn't break AI operations
        }
    }

    /**
     * Calculate estimated cost for GPT API call
     *
     * @param string $model
     * @param int $inputTokens
     * @param int $outputTokens
     * @return float USD
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        if (!isset($this->costPerModel[$model])) {
            Log::warning("Unknown model for cost calculation: {$model}");
            return 0;
        }

        $rates = $this->costPerModel[$model];
        $inputCost = ($inputTokens / 1000) * $rates['input'];
        $outputCost = ($outputTokens / 1000) * $rates['output'];

        return round($inputCost + $outputCost, 4);
    }

    /**
     * Get quota status for dashboard
     *
     * @param string $orgId
     * @param string|null $userId
     * @return array
     */
    public function getQuotaStatus(string $orgId, ?string $userId = null): array
    {
        $services = ['gpt', 'embeddings'];
        $status = [];

        foreach ($services as $service) {
            $quota = $this->getOrCreateQuota($orgId, $userId, $service);
            $this->resetQuotaIfNeeded($quota);

            $status[$service] = [
                'tier' => $quota->tier,
                'daily' => [
                    'limit' => $quota->daily_limit,
                    'used' => $quota->daily_used,
                    'remaining' => max(0, $quota->daily_limit - $quota->daily_used),
                    'percentage' => $quota->daily_limit > 0
                        ? round(($quota->daily_used / $quota->daily_limit) * 100, 1)
                        : 0,
                ],
                'monthly' => [
                    'limit' => $quota->monthly_limit,
                    'used' => $quota->monthly_used,
                    'remaining' => max(0, $quota->monthly_limit - $quota->monthly_used),
                    'percentage' => $quota->monthly_limit > 0
                        ? round(($quota->monthly_used / $quota->monthly_limit) * 100, 1)
                        : 0,
                ],
                'cost' => [
                    'limit' => $quota->cost_limit_monthly,
                    'used' => (float) $quota->cost_used_monthly,
                    'remaining' => $quota->cost_limit_monthly
                        ? max(0, $quota->cost_limit_monthly - $quota->cost_used_monthly)
                        : null,
                ],
                'last_reset' => [
                    'daily' => $quota->last_daily_reset,
                    'monthly' => $quota->last_monthly_reset,
                ],
            ];
        }

        return $status;
    }

    /**
     * Get or create quota for org/user
     */
    protected function getOrCreateQuota(string $orgId, ?string $userId, string $aiService): object
    {
        // Try user-specific quota first
        if ($userId) {
            $quota = DB::table('cmis_ai.usage_quotas')
                ->where('user_id', $userId)
                ->where('ai_service', $aiService)
                ->where('is_active', true)
                ->first();

            if ($quota) {
                return $quota;
            }
        }

        // Try org-specific quota
        $quota = DB::table('cmis_ai.usage_quotas')
            ->where('org_id', $orgId)
            ->whereNull('user_id')
            ->where('ai_service', $aiService)
            ->where('is_active', true)
            ->first();

        if ($quota) {
            return $quota;
        }

        // Get org tier to determine default quota
        $org = DB::table('cmis.orgs')->where('id', $orgId)->first();
        $tier = $org->subscription_tier ?? 'free';

        // Get system default quota for this tier
        $systemQuota = DB::table('cmis_ai.usage_quotas')
            ->whereNull('org_id')
            ->whereNull('user_id')
            ->where('tier', $tier)
            ->where('ai_service', $aiService)
            ->first();

        if (!$systemQuota) {
            // Fallback to free tier
            $systemQuota = DB::table('cmis_ai.usage_quotas')
                ->whereNull('org_id')
                ->whereNull('user_id')
                ->where('tier', 'free')
                ->where('ai_service', $aiService)
                ->first();
        }

        // Create org-specific quota based on system default
        $quotaId = \Illuminate\Support\Str::uuid();
        DB::table('cmis_ai.usage_quotas')->insert([
            'id' => $quotaId,
            'org_id' => $orgId,
            'user_id' => $userId,
            'tier' => $tier,
            'ai_service' => $aiService,
            'daily_limit' => $systemQuota->daily_limit,
            'monthly_limit' => $systemQuota->monthly_limit,
            'cost_limit_monthly' => $systemQuota->cost_limit_monthly,
            'daily_used' => 0,
            'monthly_used' => 0,
            'cost_used_monthly' => 0,
            'last_daily_reset' => now()->toDateString(),
            'last_monthly_reset' => now()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('cmis_ai.usage_quotas')->where('id', $quotaId)->first();
    }

    /**
     * Reset quota counters if period has passed
     */
    protected function resetQuotaIfNeeded(object $quota): void
    {
        $needsUpdate = false;
        $updates = [];

        // Check daily reset
        if (!$quota->last_daily_reset || Carbon::parse($quota->last_daily_reset)->isYesterday()) {
            $updates['daily_used'] = 0;
            $updates['last_daily_reset'] = now()->toDateString();
            $needsUpdate = true;
        }

        // Check monthly reset
        if (!$quota->last_monthly_reset ||
            Carbon::parse($quota->last_monthly_reset)->month !== now()->month
        ) {
            $updates['monthly_used'] = 0;
            $updates['cost_used_monthly'] = 0;
            $updates['last_monthly_reset'] = now()->toDateString();
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            $updates['updated_at'] = now();
            DB::table('cmis_ai.usage_quotas')
                ->where('id', $quota->id)
                ->update($updates);

            // Clear cache
            Cache::forget("ai_quota:{$quota->org_id}:{$quota->user_id}:{$quota->ai_service}");
        }
    }

    /**
     * Increment quota usage
     */
    protected function incrementQuotaUsage(string $orgId, ?string $userId, string $aiService, float $cost): void
    {
        $query = DB::table('cmis_ai.usage_quotas')
            ->where('org_id', $orgId)
            ->where('ai_service', $aiService)
            ->where('is_active', true);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id');
        }

        $query->increment('daily_used', 1);
        $query->increment('monthly_used', 1);
        $query->increment('cost_used_monthly', $cost);
    }

    /**
     * Update daily summary statistics
     */
    protected function updateDailySummary(string $orgId, ?string $userId, string $aiService, array $details): void
    {
        $today = now()->toDateString();

        DB::table('cmis_ai.usage_summary')->updateOrInsert(
            [
                'org_id' => $orgId,
                'user_id' => $userId,
                'ai_service' => $aiService,
                'summary_date' => $today,
                'period_type' => 'daily',
            ],
            [
                'total_requests' => DB::raw('total_requests + 1'),
                'successful_requests' => DB::raw(
                    ($details['status'] ?? 'success') === 'success'
                        ? 'successful_requests + 1'
                        : 'successful_requests'
                ),
                'failed_requests' => DB::raw(
                    ($details['status'] ?? 'success') !== 'success'
                        ? 'failed_requests + 1'
                        : 'failed_requests'
                ),
                'cached_requests' => DB::raw(
                    ($details['cached'] ?? false)
                        ? 'cached_requests + 1'
                        : 'cached_requests'
                ),
                'total_tokens' => DB::raw('total_tokens + ' . ($details['tokens'] ?? 0)),
                'total_cost' => DB::raw('total_cost + ' . ($details['cost'] ?? 0)),
                'updated_at' => now(),
            ]
        );
    }
}
