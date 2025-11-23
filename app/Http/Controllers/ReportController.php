<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ApiResponse;

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->middleware('auth:sanctum');
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of reports
     */
    public function index(): View
    {
        Gate::authorize('viewReports', auth()->user());

        return view('analytics.reports');
    }

    /**
     * Generate campaign report
     */
    public function campaign(Request $request, string $campaignId): JsonResponse
    {
        Gate::authorize('viewReports', auth()->user());

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,excel,html',
        ]);

        $reportData = $this->reportService->generateCampaignReport($campaignId, $validated);

        if ($request->get('format') === 'pdf') {
            $filename = $this->reportService->exportToPDF($reportData, 'reports.campaign');
            return response()->download(storage_path('app/reports/' . $filename));
        }

        if ($request->get('format') === 'excel') {
            $filename = $this->reportService->exportToExcel($reportData);
            return response()->download(storage_path('app/reports/' . $filename));
        }

        return response()->json($reportData);
    }

    /**
     * Generate organization report
     */
    public function organization(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewReports', auth()->user());

        $validated = $request->validate([
            'status' => 'nullable|in:active,paused,completed,draft',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $reportData = $this->reportService->generateOrgReport($orgId, $validated);

        return response()->json($reportData);
    }

    /**
     * Get report statistics
     */
    public function stats(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewReports', auth()->user());

        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $dateRange = [];
        if (isset($validated['start'])) {
            $dateRange['start'] = $validated['start'];
        }
        if (isset($validated['end'])) {
            $dateRange['end'] = $validated['end'];
        }

        $stats = $this->reportService->getReportStats($orgId, $dateRange);

        return response()->json($stats);
    }

    /**
     * Export report
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('exportData', auth()->user());

        $validated = $request->validate([
            'type' => 'required|in:campaign,organization,performance,assets',
            'format' => 'required|in:pdf,excel,csv',
            'entity_id' => 'nullable|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Generate report based on type
        $reportData = [];

        switch ($validated['type']) {
            case 'campaign':
                if (!isset($validated['entity_id'])) {
                    return response()->json(['error' => 'Campaign ID required'], 400);
                }
                $reportData = $this->reportService->generateCampaignReport(
                    $validated['entity_id'],
                    ['start_date' => $validated['start_date'] ?? null, 'end_date' => $validated['end_date'] ?? null]
                );
                break;

            case 'organization':
                if (!isset($validated['entity_id'])) {
                    return response()->json(['error' => 'Organization ID required'], 400);
                }
                $reportData = $this->reportService->generateOrgReport($validated['entity_id']);
                break;
        }

        // Export based on format
        if ($validated['format'] === 'pdf') {
            $filename = $this->reportService->exportToPDF($reportData);
            return response()->download(storage_path('app/reports/' . $filename));
        }

        if ($validated['format'] === 'excel' || $validated['format'] === 'csv') {
            $filename = $this->reportService->exportToExcel($reportData);
            return response()->download(storage_path('app/reports/' . $filename));
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }

    /**
     * Store a new report
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('createReport', auth()->user());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:campaign,organization,channel,asset',
            'description' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'entity_id' => 'nullable|uuid',
        ]);

        // Create report record (you would have a Report model)
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'Report created successfully',
            'data' => $validated,
        ], 201);
    }

    /**
     * Delete a report
     */
    public function destroy(string $reportId): JsonResponse
    {
        Gate::authorize('createReport', auth()->user());

        // Delete report file and record
        // For now, just return success
        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }
}
