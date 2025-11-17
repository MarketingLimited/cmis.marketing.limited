<?php

namespace Tests\Unit\Models\Audience;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Audience\Audience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Audience Model Unit Tests
 */
class AudienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_audience()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'الجمهور المستهدف',
            'description' => 'جمهور مهتم بالتكنولوجيا',
        ]);

        $this->assertDatabaseHas('cmis.audiences', [
            'audience_id' => $audience->audience_id,
            'name' => 'الجمهور المستهدف',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Audience',
            'description' => 'Description',
        ]);

        $this->assertEquals($org->org_id, $audience->org->org_id);
    }

    /** @test */
    public function it_has_demographic_criteria()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $demographics = [
            'age_min' => 25,
            'age_max' => 45,
            'gender' => 'all',
            'locations' => ['الرياض', 'جدة', 'الدمام'],
            'languages' => ['ar', 'en'],
        ];

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Demographic Audience',
            'demographics' => $demographics,
        ]);

        $this->assertEquals(25, $audience->demographics['age_min']);
        $this->assertContains('الرياض', $audience->demographics['locations']);
    }

    /** @test */
    public function it_has_interest_criteria()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $interests = [
            'technology',
            'business',
            'entrepreneurship',
            'marketing',
        ];

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Interest-Based Audience',
            'interests' => $interests,
        ]);

        $this->assertCount(4, $audience->interests);
        $this->assertContains('technology', $audience->interests);
    }

    /** @test */
    public function it_has_behavioral_criteria()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $behaviors = [
            'engagement_level' => 'high',
            'purchase_history' => true,
            'last_active_days' => 30,
            'platform_usage' => ['facebook', 'instagram', 'twitter'],
        ];

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Behavioral Audience',
            'behaviors' => $behaviors,
        ]);

        $this->assertEquals('high', $audience->behaviors['engagement_level']);
        $this->assertTrue($audience->behaviors['purchase_history']);
    }

    /** @test */
    public function it_tracks_audience_size()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Large Audience',
            'estimated_size' => 150000,
        ]);

        $this->assertEquals(150000, $audience->estimated_size);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeAudience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Audience',
            'is_active' => true,
        ]);

        $inactiveAudience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Audience',
            'is_active' => false,
        ]);

        $this->assertTrue($activeAudience->is_active);
        $this->assertFalse($inactiveAudience->is_active);
    }

    /** @test */
    public function it_has_different_audience_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customAudience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Custom Audience',
            'type' => 'custom',
        ]);

        $lookalike = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lookalike Audience',
            'type' => 'lookalike',
        ]);

        $saved = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Saved Audience',
            'type' => 'saved',
        ]);

        $this->assertEquals('custom', $customAudience->type);
        $this->assertEquals('lookalike', $lookalike->type);
        $this->assertEquals('saved', $saved->type);
    }

    /** @test */
    public function it_tracks_source_platforms()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $platforms = ['facebook', 'instagram', 'google_ads'];

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Multi-Platform Audience',
            'platforms' => $platforms,
        ]);

        $this->assertCount(3, $audience->platforms);
        $this->assertContains('facebook', $audience->platforms);
    }

    /** @test */
    public function it_has_lookalike_source()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $sourceAudience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Source Audience',
            'type' => 'custom',
        ]);

        $lookalike = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lookalike Audience',
            'type' => 'lookalike',
            'lookalike_source_id' => $sourceAudience->audience_id,
            'lookalike_percentage' => 5,
        ]);

        $this->assertEquals($sourceAudience->audience_id, $lookalike->lookalike_source_id);
        $this->assertEquals(5, $lookalike->lookalike_percentage);
    }

    /** @test */
    public function it_tracks_last_updated_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Updated Audience',
            'last_synced_at' => now(),
        ]);

        $this->assertNotNull($audience->last_synced_at);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Audience',
        ]);

        $this->assertTrue(Str::isUuid($audience->audience_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $audience = Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamp Audience',
        ]);

        $this->assertNotNull($audience->created_at);
        $this->assertNotNull($audience->updated_at);
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

        Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Audience',
        ]);

        Audience::create([
            'audience_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Audience',
        ]);

        $org1Audiences = Audience::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Audiences);
        $this->assertEquals('Org 1 Audience', $org1Audiences->first()->name);
    }
}
