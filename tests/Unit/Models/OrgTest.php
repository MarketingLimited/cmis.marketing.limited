<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Organization Model Unit Tests
 */
class OrgTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_an_organization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Organization',
            'industry' => 'Technology',
            'website' => 'https://test.com',
        ]);

        $this->assertDatabaseHas('cmis.orgs', [
            'org_id' => $org->org_id,
            'name' => 'Test Organization',
        ]);
    }

    /** @test */
    public function it_has_many_users()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'password' => bcrypt('password'),
        ]);

        \App\Models\Core\UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $user1->id,
            'org_id' => $org->org_id,
            'is_active' => true,
        ]);

        \App\Models\Core\UserOrg::create([
            'id' => Str::uuid(),
            'user_id' => $user2->id,
            'org_id' => $org->org_id,
            'is_active' => true,
        ]);

        $this->assertCount(2, $org->users);
    }

    /** @test */
    public function it_has_many_campaigns()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign 1',
            'status' => 'draft',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign 2',
            'status' => 'active',
        ]);

        $this->assertCount(2, $org->campaigns);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'UUID Test Org',
        ]);

        $this->assertTrue(Str::isUuid($org->org_id));
        $this->assertEquals('org_id', $org->getKeyName());
    }

    /** @test */
    public function it_enforces_unique_name_constraint()
    {
        Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Unique Org',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Unique Org',
        ]);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Soft Delete Org',
        ]);

        $org->delete();

        $this->assertSoftDeleted('cmis.orgs', [
            'org_id' => $org->org_id,
        ]);

        $this->assertCount(0, Org::all());
        $this->assertCount(1, Org::withTrashed()->get());
    }

    /** @test */
    public function it_stores_settings_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Settings Org',
            'settings' => [
                'timezone' => 'Asia/Riyadh',
                'currency' => 'BHD',
                'notifications' => true,
            ],
        ]);

        $this->assertEquals('Asia/Riyadh', $org->settings['timezone']);
        $this->assertEquals('BHD', $org->settings['currency']);
        $this->assertTrue($org->settings['notifications']);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Timestamp Org',
        ]);

        $this->assertNotNull($org->created_at);
        $this->assertNotNull($org->updated_at);
    }

    /** @test */
    public function it_can_count_active_campaigns()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Count Org',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active 1',
            'status' => 'active',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active 2',
            'status' => 'active',
        ]);

        Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Draft',
            'status' => 'draft',
        ]);

        $activeCampaigns = $org->campaigns()->where('status', 'active')->count();
        $this->assertEquals(2, $activeCampaigns);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Org::create([
            'org_id' => Str::uuid(),
            // Missing required 'name' field
        ]);
    }
}
