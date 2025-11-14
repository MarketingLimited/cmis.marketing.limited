<?php

namespace Tests\Unit\Models\Creative;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Campaign;
use App\Models\Creative\CreativeBrief;
use App\Models\Creative\ContentPlan;
use App\Models\Creative\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Content Plan Model Unit Tests
 */
class ContentPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_a_content_plan()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Summer Content Plan',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $this->assertDatabaseHas('cmis.content_plans', [
            'plan_id' => $plan->plan_id,
            'name' => 'Summer Content Plan',
        ]);
    }

    /** @test */
    public function it_belongs_to_organization_and_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Test Plan',
        ]);

        $this->assertEquals($org->org_id, $plan->org->org_id);
        $this->assertEquals($campaign->campaign_id, $plan->campaign->campaign_id);
    }

    /** @test */
    public function it_has_many_content_items()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Plan',
        ]);

        ContentItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $plan->plan_id,
            'org_id' => $org->org_id,
            'channel_id' => 1,
            'format_id' => 1,
            'title' => 'Item 1',
            'status' => 'draft',
        ]);

        ContentItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $plan->plan_id,
            'org_id' => $org->org_id,
            'channel_id' => 2,
            'format_id' => 2,
            'title' => 'Item 2',
            'status' => 'draft',
        ]);

        $this->assertCount(2, $plan->contentItems);
    }

    /** @test */
    public function it_validates_date_range()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Date Range Plan',
            'start_date' => '2024-06-01',
            'end_date' => '2024-06-30',
        ]);

        $this->assertTrue(
            strtotime($plan->end_date) > strtotime($plan->start_date)
        );
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Plan',
        ]);

        $this->assertTrue(Str::isUuid($plan->plan_id));
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Soft Delete Plan',
        ]);

        $plan->delete();

        $this->assertSoftDeleted('cmis.content_plans', [
            'plan_id' => $plan->plan_id,
        ]);
    }

    /** @test */
    public function it_cascades_soft_delete_to_content_items()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Cascade Plan',
        ]);

        $item = ContentItem::create([
            'item_id' => Str::uuid(),
            'plan_id' => $plan->plan_id,
            'org_id' => $org->org_id,
            'channel_id' => 1,
            'format_id' => 1,
            'title' => 'Cascade Item',
            'status' => 'draft',
        ]);

        $plan->delete();

        $this->assertSoftDeleted('cmis.content_plans', [
            'plan_id' => $plan->plan_id,
        ]);

        // Items should also be soft deleted when plan is deleted
        $remainingItems = ContentItem::where('plan_id', $plan->plan_id)->count();
        $this->assertEquals(0, $remainingItems);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plan = ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamp Plan',
        ]);

        $this->assertNotNull($plan->created_at);
        $this->assertNotNull($plan->updated_at);
    }
}
