<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAnalytics;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    protected CampaignRepositoryInterface $campaignRepo;
    protected PermissionRepositoryInterface $permissionRepo;

    public function __construct(
        CampaignRepositoryInterface $campaignRepo,
        PermissionRepositoryInterface $permissionRepo
    ) {
        $this->campaignRepo = $campaignRepo;
        $this->permissionRepo = $permissionRepo;
    }

    /**
     * Create campaign with context safely using repository
     */
    public function createWithContext(array $campaignData, array $contextData): Campaign
    {
        try {
            // Initialize transaction context for security
            $userId = $campaignData['created_by'] ?? auth()->id();
            $orgId = $campaignData['org_id'];

            $this->permissionRepo->initTransactionContext($userId, $orgId);

            // Create campaign using repository method
            $results = $this->campaignRepo->createCampaignWithContext(
                $orgId,
                $contextData['offering_id'] ?? '',
                $contextData['segment_id'] ?? '',
                $campaignData['name'],
                $contextData['framework'] ?? 'default',
                $contextData['tone'] ?? 'professional',
                $contextData['tags'] ?? []
            );

            if ($results->isEmpty() || !isset($results[0]->campaign_id)) {
                throw new \Exception('فشل إنشاء الحملة مع السياق');
            }

            return Campaign::findOrFail($results[0]->campaign_id);

        } catch (\Exception $e) {
            Log::error('Campaign creation failed', [
                'error' => $e->getMessage(),
                'data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Get campaign contexts using repository
     */
    public function getCampaignContexts(string $campaignId, bool $includeInactive = false): array
    {
        try {
            $results = $this->campaignRepo->getCampaignContexts($campaignId, $includeInactive);
            return $results->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get campaign contexts', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Find related campaigns using repository
     */
    public function findRelatedCampaigns(string $campaignId, int $limit = 5): array
    {
        try {
            $results = $this->campaignRepo->findRelatedCampaigns($campaignId, $limit);
            return $results->toArray();

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
     * Create a simple campaign without context
     */
    public function create(array $campaignData): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign = Campaign::create($campaignData);

            DB::commit();

            Log::info('Campaign created', ['campaign_id' => $campaign->campaign_id]);

            return $campaign;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create campaign', [
                'error' => $e->getMessage(),
                'data' => $campaignData
            ]);
            throw $e;
        }
    }

    /**
     * Update campaign
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        try {
            DB::beginTransaction();

            $campaign->update($data);

            DB::commit();

            Log::info('Campaign updated', ['campaign_id' => $campaign->campaign_id]);

            return $campaign->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update campaign', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete campaign
     */
    public function delete(Campaign $campaign): bool
    {
        try {
            DB::beginTransaction();

            $campaign->delete();

            DB::commit();

            Log::info('Campaign deleted', ['campaign_id' => $campaign->campaign_id]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete campaign', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            return false;
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
                throw new \InvalidArgumentException("حالة غير صالحة: {$newStatus}");
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

    /**
     * Check if user has permission for campaign operation
     */
    public function checkPermission(string $userId, string $orgId, string $permissionCode): bool
    {
        return $this->permissionRepo->checkPermission($userId, $orgId, $permissionCode);
    }
}
