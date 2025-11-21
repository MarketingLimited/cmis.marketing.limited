<?php

namespace App\Services\Automation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CampaignOptimizationService
{
    private AutomationRulesEngine $rulesEngine;

    public function __construct(AutomationRulesEngine $rulesEngine)
    {
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * Run optimization for all campaigns in an organization
     */
    public function optimizeOrganizationCampaigns(string $orgId): array
    {
        $results = [
            'optimized' => 0,
            'paused' => 0,
            'budget_adjusted' => 0,
            'notifications_sent' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            // Get active automation rules for the organization
            $rules = $this->getActiveRules($orgId);

            if (empty($rules)) {
                return array_merge($results, ['message' => 'No active automation rules found']);
            }

            // Get active campaigns
            $campaigns = DB::table('cmis.campaigns')
                ->where('org_id', $orgId)
                ->where('status', '!=', 'archived')
                ->get();

            foreach ($campaigns as $campaign) {
                try {
                    $campaignResult = $this->optimizeCampaign($campaign->id, $orgId, $rules);

                    if ($campaignResult['optimized']) {
                        $results['optimized']++;

                        // Track specific actions
                        foreach ($campaignResult['actions'] as $action) {
                            switch ($action['action']) {
                                case 'paused':
                                    $results['paused']++;
                                    break;
                                case 'budget_adjusted':
                                    $results['budget_adjusted']++;
                                    break;
                                case 'notification_sent':
                                    $results['notifications_sent']++;
                                    break;
                            }
                        }

                        $results['details'][] = [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'actions' => $campaignResult['actions']
                        ];
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Campaign optimization error', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Organization optimization error', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return array_merge($results, [
                'error' => $e->getMessage(),
                'errors' => $results['errors'] + 1
            ]);
        }
    }

    /**
     * Optimize a specific campaign
     */
    public function optimizeCampaign(string $campaignId, string $orgId, ?array $rules = null): array
    {
        $result = [
            'optimized' => false,
            'actions' => []
        ];

        try {
            // Get rules if not provided
            if ($rules === null) {
                $rules = $this->getActiveRules($orgId);
            }

            if (empty($rules)) {
                return $result;
            }

            // Get campaign metrics
            $metrics = $this->getCampaignMetrics($campaignId, $orgId);

            if (empty($metrics)) {
                return $result;
            }

            // Evaluate each rule
            foreach ($rules as $rule) {
                if ($this->rulesEngine->evaluateRule($rule, $metrics)) {
                    // Rule condition met, apply action
                    $actionResult = $this->rulesEngine->applyRule($rule, $campaignId, $orgId);

                    if ($actionResult['success']) {
                        $result['optimized'] = true;
                        $result['actions'][] = $actionResult;
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Campaign optimization error', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return $result;
        }
    }

    /**
     * Get campaign performance metrics
     */
    private function getCampaignMetrics(string $campaignId, string $orgId): array
    {
        // Get campaign data from last 7 days
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        // This would aggregate data from platform-specific metrics
        // For now, using sample calculation
        $campaign = DB::table('cmis.campaigns')
            ->where('id', $campaignId)
            ->where('org_id', $orgId)
            ->first();

        if (!$campaign) {
            return [];
        }

        // Get metrics from campaigns table (simplified)
        $spend = $campaign->budget ?? 0;
        $impressions = 10000; // Would come from platform data
        $clicks = 500; // Would come from platform data
        $conversions = 25; // Would come from platform data
        $revenue = 1250; // Would come from tracking data

        return [
            'spend' => $spend,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'ctr' => $impressions > 0 ? ($clicks / $impressions) : 0,
            'cpc' => $clicks > 0 ? ($spend / $clicks) : 0,
            'cpa' => $conversions > 0 ? ($spend / $conversions) : 0,
            'conversion_rate' => $clicks > 0 ? ($conversions / $clicks) : 0,
            'roas' => $spend > 0 ? ($revenue / $spend) : 0
        ];
    }

    /**
     * Get active automation rules for organization
     */
    private function getActiveRules(string $orgId): array
    {
        $rules = DB::table('cmis_automation.automation_rules')
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->get()
            ->toArray();

        return array_map(function ($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'condition' => json_decode($rule->condition, true),
                'action' => json_decode($rule->action, true)
            ];
        }, $rules);
    }

    /**
     * Create automation rule
     */
    public function createRule(string $orgId, array $ruleData): array
    {
        // Validate rule
        $errors = $this->rulesEngine->validateRule($ruleData);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        try {
            $ruleId = \Ramsey\Uuid\Uuid::uuid4()->toString();

            DB::table('cmis_automation.automation_rules')->insert([
                'id' => $ruleId,
                'org_id' => $orgId,
                'name' => $ruleData['name'],
                'description' => $ruleData['description'] ?? null,
                'condition' => json_encode($ruleData['condition']),
                'action' => json_encode($ruleData['action']),
                'is_active' => $ruleData['is_active'] ?? true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'rule_id' => $ruleId,
                'message' => 'Automation rule created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create automation rule', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update automation rule
     */
    public function updateRule(string $ruleId, string $orgId, array $ruleData): array
    {
        // Validate rule if condition/action is being updated
        if (isset($ruleData['condition']) || isset($ruleData['action'])) {
            $rule = DB::table('cmis_automation.automation_rules')
                ->where('id', $ruleId)
                ->where('org_id', $orgId)
                ->first();

            if (!$rule) {
                return [
                    'success' => false,
                    'error' => 'Rule not found'
                ];
            }

            $fullRule = [
                'condition' => $ruleData['condition'] ?? json_decode($rule->condition, true),
                'action' => $ruleData['action'] ?? json_decode($rule->action, true)
            ];

            $errors = $this->rulesEngine->validateRule($fullRule);

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }
        }

        try {
            $updateData = ['updated_at' => Carbon::now()];

            if (isset($ruleData['name'])) {
                $updateData['name'] = $ruleData['name'];
            }
            if (isset($ruleData['description'])) {
                $updateData['description'] = $ruleData['description'];
            }
            if (isset($ruleData['condition'])) {
                $updateData['condition'] = json_encode($ruleData['condition']);
            }
            if (isset($ruleData['action'])) {
                $updateData['action'] = json_encode($ruleData['action']);
            }
            if (isset($ruleData['is_active'])) {
                $updateData['is_active'] = $ruleData['is_active'];
            }

            DB::table('cmis_automation.automation_rules')
                ->where('id', $ruleId)
                ->where('org_id', $orgId)
                ->update($updateData);

            return [
                'success' => true,
                'message' => 'Automation rule updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update automation rule', [
                'rule_id' => $ruleId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete automation rule
     */
    public function deleteRule(string $ruleId, string $orgId): array
    {
        try {
            DB::table('cmis_automation.automation_rules')
                ->where('id', $ruleId)
                ->where('org_id', $orgId)
                ->delete();

            return [
                'success' => true,
                'message' => 'Automation rule deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete automation rule', [
                'rule_id' => $ruleId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all rules for organization
     */
    public function getRules(string $orgId): array
    {
        $rules = DB::table('cmis_automation.automation_rules')
            ->where('org_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        return array_map(function ($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'description' => $rule->description,
                'condition' => json_decode($rule->condition, true),
                'action' => json_decode($rule->action, true),
                'is_active' => $rule->is_active,
                'created_at' => $rule->created_at,
                'updated_at' => $rule->updated_at
            ];
        }, $rules->toArray());
    }

    /**
     * Get rule execution history
     */
    public function getRuleExecutionHistory(string $orgId, ?string $campaignId = null): array
    {
        $query = DB::table('cmis_automation.rule_execution_log as log')
            ->join('cmis.campaigns as c', 'log.campaign_id', '=', 'c.id')
            ->where('c.org_id', $orgId)
            ->select([
                'log.id',
                'log.rule_id',
                'log.campaign_id',
                'c.name as campaign_name',
                'log.action',
                'log.details',
                'log.executed_at'
            ])
            ->orderBy('log.executed_at', 'desc')
            ->limit(100);

        if ($campaignId) {
            $query->where('log.campaign_id', $campaignId);
        }

        return $query->get()->toArray();
    }
}
