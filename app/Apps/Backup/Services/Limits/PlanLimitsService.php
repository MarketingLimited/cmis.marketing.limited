<?php

namespace App\Apps\Backup\Services\Limits;

use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupSchedule;
use App\Models\Core\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Plan Limits Service
 *
 * Enforces plan-based limits for backup operations including
 * monthly backup counts, storage limits, and schedule frequencies.
 */
class PlanLimitsService
{
    /**
     * Check if backup is allowed for organization
     */
    public function checkBackupAllowed(string $orgId): LimitCheckResult
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        if (!$limits) {
            return LimitCheckResult::denied(__('backup.invalid_plan'));
        }

        // Check monthly limit
        $monthlyLimit = $limits['monthly_limit'] ?? 10;
        if ($monthlyLimit !== -1) {
            $monthlyCount = OrganizationBackup::where('org_id', $orgId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            if ($monthlyCount >= $monthlyLimit) {
                return LimitCheckResult::denied(
                    __('backup.monthly_limit_reached', ['limit' => $monthlyLimit]),
                    [
                        'current' => $monthlyCount,
                        'limit' => $monthlyLimit,
                        'type' => 'monthly_limit',
                    ]
                );
            }
        }

        // Check storage limit
        $maxSizeMb = $limits['max_size_mb'] ?? 5120;
        $storageUsedBytes = OrganizationBackup::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->sum('file_size');

        $maxSizeBytes = $maxSizeMb * 1024 * 1024;

        if ($storageUsedBytes >= $maxSizeBytes) {
            return LimitCheckResult::denied(
                __('backup.storage_limit_reached', ['limit' => $this->formatBytes($maxSizeBytes)]),
                [
                    'current' => $storageUsedBytes,
                    'limit' => $maxSizeBytes,
                    'type' => 'storage_limit',
                ]
            );
        }

        return LimitCheckResult::allowed([
            'monthly_used' => $monthlyCount ?? 0,
            'monthly_limit' => $monthlyLimit,
            'storage_used' => $storageUsedBytes,
            'storage_limit' => $maxSizeBytes,
        ]);
    }

    /**
     * Check if a schedule frequency is allowed for the plan
     */
    public function canSchedule(string $orgId, string $frequency): bool
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        if (!$limits) {
            return false;
        }

        $allowedFrequencies = $limits['allowed_schedules'] ?? ['daily', 'weekly', 'monthly'];

        return in_array($frequency, $allowedFrequencies);
    }

    /**
     * Get allowed schedule frequencies for plan
     */
    public function getAllowedFrequencies(string $orgId): array
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        return $limits['allowed_schedules'] ?? ['daily', 'weekly', 'monthly'];
    }

    /**
     * Check if encryption is available for plan
     */
    public function canEncrypt(string $orgId): bool
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        return $limits['encryption'] ?? false;
    }

    /**
     * Check if cloud storage is available for plan
     */
    public function canUseCloudStorage(string $orgId): bool
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        return $limits['cloud_storage'] ?? false;
    }

    /**
     * Get maximum retention days for plan
     */
    public function getMaxRetentionDays(string $orgId): int
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        return $limits['retention_days'] ?? 30;
    }

    /**
     * Get maximum file size allowed for plan
     */
    public function getMaxFileSizeMb(string $orgId): int
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}");

        return $limits['max_size_mb'] ?? 5120;
    }

    /**
     * Get usage statistics for organization
     */
    public function getUsageStats(string $orgId): array
    {
        $plan = $this->getOrgPlan($orgId);
        $limits = config("backup.plans.{$plan}") ?? [];

        $monthlyCount = OrganizationBackup::where('org_id', $orgId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalCount = OrganizationBackup::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->count();

        $storageUsed = OrganizationBackup::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->sum('file_size');

        $scheduleCount = BackupSchedule::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->count();

        return [
            'plan' => $plan,
            'monthly_backups' => [
                'used' => $monthlyCount,
                'limit' => $limits['monthly_limit'] ?? 10,
                'unlimited' => ($limits['monthly_limit'] ?? 10) === -1,
            ],
            'storage' => [
                'used_bytes' => $storageUsed,
                'used_formatted' => $this->formatBytes($storageUsed),
                'limit_bytes' => ($limits['max_size_mb'] ?? 5120) * 1024 * 1024,
                'limit_formatted' => $this->formatBytes(($limits['max_size_mb'] ?? 5120) * 1024 * 1024),
                'percentage' => $this->calculatePercentage($storageUsed, ($limits['max_size_mb'] ?? 5120) * 1024 * 1024),
            ],
            'total_backups' => $totalCount,
            'active_schedules' => $scheduleCount,
            'features' => [
                'encryption' => $limits['encryption'] ?? false,
                'cloud_storage' => $limits['cloud_storage'] ?? false,
                'api_access' => $limits['api_access'] ?? false,
            ],
            'retention_days' => $limits['retention_days'] ?? 30,
            'allowed_schedules' => $limits['allowed_schedules'] ?? ['daily', 'weekly', 'monthly'],
        ];
    }

    /**
     * Check if storage warning threshold is exceeded
     */
    public function isStorageWarningThreshold(string $orgId, int $threshold = 80): bool
    {
        $stats = $this->getUsageStats($orgId);
        return $stats['storage']['percentage'] >= $threshold;
    }

    /**
     * Get organization's plan
     */
    protected function getOrgPlan(string $orgId): string
    {
        return Cache::remember("org_plan_{$orgId}", 3600, function () use ($orgId) {
            $org = Organization::find($orgId);

            if (!$org) {
                return 'free';
            }

            // Check organization's subscription/plan
            // This should integrate with your billing/subscription system
            return $org->plan ?? $org->subscription?->plan ?? 'free';
        });
    }

    /**
     * Format bytes to human readable string
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Calculate percentage
     */
    protected function calculatePercentage(int $used, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($used / $total) * 100, 2);
    }
}

/**
 * Limit Check Result
 *
 * Represents the result of a plan limit check.
 */
class LimitCheckResult
{
    protected bool $allowed;
    protected ?string $message;
    protected array $data;

    public function __construct(bool $allowed, ?string $message = null, array $data = [])
    {
        $this->allowed = $allowed;
        $this->message = $message;
        $this->data = $data;
    }

    public static function allowed(array $data = []): self
    {
        return new self(true, null, $data);
    }

    public static function denied(string $message, array $data = []): self
    {
        return new self(false, $message, $data);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function isDenied(): bool
    {
        return !$this->allowed;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
