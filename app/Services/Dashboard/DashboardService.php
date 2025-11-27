<?php

namespace App\Services\Dashboard;

use App\Models\Dashboard\Dashboard;
use App\Models\Dashboard\DashboardWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    protected DashboardWidgetService $widgetService;

    public function __construct(DashboardWidgetService $widgetService)
    {
        $this->widgetService = $widgetService;
    }

    /**
     * Create a new dashboard
     */
    public function createDashboard(array $data): Dashboard
    {
        $orgId = session('current_org_id');

        return Dashboard::create(array_merge($data, [
            'org_id' => $orgId,
            'created_by' => auth()->id(),
        ]));
    }

    /**
     * Duplicate a dashboard with all its widgets
     */
    public function duplicateDashboard(Dashboard $dashboard): Dashboard
    {
        DB::beginTransaction();
        try {
            // Create new dashboard
            $duplicated = Dashboard::create([
                'org_id' => $dashboard->org_id,
                'name' => $dashboard->name . ' (Copy)',
                'description' => $dashboard->description,
                'layout' => $dashboard->layout,
                'config' => $dashboard->config,
                'created_by' => auth()->id(),
            ]);

            // Duplicate all widgets
            foreach ($dashboard->widgets as $widget) {
                DashboardWidget::create([
                    'dashboard_id' => $duplicated->dashboard_id,
                    'org_id' => $dashboard->org_id,
                    'widget_type' => $widget->widget_type,
                    'name' => $widget->name,
                    'config' => $widget->config,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'refresh_interval' => $widget->refresh_interval,
                ]);
            }

            DB::commit();
            return $duplicated->fresh(['widgets']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Share dashboard with users or organizations
     */
    public function shareDashboard(Dashboard $dashboard, array $userIds, array $orgIds, string $permission): array
    {
        $shares = [];

        // Share with users
        foreach ($userIds as $userId) {
            $shares[] = [
                'type' => 'user',
                'id' => $userId,
                'permission' => $permission,
            ];
        }

        // Share with organizations
        foreach ($orgIds as $orgId) {
            $shares[] = [
                'type' => 'organization',
                'id' => $orgId,
                'permission' => $permission,
            ];
        }

        // Update dashboard config with shares
        $config = $dashboard->config ?? [];
        $config['shares'] = $shares;
        $dashboard->update(['config' => $config]);

        return $shares;
    }

    /**
     * Export dashboard configuration
     */
    public function exportDashboard(Dashboard $dashboard): array
    {
        return [
            'name' => $dashboard->name,
            'description' => $dashboard->description,
            'layout' => $dashboard->layout,
            'config' => $dashboard->config,
            'widgets' => $dashboard->widgets->map(function ($widget) {
                return [
                    'widget_type' => $widget->widget_type,
                    'name' => $widget->name,
                    'config' => $widget->config,
                    'position_x' => $widget->position_x,
                    'position_y' => $widget->position_y,
                    'width' => $widget->width,
                    'height' => $widget->height,
                    'refresh_interval' => $widget->refresh_interval,
                ];
            })->toArray(),
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
        ];
    }

    /**
     * Import dashboard configuration
     */
    public function importDashboard(array $config, bool $overwrite = false): Dashboard
    {
        $orgId = session('current_org_id');

        DB::beginTransaction();
        try {
            // Check if dashboard with same name exists
            $existing = Dashboard::where('org_id', $orgId)
                ->where('name', $config['name'])
                ->first();

            if ($existing && !$overwrite) {
                throw new \Exception("Dashboard with name '{$config['name']}' already exists. Use overwrite=true to replace.");
            }

            if ($existing && $overwrite) {
                // Delete existing widgets
                $existing->widgets()->delete();
                $dashboard = $existing;
            } else {
                $dashboard = new Dashboard();
                $dashboard->org_id = $orgId;
                $dashboard->created_by = auth()->id();
            }

            // Set dashboard properties
            $dashboard->name = $config['name'];
            $dashboard->description = $config['description'] ?? null;
            $dashboard->layout = $config['layout'] ?? null;
            $dashboard->config = $config['config'] ?? null;
            $dashboard->save();

            // Import widgets
            if (!empty($config['widgets'])) {
                foreach ($config['widgets'] as $widgetConfig) {
                    DashboardWidget::create([
                        'dashboard_id' => $dashboard->dashboard_id,
                        'org_id' => $orgId,
                        'widget_type' => $widgetConfig['widget_type'],
                        'name' => $widgetConfig['name'],
                        'config' => $widgetConfig['config'],
                        'position_x' => $widgetConfig['position_x'] ?? 0,
                        'position_y' => $widgetConfig['position_y'] ?? 0,
                        'width' => $widgetConfig['width'] ?? 4,
                        'height' => $widgetConfig['height'] ?? 3,
                        'refresh_interval' => $widgetConfig['refresh_interval'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return $dashboard->fresh(['widgets']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get dashboard templates
     */
    public function getTemplates(): array
    {
        return [
            [
                'id' => 'campaign_overview',
                'name' => 'Campaign Overview',
                'description' => 'Overview of all active campaigns with key metrics',
                'category' => 'campaigns',
                'widgets' => [
                    ['type' => 'metric_card', 'title' => 'Total Campaigns', 'metric' => 'campaign_count'],
                    ['type' => 'metric_card', 'title' => 'Total Spend', 'metric' => 'total_spend'],
                    ['type' => 'metric_card', 'title' => 'Total Impressions', 'metric' => 'total_impressions'],
                    ['type' => 'metric_card', 'title' => 'Average CTR', 'metric' => 'avg_ctr'],
                    ['type' => 'line_chart', 'title' => 'Spend Trend', 'metric' => 'spend_over_time'],
                    ['type' => 'table', 'title' => 'Top Campaigns', 'metric' => 'top_campaigns'],
                ],
            ],
            [
                'id' => 'performance_metrics',
                'name' => 'Performance Metrics',
                'description' => 'Detailed performance metrics and trends',
                'category' => 'analytics',
                'widgets' => [
                    ['type' => 'bar_chart', 'title' => 'Performance by Platform', 'metric' => 'platform_performance'],
                    ['type' => 'pie_chart', 'title' => 'Budget Distribution', 'metric' => 'budget_distribution'],
                    ['type' => 'line_chart', 'title' => 'Conversion Trend', 'metric' => 'conversions_over_time'],
                    ['type' => 'metric_card', 'title' => 'Conversion Rate', 'metric' => 'conversion_rate'],
                ],
            ],
            [
                'id' => 'social_media',
                'name' => 'Social Media Dashboard',
                'description' => 'Social media engagement and reach metrics',
                'category' => 'social',
                'widgets' => [
                    ['type' => 'metric_card', 'title' => 'Total Followers', 'metric' => 'total_followers'],
                    ['type' => 'metric_card', 'title' => 'Engagement Rate', 'metric' => 'engagement_rate'],
                    ['type' => 'line_chart', 'title' => 'Reach Over Time', 'metric' => 'reach_over_time'],
                    ['type' => 'table', 'title' => 'Top Posts', 'metric' => 'top_posts'],
                ],
            ],
            [
                'id' => 'executive_summary',
                'name' => 'Executive Summary',
                'description' => 'High-level overview for executives',
                'category' => 'summary',
                'widgets' => [
                    ['type' => 'metric_card', 'title' => 'Total Revenue', 'metric' => 'total_revenue'],
                    ['type' => 'metric_card', 'title' => 'ROI', 'metric' => 'roi'],
                    ['type' => 'metric_card', 'title' => 'Active Campaigns', 'metric' => 'active_campaigns'],
                    ['type' => 'line_chart', 'title' => 'Revenue Trend', 'metric' => 'revenue_trend'],
                    ['type' => 'bar_chart', 'title' => 'Top Performing Channels', 'metric' => 'top_channels'],
                ],
            ],
        ];
    }

    /**
     * Create dashboard from template
     */
    public function createFromTemplate(string $templateId, string $name, ?string $description = null): Dashboard
    {
        $templates = collect($this->getTemplates());
        $template = $templates->firstWhere('id', $templateId);

        if (!$template) {
            throw new \Exception("Template '{$templateId}' not found");
        }

        $orgId = session('current_org_id');

        DB::beginTransaction();
        try {
            // Create dashboard
            $dashboard = Dashboard::create([
                'org_id' => $orgId,
                'name' => $name,
                'description' => $description ?? $template['description'],
                'created_by' => auth()->id(),
            ]);

            // Create widgets from template
            $y = 0;
            foreach ($template['widgets'] as $index => $widgetTemplate) {
                $x = ($index % 3) * 4; // 3 widgets per row
                if ($index % 3 === 0 && $index > 0) {
                    $y += 3;
                }

                DashboardWidget::create([
                    'dashboard_id' => $dashboard->dashboard_id,
                    'org_id' => $orgId,
                    'widget_type' => $widgetTemplate['type'],
                    'name' => $widgetTemplate['title'],
                    'config' => [
                        'metric' => $widgetTemplate['metric'],
                    ],
                    'position_x' => $x,
                    'position_y' => $y,
                    'width' => 4,
                    'height' => 3,
                ]);
            }

            DB::commit();
            return $dashboard->fresh(['widgets']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get analytics for organization
     */
    public function getAnalytics(string $orgId): array
    {
        $cacheKey = "dashboards:analytics:{$orgId}";

        return Cache::remember($cacheKey, 300, function () use ($orgId) {
            $dashboards = Dashboard::where('org_id', $orgId)->get();

            $totalDashboards = $dashboards->count();
            $totalWidgets = DashboardWidget::where('org_id', $orgId)->count();

            $mostUsed = $dashboards
                ->sortByDesc('last_viewed_at')
                ->take(5)
                ->map(fn($d) => [
                    'dashboard_id' => $d->dashboard_id,
                    'name' => $d->name,
                    'last_viewed_at' => $d->last_viewed_at,
                    'widget_count' => $d->widgets->count(),
                ])
                ->values();

            $widgetTypeDistribution = DashboardWidget::where('org_id', $orgId)
                ->select('widget_type', DB::raw('count(*) as count'))
                ->groupBy('widget_type')
                ->get()
                ->pluck('count', 'widget_type')
                ->toArray();

            return [
                'summary' => [
                    'total_dashboards' => $totalDashboards,
                    'total_widgets' => $totalWidgets,
                    'avg_widgets_per_dashboard' => $totalDashboards > 0 ? round($totalWidgets / $totalDashboards, 2) : 0,
                ],
                'most_used' => $mostUsed,
                'widget_types' => $widgetTypeDistribution,
            ];
        });
    }

    /**
     * Set default dashboard for user
     */
    public function setDefaultDashboard(string $dashboardId, string $userId, string $orgId): void
    {
        // Store in user preferences or config
        $cacheKey = "user:{$userId}:default_dashboard";
        Cache::forever($cacheKey, $dashboardId);
    }

    /**
     * Get widgets data for dashboard
     */
    public function getWidgetsData(Dashboard $dashboard): array
    {
        $widgetsData = [];

        foreach ($dashboard->widgets as $widget) {
            $widgetsData[$widget->widget_id] = $this->widgetService->getWidgetData($widget);
        }

        return $widgetsData;
    }

    /**
     * Refresh all widgets in dashboard
     */
    public function refreshAllWidgets(Dashboard $dashboard): array
    {
        $widgetsData = [];

        foreach ($dashboard->widgets as $widget) {
            $widgetsData[$widget->widget_id] = $this->widgetService->refreshWidget($widget);
        }

        // Update dashboard last viewed timestamp
        $dashboard->update(['last_viewed_at' => now()]);

        return $widgetsData;
    }

    /**
     * Create snapshot of dashboard
     */
    public function createSnapshot(Dashboard $dashboard, string $format = 'json'): array
    {
        $widgetsData = $this->getWidgetsData($dashboard);

        $snapshot = [
            'dashboard' => [
                'name' => $dashboard->name,
                'description' => $dashboard->description,
            ],
            'widgets' => [],
            'created_at' => now()->toIso8601String(),
        ];

        foreach ($dashboard->widgets as $widget) {
            $snapshot['widgets'][] = [
                'name' => $widget->name,
                'type' => $widget->widget_type,
                'data' => $widgetsData[$widget->widget_id] ?? null,
            ];
        }

        return $snapshot;
    }
}
