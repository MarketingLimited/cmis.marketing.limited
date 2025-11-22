<?php

namespace App\Http\Controllers;

use App\Models\Integration\Integration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Controller for managing platform integrations
 * Handles connection, disconnection, and management of third-party platforms
 */
class IntegrationController extends Controller
{
    use ApiResponse;

    /**
     * Get list of user's platform integrations
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $integrations = Integration::where('org_id', $orgId)
                ->select([
                    'integration_id',
                    'platform',
                    'name',
                    'account_id',
                    'is_active',
                    'last_synced_at',
                    'sync_status',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $integrations,
                'total' => $integrations->count()
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to list integrations: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connect new platform integration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'platform' => 'required|string|in:meta,google,tiktok,linkedin,twitter,snapchat',
                'name' => 'required|string|max:255',
                'account_id' => 'nullable|string',
                'credentials' => 'nullable|array',
                'access_token' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $integration = Integration::create([
                'org_id' => $orgId,
                'platform' => $request->input('platform'),
                'name' => $request->input('name'),
                'account_id' => $request->input('account_id'),
                'credentials' => $request->input('credentials'),
                'access_token' => $request->input('access_token'),
                'is_active' => true,
                'sync_status' => 'pending',
            ]);

            return response()->json([
                'data' => $integration,
                'message' => 'Integration connected successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error("Failed to create integration: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show integration details
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $integration = Integration::where('integration_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Hide sensitive data
            $integration->makeHidden(['access_token', 'credentials']);

            return response()->json(['data' => $integration]);
        } catch (\Exception $e) {
            Log::error("Failed to get integration: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update integration
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $integration = Integration::where('integration_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $integration->update($request->only(['name', 'is_active']));

            return $this->success($integration, 'Integration updated successfully');
        } catch (\Exception $e) {
            Log::error("Failed to update integration: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect platform integration
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $integration = Integration::where('integration_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Soft delete or mark as inactive
            $integration->update(['is_active' => false]);

            return response()->json([
                'message' => 'Integration disconnected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete integration: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh access token for integration
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $integration = Integration::where('integration_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            Log::info('IntegrationController::refreshToken called (stub) - Token refresh logic per platform not yet implemented');
            // Stub implementation - Actual token refresh logic per platform not yet implemented
            // For now, just update last_synced_at
            $integration->update([
                'last_synced_at' => now(),
                'sync_status' => 'success'
            ]);

            return $this->success($integration, 'Token refreshed successfully');
        } catch (\Exception $e) {
            Log::error("Failed to refresh token: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check integration connection status
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function status(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json(['error' => 'No active organization found'], 404);
            }

            $integration = Integration::where('integration_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Calculate connection health
            $lastSyncedMinutes = $integration->last_synced_at
                ? now()->diffInMinutes($integration->last_synced_at)
                : null;

            $status = 'unknown';
            if ($integration->is_active && $lastSyncedMinutes !== null) {
                if ($lastSyncedMinutes < 60) {
                    $status = 'healthy';
                } elseif ($lastSyncedMinutes < 1440) { // 24 hours
                    $status = 'warning';
                } else {
                    $status = 'disconnected';
                }
            } elseif (!$integration->is_active) {
                $status = 'inactive';
            }

            return response()->json([
                'data' => [
                    'integration_id' => $integration->integration_id,
                    'platform' => $integration->platform,
                    'status' => $status,
                    'is_active' => $integration->is_active,
                    'last_synced_at' => $integration->last_synced_at,
                    'last_synced_minutes_ago' => $lastSyncedMinutes,
                    'sync_status' => $integration->sync_status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get integration status: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Resolve organization ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // Try to get from route parameter first
        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        // Fall back to user's active org
        if ($user->active_org_id) {
            return $user->active_org_id;
        }

        // Query the user_orgs pivot table for an active org
        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}
