<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Creative\CreativeAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use PHPUnit\Framework\Attributes\Test;
/**
 * Creative Asset Controller Authorization Tests
 * Tests authentication and authorization for all CreativeAssetController endpoints
 */
class CreativeAssetControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[Test]
    public function it_requires_authentication_for_listing_assets()
    {
        $response = $this->getJson('/api/orgs/org-123/creative/assets');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'index',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_list_assets_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/creative/assets");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'index',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_creating_asset()
    {
        $response = $this->postJson('/api/orgs/org-123/creative/assets', [
            'name' => 'Test Asset',
            'type' => 'image',
            'file' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'store',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_showing_asset()
    {
        $response = $this->getJson('/api/orgs/org-123/creative/assets/asset-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'show',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_can_show_asset_with_authentication()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];
        $org = $setup['org'];

        $asset = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Asset',
            'type' => 'image',
            'file_path' => 'test/path.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'active',
            'created_by' => $user->user_id,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson("/api/orgs/{$org->org_id}/creative/assets/{$asset->asset_id}");

        $response->assertStatus(200);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'show',
            'test' => 'authenticated_access',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_updating_asset()
    {
        $response = $this->putJson('/api/orgs/org-123/creative/assets/asset-123', [
            'name' => 'Updated Asset',
        ]);

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'update',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_deleting_asset()
    {
        $response = $this->deleteJson('/api/orgs/org-123/creative/assets/asset-123');

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'destroy',
            'test' => 'authentication_required',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_asset_list()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $asset1 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'name' => 'Org1 Asset',
            'type' => 'image',
            'file_path' => 'test/org1.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'active',
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's assets while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/creative/assets");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'index',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_asset_details()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $asset1 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'name' => 'Org1 Asset',
            'type' => 'image',
            'file_path' => 'test/org1.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'active',
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to access org1's asset while logged in as org2 user
        $response = $this->getJson("/api/orgs/{$setup1['org']->org_id}/creative/assets/{$asset1->asset_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'show',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_asset_update()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $asset1 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'name' => 'Org1 Asset',
            'type' => 'image',
            'file_path' => 'test/org1.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'active',
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to update org1's asset while logged in as org2 user
        $response = $this->putJson("/api/orgs/{$setup1['org']->org_id}/creative/assets/{$asset1->asset_id}", [
            'name' => 'Hacked Asset',
        ]);

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'update',
            'test' => 'org_isolation',
        ]);
    }

    #[Test]
    public function it_respects_org_isolation_for_asset_deletion()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $asset1 = CreativeAsset::create([
            'asset_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $setup1['org']->org_id,
            'name' => 'Org1 Asset',
            'type' => 'image',
            'file_path' => 'test/org1.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'status' => 'active',
            'created_by' => $setup1['user']->user_id,
        ]);

        $this->actingAs($setup2['user'], 'sanctum');

        // Try to delete org1's asset while logged in as org2 user
        $response = $this->deleteJson("/api/orgs/{$setup1['org']->org_id}/creative/assets/{$asset1->asset_id}");

        $response->assertStatus(403);

        $this->logTestResult('passed', [
            'controller' => 'CreativeAssetController',
            'method' => 'destroy',
            'test' => 'org_isolation',
        ]);
    }
}
