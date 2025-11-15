<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditReportGenerate extends Command
{
    protected $signature = 'audit:report {period=daily_summary} {--path=/tmp}';

    protected $description = '📊 توليد وتصدير تقارير التدقيق والأداء';

    public function handle()
    {
        $period = $this->argument('period');
        $path = $this->option('path');

        $this->info("📊 جاري توليد تقرير: {$period}");

        try {
            // Check if view exists
            $viewExists = DB::select("
                SELECT 1
                FROM information_schema.views
                WHERE table_schema = 'cmis_audit'
                AND table_name = ?
            ", [$period]);

            if (empty($viewExists)) {
                $this->error("❌ التقرير '{$period}' غير موجود.");
                $this->line("\nالتقارير المتاحة:");
                $this->line("  - daily_summary (التقرير اليومي)");
                $this->line("  - weekly_performance (الأداء الأسبوعي)");
                $this->line("  - realtime_status (الحالة اللحظية)");
                $this->line("  - audit_summary (ملخص شامل)");
                return 1;
            }

            // Display report preview
            $this->line("\n📋 معاينة التقرير:");
            $this->displayReport($period);

            // Export if requested
            if ($this->confirm("\nهل تريد تصدير التقرير إلى CSV؟", true)) {
                $result = DB::select("
                    SELECT * FROM cmis_audit.export_audit_report(?, ?)
                ", [$period, $path]);

                if ($result[0]->success) {
                    $this->info("✅ تم تصدير التقرير بنجاح!");
                    $this->line("📁 المسار: {$result[0]->file_path}");
                    $this->line("📊 عدد السجلات: {$result[0]->row_count}");
                } else {
                    $this->error("❌ فشل التصدير: {$result[0]->message}");
                    return 1;
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ خطأ: " . $e->getMessage());
            return 1;
        }
    }

    private function displayReport($period)
    {
        $data = DB::select("SELECT * FROM cmis_audit.{$period}");

        if (empty($data)) {
            $this->warn("⚠️  لا توجد بيانات في التقرير");
            return;
        }

        // Convert to array for table display
        $headers = array_keys((array) $data[0]);
        $rows = array_map(fn($row) => array_values((array) $row), $data);

        $this->table($headers, $rows);
    }
}
