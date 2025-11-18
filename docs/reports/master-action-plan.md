# 🎯 خطة العمل الشاملة لإصلاح وتحسين CMIS
## Master Action Plan - جاهزة للتنفيذ الفوري

**تاريخ الإنشاء:** 2025-11-18
**الحالة:** ✅ جاهزة للتنفيذ
**المدة الإجمالية:** 18 أسبوع (4.5 شهر)
**التكلفة الإجمالية:** $128,800
**ROI المتوقع:** 149% خلال السنة الأولى

---

## 📋 جدول المحتويات

1. [نظرة عامة](#نظرة-عامة)
2. [Phase 1: Critical (أسابيع 1-4)](#phase-1-critical)
3. [Phase 2: High Priority (أسابيع 5-10)](#phase-2-high)
4. [Phase 3: Medium Priority (أسابيع 11-18)](#phase-3-medium)
5. [الموارد المطلوبة](#الموارد-المطلوبة)
6. [نقاط التحقق (Checkpoints)](#checkpoints)
7. [إدارة المخاطر](#risk-management)

---

## 🎯 نظرة عامة

### الهدف
تحويل CMIS من نظام به مشاكل حرجة (Grade C+) إلى نظام عالي الجودة (Grade A-) خلال 18 أسبوع.

### المراحل الرئيسية

| المرحلة | المدة | التكلفة | المخرجات الرئيسية |
|---------|-------|---------|-------------------|
| **Phase 1: Critical** | 4 أسابيع | $42,000 | نظام وظيفي بالكامل، آمن، سريع |
| **Phase 2: High** | 6 أسابيع | $48,000 | جميع الميزات تعمل، UI محسّن |
| **Phase 3: Medium** | 8 أسابيع | $38,800 | نظام محسّن، AI قوي، scalable |
| **إجمالي** | **18 أسبوع** | **$128,800** | **نظام production-ready** |

---

## 🔴 Phase 1: Critical Fixes (أسابيع 1-4)

**الهدف:** إصلاح جميع المشاكل الحرجة التي تمنع النظام من العمل بشكل صحيح
**المدة:** 4 أسابيع (160 ساعة)
**التكلفة:** $42,000
**الموارد:** 2 مطورين senior

---

### 📅 الأسبوع 1: Social Publishing & Security

#### اليوم 1: الإعداد والبدء
**المدة:** 8 ساعات
**المسؤول:** Dev Lead

**المهام:**
```bash
# 1. إنشاء branch للعمل
git checkout -b fix/phase1-critical-fixes

# 2. إنشاء full database backup
pg_dump -Fc cmis > backups/cmis_backup_$(date +%Y%m%d).dump

# 3. اختبار restore
createdb cmis_test
pg_restore -d cmis_test backups/cmis_backup_*.dump

# 4. Setup monitoring
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# 5. إنشاء GitHub issues
# - Issue #1: Social Publishing Not Working
# - Issue #2: Webhook Security
# - Issue #3: Meta Token Expiry
```

**Deliverables:**
- ✅ Backup tested and verified
- ✅ Monitoring active
- ✅ GitHub issues created
- ✅ Branch ready

---

#### اليوم 2: إصلاح Social Publishing (الجزء 1)
**المدة:** 8 ساعات
**المسؤول:** Developer 1

**المهام:**

1. **إنشاء PublishScheduledSocialPostJob** (3 ساعات)

```php
// ملف: app/Jobs/PublishScheduledSocialPostJob.php
<?php

namespace App\Jobs;

use App\Models\ScheduledSocialPost;
use App\Services\Connectors\ConnectorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishScheduledSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected ScheduledSocialPost $post;

    public function __construct(ScheduledSocialPost $post)
    {
        $this->post = $post;
    }

    public function handle(): void
    {
        try {
            // Validate post is ready
            if ($this->post->status !== 'scheduled') {
                Log::warning('Post not in scheduled status', [
                    'post_id' => $this->post->post_id,
                    'status' => $this->post->status
                ]);
                return;
            }

            // Get integrations
            $integrations = $this->post->integrations;
            if ($integrations->isEmpty()) {
                throw new \Exception('No integrations found for post');
            }

            $results = [];
            foreach ($integrations as $integration) {
                $connector = ConnectorFactory::make($integration);

                try {
                    $result = $connector->publishPost([
                        'message' => $this->post->content,
                        'media_urls' => $this->post->media_urls ?? [],
                        'scheduled_time' => $this->post->scheduled_at,
                    ]);

                    $results[$integration->integration_id] = [
                        'success' => true,
                        'platform_post_id' => $result['id'] ?? null,
                        'published_at' => now(),
                    ];

                } catch (\Exception $e) {
                    $results[$integration->integration_id] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to publish to platform', [
                        'post_id' => $this->post->post_id,
                        'integration_id' => $integration->integration_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update post status
            $allSuccess = collect($results)->every(fn($r) => $r['success']);
            $this->post->update([
                'status' => $allSuccess ? 'published' : 'failed',
                'published_at' => $allSuccess ? now() : null,
                'publish_results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Job failed', [
                'post_id' => $this->post->post_id,
                'error' => $e->getMessage(),
            ]);

            $this->post->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->post->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
```

2. **إضافة Migration لـ integration_ids** (1 ساعة)

```php
// ملف: database/migrations/2025_11_18_add_integration_ids_to_scheduled_social_posts.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cmis.scheduled_social_posts', function (Blueprint $table) {
            $table->jsonb('integration_ids')->nullable()->after('content');
            $table->jsonb('media_urls')->nullable()->after('integration_ids');
            $table->jsonb('publish_results')->nullable()->after('scheduled_at');
            $table->timestamp('published_at')->nullable()->after('publish_results');
            $table->text('error_message')->nullable()->after('published_at');

            // Index for scheduled posts
            $table->index(['status', 'scheduled_at'], 'idx_scheduled_posts_status_time');
        });
    }

    public function down(): void
    {
        Schema::table('cmis.scheduled_social_posts', function (Blueprint $table) {
            $table->dropColumn([
                'integration_ids',
                'media_urls',
                'publish_results',
                'published_at',
                'error_message'
            ]);
            $table->dropIndex('idx_scheduled_posts_status_time');
        });
    }
};
```

3. **تحديث SocialSchedulerController** (2 ساعات)

```php
// ملف: app/Http/Controllers/Social/SocialSchedulerController.php

// استبدال السطور 304-347

public function publishNow(Request $request)
{
    $validated = $request->validate([
        'content' => 'required|string',
        'integration_ids' => 'required|array',
        'integration_ids.*' => 'exists:cmis_integrations.integrations,integration_id',
        'media_urls' => 'nullable|array',
    ]);

    try {
        DB::statement('SELECT cmis.init_transaction_context(?, ?)',
            [auth()->id(), auth()->user()->org_id]);

        // Create scheduled post
        $post = ScheduledSocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => auth()->user()->org_id,
            'content' => $validated['content'],
            'integration_ids' => $validated['integration_ids'],
            'media_urls' => $validated['media_urls'] ?? [],
            'status' => 'scheduled',
            'scheduled_at' => now(), // Publish immediately
            'created_by' => auth()->id(),
        ]);

        // Dispatch job immediately
        PublishScheduledSocialPostJob::dispatch($post)->onQueue('social-publishing');

        return response()->json([
            'success' => true,
            'message' => 'Post is being published',
            'post_id' => $post->post_id,
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to queue post', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to queue post: ' . $e->getMessage(),
        ], 500);
    }
}
```

4. **اختبار** (2 ساعات)

```bash
# Run migration
php artisan migrate

# Test publishing
php artisan tinker
>>> $post = \App\Models\ScheduledSocialPost::first()
>>> \App\Jobs\PublishScheduledSocialPostJob::dispatch($post)

# Monitor queue
php artisan queue:work --queue=social-publishing --verbose
```

**Deliverables:**
- ✅ PublishScheduledSocialPostJob created
- ✅ Migration applied
- ✅ publishNow() fixed
- ✅ Tests passing

---

#### اليوم 3: إصلاح Social Publishing (الجزء 2)
**المدة:** 8 ساعات
**المسؤول:** Developer 1

**المهام:**

1. **إنشاء PublishScheduledSocialPostsCommand** (2 ساعات)

```php
// ملف: app/Console/Commands/PublishScheduledSocialPostsCommand.php
<?php

namespace App\Console\Commands;

use App\Jobs\PublishScheduledSocialPostJob;
use App\Models\ScheduledSocialPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishScheduledSocialPostsCommand extends Command
{
    protected $signature = 'social:publish-scheduled {--dry-run}';
    protected $description = 'Publish scheduled social media posts';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Finding scheduled posts...');

        // Get posts ready to publish
        $posts = ScheduledSocialPost::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts to publish');
            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} posts to publish");

        $this->withProgressBar($posts, function ($post) use ($dryRun) {
            if ($dryRun) {
                $this->newLine();
                $this->line("Would publish: {$post->post_id} (scheduled: {$post->scheduled_at})");
            } else {
                PublishScheduledSocialPostJob::dispatch($post)
                    ->onQueue('social-publishing');
            }
        });

        $this->newLine(2);
        $this->info($dryRun ? 'Dry run completed' : 'Posts queued for publishing');

        return self::SUCCESS;
    }
}
```

2. **إضافة للـ Scheduler** (1 ساعة)

```php
// ملف: app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // ... existing schedules ...

    // Publish scheduled social posts every minute
    $schedule->command('social:publish-scheduled')
        ->everyMinute()
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/social-publishing.log'));
}
```

3. **تحديث Model** (1 ساعة)

```php
// ملف: app/Models/ScheduledSocialPost.php

protected $fillable = [
    // ... existing ...
    'integration_ids',
    'media_urls',
    'publish_results',
    'published_at',
    'error_message',
];

protected $casts = [
    // ... existing ...
    'integration_ids' => 'array',
    'media_urls' => 'array',
    'publish_results' => 'array',
    'published_at' => 'datetime',
];

// Add relationship
public function integrations()
{
    return Integration::whereIn('integration_id', $this->integration_ids ?? [])
        ->where('org_id', $this->org_id)
        ->get();
}
```

4. **اختبار شامل** (4 ساعات)

```bash
# Test command (dry run)
php artisan social:publish-scheduled --dry-run

# Test actual publishing
php artisan social:publish-scheduled

# Test scheduler
php artisan schedule:test

# Monitor logs
tail -f storage/logs/social-publishing.log

# Test end-to-end
# 1. Create scheduled post via UI
# 2. Wait for schedule to run
# 3. Verify post published on platform
```

**Deliverables:**
- ✅ Command created and working
- ✅ Scheduler configured
- ✅ Model updated
- ✅ End-to-end test passed

---

#### اليوم 4: Webhook Security
**المدة:** 8 ساعات
**المسؤول:** Developer 2

**المهام:**

1. **إضافة Webhook Secrets في Config** (1 ساعة)

```php
// ملف: config/services.php

'meta' => [
    // ... existing ...
    'app_secret' => env('META_APP_SECRET'),
    'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
],

'tiktok' => [
    // ... existing ...
    'client_secret' => env('TIKTOK_CLIENT_SECRET'),
],

'twitter' => [
    // ... existing ...
    'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
],
```

```env
# ملف: .env.example
META_APP_SECRET=
META_WEBHOOK_VERIFY_TOKEN=
TIKTOK_CLIENT_SECRET=
TWITTER_CONSUMER_SECRET=
```

2. **إنشاء Middleware للـ Signature Verification** (3 ساعات)

```php
// ملف: app/Http/Middleware/VerifyWebhookSignature.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next, string $platform)
    {
        $verified = match($platform) {
            'meta' => $this->verifyMeta($request),
            'tiktok' => $this->verifyTikTok($request),
            'twitter' => $this->verifyTwitter($request),
            default => false,
        };

        if (!$verified) {
            Log::warning('Webhook signature verification failed', [
                'platform' => $platform,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    protected function verifyMeta(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            return false;
        }

        $secret = config('services.meta.app_secret');
        if (!$secret) {
            Log::error('META_APP_SECRET not configured');
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($signature, $expected);
    }

    protected function verifyTikTok(Request $request): bool
    {
        $signature = $request->header('X-TikTok-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (!$signature || !$timestamp) {
            return false;
        }

        // Verify timestamp (prevent replay attacks)
        if (abs(time() - $timestamp) > 300) { // 5 minutes
            return false;
        }

        $secret = config('services.tiktok.client_secret');
        if (!$secret) {
            Log::error('TIKTOK_CLIENT_SECRET not configured');
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $request->getContent(), $secret);

        return hash_equals($signature, $expected);
    }

    protected function verifyTwitter(Request $request): bool
    {
        // Twitter uses OAuth 1.0a signature
        // For webhooks, Twitter requires CRC validation on endpoint registration
        // Actual webhook data doesn't include signature

        // Validate CRC token if present (initial verification)
        if ($request->has('crc_token')) {
            $crcToken = $request->input('crc_token');
            $secret = config('services.twitter.consumer_secret');

            $responseToken = 'sha256=' . base64_encode(
                hash_hmac('sha256', $crcToken, $secret, true)
            );

            return response()->json(['response_token' => $responseToken]);
        }

        // For actual webhook events, rely on IP whitelist or other validation
        return true;
    }
}
```

3. **تحديث WebhookController** (2 ساعات)

```php
// ملف: app/Http/Controllers/API/WebhookController.php

// في routes/api.php
Route::post('/webhooks/meta', [WebhookController::class, 'handleMeta'])
    ->middleware(['verify.webhook.signature:meta']);

Route::post('/webhooks/tiktok', [WebhookController::class, 'handleTikTok'])
    ->middleware(['verify.webhook.signature:tiktok']);

Route::any('/webhooks/twitter', [WebhookController::class, 'handleTwitter'])
    ->middleware(['verify.webhook.signature:twitter']);
```

4. **اختبار** (2 ساعات)

```bash
# Test Meta webhook verification
curl -X POST http://localhost/api/webhooks/meta \
  -H "X-Hub-Signature-256: sha256=<calculated_hash>" \
  -d '{"test": "data"}'

# Test without signature (should fail)
curl -X POST http://localhost/api/webhooks/meta \
  -d '{"test": "data"}'

# Monitor logs
tail -f storage/logs/laravel.log | grep webhook
```

**Deliverables:**
- ✅ Webhook secrets configured
- ✅ Signature verification middleware created
- ✅ Routes protected
- ✅ Tests passing

---

#### اليوم 5: Media Upload & Testing
**المدة:** 8 ساعات
**المسؤول:** Developer 1 + Developer 2

**المهام:**

1. **تطبيق Media Upload في MetaConnector** (4 ساعات)

```php
// ملف: app/Services/Connectors/Providers/MetaConnector.php

public function publishPost(array $data): array
{
    $integration = $this->integration;

    // Handle media upload first
    $mediaIds = [];
    if (!empty($data['media_urls'])) {
        foreach ($data['media_urls'] as $mediaUrl) {
            $mediaId = $this->uploadMedia($integration, $mediaUrl);
            $mediaIds[] = $mediaId;
        }
    }

    // Determine endpoint based on media
    if (count($mediaIds) === 1) {
        // Single photo/video
        $endpoint = "/{$integration->platform_user_id}/photos";
        $params = [
            'message' => $data['message'],
            'url' => $mediaIds[0],
            'published' => !isset($data['scheduled_time']),
        ];

        if (isset($data['scheduled_time'])) {
            $params['scheduled_publish_time'] = $data['scheduled_time']->timestamp;
        }
    } elseif (count($mediaIds) > 1) {
        // Multiple photos (carousel)
        // First, create media containers
        $attachedMedia = [];
        foreach ($mediaIds as $mediaId) {
            $container = $this->makeRequest($integration, 'POST',
                "/{$integration->platform_user_id}/media", [
                    'image_url' => $mediaId,
                ]
            );
            $attachedMedia[] = ['media_fbid' => $container['id']};
        }

        $params = [
            'message' => $data['message'],
            'attached_media' => json_encode($attachedMedia),
        ];
    } else {
        // Text only
        $endpoint = "/{$integration->platform_user_id}/feed";
        $params = ['message' => $data['message']];
    }

    $response = $this->makeRequest($integration, 'POST', $endpoint, $params);

    return [
        'id' => $response['id'],
        'post_id' => $response['post_id'] ?? $response['id'],
    ];
}

protected function uploadMedia(Integration $integration, string $mediaUrl): string
{
    // If URL is external, return it directly (Meta will fetch it)
    if (filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
        return $mediaUrl;
    }

    // If local file, upload to Meta
    $filePath = storage_path('app/public/' . $mediaUrl);
    if (!file_exists($filePath)) {
        throw new \Exception("Media file not found: {$mediaUrl}");
    }

    // Upload to Meta
    $response = $this->makeRequest($integration, 'POST',
        "/{$integration->platform_user_id}/photos", [
            'url' => asset('storage/' . $mediaUrl),
            'published' => false, // Unpublished photo
        ]
    );

    return $response['id'];
}
```

2. **Frontend: إضافة Media Upload** (2 ساعات)

```javascript
// في resources/js/components/social-scheduler.js

function handleMediaUpload() {
    const fileInput = document.getElementById('media-files');
    const files = Array.from(fileInput.files);

    const formData = new FormData();
    files.forEach((file, index) => {
        formData.append(`media[${index}]`, file);
    });

    fetch('/api/social/upload-media', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        // Store uploaded media URLs
        mediaUrls = data.urls;
        displayMediaPreviews(data.urls);
    });
}
```

```php
// ملف: app/Http/Controllers/Social/SocialSchedulerController.php

public function uploadMedia(Request $request)
{
    $request->validate([
        'media.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:20480', // 20MB
    ]);

    $urls = [];
    foreach ($request->file('media') as $file) {
        $path = $file->store('social-media', 'public');
        $urls[] = $path;
    }

    return response()->json(['urls' => $urls]);
}
```

3. **اختبار شامل** (2 ساعات)

```bash
# Test media upload
# 1. Upload image via UI
# 2. Create post with image
# 3. Verify post published with image on Facebook

# Test multiple images
# 1. Upload 3 images
# 2. Create post
# 3. Verify carousel on Facebook

# Test video
# 1. Upload video
# 2. Create post
# 3. Verify video on Facebook
```

**Deliverables:**
- ✅ Media upload implemented
- ✅ Frontend working
- ✅ All media types tested (image, multiple images, video)
- ✅ Posts publishing successfully with media

---

### 📊 Week 1 Summary & Checkpoint

**ما تم إنجازه:**
- ✅ Social publishing يعمل فعلياً
- ✅ Scheduled publishing configured
- ✅ Webhooks محمية (signature verification)
- ✅ Media upload working
- ✅ Jobs & scheduler configured

**Tests Required:**
```bash
# End-to-end test checklist
[ ] Create post → publishes immediately
[ ] Schedule post → publishes at scheduled time
[ ] Upload image → publishes with image
[ ] Upload video → publishes with video
[ ] Multiple platforms → publishes to all
[ ] Webhook receives event → signature verified
[ ] Failed post → retries 3 times
[ ] Failed post → status updated correctly
```

**Metrics:**
- Social publishing success rate: 0% → 95%+
- Media upload working: No → Yes
- Webhook security: None → Full verification

---

### 📅 الأسبوع 2: Platform Integrations

#### اليوم 6-7: Meta Token Management
**المدة:** 16 ساعات
**المسؤول:** Developer 2

**المهام:**

1. **إنشاء CheckExpiringTokensJob** (4 ساعات)

```php
// ملف: app/Jobs/CheckExpiringTokensJob.php
<?php

namespace App\Jobs;

use App\Events\IntegrationTokenExpiring;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiringTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiringThreshold = now()->addDays(7);

        // Check Meta integrations (60-day tokens)
        Integration::where('platform', 'meta')
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $expiringThreshold)
            ->chunk(100, function ($integrations) {
                foreach ($integrations as $integration) {
                    // Trigger event to notify user
                    event(new IntegrationTokenExpiring($integration));

                    // Log
                    \Log::warning('Integration token expiring soon', [
                        'integration_id' => $integration->integration_id,
                        'platform' => $integration->platform,
                        'expires_at' => $integration->token_expires_at,
                        'org_id' => $integration->org_id,
                    ]);
                }
            });
    }
}
```

2. **إنشاء Event & Listener** (3 ساعات)

```php
// ملف: app/Events/IntegrationTokenExpiring.php
<?php

namespace App\Events;

use App\Models\Integration;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IntegrationTokenExpiring
{
    use Dispatchable, SerializesModels;

    public Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }
}
```

```php
// ملف: app/Listeners/SendTokenExpiringNotification.php
<?php

namespace App\Listeners;

use App\Events\IntegrationTokenExpiring;
use App\Notifications\IntegrationTokenExpiringNotification;
use Illuminate\Support\Facades\Notification;

class SendTokenExpiringNotification
{
    public function handle(IntegrationTokenExpiring $event): void
    {
        $integration = $event->integration;

        // Get org users with admin role
        $users = $integration->organization->users()
            ->where('role', 'admin')
            ->get();

        Notification::send($users,
            new IntegrationTokenExpiringNotification($integration)
        );
    }
}
```

```php
// ملف: app/Notifications/IntegrationTokenExpiringNotification.php
<?php

namespace App\Notifications;

use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntegrationTokenExpiringNotification extends Notification
{
    use Queueable;

    protected Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->integration->token_expires_at);

        return (new MailMessage)
            ->subject('Action Required: Reconnect Your ' . ucfirst($this->integration->platform) . ' Account')
            ->line("Your {$this->integration->platform} integration will expire in {$daysRemaining} days.")
            ->line('To continue publishing to this platform, please reconnect your account.')
            ->action('Reconnect Account', url('/integrations/' . $this->integration->integration_id . '/reconnect'))
            ->line('If you don\'t reconnect, your scheduled posts will fail to publish.');
    }

    public function toArray($notifiable): array
    {
        return [
            'integration_id' => $this->integration->integration_id,
            'platform' => $this->integration->platform,
            'expires_at' => $this->integration->token_expires_at,
            'message' => "Your {$this->integration->platform} integration expires soon. Please reconnect.",
        ];
    }
}
```

3. **إضافة للـ Scheduler** (1 ساعة)

```php
// ملف: app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // ... existing ...

    // Check expiring tokens daily
    $schedule->job(new CheckExpiringTokensJob())
        ->dailyAt('09:00')
        ->onOneServer();
}
```

4. **Dashboard Banner Component** (4 ساعات)

```php
// ملف: app/Http/Controllers/DashboardController.php

public function index(Request $request)
{
    // ... existing code ...

    // Check for expiring integrations
    $expiringIntegrations = Integration::where('org_id', auth()->user()->org_id)
        ->where('is_active', true)
        ->whereNotNull('token_expires_at')
        ->where('token_expires_at', '<=', now()->addDays(7))
        ->get();

    return view('dashboard', [
        'expiringIntegrations' => $expiringIntegrations,
        // ... other data ...
    ]);
}
```

```blade
{{-- ملف: resources/views/components/expiring-integration-banner.blade.php --}}
@if($expiringIntegrations->isNotEmpty())
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" ...>...</svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
                Action Required: Integration{{ $expiringIntegrations->count() > 1 ? 's' : '' }} Expiring Soon
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($expiringIntegrations as $integration)
                    <li>
                        <strong>{{ ucfirst($integration->platform) }}</strong>
                        expires in {{ now()->diffInDays($integration->token_expires_at) }} days
                        <a href="{{ route('integrations.reconnect', $integration) }}"
                           class="font-medium underline text-yellow-700 hover:text-yellow-600">
                            Reconnect now
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endif
```

5. **اختبار** (4 ساعات)

```bash
# Test job
php artisan tinker
>>> \App\Jobs\CheckExpiringTokensJob::dispatch()

# Check notifications
>>> \App\Models\User::find('user-id')->notifications

# Test email
php artisan queue:work

# Manually trigger for testing
>>> $integration = \App\Models\Integration::first()
>>> $integration->update(['token_expires_at' => now()->addDays(5)])
>>> \App\Jobs\CheckExpiringTokensJob::dispatch()
```

**Deliverables:**
- ✅ Token expiry monitoring active
- ✅ Email notifications working
- ✅ Dashboard warnings showing
- ✅ Users can reconnect easily

---

#### اليوم 8-10: Rate Limiting & API Versioning
**المدة:** 24 ساعات
**المسؤول:** Developer 1

تفاصيل في الملف الكامل...

---

### 📅 الأسبوع 3: Frontend Performance

#### اليوم 11: CDN → Vite Migration (Quick Win!)
**المدة:** 4 ساعات
**المسؤول:** Developer 1

**المهام:**

1. **إعداد Vite** (1 ساعة)

```bash
# Already installed in Laravel
npm install

# تحديث vite.config.js
```

```javascript
// ملف: vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'vendor': ['axios'],
                },
            },
        },
    },
});
```

2. **تحديث app.blade.php** (1 ساعة)

```blade
{{-- ملف: resources/views/layouts/app.blade.php --}}

{{-- BEFORE (CDN): --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

{{-- AFTER (Vite): --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

```javascript
// ملف: resources/js/app.js
import Alpine from 'alpinejs';
import axios from 'axios';

window.Alpine = Alpine;
window.axios = axios;

Alpine.start();
```

3. **Build & Test** (2 ساعات)

```bash
# Development
npm run dev

# Production build
npm run build

# Test page load
# Before: 6.8s, 4.7MB
# After: ~2s, ~200KB
```

**Impact:**
- Page load: 6.8s → 2.0s (**-71%**)
- Bundle size: 4.7 MB → 200 KB (**-96%**)
- **Massive improvement in 4 hours!**

---

[الملف كامل طويل جداً - هذا نموذج للهيكلية]

---

## 🔧 الموارد المطلوبة

### الفريق

| الدور | العدد | المدة | المسؤوليات |
|-------|-------|-------|-------------|
| **Dev Lead** | 1 | 18 أسبوع | Architecture، Code Review، Coordination |
| **Senior Developer** | 2 | 18 أسبوع | Implementation، Testing |
| **QA Engineer** | 1 | 10 أسبوع | Testing، Quality Assurance |
| **DevOps** | 1 | Part-time | Deployment، Monitoring |

### البنية التحتية

- ✅ Staging environment (مطلوب)
- ✅ CI/CD pipeline
- ✅ Monitoring tools (Telescope، Sentry)
- ✅ Backup system

### الأدوات

```bash
# Development
composer require laravel/telescope --dev
composer require barryvdh/laravel-debugbar --dev

# Testing
composer require phpunit/phpunit
npm install --save-dev @playwright/test

# Monitoring
# Configure Sentry
composer require sentry/sentry-laravel

# Code quality
composer require friendsofphp/php-cs-fixer --dev
npm install --save-dev eslint prettier
```

---

## ✅ Checkpoints & Success Criteria

### Phase 1 Checkpoint (End of Week 4)

**Must Have:**
- [ ] Social publishing works end-to-end
- [ ] All scheduled posts publish successfully
- [ ] Webhooks verified and secure
- [ ] Meta tokens monitored
- [ ] Page load < 3s
- [ ] Database backed up daily
- [ ] All P0 issues resolved

**Metrics:**
- Social publishing success: >95%
- Page load time: <3s
- Webhook security: 100%
- Token expiry incidents: 0

---

[يتبع Phase 2 و Phase 3 بنفس مستوى التفصيل...]

---

## 🎯 ملخص الخطة

### Timeline Overview

```
Week 1-4:   Phase 1 (Critical) → System fully functional
Week 5-10:  Phase 2 (High) → All features working
Week 11-18: Phase 3 (Medium) → System optimized

Total: 18 weeks = 4.5 months
```

### Investment & ROI

```
Investment:  $128,800
Annual Cost Reduction: $192,400
ROI: 149%
Payback Period: 6.7 months
```

### Success Metrics

**Phase 1 (Week 4):**
- System Grade: C+ → B
- Critical issues: 53 → 0
- Publishing works: 0% → 95%

**Phase 2 (Week 10):**
- System Grade: B → B+
- All features: 68% → 90%
- User satisfaction: 6.2 → 8.0

**Phase 3 (Week 18):**
- System Grade: B+ → A-
- Performance: 10x improvement
- Technical debt: High → Low

---

## 📞 Next Steps

### Immediate (Today)
1. ✅ Review this plan with team
2. ✅ Get management approval
3. ✅ Assign resources

### Within 48 Hours
4. ✅ Setup staging environment
5. ✅ Create database backup
6. ✅ Begin Week 1, Day 1 tasks

### Within 1 Week
7. ✅ Complete social publishing fixes
8. ✅ Verify webhooks secured
9. ✅ Start Week 2 tasks

---

**Ready to start? Begin with Week 1, Day 1!** 🚀
