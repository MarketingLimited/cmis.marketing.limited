<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;

class SyncAllCommand extends Command
{
    protected $signature = 'sync:all {--org= : Organization ID to sync}';
    protected $description = 'Sync all platforms (Instagram, Facebook, Meta Ads, Google Ads, TikTok Ads)';

    public function handle()
    {
        $this->info('🚀 Starting sync for all platforms...');
        $this->newLine();

        $orgId = $this->option('org');
        $orgOption = $orgId ? ['--org' => $orgId] : [];

        $platforms = [
            'Instagram' => 'sync:instagram',
            'Facebook' => 'sync:facebook',
            'Meta Ads' => 'sync:meta-ads',
            'Google Ads' => 'sync:google-ads',
            'TikTok Ads' => 'sync:tiktok-ads',
        ];

        $successful = 0;
        $failed = 0;

        foreach ($platforms as $name => $command) {
            $this->info("───────────────────────────────────");
            $this->info("Syncing: {$name}");
            $this->newLine();

            try {
                $exitCode = $this->call($command, $orgOption);
                
                if ($exitCode === self::SUCCESS) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to sync {$name}: " . $e->getMessage());
                $failed++;
            }

            $this->newLine();
        }

        $this->info("───────────────────────────────────");
        $this->newLine();
        $this->info("✅ Completed: {$successful} platform(s)");
        
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed} platform(s)");
        }

        $this->newLine();
        $this->info('All sync operations completed. Check logs for details.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
