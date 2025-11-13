<?php

namespace App\Console\Commands;

use App\Models\Knowledge\EmbeddingsCache;
use App\Models\Knowledge\SemanticSearchResultCache;
use App\Repositories\CMIS\CacheRepository;
use App\Repositories\Knowledge\KnowledgeRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupCacheCommand extends Command
{
    protected CacheRepository $cacheRepo;
    protected KnowledgeRepository $knowledgeRepo;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:cleanup-cache
                            {--days=30 : Number of days to keep}
                            {--sessions : Cleanup expired sessions}
                            {--embeddings : Cleanup old embeddings}
                            {--all : Cleanup everything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تنظيف ذاكرة التخزين المؤقت والجلسات المنتهية والبيانات القديمة';

    public function __construct(CacheRepository $cacheRepo, KnowledgeRepository $knowledgeRepo)
    {
        parent::__construct();
        $this->cacheRepo = $cacheRepo;
        $this->knowledgeRepo = $knowledgeRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cleanupSessions = $this->option('sessions');
        $cleanupEmbeddings = $this->option('embeddings');
        $cleanupAll = $this->option('all');

        $this->info('بدء عملية التنظيف...');

        try {
            // Cleanup expired sessions
            if ($cleanupSessions || $cleanupAll) {
                $this->info('تنظيف الجلسات المنتهية...');
                $result = $this->cacheRepo->cleanupExpiredSessions();
                if ($result) {
                    $this->info('✓ تم تنظيف الجلسات المنتهية بنجاح');
                }
            }

            // Cleanup old cache entries
            if ($cleanupAll || (!$cleanupSessions && !$cleanupEmbeddings)) {
                $this->info("تنظيف مدخلات ذاكرة التخزين المؤقت الأقدم من {$days} يوماً...");

                $result = $this->cacheRepo->cleanupOldCacheEntries();
                if ($result) {
                    $this->info('✓ تم تنظيف ذاكرة التخزين المؤقت بنجاح');
                }

                // Clean up stale embedding cache (using models)
                $embeddingCount = EmbeddingsCache::stale($days)->delete();
                $this->info("✓ تم حذف {$embeddingCount} مدخل تضمينات قديمة");

                // Clean up expired search result cache
                $searchCount = SemanticSearchResultCache::expired()->delete();
                $this->info("✓ تم حذف {$searchCount} نتيجة بحث منتهية");
            }

            // Cleanup old embeddings from knowledge base
            if ($cleanupEmbeddings || $cleanupAll) {
                $this->info('تنظيف التضمينات القديمة من قاعدة المعرفة...');
                $result = $this->knowledgeRepo->cleanupOldEmbeddings();
                if ($result) {
                    $this->info('✓ تم تنظيف التضمينات القديمة بنجاح');
                }
            }

            $this->newLine();
            $this->info('اكتملت عملية التنظيف بنجاح!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Cleanup cache command failed: ' . $e->getMessage());
            $this->error('فشلت عملية التنظيف: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
