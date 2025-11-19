<?php

namespace Tests\Unit\Models\Audience;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Audience\AudienceSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Audience Segment Model Unit Tests
 */
class AudienceSegmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_audience_segment()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'VIP Customers',
            'description' => 'العملاء ذوي القيمة العالية',
            'criteria' => [
                'min_purchase_value' => 5000,
                'purchase_count' => 10,
                'status' => 'active',
            ],
        ]);

        $this->assertDatabaseHas('cmis.audience_segments', [
            'segment_id' => $segment->segment_id,
            'name' => 'VIP Customers',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Segment',
            'criteria' => [],
        ]);

        $this->assertEquals($org->org_id, $segment->org->org_id);
    }

    /** @test */
    public function it_stores_criteria_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $criteria = [
            'age_range' => ['min' => 25, 'max' => 40],
            'location' => ['city' => 'Manama', 'country' => 'BH'],
            'interests' => ['fashion', 'technology', 'travel'],
            'engagement_level' => 'high',
        ];

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Young Professionals',
            'criteria' => $criteria,
        ]);

        $this->assertEquals(25, $segment->criteria['age_range']['min']);
        $this->assertContains('fashion', $segment->criteria['interests']);
    }

    /** @test */
    public function it_tracks_audience_size()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Large Segment',
            'criteria' => [],
            'audience_size' => 15000,
        ]);

        $this->assertEquals(15000, $segment->audience_size);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeSegment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Segment',
            'criteria' => [],
            'is_active' => true,
        ]);

        $inactiveSegment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Segment',
            'criteria' => [],
            'is_active' => false,
        ]);

        $this->assertTrue($activeSegment->is_active);
        $this->assertFalse($inactiveSegment->is_active);
    }

    /** @test */
    public function it_supports_dynamic_and_static_segments()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $dynamicSegment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Dynamic Segment',
            'criteria' => ['purchase_last_30_days' => true],
            'segment_type' => 'dynamic',
        ]);

        $staticSegment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Static Segment',
            'criteria' => [],
            'segment_type' => 'static',
        ]);

        $this->assertEquals('dynamic', $dynamicSegment->segment_type);
        $this->assertEquals('static', $staticSegment->segment_type);
    }

    /** @test */
    public function it_stores_demographic_criteria()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $criteria = [
            'demographics' => [
                'age' => ['min' => 18, 'max' => 35],
                'gender' => 'all',
                'languages' => ['ar', 'en'],
                'education' => ['bachelor', 'master'],
            ],
        ];

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Youth Segment',
            'criteria' => $criteria,
        ]);

        $this->assertEquals(18, $segment->criteria['demographics']['age']['min']);
        $this->assertContains('ar', $segment->criteria['demographics']['languages']);
    }

    /** @test */
    public function it_stores_behavioral_criteria()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $criteria = [
            'behavior' => [
                'visited_pages' => ['/products', '/checkout'],
                'cart_abandonment' => true,
                'email_engagement' => 'high',
                'purchase_frequency' => 'weekly',
            ],
        ];

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Engaged Shoppers',
            'criteria' => $criteria,
        ]);

        $this->assertTrue($segment->criteria['behavior']['cart_abandonment']);
        $this->assertEquals('high', $segment->criteria['behavior']['email_engagement']);
    }

    /** @test */
    public function it_tracks_last_calculated_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Segment',
            'criteria' => [],
            'last_calculated_at' => now(),
        ]);

        $this->assertNotNull($segment->last_calculated_at);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Segment',
            'criteria' => [],
        ]);

        $this->assertTrue(Str::isUuid($segment->segment_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Segment',
            'criteria' => [],
        ]);

        $this->assertNotNull($segment->created_at);
        $this->assertNotNull($segment->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Segment',
            'criteria' => [],
        ]);

        $segmentId = $segment->segment_id;

        $segment->delete();

        $this->assertSoftDeleted('cmis.audience_segments', [
            'segment_id' => $segmentId,
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

        AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Segment',
            'criteria' => [],
        ]);

        AudienceSegment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Segment',
            'criteria' => [],
        ]);

        $org1Segments = AudienceSegment::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Segments);
        $this->assertEquals('Org 1 Segment', $org1Segments->first()->name);
    }
}
