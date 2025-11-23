<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Analytics\ReportExecutionLog;
use App\Models\Analytics\ReportTemplate;
use App\Models\Analytics\ScheduledReport;
use App\Services\Analytics\EmailReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Scheduled Reports Controller (Phase 12)
 *
 * Manages automated report schedules, templates, and execution history
 */
class ScheduledReportsController extends Controller
{
    use ApiResponse;

    protected EmailReportService $emailService;

    public function __construct(EmailReportService $emailService)
    {
        $this->middleware('auth:sanctum');
        $this->emailService = $emailService;
    }

    /**
     * List all scheduled reports for organization
     *
     * GET /api/orgs/{org_id}/analytics/scheduled-reports
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = ScheduledReport::where('org_id', $orgId)
            ->with(['user', 'latestExecution']);

        // Filters
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->input('frequency'));
        }

        if ($request->has('report_type')) {
            $query->where('report_type', $request->input('report_type'));
        }

        $schedules = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'schedules' => $schedules
        ]);
    }

    /**
     * Create new scheduled report
     *
     * POST /api/orgs/{org_id}/analytics/scheduled-reports
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|in:campaign,organization,comparison,attribution',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'format' => 'required|in:pdf,xlsx,csv,json',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'config' => 'required|array',
            'timezone' => 'sometimes|string|timezone',
            'delivery_time' => 'sometimes|date_format:H:i:s',
            'day_of_week' => 'sometimes|integer|min:1|max:7',
            'day_of_month' => 'sometimes|integer|min:1|max:31',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        // Initialize RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $schedule = ScheduledReport::create([
            'org_id' => $orgId,
            'user_id' => $user->user_id,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'frequency' => $validated['frequency'],
            'format' => $validated['format'],
            'recipients' => $validated['recipients'],
            'config' => $validated['config'],
            'timezone' => $validated['timezone'] ?? 'UTC',
            'delivery_time' => $validated['delivery_time'] ?? '09:00:00',
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'is_active' => $validated['is_active'] ?? true
        ]);

        // Calculate initial next_run_at
        $schedule->update([
            'next_run_at' => $schedule->calculateNextRunAt()
        ]);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
            'message' => 'Scheduled report created successfully'
        ], 201);
    }

    /**
     * Get specific scheduled report
     *
     * GET /api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}
     */
    public function show(string $orgId, string $scheduleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $schedule = ScheduledReport::with(['user', 'executionLogs' => function ($query) {
            $query->latest('executed_at')->limit(10);
        }])->findOrFail($scheduleId);

        return response()->json([
            'success' => true,
            'schedule' => $schedule
        ]);
    }

    /**
     * Update scheduled report
     *
     * PUT /api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}
     */
    public function update(string $orgId, string $scheduleId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'frequency' => 'sometimes|in:daily,weekly,monthly,quarterly',
            'format' => 'sometimes|in:pdf,xlsx,csv,json',
            'recipients' => 'sometimes|array|min:1',
            'recipients.*' => 'email',
            'config' => 'sometimes|array',
            'timezone' => 'sometimes|string|timezone',
            'delivery_time' => 'sometimes|date_format:H:i:s',
            'day_of_week' => 'sometimes|integer|min:1|max:7',
            'day_of_month' => 'sometimes|integer|min:1|max:31',
            'is_active' => 'sometimes|boolean'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $schedule = ScheduledReport::findOrFail($scheduleId);
        $schedule->update($validated);

        // Recalculate next_run_at if frequency or timing changed
        if (isset($validated['frequency']) ||
            isset($validated['delivery_time']) ||
            isset($validated['day_of_week']) ||
            isset($validated['day_of_month'])) {
            $schedule->update([
                'next_run_at' => $schedule->calculateNextRunAt()
            ]);
        }

        return response()->json([
            'success' => true,
            'schedule' => $schedule->fresh(),
            'message' => 'Scheduled report updated successfully'
        ]);
    }

    /**
     * Delete scheduled report
     *
     * DELETE /api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}
     */
    public function destroy(string $orgId, string $scheduleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $schedule = ScheduledReport::findOrFail($scheduleId);
        $schedule->delete();

        return $this->success(null, 'Scheduled report deleted successfully');
    }

    /**
     * Get execution history for a schedule
     *
     * GET /api/orgs/{org_id}/analytics/scheduled-reports/{schedule_id}/history
     */
    public function history(string $orgId, string $scheduleId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $logs = ReportExecutionLog::where('schedule_id', $scheduleId)
            ->orderBy('executed_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'history' => $logs
        ]);
    }

    /**
     * Send one-time report via email
     *
     * POST /api/orgs/{org_id}/analytics/send-report
     */
    public function sendOneTime(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_type' => 'required|in:campaign,organization,comparison',
            'format' => 'required|in:pdf,xlsx,csv,json',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'config' => 'required|array'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $result = $this->emailService->sendOneTimeReport(
            $orgId,
            $validated['report_type'],
            $validated['config'],
            $validated['recipients']
        );

        return $this->success($result, 'Operation completed successfully');
    }

    /**
     * List available report templates
     *
     * GET /api/analytics/report-templates
     */
    public function templates(Request $request): JsonResponse
    {
        $query = ReportTemplate::query();

        // Show public templates and user's own templates
        $query->where(function ($q) use ($request) {
            $q->where('is_public', true)
              ->orWhere('created_by', $request->user()->user_id);
        });

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('report_type')) {
            $query->where('report_type', $request->input('report_type'));
        }

        $templates = $query->orderBy('usage_count', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Create report template
     *
     * POST /api/analytics/report-templates
     */
    public function createTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'report_type' => 'required|in:campaign,organization,comparison,attribution',
            'default_config' => 'required|array',
            'category' => 'required|in:marketing,sales,executive,custom',
            'is_public' => 'sometimes|boolean'
        ]);

        $template = ReportTemplate::create([
            'created_by' => $request->user()->user_id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'report_type' => $validated['report_type'],
            'default_config' => $validated['default_config'],
            'category' => $validated['category'],
            'is_public' => $validated['is_public'] ?? false,
            'is_system' => false
        ]);

        return response()->json([
            'success' => true,
            'template' => $template,
            'message' => 'Report template created successfully'
        ], 201);
    }

    /**
     * Apply template to create schedule
     *
     * POST /api/orgs/{org_id}/analytics/scheduled-reports/from-template/{template_id}
     */
    public function createFromTemplate(string $orgId, string $templateId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'format' => 'required|in:pdf,xlsx,csv,json',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'timezone' => 'sometimes|string|timezone',
            'delivery_time' => 'sometimes|date_format:H:i:s',
            'config_overrides' => 'sometimes|array'
        ]);

        $template = ReportTemplate::findOrFail($templateId);
        $template->incrementUsage();

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        // Merge template config with overrides
        $config = array_merge(
            $template->default_config,
            $validated['config_overrides'] ?? []
        );

        $schedule = ScheduledReport::create([
            'org_id' => $orgId,
            'user_id' => $user->user_id,
            'name' => $validated['name'],
            'report_type' => $template->report_type,
            'frequency' => $validated['frequency'],
            'format' => $validated['format'],
            'recipients' => $validated['recipients'],
            'config' => $config,
            'timezone' => $validated['timezone'] ?? 'UTC',
            'delivery_time' => $validated['delivery_time'] ?? '09:00:00',
            'is_active' => true
        ]);

        $schedule->update([
            'next_run_at' => $schedule->calculateNextRunAt()
        ]);

        return response()->json([
            'success' => true,
            'schedule' => $schedule,
            'message' => 'Scheduled report created from template'
        ], 201);
    }
}
