<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CreativeAsset;
use App\Models\PerformanceMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    /**
     * Generate campaign performance report
     */
    public function generateCampaignReport(string $campaignId, array $options = []): array
    {
        try {
            $campaign = Campaign::with(['org', 'offerings', 'performanceMetrics'])
                ->findOrFail($campaignId);

            $metrics = $campaign->performanceMetrics()
                ->when(isset($options['start_date']), fn($q) =>
                    $q->where('collected_at', '>=', $options['start_date']))
                ->when(isset($options['end_date']), fn($q) =>
                    $q->where('collected_at', '<=', $options['end_date']))
                ->orderBy('collected_at', 'desc')
                ->get();

            $summary = [
                'total_metrics' => $metrics->count(),
                'avg_performance' => $metrics->avg('metric_value'),
                'avg_confidence' => $metrics->avg('confidence_level'),
                'total_variance' => $metrics->sum('variance'),
                'period' => [
                    'start' => $metrics->last()?->collected_at,
                    'end' => $metrics->first()?->collected_at,
                ],
            ];

            return [
                'campaign' => $campaign,
                'metrics' => $metrics,
                'summary' => $summary,
                'generated_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate campaign report', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate organization overview report
     */
    public function generateOrgReport(string $orgId, array $options = []): array
    {
        try {
            $stats = [
                'campaigns' => Campaign::where('org_id', $orgId)->count(),
                'active_campaigns' => Campaign::where('org_id', $orgId)
                    ->where('status', 'active')->count(),
                'creative_assets' => CreativeAsset::where('org_id', $orgId)->count(),
                'performance_metrics' => DB::table('cmis.performance_metrics')
                    ->join('cmis.campaigns', 'cmis.performance_metrics.campaign_id', '=', 'cmis.campaigns.campaign_id')
                    ->where('cmis.campaigns.org_id', $orgId)
                    ->count(),
            ];

            $campaigns = Campaign::where('org_id', $orgId)
                ->with(['performanceMetrics' => fn($q) => $q->latest()->limit(5)])
                ->when(isset($options['status']), fn($q) =>
                    $q->where('status', $options['status']))
                ->orderBy('created_at', 'desc')
                ->limit($options['limit'] ?? 10)
                ->get();

            $recentMetrics = PerformanceMetric::query()
                ->join('cmis.campaigns', 'cmis.performance_metrics.campaign_id', '=', 'cmis.campaigns.campaign_id')
                ->where('cmis.campaigns.org_id', $orgId)
                ->select('cmis.performance_metrics.*')
                ->orderBy('cmis.performance_metrics.collected_at', 'desc')
                ->limit(20)
                ->get();

            return [
                'org_id' => $orgId,
                'stats' => $stats,
                'campaigns' => $campaigns,
                'recent_metrics' => $recentMetrics,
                'generated_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate org report', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Export report to PDF
     */
    public function exportToPDF(array $reportData, string $template = 'reports.campaign'): string
    {
        try {
            $pdf = Pdf::loadView($template, $reportData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $filename = 'report_' . now()->format('Y-m-d_His') . '.pdf';
            $path = storage_path('app/reports/' . $filename);

            $pdf->save($path);

            Log::info('Report exported to PDF', ['filename' => $filename]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Failed to export PDF', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Export report to Excel
     */
    public function exportToExcel(array $reportData, string $filename = null): string
    {
        try {
            $filename = $filename ?? 'report_' . now()->format('Y-m-d_His') . '.xlsx';
            $path = storage_path('app/reports/' . $filename);

            // Implement Excel export logic here
            // You would use Laravel Excel package for this

            Log::info('Report exported to Excel', ['filename' => $filename]);

            return $filename;

        } catch (\Exception $e) {
            Log::error('Failed to export Excel', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get report summary statistics
     */
    public function getReportStats(string $orgId, array $dateRange = []): array
    {
        try {
            $query = Campaign::where('org_id', $orgId);

            if (isset($dateRange['start'])) {
                $query->where('created_at', '>=', $dateRange['start']);
            }
            if (isset($dateRange['end'])) {
                $query->where('created_at', '<=', $dateRange['end']);
            }

            $campaignStats = $query->selectRaw('
                COUNT(*) as total_campaigns,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                SUM(budget) as total_budget
            ')->first();

            return [
                'campaigns' => $campaignStats,
                'date_range' => $dateRange,
                'generated_at' => now(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get report stats', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
