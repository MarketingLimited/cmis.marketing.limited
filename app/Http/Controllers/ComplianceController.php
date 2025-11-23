<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CreativeAsset;
use App\Models\ComplianceRule;
use App\Services\ComplianceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Concerns\ApiResponse;

class ComplianceController extends Controller
{
    use ApiResponse;

    protected $complianceService;

    public function __construct(ComplianceService $complianceService)
    {
        $this->complianceService = $complianceService;
    }

    /**
     * Display compliance dashboard
     */
    public function index(): View
    {
        Gate::authorize('viewInsights', auth()->user());

        return view('compliance.index');
    }

    /**
     * Validate campaign compliance
     */
    public function validateCampaign(string $campaignId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            $campaign = Campaign::findOrFail($campaignId);
            $result = $this->complianceService->validateCampaign($campaign);

            return $this->success($result, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Validation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Validate creative asset compliance
     */
    public function validateAsset(string $assetId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            $asset = CreativeAsset::findOrFail($assetId);
            $result = $this->complianceService->validateAsset($asset);

            return $this->success($result, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Validation failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get organization compliance summary
     */
    public function orgSummary(string $orgId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            $summary = $this->complianceService->getOrgComplianceSummary($orgId);

            return $this->success($summary, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to get summary: ' . $e->getMessage(),
            );
        }
    }

    /**
     * List compliance rules
     */
    public function rules(Request $request): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        $query = ComplianceRule::query();

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->get('severity'));
        }

        $rules = $query->orderBy('created_at', 'desc')->paginate(20);

        return $this->success($rules, 'Retrieved successfully');
    }

    /**
     * Create compliance rule
     */
    public function storeRule(Request $request): JsonResponse
    {
        Gate::authorize('manageKnowledge', auth()->user());

        $validated = $request->validate([
            'rule_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:active,inactive',
            'criteria' => 'required|array',
            'org_id' => 'nullable|uuid',
        ]);

        try {
            $rule = ComplianceRule::create([
                'rule_id' => \Illuminate\Support\Str::uuid(),
                'rule_name' => $validated['rule_name'],
                'description' => $validated['description'] ?? null,
                'severity' => $validated['severity'],
                'status' => $validated['status'],
                'criteria' => $validated['criteria'],
                'org_id' => $validated['org_id'] ?? auth()->user()->org_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compliance rule created successfully',
                'data' => $rule,
            ], 201);
        } catch (\Exception $e) {
            return $this->serverError('Failed to create rule: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Update compliance rule
     */
    public function updateRule(Request $request, string $ruleId): JsonResponse
    {
        Gate::authorize('manageKnowledge', auth()->user());

        $validated = $request->validate([
            'rule_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:active,inactive',
            'criteria' => 'sometimes|array',
        ]);

        try {
            $rule = ComplianceRule::findOrFail($ruleId);
            $rule->update($validated);

            return $this->success($rule, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update rule: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Delete compliance rule
     */
    public function destroyRule(string $ruleId): JsonResponse
    {
        Gate::authorize('manageKnowledge', auth()->user());

        try {
            $rule = ComplianceRule::findOrFail($ruleId);
            $rule->delete();

            return $this->success(['message' => 'Compliance rule deleted successfully',], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete rule: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get compliance audits
     */
    public function audits(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        $query = \App\Models\ComplianceAudit::where('org_id', $orgId);

        if ($request->has('result')) {
            $query->where('audit_result', $request->get('result'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->get('entity_type'));
        }

        $audits = $query->orderBy('audited_at', 'desc')->paginate(20);

        return $this->success($audits, 'Retrieved successfully');
    }
}
