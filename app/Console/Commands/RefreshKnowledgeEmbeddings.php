<?php

namespace App\Console\Commands;

use App\Repositories\Knowledge\EmbeddingRepository;
use App\Repositories\Knowledge\KnowledgeRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshKnowledgeEmbeddings extends Command
{
    protected EmbeddingRepository $embeddingRepo;
    protected KnowledgeRepository $knowledgeRepo;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:refresh-embeddings
                            {--domain= : Specific domain to refresh}
                            {--category= : Specific category to refresh}
                            {--batch-size=100 : Number of items to process in batch}
                            {--force : Force refresh even if embeddings exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تحديث التضمينات المتجهة لعناصر قاعدة المعرفة';

    public function __construct(EmbeddingRepository $embeddingRepo, KnowledgeRepository $knowledgeRepo)
    {
        parent::__construct();
        $this->embeddingRepo = $embeddingRepo;
        $this->knowledgeRepo = $knowledgeRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 بدء تحديث التضمينات المتجهة...');

        $category = $this->option('category');
        $batchSize = (int) $this->option('batch-size');

        try {
            // Use batch update from repository
            $this->info("📦 معالجة دفعة من {$batchSize} عنصر...");

            if ($category) {
                $this->info("🏷️  فلترة حسب الفئة: {$category}");
            }

            $result = $this->embeddingRepo->batchUpdateEmbeddings($batchSize, $category);

            if ($result && isset($result->processed_count)) {
                $this->newLine();
                $this->info("✅ تمت معالجة: {$result->processed_count} عنصر بنجاح");

                if (isset($result->updated_count)) {
                    $this->info("✓ تم تحديث: {$result->updated_count} تضمين");
                }

                if (isset($result->failed_count) && $result->failed_count > 0) {
                    $this->warn("⚠️  فشل: {$result->failed_count} عنصر");
                }
            } else {
                $this->warn('⚠️  لم يتم العثور على عناصر للمعالجة');
            }

            $this->newLine();
            $this->info('✨ اكتمل تحديث التضمينات!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ خطأ أثناء تحديث التضمينات: ' . $e->getMessage());
            Log::error('Embeddings refresh failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
