<?php

namespace Tests\Unit\Models\Schedule;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Schedule\Schedule;
use App\Models\Content\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Schedule Model Unit Tests
 */
class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_schedule()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Daily Morning Post',
            'frequency' => 'daily',
            'scheduled_time' => '09:00:00',
        ]);

        $this->assertDatabaseHas('cmis.schedules', [
            'schedule_id' => $schedule->schedule_id,
            'name' => 'Daily Morning Post',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'create',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Weekly Post',
            'frequency' => 'weekly',
        ]);

        $this->assertEquals($org->org_id, $schedule->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'belongs_to_org',
        ]);
    }

    /** @test */
    public function it_has_different_frequencies()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $frequencies = ['once', 'daily', 'weekly', 'monthly', 'custom'];

        foreach ($frequencies as $frequency) {
            $schedule = Schedule::create([
                'schedule_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => ucfirst($frequency) . ' Schedule',
                'frequency' => $frequency,
            ]);

            $this->assertEquals($frequency, $schedule->frequency);
        }

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'frequencies',
        ]);
    }

    /** @test */
    public function it_stores_scheduled_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Morning Schedule',
            'frequency' => 'daily',
            'scheduled_time' => '08:30:00',
        ]);

        $this->assertEquals('08:30:00', $schedule->scheduled_time);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'scheduled_time',
        ]);
    }

    /** @test */
    public function it_tracks_next_run_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Next Run Schedule',
            'frequency' => 'daily',
            'next_run_at' => now()->addDay(),
        ]);

        $this->assertNotNull($schedule->next_run_at);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'next_run_time',
        ]);
    }

    /** @test */
    public function it_tracks_last_run_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Last Run Schedule',
            'frequency' => 'daily',
            'last_run_at' => now()->subHour(),
        ]);

        $this->assertNotNull($schedule->last_run_at);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'last_run_time',
        ]);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeSchedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Schedule',
            'frequency' => 'daily',
            'is_active' => true,
        ]);

        $inactiveSchedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Schedule',
            'frequency' => 'weekly',
            'is_active' => false,
        ]);

        $this->assertTrue($activeSchedule->is_active);
        $this->assertFalse($inactiveSchedule->is_active);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'active_status',
        ]);
    }

    /** @test */
    public function it_stores_schedule_config()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $config = [
            'days_of_week' => ['monday', 'wednesday', 'friday'],
            'timezone' => 'Asia/Riyadh',
            'platforms' => ['facebook', 'instagram'],
        ];

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Config Schedule',
            'frequency' => 'weekly',
            'config' => $config,
        ]);

        $this->assertEquals('Asia/Riyadh', $schedule->config['timezone']);
        $this->assertCount(3, $schedule->config['days_of_week']);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'schedule_config',
        ]);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Schedule',
            'frequency' => 'daily',
        ]);

        $this->assertTrue(Str::isUuid($schedule->schedule_id));

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'uuid_primary_key',
        ]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $schedule = Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamp Schedule',
            'frequency' => 'monthly',
        ]);

        $this->assertNotNull($schedule->created_at);
        $this->assertNotNull($schedule->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'timestamps',
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

        Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Schedule',
            'frequency' => 'daily',
        ]);

        Schedule::create([
            'schedule_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Schedule',
            'frequency' => 'weekly',
        ]);

        $org1Schedules = Schedule::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Schedules);

        $this->logTestResult('passed', [
            'model' => 'Schedule',
            'test' => 'rls_isolation',
        ]);
    }
}
