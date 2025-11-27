<?php

namespace App\Services\Dashboard;

use App\Models\Dashboard\DashboardWidget;
use App\Models\Campaign\Campaign;
use App\Models\Metrics\UnifiedMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardWidgetService
{
    /**
     * Get data for a specific widget
     */
    public function getWidgetData(DashboardWidget $widget): array
    {
        $cacheKey = "widget:{$widget->widget_id}:data";
        $ttl = $widget->refresh_interval ?? 300; // Default 5 minutes

        return Cache::remember($cacheKey, $ttl, function () use ($widget) {
            return $this->fetchWidgetData($widget);
        });
    }

    /**
     * Refresh widget data (bypass cache)
     */
    public function refreshWidget(DashboardWidget $widget): array
    {
        $cacheKey = "widget:{$widget->widget_id}:data";
        Cache::forget($cacheKey);

        return $this->fetchWidgetData($widget);
    }

    /**
     * Fetch widget data based on type
     */
    protected function fetchWidgetData(DashboardWidget $widget): array
    {
        $orgId = session('current_org_id');
        $config = $widget->config ?? [];

        return match ($widget->widget_type) {
            'metric_card' => $this->getMetricCardData($config, $orgId),
            'line_chart' => $this->getLineChartData($config, $orgId),
            'bar_chart' => $this->getBarChartData($config, $orgId),
            'pie_chart' => $this->getPieChartData($config, $orgId),
            'table' => $this->getTableData($config, $orgId),
            'gauge' => $this->getGaugeData($config, $orgId),
            'heatmap' => $this->getHeatmapData($config, $orgId),
            'funnel' => $this->getFunnelData($config, $orgId),
            default => ['error' => 'Unknown widget type'],
        };
    }

    /**
     * Get metric card data
     */
    protected function getMetricCardData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'total_spend';
        $dateRange = $config['date_range'] ?? 'last_30_days';

        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $value = match ($metric) {
            'campaign_count' => Campaign::where('org_id', $orgId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),

            'total_spend' => UnifiedMetric::where('org_id', $orgId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('spend'),

            'total_impressions' => UnifiedMetric::where('org_id', $orgId)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('impressions'),

            'avg_ctr' => $this->calculateAverageCTR($orgId, $startDate, $endDate),

            'conversion_rate' => $this->calculateConversionRate($orgId, $startDate, $endDate),

            default => 0,
        };

        // Calculate trend (compare with previous period)
        $previousPeriodDays = $endDate->diffInDays($startDate);
        $previousStart = $startDate->copy()->subDays($previousPeriodDays);
        $previousEnd = $startDate->copy()->subDay();

        $previousValue = match ($metric) {
            'campaign_count' => Campaign::where('org_id', $orgId)
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->count(),

            'total_spend' => UnifiedMetric::where('org_id', $orgId)
                ->whereBetween('date', [$previousStart, $previousEnd])
                ->sum('spend'),

            default => 0,
        };

        $trend = $previousValue > 0 ? (($value - $previousValue) / $previousValue) * 100 : 0;

        return [
            'value' => $value,
            'trend' => round($trend, 2),
            'trend_direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'neutral'),
            'formatted_value' => $this->formatValue($value, $metric),
        ];
    }

    /**
     * Get line chart data
     */
    protected function getLineChartData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'spend_over_time';
        $dateRange = $config['date_range'] ?? 'last_30_days';
        $groupBy = $config['group_by'] ?? 'day';

        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $data = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                DB::raw($this->getGroupByExpression($groupBy) . ' as period'),
                DB::raw('SUM(spend) as spend'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(conversions) as conversions'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return [
            'labels' => $data->pluck('period')->toArray(),
            'datasets' => [
                [
                    'label' => ucfirst(str_replace('_', ' ', $metric)),
                    'data' => $data->pluck($this->extractMetricName($metric))->toArray(),
                ],
            ],
        ];
    }

    /**
     * Get bar chart data
     */
    protected function getBarChartData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'platform_performance';
        $dateRange = $config['date_range'] ?? 'last_30_days';

        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $data = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                'entity_type',
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(conversions) as total_conversions'),
            ])
            ->groupBy('entity_type')
            ->get();

        return [
            'labels' => $data->pluck('entity_type')->toArray(),
            'datasets' => [
                [
                    'label' => 'Spend',
                    'data' => $data->pluck('total_spend')->toArray(),
                ],
            ],
        ];
    }

    /**
     * Get pie chart data
     */
    protected function getPieChartData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'budget_distribution';
        $dateRange = $config['date_range'] ?? 'last_30_days';

        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $data = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                'entity_type',
                DB::raw('SUM(spend) as total_spend'),
            ])
            ->groupBy('entity_type')
            ->get();

        return [
            'labels' => $data->pluck('entity_type')->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck('total_spend')->toArray(),
                    'backgroundColor' => [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get table data
     */
    protected function getTableData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'top_campaigns';
        $dateRange = $config['date_range'] ?? 'last_30_days';
        $limit = $config['limit'] ?? 10;

        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $campaigns = Campaign::where('org_id', $orgId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->withSum(['metrics as total_spend' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }], 'spend')
            ->withSum(['metrics as total_impressions' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }], 'impressions')
            ->orderByDesc('total_spend')
            ->limit($limit)
            ->get();

        return [
            'columns' => [
                ['key' => 'name', 'label' => 'Campaign'],
                ['key' => 'total_spend', 'label' => 'Spend'],
                ['key' => 'total_impressions', 'label' => 'Impressions'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'rows' => $campaigns->map(fn($c) => [
                'name' => $c->name,
                'total_spend' => number_format($c->total_spend ?? 0, 2),
                'total_impressions' => number_format($c->total_impressions ?? 0),
                'status' => $c->status,
            ])->toArray(),
        ];
    }

    /**
     * Get gauge data
     */
    protected function getGaugeData(array $config, string $orgId): array
    {
        $metric = $config['metric'] ?? 'budget_usage';
        $target = $config['target'] ?? 100;

        $currentValue = 75; // Placeholder - would calculate actual value

        return [
            'value' => $currentValue,
            'target' => $target,
            'percentage' => round(($currentValue / $target) * 100, 2),
        ];
    }

    /**
     * Get heatmap data
     */
    protected function getHeatmapData(array $config, string $orgId): array
    {
        // Placeholder implementation
        return [
            'data' => [],
            'min' => 0,
            'max' => 100,
        ];
    }

    /**
     * Get funnel data
     */
    protected function getFunnelData(array $config, string $orgId): array
    {
        $dateRange = $config['date_range'] ?? 'last_30_days';
        [$startDate, $endDate] = $this->getDateRange($dateRange);

        $metrics = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(conversions) as total_conversions'),
            ])
            ->first();

        return [
            'stages' => [
                ['name' => 'Impressions', 'value' => $metrics->total_impressions ?? 0],
                ['name' => 'Clicks', 'value' => $metrics->total_clicks ?? 0],
                ['name' => 'Conversions', 'value' => $metrics->total_conversions ?? 0],
            ],
        ];
    }

    /**
     * Duplicate a widget
     */
    public function duplicateWidget(DashboardWidget $widget): DashboardWidget
    {
        return DashboardWidget::create([
            'dashboard_id' => $widget->dashboard_id,
            'org_id' => $widget->org_id,
            'widget_type' => $widget->widget_type,
            'name' => $widget->name . ' (Copy)',
            'config' => $widget->config,
            'position_x' => $widget->position_x + 1,
            'position_y' => $widget->position_y,
            'width' => $widget->width,
            'height' => $widget->height,
            'refresh_interval' => $widget->refresh_interval,
        ]);
    }

    /**
     * Get available widget types
     */
    public function getAvailableTypes(): array
    {
        return [
            ['type' => 'metric_card', 'name' => 'Metric Card', 'icon' => 'chart-square', 'category' => 'metrics'],
            ['type' => 'line_chart', 'name' => 'Line Chart', 'icon' => 'chart-line', 'category' => 'charts'],
            ['type' => 'bar_chart', 'name' => 'Bar Chart', 'icon' => 'chart-bar', 'category' => 'charts'],
            ['type' => 'pie_chart', 'name' => 'Pie Chart', 'icon' => 'chart-pie', 'category' => 'charts'],
            ['type' => 'table', 'name' => 'Data Table', 'icon' => 'table', 'category' => 'data'],
            ['type' => 'gauge', 'name' => 'Gauge', 'icon' => 'speedometer', 'category' => 'metrics'],
            ['type' => 'heatmap', 'name' => 'Heatmap', 'icon' => 'grid', 'category' => 'charts'],
            ['type' => 'funnel', 'name' => 'Funnel Chart', 'icon' => 'funnel', 'category' => 'charts'],
        ];
    }

    /**
     * Preview widget with configuration
     */
    public function previewWidget(string $widgetType, array $config): array
    {
        $widget = new DashboardWidget([
            'widget_type' => $widgetType,
            'config' => $config,
        ]);

        return $this->fetchWidgetData($widget);
    }

    /**
     * Export widget configuration
     */
    public function exportWidget(DashboardWidget $widget): array
    {
        return [
            'widget_type' => $widget->widget_type,
            'name' => $widget->name,
            'config' => $widget->config,
            'width' => $widget->width,
            'height' => $widget->height,
            'refresh_interval' => $widget->refresh_interval,
        ];
    }

    /**
     * Bulk update widget positions
     */
    public function bulkUpdatePositions(string $dashboardId, array $widgets): int
    {
        $updated = 0;

        foreach ($widgets as $widgetData) {
            $widget = DashboardWidget::where('dashboard_id', $dashboardId)
                ->where('widget_id', $widgetData['widget_id'])
                ->first();

            if ($widget) {
                $widget->update([
                    'position_x' => $widgetData['position_x'],
                    'position_y' => $widgetData['position_y'],
                    'width' => $widgetData['width'] ?? $widget->width,
                    'height' => $widgetData['height'] ?? $widget->height,
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get query builder for widget type
     */
    public function getQueryBuilder(string $widgetType): array
    {
        return [
            'widget_type' => $widgetType,
            'available_metrics' => $this->getAvailableMetrics(),
            'available_dimensions' => $this->getAvailableDimensions(),
            'date_ranges' => $this->getDateRanges(),
        ];
    }

    /**
     * Helper methods
     */

    protected function getDateRange(string $range): array
    {
        $endDate = now();

        $startDate = match ($range) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7),
            'last_30_days' => now()->subDays(30),
            'last_90_days' => now()->subDays(90),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(30),
        };

        return [$startDate, $endDate];
    }

    protected function getGroupByExpression(string $groupBy): string
    {
        return match ($groupBy) {
            'hour' => "DATE_FORMAT(date, '%Y-%m-%d %H:00')",
            'day' => "DATE(date)",
            'week' => "DATE_FORMAT(date, '%Y-%u')",
            'month' => "DATE_FORMAT(date, '%Y-%m')",
            default => "DATE(date)",
        };
    }

    protected function extractMetricName(string $metric): string
    {
        return match ($metric) {
            'spend_over_time' => 'spend',
            'impressions_over_time' => 'impressions',
            'clicks_over_time' => 'clicks',
            'conversions_over_time' => 'conversions',
            default => 'spend',
        };
    }

    protected function calculateAverageCTR(string $orgId, $startDate, $endDate): float
    {
        $metrics = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(impressions) as total_impressions'),
            ])
            ->first();

        $impressions = $metrics->total_impressions ?? 0;
        $clicks = $metrics->total_clicks ?? 0;

        return $impressions > 0 ? ($clicks / $impressions) * 100 : 0;
    }

    protected function calculateConversionRate(string $orgId, $startDate, $endDate): float
    {
        $metrics = UnifiedMetric::where('org_id', $orgId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(clicks) as total_clicks'),
            ])
            ->first();

        $clicks = $metrics->total_clicks ?? 0;
        $conversions = $metrics->total_conversions ?? 0;

        return $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
    }

    protected function formatValue(float $value, string $metric): string
    {
        return match ($metric) {
            'total_spend', 'total_revenue' => '$' . number_format($value, 2),
            'avg_ctr', 'conversion_rate' => number_format($value, 2) . '%',
            default => number_format($value),
        };
    }

    protected function getAvailableMetrics(): array
    {
        return [
            'spend', 'impressions', 'clicks', 'conversions', 'ctr', 'cpc', 'cpm', 'conversion_rate',
        ];
    }

    protected function getAvailableDimensions(): array
    {
        return [
            'platform', 'campaign', 'ad_set', 'ad', 'date', 'device', 'location',
        ];
    }

    protected function getDateRanges(): array
    {
        return [
            'today', 'yesterday', 'last_7_days', 'last_30_days', 'last_90_days', 'this_month', 'last_month',
        ];
    }
}
