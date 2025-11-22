<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAlertsJob;
use App\Models\Analytics\AlertHistory;
use App\Models\Analytics\AlertRule;
use App\Models\Analytics\AlertTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alerts Controller (Phase 13)
 *
 * Manages real-time alert rules, history, and notifications
 */
class AlertsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List alert rules
     * GET /api/orgs/{org_id}/alerts/rules
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = AlertRule::where('org_id', $orgId)
            ->with(['creator', 'recentAlerts']);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $rules = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'rules' => $rules
        ]);
    }

    /**
     * Create alert rule
     * POST /api/orgs/{org_id}/alerts/rules
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'sometimes|uuid',
            'metric' => 'required|string|max:100',
            'condition' => 'required|in:gt,gte,lt,lte,eq,ne,change_pct',
            'threshold' => 'required|numeric',
            'time_window_minutes' => 'sometimes|integer|min:1|max:1440',
            'severity' => 'required|in:critical,high,medium,low',
            'notification_channels' => 'required|array',
            'notification_channels.*' => 'in:email,in_app,slack,webhook',
            'notification_config' => 'required|array',
            'cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $rule = AlertRule::create([
            'org_id' => $orgId,
            'created_by' => $user->user_id,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'rule' => $rule->load('creator'),
            'message' => 'Alert rule created successfully'
        ], 201);
    }

    /**
     * Get alert rule
     * GET /api/orgs/{org_id}/alerts/rules/{rule_id}
     */
    public function show(string $orgId, string $ruleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $rule = AlertRule::with(['creator', 'recentAlerts'])
            ->findOrFail($ruleId);

        return response()->json([
            'success' => true,
            'rule' => $rule
        ]);
    }

    /**
     * Update alert rule
     * PUT /api/orgs/{org_id}/alerts/rules/{rule_id}
     */
    public function update(string $orgId, string $ruleId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'metric' => 'sometimes|string|max:100',
            'condition' => 'sometimes|in:gt,gte,lt,lte,eq,ne,change_pct',
            'threshold' => 'sometimes|numeric',
            'time_window_minutes' => 'sometimes|integer|min:1|max:1440',
            'severity' => 'sometimes|in:critical,high,medium,low',
            'notification_channels' => 'sometimes|array',
            'notification_config' => 'sometimes|array',
            'cooldown_minutes' => 'sometimes|integer|min:1|max:1440',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $rule = AlertRule::findOrFail($ruleId);
        $rule->update($validated);

        return response()->json([
            'success' => true,
            'rule' => $rule->fresh(['creator']),
            'message' => 'Alert rule updated successfully'
        ]);
    }

    /**
     * Delete alert rule
     * DELETE /api/orgs/{org_id}/alerts/rules/{rule_id}
     */
    public function destroy(string $orgId, string $ruleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $rule = AlertRule::findOrFail($ruleId);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert rule deleted successfully'
        ]);
    }

    /**
     * Get alert history
     * GET /api/orgs/{org_id}/alerts/history
     */
    public function history(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = AlertHistory::where('org_id', $orgId)
            ->with(['rule', 'acknowledger', 'notifications']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('rule_id')) {
            $query->where('rule_id', $request->input('rule_id'));
        }

        if ($request->has('days')) {
            $query->recent($request->integer('days'));
        }

        $alerts = $query->latest('triggered_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'alerts' => $alerts
        ]);
    }

    /**
     * Acknowledge alert
     * POST /api/orgs/{org_id}/alerts/{alert_id}/acknowledge
     */
    public function acknowledge(string $orgId, string $alertId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $alert = AlertHistory::findOrFail($alertId);
        $alert->acknowledge($user->user_id, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'alert' => $alert->fresh(),
            'message' => 'Alert acknowledged'
        ]);
    }

    /**
     * Resolve alert
     * POST /api/orgs/{org_id}/alerts/{alert_id}/resolve
     */
    public function resolve(string $orgId, string $alertId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $alert = AlertHistory::findOrFail($alertId);
        $alert->resolve($user->user_id, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'alert' => $alert->fresh(),
            'message' => 'Alert resolved'
        ]);
    }

    /**
     * Snooze alert
     * POST /api/orgs/{org_id}/alerts/{alert_id}/snooze
     */
    public function snooze(string $orgId, string $alertId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|min:15|max:1440'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $alert = AlertHistory::findOrFail($alertId);
        $alert->snooze($validated['minutes']);

        return response()->json([
            'success' => true,
            'alert' => $alert->fresh(),
            'message' => "Alert snoozed for {$validated['minutes']} minutes"
        ]);
    }

    /**
     * Test alert rule
     * POST /api/orgs/{org_id}/alerts/rules/{rule_id}/test
     */
    public function testRule(string $orgId, string $ruleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $rule = AlertRule::findOrFail($ruleId);

        // Dispatch evaluation job
        ProcessAlertsJob::dispatch('rule', ['rule_id' => $ruleId]);

        return response()->json([
            'success' => true,
            'message' => 'Alert rule evaluation queued'
        ]);
    }

    /**
     * Get alert templates
     * GET /api/alerts/templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = AlertTemplate::query();

        $query->where(function ($q) use ($request) {
            $q->where('is_public', true)
              ->orWhere('created_by', $request->user()->user_id);
        });

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        $templates = $query->orderBy('usage_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Create rule from template
     * POST /api/orgs/{org_id}/alerts/rules/from-template/{template_id}
     */
    public function createFromTemplate(string $orgId, string $templateId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_id' => 'sometimes|uuid',
            'config_overrides' => 'sometimes|array'
        ]);

        $template = AlertTemplate::findOrFail($templateId);
        $template->incrementUsage();

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $config = array_merge(
            $template->default_config,
            $validated['config_overrides'] ?? []
        );

        $rule = AlertRule::create([
            'org_id' => $orgId,
            'created_by' => $user->user_id,
            'name' => $validated['name'],
            'entity_type' => $template->entity_type,
            'entity_id' => $validated['entity_id'] ?? null,
            ...$config
        ]);

        return response()->json([
            'success' => true,
            'rule' => $rule->load('creator'),
            'message' => 'Alert rule created from template'
        ], 201);
    }
}
