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
     * Export report to Excel (CSV format for compatibility)
     *
     * Uses CSV format which can be opened in Excel, Google Sheets, and other
     * spreadsheet applications. Includes UTF-8 BOM for proper character encoding.
     */
    public function exportToExcel(array $reportData, string $filename = null): string
    {
        try {
            $filename = $filename ?? 'report_' . now()->format('Y-m-d_His') . '.csv';
            $path = storage_path('app/reports/' . $filename);

            // Ensure reports directory exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $file = fopen($path, 'w');

            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Write report data
            if (isset($reportData['campaign'])) {
                // Campaign report format
                fputcsv($file, ['Campaign Report']);
                fputcsv($file, ['Generated At', now()->format('Y-m-d H:i:s')]);
                fputcsv($file, []);

                // Campaign details
                $campaign = $reportData['campaign'];
                fputcsv($file, ['Campaign Name', $campaign->campaign_name ?? 'N/A']);
                fputcsv($file, ['Status', $campaign->status ?? 'N/A']);
                fputcsv($file, ['Budget', $campaign->budget ?? 0]);
                fputcsv($file, []);

                // Metrics
                if (isset($reportData['metrics']) && count($reportData['metrics']) > 0) {
                    fputcsv($file, ['Metrics']);
                    fputcsv($file, ['Date', 'Metric Type', 'Value', 'Confidence']);
                    foreach ($reportData['metrics'] as $metric) {
                        fputcsv($file, [
                            $metric->collected_at ?? '',
                            $metric->metric_type ?? '',
                            $metric->metric_value ?? 0,
                            $metric->confidence_level ?? 0
                        ]);
                    }
                }

                // Summary
                if (isset($reportData['summary'])) {
                    fputcsv($file, []);
                    fputcsv($file, ['Summary']);
                    foreach ($reportData['summary'] as $key => $value) {
                        if (!is_array($value)) {
                            fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value]);
                        }
                    }
                }
            } elseif (isset($reportData['stats'])) {
                // Organization report format
                fputcsv($file, ['Organization Report']);
                fputcsv($file, ['Generated At', now()->format('Y-m-d H:i:s')]);
                fputcsv($file, []);

                // Stats
                fputcsv($file, ['Statistics']);
                foreach ($reportData['stats'] as $key => $value) {
                    fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value]);
                }

                // Campaigns
                if (isset($reportData['campaigns']) && count($reportData['campaigns']) > 0) {
                    fputcsv($file, []);
                    fputcsv($file, ['Campaigns']);
                    fputcsv($file, ['Name', 'Status', 'Created At']);
                    foreach ($reportData['campaigns'] as $campaign) {
                        fputcsv($file, [
                            $campaign->campaign_name ?? 'N/A',
                            $campaign->status ?? 'N/A',
                            $campaign->created_at ?? ''
                        ]);
                    }
                }
            } else {
                // Generic data export
                fputcsv($file, ['Report']);
                fputcsv($file, ['Generated At', now()->format('Y-m-d H:i:s')]);
                fputcsv($file, []);

                foreach ($reportData as $key => $value) {
                    if (is_array($value) || $value instanceof \Illuminate\Support\Collection) {
                        fputcsv($file, [ucfirst(str_replace('_', ' ', $key))]);
                        $items = is_array($value) ? $value : $value->toArray();
                        if (count($items) > 0) {
                            $first = reset($items);
                            if (is_object($first)) {
                                $first = (array) $first;
                            }
                            if (is_array($first)) {
                                fputcsv($file, array_keys($first));
                                foreach ($items as $item) {
                                    $item = is_object($item) ? (array) $item : $item;
                                    fputcsv($file, array_values($item));
                                }
                            }
                        }
                        fputcsv($file, []);
                    } elseif (!is_object($value)) {
                        fputcsv($file, [ucfirst(str_replace('_', ' ', $key)), $value]);
                    }
                }
            }

            fclose($file);

            Log::info('Report exported to Excel/CSV', ['filename' => $filename, 'path' => $path]);

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
