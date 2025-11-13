<?php

namespace App\Console\Commands;

use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class ProcessEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:process-embeddings {--batch=10 : Number of items to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending embedding queue items';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingService $embeddingService): int
    {
        $batchSize = (int) $this->option('batch');

        $this->info("Processing up to {$batchSize} embedding queue items...");

        $processed = $embeddingService->processQueue($batchSize);

        if ($processed > 0) {
            $this->info("Successfully processed {$processed} items.");
        } else {
            $this->info("No items to process.");
        }

        return Command::SUCCESS;
    }
}
