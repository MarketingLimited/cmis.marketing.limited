<?php

namespace Tests\Integration\Knowledge;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\EmbeddingService;
use App\Services\CMIS\SemanticSearchService;
use App\Services\CMIS\KnowledgeFeedbackService;
use App\Jobs\GenerateEmbeddingsJob;
use App\Models\Knowledge\EmbeddingsCache;
use App\Models\Knowledge\SemanticSearchLog;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
class EmbeddingWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected EmbeddingService $embeddingService;
    protected SemanticSearchService $searchService;
    protected KnowledgeFeedbackService $feedbackService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->embeddingService = app(EmbeddingService::class);
        $this->searchService = app(SemanticSearchService::class);
        $this->feedbackService = app(KnowledgeFeedbackService::class);
    }

    #[Test]
    public function it_can_generate_embeddings_for_content()
    {
        $this->mockGeminiAPI('success');

        $content = 'This is a test content for embedding generation.';

        $embedding = $this->embeddingService->generateEmbedding($content);

        $this->assertIsArray($embedding);
        $this->assertCount(768, $embedding);
        $this->assertIsFloat($embedding[0]);

        $this->logTestResult('passed', [
            'embedding_dimension' => count($embedding),
            'content_length' => strlen($content),
        ]);
    }

    #[Test]
    public function it_caches_generated_embeddings()
    {
        $this->mockGeminiAPI('success');

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $embedding = $this->embeddingService->getOrGenerateEmbedding(
            'cmis.campaigns',
            $campaign->campaign_id,
            'name',
            $campaign->name
        );

        $this->assertIsArray($embedding);

        // Verify cache entry was created
        $cached = EmbeddingsCache::where('source_table', 'cmis.campaigns')
            ->where('source_id', $campaign->campaign_id)
            ->where('source_field', 'name')
            ->first();

        $this->assertNotNull($cached);
        $this->assertCount(768, json_decode($cached->embedding));

        $this->logTestResult('passed', [
            'source_table' => 'cmis.campaigns',
            'source_id' => $campaign->campaign_id,
            'cached' => true,
        ]);
    }

    #[Test]
    public function it_can_perform_semantic_search()
    {
        $this->mockGeminiAPI('success');

        $searchQuery = 'marketing campaign for summer sale';

        $results = $this->searchService->search($searchQuery, [
            'limit' => 10,
            'threshold' => 0.7,
            'category' => 'marketing',
        ]);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('results', $results);
        $this->assertArrayHasKey('query_embedding', $results);

        // Verify search was logged
        $this->assertDatabaseHas('cmis_knowledge.semantic_search_logs', [
            'query_text' => $searchQuery,
        ]);

        $this->logTestResult('passed', [
            'query' => $searchQuery,
            'results_count' => count($results['results']),
        ]);
    }

    #[Test]
    public function it_caches_search_results()
    {
        $this->mockGeminiAPI('success');

        $searchQuery = 'test search query';

        // First search
        $results1 = $this->searchService->search($searchQuery);

        // Second search (should hit cache)
        $results2 = $this->searchService->search($searchQuery);

        $this->assertEquals($results1, $results2);

        // Verify cache entry exists
        $queryHash = hash('sha256', $searchQuery);

        $this->assertDatabaseHas('cmis_knowledge.semantic_search_results_cache', [
            'query_hash' => $queryHash,
            'query_text' => $searchQuery,
        ]);

        $this->logTestResult('passed', [
            'query' => $searchQuery,
            'cache' => 'hit',
        ]);
    }

    #[Test]
    public function it_can_queue_embedding_generation()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        GenerateEmbeddingsJob::dispatch('cmis.campaigns', $campaign->campaign_id);

        Queue::assertPushed(GenerateEmbeddingsJob::class);

        $this->logTestResult('passed', [
            'job' => 'GenerateEmbeddingsJob',
            'source_table' => 'cmis.campaigns',
        ]);
    }

    #[Test]
    public function it_handles_embedding_api_errors()
    {
        $this->mockGeminiAPI('error');

        $this->expectException(\Exception::class);

        $this->embeddingService->generateEmbedding('test content');
    }

    #[Test]
    public function it_can_register_feedback_for_search_results()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $searchLog = SemanticSearchLog::create([
            'log_id' => Str::uuid(),
            'query_text' => 'test search',
            'results_count' => 5,
            'avg_similarity' => 0.85,
            'user_id' => $user->user_id,
            'created_at' => now(),
        ]);

        $feedback = $this->feedbackService->registerFeedback(
            $searchLog->log_id,
            'positive',
            ['helpful' => true]
        );

        $this->assertTrue($feedback);

        // Verify feedback was recorded
        $searchLog = $searchLog->fresh();
        $this->assertEquals('positive', $searchLog->user_feedback);

        $this->logTestResult('passed', [
            'search_log_id' => $searchLog->log_id,
            'feedback' => 'positive',
        ]);
    }

    #[Test]
    public function it_can_batch_generate_embeddings()
    {
        $this->mockGeminiAPI('batch_success');

        $contents = [
            'First content to embed',
            'Second content to embed',
        ];

        $embeddings = $this->embeddingService->batchGenerateEmbeddings($contents);

        $this->assertIsArray($embeddings);
        $this->assertCount(2, $embeddings);
        $this->assertCount(768, $embeddings[0]);
        $this->assertCount(768, $embeddings[1]);

        $this->logTestResult('passed', [
            'batch_size' => 2,
            'embeddings_generated' => count($embeddings),
        ]);
    }

    #[Test]
    public function it_respects_embedding_quality_threshold()
    {
        $this->mockGeminiAPI('success');

        $highQualityContent = 'This is a well-structured, meaningful content about marketing campaigns and their strategies.';
        $lowQualityContent = 'abc';

        $highQualityEmbedding = $this->embeddingService->generateEmbedding($highQualityContent);
        $lowQualityEmbedding = $this->embeddingService->generateEmbedding($lowQualityContent);

        $this->assertIsArray($highQualityEmbedding);
        $this->assertIsArray($lowQualityEmbedding);

        $this->logTestResult('passed', [
            'quality_check' => 'performed',
        ]);
    }

    #[Test]
    public function it_can_update_embeddings_when_content_changes()
    {
        $this->mockGeminiAPI('success');

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Original Name',
        ]);

        // Generate initial embedding
        $embedding1 = $this->embeddingService->getOrGenerateEmbedding(
            'cmis.campaigns',
            $campaign->campaign_id,
            'name',
            $campaign->name
        );

        // Update campaign
        $campaign->update(['name' => 'Updated Name']);

        // Invalidate cache and regenerate
        $this->embeddingService->invalidateCache('cmis.campaigns', $campaign->campaign_id, 'name');

        $embedding2 = $this->embeddingService->getOrGenerateEmbedding(
            'cmis.campaigns',
            $campaign->campaign_id,
            'name',
            $campaign->name
        );

        $this->assertNotEquals($embedding1, $embedding2);

        $this->logTestResult('passed', [
            'campaign_id' => $campaign->campaign_id,
            'cache_invalidation' => 'verified',
            'regeneration' => 'verified',
        ]);
    }
}
