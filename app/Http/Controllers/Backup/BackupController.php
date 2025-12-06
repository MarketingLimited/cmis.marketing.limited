<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupAuditLog;
use App\Jobs\Backup\ProcessBackupJob;
use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Backup Controller
 *
 * Handles backup creation, listing, downloading, and management.
 */
class BackupController extends Controller
{
    use ApiResponse;

    protected SchemaDiscoveryService $schemaDiscovery;

    public function __construct(SchemaDiscoveryService $schemaDiscovery)
    {
        $this->schemaDiscovery = $schemaDiscovery;
    }

    /**
     * Display backup dashboard
     */
    public function index(Request $request, string $org)
    {
        $backups = OrganizationBackup::where('org_id', $org)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate stats
        $stats = [
            'total' => OrganizationBackup::where('org_id', $org)->count(),
            'this_month' => OrganizationBackup::where('org_id', $org)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'storage_used' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'completed')
                ->sum('file_size'),
            'last_backup' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first(),
        ];

        if ($request->wantsJson()) {
            return $this->success([
                'backups' => $backups,
                'stats' => $stats,
            ]);
        }

        return view('apps.backup.index', compact('backups', 'stats', 'org'));
    }

    /**
     * Show backup creation form
     */
    public function create(Request $request, string $org)
    {
        // Get available categories
        $categories = $this->schemaDiscovery->discoverByCategory();

        // Get data summary for the organization
        $dataSummary = $this->schemaDiscovery->getOrgDataSummary($org);

        if ($request->wantsJson()) {
            return $this->success([
                'categories' => $categories,
                'data_summary' => $dataSummary,
            ]);
        }

        return view('apps.backup.create', compact('categories', 'dataSummary', 'org'));
    }

    /**
     * Store a new backup
     */
    public function store(Request $request, string $org)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'encrypt' => 'boolean',
            'encryption_key_id' => 'nullable|uuid',
            'storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
        ]);

        // Create backup record
        $backup = OrganizationBackup::create([
            'org_id' => $org,
            'backup_code' => OrganizationBackup::generateBackupCode(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => 'manual',
            'status' => 'pending',
            'storage_disk' => $validated['storage_disk'] ?? config('backup.storage.default', 'local'),
            'created_by' => auth()->id(),
        ]);

        // Dispatch backup job
        ProcessBackupJob::dispatch(
            $backup,
            $validated['categories'] ?? null,
            $validated['encrypt'] ?? false,
            $validated['encryption_key_id'] ?? null
        );

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'backup_created',
            'entity_id' => $backup->id,
            'entity_type' => 'organization_backup',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'name' => $backup->name,
                'categories' => $validated['categories'] ?? 'all',
            ],
        ]);

        if ($request->wantsJson()) {
            return $this->created($backup, __('backup.backup_started'));
        }

        return redirect()
            ->route('orgs.backup.show', ['org' => $org, 'backup' => $backup->id])
            ->with('success', __('backup.backup_started'));
    }

    /**
     * Display a specific backup
     */
    public function show(Request $request, string $org, string $backup)
    {
        $backup = OrganizationBackup::where('org_id', $org)
            ->findOrFail($backup);

        if ($request->wantsJson()) {
            return $this->success($backup);
        }

        return view('apps.backup.show', compact('backup', 'org'));
    }

    /**
     * Download backup file
     */
    public function download(Request $request, string $org, string $backup)
    {
        $backup = OrganizationBackup::where('org_id', $org)
            ->where('status', 'completed')
            ->findOrFail($backup);

        if (!$backup->file_path) {
            return $this->error(__('backup.file_not_found'), 404);
        }

        $disk = Storage::disk($backup->storage_disk);

        if (!$disk->exists($backup->file_path)) {
            return $this->error(__('backup.file_not_found'), 404);
        }

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'backup_downloaded',
            'entity_id' => $backup->id,
            'entity_type' => 'organization_backup',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $filename = $backup->backup_code . ($backup->is_encrypted ? '.zip.enc' : '.zip');

        return $disk->download($backup->file_path, $filename);
    }

    /**
     * Get backup progress
     */
    public function progress(Request $request, string $org, string $backup)
    {
        $backup = OrganizationBackup::where('org_id', $org)
            ->findOrFail($backup);

        return $this->success([
            'id' => $backup->id,
            'status' => $backup->status,
            'started_at' => $backup->started_at,
            'completed_at' => $backup->completed_at,
            'error_message' => $backup->error_message,
            'summary' => $backup->summary,
            'file_size' => $backup->file_size,
        ]);
    }

    /**
     * Delete a backup
     */
    public function destroy(Request $request, string $org, string $backup)
    {
        $backup = OrganizationBackup::where('org_id', $org)
            ->findOrFail($backup);

        // Delete file from storage
        if ($backup->file_path) {
            $disk = Storage::disk($backup->storage_disk);
            if ($disk->exists($backup->file_path)) {
                $disk->delete($backup->file_path);
            }
        }

        // Create audit log before deletion
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'backup_deleted',
            'entity_id' => $backup->id,
            'entity_type' => 'organization_backup',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'backup_code' => $backup->backup_code,
                'deleted_by' => 'user',
            ],
        ]);

        // Soft delete
        $backup->delete();

        if ($request->wantsJson()) {
            return $this->deleted(__('backup.backup_deleted'));
        }

        return redirect()
            ->route('orgs.backup.index', ['org' => $org])
            ->with('success', __('backup.backup_deleted'));
    }

    /**
     * API: List backups
     */
    public function apiList(Request $request, string $org)
    {
        $backups = OrganizationBackup::where('org_id', $org)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->paginated($backups);
    }

    /**
     * API: Get backup stats
     */
    public function apiStats(Request $request, string $org)
    {
        $stats = [
            'total_backups' => OrganizationBackup::where('org_id', $org)->count(),
            'completed_backups' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'completed')
                ->count(),
            'failed_backups' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'failed')
                ->count(),
            'total_storage_bytes' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'completed')
                ->sum('file_size'),
            'backups_this_month' => OrganizationBackup::where('org_id', $org)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'last_backup_at' => OrganizationBackup::where('org_id', $org)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->value('completed_at'),
        ];

        return $this->success($stats);
    }
}
