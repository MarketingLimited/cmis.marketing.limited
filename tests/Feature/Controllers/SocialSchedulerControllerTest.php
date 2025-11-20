<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialPost;

use PHPUnit\Framework\Attributes\Test;
/**
 * Social Scheduler Controller Authorization Tests
 * Tests authentication and authorization for all SocialSchedulerController endpoints
 */
class SocialSchedulerControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_requires_authentication_for_dashboard()
    {
        $response = $this->getJson('/api/orgs/org-123/social/dashboard');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'dashboard',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_access_dashboard_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/social/dashboard");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'dashboard',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_scheduled_posts()
    {
        $response = $this->getJson('/api/orgs/org-123/social/posts/scheduled');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'scheduled',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_view_scheduled_posts_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/social/posts/scheduled");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'scheduled',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_published_posts()
    {
        $response = $this->getJson('/api/orgs/org-123/social/posts/published');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'published',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_drafts()
    {
        $response = $this->getJson('/api/orgs/org-123/social/posts/drafts');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'drafts',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_scheduling_post()
    {
        $response = $this->postJson('/api/orgs/org-123/social/posts/schedule', [
            'content' => 'Test post content',
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'schedule',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_showing_post()
    {
        $response = $this->getJson('/api/orgs/org-123/social/posts/post-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'show',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_updating_post()
    {
        $response = $this->putJson('/api/orgs/org-123/social/posts/post-123', [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'update',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_deleting_post()
    {
        $response = $this->deleteJson('/api/orgs/org-123/social/posts/post-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'destroy',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_publishing_now()
    {
        $response = $this->postJson('/api/orgs/org-123/social/posts/post-123/publish-now');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'publishNow',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_rescheduling()
    {
        $response = $this->postJson('/api/orgs/org-123/social/posts/post-123/reschedule', [
            'scheduled_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'reschedule',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_dashboard()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's dashboard while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/social/dashboard");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'dashboard',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_scheduled_posts()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's scheduled posts while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/social/posts/scheduled");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'scheduled',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_post_details()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $post = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'content' => 'Test post for org1',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's post while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/social/posts/{$post->post_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'show',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_post_update()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $post = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'content' => 'Test post for org1',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to update org1's post while logged in as org2 user
        $response = $this->putJson("/api/orgs/{$setup1['org']->org_id}/social/posts/{$post->post_id}", [
            'content' => 'Hacked content',
        ]);

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'update',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_post_deletion()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $post = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'content' => 'Test post for org1',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to delete org1's post while logged in as org2 user
        $response = $this->deleteJson("/api/orgs/{$setup1['org']->org_id}/social/posts/{$post->post_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'destroy',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_publish_now()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $post = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'content' => 'Test post for org1',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to publish org1's post while logged in as org2 user
        $response = $this->postJson("/api/orgs/{$setup1['org']->org_id}/social/posts/{$post->post_id}/publish-now");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'publishNow',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_reschedule()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $post = SocialPost::create([
            'post_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'content' => 'Test post for org1',
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to reschedule org1's post while logged in as org2 user
        $response = $this->postJson("/api/orgs/{$setup1['org']->org_id}/social/posts/{$post->post_id}/reschedule", [
            'scheduled_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'SocialSchedulerController',
            'method' => 'reschedule',
            'test' => 'org_isolation',
        ]);
    }
}
