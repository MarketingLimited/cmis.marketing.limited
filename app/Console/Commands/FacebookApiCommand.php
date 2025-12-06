<?php

namespace App\Console\Commands;

use App\Models\Core\Integration;
use App\Services\Connectors\Providers\MetaConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacebookApiCommand extends Command
{
    protected $signature = 'facebook:api {page?} {operation?} {--limit=10} {--from=} {--to=} {--metric=} {--sort=} {--debug} {--debug-full} {--lang=en}';
    protected $description = 'Execute Facebook/Meta API operations via Artisan (fetching posts, analytics, etc).';

    protected ?MetaConnector $connector = null;

    public function handle()
    {
        $lang = $this->option('lang') ?? 'en';
        $helpFile = base_path("docs/social/facebook/help_{$lang}.md");

        $page = $this->argument('page');

        if ($page === 'help') {
            return $this->showHelp($helpFile, $lang);
        }

        if ($page === 'list') {
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

        $this->info("Facebook/Meta API Command");
        $this->line(str_repeat('─', 50));
        $this->comment("Operation: {$operation}, Limit: {$limit}");

        // Find integration
        $integration = $this->findIntegration($page);
        if (!$integration) {
            $this->error("No Meta/Facebook integration found" . ($page ? " for: {$page}" : ""));
            $this->line("Use 'php artisan facebook:api list' to see available integrations");
            return 1;
        }

        $this->info("Using integration: " . ($integration->account_name ?? $integration->external_account_id) . " (ID: {$integration->integration_id})");

        try {
            $this->connector = app(MetaConnector::class);

            switch ($operation) {
                case 'posts':
                    return $this->syncPosts($integration, $limit, $from);

                case 'metrics':
                case 'analytics':
                    return $this->getMetrics($integration);

                case 'comments':
                    return $this->syncComments($integration, $limit);

                case 'messages':
                    return $this->syncMessages($integration, $limit);

                case 'campaigns':
                    return $this->syncCampaigns($integration, $limit);

                case 'page':
                case 'profile':
                    return $this->getPageInfo($integration);

                case 'sync':
                    return $this->fullSync($integration, $limit, $from);

                default:
                    $this->error("Unknown operation: {$operation}");
                    $this->line("Available operations: posts, metrics, comments, messages, campaigns, page, sync");
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

    protected function findIntegration(?string $page): ?Integration
    {
        $query = Integration::where('platform', 'meta')
            ->where('is_active', true);

        if ($page) {
            $query->where(function ($q) use ($page) {
                $q->where('external_account_id', $page)
                    ->orWhere('account_name', 'LIKE', "%{$page}%")
                    ->orWhere('integration_id', $page)
                    ->orWhereJsonContains('settings->page_id', $page);
            });
        }

        return $query->first();
    }

    protected function listIntegrations(): int
    {
        $integrations = Integration::where('platform', 'meta')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn("No active Meta/Facebook integrations found.");
            return 0;
        }

        $this->info("Active Meta/Facebook Integrations:");
        $this->line(str_repeat('─', 80));

        $headers = ['ID', 'Account/Page', 'Type', 'Org ID', 'Last Sync', 'Status'];
        $rows = $integrations->map(function ($i) {
            $settings = is_array($i->settings) ? $i->settings : json_decode($i->settings ?? '{}', true);
            $type = isset($settings['ad_account_id']) ? 'Ads + Page' : 'Page Only';
            return [
                substr($i->integration_id, 0, 8) . '...',
                $i->account_name ?? $settings['account_name'] ?? 'N/A',
                $type,
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
        $this->info("Syncing posts from Facebook...");

        $options = ['limit' => $limit];
        if ($since) {
            $options['since'] = $since;
        }

        $posts = $this->connector->syncPosts($integration, $options);

        $this->info("Synced {$posts->count()} posts");

        if ($posts->isNotEmpty()) {
            $this->line(str_repeat('─', 80));
            $headers = ['Post ID', 'Content', 'Reactions', 'Comments', 'Shares', 'Date'];
            $rows = $posts->take(10)->map(function ($post) {
                if (is_array($post)) {
                    $metrics = $post['metrics'] ?? [];
                    return [
                        substr($post['id'] ?? '', 0, 15) . '...',
                        substr($post['message'] ?? '', 0, 25) . '...',
                        number_format($metrics['reactions'] ?? 0),
                        number_format($metrics['comments'] ?? 0),
                        number_format($metrics['shares'] ?? 0),
                        $post['created_time'] ?? 'N/A',
                    ];
                }
                $metrics = is_array($post->metrics) ? $post->metrics : json_decode($post->metrics ?? '{}', true);
                return [
                    substr($post->platform_post_id ?? '', 0, 15) . '...',
                    substr($post->content ?? '', 0, 25) . '...',
                    number_format($metrics['reactions'] ?? 0),
                    number_format($metrics['comments'] ?? 0),
                    number_format($metrics['shares'] ?? 0),
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
        $this->info("Fetching Facebook page metrics...");

        $metrics = $this->connector->getAccountMetrics($integration);

        if ($metrics->isEmpty()) {
            $this->warn("No metrics available. Make sure page_id is configured.");
            return 0;
        }

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
        $this->info("Syncing comments from Facebook...");

        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);

        $this->info("Synced {$comments->count()} comments");

        if ($comments->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            $headers = ['Comment ID', 'From', 'Text', 'Likes', 'Hidden'];
            $rows = $comments->take(10)->map(function ($comment) {
                if (is_array($comment)) {
                    return [
                        substr($comment['id'] ?? '', 0, 12) . '...',
                        $comment['from']['name'] ?? 'Unknown',
                        substr($comment['message'] ?? '', 0, 30) . '...',
                        number_format($comment['like_count'] ?? 0),
                        ($comment['is_hidden'] ?? false) ? 'Yes' : 'No',
                    ];
                }
                return [
                    substr($comment->platform_comment_id ?? '', 0, 12) . '...',
                    $comment->commenter_name ?? 'Unknown',
                    substr($comment->comment_text ?? '', 0, 30) . '...',
                    number_format($comment->likes_count ?? 0),
                    $comment->is_hidden ? 'Yes' : 'No',
                ];
            })->toArray();
            $this->table($headers, $rows);
        }

        return 0;
    }

    protected function syncMessages(Integration $integration, int $limit): int
    {
        $this->info("Syncing messages from Facebook...");

        $messages = $this->connector->syncMessages($integration, ['limit' => $limit]);

        $this->info("Synced {$messages->count()} messages");

        if ($messages->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            foreach ($messages->take(5) as $message) {
                $text = is_array($message) ? ($message['message'] ?? '') : ($message->message_text ?? '');
                $sender = is_array($message) ? ($message['from']['name'] ?? 'Unknown') : ($message->sender_name ?? 'Unknown');
                $this->line("- [{$sender}]: " . substr($text, 0, 50) . (strlen($text) > 50 ? '...' : ''));
            }
        }

        return 0;
    }

    protected function syncCampaigns(Integration $integration, int $limit): int
    {
        $this->info("Syncing ad campaigns from Facebook...");

        try {
            $campaigns = $this->connector->syncCampaigns($integration, ['limit' => $limit]);
        } catch (\Exception $e) {
            $this->error("Failed to sync campaigns: " . $e->getMessage());
            $this->line("Make sure ad_account_id is configured in the integration settings.");
            return 1;
        }

        $this->info("Synced {$campaigns->count()} campaigns");

        if ($campaigns->isNotEmpty()) {
            $this->line(str_repeat('─', 80));
            $headers = ['Campaign ID', 'Name', 'Objective', 'Status', 'Daily Budget', 'Spend'];
            $rows = $campaigns->take(10)->map(function ($campaign) {
                if (is_array($campaign)) {
                    $insights = $campaign['insights']['data'][0] ?? [];
                    return [
                        substr($campaign['id'] ?? '', 0, 15) . '...',
                        substr($campaign['name'] ?? '', 0, 20) . '...',
                        $campaign['objective'] ?? 'N/A',
                        $campaign['status'] ?? 'N/A',
                        '$' . number_format(($campaign['daily_budget'] ?? 0) / 100, 2),
                        '$' . number_format($insights['spend'] ?? 0, 2),
                    ];
                }
                $metrics = is_array($campaign->metrics) ? $campaign->metrics : json_decode($campaign->metrics ?? '{}', true);
                return [
                    substr($campaign->platform_campaign_id ?? '', 0, 15) . '...',
                    substr($campaign->campaign_name ?? '', 0, 20) . '...',
                    $campaign->objective ?? 'N/A',
                    $campaign->status ?? 'N/A',
                    '$' . number_format(($campaign->daily_budget ?? 0) / 100, 2),
                    '$' . number_format($metrics['spend'] ?? 0, 2),
                ];
            })->toArray();
            $this->table($headers, $rows);
        }

        return 0;
    }

    protected function getPageInfo(Integration $integration): int
    {
        $this->info("Fetching Facebook page information...");

        $metrics = $this->connector->getAccountMetrics($integration);

        if ($metrics->isEmpty()) {
            $this->warn("No page data available");
            return 0;
        }

        $this->line(str_repeat('─', 50));
        $this->info("Page Information:");
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                $this->line("{$key}: " . json_encode($value));
            } else {
                $displayValue = is_numeric($value) ? number_format($value) : $value;
                $this->line("{$key}: {$displayValue}");
            }
        }

        // Show settings if available
        $settings = is_array($integration->settings) ? $integration->settings : json_decode($integration->settings ?? '{}', true);
        if (!empty($settings)) {
            $this->line("");
            $this->comment("Integration Settings:");
            if (isset($settings['page_id'])) {
                $this->line("  Page ID: {$settings['page_id']}");
            }
            if (isset($settings['ad_account_id'])) {
                $this->line("  Ad Account ID: {$settings['ad_account_id']}");
            }
            if (isset($settings['account_name'])) {
                $this->line("  Account Name: {$settings['account_name']}");
            }
        }

        return 0;
    }

    protected function fullSync(Integration $integration, int $limit, ?Carbon $since): int
    {
        $this->info("Running full Facebook sync...");

        $startTime = microtime(true);

        // Sync posts
        $this->comment("Step 1/4: Syncing posts...");
        $posts = $this->connector->syncPosts($integration, ['limit' => $limit, 'since' => $since]);
        $this->info("  - {$posts->count()} posts synced");

        // Sync comments
        $this->comment("Step 2/4: Syncing comments...");
        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);
        $this->info("  - {$comments->count()} comments synced");

        // Sync messages
        $this->comment("Step 3/4: Syncing messages...");
        try {
            $messages = $this->connector->syncMessages($integration, ['limit' => $limit]);
            $this->info("  - {$messages->count()} messages synced");
        } catch (\Exception $e) {
            $this->warn("  - Messages sync skipped (not configured or no permissions)");
        }

        // Sync campaigns (if ad account configured)
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

            $this->renderSyncSection('facebook', $lang);
            return 0;
        } else {
            $this->showBuiltInHelp();
            return 0;
        }
    }

    protected function showBuiltInHelp(): void
    {
        $this->info("Facebook/Meta API Command - Help");
        $this->line(str_repeat('─', 60));
        $this->line("");
        $this->comment("Usage:");
        $this->line("  php artisan facebook:api [page] [operation] [options]");
        $this->line("");
        $this->comment("Arguments:");
        $this->line("  page       Page ID, integration ID, or account name (optional)");
        $this->line("  operation  Operation to perform (default: posts)");
        $this->line("");
        $this->comment("Operations:");
        $this->line("  list       List all active Meta/Facebook integrations");
        $this->line("  posts      Sync posts from Facebook page");
        $this->line("  metrics    Get page metrics/analytics");
        $this->line("  comments   Sync comments on posts");
        $this->line("  messages   Sync page messages (requires permissions)");
        $this->line("  campaigns  Sync ad campaigns (requires ad account)");
        $this->line("  page       Get page information");
        $this->line("  sync       Full sync (posts, comments, messages, campaigns)");
        $this->line("");
        $this->comment("Options:");
        $this->line("  --limit=N  Limit number of items (default: 10)");
        $this->line("  --from=    Start date for sync (YYYY-MM-DD)");
        $this->line("  --to=      End date for sync (YYYY-MM-DD)");
        $this->line("  --debug    Show debug information");
        $this->line("");
        $this->comment("Examples:");
        $this->line("  php artisan facebook:api list");
        $this->line("  php artisan facebook:api posts --limit=50");
        $this->line("  php artisan facebook:api MyPage sync --from=2025-01-01");
        $this->line("  php artisan facebook:api campaigns");
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
