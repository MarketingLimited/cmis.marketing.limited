<?php

namespace App\Console\Commands\VectorEmbeddings;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessEmbeddingQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vector:process-queue
                            {--batch=100 : عدد العناصر للمعالجة}
                            {--continuous : معالجة مستمرة}
                            {--delay=60 : التأخير بين الدفعات (بالثواني)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'معالجة قائمة انتظار Embeddings (v2.0)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $continuous = $this->option('continuous');
        $delay = (int) $this->option('delay');

        $this->info("🚀 بدء معالجة قائمة انتظار Embeddings...");
        $this->info("📦 حجم الدفعة: {$batchSize}");

        if ($continuous) {
            $this->info("♻️  وضع المعالجة المستمرة (Ctrl+C للإيقاف)");
        }

        do {
            try {
                $result = DB::selectOne(
                    'SELECT cmis_knowledge.process_embedding_queue(?) as result',
                    [$batchSize]
                );

                $data = json_decode($result->result, true);

                if ($data['processed'] > 0) {
                    $this->line('');
                    $this->info("✅ تمت المعالجة: {$data['processed']} عنصر");
                    $this->info("   ✓ ناجح: {$data['successful']}");

                    if ($data['failed'] > 0) {
                        $this->warn("   ✗ فشل: {$data['failed']}");
                    }

                    $this->info("   ⏱️  وقت التنفيذ: " . round($data['execution_time_ms'], 2) . " ms");
                } else {
                    $this->comment("ℹ️  لا توجد عناصر في قائمة الانتظار");

                    if (!$continuous) {
                        break;
                    }
                }

                if ($continuous && $data['processed'] > 0) {
                    $this->comment("⏳ انتظار {$delay} ثانية...");
                    sleep($delay);
                }

            } catch (\Exception $e) {
                $this->error("❌ خطأ في المعالجة: " . $e->getMessage());

                if (!$continuous) {
                    return Command::FAILURE;
                }

                sleep($delay);
            }

        } while ($continuous);

        $this->newLine();
        $this->info("🎉 اكتملت المعالجة بنجاح!");

        return Command::SUCCESS;
    }
}
