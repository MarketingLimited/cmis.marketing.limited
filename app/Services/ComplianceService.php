<?php

namespace App\Services;

use App\Models\ComplianceRule;
use App\Models\ComplianceAudit;
use App\Models\CreativeAsset;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplianceService
{
    /**
     * Validate campaign against compliance rules
     */
    public function validateCampaign(Campaign $campaign): array
    {
        try {
            $violations = [];
            $warnings = [];

            // Get active compliance rules
            $rules = ComplianceRule::where('status', 'active')
                ->where(function($q) use ($campaign) {
                    $q->whereNull('org_id')
                      ->orWhere('org_id', $campaign->org_id);
                })
                ->get();

            foreach ($rules as $rule) {
                $result = $this->checkRule($campaign, $rule);

                if ($result['violated']) {
                    if ($rule->severity === 'critical' || $rule->severity === 'high') {
                        $violations[] = [
                            'rule' => $rule->rule_name,
                            'message' => $result['message'],
                            'severity' => $rule->severity,
                        ];
                    } else {
                        $warnings[] = [
                            'rule' => $rule->rule_name,
                            'message' => $result['message'],
                            'severity' => $rule->severity,
                        ];
                    }
                }
            }

            // Log audit
            $this->logAudit($campaign, $violations, $warnings);

            return [
                'passed' => empty($violations),
                'violations' => $violations,
                'warnings' => $warnings,
                'total_rules_checked' => $rules->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Compliance validation failed', [
                'campaign_id' => $campaign->campaign_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check specific rule against campaign
     */
    protected function checkRule(Campaign $campaign, ComplianceRule $rule): array
    {
        try {
            // Implement rule checking logic based on rule criteria
            $criteria = $rule->criteria;
            $violated = false;
            $message = '';

            // Example checks (customize based on your rules)
            if (isset($criteria['min_budget']) && $campaign->budget < $criteria['min_budget']) {
                $violated = true;
                $message = "Budget below minimum: {$criteria['min_budget']}";
            }

            if (isset($criteria['required_fields'])) {
                foreach ($criteria['required_fields'] as $field) {
                    if (empty($campaign->$field)) {
                        $violated = true;
                        $message = "Missing required field: {$field}";
                        break;
                    }
                }
            }

            return [
                'violated' => $violated,
                'message' => $message ?: 'Passed',
            ];

        } catch (\Exception $e) {
            Log::error('Rule check failed', [
                'rule' => $rule->rule_name,
                'error' => $e->getMessage()
            ]);
            return [
                'violated' => false,
                'message' => 'Rule check error',
            ];
        }
    }

    /**
     * Validate creative asset
     */
    public function validateAsset(CreativeAsset $asset): array
    {
        try {
            $violations = [];
            $warnings = [];

            // Get asset-specific compliance rules
            $rules = ComplianceRule::where('status', 'active')
                ->where(function($q) use ($asset) {
                    $q->whereNull('org_id')
                      ->orWhere('org_id', $asset->org_id);
                })
                ->whereJsonContains('criteria->applies_to', 'assets')
                ->get();

            foreach ($rules as $rule) {
                $result = $this->checkAssetRule($asset, $rule);

                if ($result['violated']) {
                    if ($rule->severity === 'critical') {
                        $violations[] = [
                            'rule' => $rule->rule_name,
                            'message' => $result['message'],
                            'severity' => $rule->severity,
                        ];
                    } else {
                        $warnings[] = [
                            'rule' => $rule->rule_name,
                            'message' => $result['message'],
                            'severity' => $rule->severity,
                        ];
                    }
                }
            }

            return [
                'passed' => empty($violations),
                'violations' => $violations,
                'warnings' => $warnings,
            ];

        } catch (\Exception $e) {
            Log::error('Asset compliance validation failed', [
                'asset_id' => $asset->asset_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check asset-specific rule
     */
    protected function checkAssetRule(CreativeAsset $asset, ComplianceRule $rule): array
    {
        $violated = false;
        $message = '';

        // Implement asset-specific checks
        $criteria = $rule->criteria;

        if (isset($criteria['requires_approval']) && $criteria['requires_approval']) {
            if ($asset->status !== 'approved') {
                $violated = true;
                $message = 'Asset requires approval before use';
            }
        }

        return [
            'violated' => $violated,
            'message' => $message ?: 'Passed',
        ];
    }

    /**
     * Log compliance audit
     */
    protected function logAudit($entity, array $violations, array $warnings): void
    {
        try {
            ComplianceAudit::create([
                'audit_id' => \Illuminate\Support\Str::uuid(),
                'entity_type' => get_class($entity),
                'entity_id' => $entity->getKey(),
                'org_id' => $entity->org_id ?? null,
                'audit_result' => empty($violations) ? 'passed' : 'failed',
                'violations_found' => count($violations),
                'warnings_found' => count($warnings),
                'details' => [
                    'violations' => $violations,
                    'warnings' => $warnings,
                ],
                'audited_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log compliance audit', [
                'entity' => get_class($entity),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get compliance summary for organization
     */
    public function getOrgCompliance Summary(string $orgId): array
    {
        try {
            $audits = ComplianceAudit::where('org_id', $orgId)
                ->where('audited_at', '>=', now()->subDays(30))
                ->get();

            return [
                'total_audits' => $audits->count(),
                'passed' => $audits->where('audit_result', 'passed')->count(),
                'failed' => $audits->where('audit_result', 'failed')->count(),
                'total_violations' => $audits->sum('violations_found'),
                'total_warnings' => $audits->sum('warnings_found'),
                'compliance_rate' => $audits->count() > 0
                    ? round(($audits->where('audit_result', 'passed')->count() / $audits->count()) * 100, 2)
                    : 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get org compliance summary', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
