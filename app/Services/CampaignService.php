<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAnalytics;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Create campaign with context safely using database function
     */
    public function createWithContext(array $campaignData, array $contextData): Campaign
    {
        try {
            $result = DB::selectOne(
                'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $campaignData['org_id'],
                    $campaignData['name'],
                    $campaignData['status'] ?? 'draft',
                    $campaignData['budget'] ?? null,
                    $campaignData['start_date'] ?? null,
                    $campaignData['end_date'] ?? null,
                    $contextData['intent'] ?? null,
                    $contextData['direction'] ?? null,
                    $contextData['purpose'] ?? null,
                ]
            );

            if (!$result || !$result->campaign_id) {
                throw new \Exception('Failed to create campaign with context');
            }

            return Campaign::findOrFail($result->campaign_id);

        } catch (\Exception $e) {
            Log::error('Campaign creation failed', [
                'error' => $e->getMessage(),
                'data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign contexts using database function
     */
    public function getCampaignContexts(string $campaignId): array
    {
        try {
            $results = DB::select(
                'SELECT * FROM cmis.get_campaign_contexts(?)',
                [$campaignId]
            );

            return array_map(fn($row) => (array) $row, $results);

        } catch (\Exception $e) {
            Log::error('Failed to get campaign contexts', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find related campaigns using database function
     */
    public function findRelatedCampaigns(string $campaignId, int $limit = 5): array
    {
        try {
            $results = DB::select(
                'SELECT * FROM cmis.find_related_campaigns(?, ?)',
                [$campaignId, $limit]
            );

            return array_map(fn($row) => (array) $row, $results);

        } catch (\Exception $e) {
            Log::error('Failed to find related campaigns', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get campaign analytics summary
     */
    public function getAnalyticsSummary(string $campaignId): ?array
    {
        try {
            $campaign = Campaign::with(['performanceMetrics', 'analytics'])->findOrFail($campaignId);

            $metrics = $campaign->performanceMetrics()
                ->latest('collected_at')
                ->limit(10)
                ->get();

            $analytics = CampaignAnalytics::where('campaign_id', $campaignId)
                ->latest('calculated_at')
                ->first();

            return [
                'campaign' => $campaign,
                'recent_metrics' => $metrics,
                'analytics' => $analytics,
                'performance_summary' => [
                    'total_metrics' => $metrics->count(),
                    'avg_confidence' => $metrics->avg('confidence_level'),
                    'total_variance' => $metrics->sum('variance'),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get campaign analytics', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update campaign status with validation
     */
    public function updateStatus(string $campaignId, string $newStatus): bool
    {
        try {
            $campaign = Campaign::findOrFail($campaignId);

            $validStatuses = ['draft', 'active', 'paused', 'completed', 'archived'];
            if (!in_array($newStatus, $validStatuses)) {
                throw new \InvalidArgumentException("Invalid status: {$newStatus}");
            }

            $campaign->update(['status' => $newStatus]);

            Log::info('Campaign status updated', [
                'campaign_id' => $campaignId,
                'old_status' => $campaign->getOriginal('status'),
                'new_status' => $newStatus
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update campaign status', [
                'campaign_id' => $campaignId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
