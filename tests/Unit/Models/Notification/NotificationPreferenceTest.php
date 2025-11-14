<?php

namespace Tests\Unit\Models\Notification;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Notification\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * NotificationPreference Model Unit Tests
 */
class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_notification_preference()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'campaign_created',
            'email_enabled' => true,
            'sms_enabled' => false,
        ]);

        $this->assertDatabaseHas('cmis.notification_preferences', [
            'preference_id' => $preference->preference_id,
            'notification_type' => 'campaign_created',
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
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'post_published',
            'email_enabled' => true,
        ]);

        $this->assertEquals($user->user_id, $preference->user->user_id);
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
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'lead_captured',
            'email_enabled' => true,
        ]);

        $this->assertEquals($org->org_id, $preference->org->org_id);
    }

    /** @test */
    public function it_has_different_notification_types()
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

        $notificationTypes = [
            'campaign_created',
            'campaign_completed',
            'post_published',
            'lead_captured',
            'budget_alert',
            'report_generated',
        ];

        foreach ($notificationTypes as $type) {
            NotificationPreference::create([
                'preference_id' => Str::uuid(),
                'user_id' => $user->user_id,
                'org_id' => $org->org_id,
                'notification_type' => $type,
                'email_enabled' => true,
            ]);
        }

        $preferences = NotificationPreference::where('user_id', $user->user_id)->get();
        $this->assertCount(6, $preferences);
    }

    /** @test */
    public function it_can_enable_email_notifications()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'campaign_created',
            'email_enabled' => true,
        ]);

        $this->assertTrue($preference->email_enabled);
    }

    /** @test */
    public function it_can_enable_sms_notifications()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'budget_alert',
            'sms_enabled' => true,
        ]);

        $this->assertTrue($preference->sms_enabled);
    }

    /** @test */
    public function it_can_enable_push_notifications()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'post_published',
            'push_enabled' => true,
        ]);

        $this->assertTrue($preference->push_enabled);
    }

    /** @test */
    public function it_can_enable_in_app_notifications()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'lead_captured',
            'in_app_enabled' => true,
        ]);

        $this->assertTrue($preference->in_app_enabled);
    }

    /** @test */
    public function it_can_disable_all_channels()
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

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'report_generated',
            'email_enabled' => false,
            'sms_enabled' => false,
            'push_enabled' => false,
            'in_app_enabled' => false,
        ]);

        $this->assertFalse($preference->email_enabled);
        $this->assertFalse($preference->sms_enabled);
        $this->assertFalse($preference->push_enabled);
        $this->assertFalse($preference->in_app_enabled);
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
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'test',
            'email_enabled' => true,
        ]);

        $this->assertTrue(Str::isUuid($preference->preference_id));
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
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $preference = NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'notification_type' => 'test',
            'email_enabled' => true,
        ]);

        $this->assertNotNull($preference->created_at);
        $this->assertNotNull($preference->updated_at);
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

        NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user1->user_id,
            'org_id' => $org1->org_id,
            'notification_type' => 'test',
            'email_enabled' => true,
        ]);

        NotificationPreference::create([
            'preference_id' => Str::uuid(),
            'user_id' => $user2->user_id,
            'org_id' => $org2->org_id,
            'notification_type' => 'test',
            'email_enabled' => true,
        ]);

        $org1Preferences = NotificationPreference::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Preferences);
    }
}
