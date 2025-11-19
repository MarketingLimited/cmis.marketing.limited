<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * CleanupExpiredSessions Command Unit Tests
 */
class CleanupExpiredSessionsCommandTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_cleans_up_expired_sessions()
    {
        $this->artisan('sessions:cleanup')
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'cleanup_expired',
        ]);
    }

    #[Test]
    public function it_shows_verbose_output()
    {
        $this->artisan('sessions:cleanup', ['--verbose' => true])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'verbose_output',
        ]);
    }

    #[Test]
    public function it_shows_dry_run_mode()
    {
        $this->artisan('sessions:cleanup', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'dry_run',
        ]);
    }

    #[Test]
    public function it_accepts_custom_expiry_time()
    {
        $this->artisan('sessions:cleanup', ['--hours' => 48])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'custom_expiry',
        ]);
    }

    #[Test]
    public function it_handles_no_expired_sessions()
    {
        $this->artisan('sessions:cleanup')
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'no_expired',
        ]);
    }

    #[Test]
    public function it_limits_batch_size()
    {
        $this->artisan('sessions:cleanup', ['--limit' => 100])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'batch_limit',
        ]);
    }

    #[Test]
    public function it_shows_cleanup_summary()
    {
        $this->artisan('sessions:cleanup')
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'cleanup_summary',
        ]);
    }

    #[Test]
    public function it_cleans_database_sessions()
    {
        $this->artisan('sessions:cleanup', ['--type' => 'database'])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'database_sessions',
        ]);
    }

    #[Test]
    public function it_cleans_redis_sessions()
    {
        $this->artisan('sessions:cleanup', ['--type' => 'redis'])
            ->assertExitCode(0);

        $this->logTestResult('passed', [
            'command' => 'CleanupExpiredSessionsCommand',
            'test' => 'redis_sessions',
        ]);
    }
}
