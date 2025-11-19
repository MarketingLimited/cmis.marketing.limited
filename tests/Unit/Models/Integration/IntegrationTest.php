<?php

namespace Tests\Unit\Models\Integration;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Integration\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

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

    /** @test */
    public function it_can_create_integration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page Integration',
            'credentials' => [
                'access_token' => 'test_token',
                'page_id' => 'page_123',
            ],
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('cmis.integrations', [
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'name' => 'Instagram Business',
            'credentials' => [],
        ]);

        $this->assertEquals($org->org_id, $integration->org->org_id);
    }

    /** @test */
    public function it_encrypts_credentials()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $credentials = [
            'access_token' => 'sensitive_token_123',
            'client_secret' => 'secret_456',
        ];

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'credentials' => $credentials,
        ]);

        // Credentials should be accessible as array (auto-decrypted)
        $this->assertEquals('sensitive_token_123', $integration->credentials['access_token']);
        $this->assertEquals('secret_456', $integration->credentials['client_secret']);
    }

    /** @test */
    public function it_supports_multiple_platforms()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'];

        foreach ($platforms as $platform) {
            Integration::create([
                'integration_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'platform' => $platform,
                'name' => ucfirst($platform) . ' Integration',
                'credentials' => [],
            ]);
        }

        $integrations = Integration::where('org_id', $org->org_id)->get();
        $this->assertCount(6, $integrations);
    }

    /** @test */
    public function it_can_be_activated_or_deactivated()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [],
            'is_active' => true,
        ]);

        $this->assertTrue($integration->is_active);

        $integration->update(['is_active' => false]);

        $this->assertFalse($integration->fresh()->is_active);
    }

    /** @test */
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'page_name' => 'My Business Page',
            'page_category' => 'Local Business',
            'followers_count' => 15000,
            'verified' => true,
        ];

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [],
            'metadata' => $metadata,
        ]);

        $this->assertEquals('My Business Page', $integration->metadata['page_name']);
        $this->assertEquals(15000, $integration->metadata['followers_count']);
        $this->assertTrue($integration->metadata['verified']);
    }

    /** @test */
    public function it_tracks_last_sync_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'name' => 'Instagram Business',
            'credentials' => [],
            'last_synced_at' => now(),
        ]);

        $this->assertNotNull($integration->last_synced_at);
    }

    /** @test */
    public function it_tracks_token_expiry()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [
                'access_token' => 'token_123',
            ],
            'token_expires_at' => now()->addDays(60),
        ]);

        $this->assertNotNull($integration->token_expires_at);
        $this->assertTrue($integration->token_expires_at->isFuture());
    }

    /** @test */
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
            'name' => 'Expired Integration',
            'credentials' => [],
            'token_expires_at' => now()->subDays(1),
        ]);

        $validIntegration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'name' => 'Valid Integration',
            'credentials' => [],
            'token_expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($expiredIntegration->token_expires_at->isPast());
        $this->assertTrue($validIntegration->token_expires_at->isFuture());
    }

    /** @test */
    public function it_stores_connection_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'credentials' => [],
            'connection_status' => 'connected',
        ]);

        $this->assertEquals('connected', $integration->connection_status);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'linkedin',
            'name' => 'LinkedIn Page',
            'credentials' => [],
        ]);

        $this->assertTrue(Str::isUuid($integration->integration_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'youtube',
            'name' => 'YouTube Channel',
            'credentials' => [],
        ]);

        $this->assertNotNull($integration->created_at);
        $this->assertNotNull($integration->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $integration = Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'tiktok',
            'name' => 'TikTok Account',
            'credentials' => [],
        ]);

        $integrationId = $integration->integration_id;

        $integration->delete();

        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integrationId,
        ]);
    }

    /** @test */
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
            'platform' => 'facebook',
            'name' => 'Org 1 Integration',
            'credentials' => [],
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'instagram',
            'name' => 'Org 2 Integration',
            'credentials' => [],
        ]);

        $org1Integrations = Integration::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Integrations);
        $this->assertEquals('Org 1 Integration', $org1Integrations->first()->name);
    }
}
