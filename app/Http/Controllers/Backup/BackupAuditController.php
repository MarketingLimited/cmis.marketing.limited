<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Backup\BackupAuditLog;
use Illuminate\Http\Request;

/**
 * Backup Audit Controller
 *
 * Displays audit logs for backup operations.
 */
class BackupAuditController extends Controller
{
    use ApiResponse;

    /**
     * Display audit logs
     */
    public function index(Request $request, string $org)
    {
        $query = BackupAuditLog::where('org_id', $org)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by entity type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        $logs = $query->paginate(25);

        // Get available actions for filter
        $actions = [
            'backup_created' => __('backup.action_backup_created'),
            'backup_downloaded' => __('backup.action_backup_downloaded'),
            'backup_deleted' => __('backup.action_backup_deleted'),
            'restore_started' => __('backup.action_restore_started'),
            'restore_completed' => __('backup.action_restore_completed'),
            'restore_failed' => __('backup.action_restore_failed'),
            'restore_rolled_back' => __('backup.action_restore_rolled_back'),
            'schedule_created' => __('backup.action_schedule_created'),
            'schedule_updated' => __('backup.action_schedule_updated'),
            'schedule_deleted' => __('backup.action_schedule_deleted'),
            'settings_updated' => __('backup.action_settings_updated'),
            'external_upload' => __('backup.action_external_upload'),
        ];

        $entityTypes = [
            'organization_backup' => __('backup.entity_backup'),
            'backup_schedule' => __('backup.entity_schedule'),
            'backup_restore' => __('backup.entity_restore'),
            'backup_setting' => __('backup.entity_setting'),
        ];

        if ($request->wantsJson()) {
            return $this->paginated($logs);
        }

        return view('apps.backup.logs.index', compact('logs', 'actions', 'entityTypes', 'org'));
    }

    /**
     * Show audit log details
     */
    public function show(Request $request, string $org, string $log)
    {
        $log = BackupAuditLog::where('org_id', $org)
            ->with('user')
            ->findOrFail($log);

        if ($request->wantsJson()) {
            return $this->success($log);
        }

        return view('apps.backup.logs.show', compact('log', 'org'));
    }

    /**
     * Export audit logs
     */
    public function export(Request $request, string $org)
    {
        $validated = $request->validate([
            'format' => 'required|in:csv,json',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $query = BackupAuditLog::where('org_id', $org)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->get();

        if ($validated['format'] === 'json') {
            return response()->json($logs)
                ->header('Content-Disposition', 'attachment; filename="audit_logs.json"');
        }

        // CSV export
        $csv = "ID,Action,Entity Type,Entity ID,User,IP Address,Date\n";
        foreach ($logs as $log) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $log->id,
                $log->action,
                $log->entity_type ?? '',
                $log->entity_id ?? '',
                $log->user->name ?? '',
                $log->ip_address ?? '',
                $log->created_at->format('Y-m-d H:i:s')
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="audit_logs.csv"');
    }
}
