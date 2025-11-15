<?php

namespace App\Policies;

use App\Models\User;

class AuditPolicy
{
    /**
     * Determine if the user can view audit dashboard
     */
    public function viewDashboard(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_dashboard');
    }

    /**
     * Determine if the user can view realtime status
     */
    public function viewRealtimeStatus(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_realtime');
    }

    /**
     * Determine if the user can view reports
     */
    public function viewReports(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_reports');
    }

    /**
     * Determine if the user can view activity log
     */
    public function viewActivityLog(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_activity_log');
    }

    /**
     * Determine if the user can log events
     */
    public function logEvent(User $user): bool
    {
        return $this->hasPermission($user, 'audit.log_event');
    }

    /**
     * Determine if the user can view alerts
     */
    public function viewAlerts(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_alerts');
    }

    /**
     * Determine if the user can export reports
     */
    public function exportReports(User $user): bool
    {
        return $this->hasPermission($user, 'audit.export_reports');
    }

    /**
     * Determine if the user can view all organization audit data
     */
    public function viewAll(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_all');
    }

    /**
     * Determine if the user can manage audit settings
     */
    public function manageSettings(User $user): bool
    {
        return $this->hasPermission($user, 'audit.manage_settings');
    }

    /**
     * Check if user has a specific permission
     */
    private function hasPermission(User $user, string $permission): bool
    {
        // Check if user is admin (full access)
        if ($user->role === 'admin' || $user->role === 'owner') {
            return true;
        }

        // Check specific permission
        return $user->hasPermission($permission);
    }

    /**
     * Determine if user can view security-related logs
     */
    public function viewSecurityLogs(User $user): bool
    {
        // Only admins and security managers can view security logs
        return in_array($user->role, ['admin', 'owner']) ||
               $this->hasPermission($user, 'audit.view_security_logs');
    }

    /**
     * Check if user can view logs for specific category
     */
    public function viewCategoryLogs(User $user, string $category): bool
    {
        // Security category has special restrictions
        if ($category === 'security') {
            return $this->viewSecurityLogs($user);
        }

        // For other categories, check general view permission
        return $this->viewActivityLog($user);
    }
}
