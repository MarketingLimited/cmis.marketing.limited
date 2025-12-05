<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Backup\BackupSetting;
use App\Models\Backup\BackupAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Backup Settings Controller
 *
 * Manages backup notification and storage settings for organizations.
 */
class BackupSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display settings page
     */
    public function index(Request $request, string $org)
    {
        $settings = BackupSetting::firstOrCreate(
            ['org_id' => $org],
            [
                'email_on_backup_complete' => true,
                'email_on_backup_failed' => true,
                'email_on_restore_complete' => true,
                'email_on_restore_failed' => true,
                'notify_all_admins' => false,
                'notification_emails' => [],
                'default_storage_disk' => config('backup.storage.default', 'local'),
            ]
        );

        $storageDisks = [
            'local' => __('backup.storage_local'),
            'google' => __('backup.storage_google'),
            'onedrive' => __('backup.storage_onedrive'),
            'dropbox' => __('backup.storage_dropbox'),
        ];

        if ($request->wantsJson()) {
            return $this->success([
                'settings' => $settings,
                'storage_disks' => $storageDisks,
            ]);
        }

        return view('apps.backup.settings.index', compact('settings', 'storageDisks', 'org'));
    }

    /**
     * Update settings
     */
    public function update(Request $request, string $org)
    {
        $validated = $request->validate([
            'email_on_backup_complete' => 'boolean',
            'email_on_backup_failed' => 'boolean',
            'email_on_restore_complete' => 'boolean',
            'email_on_restore_failed' => 'boolean',
            'notify_all_admins' => 'boolean',
            'notification_emails' => 'nullable|array',
            'notification_emails.*' => 'email',
            'default_storage_disk' => 'nullable|string|in:local,google,onedrive,dropbox',
            'google_client_id' => 'nullable|string',
            'google_client_secret' => 'nullable|string',
            'onedrive_client_id' => 'nullable|string',
            'onedrive_client_secret' => 'nullable|string',
            'dropbox_token' => 'nullable|string',
        ]);

        $settings = BackupSetting::firstOrCreate(
            ['org_id' => $org],
            [
                'email_on_backup_complete' => true,
                'email_on_backup_failed' => true,
                'email_on_restore_complete' => true,
                'email_on_restore_failed' => true,
                'notify_all_admins' => false,
            ]
        );

        // Prepare storage credentials
        $storageCredentials = $settings->storage_credentials ?? [];

        if (!empty($validated['google_client_id']) && !empty($validated['google_client_secret'])) {
            $storageCredentials['google'] = [
                'client_id' => $validated['google_client_id'],
                'client_secret' => Crypt::encryptString($validated['google_client_secret']),
            ];
        }

        if (!empty($validated['onedrive_client_id']) && !empty($validated['onedrive_client_secret'])) {
            $storageCredentials['onedrive'] = [
                'client_id' => $validated['onedrive_client_id'],
                'client_secret' => Crypt::encryptString($validated['onedrive_client_secret']),
            ];
        }

        if (!empty($validated['dropbox_token'])) {
            $storageCredentials['dropbox'] = [
                'token' => Crypt::encryptString($validated['dropbox_token']),
            ];
        }

        $settings->update([
            'email_on_backup_complete' => $validated['email_on_backup_complete'] ?? false,
            'email_on_backup_failed' => $validated['email_on_backup_failed'] ?? false,
            'email_on_restore_complete' => $validated['email_on_restore_complete'] ?? false,
            'email_on_restore_failed' => $validated['email_on_restore_failed'] ?? false,
            'notify_all_admins' => $validated['notify_all_admins'] ?? false,
            'notification_emails' => array_filter($validated['notification_emails'] ?? []),
            'default_storage_disk' => $validated['default_storage_disk'] ?? 'local',
            'storage_credentials' => $storageCredentials,
        ]);

        // Create audit log
        BackupAuditLog::create([
            'org_id' => $org,
            'action' => 'settings_updated',
            'entity_id' => $settings->id,
            'entity_type' => 'backup_setting',
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'details' => [
                'changes' => array_keys($settings->getChanges()),
            ],
        ]);

        if ($request->wantsJson()) {
            return $this->success($settings, __('backup.settings_updated'));
        }

        return redirect()
            ->route('backup.settings', ['org' => $org])
            ->with('success', __('backup.settings_updated'));
    }

    /**
     * Test storage connection
     */
    public function testStorage(Request $request, string $org)
    {
        $validated = $request->validate([
            'disk' => 'required|string|in:local,google,onedrive,dropbox',
        ]);

        try {
            $settings = BackupSetting::where('org_id', $org)->first();
            $disk = $validated['disk'];

            // Test connection based on disk type
            $result = match ($disk) {
                'local' => $this->testLocalStorage(),
                'google' => $this->testGoogleStorage($settings),
                'onedrive' => $this->testOneDriveStorage($settings),
                'dropbox' => $this->testDropboxStorage($settings),
            };

            return $this->success([
                'connected' => $result['connected'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Test local storage
     */
    protected function testLocalStorage(): array
    {
        $path = storage_path('app/backups');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $testFile = $path . '/test_' . time() . '.txt';
        $written = file_put_contents($testFile, 'test');

        if ($written !== false) {
            unlink($testFile);
            return [
                'connected' => true,
                'message' => __('backup.storage_test_success'),
            ];
        }

        return [
            'connected' => false,
            'message' => __('backup.storage_test_failed'),
        ];
    }

    /**
     * Test Google Drive storage
     */
    protected function testGoogleStorage(?BackupSetting $settings): array
    {
        if (!$settings || empty($settings->storage_credentials['google'] ?? null)) {
            return [
                'connected' => false,
                'message' => __('backup.storage_not_configured'),
            ];
        }

        // TODO: Implement actual Google Drive API test
        return [
            'connected' => false,
            'message' => __('backup.storage_google_not_implemented'),
        ];
    }

    /**
     * Test OneDrive storage
     */
    protected function testOneDriveStorage(?BackupSetting $settings): array
    {
        if (!$settings || empty($settings->storage_credentials['onedrive'] ?? null)) {
            return [
                'connected' => false,
                'message' => __('backup.storage_not_configured'),
            ];
        }

        // TODO: Implement actual OneDrive API test
        return [
            'connected' => false,
            'message' => __('backup.storage_onedrive_not_implemented'),
        ];
    }

    /**
     * Test Dropbox storage
     */
    protected function testDropboxStorage(?BackupSetting $settings): array
    {
        if (!$settings || empty($settings->storage_credentials['dropbox'] ?? null)) {
            return [
                'connected' => false,
                'message' => __('backup.storage_not_configured'),
            ];
        }

        // TODO: Implement actual Dropbox API test
        return [
            'connected' => false,
            'message' => __('backup.storage_dropbox_not_implemented'),
        ];
    }
}
