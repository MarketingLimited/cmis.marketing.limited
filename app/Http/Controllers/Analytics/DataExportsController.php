<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDataExportJob;
use App\Models\Analytics\DataExportConfig;
use App\Models\Analytics\DataExportLog;
use App\Models\Core\APIToken;
use App\Services\Analytics\DataExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data Exports Controller (Phase 14)
 *
 * Manages data export configurations, API tokens, and export execution
 */
class DataExportsController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List export configurations
     * GET /api/orgs/{org_id}/exports/configs
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = DataExportConfig::where('org_id', $orgId)
            ->with(['creator']);

        if ($request->has('export_type')) {
            $query->where('export_type', $request->input('export_type'));
        }

        if ($request->has('format')) {
            $query->where('format', $request->input('format'));
        }

        if ($request->has('delivery_method')) {
            $query->where('delivery_method', $request->input('delivery_method'));
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $configs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'configs' => $configs
        ]);
    }

    /**
     * Create export configuration
     * POST /api/orgs/{org_id}/exports/configs
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'export_type' => 'required|in:analytics,campaigns,metrics,custom',
            'format' => 'required|in:json,csv,xlsx,parquet',
            'delivery_method' => 'required|in:download,webhook,sftp,s3',
            'data_config' => 'required|array',
            'delivery_config' => 'required|array',
            'schedule' => 'sometimes|array',
            'schedule.frequency' => 'required_with:schedule|in:hourly,daily,weekly,monthly',
            'schedule.time' => 'sometimes|date_format:H:i',
            'schedule.day_of_week' => 'sometimes|integer|between:0,6',
            'schedule.day_of_month' => 'sometimes|integer|between:1,31',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $config = DataExportConfig::create([
            'org_id' => $orgId,
            'created_by' => $user->user_id,
            ...$validated
        ]);

        return response()->json([
            'success' => true,
            'config' => $config->load('creator'),
            'message' => 'Export configuration created successfully'
        ], 201);
    }

    /**
     * Get export configuration
     * GET /api/orgs/{org_id}/exports/configs/{config_id}
     */
    public function show(string $orgId, string $configId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $config = DataExportConfig::with(['creator', 'logs'])
            ->findOrFail($configId);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    /**
     * Update export configuration
     * PUT /api/orgs/{org_id}/exports/configs/{config_id}
     */
    public function update(string $orgId, string $configId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'format' => 'sometimes|in:json,csv,xlsx,parquet',
            'delivery_method' => 'sometimes|in:download,webhook,sftp,s3',
            'data_config' => 'sometimes|array',
            'delivery_config' => 'sometimes|array',
            'schedule' => 'sometimes|array',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $config = DataExportConfig::findOrFail($configId);
        $config->update($validated);

        return response()->json([
            'success' => true,
            'config' => $config->fresh(['creator']),
            'message' => 'Export configuration updated successfully'
        ]);
    }

    /**
     * Delete export configuration
     * DELETE /api/orgs/{org_id}/exports/configs/{config_id}
     */
    public function destroy(string $orgId, string $configId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $config = DataExportConfig::findOrFail($configId);
        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Export configuration deleted successfully'
        ]);
    }

    /**
     * Execute manual export
     * POST /api/orgs/{org_id}/exports/execute
     */
    public function execute(string $orgId, Request $request, DataExportService $exportService): JsonResponse
    {
        $validated = $request->validate([
            'config_id' => 'sometimes|uuid',
            'export_type' => 'required_without:config_id|in:analytics,campaigns,metrics,custom',
            'format' => 'required_without:config_id|in:json,csv,xlsx,parquet',
            'data_config' => 'required_without:config_id|array',
            'async' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        // Use existing config or create manual export
        if (isset($validated['config_id'])) {
            $config = DataExportConfig::findOrFail($validated['config_id']);
        } else {
            // Manual one-time export
            if ($validated['async'] ?? false) {
                // Create temporary config and dispatch job
                $config = DataExportConfig::create([
                    'org_id' => $orgId,
                    'created_by' => $user->user_id,
                    'name' => 'manual_export_' . time(),
                    'export_type' => $validated['export_type'],
                    'format' => $validated['format'],
                    'delivery_method' => 'download',
                    'data_config' => $validated['data_config'],
                    'delivery_config' => [],
                    'is_active' => false
                ]);

                ProcessDataExportJob::dispatch($config->config_id, $orgId);

                return response()->json([
                    'success' => true,
                    'message' => 'Export queued for processing',
                    'config_id' => $config->config_id
                ], 202);
            }

            // Synchronous manual export
            $log = $exportService->manualExport(
                $orgId,
                $validated['export_type'],
                $validated['format'],
                $validated['data_config']
            );

            return response()->json([
                'success' => true,
                'log' => $log,
                'download_url' => route('api.exports.download', [
                    'org_id' => $orgId,
                    'log_id' => $log->log_id
                ])
            ]);
        }

        // Execute existing config
        if ($validated['async'] ?? true) {
            ProcessDataExportJob::dispatch($config->config_id, $orgId);

            return response()->json([
                'success' => true,
                'message' => 'Export queued for processing',
                'config_id' => $config->config_id
            ], 202);
        }

        $log = $exportService->executeExport($config);

        return response()->json([
            'success' => true,
            'log' => $log,
            'download_url' => route('api.exports.download', [
                'org_id' => $orgId,
                'log_id' => $log->log_id
            ])
        ]);
    }

    /**
     * Get export logs
     * GET /api/orgs/{org_id}/exports/logs
     */
    public function logs(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = DataExportLog::where('org_id', $orgId)
            ->with(['config']);

        if ($request->has('config_id')) {
            $query->where('config_id', $request->input('config_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('format')) {
            $query->where('format', $request->input('format'));
        }

        if ($request->has('days')) {
            $query->where('started_at', '>=', now()->subDays($request->integer('days')));
        }

        $logs = $query->latest('started_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Download export file
     * GET /api/orgs/{org_id}/exports/download/{log_id}
     */
    public function download(string $orgId, string $logId, Request $request)
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $log = DataExportLog::where('org_id', $orgId)
            ->where('log_id', $logId)
            ->where('status', 'completed')
            ->firstOrFail();

        if (!$log->file_path || !Storage::exists($log->file_path)) {
            return $this->error('Export file not found or has been deleted', 404);
        }

        return Storage::download($log->file_path, basename($log->file_path));
    }

    /**
     * List API tokens
     * GET /api/orgs/{org_id}/api-tokens
     */
    public function tokens(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = APIToken::where('org_id', $orgId)
            ->with(['creator']);

        if ($request->has('active')) {
            if ($request->boolean('active')) {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $tokens = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'tokens' => $tokens
        ]);
    }

    /**
     * Create API token
     * POST /api/orgs/{org_id}/api-tokens
     */
    public function createToken(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'required|array',
            'scopes.*' => 'string|in:analytics:read,campaigns:read,exports:read,exports:write',
            'rate_limits' => 'sometimes|array',
            'expires_at' => 'sometimes|date|after:now'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $tokenData = APIToken::generateToken();

        $token = APIToken::create([
            'org_id' => $orgId,
            'created_by' => $user->user_id,
            'name' => $validated['name'],
            'token_hash' => $tokenData['hash'],
            'token_prefix' => $tokenData['prefix'],
            'scopes' => $validated['scopes'],
            'rate_limits' => $validated['rate_limits'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'token' => $token->load('creator'),
            'plaintext_token' => $tokenData['token'],
            'message' => 'API token created. Store the plaintext token securely - it will not be shown again.'
        ], 201);
    }

    /**
     * Revoke API token
     * DELETE /api/orgs/{org_id}/api-tokens/{token_id}
     */
    public function revokeToken(string $orgId, string $tokenId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $token = APIToken::where('org_id', $orgId)
            ->findOrFail($tokenId);

        $token->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'API token revoked successfully'
        ]);
    }

    /**
     * Get export statistics
     * GET /api/orgs/{org_id}/exports/stats
     */
    public function stats(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $days = $request->integer('days', 30);

        $stats = [
            'total_configs' => DataExportConfig::where('org_id', $orgId)->count(),
            'active_configs' => DataExportConfig::where('org_id', $orgId)
                ->where('is_active', true)
                ->count(),
            'total_exports' => DataExportLog::where('org_id', $orgId)
                ->where('started_at', '>=', now()->subDays($days))
                ->count(),
            'successful_exports' => DataExportLog::where('org_id', $orgId)
                ->where('status', 'completed')
                ->where('started_at', '>=', now()->subDays($days))
                ->count(),
            'failed_exports' => DataExportLog::where('org_id', $orgId)
                ->where('status', 'failed')
                ->where('started_at', '>=', now()->subDays($days))
                ->count(),
            'total_records_exported' => DataExportLog::where('org_id', $orgId)
                ->where('status', 'completed')
                ->where('started_at', '>=', now()->subDays($days))
                ->sum('records_count'),
            'total_data_size' => DataExportLog::where('org_id', $orgId)
                ->where('status', 'completed')
                ->where('started_at', '>=', now()->subDays($days))
                ->sum('file_size'),
            'active_tokens' => APIToken::where('org_id', $orgId)
                ->active()
                ->count()
        ];

        // Format breakdown by format
        $byFormat = DataExportLog::where('org_id', $orgId)
            ->where('started_at', '>=', now()->subDays($days))
            ->select('format', DB::raw('COUNT(*) as count'))
            ->groupBy('format')
            ->get()
            ->pluck('count', 'format');

        $stats['by_format'] = $byFormat;

        // Recent exports
        $stats['recent_exports'] = DataExportLog::where('org_id', $orgId)
            ->with('config')
            ->latest('started_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'period_days' => $days
        ]);
    }
}
