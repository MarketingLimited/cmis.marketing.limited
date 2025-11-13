<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\{Org, Campaign, CreativeAsset, Integration};

class SystemHealthCommand extends Command
{
    protected $signature = 'system:health';

    protected $description = 'Check system health and display statistics';

    public function handle()
    {
        $this->info('🏥 System Health Check');
        $this->newLine();

        // Database Connection
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database: Connected');
        } catch (\Exception $e) {
            $this->error('❌ Database: Failed - ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Statistics
        $this->newLine();
        $this->info('📊 System Statistics:');

        $stats = [
            'Organizations' => Org::count(),
            'Active Orgs' => Org::whereNull('deleted_at')->count(),
            'Total Campaigns' => Campaign::count(),
            'Active Campaigns' => Campaign::where('status', 'active')->count(),
            'Creative Assets' => CreativeAsset::count(),
            'Integrations' => Integration::where('status', 'active')->count(),
        ];

        foreach ($stats as $label => $value) {
            $this->line("   {$label}: {$value}");
        }

        // Disk Space (if available)
        $this->newLine();
        $diskFree = @disk_free_space('/');
        $diskTotal = @disk_total_space('/');
        if ($diskFree && $diskTotal) {
            $diskUsage = round(($diskTotal - $diskFree) / $diskTotal * 100, 2);
            $this->info("💾 Disk Usage: {$diskUsage}%");
        }

        $this->newLine();
        $this->info('✅ Health Check Completed');

        return Command::SUCCESS;
    }
}
