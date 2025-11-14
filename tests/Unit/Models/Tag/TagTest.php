<?php

namespace Tests\Unit\Models\Tag;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Tag\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Tag Model Unit Tests
 */
class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_tag()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'VIP',
            'slug' => 'vip',
            'color' => '#FF5733',
        ]);

        $this->assertDatabaseHas('cmis.tags', [
            'tag_id' => $tag->tag_id,
            'name' => 'VIP',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $this->assertEquals($org->org_id, $tag->org->org_id);
    }

    /** @test */
    public function it_has_unique_slug()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag1 = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Marketing',
            'slug' => 'marketing',
        ]);

        $tag2 = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Marketing Campaign',
            'slug' => 'marketing-campaign',
        ]);

        $this->assertNotEquals($tag1->slug, $tag2->slug);
    }

    /** @test */
    public function it_stores_tag_color()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Urgent',
            'slug' => 'urgent',
            'color' => '#FF0000',
        ]);

        $this->assertEquals('#FF0000', $tag->color);
    }

    /** @test */
    public function it_has_different_tag_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaignTag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign Tag',
            'slug' => 'campaign-tag',
            'type' => 'campaign',
        ]);

        $contactTag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Contact Tag',
            'slug' => 'contact-tag',
            'type' => 'contact',
        ]);

        $contentTag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Content Tag',
            'slug' => 'content-tag',
            'type' => 'content',
        ]);

        $this->assertEquals('campaign', $campaignTag->type);
        $this->assertEquals('contact', $contactTag->type);
        $this->assertEquals('content', $contentTag->type);
    }

    /** @test */
    public function it_tracks_usage_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Popular Tag',
            'slug' => 'popular-tag',
            'usage_count' => 0,
        ]);

        $tag->increment('usage_count');
        $tag->increment('usage_count');
        $tag->increment('usage_count');

        $this->assertEquals(3, $tag->fresh()->usage_count);
    }

    /** @test */
    public function it_stores_tag_description()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Special Offer',
            'slug' => 'special-offer',
            'description' => 'Tags for special promotional offers',
        ]);

        $this->assertEquals('Tags for special promotional offers', $tag->description);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeTag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Tag',
            'slug' => 'active-tag',
            'is_active' => true,
        ]);

        $inactiveTag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Tag',
            'slug' => 'inactive-tag',
            'is_active' => false,
        ]);

        $this->assertTrue($activeTag->is_active);
        $this->assertFalse($inactiveTag->is_active);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $this->assertTrue(Str::isUuid($tag->tag_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);

        $this->assertNotNull($tag->created_at);
        $this->assertNotNull($tag->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tag = Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Tag',
            'slug' => 'deletable-tag',
        ]);

        $tagId = $tag->tag_id;

        $tag->delete();

        $this->assertSoftDeleted('cmis.tags', [
            'tag_id' => $tagId,
        ]);
    }

    /** @test */
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

        Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Tag',
            'slug' => 'org1-tag',
        ]);

        Tag::create([
            'tag_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Tag',
            'slug' => 'org2-tag',
        ]);

        $org1Tags = Tag::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Tags);
        $this->assertEquals('Org 1 Tag', $org1Tags->first()->name);
    }
}
