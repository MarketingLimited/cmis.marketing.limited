<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Automation\AutomationRule;
use App\Models\Automation\AutomationExecution;
use App\Services\Automation\AutomationRulesEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Automation Rules Controller
 *
 * Manages automation rules for campaign optimization and workflow automation
 */
class AutomationRulesController extends Controller
{
    use ApiResponse;

    protected AutomationRulesEngine $rulesEngine;

    public function __construct(AutomationRulesEngine $rulesEngine)
    {
        $this->middleware('auth:sanctum');
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * List automation rules
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $rules = AutomationRule::where('org_id', $orgId)
            ->when($request->rule_type, fn($q) => $q->where('rule_type', $request->rule_type))
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->has('enabled'), fn($q) => $q->where('enabled', $request->boolean('enabled')))
            ->with(['creator', 'executions' => fn($q) => $q->latest()->limit(5)])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($rules, 'Automation rules retrieved successfully');
        }

        return view('automation.rules.index', compact('rules'));
    }

    /**
     * Create automation rule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'rule_type' => 'required|string|in:budget_optimization,bid_adjustment,creative_rotation,schedule_pause,schedule_resume,alert',
            'entity_type' => 'required|string|in:campaign,ad_set,ad',
            'entity_id' => 'sometimes|uuid',
            'conditions' => 'required|array|min:1',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|string|in:>,>=,<,<=,=,!=,contains,between',
            'conditions.*.value' => 'required',
            'condition_logic' => 'sometimes|string|in:and,or',
            'actions' => 'required|array|min:1',
            'actions.*.type' => 'required|string',
            'actions.*.params' => 'required|array',
            'priority' => 'sometimes|integer|between:1,100',
            'enabled' => 'sometimes|boolean',
            'max_executions_per_day' => 'sometimes|integer|min:1',
            'cooldown_minutes' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $rule = AutomationRule::create([
            'org_id' => $orgId,
            'created_by' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'rule_type' => $request->rule_type,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'conditions' => $request->conditions,
            'condition_logic' => $request->condition_logic ?? 'and',
            'actions' => $request->actions,
            'priority' => $request->priority ?? 50,
            'status' => 'draft',
            'enabled' => $request->enabled ?? false,
            'max_executions_per_day' => $request->max_executions_per_day,
            'cooldown_minutes' => $request->cooldown_minutes,
        ]);

        if ($request->expectsJson()) {
            return $this->created($rule, 'Automation rule created successfully');
        }

        return redirect()->route('automation.rules.show', $rule->rule_id)
            ->with('success', 'Automation rule created successfully');
    }

    /**
     * Show automation rule
     */
    public function show(string $id, Request $request)
    {
        $rule = AutomationRule::with(['creator', 'executions', 'schedules'])->findOrFail($id);

        if ($request->expectsJson()) {
            return $this->success($rule, 'Automation rule retrieved successfully');
        }

        return view('automation.rules.show', compact('rule'));
    }

    /**
     * Update automation rule
     */
    public function update(string $id, Request $request)
    {
        $rule = AutomationRule::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'conditions' => 'sometimes|array|min:1',
            'condition_logic' => 'sometimes|string|in:and,or',
            'actions' => 'sometimes|array|min:1',
            'priority' => 'sometimes|integer|between:1,100',
            'enabled' => 'sometimes|boolean',
            'max_executions_per_day' => 'sometimes|integer|min:1',
            'cooldown_minutes' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $rule->update($request->only([
            'name', 'description', 'conditions', 'condition_logic', 'actions',
            'priority', 'enabled', 'max_executions_per_day', 'cooldown_minutes'
        ]));

        if ($request->expectsJson()) {
            return $this->success($rule->fresh(), 'Automation rule updated successfully');
        }

        return redirect()->route('automation.rules.show', $rule->rule_id)
            ->with('success', 'Automation rule updated successfully');
    }

    /**
     * Delete automation rule
     */
    public function destroy(string $id, Request $request)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->delete();

        if ($request->expectsJson()) {
            return $this->deleted('Automation rule deleted successfully');
        }

        return redirect()->route('automation.rules.index')
            ->with('success', 'Automation rule deleted successfully');
    }

    /**
     * Activate rule
     */
    public function activate(string $id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->activate();

        return $this->success($rule->fresh(), 'Automation rule activated successfully');
    }

    /**
     * Pause rule
     */
    public function pause(string $id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->pause();

        return $this->success($rule->fresh(), 'Automation rule paused successfully');
    }

    /**
     * Archive rule
     */
    public function archive(string $id)
    {
        $rule = AutomationRule::findOrFail($id);
        $rule->archive();

        return $this->success($rule->fresh(), 'Automation rule archived successfully');
    }

    /**
     * Test rule execution
     */
    public function test(string $id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $rule = AutomationRule::findOrFail($id);

        $result = $this->rulesEngine->evaluateRule($rule, $request->test_data);

        return $this->success([
            'rule_id' => $rule->rule_id,
            'would_trigger' => $result['matches'],
            'matched_conditions' => $result['matched_conditions'] ?? [],
            'actions_to_execute' => $result['actions'] ?? [],
        ], 'Rule test completed successfully');
    }

    /**
     * Get rule execution history
     */
    public function executionHistory(string $id, Request $request)
    {
        $rule = AutomationRule::findOrFail($id);

        $executions = AutomationExecution::where('rule_id', $rule->rule_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->days, fn($q) => $q->where('executed_at', '>=', now()->subDays($request->days)))
            ->with('rule')
            ->latest('executed_at')
            ->paginate($request->get('per_page', 20));

        return $this->paginated($executions, 'Execution history retrieved successfully');
    }

    /**
     * Get rule analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');
        $days = $request->get('days', 30);

        $totalRules = AutomationRule::where('org_id', $orgId)->count();
        $activeRules = AutomationRule::where('org_id', $orgId)->active()->count();

        $executions = AutomationExecution::whereHas('rule', function ($q) use ($orgId) {
            $q->where('org_id', $orgId);
        })->where('executed_at', '>=', now()->subDays($days));

        $analytics = [
            'total_rules' => $totalRules,
            'active_rules' => $activeRules,
            'paused_rules' => AutomationRule::where('org_id', $orgId)
                ->where('status', 'paused')->count(),
            'archived_rules' => AutomationRule::where('org_id', $orgId)
                ->where('status', 'archived')->count(),
            'total_executions' => $executions->count(),
            'successful_executions' => (clone $executions)->where('status', 'success')->count(),
            'failed_executions' => (clone $executions)->where('status', 'failure')->count(),
            'by_type' => AutomationRule::where('org_id', $orgId)
                ->selectRaw('rule_type, COUNT(*) as count')
                ->groupBy('rule_type')
                ->pluck('count', 'rule_type'),
            'top_performing' => AutomationRule::where('org_id', $orgId)
                ->where('execution_count', '>', 0)
                ->orderByDesc('success_count')
                ->limit(5)
                ->get(['rule_id', 'name', 'execution_count', 'success_count']),
        ];

        return $this->success($analytics, 'Analytics retrieved successfully');
    }

    /**
     * Duplicate rule
     */
    public function duplicate(string $id)
    {
        $rule = AutomationRule::findOrFail($id);

        $duplicated = $rule->replicate();
        $duplicated->name = $rule->name . ' (Copy)';
        $duplicated->status = 'draft';
        $duplicated->enabled = false;
        $duplicated->execution_count = 0;
        $duplicated->success_count = 0;
        $duplicated->failure_count = 0;
        $duplicated->last_executed_at = null;
        $duplicated->save();

        return $this->created($duplicated, 'Automation rule duplicated successfully');
    }

    /**
     * Bulk update rules
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_ids' => 'required|array|min:1',
            'rule_ids.*' => 'uuid',
            'action' => 'required|string|in:activate,pause,archive,delete',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $rules = AutomationRule::where('org_id', $orgId)
            ->whereIn('rule_id', $request->rule_ids)
            ->get();

        foreach ($rules as $rule) {
            match ($request->action) {
                'activate' => $rule->activate(),
                'pause' => $rule->pause(),
                'archive' => $rule->archive(),
                'delete' => $rule->delete(),
            };
        }

        return $this->success([
            'updated_count' => $rules->count(),
            'action' => $request->action,
        ], "Bulk {$request->action} completed successfully");
    }

    /**
     * Get rule suggestions
     */
    public function suggestions(Request $request)
    {
        $orgId = session('current_org_id');

        $suggestions = $this->rulesEngine->generateRuleSuggestions($orgId, [
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
        ]);

        return $this->success($suggestions, 'Rule suggestions retrieved successfully');
    }
}
