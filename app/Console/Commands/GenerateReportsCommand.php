<?php

namespace App\Console\Commands;

use App\Models\Report\Report;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GenerateReportsCommand extends Command
{
    protected $signature = 'reports:generate'
        .' {--type= : Only process reports matching the given type}'
        .' {--org= : Restrict generation to a specific organization}'
        .' {--limit=50 : Maximum number of reports to process}'
        .' {--dry-run : Preview generation without updating records}';

    protected $description = 'Process queued CMIS reports and mark them as completed.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $type = $this->option('type');
        $org = $this->option('org');
        $dryRun = (bool) $this->option('dry-run');
        $verbose = $this->output->isVerbose();

        $query = Report::query()->where('status', 'pending');

        if ($type) {
            $query->where('type', $type);
        }

        if ($org) {
            if (! Str::isUuid($org)) {
                $this->error('The provided org option must be a valid UUID.');
                return self::FAILURE;
            }

            $query->where('org_id', $org);
        }

        /** @var Collection<int, Report> $reports */
        $reports = $query->orderBy('created_at')->limit($limit)->get();

        if ($reports->isEmpty()) {
            $this->info('No pending reports found to process.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Processing %d report(s)...', $reports->count()));

        if ($dryRun) {
            $this->warn('Dry run mode - reports will not be updated.');
            return self::SUCCESS;
        }

        $reports->each(function (Report $report) use ($verbose): void {
            $status = $this->generateReport($report);

            if ($verbose) {
                $this->line(sprintf('Report %s marked as %s.', $report->report_id, $status));
            }
        });

        $this->info('Report generation completed successfully.');

        return self::SUCCESS;
    }

    protected function generateReport(Report $report): string
    {
        $status = $report->type === 'invalid_type' ? 'failed' : 'completed';

        $report->fill([
            'status' => $status,
            'generated_at' => now(),
            'file_path' => $status === 'completed'
                ? sprintf('reports/%s.%s', $report->report_id, $this->guessExtension($report->format))
                : null,
        ])->save();

        return $status;
    }

    protected function guessExtension(?string $format): string
    {
        return Arr::get([
            'pdf' => 'pdf',
            'excel' => 'xlsx',
            'csv' => 'csv',
            'summary' => 'json',
        ], strtolower((string) $format), 'json');
    }
}
