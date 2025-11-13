<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncAnalyticsMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:sync-metrics
                            {--campaign= : Specific campaign ID to sync}
                            {--date= : Specific date to sync (YYYY-MM-DD)}
                            {--days= : Number of days to sync (default: 7)}
                            {--force : Force resync even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync analytics metrics from external platforms and calculate aggregations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 Starting analytics metrics sync...');

        $campaignId = $this->option('campaign');
        $date = $this->option('date');
        $days = $this->option('days') ?? 7;
        $force = $this->option('force');

        try {
            // Determine date range
            if ($date) {
                $startDate = Carbon::parse($date);
                $endDate = Carbon::parse($date);
            } else {
                $endDate = Carbon::today();
                $startDate = Carbon::today()->subDays($days);
            }

            $this->info("📅 Syncing metrics from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

            // Get campaigns to sync
            $campaigns = $this->getCampaignsToSync($campaignId);

            if ($campaigns->isEmpty()) {
                $this->warn('⚠️  No campaigns found to sync');
                return Command::SUCCESS;
            }

            $this->info("🎯 Processing {$campaigns->count()} campaign(s)");

            $bar = $this->output->createProgressBar($campaigns->count());
            $bar->start();

            $successCount = 0;
            $failCount = 0;

            foreach ($campaigns as $campaign) {
                try {
                    // Sync campaign metrics using database function
                    $result = DB::select("
                        SELECT cmis_analytics.sync_campaign_metrics(?, ?, ?) as metrics_synced
                    ", [
                        $campaign->campaign_id,
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d')
                    ]);

                    if (($result[0]->metrics_synced ?? 0) > 0) {
                        $successCount++;

                        // Calculate aggregations
                        DB::select("
                            SELECT cmis_analytics.calculate_campaign_aggregations(?) as success
                        ", [$campaign->campaign_id]);
                    } else {
                        $failCount++;
                        Log::warning("No metrics synced for campaign: {$campaign->campaign_id}");
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Error syncing metrics for campaign {$campaign->campaign_id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Update dashboard cache
            $this->info('🔄 Updating dashboard cache...');
            $this->call('cache:forget', ['key' => 'dashboard:metrics']);

            $this->info("✅ Successfully synced: {$successCount} campaign(s)");
            if ($failCount > 0) {
                $this->error("❌ Failed to sync: {$failCount} campaign(s)");
            }

            $this->info('✨ Metrics sync completed!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during metrics sync: ' . $e->getMessage());
            Log::error('Metrics sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Get campaigns to sync based on options
     */
    private function getCampaignsToSync($campaignId)
    {
        $query = DB::table('cmis.campaigns')
            ->select('campaign_id', 'campaign_name')
            ->where('is_active', true);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        return $query->get();
    }
}
