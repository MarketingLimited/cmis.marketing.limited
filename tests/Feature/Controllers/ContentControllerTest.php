<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Content\ContentPlanItem;
use Illuminate\Support\Str;

/**
 * Content Controller Feature Tests
 */
class ContentControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_list_content_items()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Content 1',
            'status' => 'draft',
        ]);

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Content 2',
            'status' => 'published',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/content');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'GET /api/content',
        ]);
    }

    /** @test */
    public function it_can_create_content_item()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/content', [
            'title' => 'محتوى جديد',
            'body' => 'نص المحتوى',
            'content_type' => 'post',
            'platform' => 'facebook',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'محتوى جديد');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'POST /api/content',
        ]);
    }

    /** @test */
    public function it_can_get_single_content_item()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $content = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/content/{$content->item_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Test Content');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'GET /api/content/{id}',
        ]);
    }

    /** @test */
    public function it_can_update_content_item()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $content = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Original Title',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson("/api/content/{$content->item_id}", [
            'title' => 'Updated Title',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.title', 'Updated Title');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'PUT /api/content/{id}',
        ]);
    }

    /** @test */
    public function it_can_delete_content_item()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $content = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'To Delete',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson("/api/content/{$content->item_id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('cmis.content_plan_items', [
            'item_id' => $content->item_id,
        ]);

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'DELETE /api/content/{id}',
        ]);
    }

    /** @test */
    public function it_can_schedule_content()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $content = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Content to Schedule',
            'status' => 'draft',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/content/{$content->item_id}/schedule", [
            'scheduled_time' => now()->addHours(2)->toDateTimeString(),
            'platform' => 'facebook',
        ]);

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'endpoint' => 'POST /api/content/{id}/schedule',
        ]);
    }

    /** @test */
    public function it_can_filter_by_status()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Draft 1',
            'status' => 'draft',
        ]);

        ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Published 1',
            'status' => 'published',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/content?status=draft');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'test' => 'status_filtering',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/content', [
            'body' => 'Content without title',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'test' => 'validation',
        ]);
    }

    /** @test */
    public function it_respects_org_isolation()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $content1 = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'title' => 'Org 1 Content',
            'status' => 'draft',
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        $response = $this->getJson("/api/content/{$content1->item_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'test' => 'org_isolation',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/content');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'ContentController',
            'test' => 'authentication_required',
        ]);
    }
}
