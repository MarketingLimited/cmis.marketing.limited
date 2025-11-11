<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\{Org, Integration};
use Illuminate\Support\Facades\{Queue, Artisan};
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\{SyncInstagramDataJob, SyncFacebookDataJob, SyncMetaAdsJob};

class SyncCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
    }

    public function test_sync_instagram_command_dispatches_jobs_when_queue_flag_used()
    {
        Queue::fake();

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
            'account_username' => 'test_instagram',
        ]);

        $this->artisan('sync:instagram', ['--queue' => true, '--org' => [$this->org->org_id]])
            ->assertExitCode(0);

        Queue::assertPushed(SyncInstagramDataJob::class);
    }

    public function test_sync_facebook_command_dispatches_jobs_when_queue_flag_used()
    {
        Queue::fake();

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
            'account_username' => 'test_facebook',
        ]);

        $this->artisan('sync:facebook', ['--queue' => true, '--org' => [$this->org->org_id]])
            ->assertExitCode(0);

        Queue::assertPushed(SyncFacebookDataJob::class);
    }

    public function test_sync_meta_ads_command_dispatches_jobs_when_queue_flag_used()
    {
        Queue::fake();

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'meta_ads',
            'status' => 'active',
            'metadata' => ['ad_account_id' => '123456'],
        ]);

        $this->artisan('sync:meta-ads', ['--queue' => true, '--org' => [$this->org->org_id]])
            ->assertExitCode(0);

        Queue::assertPushed(SyncMetaAdsJob::class);
    }

    public function test_sync_all_command_runs_all_platform_syncs()
    {
        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->artisan('sync:all', ['--org' => [$this->org->org_id]])
            ->expectsOutput('🌐 Starting Multi-Platform Sync')
            ->assertExitCode(0);
    }

    public function test_sync_all_command_with_queue_flag_dispatches_all_jobs()
    {
        Queue::fake();

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'instagram',
            'status' => 'active',
        ]);

        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->artisan('sync:all', [
            '--queue' => true,
            '--org' => [$this->org->org_id]
        ])->assertExitCode(0);

        Queue::assertPushed(SyncInstagramDataJob::class);
        Queue::assertPushed(SyncFacebookDataJob::class);
    }

    public function test_sync_command_handles_date_range_filters()
    {
        Integration::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
            'status' => 'active',
        ]);

        $this->artisan('sync:facebook', [
            '--org' => [$this->org->org_id],
            '--from' => '2024-01-01',
            '--to' => '2024-01-31',
            '--limit' => 50,
        ])->assertExitCode(0);
    }

    public function test_sync_command_warns_when_no_active_integrations()
    {
        $this->artisan('sync:instagram', ['--org' => [$this->org->org_id]])
            ->expectsOutput('  ⚠️  No active Instagram integrations')
            ->assertExitCode(0);
    }

    public function test_database_cleanup_command_with_dry_run()
    {
        $this->artisan('database:cleanup', ['--dry-run' => true])
            ->expectsOutput('🧹 Starting Database Cleanup')
            ->assertExitCode(0);
    }

    public function test_system_health_command_checks_database()
    {
        $this->artisan('system:health')
            ->expectsOutput('🏥 System Health Check')
            ->expectsOutput('✅ Database: Connected')
            ->assertExitCode(0);
    }

    public function test_embeddings_generate_command_dispatches_job()
    {
        Queue::fake();

        $this->artisan('embeddings:generate', [
            '--queue' => true,
            '--org' => [$this->org->org_id],
            '--limit' => 50,
        ])->assertExitCode(0);

        Queue::assertPushed(\App\Jobs\GenerateEmbeddingsJob::class);
    }
}
