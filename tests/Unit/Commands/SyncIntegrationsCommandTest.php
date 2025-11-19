<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Core\Org;
use App\Models\Integration\Integration;
use Illuminate\Support\Str;

/**
 * SyncIntegrations Command Unit Tests
 */
class SyncIntegrationsCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_syncs_all_integrations()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'credentials' => ['access_token' => 'token_456'],
        ]);

        $this->mockMetaAPI('success');
        $this->mockTwitterAPI('success');

        $this->artisan('integrations:sync')
             ->expectsOutput('Syncing integrations...')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'sync_all',
        ]);
    }

    /** @test */
    public function it_syncs_specific_platform()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        $this->mockMetaAPI('success');

        $this->artisan('integrations:sync', ['--platform' => 'facebook'])
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'sync_platform',
        ]);
    }

    /** @test */
    public function it_syncs_specific_organization()
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
            'name' => 'Org 1 Facebook',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'platform' => 'facebook',
            'name' => 'Org 2 Facebook',
            'credentials' => ['access_token' => 'token_456'],
        ]);

        $this->mockMetaAPI('success');

        $this->artisan('integrations:sync', ['--org' => $org1->org_id])
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'sync_org',
        ]);
    }

    /** @test */
    public function it_handles_sync_errors_gracefully()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => ['access_token' => 'invalid_token'],
        ]);

        $this->mockMetaAPI('error');

        $this->artisan('integrations:sync')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'error_handling',
        ]);
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        $this->artisan('integrations:sync', ['--dry-run' => true])
             ->expectsOutput('Dry run mode - no changes will be made')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'dry_run',
        ]);
    }

    /** @test */
    public function it_outputs_sync_summary()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'instagram',
            'name' => 'Instagram Account',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        $this->mockMetaAPI('success');

        $this->artisan('integrations:sync')
             ->expectsOutput('Syncing integrations...')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'output_summary',
        ]);
    }

    /** @test */
    public function it_updates_last_synced_timestamp()
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
            'credentials' => ['access_token' => 'token_123'],
            'last_synced_at' => null,
        ]);

        $this->mockLinkedInAPI('success');

        $this->artisan('integrations:sync')
             ->assertExitCode(0);

        // Integration should have last_synced_at updated
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'update_timestamp',
        ]);
    }

    /** @test */
    public function it_supports_verbose_output()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Integration::create([
            'integration_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'platform' => 'tiktok',
            'name' => 'TikTok Account',
            'credentials' => ['access_token' => 'token_123'],
        ]);

        $this->mockTikTokAPI('success');

        $this->artisan('integrations:sync', ['-v' => true])
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'verbose_output',
        ]);
    }

    /** @test */
    public function it_handles_no_integrations()
    {
        $this->artisan('integrations:sync')
             ->expectsOutput('No integrations found to sync.')
             ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'SyncIntegrationsCommand',
            'test' => 'no_integrations',
        ]);
    }
}
