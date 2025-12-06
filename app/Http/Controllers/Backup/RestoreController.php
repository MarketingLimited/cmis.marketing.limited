<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Backup\OrganizationBackup;
use App\Models\Backup\BackupRestore;
use App\Models\Backup\BackupAuditLog;
use App\Apps\Backup\Services\Restore\RestoreOrchestrator;
use App\Apps\Backup\Services\Restore\RollbackService;
use App\Jobs\Backup\ProcessRestoreJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Restore Controller
 *
 * Handles the restore wizard workflow including backup selection,
 * analysis, category selection, conflict resolution, and execution.
 */
class RestoreController extends Controller
{
    use ApiResponse;

    /**
     * Get the RestoreOrchestrator instance (lazy-loaded)
     */
    protected function getOrchestrator(): RestoreOrchestrator
    {
        return app(RestoreOrchestrator::class);
    }

    /**
     * Get the RollbackService instance (lazy-loaded)
     */
    protected function getRollbackService(): RollbackService
    {
        return app(RollbackService::class);
    }

    /**
     * Display available backups for restore
     */
    public function index(Request $request, string $org)
    {
        $backups = OrganizationBackup::where('org_id', $org)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get restore history
        $restores = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($request->wantsJson()) {
            return $this->success([
                'backups' => $backups,
                'restores' => $restores,
            ]);
        }

        return view('apps.orgs.backup.restore.index', compact('backups', 'restores', 'org'));
    }

    /**
     * Show external backup upload form
     */
    public function upload(Request $request, string $org)
    {
        return view('apps.orgs.backup.restore.upload', compact('org'));
    }

