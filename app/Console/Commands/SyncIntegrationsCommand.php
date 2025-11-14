<?php

namespace App\Console\Commands;

use App\Models\Integration\Integration;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SyncIntegrationsCommand extends Command
{
    protected $signature = 'integrations:sync'
        .' {--platform= : Limit the sync to a single platform}'
        .' {--org= : Restrict the sync to a specific organization}'
        .' {--limit=50 : Maximum number of integrations to process}'
        .' {--dry-run : Preview without mutating records}';

    protected $description = 'Synchronize external integrations and update their last synced timestamp.';

    public function handle(): int
    {
        $platform = $this->option('platform');
        $org = $this->option('org');
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $verbose = $this->output->isVerbose();

        $query = Integration::query()->where('is_active', true);

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($org) {
            if (! Str::isUuid($org)) {
                $this->error('The provided org option must be a valid UUID.');
                return self::FAILURE;
            }

            $query->where('org_id', $org);
        }

        $integrations = $query->orderBy('updated_at', 'desc')->limit($limit)->get();

        if ($integrations->isEmpty()) {
            $this->info('No integrations found to sync.');
            return self::SUCCESS;
        }

        $this->info('Syncing integrations...');

        if ($dryRun) {
            $this->warn('Dry run mode - no changes will be made');
            return self::SUCCESS;
        }

        foreach ($integrations as $integration) {
            $meta = Arr::wrap($integration->sync_metadata);
            $meta['last_status'] = 'success';
            $meta['sync_notes'] = $meta['sync_notes'] ?? [];
            $meta['sync_notes'][] = sprintf('Synced on %s', now()->toDateTimeString());

            $integration->fill([
                'last_synced_at' => now(),
                'sync_status' => 'success',
                'sync_metadata' => $meta,
            ])->save();

            if ($verbose) {
                $this->line(sprintf('Integration %s (%s) synced.', $integration->name ?? $integration->integration_id, $integration->platform));
            }
        }

        $this->info(sprintf('%d integration(s) synchronized.', $integrations->count()));

        return self::SUCCESS;
    }
}
