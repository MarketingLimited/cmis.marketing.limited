<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Dashboard\Dashboard;
use App\Models\Dashboard\DashboardWidget;
use App\Services\Dashboard\DashboardWidgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardWidgetController extends Controller
{
    use ApiResponse;

    protected DashboardWidgetService $widgetService;

    public function __construct(DashboardWidgetService $widgetService)
    {
        $this->widgetService = $widgetService;
    }

    /**
     * Display a listing of widgets for a dashboard
     */
    public function index(Request $request, string $dashboardId)
    {
        $dashboard = Dashboard::findOrFail($dashboardId);

        $widgets = $dashboard->widgets()
            ->when($request->widget_type, fn($q) => $q->where('widget_type', $request->widget_type))
            ->orderBy('position_y')
            ->orderBy('position_x')
            ->get();

        if ($request->expectsJson()) {
            return $this->success($widgets, 'Widgets retrieved successfully');
        }

        return view('dashboards.widgets.index', compact('dashboard', 'widgets'));
    }

    /**
     * Store a newly created widget
     */
    public function store(Request $request, string $dashboardId)
    {
        $dashboard = Dashboard::findOrFail($dashboardId);

        $validator = Validator::make($request->all(), DashboardWidget::createRules(), DashboardWidget::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $widget = DashboardWidget::create(array_merge($request->all(), [
            'dashboard_id' => $dashboardId,
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($widget, 'Widget created successfully');
        }

        return redirect()->route('dashboards.show', $dashboardId)
            ->with('success', __('dashboardwidget.created_success'));
    }

    /**
     * Display the specified widget
     */
    public function show(string $dashboardId, string $id)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        // Get widget data
        $data = $this->widgetService->getWidgetData($widget);

        if (request()->expectsJson()) {
            return $this->success([
                'widget' => $widget,
                'data' => $data,
            ], 'Widget retrieved successfully');
        }

        return view('dashboards.widgets.show', compact('widget', 'data'));
    }

    /**
     * Update the specified widget
     */
    public function update(Request $request, string $dashboardId, string $id)
    {
        $validator = Validator::make($request->all(), DashboardWidget::updateRules(), DashboardWidget::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $widget->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($widget, 'Widget updated successfully');
        }

        return redirect()->route('dashboards.show', $dashboardId)
            ->with('success', __('dashboardwidget.updated_success'));
    }

    /**
     * Remove the specified widget
     */
    public function destroy(string $dashboardId, string $id)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $widget->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Widget deleted successfully');
        }

        return redirect()->route('dashboards.show', $dashboardId)
            ->with('success', __('dashboardwidget.deleted_success'));
    }

    /**
     * Update widget position and size
     */
    public function updatePosition(Request $request, string $dashboardId, string $id)
    {
        $validator = Validator::make($request->all(), [
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'nullable|integer|min:1|max:12',
            'height' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $widget->update([
            'position_x' => $request->position_x,
            'position_y' => $request->position_y,
            'width' => $request->width ?? $widget->width,
            'height' => $request->height ?? $widget->height,
        ]);

        return $this->success($widget, 'Widget position updated successfully');
    }

    /**
     * Refresh widget data
     */
    public function refresh(string $dashboardId, string $id)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $data = $this->widgetService->refreshWidget($widget);

        return $this->success($data, 'Widget refreshed successfully');
    }

    /**
     * Duplicate a widget
     */
    public function duplicate(string $dashboardId, string $id)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $duplicated = $this->widgetService->duplicateWidget($widget);

        if (request()->expectsJson()) {
            return $this->created($duplicated, 'Widget duplicated successfully');
        }

        return redirect()->route('dashboards.show', $dashboardId)
            ->with('success', __('dashboardwidget.widget_duplicated_successfully'));
    }

    /**
     * Move widget to another dashboard
     */
    public function move(Request $request, string $dashboardId, string $id)
    {
        $validator = Validator::make($request->all(), [
            'target_dashboard_id' => 'required|uuid|exists:cmis_dashboard.dashboards,dashboard_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $widget->update(['dashboard_id' => $request->target_dashboard_id]);

        return $this->success($widget, 'Widget moved successfully');
    }

    /**
     * Get available widget types
     */
    public function types()
    {
        $types = $this->widgetService->getAvailableTypes();

        return $this->success($types, 'Widget types retrieved successfully');
    }

    /**
     * Preview widget with configuration
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'widget_type' => 'required|string',
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $preview = $this->widgetService->previewWidget(
            $request->widget_type,
            $request->config
        );

        return $this->success($preview, 'Widget preview generated successfully');
    }

    /**
     * Export widget configuration
     */
    public function export(string $dashboardId, string $id)
    {
        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $export = $this->widgetService->exportWidget($widget);

        return $this->success($export, 'Widget exported successfully');
    }

    /**
     * Update widget refresh interval
     */
    public function updateRefreshInterval(Request $request, string $dashboardId, string $id)
    {
        $validator = Validator::make($request->all(), [
            'refresh_interval' => 'nullable|integer|min:10|max:3600',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $widget = DashboardWidget::where('dashboard_id', $dashboardId)
            ->findOrFail($id);

        $widget->update(['refresh_interval' => $request->refresh_interval]);

        return $this->success($widget, 'Widget refresh interval updated successfully');
    }

    /**
     * Bulk update widget positions (for drag-and-drop)
     */
    public function bulkUpdatePositions(Request $request, string $dashboardId)
    {
        $validator = Validator::make($request->all(), [
            'widgets' => 'required|array',
            'widgets.*.widget_id' => 'required|uuid',
            'widgets.*.position_x' => 'required|integer|min:0',
            'widgets.*.position_y' => 'required|integer|min:0',
            'widgets.*.width' => 'nullable|integer|min:1|max:12',
            'widgets.*.height' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $updated = $this->widgetService->bulkUpdatePositions($dashboardId, $request->widgets);

        return $this->success([
            'updated_count' => $updated,
        ], 'Widget positions updated successfully');
    }

    /**
     * Get widget query builder
     */
    public function queryBuilder(Request $request, string $dashboardId)
    {
        $validator = Validator::make($request->all(), [
            'widget_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $queryBuilder = $this->widgetService->getQueryBuilder($request->widget_type);

        return $this->success($queryBuilder, 'Query builder retrieved successfully');
    }
}
