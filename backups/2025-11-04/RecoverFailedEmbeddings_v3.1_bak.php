<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecoverFailedEmbeddings extends Command
{
    protected $signature = 'cmis:recover-failed-embeddings 
                            {--log=/httpdocs/storage/logs/laravel.log : Path to the Laravel log file}
                            {--batch=20 : Number of entries to process per batch}';

    protected $description = 'Recover failed topic embeddings by analyzing full log blocks (Block Parsing Mode).';

    private int $successCount = 0;
    private int $failureCount = 0;

    public function handle()
    {
        ini_set('memory_limit', '2048M');

        $logPath = $this->option('log');
        $batchSize = (int) $this->option('batch');

        if (!file_exists($logPath)) {
            $this->error("❌ Log file not found: {$logPath}");
            return Command::FAILURE;
        }

        $this->info("🔍 Scanning log file (Block Parsing Mode): {$logPath}");

        $file = new \SplFileObject($logPath, 'r');
        $batch = [];
        $block = '';

        while (!$file->eof()) {
            $line = $file->fgets();

            if (str_contains($line, '(Connection: pgsql, SQL: update')) {
                $block = $line; // start new block
            } else {
                $block .= $line;
            }

            if (strlen($block) > 20 * 1024 * 1024) {
                $this->recordFailedBlock($block, null, null);
                $block = '';
                continue;
            }

            if (str_contains($line, 'knowledge_id')) {
                $embedding = $this->extractBetween($block, '"topic_embedding" = "', '", "embedding_updated_at"');
                $knowledgeId = $this->extractBetween($block, '"knowledge_id" = ', ')');

                if ($embedding && $knowledgeId) {
                    $this->line("🧹 Extracted block for {$knowledgeId}");
                    $batch[] = [
                        'knowledge_id' => trim($knowledgeId),
                        'embedding' => trim($embedding),
                        'block' => $block
                    ];
                } else {
                    $this->recordFailedBlock($block, $embedding, $knowledgeId);
                }

                $block = '';
            }

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            $this->processBatch($batch);
        }

        $this->info("✅ Completed Block Parsing Mode. Success: {$this->successCount}, Failures: {$this->failureCount}");
        return Command::SUCCESS;
    }

    private function extractBetween(string $text, string $start, string $end): ?string
    {
        $pattern = sprintf('/%s(.*?)%s/s', preg_quote($start, '/'), preg_quote($end, '/'));
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function recordFailedBlock(string $block, ?string $embedding, ?string $knowledgeId)
    {
        $this->failureCount++;
        $summary = "⚠️ Failed block parsing | ID: " . ($knowledgeId ?? 'N/A');

        Log::error("{$summary}");
        file_put_contents('/httpdocs/storage/logs/failed_blocks.log', $summary . "\n" . $block . "\n\n", FILE_APPEND);

        DB::insert(
            'INSERT INTO cmis_dev.dev_logs (event, details, created_at) VALUES ($1, $2::jsonb, NOW())',
            [
                'embedding_recovery_failed',
                json_encode([
                    'knowledge_id' => $knowledgeId,
                    'embedding' => $embedding,
                    'block_snippet' => substr($block, 0, 200)
                ])
            ]
        );
    }

    private function recordSuccessBlock(string $block, string $knowledgeId)
    {
        $summary = "✅ Success block parsed | ID: {$knowledgeId}";
        file_put_contents('/httpdocs/storage/logs/success_blocks.log', $summary . "\n" . $block . "\n\n", FILE_APPEND);

        DB::insert(
            'INSERT INTO cmis_dev.dev_logs (event, details, created_at) VALUES ($1, $2::jsonb, NOW())',
            [
                'embedding_recovery_success',
                json_encode([
                    'knowledge_id' => $knowledgeId,
                    'block_snippet' => substr($block, 0, 200)
                ])
            ]
        );
    }

    private function processBatch(array $batch)
    {
        foreach ($batch as $item) {
            try {
                $exists = DB::selectOne(
                    'SELECT topic_embedding FROM cmis_knowledge.index WHERE knowledge_id = $1',
                    [$item['knowledge_id']]
                );

                if ($exists && ($exists->topic_embedding === null || $exists->topic_embedding === '[]')) {
                    DB::update(
                        'UPDATE cmis_knowledge.index 
                         SET topic_embedding = $1, embedding_updated_at = NOW(), embedding_version = 1, updated_at = NOW() 
                         WHERE knowledge_id = $2',
                        [$item['embedding'], $item['knowledge_id']]
                    );

                    DB::insert(
                        'INSERT INTO cmis_dev.dev_logs (event, details, created_at) VALUES ($1, $2::jsonb, NOW())',
                        [
                            'embedding_recovered',
                            json_encode(['knowledge_id' => $item['knowledge_id']])
                        ]
                    );

                    $this->recordSuccessBlock($item['block'], $item['knowledge_id']);
                    $this->line("✅ Updated embedding for {$item['knowledge_id']}");
                    $this->successCount++;
                } else {
                    $this->line("⚠️ Skipped (exists or not found): {$item['knowledge_id']}");
                }

            } catch (\Exception $e) {
                $this->recordFailedBlock("DB Error for {$item['knowledge_id']}: " . $e->getMessage(), $item['embedding'], $item['knowledge_id']);
            }
        }
    }
}
