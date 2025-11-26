<?php

namespace Tests\Unit\Models\Platform;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Platform\PlatformConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * PlatformConnection Model Unit Tests
 */
class PlatformConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_platform_connection()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
        ]);

        $this->assertDatabaseHas('cmis.platform_connections', [
            'connection_id' => $connection->connection_id,
            'platform' => 'facebook',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'status' => 'connected',
        ]);

        $this->assertEquals($org->org_id, $connection->org->org_id);
    }

    #[Test]
    public function it_has_different_platforms()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube'];

        foreach ($platforms as $platform) {
            PlatformConnection::create([
                'connection_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'platform' => $platform,
                'status' => 'connected',
            ]);
        }

        $connections = PlatformConnection::where('org_id', $org->org_id)->get();
        $this->assertCount(6, $connections);
    }

    #[Test]
    public function it_has_different_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connected = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
        ]);

        $disconnected = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'status' => 'disconnected',
        ]);

        $expired = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'expired',
        ]);

        $this->assertEquals('connected', $connected->status);
        $this->assertEquals('disconnected', $disconnected->status);
        $this->assertEquals('expired', $expired->status);
    }

    #[Test]
    public function it_stores_access_token()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
            'access_token' => 'encrypted_token_123',
        ]);

        $this->assertNotNull($connection->access_token);
    }

    #[Test]
    public function it_stores_refresh_token()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'google',
            'status' => 'connected',
            'access_token' => 'access_token_123',
            'refresh_token' => 'refresh_token_456',
        ]);

        $this->assertNotNull($connection->refresh_token);
    }

    #[Test]
    public function it_tracks_token_expiry()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'linkedin',
            'status' => 'connected',
            'access_token' => 'token_123',
            'expires_at' => now()->addDays(60),
        ]);

        $this->assertNotNull($connection->expires_at);
        $this->assertTrue($connection->expires_at->isFuture());
    }

    #[Test]
    public function it_stores_platform_account_id()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
            'platform_account_id' => 'fb_page_123456789',
        ]);

        $this->assertEquals('fb_page_123456789', $connection->platform_account_id);
    }

    #[Test]
    public function it_stores_platform_account_name()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'status' => 'connected',
            'platform_account_name' => 'شركة_التسويق_الرقمي',
        ]);

        $this->assertEquals('شركة_التسويق_الرقمي', $connection->platform_account_name);
    }

    #[Test]
    public function it_stores_additional_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'followers_count' => 15000,
            'verified' => true,
            'business_account' => true,
        ];

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'status' => 'connected',
            'metadata' => $metadata,
        ]);

        $this->assertEquals(15000, $connection->metadata['followers_count']);
        $this->assertTrue($connection->metadata['verified']);
    }

    #[Test]
    public function it_tracks_last_synced_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
            'last_synced_at' => now(),
        ]);

        $this->assertNotNull($connection->last_synced_at);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
        ]);

        $this->assertTrue(Str::isUuid($connection->connection_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $connection = PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'status' => 'connected',
        ]);

        $this->assertNotNull($connection->created_at);
        $this->assertNotNull($connection->updated_at);
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

        PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
        ]);

        PlatformConnection::create([
            'connection_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'facebook',
            'status' => 'connected',
        ]);

        $org1Connections = PlatformConnection::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Connections);
    }
}
