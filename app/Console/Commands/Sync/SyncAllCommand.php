<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;

class SyncAllCommand extends Command
{
    protected $signature = 'sync:all
                            {--org=* : Specific org IDs}
                            {--platforms=* : Specific platforms}';

    protected $description = 'Sync all platforms for all organizations';

    public function handle()
    {
        $this->info('🌐 Starting Multi-Platform Sync');
        $this->newLine();

        $orgIds = $this->option('org');
        $platforms = $this->option('platforms') ?: [
            'instagram',
            'facebook',
            'meta',
        ];

        $commands = [
            'instagram' => 'sync:instagram',
            'facebook' => 'sync:facebook',
            'meta' => 'sync:meta-ads',
        ];

        foreach ($platforms as $platform) {
            if (!isset($commands[$platform])) {
                $this->warn("⚠️  Unknown platform: {$platform}");
                continue;
            }

            $this->info("🔄 Syncing {$platform}...");

            $options = $orgIds ? ['--org' => $orgIds] : [];
            $this->call($commands[$platform], $options);

            $this->newLine();
        }

        $this->info('✅ All Platforms Synced');

        return Command::SUCCESS;
    }
}
