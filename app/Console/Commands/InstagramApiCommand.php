<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Services\InstagramService;
use Carbon\Carbon;

class InstagramApiCommand extends Command
{
    protected $signature = 'instagram:api {account?} {operation?} {--by=id} {--limit=10} {--from=} {--to=} {--metric=} {--sort=desc} {--debug} {--debug-full}';

    protected $description = 'Execute Instagram operations via Artisan (fetching posts, insights, analytics).';

    public function handle()
    {
        // Help handler: php artisan instagram:api help
        if ($this->argument('account') === 'help') {
            $helpFile = base_path('docs/instagram/help_en.md');
            if (file_exists($helpFile)) {
                $content = file($helpFile);
                $this->newLine();
                $this->info('📘 Instagram API Command Help');
                $this->line(str_repeat('─', 60));

                foreach ($content as $line) {
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, '##')) {
                        $this->newLine();
                        $this->comment('🔹 ' . str_replace('#', '', $trimmed));
                    } elseif (str_starts_with($trimmed, '###')) {
                        $this->info('📖 ' . str_replace('#', '', $trimmed));
                    } elseif (str_starts_with($trimmed, '```')) {
                        $this->line('');
                    } else {
                        $this->line('   ' . $trimmed);
                    }
                }

                $this->newLine();
                $this->line(str_repeat('─', 60));
                $this->info('✅ End of Help');

                $this->line(str_repeat("─", 60));
                $this->comment("🔹 Sync Command");
                $this->line("   Usage: php artisan instagram:sync [--debug] [--from=<YYYY-MM-DD>] [--to=<YYYY-MM-DD>]");
                $this->line("   Description: Synchronizes Instagram data between Graph API and CMIS database.");
                $this->line("   Examples:");
                $this->line("      php artisan instagram:sync");
                $this->line("      php artisan instagram:sync --from=2024-01-01 --to=2024-12-31 --debug");
                $this->line(str_repeat("─", 60));
                $this->newLine();
            } else {
                $this->error('Help file not found: ' . $helpFile);
            }
            return 0;
        }

        $account = $this->argument('account');
        $operation = $this->argument('operation') ?? 'media';
        $by = $this->option('by');
        $limit = (int) $this->option('limit');
        $from = $this->option('from');
        $to = $this->option('to');
        $metric = $this->option('metric');
        $sort = $this->option('sort');
        $debug = $this->option('debug');
        $debugFull = $this->option('debug-full');

        $service = new InstagramService();

        if ($account) {
            $integration = Integration::where('platform', 'instagram')
                ->where(function ($q) use ($account, $by) {
                    if ($by === 'id') {
                        $q->where('integration_id', $account);
                    } elseif ($by === 'username') {
                        $q->where('username', $account);
                    } else {
                        $q->where('account_id', $account)
                          ->orWhere('integration_id', $account);
                    }
                })
                ->first();

            if (!$integration) {
                $this->error('No matching Instagram integration found.');
                return 1;
            }

            $this->info("Executing for account: {$integration->username}");

            $results = $service->fetchMedia($integration, [
                'limit' => $limit,
                'from' => $from,
                'to' => $to,
                'metric' => $metric,
                'sort' => $sort,
                'debug' => $debug,
                'debug_full' => $debugFull,
            ]);

            foreach ($results as $post) {
                $this->line(str_repeat('─', 50));
                $this->info('📅 ' . Carbon::parse($post['timestamp'])->toDateTimeString());
                $this->line('🆔 ' . $post['id']);
                $this->line('🎞️ Type: ' . ($post['media_type'] ?? 'Unknown'));
                $this->line('🔗 Link: ' . ($post['permalink'] ?? 'N/A'));

                if (!empty($post['caption'])) {
                    $this->line("📜 Caption:\n" . $post['caption']);
                }

                if (!empty($post['media_url'])) {
                    $this->line("🖼️ Media:\n- {$post['media_url']}");
                }

                if (!empty($post['children'])) {
                    $this->line('🎞️ Carousel items:');
                    foreach ($post['children'] as $child) {
                        $this->line('- ' . $child['media_url']);
                    }
                }

                $stats = ['reach','likes','comments','saved','shares','plays','total_interactions','ig_reels_avg_watch_time','ig_reels_video_view_total_time'];
                $statsOutput = [];
                foreach ($stats as $s) {
                    if (isset($post[$s])) {
                        $statsOutput[] = ucfirst(str_replace('_', ' ', $s)) . ': ' . $post[$s];
                    }
                }
                if (!empty($statsOutput)) {
                    $this->line('📊 Stats:');
                    $this->line('   ' . implode(' | ', $statsOutput));
                }

                $this->line(str_repeat('─', 50));
            }

            $this->info('✅ Operation completed successfully.');
            return 0;
        }

        $this->info('No specific account provided. Processing all active Instagram accounts...');

        $integrations = Integration::where('platform', 'instagram')
            ->where('is_active', true)
            ->get();

        foreach ($integrations as $integration) {
            $this->info("Processing account: {$integration->username}");
            $service->fetchMedia($integration, [
                'limit' => $limit,
                'from' => $from,
                'to' => $to,
                'metric' => $metric,
                'sort' => $sort,
                'debug' => $debug,
                'debug_full' => $debugFull,
            ]);
        }

        $this->info('✅ All operations completed successfully.');
        return 0;
    }
}
