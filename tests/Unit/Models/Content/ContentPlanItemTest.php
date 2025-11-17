<?php

namespace Tests\Unit\Models\Content;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Content\ContentPlan;
use App\Models\Content\ContentPlanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Content Plan Item Model Unit Tests
 */
class ContentPlanItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_content_plan_item()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'منشور إنستقرام',
            'content_type' => 'image',
            'platform' => 'instagram',
            'scheduled_date' => now()->addDays(1),
        ]);

        $this->assertDatabaseHas('cmis.content_plan_items', [
            'item_id' => $item->item_id,
            'title' => 'منشور إنستقرام',
        ]);
    }

    /** @test */
    public function it_belongs_to_content_plan_and_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $this->assertEquals($contentPlan->plan_id, $item->contentPlan->plan_id);
        $this->assertEquals($org->org_id, $item->org->org_id);
    }

    /** @test */
    public function it_validates_content_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $this->assertContains($item->content_type, [
            'image',
            'video',
            'carousel',
            'story',
            'reel',
            'text',
        ]);
    }

    /** @test */
    public function it_validates_platform()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'facebook',
        ]);

        $this->assertContains($item->platform, [
            'instagram',
            'facebook',
            'twitter',
            'linkedin',
            'tiktok',
            'youtube',
            'snapchat',
        ]);
    }

    /** @test */
    public function it_validates_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
            'status' => 'draft',
        ]);

        $this->assertContains($item->status, [
            'draft',
            'scheduled',
            'published',
            'cancelled',
        ]);
    }

    /** @test */
    public function it_stores_content_details_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $contentDetails = [
            'caption' => 'نص المنشور',
            'hashtags' => ['#تسويق', '#رقمي'],
            'mentions' => ['@user1', '@user2'],
            'location' => 'Manama, Bahrain',
        ];

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
            'content_details' => $contentDetails,
        ]);

        $this->assertEquals('نص المنشور', $item->content_details['caption']);
        $this->assertContains('#تسويق', $item->content_details['hashtags']);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $this->assertTrue(Str::isUuid($item->item_id));
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $item->delete();

        $this->assertSoftDeleted('cmis.content_plan_items', [
            'item_id' => $item->item_id,
        ]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $contentPlan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign->campaign_id,
            'org_id' => $org->org_id,
            'name' => 'Content Plan',
            'status' => 'active',
        ]);

        $item = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan->plan_id,
            'org_id' => $org->org_id,
            'title' => 'Test Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $this->assertNotNull($item->created_at);
        $this->assertNotNull($item->updated_at);
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

        $campaign1 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Campaign 1',
            'status' => 'active',
        ]);

        $campaign2 = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Campaign 2',
            'status' => 'active',
        ]);

        $contentPlan1 = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign1->campaign_id,
            'org_id' => $org1->org_id,
            'name' => 'Plan 1',
            'status' => 'active',
        ]);

        $contentPlan2 = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'campaign_id' => $campaign2->campaign_id,
            'org_id' => $org2->org_id,
            'name' => 'Plan 2',
            'status' => 'active',
        ]);

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan1->plan_id,
            'org_id' => $org1->org_id,
            'title' => 'Org 1 Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $contentPlan2->plan_id,
            'org_id' => $org2->org_id,
            'title' => 'Org 2 Item',
            'content_type' => 'image',
            'platform' => 'instagram',
        ]);

        $org1Items = ContentPlanItem::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Items);
        $this->assertEquals('Org 1 Item', $org1Items->first()->title);
    }
}
