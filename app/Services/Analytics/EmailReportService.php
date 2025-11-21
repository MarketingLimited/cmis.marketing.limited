<?php

namespace App\Services\Analytics;

use App\Models\Analytics\ReportExecutionLog;
use App\Models\Analytics\ScheduledReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Email Report Service (Phase 12)
 *
 * Handles email delivery of generated reports
 *
 * Features:
 * - Email report delivery with attachments
 * - Batch sending to multiple recipients
 * - Retry logic for failed deliveries
 * - Delivery tracking and logging
 * - Template-based email formatting
 */
class EmailReportService
{
    protected ReportGeneratorService $reportGenerator;

    public function __construct(ReportGeneratorService $reportGenerator)
    {
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Send scheduled report via email
     *
     * @param ScheduledReport $schedule
     * @return ReportExecutionLog
     */
    public function sendScheduledReport(ScheduledReport $schedule): ReportExecutionLog
    {
        $startTime = microtime(true);

        try {
            // Generate the report
            $reportData = $this->generateReport($schedule);

            // Create execution log
            $log = ReportExecutionLog::create([
                'schedule_id' => $schedule->schedule_id,
                'org_id' => $schedule->org_id,
                'executed_at' => now(),
                'status' => 'success',
                'file_path' => $reportData['file_path'] ?? null,
                'file_url' => $reportData['file_url'] ?? null,
                'file_size' => $reportData['file_size'] ?? null,
                'recipients_count' => count($schedule->recipients)
            ]);

            // Send emails to all recipients
            $sent = 0;
            $failed = 0;

            foreach ($schedule->recipients as $recipient) {
                try {
                    $this->sendReportEmail($recipient, $schedule, $reportData);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to send report email to {$recipient}", [
                        'schedule_id' => $schedule->schedule_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Update log with email results
            $log->update([
                'emails_sent' => $sent,
                'emails_failed' => $failed,
                'status' => $failed === 0 ? 'success' : ($sent > 0 ? 'partial' : 'failed'),
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000)
            ]);

            // Mark schedule as executed
            $schedule->markAsExecuted();

            return $log;
        } catch (\Exception $e) {
            // Create failed execution log
            $log = ReportExecutionLog::create([
                'schedule_id' => $schedule->schedule_id,
                'org_id' => $schedule->org_id,
                'executed_at' => now(),
                'status' => 'failed',
                'recipients_count' => count($schedule->recipients),
                'emails_sent' => 0,
                'emails_failed' => count($schedule->recipients),
                'error_message' => $e->getMessage(),
                'execution_time_ms' => (int) ((microtime(true) - $startTime) * 1000)
            ]);

            Log::error('Failed to generate scheduled report', [
                'schedule_id' => $schedule->schedule_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $log;
        }
    }

    /**
     * Generate report based on schedule configuration
     *
     * @param ScheduledReport $schedule
     * @return array
     */
    protected function generateReport(ScheduledReport $schedule): array
    {
        $config = array_merge($schedule->config, [
            'format' => $schedule->format
        ]);

        switch ($schedule->report_type) {
            case 'campaign':
                if (!isset($config['campaign_id'])) {
                    throw new \RuntimeException('Campaign ID required for campaign report');
                }
                return $this->reportGenerator->generateCampaignReport(
                    $config['campaign_id'],
                    $config
                );

            case 'organization':
                return $this->reportGenerator->generateOrganizationReport(
                    $schedule->org_id,
                    $config
                );

            case 'comparison':
                if (!isset($config['campaign_ids']) || count($config['campaign_ids']) < 2) {
                    throw new \RuntimeException('At least 2 campaign IDs required for comparison report');
                }
                return $this->reportGenerator->generateComparisonReport(
                    $config['campaign_ids'],
                    $config
                );

            default:
                throw new \RuntimeException("Unsupported report type: {$schedule->report_type}");
        }
    }

    /**
     * Send report email to recipient
     *
     * @param string $recipient
     * @param ScheduledReport $schedule
     * @param array $reportData
     * @return void
     */
    protected function sendReportEmail(string $recipient, ScheduledReport $schedule, array $reportData): void
    {
        $subject = $this->getEmailSubject($schedule);
        $filePath = $reportData['file_path'] ?? null;

        Mail::send('emails.scheduled_report', [
            'scheduleName' => $schedule->name,
            'reportType' => $schedule->report_type,
            'frequency' => $schedule->frequency,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'fileUrl' => $reportData['file_url'] ?? null,
            'expiresAt' => $reportData['expires_at'] ?? null
        ], function ($message) use ($recipient, $subject, $filePath) {
            $message->to($recipient)
                ->subject($subject);

            // Attach report file if available
            if ($filePath && Storage::exists($filePath)) {
                $message->attach(Storage::path($filePath));
            }
        });
    }

    /**
     * Generate email subject line
     *
     * @param ScheduledReport $schedule
     * @return string
     */
    protected function getEmailSubject(ScheduledReport $schedule): string
    {
        $frequency = ucfirst($schedule->frequency);
        $reportType = ucfirst(str_replace('_', ' ', $schedule->report_type));

        return "{$frequency} {$reportType} Report - {$schedule->name}";
    }

    /**
     * Send one-time report via email
     *
     * @param string $orgId
     * @param string $reportType
     * @param array $config
     * @param array $recipients
     * @return array
     */
    public function sendOneTimeReport(string $orgId, string $reportType, array $config, array $recipients): array
    {
        try {
            // Generate report
            $reportData = match ($reportType) {
                'campaign' => $this->reportGenerator->generateCampaignReport(
                    $config['campaign_id'],
                    $config
                ),
                'organization' => $this->reportGenerator->generateOrganizationReport(
                    $orgId,
                    $config
                ),
                'comparison' => $this->reportGenerator->generateComparisonReport(
                    $config['campaign_ids'],
                    $config
                ),
                default => throw new \RuntimeException("Unsupported report type: {$reportType}")
            };

            // Send to recipients
            $sent = 0;
            $failed = 0;

            foreach ($recipients as $recipient) {
                try {
                    $this->sendOneTimeReportEmail($recipient, $reportType, $reportData);
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to send one-time report to {$recipient}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return [
                'success' => true,
                'emails_sent' => $sent,
                'emails_failed' => $failed,
                'report_data' => $reportData
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send one-time report', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'emails_sent' => 0,
                'emails_failed' => count($recipients)
            ];
        }
    }

    /**
     * Send one-time report email
     *
     * @param string $recipient
     * @param string $reportType
     * @param array $reportData
     * @return void
     */
    protected function sendOneTimeReportEmail(string $recipient, string $reportType, array $reportData): void
    {
        $subject = "CMIS Analytics Report - " . ucfirst(str_replace('_', ' ', $reportType));
        $filePath = $reportData['file_path'] ?? null;

        Mail::send('emails.one_time_report', [
            'reportType' => $reportType,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'fileUrl' => $reportData['file_url'] ?? null,
            'expiresAt' => $reportData['expires_at'] ?? null
        ], function ($message) use ($recipient, $subject, $filePath) {
            $message->to($recipient)
                ->subject($subject);

            if ($filePath && Storage::exists($filePath)) {
                $message->attach(Storage::path($filePath));
            }
        });
    }

    /**
     * Resend failed report delivery
     *
     * @param ReportExecutionLog $log
     * @param array $recipients Recipients to retry
     * @return array
     */
    public function resendReport(ReportExecutionLog $log, array $recipients = []): array
    {
        $schedule = $log->scheduledReport;

        if (!$schedule) {
            return ['success' => false, 'error' => 'Schedule not found'];
        }

        // Use failed recipients if not specified
        if (empty($recipients) && $log->emails_failed > 0) {
            $recipients = $schedule->recipients;
        }

        $reportData = [
            'file_path' => $log->file_path,
            'file_url' => $log->file_url,
            'expires_at' => now()->addDays(7)->toIso8601String()
        ];

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $this->sendReportEmail($recipient, $schedule, $reportData);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        // Update log
        $log->update([
            'emails_sent' => $log->emails_sent + $sent,
            'emails_failed' => $log->emails_failed - $sent
        ]);

        return [
            'success' => true,
            'emails_sent' => $sent,
            'emails_failed' => $failed
        ];
    }
}
