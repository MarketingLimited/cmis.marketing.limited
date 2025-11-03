<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CMIS\KnowledgeEmbeddingProcessor;

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
            $stats = $this->processor->processBatch($batchSize);
            
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