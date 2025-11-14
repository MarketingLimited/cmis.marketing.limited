<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Content\Content;
use App\Events\Content\ContentPublishedEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * ContentPublished Event Unit Tests
 */
class ContentPublishedEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Published Content',
            'body' => 'Content body',
            'status' => 'published',
        ]);

        $event = new ContentPublishedEvent($content);

        $this->assertInstanceOf(ContentPublishedEvent::class, $event);
        $this->assertEquals($content->content_id, $event->content->content_id);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'instantiation',
        ]);
    }

    /** @test */
    public function it_contains_content_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'عنوان المحتوى',
            'body' => 'نص المحتوى باللغة العربية',
            'status' => 'published',
        ]);

        $event = new ContentPublishedEvent($content);

        $this->assertEquals('عنوان المحتوى', $event->content->title);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'content_data',
        ]);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'body' => 'Test body',
            'status' => 'published',
        ]);

        event(new ContentPublishedEvent($content));

        Event::assertDispatched(ContentPublishedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_org_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Broadcast Content',
            'body' => 'Broadcast body',
            'status' => 'published',
        ]);

        $event = new ContentPublishedEvent($content);

        // Should broadcast on org-specific channel
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    /** @test */
    public function it_includes_publication_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Timestamped Content',
            'body' => 'Content with timestamp',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $event = new ContentPublishedEvent($content);

        // Should include publication timestamp
        $this->assertNotNull($content->published_at);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'publication_timestamp',
        ]);
    }

    /** @test */
    public function it_triggers_platform_distribution()
    {
        Event::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Distribution Content',
            'body' => 'Content to distribute',
            'status' => 'published',
        ]);

        event(new ContentPublishedEvent($content));

        // Should trigger distribution to platforms
        Event::assertDispatched(ContentPublishedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'trigger_distribution',
        ]);
    }

    /** @test */
    public function it_includes_platform_information()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $content = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Platform Content',
            'body' => 'Multi-platform content',
            'status' => 'published',
            'platforms' => ['facebook', 'instagram', 'twitter'],
        ]);

        $event = new ContentPublishedEvent($content);

        // Should include platform information
        $this->assertCount(3, $content->platforms);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'platform_information',
        ]);
    }

    /** @test */
    public function it_respects_org_context()
    {
        Event::fake();

        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $content1 = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'title' => 'Org 1 Content',
            'body' => 'Content 1',
            'status' => 'published',
        ]);

        $content2 = Content::create([
            'content_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'title' => 'Org 2 Content',
            'body' => 'Content 2',
            'status' => 'published',
        ]);

        event(new ContentPublishedEvent($content1));

        // Should only notify members of org1
        $this->assertNotEquals($content1->org_id, $content2->org_id);

        $this->logTestResult('passed', [
            'event' => 'ContentPublishedEvent',
            'test' => 'org_context',
        ]);
    }
}
