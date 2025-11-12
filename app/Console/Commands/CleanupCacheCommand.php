<?php

namespace App\Console\Commands;

use App\Models\Knowledge\EmbeddingsCache;
use App\Models\Knowledge\SemanticSearchResultCache;
use Illuminate\Console\Command;

class CleanupCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:cleanup-cache {--days=30 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale cache entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up cache entries older than {$days} days...");

        // Clean up stale embedding cache
        $embeddingCount = EmbeddingsCache::stale($days)->delete();
        $this->info("Deleted {$embeddingCount} stale embedding cache entries.");

        // Clean up expired search result cache
        $searchCount = SemanticSearchResultCache::expired()->delete();
        $this->info("Deleted {$searchCount} expired search result cache entries.");

        $this->info('Cache cleanup completed.');

        return Command::SUCCESS;
    }
}
