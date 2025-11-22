<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Console\Commands\Traits\HasDryRunMode;
use App\Console\Commands\Traits\HasProgressIndicators;
use App\Console\Commands\Traits\HasOperationSummary;
use App\Console\Commands\Traits\HasRetryLogic;
use App\Console\Commands\Traits\HasHelpfulErrors;
use App\Services\Connectors\ConnectorFactory;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;

class SyncPlatform extends Command
{
    use HandlesOrgContext;
    use HasDryRunMode;
    use HasProgressIndicators;
    use HasOperationSummary;
    use HasRetryLogic;
    use HasHelpfulErrors;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:platform {platform : The platform to sync (e.g., meta, google)}
                            {--org=* : Specify one or more organization IDs to sync}
                            {--type=all : The type of data to sync (e.g., all, campaigns, posts)}
                            {--dry-run : Preview what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs data from a specified external platform for one or all organizations.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $platform = $this->argument('platform');
        $orgIds = $this->option('org');
        $syncType = $this->option('type');

        // Setup dry-run mode (Issue #39)
        $this->setupDryRun();

        // Initialize summary tracking (Issue #43)
        $this->initSummary();

        // Register common error solutions (Issue #42)
        $this->registerCommonErrorSolutions();

        $this->info("🚀 Starting sync for platform: [{$platform}]");

        try {
            $connector = ConnectorFactory::make($platform);
        } catch (\InvalidArgumentException $e) {
            $this->handleErrorWithSolution($e, "Platform factory");
            return self::FAILURE;
        }

        $integrationsQuery = Integration::where('platform', $platform)->where('is_active', true);

        if (!empty($orgIds)) {
            $integrationsQuery->whereIn('org_id', $orgIds);
            $this->info("Targeting specific organizations: " . implode(', ', $orgIds));
        }

        $integrations = $integrationsQuery->get();

        if ($integrations->isEmpty()) {
            $this->warn("No active integrations found for platform [{$platform}].");
            return self::SUCCESS;
        }

        $this->info("Found {$integrations->count()} active integration(s) to process.");
        $this->newLine();

        // Progress bar (Issue #41)
        $this->startProgress($integrations->count(), 'Syncing integrations');

        foreach ($integrations as $integration) {
            $this->updateProgressMessage("Processing Org: {$integration->org_id}");

            try {
                // Use retry logic for transient failures (Issue #50)
                $this->withRetry(function () use ($integration, $connector, $syncType, $platform) {
                    // Set database context for this organization
                    $this->setOrgContext($integration->org_id);

                    $synced = 0;

                    if ($syncType === 'all' || $syncType === 'campaigns') {
                        if ($this->isDryRun) {
                            $this->recordAction("Sync campaigns", ['org_id' => $integration->org_id]);
                        } else {
                            $campaigns = $connector->syncCampaigns($integration);
                            $synced += $campaigns->count();
                        }
                    }

                    if ($syncType === 'all' || $syncType === 'posts') {
                        if ($this->isDryRun) {
                            $this->recordAction("Sync posts", ['org_id' => $integration->org_id]);
                        } else {
                            $posts = $connector->syncPosts($integration);
                            $synced += $posts->count();
                        }
                    }

                    return $synced;
                }, "Sync for org {$integration->org_id}");

                $this->recordSuccess("Org {$integration->org_id}");

            } catch (\Exception $e) {
                $this->recordFailure("Org {$integration->org_id}", $e->getMessage());
                Log::error("Sync failed for platform {$platform}, org {$integration->org_id}", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->advanceProgress();
        }

        $this->finishProgress('Sync complete');

        // Show dry-run summary if applicable
        if ($this->isDryRun) {
            $this->showDryRunSummary();
        }

        // Show operation summary (Issue #43)
        $this->showSummary("Platform Sync ({$platform})");

        // Return proper exit code (Issue #49)
        return $this->getExitCode();
    }

    protected function registerCommonErrorSolutions(): void
    {
        $this->registerErrorSolution(
            'invalid platform',
            'Supported platforms are: meta, google, tiktok, linkedin, twitter, snapchat. Check your spelling.'
        );
        $this->registerErrorSolution(
            'connection',
            'Check your internet connection and the platform API status.'
        );
        $this->registerErrorSolution(
            'credentials',
            'Verify the integration credentials are valid. You may need to re-authenticate.'
        );
        $this->registerErrorSolution(
            'rate limit',
            'The platform API rate limit was reached. Wait a few minutes and try again.'
        );
    }
}
