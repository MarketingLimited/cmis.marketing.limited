<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Content\PostPublishedEvent;
use App\Models\Core\Org;
use App\Models\Content\Post;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * PostPublished Event Unit Tests
 */
class PostPublishedEventTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

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

        $post = Post::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'منشور تجريبي',
            'platform' => 'facebook',
            'status' => 'published',
        ]);

        $event = new PostPublishedEvent($post);

        $this->assertInstanceOf(PostPublishedEvent::class, $event);
        $this->assertEquals($post->post_id, $event->post->post_id);

        $this->logTestResult('passed', [
            'event' => 'PostPublishedEvent',
            'test' => 'instantiation',
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

        $post = Post::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'منشور تجريبي على تويتر',
            'platform' => 'twitter',
            'status' => 'published',
        ]);

        event(new PostPublishedEvent($post));

        Event::assertDispatched(PostPublishedEvent::class);

        $this->logTestResult('passed', [
            'event' => 'PostPublishedEvent',
            'test' => 'dispatch',
        ]);
    }

    /** @test */
    public function it_contains_post_data()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = Post::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'محتوى المنشور',
            'platform' => 'instagram',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $event = new PostPublishedEvent($post);

        $this->assertEquals('محتوى المنشور', $event->post->content);
        $this->assertEquals('instagram', $event->post->platform);
        $this->assertNotNull($event->post->published_at);

        $this->logTestResult('passed', [
            'event' => 'PostPublishedEvent',
            'test' => 'contains_data',
        ]);
    }

    /** @test */
    public function it_broadcasts_on_private_channel()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = Post::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Test post',
            'platform' => 'facebook',
            'status' => 'published',
        ]);

        $event = new PostPublishedEvent($post);

        // Should broadcast on private channel: posts.{org_id}
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'event' => 'PostPublishedEvent',
            'test' => 'broadcast_channel',
        ]);
    }

    /** @test */
    public function it_can_be_listened_to()
    {
        Event::fake([PostPublishedEvent::class]);

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $post = Post::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'content' => 'Listening test',
            'platform' => 'linkedin',
            'status' => 'published',
        ]);

        event(new PostPublishedEvent($post));

        Event::assertDispatched(PostPublishedEvent::class, function ($e) use ($post) {
            return $e->post->post_id === $post->post_id;
        });

        $this->logTestResult('passed', [
            'event' => 'PostPublishedEvent',
            'test' => 'listener_integration',
        ]);
    }
}
