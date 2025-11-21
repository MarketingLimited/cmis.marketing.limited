<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Customization Service (Phase 11)
 *
 * Manages user-specific dashboard preferences and saved layouts
 *
 * Features:
 * - Save/load dashboard layouts
 * - Widget configuration
 * - Display preferences (dark mode, compact view, etc.)
 * - Saved filters and views
 * - Dashboard templates
 */
class DashboardCustomizationService
{
    /**
     * Get user's saved dashboard configuration
     *
     * @param string $userId User UUID
     * @param string $dashboardType Dashboard type (realtime, campaign, kpi, etc.)
     * @return array Dashboard configuration
     */
    public function getUserDashboard(string $userId, string $dashboardType): array
    {
        $cacheKey = "dashboard.{$userId}.{$dashboardType}";

        return Cache::remember($cacheKey, 3600, function () use ($userId, $dashboardType) {
            $config = DB::table('cmis.user_dashboard_configs')
                ->where('user_id', $userId)
                ->where('dashboard_type', $dashboardType)
                ->first();

            if ($config) {
                return json_decode($config->configuration, true);
            }

            return $this->getDefaultConfiguration($dashboardType);
        });
    }

    /**
     * Save user's dashboard configuration
     *
     * @param string $userId User UUID
     * @param string $dashboardType Dashboard type
     * @param array $configuration Configuration data
     * @return bool Success status
     */
    public function saveDashboard(string $userId, string $dashboardType, array $configuration): bool
    {
        $data = [
            'user_id' => $userId,
            'dashboard_type' => $dashboardType,
            'configuration' => json_encode($configuration),
            'updated_at' => now()
        ];

        $existing = DB::table('cmis.user_dashboard_configs')
            ->where('user_id', $userId)
            ->where('dashboard_type', $dashboardType)
            ->first();

        if ($existing) {
            DB::table('cmis.user_dashboard_configs')
                ->where('config_id', $existing->config_id)
                ->update($data);
        } else {
            $data['config_id'] = \Illuminate\Support\Str::uuid()->toString();
            $data['created_at'] = now();
            DB::table('cmis.user_dashboard_configs')->insert($data);
        }

        // Clear cache
        Cache::forget("dashboard.{$userId}.{$dashboardType}");

        return true;
    }

    /**
     * Get default dashboard configuration
     *
     * @param string $dashboardType Dashboard type
     * @return array Default configuration
     */
    protected function getDefaultConfiguration(string $dashboardType): array
    {
        $defaults = [
            'realtime' => [
                'widgets' => ['metrics', 'chart', 'table'],
                'time_window' => '5m',
                'auto_refresh' => true,
                'refresh_interval' => 30000,
                'theme' => 'light',
                'compact_view' => false
            ],
            'campaign' => [
                'default_tab' => 'overview',
                'attribution_model' => 'linear',
                'date_range_preset' => 'last_30_days',
                'show_insights' => true
            ],
            'kpi' => [
                'view_mode' => 'grid',
                'sort_by' => 'priority',
                'show_health_score' => true
            ]
        ];

        return $defaults[$dashboardType] ?? [];
    }

    /**
     * Create dashboard template
     *
     * @param string $userId User UUID
     * @param string $name Template name
     * @param string $dashboardType Dashboard type
     * @param array $configuration Configuration
     * @return string Template ID
     */
    public function createTemplate(string $userId, string $name, string $dashboardType, array $configuration): string
    {
        $templateId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('cmis.dashboard_templates')->insert([
            'template_id' => $templateId,
            'user_id' => $userId,
            'name' => $name,
            'dashboard_type' => $dashboardType,
            'configuration' => json_encode($configuration),
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $templateId;
    }

    /**
     * Apply template to user's dashboard
     *
     * @param string $userId User UUID
     * @param string $templateId Template UUID
     * @return bool Success status
     */
    public function applyTemplate(string $userId, string $templateId): bool
    {
        $template = DB::table('cmis.dashboard_templates')
            ->where('template_id', $templateId)
            ->first();

        if (!$template) {
            return false;
        }

        $configuration = json_decode($template->configuration, true);

        return $this->saveDashboard($userId, $template->dashboard_type, $configuration);
    }

    /**
     * Get user's saved filters
     *
     * @param string $userId User UUID
     * @param string $context Filter context (campaigns, analytics, etc.)
     * @return array Saved filters
     */
    public function getSavedFilters(string $userId, string $context): array
    {
        return DB::table('cmis.saved_filters')
            ->where('user_id', $userId)
            ->where('context', $context)
            ->get()
            ->map(function ($filter) {
                return [
                    'filter_id' => $filter->filter_id,
                    'name' => $filter->name,
                    'filters' => json_decode($filter->filters, true),
                    'created_at' => $filter->created_at
                ];
            })
            ->toArray();
    }

    /**
     * Save filter preset
     *
     * @param string $userId User UUID
     * @param string $name Filter name
     * @param string $context Filter context
     * @param array $filters Filter criteria
     * @return string Filter ID
     */
    public function saveFilter(string $userId, string $name, string $context, array $filters): string
    {
        $filterId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('cmis.saved_filters')->insert([
            'filter_id' => $filterId,
            'user_id' => $userId,
            'name' => $name,
            'context' => $context,
            'filters' => json_encode($filters),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $filterId;
    }
}
