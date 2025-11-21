<?php

namespace App\Services\Orchestration;

use App\Models\Orchestration\CampaignOrchestration;
use App\Models\Orchestration\CampaignTemplate;
use App\Models\Orchestration\OrchestrationPlatform;
use App\Models\Orchestration\OrchestrationWorkflow;
use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignOrchestrationService
{
    public function __construct(
        protected WorkflowEngine $workflowEngine,
        protected CrossPlatformSyncService $syncService
    ) {}

    /**
     * Create orchestration from template.
     */
    public function createFromTemplate(
        string $orgId,
        string $userId,
        string $templateId,
        array $overrides = []
    ): CampaignOrchestration {
        $template = CampaignTemplate::where('org_id', $orgId)
            ->orWhere('is_global', true)
            ->findOrFail($templateId);

        DB::beginTransaction();
        try {
            // Create orchestration
            $orchestration = CampaignOrchestration::create([
                'org_id' => $orgId,
                'template_id' => $templateId,
                'created_by' => $userId,
                'name' => $overrides['name'] ?? $template->name,
                'description' => $overrides['description'] ?? $template->description,
                'platforms' => $overrides['platforms'] ?? $template->platforms,
                'orchestration_config' => array_merge(
                    $template->base_config ?? [],
                    $overrides['config'] ?? []
                ),
                'total_budget' => $overrides['total_budget'] ?? null,
                'budget_allocation' => $this->calculateBudgetAllocation(
                    $template,
                    $overrides['total_budget'] ?? 0,
                    $overrides['platforms'] ?? $template->platforms
                ),
                'status' => 'draft',
            ]);

            // Create platform mappings
            foreach ($orchestration->platforms as $platform) {
                $this->createPlatformMapping($orchestration, $platform, $template);
            }

            // Update platform counts
            $orchestration->updatePlatformCounts();

            // Increment template usage
            $template->incrementUsage();

            DB::commit();
            return $orchestration;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create orchestration from template', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create platform mapping for orchestration.
     */
    protected function createPlatformMapping(
        CampaignOrchestration $orchestration,
        string $platform,
        CampaignTemplate $template
    ): OrchestrationPlatform {
        // Find active connection for this platform
        $connection = PlatformConnection::where('org_id', $orchestration->org_id)
            ->where('platform', $platform)
            ->where('status', 'active')
            ->first();

        if (!$connection) {
            throw new \Exception("No active connection found for platform: {$platform}");
        }

        return OrchestrationPlatform::create([
            'org_id' => $orchestration->org_id,
            'orchestration_id' => $orchestration->orchestration_id,
            'connection_id' => $connection->connection_id,
            'platform' => $platform,
            'status' => 'pending',
            'platform_config' => $template->getPlatformConfig($platform),
            'allocated_budget' => $orchestration->getBudgetForPlatform($platform),
        ]);
    }

    /**
     * Calculate budget allocation across platforms.
     */
    protected function calculateBudgetAllocation(
        CampaignTemplate $template,
        float $totalBudget,
        array $platforms
    ): array {
        $distribution = $template->getBudgetDistribution();
        $allocation = [];

        foreach ($platforms as $platform) {
            $percentage = $distribution[$platform] ?? (100 / count($platforms));
            $allocation[$platform] = round(($percentage / 100) * $totalBudget, 2);
        }

        return $allocation;
    }

    /**
     * Deploy orchestration to all platforms.
     */
    public function deploy(CampaignOrchestration $orchestration): OrchestrationWorkflow
    {
        return $this->workflowEngine->executeDeploymentWorkflow($orchestration);
    }

    /**
     * Sync orchestration with all platforms.
     */
    public function sync(CampaignOrchestration $orchestration, string $syncType = 'full'): array
    {
        $results = [];

        foreach ($orchestration->platformMappings as $mapping) {
            $results[$mapping->platform] = $this->syncService->syncPlatformMapping(
                $mapping,
                $syncType
            );
        }

        $orchestration->markSynced();

        return $results;
    }

    /**
     * Pause orchestration on all platforms.
     */
    public function pause(CampaignOrchestration $orchestration): void
    {
        DB::beginTransaction();
        try {
            foreach ($orchestration->platformMappings()->active()->get() as $mapping) {
                $this->syncService->pausePlatformCampaign($mapping);
                $mapping->markAsPaused();
            }

            $orchestration->pause();
            $orchestration->updatePlatformCounts();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resume orchestration on all platforms.
     */
    public function resume(CampaignOrchestration $orchestration): void
    {
        DB::beginTransaction();
        try {
            foreach ($orchestration->platformMappings()->where('status', 'paused')->get() as $mapping) {
                $this->syncService->resumePlatformCampaign($mapping);
                $mapping->markAsActive($mapping->platform_campaign_id, $mapping->platform_campaign_name);
            }

            $orchestration->resume();
            $orchestration->updatePlatformCounts();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get aggregated performance across all platforms.
     */
    public function getAggregatedPerformance(CampaignOrchestration $orchestration): array
    {
        return [
            'total_spend' => $orchestration->getTotalSpend(),
            'total_conversions' => $orchestration->getTotalConversions(),
            'total_revenue' => $orchestration->getTotalRevenue(),
            'roas' => $orchestration->getROAS(),
            'budget_utilization' => $orchestration->getBudgetUtilization(),
            'platform_performance' => $orchestration->platformMappings->map(function ($mapping) {
                return [
                    'platform' => $mapping->platform,
                    'status' => $mapping->status,
                    'spend' => $mapping->spend,
                    'conversions' => $mapping->conversions,
                    'revenue' => $mapping->revenue,
                    'roas' => $mapping->getROAS(),
                    'ctr' => $mapping->getCTR(),
                    'cpa' => $mapping->getCPA(),
                ];
            })->toArray(),
        ];
    }
}
