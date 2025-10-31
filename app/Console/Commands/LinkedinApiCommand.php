<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class LinkedinApiCommand extends Command
{
    protected $signature = 'linkedin:api {company?} {operation?} {--limit=10} {--from=} {--to=} {--metric=} {--sort=} {--debug} {--debug-full} {--lang=en}';
    protected $description = 'Execute LinkedIn API operations via Artisan (fetching posts, analytics, etc).';

    public function handle()
    {
        $lang = $this->option('lang') ?? 'en';
        $helpFile = base_path("docs/social/linkedin/help_{$lang}.md");

        $company = $this->argument('company');

        if ($company === 'help') {
            if (file_exists($helpFile)) {
                $this->line(str_repeat('─', 60));
                foreach (file($helpFile) as $line) {
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, '##')) {
                        $this->comment('🔹 ' . str_replace('#', '', $trimmed));
                    } elseif (str_starts_with($trimmed, '```')) {
                        $this->line('');
                    } else {
                        $this->line('   ' . $trimmed);
                    }
                }
                $this->line(str_repeat('─', 60));
                $this->info('End of Help');

                // Auto-sync section call
                $this->renderSyncSection('linkedin', $lang);
                return 0;
            } else {
                $this->error('Help file not found: ' . $helpFile);
                return 1;
            }
        }

        $operation = $this->argument('operation') ?? 'posts';
        $limit = (int) $this->option('limit');
        $from = $this->option('from');
        $to = $this->option('to');
        $metric = $this->option('metric');
        $sort = $this->option('sort');
        $debug = $this->option('debug');
        $debugFull = $this->option('debug-full');

        $this->comment("Operation: {$operation}, Limit: {$limit}, From: {$from}, To: {$to}");
        $this->warn('⚙️  LinkedinService integration not yet implemented.');

        return 0;
    }

    protected function renderSyncSection($platform, $lang = 'en')
    {
        $helpFile = base_path("docs/social/{$platform}/help_{$lang}.md");
        if (!file_exists($helpFile)) return;

        $content = file($helpFile);
        $syncSection = false;
        foreach ($content as $line) {
            if (strpos($line, '### 🔹 Sync Command') !== false || strpos($line, '### 🔹 أمر المزامنة') !== false) {
                $syncSection = true;
                $this->line(str_repeat('─', 60));
                $this->comment('🔹 Sync Command');
                continue;
            }
            if ($syncSection) {
                if (strpos($line, 'End of Sync Help') !== false || strpos($line, 'نهاية المساعدة الخاصة بالمزامنة') !== false) {
                    $this->line(str_repeat('─', 60));
                    break;
                }
                $this->line('   ' . trim($line));
            }
        }
    }
}
