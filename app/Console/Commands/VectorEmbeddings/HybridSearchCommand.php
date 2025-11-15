<?php

namespace App\Console\Commands\VectorEmbeddings;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HybridSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vector:hybrid-search
                            {query : استعلام البحث}
                            {--vector-query= : استعلام منفصل للـ vector}
                            {--text-weight=0.3 : وزن البحث النصي}
                            {--vector-weight=0.7 : وزن البحث الدلالي}
                            {--limit=10 : عدد النتائج}
                            {--json : عرض النتائج بصيغة JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'البحث الهجين (نصي + vector) في قاعدة المعرفة';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->argument('query');
        $vectorQuery = $this->option('vector-query');
        $textWeight = (float) $this->option('text-weight');
        $vectorWeight = (float) $this->option('vector-weight');
        $limit = (int) $this->option('limit');

        $this->info("🔍 البحث الهجين في قاعدة المعرفة...");
        $this->info("📝 الاستعلام: {$query}");
        $this->line("⚖️  الأوزان: نصي {$textWeight} | vector {$vectorWeight}");

        try {
            $results = DB::select(
                'SELECT * FROM cmis_knowledge.hybrid_search(?, ?, ?, ?, ?)',
                [
                    $query,
                    $vectorQuery,
                    $textWeight,
                    $vectorWeight,
                    $limit
                ]
            );

            if (empty($results)) {
                $this->warn("⚠️  لم يتم العثور على نتائج");
                return Command::SUCCESS;
            }

            if ($this->option('json')) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return Command::SUCCESS;
            }

            $this->newLine();
            $this->info("✅ تم العثور على " . count($results) . " نتيجة:");
            $this->newLine();

            foreach ($results as $index => $result) {
                $this->line(sprintf(
                    "<fg=cyan>[%d]</> <fg=white>%s</> <fg=yellow>(النطاق: %s)</>",
                    $index + 1,
                    $result->topic ?? 'N/A',
                    $result->domain ?? 'N/A'
                ));

                $this->line(sprintf(
                    "    📊 نصي: <fg=green>%.3f</> | Vector: <fg=blue>%.3f</> | مجموع: <fg=magenta>%.3f</>",
                    $result->text_score ?? 0,
                    $result->vector_score ?? 0,
                    $result->combined_score ?? 0
                ));

                if (isset($result->content) && strlen($result->content) > 0) {
                    $preview = mb_substr($result->content, 0, 120);
                    $this->line("    💬 " . $preview . "...");
                }

                $this->newLine();
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ فشل البحث: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
