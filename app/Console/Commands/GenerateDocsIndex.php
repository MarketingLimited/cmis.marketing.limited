<?php

namespace App\Console\Commands;

use App\Jobs\Knowledge\DiscoverDocsDirectory;
use Illuminate\Console\Command;

class GenerateDocsIndex extends Command
{
    protected $signature = 'knowledge:generate-docs-index';

    protected $description = 'Generate auto-updated documentation index (PRIMARY SOURCE OF TRUTH)';

    public function handle(): int
    {
        $this->info('🔍 Discovering documentation directory...');

        try {
            $job = new DiscoverDocsDirectory();
            $job->handle();

            $outputPath = base_path('.claude/knowledge/auto-generated/DOCS_INDEX.md');
            $this->info("✅ Documentation index generated: {$outputPath}");

            if (file_exists($outputPath)) {
                $size = round(filesize($outputPath) / 1024, 2);
                $this->comment("   File size: {$size} KB");
            }

            $this->newLine();
            $this->comment('💡 This file maps ALL documentation in /docs/');
            $this->comment('   Agents consult this BEFORE starting any work.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate documentation index: {$e->getMessage()}");
            $this->error("   " . $e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
