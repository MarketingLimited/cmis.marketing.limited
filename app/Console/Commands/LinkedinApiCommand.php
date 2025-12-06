<?php

namespace App\Console\Commands;

use App\Models\Core\Integration;
use App\Services\Connectors\Providers\LinkedInConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LinkedinApiCommand extends Command
{
    protected $signature = 'linkedin:api {account?} {operation?} {--limit=10} {--from=} {--to=} {--metric=} {--sort=} {--debug} {--debug-full} {--lang=en}';
    protected $description = 'Execute LinkedIn API operations via Artisan (fetching posts, analytics, etc).';

    protected ?LinkedInConnector $connector = null;

    public function handle()
    {
        $lang = $this->option('lang') ?? 'en';
        $helpFile = base_path("docs/social/linkedin/help_{$lang}.md");

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

        $this->info("LinkedIn API Command");
        $this->line(str_repeat('─', 50));
        $this->comment("Operation: {$operation}, Limit: {$limit}");

        // Find integration
        $integration = $this->findIntegration($account);
        if (!$integration) {
            $this->error("No LinkedIn integration found" . ($account ? " for: {$account}" : ""));
            $this->line("Use 'php artisan linkedin:api list' to see available integrations");
            return 1;
        }

        $this->info("Using integration: {$integration->account_name} (ID: {$integration->integration_id})");

        try {
            $this->connector = app(LinkedInConnector::class);

            switch ($operation) {
                case 'posts':
                    return $this->syncPosts($integration, $limit, $from);

                case 'metrics':
                case 'analytics':
                    return $this->getMetrics($integration);

                case 'comments':
                    return $this->syncComments($integration, $limit);

                case 'company':
                case 'profile':
                    return $this->getCompanyProfile($integration);

                case 'sync':
                    return $this->fullSync($integration, $limit, $from);

                default:
                    $this->error("Unknown operation: {$operation}");
                    $this->line("Available operations: posts, metrics, comments, company, sync");
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
        $query = Integration::where('platform', 'linkedin')
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
        $integrations = Integration::where('platform', 'linkedin')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn("No active LinkedIn integrations found.");
            return 0;
        }

        $this->info("Active LinkedIn Integrations:");
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
        $this->info("Syncing posts from LinkedIn...");

        $options = ['limit' => $limit];
        if ($since) {
            $options['since'] = $since;
        }

        $posts = $this->connector->syncPosts($integration, $options);

        $this->info("Synced {$posts->count()} posts");

        if ($posts->isNotEmpty()) {
            $this->line(str_repeat('─', 70));
            foreach ($posts->take(5) as $post) {
                $content = is_array($post) ? ($post['content'] ?? 'N/A') : ($post->content ?? 'N/A');
                $this->line("- " . substr($content, 0, 80) . (strlen($content) > 80 ? '...' : ''));
            }
            if ($posts->count() > 5) {
                $this->comment("... and " . ($posts->count() - 5) . " more");
            }
        }

        return 0;
    }

    protected function getMetrics(Integration $integration): int
    {
        $this->info("Fetching LinkedIn account metrics...");

        $metrics = $this->connector->getAccountMetrics($integration);

        $this->line(str_repeat('─', 50));
        $this->table(
            ['Metric', 'Value'],
            $metrics->map(fn($value, $key) => [$key, is_numeric($value) ? number_format($value) : $value])->values()->toArray()
        );

        return 0;
    }

    protected function syncComments(Integration $integration, int $limit): int
    {
        $this->info("Syncing comments from LinkedIn...");

        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);

        $this->info("Synced {$comments->count()} comments");

        return 0;
    }

    protected function getCompanyProfile(Integration $integration): int
    {
        $this->info("Fetching LinkedIn company/profile info...");

        $metrics = $this->connector->getAccountMetrics($integration);

        if ($metrics->isEmpty()) {
            $this->warn("No profile data available");
            return 0;
        }

        $this->line(str_repeat('─', 50));
        foreach ($metrics as $key => $value) {
            $this->line("{$key}: " . (is_array($value) ? json_encode($value) : $value));
        }

        return 0;
    }

    protected function fullSync(Integration $integration, int $limit, ?Carbon $since): int
    {
        $this->info("Running full LinkedIn sync...");

        $startTime = microtime(true);

        // Sync posts
        $this->comment("Step 1/3: Syncing posts...");
        $posts = $this->connector->syncPosts($integration, ['limit' => $limit, 'since' => $since]);
        $this->info("  - {$posts->count()} posts synced");

        // Sync metrics
        $this->comment("Step 2/3: Syncing metrics...");
        $metrics = $this->connector->syncMetrics($integration);
        $this->info("  - Metrics updated");

        // Sync comments
        $this->comment("Step 3/3: Syncing comments...");
        $comments = $this->connector->syncComments($integration, ['limit' => $limit]);
        $this->info("  - {$comments->count()} comments synced");

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

            $this->renderSyncSection('linkedin', $lang);
            return 0;
        } else {
            $this->showBuiltInHelp();
            return 0;
        }
    }

    protected function showBuiltInHelp(): void
    {
        $this->info("LinkedIn API Command - Help");
        $this->line(str_repeat('─', 60));
        $this->line("");
        $this->comment("Usage:");
        $this->line("  php artisan linkedin:api [account] [operation] [options]");
        $this->line("");
        $this->comment("Arguments:");
        $this->line("  account    Integration ID, account name, or external ID (optional)");
        $this->line("  operation  Operation to perform (default: posts)");
        $this->line("");
        $this->comment("Operations:");
        $this->line("  list       List all active LinkedIn integrations");
        $this->line("  posts      Sync posts from LinkedIn");
        $this->line("  metrics    Get account metrics/analytics");
        $this->line("  comments   Sync comments on posts");
        $this->line("  company    Get company/profile information");
        $this->line("  sync       Full sync (posts, metrics, comments)");
        $this->line("");
        $this->comment("Options:");
        $this->line("  --limit=N  Limit number of items (default: 10)");
        $this->line("  --from=    Start date for sync (YYYY-MM-DD)");
        $this->line("  --to=      End date for sync (YYYY-MM-DD)");
        $this->line("  --debug    Show debug information");
        $this->line("");
        $this->comment("Examples:");
        $this->line("  php artisan linkedin:api list");
        $this->line("  php artisan linkedin:api posts --limit=50");
        $this->line("  php artisan linkedin:api MyCompany sync --from=2025-01-01");
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
