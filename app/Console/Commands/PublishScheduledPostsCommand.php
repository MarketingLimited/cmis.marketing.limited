<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledPostJob;
use App\Models\Creative\ContentItem;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to publish scheduled content items';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled posts...');

        $posts = ContentItem::where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts to publish.');
            return Command::SUCCESS;
        }

        $this->info("Found {$posts->count()} posts to publish.");

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        foreach ($posts as $post) {
            PublishScheduledPostJob::dispatch($post);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$posts->count()} publishing jobs.");

        return Command::SUCCESS;
    }
}
