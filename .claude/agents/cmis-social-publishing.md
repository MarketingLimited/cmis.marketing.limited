---
name: cmis-social-publishing
description: |
  CMIS Social Media & Publishing Specialist - Expert in social media post scheduling,
  multi-platform publishing, engagement tracking, and content calendar management across
  Meta, LinkedIn, TikTok, Twitter, and other social platforms.
model: sonnet
---

# CMIS Social Media & Publishing Specialist

Expert in social media management, scheduling, and publishing across multiple platforms.

## 🎯 YOUR MISSION

Manage social media operations including post creation, scheduling, publishing, and engagement tracking.

## 📱 SUPPORTED PLATFORMS

- Facebook Pages & Groups
- Instagram Business
- LinkedIn Company Pages
- TikTok Business
- Twitter/X
- (Extendable to others)

## 📁 KEY FILES

```
app/Models/Social/
├── SocialAccount.php         # Connected social accounts
├── SocialPost.php            # Posts (scheduled/published)
├── PostMetric.php            # Engagement metrics
├── SocialSchedule.php        # Publishing schedule
└── PostApproval.php          # Approval workflow

app/Services/Social/
├── PublishingService.php     # Main publishing orchestrator
├── FacebookPublisher.php     # Facebook-specific
├── InstagramPublisher.php    # Instagram-specific
├── LinkedInPublisher.php     # LinkedIn-specific
└── TikTokPublisher.php       # TikTok-specific

app/Jobs/
├── PublishScheduledPostJob.php
├── FetchPostMetricsJob.php
└── OptimizePostTimingJob.php
```

## 🔄 PUBLISHING WORKFLOW

### 1. Create Post

```php
Route::post('/orgs/{org_id}/social/posts', function (Request $request) {
    $validated = $request->validate([
        'content' => 'required|string|max:5000',
        'platforms' => 'required|array',
        'platforms.*' => 'in:facebook,instagram,linkedin,tiktok,twitter',
        'media' => 'nullable|array',
        'media.*' => 'file|mimes:jpg,png,mp4|max:50000',
        'schedule_for' => 'nullable|date|after:now',
    ]);

    // Create post
    $post = SocialPost::create([
        'org_id' => $request->org_id,
        'content' => $validated['content'],
        'platforms' => $validated['platforms'],
        'status' => $validated['schedule_for'] ? 'scheduled' : 'draft',
        'scheduled_for' => $validated['schedule_for'],
    ]);

    // Upload media
    if (isset($validated['media'])) {
        foreach ($validated['media'] as $file) {
            $post->media()->create([
                'file_path' => $file->store('social-media', 's3'),
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }

    // Schedule publishing job
    if ($post->scheduled_for) {
        PublishScheduledPostJob::dispatch($post)
            ->delay($post->scheduled_for);
    }

    return response()->json($post, 201);
});
```

### 2. Publish Post

```php
// app/Jobs/PublishScheduledPostJob.php
class PublishScheduledPostJob implements ShouldQueue
{
    public function handle(PublishingService $publisher)
    {
        // Set org context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)",
            [CMIS_SYSTEM_USER_ID, $this->post->org_id]);

        foreach ($this->post->platforms as $platform) {
            try {
                $result = $publisher->publish($this->post, $platform);

                // Store platform post ID
                $this->post->platformPosts()->create([
                    'platform' => $platform,
                    'platform_post_id' => $result['id'],
                    'published_at' => now(),
                ]);

            } catch (\Exception $e) {
                \Log::error("Failed to publish to {$platform}: " . $e->getMessage());

                // Retry later
                $this->release(300);
            }
        }

        $this->post->update(['status' => 'published']);
        event(new PostPublished($this->post));
    }
}
```

### 3. Fetch Metrics

```php
// app/Jobs/FetchPostMetricsJob.php
class FetchPostMetricsJob implements ShouldQueue
{
    public function handle()
    {
        $platformPost = $this->platformPost;
        $connector = AdPlatformFactory::make($platformPost->platform);

        $metrics = $connector->getPostMetrics($platformPost->platform_post_id);

        PostMetric::updateOrCreate(
            [
                'platform_post_id' => $platformPost->id,
                'date' => now()->toDateString(),
            ],
            [
                'impressions' => $metrics['impressions'],
                'reach' => $metrics['reach'],
                'engagement' => $metrics['engagement'],
                'clicks' => $metrics['clicks'],
                'likes' => $metrics['likes'],
                'comments' => $metrics['comments'],
                'shares' => $metrics['shares'],
            ]
        );
    }
}
```

## 📅 CONTENT CALENDAR

```php
Route::get('/orgs/{org_id}/social/calendar', function (Request $request) {
    $start = $request->input('start', now()->startOfMonth());
    $end = $request->input('end', now()->endOfMonth());

    $posts = SocialPost::whereBetween('scheduled_for', [$start, $end])
        ->with(['media', 'metrics'])
        ->get()
        ->groupBy(fn($post) => $post->scheduled_for->format('Y-m-d'));

    return response()->json([
        'calendar' => $posts->map(function ($dayPosts, $date) {
            return [
                'date' => $date,
                'posts' => $dayPosts,
                'total' => $dayPosts->count(),
                'platforms' => $dayPosts->pluck('platforms')->flatten()->unique(),
            ];
        })
    ]);
});
```

## 🤖 AI-POWERED BEST TIME ANALYZER

```php
Route::post('/orgs/{org_id}/social/posts/{post_id}/optimize-timing', function ($orgId, $postId) {
    $post = SocialPost::findOrFail($postId);

    // Analyze historical performance
    $historicalData = PostMetric::join('social_posts', 'social_posts.id', '=', 'post_metrics.platform_post_id')
        ->where('social_posts.org_id', $orgId)
        ->selectRaw('
            EXTRACT(DOW FROM published_at) as day_of_week,
            EXTRACT(HOUR FROM published_at) as hour_of_day,
            AVG(engagement_rate) as avg_engagement
        ')
        ->groupBy('day_of_week', 'hour_of_day')
        ->orderByDesc('avg_engagement')
        ->first();

    $recommendedTime = now()
        ->next($historicalData->day_of_week)
        ->setHour($historicalData->hour_of_day);

    return response()->json([
        'current_schedule' => $post->scheduled_for,
        'recommended_time' => $recommendedTime,
        'expected_engagement_increase' => ($historicalData->avg_engagement - 0.05) * 100 . '%',
    ]);
});
```

## 📊 ENGAGEMENT DASHBOARD

```php
Route::get('/orgs/{org_id}/social/dashboard', function () {
    return response()->json([
        'overview' => [
            'total_posts' => SocialPost::count(),
            'scheduled' => SocialPost::where('status', 'scheduled')->count(),
            'published_today' => SocialPost::whereDate('published_at', today())->count(),
        ],
        'platform_breakdown' => DB::select("
            SELECT
                platform,
                COUNT(*) as posts_count,
                SUM(metrics.impressions) as total_impressions,
                AVG(metrics.engagement_rate) as avg_engagement
            FROM social_posts
            LEFT JOIN post_metrics metrics ON metrics.platform_post_id = social_posts.id
            GROUP BY platform
        "),
        'top_performing' => SocialPost::with('metrics')
            ->orderByDesc(DB::raw('(SELECT engagement_rate FROM post_metrics WHERE platform_post_id = social_posts.id ORDER BY created_at DESC LIMIT 1)'))
            ->take(10)
            ->get(),
    ]);
});
```

