<?php

namespace App\Console\Commands\Embeddings;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Jobs\GenerateEmbeddingsJob;

class GenerateEmbeddingsCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'embeddings:generate
                            {--org=* : Specific org IDs}
                            {--type= : Content type filter}
                            {--limit=100 : Limit per org}
                            {--queue : Dispatch as queue job}';

    protected $description = 'Generate vector embeddings using Gemini API';

    public function handle()
    {
        $this->info('🧠 Starting Embeddings Generation');
        $this->newLine();

        $orgIds = $this->option('org') ?: null;
        $type = $this->option('type');
        $limit = $this->option('limit');

        $this->executePerOrg(function ($org) use ($type, $limit) {
            $this->info("  🔍 Processing embeddings for org {$org->org_name}...");

            try {
                if ($this->option('queue')) {
                    // Dispatch job to queue
                    GenerateEmbeddingsJob::dispatch($org->org_id, $limit, $type);
                    $this->info("  ✓ Job dispatched to queue");
                } else {
                    // Run synchronously
                    $job = new GenerateEmbeddingsJob($org->org_id, $limit, $type);
                    $job->handle(app(\App\Services\CMIS\GeminiEmbeddingService::class));
                    $this->info("  ✓ Embeddings generated");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
            }

        }, $orgIds);

        $this->newLine();
        $this->info('✅ Embeddings Generation Completed');

        return Command::SUCCESS;
    }
}
