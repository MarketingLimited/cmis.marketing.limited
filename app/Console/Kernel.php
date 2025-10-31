<?php

namespace App\Console;

use App\Console\Commands\InstagramApiCommand;
use App\Console\Commands\SyncInstagramData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SyncInstagramData::class,
        InstagramApiCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('instagram:api marketing.limited media --limit=100 --sort=desc')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Instagram daily sync executed successfully at ' . now());
                DB::table('cmis.sync_logs')->insert([
                    'org_id' => config('app.current_org_id'),
                    'source' => 'instagram',
                    'status' => 'success',
                    'message' => 'Daily sync completed successfully',
                    'created_at' => now(),
                ]);
            })
            ->onFailure(function () {
                static $attempts = 0;
                $attempts++;

                if ($attempts < 3) {
                    Log::warning("⚠️ Instagram sync failed (attempt {$attempts}), retrying...");
                    $delay = $attempts * 10;
                    Artisan::queue('instagram:api marketing.limited media --limit=100 --sort=desc')
                        ->delay(now()->addMinutes($delay));
                } else {
                    Log::error('❌ Instagram sync failed 3 times consecutively at ' . now());
                    DB::table('cmis.sync_logs')->insert([
                        'org_id' => config('app.current_org_id'),
                        'source' => 'instagram',
                        'status' => 'failed',
                        'message' => 'Sync failed 3 consecutive times. Manual intervention required.',
                        'created_at' => now(),
                    ]);
                }
            });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
