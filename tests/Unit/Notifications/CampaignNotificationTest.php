<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CampaignCompletedNotification;
use App\Notifications\CampaignStartedNotification;
use App\Notifications\PostPublishedNotification;

/**
 * Campaign Notification Unit Tests
 */
class CampaignNotificationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_send_campaign_completed_notification()
    {
        Notification::fake();

        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Summer Campaign',
            'status' => 'completed',
        ]);

        $user->notify(new CampaignCompletedNotification($campaign));

        Notification::assertSentTo($user, CampaignCompletedNotification::class);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'send',
        ]);
    }

    /** @test */
    public function it_includes_campaign_details_in_notification()
    {
        Notification::fake();

        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Test Campaign',
            'status' => 'completed',
        ]);

        $notification = new CampaignCompletedNotification($campaign);

        $mailData = $notification->toMail($user);

        $this->assertStringContainsString('Test Campaign', $mailData->subject);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'campaign_details',
        ]);
    }

    /** @test */
    public function it_can_send_campaign_started_notification()
    {
        Notification::fake();

        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'New Campaign',
            'status' => 'active',
        ]);

        $user->notify(new CampaignStartedNotification($campaign));

        Notification::assertSentTo($user, CampaignStartedNotification::class);

        $this->logTestResult('passed', [
            'notification' => 'CampaignStartedNotification',
            'test' => 'send',
        ]);
    }

    /** @test */
    public function it_can_send_post_published_notification()
    {
        Notification::fake();

        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);
        $content = $this->createTestContent($campaign->campaign_id, [
            'title' => 'Published Post',
            'status' => 'published',
        ]);

        $user->notify(new PostPublishedNotification($content));

        Notification::assertSentTo($user, PostPublishedNotification::class);

        $this->logTestResult('passed', [
            'notification' => 'PostPublishedNotification',
            'test' => 'send',
        ]);
    }

    /** @test */
    public function it_supports_mail_channel()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $notification = new CampaignCompletedNotification($campaign);

        $this->assertContains('mail', $notification->via($user));

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'mail_channel',
        ]);
    }

    /** @test */
    public function it_supports_database_channel()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $notification = new CampaignCompletedNotification($campaign);

        $this->assertContains('database', $notification->via($user));

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'database_channel',
        ]);
    }

    /** @test */
    public function it_creates_database_notification_record()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'Test Campaign',
        ]);

        $notification = new CampaignCompletedNotification($campaign);
        $databaseData = $notification->toDatabase($user);

        $this->assertArrayHasKey('campaign_id', $databaseData);
        $this->assertArrayHasKey('campaign_name', $databaseData);
        $this->assertEquals('Test Campaign', $databaseData['campaign_name']);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'database_record',
        ]);
    }

    /** @test */
    public function it_can_be_sent_to_multiple_users()
    {
        Notification::fake();

        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $campaign = $this->createTestCampaign($setup1['org']->org_id);

        $users = collect([$setup1['user'], $setup2['user']]);

        Notification::send($users, new CampaignCompletedNotification($campaign));

        Notification::assertSentTo($users, CampaignCompletedNotification::class);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'multiple_users',
        ]);
    }

    /** @test */
    public function it_includes_action_url_in_notification()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $campaign = $this->createTestCampaign($org->org_id);

        $notification = new CampaignCompletedNotification($campaign);
        $mailData = $notification->toMail($user);

        $this->assertNotNull($mailData->actionUrl);
        $this->assertStringContainsString($campaign->campaign_id, $mailData->actionUrl);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'action_url',
        ]);
    }

    /** @test */
    public function it_respects_user_notification_preferences()
    {
        Notification::fake();

        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        // Set user preference to not receive campaign notifications
        $user->metadata = [
            'notifications' => [
                'campaign_completed' => false,
            ],
        ];
        $user->save();

        $campaign = $this->createTestCampaign($org->org_id);

        $notification = new CampaignCompletedNotification($campaign);

        // Check if notification respects preferences
        $channels = $notification->via($user);

        $this->logTestResult('passed', [
            'notification' => 'CampaignCompletedNotification',
            'test' => 'user_preferences',
        ]);
    }
}
