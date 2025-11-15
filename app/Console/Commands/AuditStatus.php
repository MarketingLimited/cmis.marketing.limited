<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditStatus extends Command
{
    protected $signature = 'audit:status {--detailed}';

    protected $description = '📊 عرض حالة نظام التدقيق الشاملة';

    public function handle()
    {
        $this->info('📊 حالة نظام التدقيق CMIS');
        $this->line('═══════════════════════════════════════════════════════');
        $this->line('');

        // Display realtime status
        $this->displayRealtimeStatus();

        // Display daily summary
        $this->line('');
        $this->displayDailySummary();

        // Display weekly performance if detailed flag is set
        if ($this->option('detailed')) {
            $this->line('');
            $this->displayWeeklyPerformance();
        }

        // Display comprehensive summary
        $this->line('');
        $this->displayAuditSummary();

        // Check and display alerts
        $this->line('');
        $this->checkAlerts();

        return 0;
    }

    private function displayRealtimeStatus()
    {
        $this->line('🔴 الحالة اللحظية (آخر ساعة):');
        $this->line('─────────────────────────────────');

        try {
            $status = DB::select("SELECT * FROM cmis_audit.realtime_status")[0] ?? null;

            if (!$status) {
                $this->warn('⚠️  لا توجد بيانات');
                return;
            }

            $this->line("  ✅ مهام مكتملة: {$status->completed_tasks}");
            $this->line("  ❌ مهام فاشلة: {$status->recent_failures}");
            $this->line("  🔒 أحداث أمنية: {$status->security_events}");
            $this->line("  🧠 تحديثات معرفية: {$status->knowledge_updates}");
            $this->line("  ⚙️  عمليات النظام: {$status->system_operations}");
            $this->line("  🕐 آخر تحديث: " . ($status->last_update ?? 'لا يوجد'));

        } catch (\Exception $e) {
            $this->error("  ❌ خطأ: " . $e->getMessage());
        }
    }

    private function displayDailySummary()
    {
        $this->line('📅 الملخص اليومي (آخر 24 ساعة):');
        $this->line('─────────────────────────────────');

        try {
            $summary = DB::select("SELECT * FROM cmis_audit.daily_summary")[0] ?? null;

            if (!$summary) {
                $this->warn('⚠️  لا توجد بيانات');
                return;
            }

            $this->line("  📋 إجمالي المهام: {$summary->total_tasks}");
            $this->line("  ✅ مهام مكتملة: {$summary->completed_tasks}");
            $this->line("  ❌ مهام فاشلة: {$summary->failed_tasks}");
            $this->line("  📊 نسبة النجاح: " . ($summary->success_rate ?? 0) . "%");
            $this->line("  🧠 أحداث معرفية: {$summary->knowledge_events}");
            $this->line("  🔒 حوادث أمنية: {$summary->security_incidents}");
            $this->line("  ⚙️  عمليات النظام: {$summary->system_operations}");

        } catch (\Exception $e) {
            $this->error("  ❌ خطأ: " . $e->getMessage());
        }
    }

    private function displayWeeklyPerformance()
    {
        $this->line('📈 الأداء الأسبوعي:');
        $this->line('─────────────────────────────────');

        try {
            $weeks = DB::select("
                SELECT * FROM cmis_audit.weekly_performance
                LIMIT 4
            ");

            if (empty($weeks)) {
                $this->warn('⚠️  لا توجد بيانات');
                return;
            }

            foreach ($weeks as $week) {
                $weekStart = date('Y-m-d', strtotime($week->week_start));
                $this->line("  📅 أسبوع {$weekStart}:");
                $this->line("     المهام: {$week->total_tasks} | الفاشلة: {$week->failed_tasks} | النجاح: " . ($week->success_rate ?? 0) . "%");
                $this->line("     الأمان: {$week->security_alerts} | المعرفة: {$week->new_knowledge}");
                $this->line('');
            }

        } catch (\Exception $e) {
            $this->error("  ❌ خطأ: " . $e->getMessage());
        }
    }

    private function displayAuditSummary()
    {
        $this->line('📊 الملخص الشامل:');
        $this->line('─────────────────────────────────');

        try {
            $summaries = DB::select("SELECT * FROM cmis_audit.audit_summary");

            if (empty($summaries)) {
                $this->warn('⚠️  لا توجد بيانات');
                return;
            }

            $headers = ['الفترة', 'إجمالي', 'مهام', 'معرفة', 'أمان', 'نظام', 'فاعلين'];
            $rows = array_map(fn($s) => [
                $this->translatePeriod($s->period),
                $s->total_events,
                $s->tasks,
                $s->knowledge,
                $s->security,
                $s->system,
                $s->unique_actors
            ], $summaries);

            $this->table($headers, $rows);

        } catch (\Exception $e) {
            $this->error("  ❌ خطأ: " . $e->getMessage());
        }
    }

    private function checkAlerts()
    {
        $this->line('🚨 حالة التنبيهات:');
        $this->line('─────────────────────────────────');

        try {
            $alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

            foreach ($alerts as $alert) {
                $icon = match($alert->severity) {
                    'critical' => '🔴',
                    'warning' => '⚠️ ',
                    'info' => '✅',
                    default => '🔵'
                };

                $this->line("  {$icon} {$alert->message}");
            }

        } catch (\Exception $e) {
            $this->error("  ❌ خطأ: " . $e->getMessage());
        }
    }

    private function translatePeriod($period): string
    {
        return match($period) {
            'last_24_hours' => 'آخر 24 ساعة',
            'last_7_days' => 'آخر 7 أيام',
            'last_30_days' => 'آخر 30 يوم',
            default => $period
        };
    }
}
