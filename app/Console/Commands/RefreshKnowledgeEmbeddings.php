<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshKnowledgeEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:refresh-embeddings
                            {--domain= : Specific domain to refresh}
                            {--category= : Specific category to refresh}
                            {--limit= : Limit number of items to process}
                            {--force : Force refresh even if embeddings exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh vector embeddings for knowledge base items using database functions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting knowledge embeddings refresh...');

        $domain = $this->option('domain');
        $category = $this->option('category');
        $limit = $this->option('limit') ?? 100;
        $force = $this->option('force');

        try {
            // Build query to get knowledge items needing refresh
            $query = DB::table('cmis_knowledge.knowledge_base')
                ->select('knowledge_id', 'domain', 'category', 'topic', 'content');

            if ($domain) {
                $query->where('domain', $domain);
                $this->info("📂 Filtering by domain: {$domain}");
            }

            if ($category) {
                $query->where('category', $category);
                $this->info("🏷️  Filtering by category: {$category}");
            }

            if (!$force) {
                $query->whereNull('embedding');
                $this->info('⚡ Only processing items without embeddings');
            }

            $items = $query->limit($limit)->get();

            $this->info("📊 Found {$items->count()} items to process");

            if ($items->isEmpty()) {
                $this->warn('⚠️  No items found to process');
                return Command::SUCCESS;
            }

            $bar = $this->output->createProgressBar($items->count());
            $bar->start();

            $successCount = 0;
            $failCount = 0;

            foreach ($items as $item) {
                try {
                    // Use database function to refresh embedding
                    $result = DB::select("
                        SELECT cmis_knowledge.refresh_knowledge_embedding(?) as success
                    ", [$item->knowledge_id]);

                    if ($result[0]->success ?? false) {
                        $successCount++;
                    } else {
                        $failCount++;
                        Log::warning("Failed to refresh embedding for knowledge_id: {$item->knowledge_id}");
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Error refreshing embedding for knowledge_id {$item->knowledge_id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            $this->info("✅ Successfully refreshed: {$successCount}");
            if ($failCount > 0) {
                $this->error("❌ Failed to refresh: {$failCount}");
            }

            $this->info('✨ Embeddings refresh completed!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during embeddings refresh: ' . $e->getMessage());
            Log::error('Embeddings refresh failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
