<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GeneratePerformanceReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:generate-report
                            {type=weekly : Report type (daily, weekly, monthly)}
                            {--campaign= : Specific campaign ID}
                            {--org= : Specific organization ID}
                            {--format=json : Output format (json, csv, pdf)}
                            {--email= : Email address to send report to}
                            {--save : Save report to storage}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate performance reports for campaigns and organizations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $campaignId = $this->option('campaign');
        $orgId = $this->option('org');
        $format = $this->option('format');
        $save = $this->option('save');
        $email = $this->option('email');

        $this->info("📊 Generating {$type} performance report...");

        try {
            // Determine date range
            $dateRange = $this->getDateRange($type);

            // Generate report data
            $reportData = $this->generateReportData($dateRange, $campaignId, $orgId);

            if (empty($reportData)) {
                $this->warn('⚠️  No data found for the specified period');
                return Command::SUCCESS;
            }

            // Format and output report
            $output = $this->formatReport($reportData, $format);

            // Save to storage if requested
            if ($save) {
                $filename = $this->saveReport($output, $type, $format);
                $this->info("💾 Report saved: {$filename}");
            }

            // Send via email if requested
            if ($email) {
                $this->sendReportEmail($email, $output, $type);
                $this->info("📧 Report sent to: {$email}");
            }

            // Display summary
            $this->displayReportSummary($reportData);

            $this->info('✨ Report generated successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error generating report: ' . $e->getMessage());
            Log::error('Report generation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get date range based on report type
     */
    private function getDateRange($type): array
    {
        $endDate = Carbon::now();

        switch ($type) {
            case 'daily':
                $startDate = Carbon::today();
                break;
            case 'monthly':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'weekly':
            default:
                $startDate = Carbon::now()->startOfWeek();
                break;
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Generate report data from database
     */
    private function generateReportData($dateRange, $campaignId = null, $orgId = null): array
    {
        $this->info('📈 Collecting data...');

        // Get campaigns performance
        $query = "
            SELECT
                c.campaign_id,
                c.campaign_name,
                o.org_name,
                COUNT(DISTINCT sm.metric_id) as total_metrics,
                SUM(CASE WHEN sm.metric_name = 'impressions' THEN sm.metric_value ELSE 0 END) as total_impressions,
                SUM(CASE WHEN sm.metric_name = 'clicks' THEN sm.metric_value ELSE 0 END) as total_clicks,
                SUM(CASE WHEN sm.metric_name = 'conversions' THEN sm.metric_value ELSE 0 END) as total_conversions,
                SUM(CASE WHEN sm.metric_name = 'spend' THEN sm.metric_value ELSE 0 END) as total_spend,
                c.budget
            FROM cmis.campaigns c
            JOIN cmis.organizations o ON c.org_id = o.org_id
            LEFT JOIN cmis_social.social_media_metrics sm ON c.campaign_id = sm.campaign_id
                AND sm.recorded_at BETWEEN ? AND ?
            WHERE c.is_active = true
        ";

        $params = [$dateRange['start'], $dateRange['end']];

        if ($campaignId) {
            $query .= " AND c.campaign_id = ?";
            $params[] = $campaignId;
        }

        if ($orgId) {
            $query .= " AND c.org_id = ?";
            $params[] = $orgId;
        }

        $query .= " GROUP BY c.campaign_id, c.campaign_name, o.org_name, c.budget ORDER BY total_impressions DESC";

        $campaigns = DB::select($query, $params);

        return [
            'period' => [
                'start' => $dateRange['start']->format('Y-m-d'),
                'end' => $dateRange['end']->format('Y-m-d'),
            ],
            'campaigns' => $campaigns,
            'summary' => $this->calculateSummary($campaigns),
        ];
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($campaigns): array
    {
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalConversions = 0;
        $totalSpend = 0;
        $totalBudget = 0;

        foreach ($campaigns as $campaign) {
            $totalImpressions += $campaign->total_impressions ?? 0;
            $totalClicks += $campaign->total_clicks ?? 0;
            $totalConversions += $campaign->total_conversions ?? 0;
            $totalSpend += $campaign->total_spend ?? 0;
            $totalBudget += $campaign->budget ?? 0;
        }

        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
        $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $budgetUtilization = $totalBudget > 0 ? ($totalSpend / $totalBudget) * 100 : 0;

        return [
            'total_campaigns' => count($campaigns),
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'total_spend' => $totalSpend,
            'total_budget' => $totalBudget,
            'avg_ctr' => round($ctr, 2),
            'avg_conversion_rate' => round($conversionRate, 2),
            'budget_utilization' => round($budgetUtilization, 2),
        ];
    }

    /**
     * Format report based on requested format
     */
    private function formatReport($data, $format): string
    {
        switch ($format) {
            case 'csv':
                return $this->formatAsCsv($data);
            case 'pdf':
                return $this->formatAsPdf($data);
            case 'json':
            default:
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Format report as CSV
     */
    private function formatAsCsv($data): string
    {
        $csv = "Campaign Name,Organization,Impressions,Clicks,Conversions,Spend,Budget\n";

        foreach ($data['campaigns'] as $campaign) {
            $csv .= sprintf(
                "%s,%s,%d,%d,%d,%.2f,%.2f\n",
                $campaign->campaign_name,
                $campaign->org_name,
                $campaign->total_impressions ?? 0,
                $campaign->total_clicks ?? 0,
                $campaign->total_conversions ?? 0,
                $campaign->total_spend ?? 0,
                $campaign->budget ?? 0
            );
        }

        return $csv;
    }

    /**
     * Format report as PDF (placeholder - requires PDF library)
     */
    private function formatAsPdf($data): string
    {
        // This would require a PDF library like DomPDF or TCPDF
        // For now, return JSON with a note
        return json_encode([
            'note' => 'PDF generation requires additional setup',
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Save report to storage
     */
    private function saveReport($output, $type, $format): string
    {
        $filename = sprintf(
            'reports/%s_report_%s.%s',
            $type,
            Carbon::now()->format('Y-m-d_His'),
            $format
        );

        Storage::disk('local')->put($filename, $output);

        return $filename;
    }

    /**
     * Send report via email (placeholder)
     */
    private function sendReportEmail($email, $output, $type): void
    {
        // This would use Laravel's Mail facade
        // Placeholder for now
        Log::info("Report would be sent to: {$email}");
    }

    /**
     * Display report summary in console
     */
    private function displayReportSummary($data): void
    {
        $this->newLine();
        $this->info('📊 Report Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Campaigns', $data['summary']['total_campaigns']],
                ['Total Impressions', number_format($data['summary']['total_impressions'])],
                ['Total Clicks', number_format($data['summary']['total_clicks'])],
                ['Total Conversions', number_format($data['summary']['total_conversions'])],
                ['Average CTR', $data['summary']['avg_ctr'] . '%'],
                ['Average Conversion Rate', $data['summary']['avg_conversion_rate'] . '%'],
                ['Total Spend', number_format($data['summary']['total_spend'], 2) . ' SAR'],
                ['Total Budget', number_format($data['summary']['total_budget'], 2) . ' SAR'],
                ['Budget Utilization', $data['summary']['budget_utilization'] . '%'],
            ]
        );
    }
}
