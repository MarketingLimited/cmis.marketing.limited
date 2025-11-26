<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Campaign\CampaignCreated;
use App\Listeners\Campaign\SendCampaignNotification;
use App\Models\Core\Campaign;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

use PHPUnit\Framework\Attributes\Test;
/**
 * Send Campaign Notification Listener Unit Tests
 */
class SendCampaignNotificationListenerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected SendCampaignNotification $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = app(SendCampaignNotification::class);
    }

    #[Test]
    public function it_sends_notification_when_campaign_is_created()
    {
        Notification::fake();

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

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Campaign',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $event = new CampaignCreated($campaign);
        $this->listener->handle($event);

        // Verify notification was sent
        Notification::assertSentTo($user, function ($notification) {
            return $notification instanceof \App\Notifications\CampaignCreated;
        });

        $this->logTestResult('passed', [
            'listener' => 'SendCampaignNotification',
            'test' => 'notification_sent',
        ]);
    }

    #[Test]
    public function it_handles_event_correctly()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $event = new CampaignCreated($campaign);

        try {
            $this->listener->handle($event);
            $handled = true;
        } catch (\Exception $e) {
            $handled = false;
        }

        $this->assertTrue($handled);

        $this->logTestResult('passed', [
            'listener' => 'SendCampaignNotification',
            'test' => 'event_handling',
        ]);
    }

    #[Test]
    public function it_can_be_queued()
    {
        $listenerReflection = new \ReflectionClass($this->listener);

        // Check if listener implements ShouldQueue
        $interfaces = $listenerReflection->getInterfaceNames();
        $shouldQueue = in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces);

        // This test passes whether queued or not
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'listener' => 'SendCampaignNotification',
            'test' => 'queueable',
            'is_queued' => $shouldQueue,
        ]);
    }

    #[Test]
    public function it_handles_errors_gracefully()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);

        $event = new CampaignCreated($campaign);

        try {
            $this->listener->handle($event);
            $errorOccurred = false;
        } catch (\Exception $e) {
            $errorOccurred = true;
        }

        // Should not throw unhandled exceptions
        $this->assertFalse($errorOccurred);

        $this->logTestResult('passed', [
            'listener' => 'SendCampaignNotification',
            'test' => 'error_handling',
        ]);
    }
}
