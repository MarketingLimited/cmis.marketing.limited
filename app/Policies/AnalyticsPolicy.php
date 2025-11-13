<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;

class AnalyticsPolicy
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function viewDashboard(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.view_dashboard');
    }

    public function viewReports(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.view_reports');
    }

    public function createReport(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.create_report');
    }

    public function exportData(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.export');
    }

    public function viewInsights(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.view_insights');
    }

    public function viewPerformance(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.view_performance');
    }

    public function manageDashboard(User $user): bool
    {
        return $this->permissionService->check($user, 'cmis.analytics.manage_dashboard');
    }
}
