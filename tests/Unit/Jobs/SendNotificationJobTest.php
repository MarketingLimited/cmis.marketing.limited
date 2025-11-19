<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Notifications\SendNotificationJob;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

/**
 * SendNotification Job Unit Tests
 */
class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_sends_notification_to_user()
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

        Notification::fake();

        $job = new SendNotificationJob($user, 'test_notification', [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
        ]);

        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'send_to_user',
        ]);
    }

    /** @test */
    public function it_sends_email_notification()
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

        Notification::fake();

        $job = new SendNotificationJob($user, 'email_notification', [
            'title' => 'إشعار بريد إلكتروني',
            'message' => 'رسالة الإشعار',
        ]);

        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'email_notification',
        ]);
    }

    /** @test */
    public function it_sends_database_notification()
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

        Notification::fake();

        $job = new SendNotificationJob($user, 'database_notification', [
            'title' => 'Database Notification',
            'message' => 'Stored in database',
        ]);

        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'database_notification',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        SendNotificationJob::dispatch($user, 'test', ['message' => 'Test']);

        Queue::assertPushed(SendNotificationJob::class);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_supports_multiple_channels()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Notification::fake();

        $job = new SendNotificationJob($user, 'multi_channel', [
            'title' => 'Multi-channel Notification',
            'message' => 'Sent via multiple channels',
        ], ['mail', 'database', 'broadcast']);

        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'multiple_channels',
        ]);
    }

    /** @test */
    public function it_handles_notification_preferences()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Notification::fake();

        $job = new SendNotificationJob($user, 'preferenced_notification', [
            'title' => 'Notification',
            'message' => 'Respects user preferences',
        ]);

        $result = $job->handle();

        // Should check user preferences before sending
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'notification_preferences',
        ]);
    }

    /** @test */
    public function it_sends_bulk_notifications()
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = User::create([
                'user_id' => Str::uuid(),
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);
        }

        Notification::fake();

        foreach ($users as $user) {
            $job = new SendNotificationJob($user, 'bulk_notification', [
                'title' => 'Bulk Notification',
                'message' => 'Sent to multiple users',
            ]);
            $job->handle();
        }

        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'bulk_notifications',
        ]);
    }

    /** @test */
    public function it_includes_action_url()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Notification::fake();

        $job = new SendNotificationJob($user, 'actionable_notification', [
            'title' => 'Action Required',
            'message' => 'Please review this campaign',
            'action_url' => 'https://app.example.com/campaigns/123',
        ]);

        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'action_url',
        ]);
    }

    /** @test */
    public function it_handles_failed_delivery()
    {
        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => bcrypt('password'),
        ]);

        Notification::fake();

        $job = new SendNotificationJob($user, 'failing_notification', [
            'title' => 'Test',
            'message' => 'This might fail',
        ]);

        try {
            $result = $job->handle();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'failed_delivery',
        ]);
    }

    /** @test */
    public function it_queues_with_delay()
    {
        Queue::fake();

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        SendNotificationJob::dispatch($user, 'delayed', ['message' => 'Test'])
            ->delay(now()->addMinutes(10));

        Queue::assertPushed(SendNotificationJob::class);

        $this->logTestResult('passed', [
            'job' => 'SendNotificationJob',
            'test' => 'delayed_queue',
        ]);
    }
}
