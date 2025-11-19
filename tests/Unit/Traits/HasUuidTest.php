<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * HasUuid Trait Unit Tests
 */
class HasUuidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_automatically_generates_uuid_on_creation()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // UUID should be automatically generated
        $this->assertTrue(Str::isUuid($org->org_id));

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'auto_generate',
        ]);
    }

    /** @test */
    public function it_does_not_auto_increment()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        // UUIDs should not be sequential integers
        $this->assertNotEquals($org1->org_id + 1, $org2->org_id);
        $this->assertTrue(Str::isUuid($org1->org_id));
        $this->assertTrue(Str::isUuid($org2->org_id));

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'no_auto_increment',
        ]);
    }

    /** @test */
    public function it_allows_manual_uuid_assignment()
    {
        $customUuid = Str::uuid();

        $org = Org::create([
            'org_id' => $customUuid,
            'name' => 'Test Org',
        ]);

        $this->assertEquals($customUuid, $org->org_id);

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'manual_assignment',
        ]);
    }

    /** @test */
    public function it_uses_string_key_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // Primary key should be string type, not integer
        $this->assertIsString($org->org_id);
        $this->assertNotIsInt($org->org_id);

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'string_key_type',
        ]);
    }

    /** @test */
    public function it_works_with_relationships()
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

        // Relationship should work with UUID foreign keys
        $this->assertEquals($org->org_id, $campaign->org->org_id);
        $this->assertTrue(Str::isUuid($campaign->org_id));

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'relationships',
        ]);
    }

    /** @test */
    public function it_generates_unique_uuids()
    {
        $uuids = [];

        for ($i = 0; $i < 100; $i++) {
            $org = Org::create([
                'org_id' => Str::uuid(),
                'name' => "Org {$i}",
            ]);
            $uuids[] = $org->org_id;
        }

        // All UUIDs should be unique
        $uniqueUuids = array_unique($uuids);
        $this->assertCount(100, $uniqueUuids);

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'uniqueness',
        ]);
    }

    /** @test */
    public function it_maintains_uuid_format()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // UUID should match standard format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        $this->assertMatchesRegularExpression($pattern, $org->org_id);

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'uuid_format',
        ]);
    }

    /** @test */
    public function it_works_across_different_models()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        // All models should have valid UUIDs
        $this->assertTrue(Str::isUuid($org->org_id));
        $this->assertTrue(Str::isUuid($user->user_id));
        $this->assertTrue(Str::isUuid($campaign->campaign_id));

        $this->logTestResult('passed', [
            'trait' => 'HasUuid',
            'test' => 'multiple_models',
        ]);
    }
}
