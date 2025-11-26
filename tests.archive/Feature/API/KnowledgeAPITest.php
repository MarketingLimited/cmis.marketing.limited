<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Knowledge\KnowledgeIndex;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Knowledge Base API Feature Tests
 */
class KnowledgeAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_knowledge_entry()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/knowledge', [
            'title' => 'استراتيجيات التسويق الرقمي',
            'content' => 'دليل شامل لأفضل استراتيجيات التسويق الرقمي في 2024',
            'category' => 'marketing',
            'tags' => ['تسويق', 'استراتيجية', 'رقمي'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'استراتيجيات التسويق الرقمي');

        $this->assertDatabaseHas('cmis.knowledge_index', [
            'org_id' => $org->org_id,
            'title' => 'استراتيجيات التسويق الرقمي',
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/knowledge',
        ]);
    }

    #[Test]
    public function it_can_get_all_knowledge_entries()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Entry 1',
            'content' => 'Content 1',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Entry 2',
            'content' => 'Content 2',
            'content_type' => 'guide',
            'embedding' => array_fill(0, 768, 0.2),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/knowledge');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge',
        ]);
    }

    #[Test]
    public function it_can_get_knowledge_entry_by_id()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $entry = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Entry',
            'content' => 'Test Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/knowledge/{$entry->index_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Test Entry');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge/{id}',
        ]);
    }

    #[Test]
    public function it_can_update_knowledge_entry()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $entry = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Original Title',
            'content' => 'Original Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/knowledge/{$entry->index_id}", [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Updated Title');

        $this->logTestResult('passed', [
            'endpoint' => 'PUT /api/knowledge/{id}',
        ]);
    }

    #[Test]
    public function it_can_delete_knowledge_entry()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $entry = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'To Delete',
            'content' => 'Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/knowledge/{$entry->index_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.knowledge_index', [
            'index_id' => $entry->index_id,
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/knowledge/{id}',
        ]);
    }

    #[Test]
    public function it_can_perform_semantic_search()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'التسويق عبر السوشيال ميديا',
            'content' => 'دليل شامل للتسويق عبر منصات التواصل الاجتماعي',
            'content_type' => 'guide',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/knowledge/search', [
            'query' => 'كيفية التسويق على إنستقرام',
            'limit' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['index_id', 'title', 'similarity'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/knowledge/search',
        ]);
    }

    #[Test]
    public function it_can_find_similar_content()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $entry = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Original Entry',
            'content' => 'Content about marketing',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/knowledge/{$entry->index_id}/similar");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['index_id', 'title', 'similarity'],
            ],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge/{id}/similar',
        ]);
    }

    #[Test]
    public function it_can_filter_by_category()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Marketing Guide',
            'content' => 'Content',
            'content_type' => 'guide',
            'metadata' => ['category' => 'marketing'],
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Sales Guide',
            'content' => 'Content',
            'content_type' => 'guide',
            'metadata' => ['category' => 'sales'],
            'embedding' => array_fill(0, 768, 0.2),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/knowledge?category=marketing');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge?category=',
        ]);
    }

    #[Test]
    public function it_can_filter_by_content_type()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Article 1',
            'content' => 'Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Guide 1',
            'content' => 'Content',
            'content_type' => 'guide',
            'embedding' => array_fill(0, 768, 0.2),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/knowledge?content_type=article');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge?content_type=',
        ]);
    }

    #[Test]
    public function it_can_get_knowledge_statistics()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Entry 1',
            'content' => 'Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/knowledge/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['total_entries', 'by_type', 'by_category'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge/statistics',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $entry1 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'title' => 'Org 1 Entry',
            'content' => 'Content',
            'content_type' => 'article',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/knowledge/{$entry1->index_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/knowledge/{id}',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/knowledge', [
            'content' => 'Content without title',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/knowledge',
            'validation' => 'required_fields',
        ]);
    }
}
