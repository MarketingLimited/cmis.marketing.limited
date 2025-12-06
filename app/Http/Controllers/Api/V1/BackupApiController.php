<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Jobs\Backup\ProcessBackupJob;
use App\Jobs\Backup\ProcessRestoreJob;
use App\Jobs\Backup\ScheduledBackupJob;
use App\Models\Backup\BackupSchedule;
use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupRestore;
use App\Apps\Backup\Services\BackupOrchestrator;
use App\Apps\Backup\Services\Limits\PlanLimitsService;
use App\Apps\Backup\Services\Restore\SchemaReconcilerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Backup API Controller (Enterprise)
 *
 * Provides RESTful API endpoints for backup and restore operations.
 * Requires API authentication and appropriate permissions.
 */
class BackupApiController extends Controller
{
    use ApiResponse;

    protected PlanLimitsService $planLimits;
    protected BackupOrchestrator $orchestrator;

    public function __construct(
        PlanLimitsService $planLimits,
        BackupOrchestrator $orchestrator
    ) {
        $this->planLimits = $planLimits;
        $this->orchestrator = $orchestrator;
    }

    /**
     * List all backups for the organization
     *
     * GET /api/v1/backup/list
     */
    public function list(Request $request): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $backups = OrganizationBackup::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($backups, __('backup.backups_retrieved'));
    }

    /**
     * Create a new backup
     *
     * POST /api/v1/backup/create
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
            'encrypt' => 'nullable|boolean',
        ]);

        $orgId = $this->getOrgId($request);

        // Check plan limits
        $limitCheck = $this->planLimits->checkBackupAllowed($orgId);
        if ($limitCheck->isDenied()) {
            return $this->error($limitCheck->getMessage(), 403, $limitCheck->getData());
        }

        // Generate backup code
        $backupCode = $this->generateBackupCode($orgId);

        // Create backup record
        $backup = OrganizationBackup::create([
            'org_id' => $orgId,
            'backup_code' => $backupCode,
            'name' => $request->input('name', __('backup.api_backup', ['date' => now()->format('Y-m-d H:i')])),
            'description' => $request->input('description'),
            'type' => 'manual',
            'status' => 'pending',
            'storage_disk' => $request->input('storage_disk', config('backup.storage.default', 'local')),
            'is_encrypted' => $request->boolean('encrypt', false),
            'summary' => ['categories' => $request->input('categories')],
            'created_by' => auth()->id(),
        ]);

        // Dispatch job
        ProcessBackupJob::dispatch($backup);

        return $this->created([
            'id' => $backup->id,
            'backup_code' => $backup->backup_code,
            'status' => $backup->status,
        ], __('backup.backup_queued'));
    }

    /**
     * Get backup details
     *
     * GET /api/v1/backup/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $backup = OrganizationBackup::where('org_id', $orgId)
            ->where('id', $id)
            ->firstOrFail();

        return $this->success([
            'id' => $backup->id,
            'backup_code' => $backup->backup_code,
            'name' => $backup->name,
            'description' => $backup->description,
            'type' => $backup->type,
            'status' => $backup->status,
            'file_size' => $backup->file_size,
            'file_size_formatted' => $this->formatBytes($backup->file_size),
            'is_encrypted' => $backup->is_encrypted,
            'storage_disk' => $backup->storage_disk,
            'summary' => $backup->summary,
            'started_at' => $backup->started_at?->toISOString(),
            'completed_at' => $backup->completed_at?->toISOString(),
            'expires_at' => $backup->expires_at?->toISOString(),
            'error_message' => $backup->error_message,
            'created_at' => $backup->created_at->toISOString(),
        ], __('backup.backup_retrieved'));
    }

    /**
     * Download backup file
     *
     * GET /api/v1/backup/{id}/download
     */
    public function download(Request $request, string $id)
    {
        $orgId = $this->getOrgId($request);

        $backup = OrganizationBackup::where('org_id', $orgId)
            ->where('id', $id)
            ->where('status', 'completed')
            ->firstOrFail();

        if (!$backup->file_path || !Storage::disk($backup->storage_disk)->exists($backup->file_path)) {
            return $this->notFound(__('backup.file_not_found'));
        }

        // Generate download URL (signed for security)
        $url = Storage::disk($backup->storage_disk)->temporaryUrl(
            $backup->file_path,
            now()->addMinutes(30)
        );

        return $this->success([
            'download_url' => $url,
            'expires_in' => 1800, // 30 minutes in seconds
            'file_name' => basename($backup->file_path),
            'file_size' => $backup->file_size,
        ], __('backup.download_url_generated'));
    }

    /**
     * Delete a backup
     *
     * DELETE /api/v1/backup/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $backup = OrganizationBackup::where('org_id', $orgId)
            ->where('id', $id)
            ->firstOrFail();

        // Soft delete
        $backup->delete();

        // TODO: Schedule file deletion after retention period

        return $this->deleted(__('backup.backup_deleted'));
    }

    /**
     * Analyze backup for restore compatibility
     *
     * POST /api/v1/restore/analyze
     */
    public function analyzeRestore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_id' => 'required|uuid',
        ]);

        $orgId = $this->getOrgId($request);

        $backup = OrganizationBackup::where('org_id', $orgId)
            ->where('id', $request->input('backup_id'))
            ->where('status', 'completed')
            ->firstOrFail();

        // Get schema reconciliation
        $reconciler = app(SchemaReconcilerService::class);
        $report = $reconciler->reconcile($backup->schema_snapshot ?? []);

        return $this->success([
            'backup_id' => $backup->id,
            'backup_code' => $backup->backup_code,
            'compatibility' => [
                'compatible' => $report->getCompatibleCategories(),
                'partially_compatible' => $report->getPartiallyCompatibleCategories(),
                'incompatible' => $report->getIncompatibleCategories(),
            ],
            'can_restore' => $report->canRestore(),
            'warnings' => $report->getWarnings(),
        ], __('backup.analysis_complete'));
    }

    /**
     * Start restore process
     *
     * POST /api/v1/restore/start
     */
    public function startRestore(Request $request): JsonResponse
    {
        $request->validate([
            'backup_id' => 'required|uuid',
            'type' => 'required|in:full,selective,merge',
            'categories' => 'required_if:type,selective|array',
            'categories.*' => 'string',
            'conflict_strategy' => 'nullable|in:skip,replace,merge',
        ]);

        $orgId = $this->getOrgId($request);

        $backup = OrganizationBackup::where('org_id', $orgId)
            ->where('id', $request->input('backup_id'))
            ->where('status', 'completed')
            ->firstOrFail();

        // Generate restore code
        $restoreCode = $this->generateRestoreCode($orgId);

        // Create restore record
        $restore = BackupRestore::create([
            'org_id' => $orgId,
            'backup_id' => $backup->id,
            'restore_code' => $restoreCode,
            'type' => $request->input('type'),
            'status' => 'pending',
            'selected_categories' => $request->input('categories'),
            'conflict_resolution' => [
                'strategy' => $request->input('conflict_strategy', 'skip'),
                'decisions' => [],
            ],
            'created_by' => auth()->id(),
        ]);

        // Dispatch job
        ProcessRestoreJob::dispatch($restore);

        return $this->created([
            'id' => $restore->id,
            'restore_code' => $restore->restore_code,
            'status' => $restore->status,
        ], __('backup.restore_queued'));
    }

    /**
     * Get restore status
     *
     * GET /api/v1/restore/{id}/status
     */
    public function restoreStatus(Request $request, string $id): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $restore = BackupRestore::where('org_id', $orgId)
            ->where('id', $id)
            ->firstOrFail();

        return $this->success([
            'id' => $restore->id,
            'restore_code' => $restore->restore_code,
            'status' => $restore->status,
            'type' => $restore->type,
            'progress' => $restore->execution_report['progress'] ?? 0,
            'current_step' => $restore->execution_report['current_step'] ?? null,
            'stats' => [
                'records_restored' => $restore->execution_report['records_restored'] ?? 0,
                'records_updated' => $restore->execution_report['records_updated'] ?? 0,
                'records_skipped' => $restore->execution_report['records_skipped'] ?? 0,
                'files_restored' => $restore->execution_report['files_restored'] ?? 0,
            ],
            'started_at' => $restore->started_at?->toISOString(),
            'completed_at' => $restore->completed_at?->toISOString(),
            'error_message' => $restore->error_message,
            'can_rollback' => $restore->canRollback(),
            'rollback_expires_at' => $restore->rollback_expires_at?->toISOString(),
        ], __('backup.restore_status_retrieved'));
    }

    /**
     * Rollback a restore
     *
     * POST /api/v1/restore/{id}/rollback
     */
    public function rollback(Request $request, string $id): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $restore = BackupRestore::where('org_id', $orgId)
            ->where('id', $id)
            ->where('status', 'completed')
            ->firstOrFail();

        if (!$restore->canRollback()) {
            return $this->error(__('backup.rollback_expired'), 400);
        }

        // Update status
        $restore->update(['status' => 'processing']);

        // The actual rollback uses the safety backup
        if ($restore->safety_backup_id) {
            $safetyBackup = OrganizationBackup::find($restore->safety_backup_id);
            if ($safetyBackup) {
                $rollbackRestore = BackupRestore::create([
                    'org_id' => $orgId,
                    'backup_id' => $safetyBackup->id,
                    'restore_code' => $this->generateRestoreCode($orgId),
                    'type' => 'full',
                    'status' => 'pending',
                    'conflict_resolution' => ['strategy' => 'replace'],
                    'created_by' => auth()->id(),
                ]);

                ProcessRestoreJob::dispatch($rollbackRestore);

                // Mark original restore as rolled back
                $restore->update(['status' => 'rolled_back']);

                return $this->success([
                    'rollback_restore_id' => $rollbackRestore->id,
                    'restore_code' => $rollbackRestore->restore_code,
                ], __('backup.rollback_started'));
            }
        }

        return $this->error(__('backup.rollback_not_available'), 400);
    }

    /**
     * Get schedule list
     *
     * GET /api/v1/schedule
     */
    public function scheduleList(Request $request): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $schedules = BackupSchedule::where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'frequency' => $schedule->frequency,
                'time' => $schedule->time,
                'day_of_week' => $schedule->day_of_week,
                'day_of_month' => $schedule->day_of_month,
                'timezone' => $schedule->timezone,
                'is_active' => $schedule->is_active,
                'retention_days' => $schedule->retention_days,
                'categories' => $schedule->categories,
                'storage_disk' => $schedule->storage_disk,
                'last_run_at' => $schedule->last_run_at?->toISOString(),
                'next_run_at' => $schedule->next_run_at?->toISOString(),
            ];
        }), __('backup.schedules_retrieved'));
    }

    /**
     * Create or update schedule
     *
     * PUT /api/v1/schedule
     */
    public function scheduleUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|uuid',
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:hourly,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
            'timezone' => 'required|string|timezone',
            'is_active' => 'boolean',
            'retention_days' => 'nullable|integer|min:1|max:365',
            'categories' => 'nullable|array',
            'storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
        ]);

        $orgId = $this->getOrgId($request);

        // Check if frequency is allowed for plan
        if (!$this->planLimits->canSchedule($orgId, $request->input('frequency'))) {
            return $this->error(__('backup.frequency_not_allowed'), 403);
        }

        $data = [
            'org_id' => $orgId,
            'name' => $request->input('name'),
            'frequency' => $request->input('frequency'),
            'time' => $request->input('time'),
            'day_of_week' => $request->input('day_of_week'),
            'day_of_month' => $request->input('day_of_month'),
            'timezone' => $request->input('timezone'),
            'is_active' => $request->boolean('is_active', true),
            'retention_days' => $request->input('retention_days', 30),
            'categories' => $request->input('categories'),
            'storage_disk' => $request->input('storage_disk', config('backup.storage.default')),
            'created_by' => auth()->id(),
        ];

        if ($request->input('id')) {
            $schedule = BackupSchedule::where('org_id', $orgId)
                ->where('id', $request->input('id'))
                ->firstOrFail();

            $schedule->update($data);
            $message = __('backup.schedule_updated');
        } else {
            $schedule = BackupSchedule::create($data);
            $message = __('backup.schedule_created');
        }

        // Calculate next run
        $schedule->next_run_at = $this->calculateNextRun($schedule);
        $schedule->save();

        return $this->success([
            'id' => $schedule->id,
            'name' => $schedule->name,
            'next_run_at' => $schedule->next_run_at?->toISOString(),
        ], $message);
    }

    /**
     * Trigger a scheduled backup now
     *
     * POST /api/v1/schedule/trigger
     */
    public function scheduleTrigger(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id' => 'required|uuid',
        ]);

        $orgId = $this->getOrgId($request);

        $schedule = BackupSchedule::where('org_id', $orgId)
            ->where('id', $request->input('schedule_id'))
            ->where('is_active', true)
            ->firstOrFail();

        // Dispatch immediate backup job
        ScheduledBackupJob::dispatch($schedule);

        return $this->success([
            'schedule_id' => $schedule->id,
            'triggered_at' => now()->toISOString(),
        ], __('backup.schedule_triggered'));
    }

    /**
     * Get usage statistics
     *
     * GET /api/v1/backup/usage
     */
    public function usage(Request $request): JsonResponse
    {
        $orgId = $this->getOrgId($request);

        $stats = $this->planLimits->getUsageStats($orgId);

        return $this->success($stats, __('backup.usage_retrieved'));
    }

    /**
     * Helper: Get organization ID from request
     */
    protected function getOrgId(Request $request): string
    {
        // Try route parameter first, then header, then authenticated user
        return $request->route('org')
            ?? $request->header('X-Org-ID')
            ?? auth()->user()->current_org_id
            ?? auth()->user()->org_id;
    }

    /**
     * Helper: Generate backup code
     */
    protected function generateBackupCode(string $orgId): string
    {
        $count = OrganizationBackup::where('org_id', $orgId)
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return sprintf('BKUP-%d-%04d', now()->year, $count);
    }

    /**
     * Helper: Generate restore code
     */
    protected function generateRestoreCode(string $orgId): string
    {
        $count = BackupRestore::where('org_id', $orgId)
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return sprintf('REST-%d-%04d', now()->year, $count);
    }

    /**
     * Helper: Format bytes
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
     * Helper: Calculate next run time for schedule
     */
    protected function calculateNextRun(BackupSchedule $schedule): ?\DateTime
    {
        $now = now()->setTimezone($schedule->timezone);
        $time = explode(':', $schedule->time);
        $hour = (int) $time[0];
        $minute = (int) ($time[1] ?? 0);

        $next = $now->copy()->setTime($hour, $minute, 0);

        switch ($schedule->frequency) {
            case 'hourly':
                if ($next->lte($now)) {
                    $next->addHour();
                }
                break;

            case 'daily':
                if ($next->lte($now)) {
                    $next->addDay();
                }
                break;

            case 'weekly':
                $next->next($schedule->day_of_week ?? 0);
                $next->setTime($hour, $minute, 0);
                break;

            case 'monthly':
                $day = $schedule->day_of_month ?? 1;
                $next->day($day)->setTime($hour, $minute, 0);
                if ($next->lte($now)) {
                    $next->addMonth();
                }
                break;
        }

        return $next;
    }
}
