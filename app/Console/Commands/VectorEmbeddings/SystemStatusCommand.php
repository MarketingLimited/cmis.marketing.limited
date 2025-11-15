<?php

namespace App\Console\Commands\VectorEmbeddings;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SystemStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vector:status
                            {--detailed : عرض تفاصيل موسعة}
                            {--json : عرض بصيغة JSON}
                            {--verify : التحقق من التثبيت}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'عرض حالة نظام Vector Embeddings v2.0';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("📊 حالة نظام Vector Embeddings v2.0");
        $this->newLine();

        try {
            // 1. التحقق من التثبيت
            if ($this->option('verify')) {
                $this->verifyInstallation();
                $this->newLine();
            }

            // 2. حالة Embeddings
            $this->displayEmbeddingStatus();
            $this->newLine();

            // 3. حالة قائمة الانتظار
            $this->displayQueueStatus();
            $this->newLine();

            // 4. تقرير النظام الشامل (detailed)
            if ($this->option('detailed')) {
                $this->displaySystemReport();
                $this->newLine();
            }

            // 5. تحليل النوايا
            if ($this->option('detailed')) {
                $this->displayIntentAnalysis();
                $this->newLine();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ فشل الحصول على الحالة: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function verifyInstallation(): void
    {
        $this->line("🔍 التحقق من التثبيت...");

        $verification = DB::selectOne('SELECT cmis_knowledge.verify_installation() as result');
        $data = json_decode($verification->result, true);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $status = $data['status'] ?? 'unknown';
        if ($status === 'success') {
            $this->info("✅ التثبيت صحيح");
        } else {
            $this->warn("⚠️  مشاكل في التثبيت");
        }

        if (isset($data['functions'])) {
            $this->line("   دوال: " . count($data['functions']) . " موجودة");
        }
    }

    private function displayEmbeddingStatus(): void
    {
        $this->line("📈 حالة تغطية Embeddings:");

        $status = DB::select('SELECT * FROM cmis_knowledge.v_embedding_status LIMIT 10');

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $headers = ['الفئة', 'النطاق', 'السجلات', 'مع Embedding', 'التغطية %', 'التقييم'];
        $rows = [];

        foreach ($status as $item) {
            $rows[] = [
                $item->{'الفئة'} ?? 'N/A',
                $item->{'النطاق'} ?? 'N/A',
                $item->{'إجمالي السجلات'} ?? 0,
                $item->{'السجلات مع Embedding'} ?? 0,
                number_format($item->{'نسبة التغطية %'} ?? 0, 2),
                $item->{'التقييم'} ?? '—'
            ];
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->comment("   لا توجد بيانات");
        }
    }

    private function displayQueueStatus(): void
    {
        $this->line("⏳ حالة قائمة الانتظار:");

        $status = DB::select('SELECT * FROM cmis_knowledge.v_embedding_queue_status');

        if ($this->option('json')) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $headers = ['الحالة', 'العدد', 'متوسط المحاولات', 'متوسط الانتظار', 'الوصف'];
        $rows = [];

        foreach ($status as $item) {
            $rows[] = [
                $item->{'الحالة'} ?? 'N/A',
                $item->{'العدد'} ?? 0,
                number_format($item->{'متوسط المحاولات'} ?? 0, 2),
                number_format($item->{'متوسط وقت الانتظار (دقيقة)'} ?? 0, 2) . ' دقيقة',
                $item->{'الوصف'} ?? '—'
            ];
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->comment("   قائمة الانتظار فارغة");
        }
    }

    private function displaySystemReport(): void
    {
        $this->line("📋 تقرير النظام الشامل:");

        $report = DB::selectOne('SELECT cmis_knowledge.generate_system_report() as report');
        $data = json_decode($report->report, true);

        if ($this->option('json')) {
            $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        if (isset($data['summary'])) {
            foreach ($data['summary'] as $key => $value) {
                $this->line("   {$key}: <fg=cyan>{$value}</>");
            }
        }
    }

    private function displayIntentAnalysis(): void
    {
        $this->line("🎯 تحليل النوايا:");

        $analysis = DB::select('SELECT * FROM cmis_knowledge.v_intent_analysis LIMIT 10');

        if ($this->option('json')) {
            $this->line(json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $headers = ['النية', 'الاستخدامات', 'عمليات البحث', 'متوسط الصلة %', 'التقييم'];
        $rows = [];

        foreach ($analysis as $item) {
            $rows[] = [
                $item->{'النية'} ?? 'N/A',
                $item->{'عدد الاستخدامات'} ?? 0,
                $item->{'عمليات البحث (30 يوم)'} ?? 0,
                number_format($item->{'متوسط الصلة %'} ?? 0, 2),
                $item->{'التقييم'} ?? '—'
            ];
        }

        if (!empty($rows)) {
            $this->table($headers, $rows);
        } else {
            $this->comment("   لا توجد بيانات تحليلية");
        }
    }
}
