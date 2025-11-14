<?php

namespace App\Console\Commands;

use App\Models\Core\Org;
use App\Models\Report\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAnalyticsReportCommand extends Command
{
    protected $signature = 'analytics:generate'
        .' {org_id : Organization identifier}'
        .' {--type=summary : Report type (summary, detailed, engagement, performance)}'
        .' {--format=summary : Export format (summary, pdf, excel)}'
        .' {--start-date= : Optional start date}'
        .' {--end-date= : Optional end date}'
        .' {--dry-run : Preview report generation without storing output}';

    protected $description = 'Generate analytics snapshots for a specific organization.';

    public function handle(): int
    {
        $orgId = (string) $this->argument('org_id');

        if (! Str::isUuid($orgId)) {
            $this->error('The provided org_id is not a valid UUID.');
            return self::FAILURE;
        }

        $org = Org::query()->whereKey($orgId)->first();

        if (! $org) {
            $this->error('Organization not found.');
            return self::FAILURE;
        }

        $this->info('Generating analytics report...');

        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - analytics report will not be persisted.');
            return self::SUCCESS;
        }

        $report = Report::query()->create([
            'org_id' => $org->org_id,
            'name' => sprintf('Analytics report for %s', $org->name),
            'type' => (string) $this->option('type'),
            'format' => (string) $this->option('format'),
            'status' => 'completed',
            'parameters' => array_filter([
                'start_date' => $this->option('start-date'),
                'end_date' => $this->option('end-date'),
            ]),
            'generated_at' => now(),
        ]);

        $this->line(sprintf('Report %s generated with format %s.', $report->report_id, $report->format ?? 'summary'));

        return self::SUCCESS;
    }
}
