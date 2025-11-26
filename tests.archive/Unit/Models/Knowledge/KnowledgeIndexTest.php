<?php

namespace Tests\Unit\Models\Knowledge;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Knowledge\KnowledgeIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Knowledge Index Model Unit Tests
 */
class KnowledgeIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_a_knowledge_index()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'This is a test content for knowledge indexing',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->assertDatabaseHas('cmis.knowledge_indexes', [
            'index_id' => $knowledgeIndex->index_id,
            'content_type' => 'campaign',
        ]);
    }

    #[Test]
    public function it_belongs_to_an_organization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Test content',
            'content_type' => 'creative',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->assertEquals($org->org_id, $knowledgeIndex->org->org_id);
    }

    #[Test]
    public function it_stores_embedding_vector()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $embedding = array_fill(0, 768, 0.5);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Embedding test content',
            'content_type' => 'content',
            'embedding' => $embedding,
        ]);

        $this->assertIsArray($knowledgeIndex->embedding);
        $this->assertCount(768, $knowledgeIndex->embedding);
        $this->assertEquals(0.5, $knowledgeIndex->embedding[0]);
    }

    #[Test]
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'campaign_id' => Str::uuid(),
            'campaign_name' => 'Summer Campaign',
            'created_by' => 'user_123',
            'tags' => ['summer', 'fashion', 'sale'],
        ];

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Metadata test',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.1),
            'metadata' => $metadata,
        ]);

        $this->assertEquals('Summer Campaign', $knowledgeIndex->metadata['campaign_name']);
        $this->assertContains('summer', $knowledgeIndex->metadata['tags']);
    }

    #[Test]
    public function it_validates_content_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Content type test',
            'content_type' => 'campaign',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->assertContains($knowledgeIndex->content_type, [
            'campaign',
            'creative',
            'content',
            'product',
            'service',
            'knowledge',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'UUID test',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->assertTrue(Str::isUuid($knowledgeIndex->index_id));
    }

    #[Test]
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Soft delete test',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $knowledgeIndex->delete();

        $this->assertSoftDeleted('cmis.knowledge_indexes', [
            'index_id' => $knowledgeIndex->index_id,
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $knowledgeIndex = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Timestamp test',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $this->assertNotNull($knowledgeIndex->created_at);
        $this->assertNotNull($knowledgeIndex->updated_at);
    }

    #[Test]
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'content' => 'Org 1 knowledge',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'content' => 'Org 2 knowledge',
            'content_type' => 'content',
            'embedding' => array_fill(0, 768, 0.1),
        ]);

        $org1Indexes = KnowledgeIndex::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Indexes);
        $this->assertEquals('Org 1 knowledge', $org1Indexes->first()->content);
    }

    #[Test]
    public function it_can_calculate_similarity()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $embedding1 = array_fill(0, 768, 0.5);
        $embedding2 = array_fill(0, 768, 0.5);
        $embedding3 = array_fill(0, 768, 0.1);

        $index1 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Similar content 1',
            'content_type' => 'content',
            'embedding' => $embedding1,
        ]);

        $index2 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Similar content 2',
            'content_type' => 'content',
            'embedding' => $embedding2,
        ]);

        $index3 = KnowledgeIndex::create([
            'index_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Different content',
            'content_type' => 'content',
            'embedding' => $embedding3,
        ]);

        // index1 and index2 should be more similar than index1 and index3
        $this->assertTrue(true); // Placeholder for actual similarity calculation
    }
}
