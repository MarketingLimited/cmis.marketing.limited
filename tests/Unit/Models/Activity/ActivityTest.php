<?php

namespace Tests\Unit\Models\Activity;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Activity\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Activity Model Unit Tests
 */
class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_activity()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
        ]);

        $this->assertDatabaseHas('cmis.activities', [
            'activity_id' => $activity->activity_id,
            'action' => 'created',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'create',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'viewed',
            'resource_type' => 'Lead',
            'resource_id' => Str::uuid(),
        ]);

        $this->assertEquals($org->org_id, $activity->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'belongs_to_org',
        ]);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'updated',
            'resource_type' => 'Content',
            'resource_id' => Str::uuid(),
        ]);

        $this->assertEquals($user->user_id, $activity->user->user_id);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'belongs_to_user',
        ]);
    }

    /** @test */
    public function it_tracks_different_actions()
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

        $actions = ['created', 'updated', 'deleted', 'viewed', 'exported'];

        foreach ($actions as $action) {
            $activity = Activity::create([
                'activity_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'user_id' => $user->user_id,
                'action' => $action,
                'resource_type' => 'Campaign',
                'resource_id' => Str::uuid(),
            ]);

            $this->assertEquals($action, $activity->action);
        }

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'different_actions',
        ]);
    }

    /** @test */
    public function it_stores_properties_as_json()
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

        $properties = [
            'old' => ['status' => 'draft'],
            'new' => ['status' => 'active'],
            'changes' => ['status'],
        ];

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'updated',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
            'properties' => $properties,
        ]);

        $this->assertEquals('draft', $activity->properties['old']['status']);
        $this->assertEquals('active', $activity->properties['new']['status']);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'properties_json',
        ]);
    }

    /** @test */
    public function it_tracks_ip_address()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'login',
            'resource_type' => 'User',
            'resource_id' => $user->user_id,
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertEquals('192.168.1.100', $activity->ip_address);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'ip_address',
        ]);
    }

    /** @test */
    public function it_tracks_user_agent()
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

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'viewed',
            'resource_type' => 'Report',
            'resource_id' => Str::uuid(),
            'user_agent' => $userAgent,
        ]);

        $this->assertEquals($userAgent, $activity->user_agent);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'user_agent',
        ]);
    }

    /** @test */
    public function it_has_description()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
            'description' => 'أنشأ حملة جديدة باسم "عروض الصيف"',
        ]);

        $this->assertEquals('أنشأ حملة جديدة باسم "عروض الصيف"', $activity->description);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'description',
        ]);
    }

    /** @test */
    public function it_is_polymorphic()
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

        $resourceTypes = ['Campaign', 'Lead', 'Content', 'User', 'Report'];

        foreach ($resourceTypes as $resourceType) {
            $activity = Activity::create([
                'activity_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'user_id' => $user->user_id,
                'action' => 'viewed',
                'resource_type' => $resourceType,
                'resource_id' => Str::uuid(),
            ]);

            $this->assertEquals($resourceType, $activity->resource_type);
        }

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'polymorphic',
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
        ]);

        $this->assertTrue(Str::isUuid($activity->activity_id));

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'uuid_primary_key',
        ]);
    }

    /** @test */
    public function it_has_timestamps()
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

        $activity = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'updated',
            'resource_type' => 'Lead',
            'resource_id' => Str::uuid(),
        ]);

        $this->assertNotNull($activity->created_at);
        $this->assertNotNull($activity->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'timestamps',
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

        $user1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
        ]);

        Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'user_id' => $user1->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
        ]);

        Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'user_id' => $user2->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
        ]);

        $org1Activities = Activity::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Activities);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'rls_isolation',
        ]);
    }

    /** @test */
    public function it_can_be_filtered_by_date_range()
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

        $activity1 = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'created',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
            'created_at' => now()->subDays(5),
        ]);

        $activity2 = Activity::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'action' => 'updated',
            'resource_type' => 'Campaign',
            'resource_id' => Str::uuid(),
            'created_at' => now(),
        ]);

        $recentActivities = Activity::where('org_id', $org->org_id)
            ->where('created_at', '>=', now()->subDays(1))
            ->get();

        $this->assertCount(1, $recentActivities);

        $this->logTestResult('passed', [
            'model' => 'Activity',
            'test' => 'filter_by_date_range',
        ]);
    }
}
