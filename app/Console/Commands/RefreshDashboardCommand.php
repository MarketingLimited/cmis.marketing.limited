<?php

namespace App\Console\Commands;

use App\Repositories\CMIS\CacheRepository;
use App\Repositories\Analytics\AnalyticsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Refresh dashboard metrics and performance data
 */
class RefreshDashboardCommand extends Command
{
    protected CacheRepository $cacheRepo;
    protected AnalyticsRepository $analyticsRepo;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:refresh-dashboard
                            {--days=30 : Number of days for performance snapshot}
                            {--metrics : Refresh dashboard metrics only}
                            {--social : Sync social metrics only}
                            {--performance : Refresh performance snapshot only}
                            {--all : Refresh everything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تحديث لوحة المعلومات ومقاييس الأداء';

    public function __construct(CacheRepository $cacheRepo, AnalyticsRepository $analyticsRepo)
    {
        parent::__construct();
        $this->cacheRepo = $cacheRepo;
        $this->analyticsRepo = $analyticsRepo;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $refreshMetrics = $this->option('metrics');
        $syncSocial = $this->option('social');
        $refreshPerformance = $this->option('performance');
        $refreshAll = $this->option('all');

        $this->info('بدء تحديث لوحة المعلومات...');

        try {
            // Refresh dashboard metrics
            if ($refreshMetrics || $refreshAll || (!$syncSocial && !$refreshPerformance)) {
                $this->info('تحديث مقاييس لوحة المعلومات...');
                $result = $this->cacheRepo->refreshDashboardMetrics();
                if ($result) {
                    $this->info('✓ تم تحديث مقاييس لوحة المعلومات بنجاح');
                }
            }

            // Sync social metrics
            if ($syncSocial || $refreshAll) {
                $this->info('مزامنة مقاييس وسائل التواصل الاجتماعي...');
                $result = $this->cacheRepo->syncSocialMetrics();
                if ($result) {
                    $this->info('✓ تمت مزامنة مقاييس وسائل التواصل الاجتماعي بنجاح');
                }
            }

            // Refresh performance snapshot
            if ($refreshPerformance || $refreshAll) {
                $this->info("تحديث لقطة الأداء (آخر {$days} يوماً)...");
                $snapshot = $this->analyticsRepo->snapshotPerformanceForDays($days);
                $this->info("✓ تم تحديث لقطة الأداء بنجاح ({$snapshot->count()} سجل)");
            }

            // Refresh required fields cache
            if ($refreshAll) {
                $this->info('تحديث ذاكرة التخزين المؤقت للحقول المطلوبة...');
                $result = $this->cacheRepo->refreshRequiredFieldsCacheWithMetrics();
                if ($result) {
                    $this->info('✓ تم تحديث ذاكرة التخزين المؤقت للحقول المطلوبة بنجاح');
                }
            }

            $this->newLine();
            $this->info('اكتمل تحديث لوحة المعلومات بنجاح!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Refresh dashboard command failed: ' . $e->getMessage());
            $this->error('فشل تحديث لوحة المعلومات: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
