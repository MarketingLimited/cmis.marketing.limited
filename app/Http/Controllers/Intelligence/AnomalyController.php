<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Intelligence\Anomaly;
use App\Services\Intelligence\AnomalyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnomalyController extends Controller
{
    use ApiResponse;

    protected AnomalyService $anomalyService;

    public function __construct(AnomalyService $anomalyService)
    {
        $this->anomalyService = $anomalyService;
    }

    /**
     * Display a listing of anomalies
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $anomalies = Anomaly::where('org_id', $orgId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->severity, fn($q) => $q->bySeverity($request->severity))
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->unresolved_only, fn($q) => $q->unresolved())
            ->when($request->critical_only, fn($q) => $q->critical())
            ->with(['creator', 'resolver'])
            ->latest('detected_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($anomalies, 'Anomalies retrieved successfully');
        }

        return view('intelligence.anomalies.index', compact('anomalies'));
    }

    /**
     * Display the specified anomaly
     */
    public function show(string $id)
    {
        $anomaly = Anomaly::with(['creator', 'resolver', 'entity'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($anomaly, 'Anomaly retrieved successfully');
        }

        return view('intelligence.anomalies.show', compact('anomaly'));
    }

    /**
     * Detect anomalies for an entity
     */
    public function detect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'metrics' => 'required|array|min:1',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $anomalies = $this->anomalyService->detectAnomalies(
            $request->entity_type,
            $request->entity_id,
            $request->metrics,
            $request->date_from,
            $request->date_to
        );

        return $this->success($anomalies, 'Anomaly detection completed');
    }

    /**
     * Mark anomaly as resolved
     */
    public function resolve(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'resolution' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $anomaly = Anomaly::findOrFail($id);
        $anomaly->markAsResolved($request->resolution, auth()->id());

        if ($request->expectsJson()) {
            return $this->success($anomaly, 'Anomaly resolved successfully');
        }

        return redirect()->route('anomalies.show', $anomaly->anomaly_id)
            ->with('success', 'Anomaly resolved successfully');
    }

    /**
     * Mark anomaly as false positive
     */
    public function markFalsePositive(Request $request, string $id)
    {
        $anomaly = Anomaly::findOrFail($id);
        $anomaly->markAsFalsePositive(auth()->id());

        if ($request->expectsJson()) {
            return $this->success($anomaly, 'Anomaly marked as false positive');
        }

        return redirect()->route('anomalies.index')
            ->with('success', 'Anomaly marked as false positive');
    }

    /**
     * Mark anomaly as investigating
     */
    public function investigate(Request $request, string $id)
    {
        $anomaly = Anomaly::findOrFail($id);
        $anomaly->markAsInvestigating();

        if ($request->expectsJson()) {
            return $this->success($anomaly, 'Anomaly marked as investigating');
        }

        return redirect()->route('anomalies.show', $anomaly->anomaly_id)
            ->with('success', 'Anomaly marked as investigating');
    }

    /**
     * Get anomaly analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->anomalyService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Anomaly analytics retrieved successfully');
        }

        return view('intelligence.anomalies.analytics', compact('analytics'));
    }

    /**
     * Get anomalies summary by severity
     */
    public function summary(Request $request)
    {
        $orgId = session('current_org_id');

        $summary = $this->anomalyService->getSummary($orgId, $request->days ?? 30);

        return $this->success($summary, 'Anomaly summary retrieved successfully');
    }
}
