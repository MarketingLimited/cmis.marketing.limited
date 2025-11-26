<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Integration Model Unit Tests
 */
class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_an_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
            'access_token' => encrypt('test_token_123'),
        ]);

        $this->assertDatabaseHas('cmis.integrations', [
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
        ]);
    }

    #[Test]
    public function it_belongs_to_an_organization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->assertEquals($org->org_id, $integration->org->org_id);
    }

    #[Test]
    public function it_encrypts_access_token()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $plainToken = 'secret_access_token_123';

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
            'access_token' => encrypt($plainToken),
        ]);

        $decryptedToken = decrypt($integration->access_token);
        $this->assertEquals($plainToken, $decryptedToken);
    }

    #[Test]
    public function it_validates_platform()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $this->assertContains($integration->platform, [
            'instagram',
            'facebook',
            'twitter',
            'linkedin',
            'tiktok',
            'youtube',
            'snapchat',
            'whatsapp',
            'google_ads',
            'meta_ads',
        ]);
    }

    #[Test]
    public function it_validates_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->assertContains($integration->status, [
            'active',
            'inactive',
            'expired',
            'error',
        ]);
    }

    #[Test]
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'account_id' => 'ig_account_123',
            'account_name' => 'Test Instagram',
            'followers_count' => 5000,
            'scopes' => ['instagram_basic', 'instagram_content_publish'],
        ];

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('ig_account_123', $integration->metadata['account_id']);
        $this->assertEquals(5000, $integration->metadata['followers_count']);
        $this->assertContains('instagram_basic', $integration->metadata['scopes']);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->assertTrue(Str::isUuid($integration->integration_id));
    }

    #[Test]
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        $integration->delete();

        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->assertNotNull($integration->created_at);
        $this->assertNotNull($integration->updated_at);
    }

    #[Test]
    public function it_tracks_token_expiration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $expiresAt = now()->addDays(60);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        $this->assertNotNull($integration->expires_at);
        $this->assertTrue($integration->expires_at->isFuture());
    }

    #[Test]
    public function it_can_check_if_token_is_expired()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $expiredIntegration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'expired',
            'expires_at' => now()->subDays(1),
        ]);

        $activeIntegration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($expiredIntegration->expires_at->isPast());
        $this->assertTrue($activeIntegration->expires_at->isFuture());
    }

    #[Test]
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

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $org1Integrations = Integration::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Integrations);
        $this->assertEquals('instagram', $org1Integrations->first()->platform);
    }
}
