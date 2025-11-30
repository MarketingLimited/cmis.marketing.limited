<?php

namespace App\Console\Commands;

use App\Jobs\Social\PublishSocialPostJob;
use App\Models\Social\SocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // MULTI-TENANCY FIX: Query without RLS first to get all orgs with scheduled posts
        // We disable RLS temporarily to see posts across all organizations
        $postsData = DB::select("
            SELECT DISTINCT sp.org_id, sp.id, sp.platform, sp.content, sp.media, sp.options, sp.scheduled_at
            FROM cmis.social_posts sp
            WHERE sp.status = 'scheduled'
              AND sp.scheduled_at IS NOT NULL
              AND sp.scheduled_at <= NOW()
            ORDER BY sp.scheduled_at
            LIMIT ?
        ", [$limit]);

        if (empty($postsData)) {
            $this->info('✓ No posts to publish');
            return self::SUCCESS;
        }

        // Convert to collection of SocialPost models with proper org context
        $posts = collect($postsData)->map(function ($data) {
            // Set RLS context for this org
            DB::statement("SET LOCAL app.current_org_id = '{$data->org_id}'");

            // Now fetch the model with proper relationships
            $post = SocialPost::find($data->id);

            // Reset context after fetching
            DB::statement("RESET app.current_org_id");

            return $post;
        })->filter(); // Remove any nulls

        $this->info(sprintf('Found %d post(s) to publish', $posts->count()));

        if ($dryRun) {
            $this->warn('🔍 Running in DRY-RUN mode - no posts will be published');
            $this->newLine();
        }

        $published = 0;
        $failed = 0;

        $this->withProgressBar($posts, function ($post) use ($dryRun, &$published, &$failed) {
            if ($dryRun) {
                $this->newLine();
                $this->line(sprintf(
                    '  Would publish: %s [%s] (scheduled: %s)',
                    $post->id,
                    $post->platform,
                    $post->scheduled_at
                ));
                $published++;
            } else {
                try {
                    // Dispatch the publishing job with all required parameters
                    PublishSocialPostJob::dispatch(
                        $post->id,
                        $post->org_id,
                        $post->platform,
                        $post->content ?? '',
                        $post->media ?? [],
                        $post->options ?? []
                    )->onQueue('social-publishing');

                    Log::info('Scheduled post queued for publishing', [
                        'post_id' => $post->id,
                        'platform' => $post->platform,
                        'scheduled_at' => $post->scheduled_at,
                    ]);

                    $published++;
                } catch (\Exception $e) {
                    Log::error('Failed to queue scheduled post', [
                        'post_id' => $post->id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }
        });

        $this->newLine(2);

        if ($dryRun) {
            $this->info('✓ Dry run completed');
        } else {
            $this->info(sprintf('✓ %d post(s) queued for publishing', $published));
            if ($failed > 0) {
                $this->warn(sprintf('⚠ %d post(s) failed to queue', $failed));
            }
            $this->comment('💡 Tip: Monitor queue with: php artisan queue:work --queue=social-publishing');
        }

        return self::SUCCESS;
    }
}
