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
use App\Jobs\Sync\DispatchPlatformSyncs;
use App\Jobs\Social\ProcessScheduledPostsJob;
use App\Jobs\Social\RefreshExpiredTokensJob;
use App\Jobs\Social\SyncPlatformAnalyticsJob;
use App\Jobs\FetchMetaAssetsJob;
use App\Jobs\AuditProfileConnectionSyncJob;
use App\Jobs\Platform\SyncPlatformAssetsJob;
use App\Jobs\Platform\VerifyAssetAccessJob;
use App\Jobs\Platform\CleanupStaleAssetsJob;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DbExecuteSql::class,
        SyncInstagramData::class,
        InstagramApiCommand::class,
        CognitiveVitalityLog::class,
        CognitiveVitalityWatch::class,
        \App\Console\Commands\ProcessEmbeddingsCommand::class,
        \App\Console\Commands\PublishScheduledPostsCommand::class,
        \App\Console\Commands\PublishScheduledSocialPostsCommand::class, // NEW: Social Publishing Fix
        \App\Console\Commands\CheckExpiringTokensCommand::class, // NEW: Week 2 - Token Expiry Monitoring
        \App\Console\Commands\Database\BackupDatabaseCommand::class, // NEW: Week 4 - Database Backups
        \App\Console\Commands\Database\RestoreDatabaseCommand::class, // NEW: Week 4 - Database Restoration
        \App\Console\Commands\Database\AuditForeignKeysCommand::class, // NEW: Week 4 - Foreign Key Audit
        \App\Console\Commands\SyncPlatformsCommand::class,
        \App\Console\Commands\CleanupCacheCommand::class,
        \App\Console\Commands\CleanupExpiredSessionsCommand::class,
        \App\Console\Commands\GenerateAnalyticsReportCommand::class,
        \App\Console\Commands\GenerateReportsCommand::class,
        \App\Console\Commands\ProcessScheduledPostsCommand::class,
        \App\Console\Commands\SyncIntegrationsCommand::class,
        \App\Console\Commands\ManagePartitions::class,

        // Vector Embeddings v2.0 Commands
        \App\Console\Commands\VectorEmbeddings\ProcessEmbeddingQueueCommand::class,
        \App\Console\Commands\VectorEmbeddings\HybridSearchCommand::class,
        \App\Console\Commands\VectorEmbeddings\SystemStatusCommand::class,

        // Marketing Automation Commands
        \App\Console\Commands\ProcessAutomationSchedulesCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // ==========================================
        // 🔄 CMIS Platform Sync & Processing (Phase 2)
        // ==========================================

        // Auto-sync platform metrics every hour
        $schedule->job(new DispatchPlatformSyncs('metrics'))
            ->hourly()
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('✅ Platform metrics sync dispatched');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to dispatch metrics sync');
            });

        // Auto-sync campaigns every 4 hours
        $schedule->job(new DispatchPlatformSyncs('campaigns'))
            ->everyFourHours()
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('✅ Campaign sync dispatched');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to dispatch campaign sync');
            });

        // Full sync daily at 2 AM
        $schedule->job(new DispatchPlatformSyncs('all'))
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('✅ Full platform sync dispatched');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to dispatch full sync');
            });

        // Publish scheduled posts every 5 minutes
        $schedule->command('cmis:publish-scheduled')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('✅ Scheduled posts published successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to publish scheduled posts');
            });

        // 🚀 NEW: Publish scheduled social media posts every minute
        $schedule->command('social:publish-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/social-publishing.log'))
            ->onSuccess(function () {
                Log::info('✅ Social media posts published successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to publish social media posts');
            });

        // ==========================================
        // 📱 Social Media Publishing & Analytics
        // ==========================================

        // Process scheduled posts queue every minute
        $schedule->job(new ProcessScheduledPostsJob())
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Scheduled posts processed successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to process scheduled posts');
            });

        // Refresh expired OAuth tokens every 30 minutes
        // More frequent than hourly since Google tokens expire in 1 hour
        $schedule->job(new RefreshExpiredTokensJob())
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Platform tokens refreshed successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to refresh platform tokens');
            });

        // Sync platform analytics every 30 minutes
        $schedule->job(new SyncPlatformAnalyticsJob())
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Platform analytics synced successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to sync platform analytics');
            });

        // ==========================================
        // 📦 Meta Assets Cache Warm-up (NEW)
        // ==========================================

        // Pre-fetch Meta assets every hour to warm up cache
        // This ensures instant loading when users visit the Meta Assets page
        $schedule->job(new FetchMetaAssetsJob())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Meta assets cache warmed up successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to warm up Meta assets cache');
            });

        // 🔐 NEW Week 2: Check for expiring integration tokens daily at 9 AM
        $schedule->command('integrations:check-expiring-tokens --days=7')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/token-monitoring.log'))
            ->onSuccess(function () {
                Log::info('✅ Token expiry check completed successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to check expiring tokens');
            });

        // Process embedding queue every 15 minutes
        $schedule->command('cmis:process-embeddings --batch=20')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('✅ Embeddings processed successfully');
            });

        // Sync platforms hourly
        $schedule->command('cmis:sync-platforms --type=metrics')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('✅ Platform metrics synced successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Platform sync failed');
            });

        // Full platform sync daily at 3 AM
        $schedule->command('cmis:sync-platforms --type=full')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('✅ Full platform sync completed');
            });

        // Clean up stale cache weekly on Sundays at 4 AM
        $schedule->command('cmis:cleanup-cache --days=30')
            ->weekly()
            ->sundays()
            ->at('04:00')
            ->onSuccess(function () {
                Log::info('✅ Cache cleanup completed');
            });

        // ==========================================
        // 🤖 Marketing Automation (NEW)
        // ==========================================

        // Process automation schedules every 5 minutes
        $schedule->command('automation:process-schedules --limit=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('✅ Automation schedules processed successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to process automation schedules');
            });

        // ==========================================
        // 💾 Database Backups (NEW: Week 4)
        // ==========================================

        // Full database backup daily at 2 AM
        $schedule->command('db:backup --no-interaction')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/database-backups.log'))
            ->onSuccess(function () {
                Log::info('✅ Daily database backup completed');
            })
            ->onFailure(function () {
                Log::error('❌ Daily database backup failed');
            });

        // Schema-specific backup (cmis schema only) every 6 hours
        $schedule->command('db:backup --schema=cmis --no-interaction')
            ->everySixHours()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/database-backups.log'))
            ->onSuccess(function () {
                Log::info('✅ Schema backup completed (cmis)');
            });

        // ==========================================
        // 🔗 Profile-Connection Sync Audit (NEW)
        // ==========================================

        // Audit and fix profile-connection-asset sync issues every 6 hours
        // Detects and fixes: orphaned profiles, missing profiles, deselected
        // profiles, profiles to restore, and stale queue settings
        $schedule->job(new AuditProfileConnectionSyncJob(null, true), 'maintenance')
            ->everySixHours()
            ->withoutOverlapping(60)
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Profile-connection sync audit completed');
            })
            ->onFailure(function () {
                Log::error('❌ Profile-connection sync audit failed');
            });

        // ==========================================
        // 🗃️ Platform Assets Database Persistence (NEW)
        // ==========================================

        // Sync platform assets to database every 6 hours
        // Three-tier caching: Cache (15min) → Database (6hr) → API
        $schedule->job(new SyncPlatformAssetsJob(), 'asset-sync')
            ->everySixHours()
            ->withoutOverlapping(120)
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Platform assets synced to database');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to sync platform assets');
            });

        // Verify org asset access records daily at 4 AM
        // Checks connection validity, asset existence, cleans orphans
        $schedule->job(new VerifyAssetAccessJob(), 'maintenance')
            ->dailyAt('04:00')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Asset access verification completed');
            })
            ->onFailure(function () {
                Log::error('❌ Asset access verification failed');
            });

        // Cleanup stale assets weekly on Sundays at 5 AM
        // Marks inactive, hard-deletes old records, optimizes tables
        $schedule->job(new CleanupStaleAssetsJob(), 'maintenance')
            ->weekly()
            ->sundays()
            ->at('05:00')
            ->withoutOverlapping(60)
            ->onOneServer()
            ->onSuccess(function () {
                Log::info('✅ Stale assets cleanup completed');
            })
            ->onFailure(function () {
                Log::error('❌ Stale assets cleanup failed');
            });

        // ==========================================
        // 📊 Database Partition Management (Phase 4)
        // ==========================================

        // Manage partitions monthly (create future, cleanup old)
        $schedule->command('partitions:manage')
            ->monthlyOn(1, '05:00')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('✅ Database partitions managed successfully');
            })
            ->onFailure(function () {
                Log::error('❌ Failed to manage database partitions');
            });

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
        ->name('cognitive-health-report')
        ->dailyAt('02:00')
        ->onOneServer()
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
        ->name('cognitive-morning-email')
        ->dailyAt('08:00')
        ->onOneServer()
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