    /**
     * Handle external backup upload
     */
    public function storeUpload(Request $request, string $org)
    {
        $request->validate([
            'backup_file' => 'required|file|max:512000', // 500MB max
        ]);

        $file = $request->file('backup_file');
        $fileName = $file->getClientOriginalName();

        try {
            // Validate and process upload
            $result = $this->getOrchestrator()->uploadExternalBackup(
                $org,
                $file->path(),
                $fileName
            );

            // Create backup record for external upload
            $backup = OrganizationBackup::create([
                'org_id' => $org,
                'backup_code' => OrganizationBackup::generateBackupCode(),
                'name' => 'External Upload: ' . $fileName,
                'description' => 'Uploaded backup file',
                'type' => 'manual',
                'status' => 'completed',
                'storage_disk' => 'local',
                'file_path' => $result['file_path'],
                'summary' => $result['manifest']['summary'] ?? null,
                'schema_snapshot' => $result['manifest']['schema_snapshot'] ?? null,
                'created_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            // Create audit log
            BackupAuditLog::create([
                'org_id' => $org,
                'action' => 'external_upload',
                'entity_id' => $backup->id,
                'entity_type' => 'organization_backup',
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => [
                    'file_name' => $fileName,
                ],
            ]);

            if ($request->wantsJson()) {
                return $this->success([
                    'backup' => $backup,
                    'redirect' => route('orgs.backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]),
                ]);
            }

            return redirect()
                ->route('orgs.backup.restore.analyze', ['org' => $org, 'backup' => $backup->id])
                ->with('success', __('backup.upload_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['backup_file' => $e->getMessage()]);
        }
    }

    /**
     * Analyze backup for restore compatibility
     */
    public function analyze(Request $request, string $org, string $backup)
    {
        $backup = OrganizationBackup::where('org_id', $org)
            ->where('status', 'completed')
            ->findOrFail($backup);

        // Check for existing restore in progress
        $existingRestore = BackupRestore::where('org_id', $org)
            ->where('backup_id', $backup->id)
            ->whereIn('status', ['pending', 'analyzing', 'awaiting_confirmation', 'processing'])
            ->first();

        if ($existingRestore) {
            // Redirect to existing restore
            return redirect()->route('orgs.backup.restore.select', [
                'org' => $org,
                'restore' => $existingRestore->id
            ]);
        }

        // Create new restore record
        $restore = BackupRestore::create([
            'org_id' => $org,
            'backup_id' => $backup->id,
            'restore_code' => BackupRestore::generateRestoreCode(),
            'type' => 'selective', // Default to selective
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Run analysis
        try {
            $analysis = $this->getOrchestrator()->analyze($restore);

            if ($request->wantsJson()) {
                return $this->success([
                    'restore' => $restore->fresh(),
                    'analysis' => $analysis,
                ]);
            }

            return view('apps.orgs.backup.restore.analyze', [
                'backup' => $backup,
                'restore' => $restore->fresh(),
                'analysis' => $analysis,
                'org' => $org,
            ]);
        } catch (\Exception $e) {
            $restore->delete();

            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return redirect()
                ->route('orgs.backup.restore.index', ['org' => $org])
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show category selection
     */
    public function select(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->findOrFail($restore);

        if (!in_array($restore->status, ['awaiting_confirmation', 'pending'])) {
            return redirect()->route('orgs.backup.restore.progress', [
                'org' => $org,
                'restore' => $restore->id
            ]);
        }

        $reconciliation = $restore->reconciliation_report ?? [];
        $conflictPreview = $restore->conflict_resolution['preview'] ?? [];

        if ($request->wantsJson()) {
            return $this->success([
                'restore' => $restore,
                'reconciliation' => $reconciliation,
                'conflict_preview' => $conflictPreview,
            ]);
        }

        return view('apps.orgs.backup.restore.select', [
            'restore' => $restore,
            'backup' => $restore->backup,
            'reconciliation' => $reconciliation,
            'conflictPreview' => $conflictPreview,
            'org' => $org,
        ]);
    }

    /**
     * Store category selection
     */
    public function storeSelect(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        $validated = $request->validate([
            'type' => 'required|in:full,selective,merge',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
        ]);

        $restore->update([
            'type' => $validated['type'],
            'selected_categories' => $validated['categories'] ?? null,
        ]);

        // Check if there are conflicts that need resolution
        $conflictPreview = $restore->conflict_resolution['preview'] ?? [];

        if (!empty($conflictPreview['total']) && $conflictPreview['total'] > 0) {
            // Redirect to conflict resolution
            return redirect()->route('orgs.backup.restore.conflicts', [
                'org' => $org,
                'restore' => $restore->id
            ]);
        }

        // No conflicts, go directly to confirmation
        return redirect()->route('orgs.backup.restore.confirm', [
            'org' => $org,
            'restore' => $restore->id
        ]);
    }

    /**
     * Show conflict resolution UI
     */
    public function conflicts(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->findOrFail($restore);

        $conflictPreview = $restore->conflict_resolution['preview'] ?? [];

        if ($request->wantsJson()) {
            return $this->success([
                'restore' => $restore,
                'conflicts' => $conflictPreview,
            ]);
        }

        return view('apps.orgs.backup.restore.conflicts', [
            'restore' => $restore,
            'backup' => $restore->backup,
            'conflicts' => $conflictPreview,
            'org' => $org,
        ]);
    }

    /**
     * Store conflict resolution decisions
     */
    public function storeConflicts(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        $validated = $request->validate([
            'strategy' => 'required|in:skip,replace,merge,ask',
            'decisions' => 'nullable|array',
        ]);

        $conflictResolution = $restore->conflict_resolution ?? [];
        $conflictResolution['strategy'] = $validated['strategy'];
        $conflictResolution['decisions'] = $validated['decisions'] ?? [];

        $restore->update([
            'conflict_resolution' => $conflictResolution,
        ]);

        return redirect()->route('orgs.backup.restore.confirm', [
            'org' => $org,
            'restore' => $restore->id
        ]);
    }

    /**
     * Show confirmation page
     */
    public function confirm(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->findOrFail($restore);

        // Get organization name for confirmation
        $organization = \App\Models\Core\Organization::find($org);

        if ($request->wantsJson()) {
            return $this->success([
                'restore' => $restore,
                'organization' => $organization,
            ]);
        }

        return view('apps.orgs.backup.restore.confirm', [
            'restore' => $restore,
            'backup' => $restore->backup,
            'organization' => $organization,
            'org' => $org,
        ]);
    }

    /**
     * Send verification code for full restore
     */
    public function sendVerificationCode(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        if ($restore->type !== 'full') {
            return $this->error(__('backup.verification_not_required'), 400);
        }

        $user = auth()->user();
        if (!$user || !$user->email) {
            return $this->error(__('backup.user_email_required'), 400);
        }

        // Generate a 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the code in cache for 10 minutes
        $cacheKey = "restore_verification_{$restore->id}";
        cache()->put($cacheKey, [
            'code' => $code,
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10),
        ], now()->addMinutes(10));

        // Send email notification
        try {
            \Illuminate\Support\Facades\Mail::send('emails.backup.verification-code', [
                'user' => $user,
                'code' => $code,
                'restore' => $restore,
                'expiresIn' => 10,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject(__('backup.verification_code_subject'));
            });

            // Log the action
            BackupAuditLog::create([
                'org_id' => $org,
                'action' => 'verification_code_sent',
                'entity_id' => $restore->id,
                'entity_type' => 'backup_restore',
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => [
                    'restore_code' => $restore->restore_code,
                    'email' => $user->email,
                ],
            ]);

            return $this->success([
                'message' => __('backup.verification_code_sent'),
                'email' => substr($user->email, 0, 3) . '***' . substr($user->email, strpos($user->email, '@')),
            ], __('backup.verification_code_sent'));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification code', [
                'restore_id' => $restore->id,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError(__('backup.code_send_failed'));
        }
    }

    /**
     * Process restore
     */
    public function process(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        // Verify confirmation
        if ($restore->type === 'full') {
            $request->validate([
                'org_name_confirmation' => 'required',
                'verification_code' => 'required',
            ]);

            // Verify org name
            $organization = \App\Models\Core\Organization::find($org);
            if ($request->org_name_confirmation !== $organization->name) {
                return back()->withErrors([
                    'org_name_confirmation' => __('backup.org_name_mismatch'),
                ]);
            }

            // Verify the verification code from cache
            $cacheKey = "restore_verification_{$restore->id}";
            $storedData = cache()->get($cacheKey);

            if (!$storedData) {
                return back()->withErrors([
                    'verification_code' => __('backup.verification_code_expired'),
                ]);
            }

            if ($storedData['code'] !== $request->verification_code) {
                return back()->withErrors([
                    'verification_code' => __('backup.verification_code_invalid'),
                ]);
            }

            if ($storedData['user_id'] !== auth()->id()) {
                return back()->withErrors([
                    'verification_code' => __('backup.verification_code_invalid'),
                ]);
            }

            // Clear the used code
            cache()->forget($cacheKey);
        } elseif ($restore->type === 'merge') {
            $request->validate([
                'org_name_confirmation' => 'required',
            ]);

            $organization = \App\Models\Core\Organization::find($org);
            if ($request->org_name_confirmation !== $organization->name) {
                return back()->withErrors([
                    'org_name_confirmation' => __('backup.org_name_mismatch'),
                ]);
            }
        }

        // Mark as confirmed
        $restore->update([
            'confirmed_by' => auth()->id(),
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'restore_started',
            'entity_id' => $restore->id,
            'entity_type' => 'backup_restore',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'restore_code' => $restore->restore_code,
                'type' => $restore->type,
                'backup_code' => $restore->backup?->backup_code,
            ],
        ]);

        // Dispatch restore job
        ProcessRestoreJob::dispatch($restore);

        if ($request->wantsJson()) {
            return $this->success([
                'restore' => $restore,
                'message' => __('backup.restore_started'),
            ]);
        }

        return redirect()->route('orgs.backup.restore.progress', [
            'org' => $org,
            'restore' => $restore->id
        ]);
    }

    /**
     * Show restore progress
     */
    public function progress(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->findOrFail($restore);

        if ($request->wantsJson()) {
            return $this->success($this->getOrchestrator()->getProgress($restore));
        }

        return view('apps.orgs.backup.restore.progress', [
            'restore' => $restore,
            'backup' => $restore->backup,
            'org' => $org,
        ]);
    }

    /**
     * Get progress status (AJAX)
     */
    public function progressStatus(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        return $this->success($this->getOrchestrator()->getProgress($restore));
    }

    /**
     * Rollback a restore
     */
    public function rollback(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->findOrFail($restore);

        try {
            $result = $this->getRollbackService()->rollback($restore);

            // Create audit log
            BackupAuditLog::create([
                'org_id' => $org,
                'action' => 'restore_rolled_back',
                'entity_id' => $restore->id,
                'entity_type' => 'backup_restore',
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->wantsJson()) {
                return $this->success($result);
            }

            return redirect()
                ->route('orgs.backup.restore.progress', [
                    'org' => $org,
                    'restore' => $result['rollback_restore']->id
                ])
                ->with('success', __('backup.rollback_started'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage(), 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show restore complete/result page
     */
    public function complete(Request $request, string $org, string $restore)
    {
        $restore = BackupRestore::where('org_id', $org)
            ->with('backup')
            ->findOrFail($restore);

        if ($request->wantsJson()) {
            return $this->success([
                'restore' => $restore,
                'execution_report' => $restore->execution_report,
            ]);
        }

        return view('apps.orgs.backup.restore.complete', [
            'restore' => $restore,
            'backup' => $restore->backup,
            'org' => $org,
        ]);
    }
}
