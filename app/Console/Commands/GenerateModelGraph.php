<?php

namespace App\Console\Commands;

use App\Jobs\Knowledge\DiscoverModelGraph;
use Illuminate\Console\Command;

class GenerateModelGraph extends Command
{
    protected $signature = 'knowledge:generate-model-graph';

    protected $description = 'Generate auto-updated model relationship graph with visual tree structure';

    public function handle(): int
    {
        $this->info('🔍 Discovering model relationships...');

        try {
            $job = new DiscoverModelGraph();
            $job->handle();

            $outputPath = base_path('.claude/knowledge/auto-generated/MODEL_RELATIONSHIP_GRAPH.md');
            $this->info("✅ Model graph generated: {$outputPath}");

            if (file_exists($outputPath)) {
                $size = round(filesize($outputPath) / 1024, 2);
                $this->comment("   File size: {$size} KB");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate model graph: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
