<?php

namespace App\Console\Commands;

use App\Models\Content\ScheduledPost;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcessScheduledPostsCommand extends Command
{
    protected $signature = 'posts:process-scheduled'
        .' {--org= : Restrict processing to a single organization}'
        .' {--limit=50 : Maximum number of posts to process}'
        .' {--dry-run : Preview processing without updating records}';

    protected $description = 'Publish or mark due scheduled posts as processed.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $org = $this->option('org');

        $query = ScheduledPost::query()
            ->where('status', 'scheduled')
            ->where(function ($builder) {
                $builder->whereNull('scheduled_time')
                    ->orWhere('scheduled_time', '<=', Carbon::now());
            });

        if ($org) {
            if (! Str::isUuid($org)) {
                $this->error('The provided org option must be a valid UUID.');
                return self::FAILURE;
            }

            $query->where('org_id', $org);
        }

        $posts = $query->orderBy('scheduled_time')->limit($limit)->get();

        if ($posts->isEmpty()) {
            $this->info('No scheduled posts to process.');
            return self::SUCCESS;
        }

        $this->info('Processing scheduled posts...');

        if ($dryRun) {
            $this->warn('Dry run mode - no status changes will be applied.');
            return self::SUCCESS;
        }

        $processed = 0;
        foreach ($posts as $post) {
            $post->fill([
                'status' => 'processed',
                'processed_at' => Carbon::now(),
            ])->save();

            $processed++;
        }

        $this->line(sprintf('%d scheduled post(s) processed.', $processed));

        return self::SUCCESS;
    }
}
