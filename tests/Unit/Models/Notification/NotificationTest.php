<?php

namespace Tests\Unit\Models\Notification;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notification Model Unit Tests
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_notification()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\CampaignCreated',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => [
                'campaign_id' => 'camp_123',
                'campaign_name' => 'Summer Sale',
                'message' => 'Campaign created successfully',
            ],
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'notifiable_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_notifiable()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test'],
        ]);

        $this->assertEquals($user->id, $notification->notifiable->id);
    }

    /** @test */
    public function it_stores_notification_data_as_json()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $data = [
            'title' => 'Campaign Published',
            'message' => 'Your campaign has been published successfully',
            'campaign_id' => 'camp_456',
            'action_url' => '/campaigns/camp_456',
            'icon' => 'campaign',
        ];

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\CampaignPublished',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => $data,
        ]);

        $this->assertEquals('Campaign Published', $notification->data['title']);
        $this->assertEquals('/campaigns/camp_456', $notification->data['action_url']);
    }

    /** @test */
    public function it_can_be_marked_as_read()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test'],
            'read_at' => null,
        ]);

        $this->assertNull($notification->read_at);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_filter_unread_notifications()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\Notification1',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Unread 1'],
            'read_at' => null,
        ]);

        DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\Notification2',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Read'],
            'read_at' => now(),
        ]);

        $unread = $user->unreadNotifications;
        $this->assertCount(1, $unread);
    }

    /** @test */
    public function it_has_different_notification_types()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $types = [
            'App\Notifications\CampaignCreated',
            'App\Notifications\PostPublished',
            'App\Notifications\TeamMemberInvited',
            'App\Notifications\BudgetExceeded',
        ];

        foreach ($types as $type) {
            DatabaseNotification::create([
                'id' => Str::uuid(),
                'type' => $type,
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => ['message' => 'Notification'],
            ]);
        }

        $notifications = $user->notifications;
        $this->assertCount(4, $notifications);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test'],
        ]);

        $this->assertTrue(Str::isUuid($notification->id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test'],
        ]);

        $this->assertNotNull($notification->created_at);
        $this->assertNotNull($notification->updated_at);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $notification = DatabaseNotification::create([
            'id' => Str::uuid(),
            'type' => 'App\Notifications\TestNotification',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['message' => 'Test'],
        ]);

        $notificationId = $notification->id;

        $notification->delete();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
        ]);
    }
}
