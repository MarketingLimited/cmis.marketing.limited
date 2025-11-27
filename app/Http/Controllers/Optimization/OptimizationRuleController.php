<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Optimization\OptimizationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OptimizationRuleController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of optimization rules
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $rules = OptimizationRule::where('org_id', $orgId)
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->rule_type, fn($q) => $q->where('rule_type', $request->rule_type))
            ->when($request->is_active, fn($q) => $q->where('is_active', $request->is_active === 'true'))
            ->when($request->search, fn($q) => $q->where('rule_name', 'like', "%{$request->search}%"))
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($rules, 'Optimization rules retrieved successfully');
        }

        return view('optimization.rules.index', compact('rules'));
    }

    /**
     * Store a newly created optimization rule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), OptimizationRule::createRules(), OptimizationRule::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $rule = OptimizationRule::create(array_merge($request->all(), [
            'org_id' => session('current_org_id'),
            'created_by' => auth()->id(),
        ]));

        if ($request->expectsJson()) {
            return $this->created($rule, 'Optimization rule created successfully');
        }

        return redirect()->route('optimization.rules.show', $rule->rule_id)
            ->with('success', 'Optimization rule created successfully');
    }

    /**
     * Display the specified optimization rule
     */
    public function show(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($rule, 'Optimization rule retrieved successfully');
        }

        return view('optimization.rules.show', compact('rule'));
    }

    /**
     * Update the specified optimization rule
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), OptimizationRule::updateRules(), OptimizationRule::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $rule = OptimizationRule::findOrFail($id);
        $rule->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($rule, 'Optimization rule updated successfully');
        }

        return redirect()->route('optimization.rules.show', $rule->rule_id)
            ->with('success', 'Optimization rule updated successfully');
    }

    /**
     * Remove the specified optimization rule
     */
    public function destroy(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);
        $rule->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Optimization rule deleted successfully');
        }

        return redirect()->route('optimization.rules.index')
            ->with('success', 'Optimization rule deleted successfully');
    }

    /**
     * Activate optimization rule
     */
    public function activate(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);

        $rule->update([
            'is_active' => true,
            'last_triggered_at' => null,
        ]);

        return $this->success($rule, 'Optimization rule activated successfully');
    }

    /**
     * Deactivate optimization rule
     */
    public function deactivate(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);

        $rule->update(['is_active' => false]);

        return $this->success($rule, 'Optimization rule deactivated successfully');
    }

    /**
     * Test optimization rule without applying
     */
    public function test(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'test_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $rule = OptimizationRule::findOrFail($id);

        // Simulate rule evaluation
        $testData = $request->test_data;
        $conditions = $rule->conditions ?? [];

        $results = [
            'rule_id' => $rule->rule_id,
            'rule_name' => $rule->rule_name,
            'would_trigger' => $this->evaluateConditions($conditions, $testData),
            'actions' => $rule->actions ?? [],
            'test_data' => $testData,
        ];

        return $this->success($results, 'Rule test completed successfully');
    }

    /**
     * Get rule execution history
     */
    public function executionHistory(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);

        $history = [
            'rule_id' => $rule->rule_id,
            'total_executions' => $rule->execution_count ?? 0,
            'last_triggered_at' => $rule->last_triggered_at,
            'success_rate' => 100, // Could be calculated from actual execution logs
        ];

        return $this->success($history, 'Rule execution history retrieved successfully');
    }

    /**
     * Get optimization suggestions
     */
    public function suggestions(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        // Get applicable rules
        $applicableRules = OptimizationRule::where('org_id', $orgId)
            ->where('entity_type', $request->entity_type)
            ->where('is_active', true)
            ->get();

        $suggestions = $applicableRules->map(function ($rule) {
            return [
                'rule_id' => $rule->rule_id,
                'rule_name' => $rule->rule_name,
                'rule_type' => $rule->rule_type,
                'description' => $rule->description,
                'actions' => $rule->actions,
                'priority' => $rule->priority ?? 'medium',
            ];
        });

        return $this->success($suggestions, 'Optimization suggestions retrieved successfully');
    }

    /**
     * Get rule analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $rules = OptimizationRule::where('org_id', $orgId)->get();

        $totalRules = $rules->count();
        $activeRules = $rules->where('is_active', true)->count();
        $totalExecutions = $rules->sum('execution_count');

        $analytics = [
            'summary' => [
                'total_rules' => $totalRules,
                'active_rules' => $activeRules,
                'inactive_rules' => $totalRules - $activeRules,
                'total_executions' => $totalExecutions,
            ],
            'by_type' => $rules->groupBy('rule_type')->map->count(),
            'by_entity_type' => $rules->groupBy('entity_type')->map->count(),
            'most_triggered' => $rules->sortByDesc('execution_count')->take(5)->map(fn($r) => [
                'rule_id' => $r->rule_id,
                'rule_name' => $r->rule_name,
                'execution_count' => $r->execution_count ?? 0,
            ])->values(),
        ];

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Optimization analytics retrieved successfully');
        }

        return view('optimization.rules.analytics', compact('analytics'));
    }

    /**
     * Bulk update rules
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rule_ids' => 'required|array',
            'rule_ids.*' => 'uuid',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $updated = OptimizationRule::where('org_id', $orgId)
            ->whereIn('rule_id', $request->rule_ids)
            ->update(array_filter([
                'is_active' => $request->is_active,
            ]));

        return $this->success([
            'updated_count' => $updated,
        ], 'Rules updated successfully');
    }

    /**
     * Duplicate optimization rule
     */
    public function duplicate(string $id)
    {
        $rule = OptimizationRule::findOrFail($id);

        $duplicated = OptimizationRule::create([
            'org_id' => $rule->org_id,
            'rule_name' => $rule->rule_name . ' (Copy)',
            'rule_type' => $rule->rule_type,
            'entity_type' => $rule->entity_type,
            'entity_id' => $rule->entity_id,
            'conditions' => $rule->conditions,
            'actions' => $rule->actions,
            'priority' => $rule->priority,
            'description' => $rule->description,
            'is_active' => false, // Start as inactive
            'created_by' => auth()->id(),
        ]);

        if (request()->expectsJson()) {
            return $this->created($duplicated, 'Rule duplicated successfully');
        }

        return redirect()->route('optimization.rules.show', $duplicated->rule_id)
            ->with('success', 'Rule duplicated successfully');
    }

    /**
     * Evaluate rule conditions against test data
     */
    protected function evaluateConditions(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            $actualValue = $data[$field] ?? null;

            $result = match ($operator) {
                '=' => $actualValue == $value,
                '!=' => $actualValue != $value,
                '>' => $actualValue > $value,
                '>=' => $actualValue >= $value,
                '<' => $actualValue < $value,
                '<=' => $actualValue <= $value,
                'contains' => str_contains((string)$actualValue, (string)$value),
                'not_contains' => !str_contains((string)$actualValue, (string)$value),
                default => false,
            };

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
