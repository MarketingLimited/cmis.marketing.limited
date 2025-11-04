<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CMIS\KnowledgeEmbeddingProcessor;
use Illuminate\Support\Facades\DB;

class ProcessEmbeddings extends Command
{
    protected $signature = 'cmis:process-embeddings 
                            {--batch-size=100 : The batch size for processing}
                            {--continuous : Run continuously}';
    
    protected $description = 'Process pending CMIS knowledge embeddings';
    
    private KnowledgeEmbeddingProcessor $processor;
    
    public function __construct(KnowledgeEmbeddingProcessor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }
    
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $continuous = $this->option('continuous');
        
        $this->info('Starting CMIS Embedding Processing...');
        $this->info("Batch Size: {$batchSize}");

        do {
            $items = $this->processor->getPendingItems($batchSize);

            foreach ($items as $item) {
                if (str_starts_with($item->error_message ?? '', '❌ Halted by orchestrator')) {
                    $this->warn("Skipping halted record: {$item->id}");
                    continue;
                }

                if ($item->attempts >= 3) {
                    $item->error_message = '❌ Exceeded max attempts';
                    $item->status = 'failed';
                    $item->save();
                    $this->warn("Max attempts reached: {$item->id}");
                    continue;
                }

                try {
                    $this->processor->processItem($item);
                } catch (\Throwable $e) {
                    DB::table('cmis_dev.dev_logs')->insert([
                        'created_at' => now(),
                        'event' => 'embedding_failed',
                        'details' => json_encode([
                            'id' => $item->id,
                            'knowledge_id' => $item->knowledge_id,
                            'reason' => $e->getMessage(),
                        ]),
                    ]);

                    $item->error_message = $e->getMessage();
                    $item->status = 'failed';
                    $item->save();
                }
            }

            $stats = [
                'total_processed' => count($items),
                'successful' => count(array_filter($items, fn($i) => $i->status === 'completed')),
                'failed' => count(array_filter($items, fn($i) => $i->status === 'failed')),
            ];

            $this->displayStats($stats);

            if ($continuous && $stats['total_processed'] > 0) {
                $this->info('Waiting before next batch...');
                sleep(config('cmis-embeddings.processing.queue_check_interval_seconds', 60));
            }

        } while ($continuous);

        $this->info('Processing completed.');

        return Command::SUCCESS;
    }

    private function displayStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', $stats['total_processed']],
                ['Successful', $stats['successful']],
                ['Failed', $stats['failed']],
                ['Success Rate', $stats['total_processed'] > 0 
                    ? round(($stats['successful'] / $stats['total_processed']) * 100, 2) . '%' 
                    : 'N/A'],
            ]
        );
    }
}