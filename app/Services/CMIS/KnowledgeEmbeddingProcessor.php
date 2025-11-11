<?php

namespace App\Services\CMIS;

use App\Models\CMIS\KnowledgeItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class KnowledgeEmbeddingProcessor
{
    private GeminiEmbeddingService $embeddingService;
    private array $processingStats;
    
    public function __construct(GeminiEmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
        $this->resetStats();
    }
    
    /**
     * Process a batch of pending items
     */
    public function processBatch(int $batchSize = 100): array
    {
        $this->resetStats();
        $this->processingStats['start_time'] = now();
        
        $items = $this->getPendingItems($batchSize);
        Log::info("Starting processing of {$items->count()} items");
        
        foreach ($items as $item) {
            $success = $this->processItem($item);
            
            $this->processingStats['total_processed']++;
            if ($success) {
                $this->processingStats['successful']++;
            } else {
                $this->processingStats['failed']++;
            }
        }
        
        $this->processingStats['end_time'] = now();
        $this->logProcessingStats();
        
        return $this->processingStats;
    }
    
    /**
     * Get pending items for processing
     */
    public function getPendingItems(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        return KnowledgeItem::pendingEmbeddings()
            ->limit($limit)
            ->get();
    }
    
    /**
     * Process single knowledge item
     */
    public function processItem(KnowledgeItem $item): bool
    {
        try {
            $content = $item->getContent();
            
            if (!$content) {
                Log::warning("No content found for knowledge_id: {$item->knowledge_id}");
                return false;
            }
            
            // Generate embeddings
            $contentEmbedding = $this->embeddingService->generateEmbedding($content);
            
            // Handle topic embedding if exists
            $topicEmbedding = null;
            if ($item->topic) {
                $topicEmbedding = $this->embeddingService->generateEmbedding($item->topic);
            }
            
            // Handle keywords embedding if exists
            $keywordsEmbedding = null;
            if ($item->keywords && is_array($item->keywords)) {
                $keywordsText = implode(' ', $item->keywords);
                $keywordsEmbedding = $this->embeddingService->generateEmbedding($keywordsText);
            }
            
            // Process chunks for long content
            $chunksEmbeddings = null;
            if (strlen($content) > 2000) {
                $chunks = $this->splitIntoChunks($content, 1000);
                $chunksEmbeddings = $this->embeddingService->generateBatchEmbeddings($chunks);
            }
            
            // Update database
            $this->updateDatabase($item, $contentEmbedding, $topicEmbedding, 
                                 $keywordsEmbedding, $chunksEmbeddings);
            
            Log::info("Successfully processed {$item->knowledge_id}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Error processing {$item->knowledge_id}: " . $e->getMessage());
            $this->logError($item->knowledge_id, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process specific knowledge item by ID
     */
    public function processSpecificKnowledge(string $knowledgeId): bool
    {
        $item = KnowledgeItem::find($knowledgeId);
        
        if (!$item) {
            Log::error("Knowledge ID {$knowledgeId} not found");
            return false;
        }
        
        return $this->processItem($item);
    }
    
    /**
     * Split text into chunks
     */
    private function splitIntoChunks(string $text, int $chunkSize = 1000): array
    {
        $words = explode(' ', $text);
        $chunks = [];
        
        for ($i = 0; $i < count($words); $i += $chunkSize) {
            $chunk = implode(' ', array_slice($words, $i, $chunkSize));
            $chunks[] = $chunk;
        }
        
        return $chunks;
    }
    
    /**
     * Update database with embeddings
     */
    private function updateDatabase(
        KnowledgeItem $item,
        array $contentEmbedding,
        ?array $topicEmbedding,
        ?array $keywordsEmbedding,
        ?array $chunksEmbeddings
    ): void {
        DB::transaction(function () use ($item, $contentEmbedding, $topicEmbedding, 
                                        $keywordsEmbedding, $chunksEmbeddings) {
            
            // Calculate semantic fingerprint
            $semanticFingerprint = null;
            if ($topicEmbedding && $keywordsEmbedding) {
                $semanticFingerprint = $this->calculateSemanticFingerprint(
                    $topicEmbedding, 
                    $keywordsEmbedding
                );
            } elseif ($topicEmbedding) {
                $semanticFingerprint = $topicEmbedding;
            } elseif ($keywordsEmbedding) {
                $semanticFingerprint = $keywordsEmbedding;
            }
            
            // Update index table
            $item->topic_embedding = $this->formatEmbeddingForPostgres($topicEmbedding);
            $item->keywords_embedding = $this->formatEmbeddingForPostgres($keywordsEmbedding);
            $item->semantic_fingerprint = $this->formatEmbeddingForPostgres($semanticFingerprint);
            $item->embedding_updated_at = now();
            $item->embedding_version = ($item->embedding_version ?? 0) + 1;
            $item->save();
            
            // Update content table
            $this->updateContentTable($item, $contentEmbedding, $chunksEmbeddings);
            
            // Update cache
            $this->updateCache($item->knowledge_id, $contentEmbedding);
        });
    }
    
    /**
     * Format embedding for PostgreSQL vector column
     */
    private function formatEmbeddingForPostgres(?array $embedding): ?string
    {
        if (!$embedding) {
            return null;
        }
        
        return '[' . implode(',', $embedding) . ']';
    }
    
    /**
     * Update content-specific table
     */
    private function updateContentTable(
        KnowledgeItem $item,
        array $contentEmbedding,
        ?array $chunksEmbeddings
    ): void {
        $tables = [
            'dev' => 'cmis_knowledge.dev',
            'marketing' => 'cmis_knowledge.marketing',
            'org' => 'cmis_knowledge.org',
            'research' => 'cmis_knowledge.research'
        ];
        
        if (!isset($tables[$item->category])) {
            return;
        }
        
        $updateData = [
            'content_embedding' => $this->formatEmbeddingForPostgres($contentEmbedding)
        ];
        
        if ($chunksEmbeddings) {
            $updateData['chunk_embeddings'] = json_encode(
                array_map(fn($chunk) => $chunk, $chunksEmbeddings)
            );
        }
        
        DB::connection($item->getConnectionName())
            ->table($tables[$item->category])
            ->where('knowledge_id', $item->knowledge_id)
            ->update($updateData);
    }
    
    /**
     * Update embeddings cache
     */
    private function updateCache(string $knowledgeId, array $embedding): void
    {
        DB::connection($this->getConnection())
            ->table('cmis_knowledge.embeddings_cache')
            ->updateOrInsert(
                [
                    'source_table' => 'index',
                    'source_id' => $knowledgeId,
                    'source_field' => 'content'
                ],
                [
                    'embedding' => $this->formatEmbeddingForPostgres($embedding),
                    'updated_at' => now(),
                    'usage_count' => DB::raw('COALESCE(usage_count, 0) + 1')
                ]
            );
    }
    
    /**
     * Calculate semantic fingerprint
     */
    private function calculateSemanticFingerprint(array $vec1, array $vec2): array
    {
        $result = [];
        for ($i = 0; $i < count($vec1); $i++) {
            $result[] = ($vec1[$i] + $vec2[$i]) / 2;
        }
        
        // Normalize
        $norm = sqrt(array_sum(array_map(fn($x) => $x * $x, $result)));
        if ($norm > 0) {
            $result = array_map(fn($x) => $x / $norm, $result);
        }
        
        return $result;
    }
    
    /**
     * Log processing error
     */
    private function logError(string $knowledgeId, string $errorMessage): void
    {
        DB::connection($this->getConnection())
            ->table('cmis_knowledge.embedding_update_queue')
            ->insert([
                'knowledge_id' => $knowledgeId,
                'source_table' => 'index',
                'source_field' => 'content',
                'status' => 'failed',
                'error_message' => $errorMessage,
                'created_at' => now()
            ]);
    }
    
    /**
     * Reset processing statistics
     */
    private function resetStats(): void
    {
        $this->processingStats = [
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'start_time' => null,
            'end_time' => null
        ];
    }
    
    /**
     * Log processing statistics
     */
    private function logProcessingStats(): void
    {
        $duration = $this->processingStats['end_time']->diffInSeconds(
            $this->processingStats['start_time']
        );
        
        $rate = $duration > 0 ? $this->processingStats['total_processed'] / $duration : 0;
        
        Log::info("
            =================== Processing Statistics ===================
            Total Processed: {$this->processingStats['total_processed']}
            Successful: {$this->processingStats['successful']}
            Failed: {$this->processingStats['failed']}
            Duration: {$duration} seconds
            Processing Rate: {$rate} items/second
            =============================================================
        ");
    }
    
    /**
     * Get database connection name
     */
    private function getConnection(): string
    {
        return config('cmis-embeddings.database.connection', 'pgsql');
    }
}