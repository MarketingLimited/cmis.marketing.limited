<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixCachePermissions extends Command
{
    protected $signature = 'cache:fix-permissions';
    protected $description = 'Fix cache directory permissions by recreating the directory structure';

    public function handle()
    {
        $this->info('Fixing cache directory permissions...');

        $baseDir = storage_path('framework/cache/data');

        // Create all hex subdirectories (00-ff)
        $hexChars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];

        $created = 0;
        $failed = 0;

        foreach ($hexChars as $first) {
            foreach ($hexChars as $second) {
                $dir = "$baseDir/{$first}{$second}";

                try {
                    if (!File::isDirectory($dir)) {
                        File::makeDirectory($dir, 0775, true, true);
                        $created++;
                        $this->line("Created: {$first}{$second}");
                    } else {
                        $this->line("Exists: {$first}{$second}");
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("Failed: {$first}{$second} - " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("Created: $created directories");
        $this->error("Failed: $failed directories");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('Some directories could not be created due to permission issues.');
            $this->warn('You may need to run the following commands as root:');
            $this->warn('  sudo chown -R cmis-test:cmis-test storage/framework/cache');
            $this->warn('  sudo chmod -R 775 storage/framework/cache');
            return 1;
        }

        $this->newLine();
        $this->info('✓ Cache directory structure fixed successfully!');
        return 0;
    }
}
