<?php

namespace Tests\Unit\Services\CMIS;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CMIS\SemanticSearchService;
use App\Models\Knowledge\KnowledgeIndex;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Semantic Search Service Unit Tests
 */
class SemanticSearchServiceTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected SemanticSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SemanticSearchService::class);
    }

    #[Test]
    public function it_can_perform_semantic_search()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockGeminiAPI('success');

        // Create knowledge items
        $item1 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Summer campaign for fashion products',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $item2 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Winter sales promotion',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.2),
        ]);

        $results = $this->service->search('summer fashion', $org->org_id, 5);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('results', $results);
    }

    #[Test]
    public function it_can_generate_embeddings_for_text()
    {
        $this->mockGeminiAPI('success');

        $text = 'This is a test text for embedding generation';

        $embedding = $this->service->generateEmbedding($text);

        $this->assertIsArray($embedding);
        $this->assertCount(768, $embedding); // Gemini embedding dimension
    }

    #[Test]
    public function it_caches_embeddings_for_same_text()
    {
        $this->mockGeminiAPI('success');

        $text = 'Cached embedding test';

        // First call - should hit API
        $embedding1 = $this->service->generateEmbedding($text);

        // Second call - should use cache
        $embedding2 = $this->service->generateEmbedding($text);

        $this->assertEquals($embedding1, $embedding2);
    }

    #[Test]
    public function it_can_find_similar_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockGeminiAPI('success');

        // Create knowledge items
        for ($i = 1; $i <= 5; $i++) {
            KnowledgeIndex::create([
                'index_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'content' => "Content item {$i}",
                'content_type' => 'content',
                'embedding' => array_fill(0, 768, $i * 0.1),
            ]);
        }

        $queryEmbedding = array_fill(0, 768, 0.15);

        $similar = $this->service->findSimilar($queryEmbedding, $org->org_id, 3);

        $this->assertIsArray($similar);
        $this->assertLessThanOrEqual(3, count($similar));
    }

    #[Test]
    public function it_filters_search_by_content_type()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockGeminiAPI('success');

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Campaign content',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Creative content',
            'content_type' => 'creative',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $results = $this->service->search('content', $org->org_id, 10, [
            'content_type' => 'campaign',
        ]);

        $this->assertArrayHasKey('results', $results);

        foreach ($results['results'] as $result) {
            $this->assertEquals('campaign', $result['content_type']);
        }
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];
        $user1 = $setup1['user'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $this->mockGeminiAPI('success');

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'content' => 'Org 1 content',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'content' => 'Org 2 content',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAsUserInOrg($user1, $org1);

        $results = $this->service->search('content', $org1->org_id);

        foreach ($results['results'] ?? [] as $result) {
            $this->assertEquals($org1->org_id, $result['org_id']);
        }
    }

    #[Test]
    public function it_handles_empty_search_results()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockGeminiAPI('success');

        $results = $this->service->search('nonexistent query', $org->org_id);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('results', $results);
        $this->assertEmpty($results['results']);
    }

    #[Test]
    public function it_can_index_new_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockGeminiAPI('success');

        $contentData = [
            'content' => 'New content to index',
            'content_type' => 'campaign',
            'metadata' => [
                'campaign_id' => Str::uuid(),
                'name' => 'Test Campaign',
            ],
        ];

        $result = $this->service->indexContent($org->org_id, $contentData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('index_id', $result);

        $this->assertDatabaseHas('cmis.knowledge_indexes', [
            'org_id' => $org->org_id,
            'content' => 'New content to index',
        ]);
    }

    #[Test]
    public function it_handles_api_errors_gracefully()
    {
        $this->mockGeminiAPI('error');

        $result = $this->service->generateEmbedding('test text');

        // Should return empty array or handle error gracefully
        $this->assertTrue(is_array($result) || is_null($result));
    }
}
