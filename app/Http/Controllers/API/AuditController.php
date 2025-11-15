<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Policies\AuditPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    /**
     * Get realtime audit status
     *
     * @return JsonResponse
     */
    public function realtimeStatus(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewRealtimeStatus($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view realtime status'
            ], 403);
        }

        try {
            $status = DB::select("SELECT * FROM cmis_audit.realtime_status")[0] ?? null;

            if (!$status) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'recent_failures' => 0,
                        'security_events' => 0,
                        'knowledge_updates' => 0,
                        'completed_tasks' => 0,
                        'system_operations' => 0,
                        'last_update' => null
                    ],
                    'message' => 'No data available'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Realtime status retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve realtime status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily summary
     *
     * @return JsonResponse
     */
    public function dailySummary(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewReports($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view reports'
            ], 403);
        }

        try {
            $summary = DB::select("SELECT * FROM cmis_audit.daily_summary")[0] ?? null;

            if (!$summary) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No data available for daily summary'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Daily summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve daily summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weekly performance
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function weeklyPerformance(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewReports($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view reports'
            ], 403);
        }

        try {
            $limit = $request->input('limit', 4);

            $weeks = DB::select("
                SELECT * FROM cmis_audit.weekly_performance
                LIMIT ?
            ", [$limit]);

            return response()->json([
                'success' => true,
                'data' => $weeks,
                'count' => count($weeks),
                'message' => 'Weekly performance retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve weekly performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive audit summary
     *
     * @return JsonResponse
     */
    public function auditSummary(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewReports($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view reports'
            ], 403);
        }

        try {
            $summary = DB::select("SELECT * FROM cmis_audit.audit_summary");

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Audit summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get activity log with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activityLog(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewActivityLog($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view activity log'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'category' => 'nullable|in:task,knowledge,security,system',
                'actor' => 'nullable|string',
                'action' => 'nullable|string',
                'from' => 'nullable|date',
                'to' => 'nullable|date',
                'limit' => 'nullable|integer|min:1|max:1000',
                'offset' => 'nullable|integer|min:0'
            ]);

            $query = "SELECT * FROM cmis_audit.activity_log WHERE 1=1";
            $params = [];

            if (!empty($validated['category'])) {
                $query .= " AND category = ?";
                $params[] = $validated['category'];
            }

            if (!empty($validated['actor'])) {
                $query .= " AND actor = ?";
                $params[] = $validated['actor'];
            }

            if (!empty($validated['action'])) {
                $query .= " AND action = ?";
                $params[] = $validated['action'];
            }

            if (!empty($validated['from'])) {
                $query .= " AND created_at >= ?";
                $params[] = $validated['from'];
            }

            if (!empty($validated['to'])) {
                $query .= " AND created_at <= ?";
                $params[] = $validated['to'];
            }

            $query .= " ORDER BY created_at DESC";

            if (!empty($validated['limit'])) {
                $query .= " LIMIT ?";
                $params[] = $validated['limit'];
            } else {
                $query .= " LIMIT 100";
            }

            if (!empty($validated['offset'])) {
                $query .= " OFFSET ?";
                $params[] = $validated['offset'];
            }

            $logs = DB::select($query, $params);

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM cmis_audit.activity_log WHERE 1=1";
            $countParams = array_slice($params, 0, count($params) - (isset($validated['limit']) ? 1 : 0) - (isset($validated['offset']) ? 1 : 0));
            $total = DB::select($countQuery . substr($query, strpos($query, " AND"), strpos($query, " ORDER") - strpos($query, " AND")), $countParams)[0]->total ?? 0;

            return response()->json([
                'success' => true,
                'data' => $logs,
                'pagination' => [
                    'total' => $total,
                    'limit' => $validated['limit'] ?? 100,
                    'offset' => $validated['offset'] ?? 0,
                    'count' => count($logs)
                ],
                'message' => 'Activity log retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log a new event
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logEvent(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->logEvent($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to log events'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'actor' => 'required|string|max:255',
                'action' => 'required|string|max:255',
                'category' => 'required|in:task,knowledge,security,system',
                'context' => 'nullable|array'
            ]);

            DB::table('cmis_audit.activity_log')->insert([
                'actor' => $validated['actor'],
                'action' => $validated['action'],
                'category' => $validated['category'],
                'context' => !empty($validated['context']) ? json_encode($validated['context']) : null,
                'created_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event logged successfully',
                'data' => [
                    'actor' => $validated['actor'],
                    'action' => $validated['action'],
                    'category' => $validated['category'],
                    'created_at' => now()->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check alerts
     *
     * @return JsonResponse
     */
    public function checkAlerts(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewAlerts($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view alerts'
            ], 403);
        }

        try {
            $alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

            $criticalAlerts = array_filter($alerts, fn($a) => $a->severity === 'critical');

            return response()->json([
                'success' => true,
                'data' => $alerts,
                'has_critical' => !empty($criticalAlerts),
                'count' => count($alerts),
                'message' => empty($alerts) ? 'No alerts' : count($alerts) . ' alert(s) found'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->exportReports($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to export reports'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'period' => 'required|in:daily_summary,weekly_performance,realtime_status,audit_summary',
                'path' => 'nullable|string'
            ]);

            $path = $validated['path'] ?? '/tmp';

            $result = DB::select("
                SELECT * FROM cmis_audit.export_audit_report(?, ?)
            ", [$validated['period'], $path])[0];

            if ($result->success) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'file_path' => $result->file_path,
                        'row_count' => $result->row_count
                    ],
                    'message' => $result->message
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->message
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics dashboard
     *
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Check permission
        $policy = new AuditPolicy();
        if (!$policy->viewDashboard($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to view audit dashboard'
            ], 403);
        }

        try {
            $realtime = DB::select("SELECT * FROM cmis_audit.realtime_status")[0] ?? null;
            $daily = DB::select("SELECT * FROM cmis_audit.daily_summary")[0] ?? null;
            $alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

            return response()->json([
                'success' => true,
                'data' => [
                    'realtime' => $realtime,
                    'daily_summary' => $daily,
                    'alerts' => $alerts,
                    'has_critical_alerts' => !empty(array_filter($alerts, fn($a) => $a->severity === 'critical'))
                ],
                'message' => 'Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
