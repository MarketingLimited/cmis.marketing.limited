<?php

namespace App\Console;

use App\Console\Commands\InstagramApiCommand;
use App\Console\Commands\SyncInstagramData;
use App\Console\Commands\CognitiveVitalityLog;
use App\Console\Commands\CognitiveVitalityWatch;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DbExecuteSql::class,
        SyncInstagramData::class,
        InstagramApiCommand::class,
        CognitiveVitalityLog::class,
        CognitiveVitalityWatch::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // ==========================================
        // 🔄 NEW: Multi-Platform Sync Commands
        // ==========================================

        // Auto-sync all platforms hourly
        $schedule->command('sync:all')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Daily embeddings generation at 2 AM
        $schedule->command('embeddings:generate')
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Weekly database cleanup on Sundays at 3 AM
        $schedule->command('database:cleanup')
            ->weekly()
            ->sundays()
            ->at('03:00');

        // Daily system health check
        $schedule->command('system:health')
            ->daily()
            ->appendOutputTo(storage_path('logs/health-check.log'));

        // ==========================================
        // 🧠 Original Cognitive Vitality Monitoring
        // ==========================================

        // 🔁 المهمة الأساسية لمزامنة إنستغرام اليومية
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

        // 🧠 تسجيل الحيوية الإدراكية كل ساعة
        $schedule->command('cognitive:vitality-hourly')
            ->hourly()
            ->onOneServer()
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('🧠 تم تسجيل قراءة جديدة للحيوية الإدراكية بنجاح في ' . now());
            })
            ->onFailure(function () {
                Log::warning('⚠️ فشل تسجيل الحيوية الإدراكية في ' . now());
            });

        // 🔍 مراقبة نبض الوعي الإدراكي كل ساعتين
        $schedule->command('cognitive:vitality-watch')
            ->everyTwoHours()
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('🔍 فحص الوعي الإدراكي تم بنجاح في ' . now());
            })
            ->onFailure(function () {
                Log::warning('⚠️ فشل فحص الوعي الإدراكي في ' . now());
            });

        // 📄 إنشاء التقرير الإدراكي الذاتي كل 24 ساعة
        $schedule->call(function () {
            DB::statement('SELECT generate_cognitive_health_report();');
            Log::info('🧠 تم توليد التقرير الإدراكي الدوري بنجاح في ' . now());
            DB::table('cmis_audit.logs')->insert([
                'event_type' => 'cognitive_report',
                'event_source' => 'CognitiveHealthReport',
                'description' => '📄 تم توليد التقرير الإدراكي الدوري تلقائيًا.',
                'created_at' => now(),
            ]);
        })
        ->dailyAt('02:00')
        ->onOneServer()
        ->withoutOverlapping()
        ->onFailure(function () {
            Log::warning('⚠️ فشل توليد التقرير الإدراكي الدوري في ' . now());
        });

        // 📬 إرسال التقرير الإدراكي الصباحي إلى الإدارة كل يوم الساعة 08:00
        $schedule->call(function () {
            $report = DB::table('cmis_system_health.cognitive_reports')
                ->orderByDesc('created_at')
                ->limit(1)
                ->first();

            if ($report) {
                $toAddress = config('mail.from.address', 'info@marketing.limited');

                Mail::raw($report->report_text, function ($message) use ($toAddress) {
                    $message->to($toAddress)
                        ->subject('🧠 التقرير الإدراكي الصباحي | CMIS Cognitive Health');
                });

                DB::table('cmis_audit.logs')->insert([
                    'event_type' => 'cognitive_notification',
                    'event_source' => 'CognitiveHealthMailer',
                    'description' => '📤 تم إرسال التقرير الإدراكي إلى الإدارة صباحًا.',
                    'created_at' => now(),
                ]);
            }
        })
        ->dailyAt('08:00')
        ->onOneServer()
        ->withoutOverlapping()
        ->onFailure(function () {
            Log::warning('⚠️ فشل إرسال التقرير الإدراكي الصباحي في ' . now());
        });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
