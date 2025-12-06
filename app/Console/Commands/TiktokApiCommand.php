<?php

namespace App\Console\Commands;

use App\Models\Core\Integration;
use App\Services\Connectors\Providers\TikTokConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TiktokApiCommand extends Command
{
    protected $signature = 'tiktok:api {account?} {operation?} {--limit=10} {--from=} {--to=} {--metric=} {--sort=} {--debug} {--debug-full} {--lang=en}';
    protected $description = 'Execute TikTok API operations via Artisan (fetching posts, analytics, etc).';

    protected ?TikTokConnector $connector = null;

    public function handle()
    {
        $lang = $this->option('lang') ?? 'en';
        $helpFile = base_path("docs/social/tiktok/help_{$lang}.md");

        $account = $this->argument('account');

        if ($account === 'help') {
            return $this->showHelp($helpFile, $lang);
        }

        if ($account === 'list') {
            return $this->listIntegrations();
        }

        $operation = $this->argument('operation') ?? 'posts';
        $limit = (int) $this->option('limit');
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : null;
        $metric = $this->option('metric');
        $sort = $this->option('sort');
        $debug = $this->option('debug');
        $debugFull = $this->option('debug-full');

        $this->info("TikTok API Command");
        $this->line(str_repeat('─', 50));
        $this->comment("Operation: {$operation}, Limit: {$limit}");

        // Find integration
        $integration = $this->findIntegration($account);
        if (!$integration) {
            $this->error("No TikTok integration found" . ($account ? " for: {$account}" : ""));
            $this->line("Use 'php artisan tiktok:api list' to see available integrations");
            return 1;
        }

        $this->info("Using integration: {$integration->account_name} (ID: {$integration->integration_id})");

        try {
            $this->connector = app(TikTokConnector::class);

            switch ($operation) {
                case 'posts':
                case 'videos':
                    return $this->syncPosts($integration, $limit, $from);

                case 'metrics':
                case 'analytics':
                    return $this->getMetrics($integration);

                case 'comments':
                    return $this->syncComments($integration, $limit);

                case 'profile':
                case 'user':
                    return $this->getUserProfile($integration);

                case 'sync':
                    return $this->fullSync($integration, $limit, $from);

                case 'campaigns':
                    return $this->syncCampaigns($integration, $limit);

                default:
                    $this->error("Unknown operation: {$operation}");
                    $this->line("Available operations: posts, videos, metrics, comments, profile, campaigns, sync");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            if ($debug || $debugFull) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    protected function findIntegration(?string $account): ?Integration
    {
        $query = Integration::where('platform', 'tiktok')
            ->where('is_active', true);

        if ($account) {
            $query->where(function ($q) use ($account) {
                $q->where('external_account_id', $account)
                    ->orWhere('account_name', 'LIKE', "%{$account}%")
                    ->orWhere('integration_id', $account);
            });
        }

        return $query->first();
    }

    protected function listIntegrations(): int
    {
        $integrations = Integration::where('platform', 'tiktok')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn("No active TikTok integrations found.");
            return 0;
        }

        $this->info("Active TikTok Integrations:");
        $this->line(str_repeat('─', 70));

        $headers = ['ID', 'Account Name', 'Org ID', 'Last Sync', 'Status'];
        $rows = $integrations->map(function ($i) {
            return [
                substr($i->integration_id, 0, 8) . '...',
                $i->account_name ?? 'N/A',
                substr($i->org_id, 0, 8) . '...',
                $i->last_sync_at?->diffForHumans() ?? 'Never',
                $i->sync_status ?? 'unknown',
            ];
        })->toArray();

        $this->table($headers, $rows);
        return 0;
    }

    protected function syncPosts(Integration $integration, int $limit, ?Carbon $since): int
    {
        $this->info("Syncing videos from TikTok...");

        $options = ['limit' => $limit];
        if ($since) {
            $options['since'] = $since;
        }

        $posts = $this->connector->syncPosts($integration, $options);

        $this->info("Synced {$posts->count()} videos");

        if ($posts->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            $headers = ['Video ID', 'Description', 'Views', 'Likes', 'Date'];
            $rows = $posts->take(10)->map(function ($post) {
                if (is_array($post)) {
                    return [
                        substr($post['video_id'] ?? '', 0, 15) . '...',
                        substr($post['description'] ?? '', 0, 30) . '...',
                        number_format($post['views'] ?? 0),
                        number_format($post['likes'] ?? 0),
                        $post['created_at'] ?? 'N/A',
                    ];
                }
                return [
                    substr($post->platform_post_id ?? '', 0, 15) . '...',
                    substr($post->content ?? '', 0, 30) . '...',
                    number_format($post->metrics['views'] ?? 0),
                    number_format($post->metrics['likes'] ?? 0),
                    $post->published_at ?? 'N/A',
                ];
            })->toArray();
            $this->table($headers, $rows);

            if ($posts->count() > 10) {
                $this->comment("... and " . ($posts->count() - 10) . " more");
            }
        }

        return 0;
    }

    protected function getMetrics(Integration $integration): int
    {
        $this->info("Fetching TikTok account metrics...");

        $metrics = $this->connector->getAccountMetrics($integration);

        $this->line(str_repeat('─', 50));
        $this->table(
            ['Metric', 'Value'],
            $metrics->map(fn($value, $key) => [
                $key,
                is_numeric($value) ? number_format($value) : (is_array($value) ? json_encode($value) : $value),
            ])->values()->toArray()
        );

        return 0;
    }

    protected function syncComments(Integration $integration, int $limit): int
    {
        $this->info("Syncing comments from TikTok...");

        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);

        $this->info("Synced {$comments->count()} comments");

        if ($comments->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            foreach ($comments->take(5) as $comment) {
                $text = is_array($comment) ? ($comment['text'] ?? '') : ($comment->comment_text ?? '');
                $this->line("- " . substr($text, 0, 60) . (strlen($text) > 60 ? '...' : ''));
            }
        }

        return 0;
    }

    protected function getUserProfile(Integration $integration): int
    {
        $this->info("Fetching TikTok user profile...");

        $metrics = $this->connector->getAccountMetrics($integration);

        if ($metrics->isEmpty()) {
            $this->warn("No profile data available");
            return 0;
        }

        $this->line(str_repeat('─', 50));
        $this->info("Profile Information:");
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                $this->line("{$key}: " . json_encode($value));
            } else {
                $displayValue = is_numeric($value) ? number_format($value) : $value;
                $this->line("{$key}: {$displayValue}");
            }
        }

        return 0;
    }

    protected function syncCampaigns(Integration $integration, int $limit): int
    {
        $this->info("Syncing ad campaigns from TikTok...");

        $campaigns = $this->connector->syncCampaigns($integration, ['limit' => $limit]);

        $this->info("Synced {$campaigns->count()} campaigns");

        if ($campaigns->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            $headers = ['Campaign ID', 'Name', 'Status', 'Objective', 'Budget'];
            $rows = $campaigns->take(10)->map(function ($campaign) {
                if (is_array($campaign)) {
                    return [
                        substr($campaign['campaign_id'] ?? '', 0, 12) . '...',
                        substr($campaign['name'] ?? '', 0, 25) . '...',
                        $campaign['status'] ?? 'N/A',
                        $campaign['objective'] ?? 'N/A',
                        '$' . number_format($campaign['budget'] ?? 0, 2),
                    ];
                }
                return [
                    substr($campaign->platform_campaign_id ?? '', 0, 12) . '...',
                    substr($campaign->campaign_name ?? '', 0, 25) . '...',
                    $campaign->status ?? 'N/A',
                    $campaign->objective ?? 'N/A',
                    '$' . number_format($campaign->daily_budget ?? 0, 2),
                ];
            })->toArray();
            $this->table($headers, $rows);
        }

        return 0;
    }

    protected function fullSync(Integration $integration, int $limit, ?Carbon $since): int
    {
        $this->info("Running full TikTok sync...");

        $startTime = microtime(true);

        // Sync posts/videos
        $this->comment("Step 1/4: Syncing videos...");
        $posts = $this->connector->syncPosts($integration, ['limit' => $limit, 'since' => $since]);
        $this->info("  - {$posts->count()} videos synced");

        // Sync metrics
        $this->comment("Step 2/4: Syncing metrics...");
        $metrics = $this->connector->syncMetrics($integration);
        $this->info("  - Metrics updated");

        // Sync comments
        $this->comment("Step 3/4: Syncing comments...");
        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);
        $this->info("  - {$comments->count()} comments synced");

        // Sync campaigns (if ad account)
        $this->comment("Step 4/4: Syncing campaigns...");
        try {
            $campaigns = $this->connector->syncCampaigns($integration, ['limit' => $limit]);
            $this->info("  - {$campaigns->count()} campaigns synced");
        } catch (\Exception $e) {
            $this->warn("  - Campaigns sync skipped (no ad account configured)");
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->line(str_repeat('─', 50));
        $this->info("Full sync completed in {$elapsed}s");

        // Update last sync timestamp
        $integration->update([
            'last_sync_at' => now(),
            'sync_status' => 'success',
        ]);

        return 0;
    }

    protected function showHelp(string $helpFile, string $lang): int
    {
        if (file_exists($helpFile)) {
            $this->line(str_repeat('─', 60));
            foreach (file($helpFile) as $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '##')) {
                    $this->comment(str_replace('#', '', $trimmed));
                } elseif (str_starts_with($trimmed, '```')) {
                    $this->line('');
                } else {
                    $this->line('   ' . $trimmed);
                }
            }
            $this->line(str_repeat('─', 60));
            $this->info('End of Help');

            $this->renderSyncSection('tiktok', $lang);
            return 0;
        } else {
            $this->showBuiltInHelp();
            return 0;
        }
    }

    protected function showBuiltInHelp(): void
    {
        $this->info("TikTok API Command - Help");
        $this->line(str_repeat('─', 60));
        $this->line("");
        $this->comment("Usage:");
        $this->line("  php artisan tiktok:api [account] [operation] [options]");
        $this->line("");
        $this->comment("Arguments:");
        $this->line("  account    Integration ID, account name, or external ID (optional)");
        $this->line("  operation  Operation to perform (default: posts)");
        $this->line("");
        $this->comment("Operations:");
        $this->line("  list       List all active TikTok integrations");
        $this->line("  posts      Sync videos/posts from TikTok");
        $this->line("  videos     Alias for posts");
        $this->line("  metrics    Get account metrics/analytics");
        $this->line("  comments   Sync comments on videos");
        $this->line("  profile    Get user profile information");
        $this->line("  campaigns  Sync ad campaigns (requires ads account)");
        $this->line("  sync       Full sync (videos, metrics, comments, campaigns)");
        $this->line("");
        $this->comment("Options:");
        $this->line("  --limit=N  Limit number of items (default: 10)");
        $this->line("  --from=    Start date for sync (YYYY-MM-DD)");
        $this->line("  --to=      End date for sync (YYYY-MM-DD)");
        $this->line("  --debug    Show debug information");
        $this->line("");
        $this->comment("Examples:");
        $this->line("  php artisan tiktok:api list");
        $this->line("  php artisan tiktok:api videos --limit=50");
        $this->line("  php artisan tiktok:api @myaccount sync --from=2025-01-01");
    }

    protected function renderSyncSection(string $platform, string $lang = 'en'): void
    {
        $helpFile = base_path("docs/social/{$platform}/help_{$lang}.md");
        if (!file_exists($helpFile)) {
            return;
        }

        $content = file($helpFile);
        $syncSection = false;
        foreach ($content as $line) {
            if (strpos($line, '### Sync Command') !== false || strpos($line, 'أمر المزامنة') !== false) {
                $syncSection = true;
                $this->line(str_repeat('─', 60));
                $this->comment('Sync Command');
                continue;
            }
            if ($syncSection) {
                if (strpos($line, 'End of Sync Help') !== false || strpos($line, 'نهاية المساعدة') !== false) {
                    $this->line(str_repeat('─', 60));
                    break;
                }
                $this->line('   ' . trim($line));
            }
        }
    }
}
