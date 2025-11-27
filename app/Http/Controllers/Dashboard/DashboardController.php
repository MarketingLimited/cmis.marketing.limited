<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Dashboard\Dashboard;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use ApiResponse;

    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display a listing of dashboards
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $dashboards = Dashboard::where('org_id', $orgId)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->created_by, fn($q) => $q->where('created_by', $request->created_by))
            ->with(['creator', 'widgets'])
            ->latest('updated_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($dashboards, 'Dashboards retrieved successfully');
        }

        return view('dashboards.index', compact('dashboards'));
    }

    /**
     * Show the form for creating a new dashboard
     */
    public function create()
    {
        return view('dashboards.create');
    }

    /**
     * Store a newly created dashboard
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Dashboard::createRules(), Dashboard::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $dashboard = $this->dashboardService->createDashboard($request->all());

        if ($request->expectsJson()) {
            return $this->created($dashboard, 'Dashboard created successfully');
        }

        return redirect()->route('dashboards.show', $dashboard->dashboard_id)
            ->with('success', 'Dashboard created successfully');
    }

    /**
     * Display the specified dashboard
     */
    public function show(string $id)
    {
        $dashboard = Dashboard::with(['widgets', 'creator'])
            ->findOrFail($id);

        // Get widget data
        $widgetData = $this->dashboardService->getWidgetsData($dashboard);

        if (request()->expectsJson()) {
            return $this->success([
                'dashboard' => $dashboard,
                'widgets_data' => $widgetData,
            ], 'Dashboard retrieved successfully');
        }

        return view('dashboards.show', compact('dashboard', 'widgetData'));
    }

    /**
     * Show the form for editing the specified dashboard
     */
    public function edit(string $id)
    {
        $dashboard = Dashboard::with('widgets')->findOrFail($id);

        return view('dashboards.edit', compact('dashboard'));
    }

    /**
     * Update the specified dashboard
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), Dashboard::updateRules(), Dashboard::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $dashboard = Dashboard::findOrFail($id);
        $dashboard->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($dashboard, 'Dashboard updated successfully');
        }

        return redirect()->route('dashboards.show', $dashboard->dashboard_id)
            ->with('success', 'Dashboard updated successfully');
    }

    /**
     * Remove the specified dashboard
     */
    public function destroy(string $id)
    {
        $dashboard = Dashboard::findOrFail($id);
        $dashboard->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Dashboard deleted successfully');
        }

        return redirect()->route('dashboards.index')
            ->with('success', 'Dashboard deleted successfully');
    }

    /**
     * Duplicate a dashboard
     */
    public function duplicate(string $id)
    {
        $dashboard = Dashboard::with('widgets')->findOrFail($id);

        $duplicated = $this->dashboardService->duplicateDashboard($dashboard);

        if (request()->expectsJson()) {
            return $this->created($duplicated, 'Dashboard duplicated successfully');
        }

        return redirect()->route('dashboards.show', $duplicated->dashboard_id)
            ->with('success', 'Dashboard duplicated successfully');
    }

    /**
     * Share a dashboard
     */
    public function share(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'uuid',
            'org_ids' => 'nullable|array',
            'org_ids.*' => 'uuid',
            'permission' => 'required|in:view,edit',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $dashboard = Dashboard::findOrFail($id);

        $shares = $this->dashboardService->shareDashboard(
            $dashboard,
            $request->user_ids ?? [],
            $request->org_ids ?? [],
            $request->permission
        );

        return $this->success($shares, 'Dashboard shared successfully');
    }

    /**
     * Export dashboard configuration
     */
    public function export(string $id)
    {
        $dashboard = Dashboard::with('widgets')->findOrFail($id);

        $export = $this->dashboardService->exportDashboard($dashboard);

        if (request()->expectsJson()) {
            return $this->success($export, 'Dashboard exported successfully');
        }

        return response()->json($export)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="dashboard-' . $dashboard->dashboard_id . '.json"');
    }

    /**
     * Import dashboard configuration
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'config' => 'required|json',
            'overwrite' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $config = json_decode($request->config, true);

        $dashboard = $this->dashboardService->importDashboard($config, $request->overwrite ?? false);

        if ($request->expectsJson()) {
            return $this->created($dashboard, 'Dashboard imported successfully');
        }

        return redirect()->route('dashboards.show', $dashboard->dashboard_id)
            ->with('success', 'Dashboard imported successfully');
    }

    /**
     * Get dashboard templates
     */
    public function templates(Request $request)
    {
        $templates = $this->dashboardService->getTemplates();

        if ($request->expectsJson()) {
            return $this->success($templates, 'Templates retrieved successfully');
        }

        return view('dashboards.templates', compact('templates'));
    }

    /**
     * Create dashboard from template
     */
    public function createFromTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $dashboard = $this->dashboardService->createFromTemplate(
            $request->template_id,
            $request->name,
            $request->description
        );

        if ($request->expectsJson()) {
            return $this->created($dashboard, 'Dashboard created from template successfully');
        }

        return redirect()->route('dashboards.show', $dashboard->dashboard_id)
            ->with('success', 'Dashboard created from template successfully');
    }

    /**
     * Update dashboard layout
     */
    public function updateLayout(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'layout' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $dashboard = Dashboard::findOrFail($id);
        $dashboard->update(['layout' => $request->layout]);

        return $this->success($dashboard, 'Dashboard layout updated successfully');
    }

    /**
     * Get dashboard analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->dashboardService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Analytics retrieved successfully');
        }

        return view('dashboards.analytics', compact('analytics'));
    }

    /**
     * Set default dashboard
     */
    public function setDefault(string $id)
    {
        $orgId = session('current_org_id');
        $userId = auth()->id();

        $this->dashboardService->setDefaultDashboard($id, $userId, $orgId);

        if (request()->expectsJson()) {
            return $this->success(null, 'Default dashboard set successfully');
        }

        return redirect()->back()
            ->with('success', 'Default dashboard set successfully');
    }

    /**
     * Refresh all widgets in dashboard
     */
    public function refreshWidgets(string $id)
    {
        $dashboard = Dashboard::with('widgets')->findOrFail($id);

        $widgetData = $this->dashboardService->refreshAllWidgets($dashboard);

        return $this->success($widgetData, 'Widgets refreshed successfully');
    }

    /**
     * Get dashboard snapshot
     */
    public function snapshot(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:pdf,png,json',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $dashboard = Dashboard::with('widgets')->findOrFail($id);
        $format = $request->format ?? 'json';

        $snapshot = $this->dashboardService->createSnapshot($dashboard, $format);

        return $this->success($snapshot, 'Dashboard snapshot created successfully');
    }
}
