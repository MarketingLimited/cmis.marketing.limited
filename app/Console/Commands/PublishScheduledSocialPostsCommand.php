<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledSocialPostJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishScheduledSocialPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social:publish-scheduled
                            {--dry-run : Run in dry-run mode without actually publishing}
                            {--limit=100 : Maximum number of posts to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled social media posts that are ready';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Finding scheduled posts ready to publish...');

        // Get posts ready to publish
        $posts = DB::table('cmis.scheduled_social_posts')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->info('✓ No posts to publish');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d post(s) to publish', $posts->count()));

        if ($dryRun) {
            $this->warn('🔍 Running in DRY-RUN mode - no posts will be published');
            $this->newLine();
        }

        $this->withProgressBar($posts, function ($post) use ($dryRun) {
            if ($dryRun) {
                $this->newLine();
                $this->line(sprintf(
                    '  Would publish: %s (scheduled: %s)',
                    $post->post_id,
                    $post->scheduled_at
                ));
            } else {
                // Load full model and dispatch job
                $postModel = \App\Models\ScheduledSocialPost::find($post->post_id);

                if ($postModel) {
                    PublishScheduledSocialPostJob::dispatch($postModel)
                        ->onQueue('social-publishing');
                }
            }
        });

        $this->newLine(2);

        if ($dryRun) {
            $this->info('✓ Dry run completed');
        } else {
            $this->info(sprintf('✓ %d post(s) queued for publishing', $posts->count()));
            $this->comment('💡 Tip: Monitor queue with: php artisan queue:work --queue=social-publishing');
        }

        return self::SUCCESS;
    }
}
