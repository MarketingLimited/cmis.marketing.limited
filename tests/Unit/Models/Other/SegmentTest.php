<?php

namespace Tests\Unit\Models\Other;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Other\Segment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Segment Model Unit Tests
 */
class SegmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_segment()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'High-Value Customers',
            'provider' => 'internal',
        ]);

        $this->assertDatabaseHas('cmis.segments', [
            'segment_id' => $segment->segment_id,
            'name' => 'High-Value Customers',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'create',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Engaged Users',
        ]);

        $this->assertEquals($org->org_id, $segment->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'belongs_to_org',
        ]);
    }

    #[Test]
    public function it_stores_persona_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $persona = [
            'age_range' => '25-34',
            'interests' => ['technology', 'fashion', 'travel'],
            'location' => 'Riyadh',
            'income_level' => 'high',
        ];

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Young Professionals',
            'persona' => $persona,
        ]);

        $this->assertEquals('25-34', $segment->persona['age_range']);
        $this->assertContains('technology', $segment->persona['interests']);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'persona_data',
        ]);
    }

    #[Test]
    public function it_supports_arabic_names()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'العملاء المميزون',
            'notes' => 'شريحة العملاء ذوي القيمة العالية',
        ]);

        $this->assertEquals('العملاء المميزون', $segment->name);
        $this->assertEquals('شريحة العملاء ذوي القيمة العالية', $segment->notes);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'arabic_names',
        ]);
    }

    #[Test]
    public function it_can_have_notes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'VIP Segment',
            'notes' => 'Customers with lifetime value over $10,000',
        ]);

        $this->assertEquals('Customers with lifetime value over $10,000', $segment->notes);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'notes',
        ]);
    }

    #[Test]
    public function it_tracks_provider()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $providers = ['internal', 'facebook', 'google', 'mailchimp'];

        foreach ($providers as $provider) {
            $segment = Segment::create([
                'segment_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => ucfirst($provider) . ' Segment',
                'provider' => $provider,
            ]);

            $this->assertEquals($provider, $segment->provider);
        }

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'provider',
        ]);
    }

    #[Test]
    public function it_uses_soft_deletes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Segment',
        ]);

        $segment->delete();

        $this->assertSoftDeleted('cmis.segments', [
            'segment_id' => $segment->segment_id,
        ]);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'soft_deletes',
        ]);
    }

    #[Test]
    public function it_can_scope_by_org()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Segment',
        ]);

        Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Segment',
        ]);

        $org1Segments = Segment::forOrg($org1->org_id)->get();
        $this->assertCount(1, $org1Segments);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'scope_by_org',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Segment',
        ]);

        $this->assertTrue(Str::isUuid($segment->segment_id));

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'uuid_primary_key',
        ]);
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

        Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Segment 1',
        ]);

        Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Segment 2',
        ]);

        $org1Segments = Segment::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Segments);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'rls_isolation',
        ]);
    }

    #[Test]
    public function it_can_store_complex_persona()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $persona = [
            'demographics' => [
                'age_range' => '35-44',
                'gender' => 'all',
                'education' => 'university',
            ],
            'behaviors' => [
                'purchase_frequency' => 'monthly',
                'avg_order_value' => 500,
                'preferred_channels' => ['email', 'sms'],
            ],
            'interests' => ['sports', 'health', 'finance'],
        ];

        $segment = Segment::create([
            'segment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Buyers',
            'persona' => $persona,
        ]);

        $this->assertEquals('35-44', $segment->persona['demographics']['age_range']);
        $this->assertEquals('monthly', $segment->persona['behaviors']['purchase_frequency']);
        $this->assertContains('sports', $segment->persona['interests']);

        $this->logTestResult('passed', [
            'model' => 'Segment',
            'test' => 'complex_persona',
        ]);
    }
}
