<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait for logging super admin actions to the super_admin_actions table.
 *
 * This trait provides consistent logging of admin activities across all
 * super admin controllers with proper error handling and debugging.
 */
trait LogsSuperAdminActions
{
    /**
     * Log a super admin action.
     *
     * @param string $actionType The type of action (e.g., 'user_suspended', 'org_blocked')
     * @param string $targetType The type of target (e.g., 'user', 'organization', 'plan')
     * @param string|null $targetId The ID of the target entity
     * @param string|null $targetName The name/identifier of the target for display
     * @param array $details Additional details about the action
     */
    protected function logAction(string $actionType, string $targetType, ?string $targetId, ?string $targetName, array $details = []): void
    {
        // Get the current authenticated user
        $user = Auth::user();
        $adminUserId = $user ? $user->user_id : null;

        // Log debug info
        Log::info('Super admin action attempt', [
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_name' => $targetName,
            'admin_user_id' => $adminUserId,
            'admin_email' => $user ? $user->email : null,
        ]);

        // Validate that we have a valid admin user ID
        if (!$adminUserId) {
            Log::error('Cannot log super admin action: No authenticated user', [
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id(),
            ]);
            return;
        }

        try {
            $data = [
                'action_id' => Str::uuid(),
                'admin_user_id' => $adminUserId,
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_name' => $targetName,
                'details' => json_encode($details),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ];

            DB::table('cmis.super_admin_actions')->insert($data);

            Log::info('Super admin action logged successfully', [
                'action_type' => $actionType,
                'target_type' => $targetType,
                'admin_user_id' => $adminUserId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log super admin action', [
                'error' => $e->getMessage(),
                'action_type' => $actionType,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'admin_user_id' => $adminUserId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
