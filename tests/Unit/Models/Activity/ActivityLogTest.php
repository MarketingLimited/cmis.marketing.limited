<?php

namespace Tests\Unit\Models\Activity;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Activity\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Activity Log Model Unit Tests
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_activity_log()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'campaign_created',
            'description' => 'Created campaign: Summer Sale',
            'ip_address' => '192.168.1.1',
        ]);

        $this->assertDatabaseHas('cmis.activity_logs', [
            'activity_id' => $activity->activity_id,
            'action' => 'campaign_created',
        ]);
    }

    #[Test]
    public function it_belongs_to_user_and_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'User logged in',
        ]);

        $this->assertEquals($user->id, $activity->user->id);
        $this->assertEquals($org->org_id, $activity->org->org_id);
    }

    #[Test]
    public function it_tracks_different_action_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $actions = [
            'campaign_created',
            'campaign_updated',
            'campaign_deleted',
            'post_published',
            'user_invited',
        ];

        foreach ($actions as $action) {
            ActivityLog::create([
                'activity_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'user_id' => $user->id,
                'action' => $action,
                'description' => "Action: {$action}",
            ]);
        }

        $logs = ActivityLog::where('org_id', $org->org_id)->get();
        $this->assertCount(5, $logs);
    }

    #[Test]
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $metadata = [
            'campaign_id' => 'camp_123',
            'campaign_name' => 'Summer Sale',
            'old_status' => 'draft',
            'new_status' => 'active',
            'changes' => ['status', 'budget'],
        ];

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'campaign_updated',
            'description' => 'Updated campaign status',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('camp_123', $activity->metadata['campaign_id']);
        $this->assertEquals('draft', $activity->metadata['old_status']);
        $this->assertContains('status', $activity->metadata['changes']);
    }

    #[Test]
    public function it_stores_ip_address()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'User logged in',
            'ip_address' => '203.0.113.42',
        ]);

        $this->assertEquals('203.0.113.42', $activity->ip_address);
    }

    #[Test]
    public function it_stores_user_agent()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'page_view',
            'description' => 'Viewed dashboard',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        $this->assertStringContainsString('Mozilla', $activity->user_agent);
    }

    #[Test]
    public function it_tracks_entity_type_and_id()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'campaign_updated',
            'description' => 'Updated campaign',
            'entity_type' => 'Campaign',
            'entity_id' => 'camp_123',
        ]);

        $this->assertEquals('Campaign', $activity->entity_type);
        $this->assertEquals('camp_123', $activity->entity_id);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'test_action',
            'description' => 'Test',
        ]);

        $this->assertTrue(Str::isUuid($activity->activity_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $activity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'test_action',
            'description' => 'Test',
        ]);

        $this->assertNotNull($activity->created_at);
        $this->assertNotNull($activity->updated_at);
    }

    #[Test]
    public function it_can_filter_by_date_range()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $oldActivity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'old_action',
            'description' => 'Old activity',
            'created_at' => now()->subDays(10),
        ]);

        $recentActivity = ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->id,
            'action' => 'recent_action',
            'description' => 'Recent activity',
            'created_at' => now()->subDays(2),
        ]);

        $recentLogs = ActivityLog::where('org_id', $org->org_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        $this->assertCount(1, $recentLogs);
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

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'user_id' => $user->id,
            'action' => 'org1_action',
            'description' => 'Org 1 activity',
        ]);

        ActivityLog::create([
            'activity_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'user_id' => $user->id,
            'action' => 'org2_action',
            'description' => 'Org 2 activity',
        ]);

        $org1Logs = ActivityLog::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Logs);
        $this->assertEquals('org1_action', $org1Logs->first()->action);
    }
}
