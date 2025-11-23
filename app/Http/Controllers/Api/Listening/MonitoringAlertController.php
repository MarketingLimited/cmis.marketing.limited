<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\MonitoringAlert;
use App\Services\Listening\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Monitoring Alert Controller
 *
 * Manages monitoring alerts and notification rules
 */
class MonitoringAlertController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AlertService $alertService
    ) {}

    /**
     * Get alerts
     *
     * GET /api/listening/alerts
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $alerts = MonitoringAlert::where('org_id', $orgId);

        if ($request->has('status')) {
            $alerts->where('status', $request->status);
        }

        if ($request->has('type')) {
            $alerts->where('alert_type', $request->type);
        }

        $alerts = $alerts->orderBy('severity', 'desc')->get();

        return $this->success($alerts, 'Alerts retrieved successfully');
    }

    /**
     * Create alert
     *
     * POST /api/listening/alerts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'alert_name' => 'required|string|max:255',
            'alert_type' => 'required|in:mention,sentiment,volume,competitor,trend',
            'description' => 'string|nullable',
            'trigger_conditions' => 'required|array',
            'severity' => 'in:low,medium,high,critical',
            'threshold_value' => 'integer|nullable',
            'threshold_unit' => 'string|nullable',
            'notification_channels' => 'array',
            'recipients' => 'array',
        ]);

        $alert = $this->alertService->createAlert(
            $request->user()->org_id,
            $request->user()->id,
            $validated
        );

        return $this->created($alert, 'Alert created successfully');
    }

    /**
     * Show single alert
     *
     * GET /api/listening/alerts/{id}
     */
    public function show(string $id): JsonResponse
    {
        $alert = MonitoringAlert::findOrFail($id);

        return $this->success($alert, 'Alert retrieved successfully');
    }

    /**
     * Update alert
     *
     * PUT/PATCH /api/listening/alerts/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $alert = MonitoringAlert::findOrFail($id);

        $validated = $request->validate([
            'alert_name' => 'string|max:255',
            'description' => 'string|nullable',
            'trigger_conditions' => 'array',
            'severity' => 'in:low,medium,high,critical',
            'threshold_value' => 'integer|nullable',
            'status' => 'in:active,paused,archived',
        ]);

        $alert->update($validated);

        return $this->success($alert->fresh(), 'Alert updated successfully');
    }

    /**
     * Delete alert
     *
     * DELETE /api/listening/alerts/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $alert = MonitoringAlert::findOrFail($id);
        $alert->delete();

        return $this->deleted('Alert deleted successfully');
    }
}
