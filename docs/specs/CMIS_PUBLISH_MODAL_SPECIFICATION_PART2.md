# CMIS Publish Modal - Part 2: Implementation Details

## 6. Publishing Flow & Logic

### 6.1 Multi-Platform Post Creation Flow

**Step-by-Step Process:**

```
1. User opens publishing modal
   ↓
2. Select target profiles (grouped by Profile Groups)
   - Auto-load brand voice if single group selected
   - Auto-load brand safety policy
   ↓
3. Compose global content
   - AI Assistant available with brand voice
   - Media upload/selection
   - Character counters update in real-time
   ↓
4. (Optional) Customize per network
   - Override caption for specific networks
   - Set network-specific settings
   - Preview how post will appear
   ↓
5. Brand Safety Validation
   - Check content against policies
   - Block or warn based on enforcement level
   - Suggest replacements for banned words
   ↓
6. Select publishing timing
   - Publish now / Schedule / Queue
   - Set date & time for scheduling
   ↓
7. Approval Workflow Check
   - If user requires approval → Submit for approval
   - If admin/owner → Proceed to publish
   ↓
8. Boost Rule Check
   - Detect applicable boost rules
   - Show preview of boost that will apply
   - Allow user to disable for this post
   ↓
9. Publish/Schedule
   - Create post record in database
   - Create per-network variant records
   - Queue publishing jobs
   ↓
10. Background Publishing
    - Execute platform API calls
    - Handle partial failures
    - Update status per network
    - Trigger boost if applicable
```

### 6.2 Handling Partial Failures

**Scenario:** Post succeeds on Instagram and Facebook, but fails on Twitter.

**Behavior:**

```javascript
// Pseudo-code for publishing logic

async function publishPost(post, networks) {
  const results = [];

  for (const network of networks) {
    try {
      const platformPost = await publishToNetwork(network, post);
      results.push({
        network_id: network.id,
        status: 'published',
        platform_post_id: platformPost.id,
        published_at: new Date()
      });

      // Trigger boost if applicable
      await checkAndTriggerBoost(network, platformPost);

    } catch (error) {
      results.push({
        network_id: network.id,
        status: 'failed',
        error_message: error.message,
        retry_count: 0
      });

      // Log failure for retry
      await logFailureForRetry(network, post, error);
    }
  }

  // Update post status
  const overallStatus = results.every(r => r.status === 'published')
    ? 'published'
    : results.some(r => r.status === 'published')
      ? 'partial'
      : 'failed';

  await updatePostStatus(post.id, overallStatus, results);

  // Notify user
  await notifyUser(post.user_id, {
    post_id: post.id,
    status: overallStatus,
    details: results
  });

  return results;
}
```

**User Notification:**

```
┌────────────────────────────────────────────────────────────────┐
│ ⚠️ منشور منشور جزئياً                                         │
├────────────────────────────────────────────────────────────────┤
│ تم نشر منشورك بنجاح على:                                      │
│ ✅ Instagram (@3bs.gents.saloon)                               │
│ ✅ Facebook (3BS Gents Saloon)                                 │
│                                                                │
│ فشل النشر على:                                                │
│ ❌ Twitter (@3BSsaloon)                                        │
│    الخطأ: Rate limit exceeded. Try again in 15 minutes.       │
│                                                                │
│                     [إعادة محاولة Twitter]  [عرض المنشور]     │
└────────────────────────────────────────────────────────────────┘
```

### 6.3 Brand Safety Validation Flow

```typescript
interface ValidationResult {
  is_valid: boolean;
  violations: Array<{
    type: 'banned_word' | 'banned_phrase' | 'profanity' | 'offensive' | 'missing_disclosure';
    message: string;
    location: string; // Where in content
    suggestion?: string; // Replacement suggestion
    severity: 'error' | 'warning';
  }>;
  can_override: boolean; // Based on user role
}

async function validateContent(content: string, policy: BrandSafetyPolicy, userRole: string): Promise<ValidationResult> {
  const violations = [];

  // Check banned words
  if (policy.custom_banned_words.length > 0) {
    for (const word of policy.custom_banned_words) {
      const regex = new RegExp(`\\b${word}\\b`, 'gi');
      if (regex.test(content)) {
        violations.push({
          type: 'banned_word',
          message: `Found banned word: "${word}"`,
          location: content.match(regex).index,
          suggestion: getSuggestion(word, policy),
          severity: policy.enforcement_level === 'block' ? 'error' : 'warning'
        });
      }
    }
  }

  // Check for profanity (if enabled)
  if (policy.prohibit_profanity) {
    const profanityResult = await checkProfanity(content);
    if (profanityResult.found) {
      violations.push({
        type: 'profanity',
        message: 'Profanity detected',
        severity: 'error'
      });
    }
  }

  // Check for required disclosure (for sponsored content)
  if (policy.require_disclosure && isPromotionalContent(content)) {
    if (!content.includes(policy.disclosure_text)) {
      violations.push({
        type: 'missing_disclosure',
        message: `Missing required disclosure: "${policy.disclosure_text}"`,
        suggestion: `Add "${policy.disclosure_text}" to your post`,
        severity: 'error'
      });
    }
  }

  return {
    is_valid: violations.filter(v => v.severity === 'error').length === 0,
    violations,
    can_override: userRole === 'admin' || userRole === 'owner'
  };
}
```

### 6.4 Boost Rule Execution Flow

**After successful publish:**

```typescript
async function checkAndTriggerBoost(profile: SocialProfile, post: PublishedPost) {
  // Find applicable boost rules
  const boostRules = await BoostRule.findAll({
    where: {
      profile_group_id: profile.profile_group_id,
      is_active: true,
      apply_to_social_profiles: { [Op.contains]: [profile.id] }
    }
  });

  for (const rule of boostRules) {
    if (rule.trigger_type === 'auto_after_publish') {
      // Schedule boost job
      const delay = convertToMilliseconds(
        rule.delay_after_publish.value,
        rule.delay_after_publish.unit
      );

      await scheduleJob({
        job_type: 'boost_post',
        scheduled_at: new Date(Date.now() + delay),
        payload: {
          post_id: post.id,
          profile_id: profile.id,
          boost_rule_id: rule.id,
          ad_account_id: rule.ad_account_id,
          config: rule.boost_config
        }
      });

      await logBoostScheduled(post.id, rule.id, delay);
    }

    if (rule.trigger_type === 'auto_performance') {
      // Schedule performance monitoring job
      await schedulePerformanceCheck({
        post_id: post.id,
        rule_id: rule.id,
        check_after: rule.performance_threshold.time_window_hours
      });
    }
  }
}
```

### 6.5 Approval Workflow Flow

**Submission for Approval:**

```typescript
async function submitForApproval(post: Post, workflow: ApprovalWorkflow) {
  // Create approval request
  const approval = await ApprovalRequest.create({
    post_id: post.id,
    workflow_id: workflow.id,
    current_step: 1,
    status: 'pending',
    submitted_by: post.created_by,
    submitted_at: new Date()
  });

  // Get approvers for first step
  const firstStep = workflow.approval_steps.find(s => s.step_number === 1);

  // Notify approvers
  for (const approverId of firstStep.approver_user_ids) {
    await sendNotification({
      user_id: approverId,
      type: 'approval_request',
      title: 'New post awaiting your approval',
      body: `${post.created_by.name} has submitted a post for approval`,
      action_url: `/approvals/${approval.id}`,
      data: {
        approval_id: approval.id,
        post_id: post.id
      }
    });
  }

  // Set timeout if configured
  if (firstStep.timeout_hours) {
    await scheduleJob({
      job_type: 'approval_timeout',
      scheduled_at: addHours(new Date(), firstStep.timeout_hours),
      payload: {
        approval_id: approval.id,
        step_number: 1
      }
    });
  }

  return approval;
}
```

---

## 7. Implementation Plan

### 7.1 Phase 1: Foundation & Profile Groups (Weeks 1-4)

#### Sprint 1 (Week 1-2): Database & Backend

**Tasks:**
1. Create database migrations for 7 new tables
   - `profile_groups`
   - `brand_voices`
   - `brand_safety_policies`
   - `profile_group_members`
   - `approval_workflows`
   - `ad_accounts`
   - `boost_rules`

2. Create Eloquent models with relationships
3. Apply RLS policies to all new tables
4. Create base API controllers and routes

**Deliverables:**
- All migrations executed
- Models with proper relationships
- API endpoints (CRUD) for each entity
- RLS policies tested with multiple orgs

**Acceptance Criteria:**
- Can create/read/update/delete all entities via API
- RLS properly isolates data between organizations
- All foreign key relationships work correctly

#### Sprint 2 (Week 3-4): Profile Groups UI

**Tasks:**
1. Create Profile Groups list page
2. Create single Profile Group detail page
3. Implement Brand Voice modal (without AI generator)
4. Implement Brand Safety modal (without AI generator)
5. Implement team members management
6. Implement social profiles assignment

**Deliverables:**
- Functional Profile Groups management UI
- Brand Voice and Safety policy configuration
- Team member assignment interface

**Acceptance Criteria:**
- Can create and manage profile groups
- Can assign social profiles to groups
- Can configure basic brand voice and safety settings

### 7.2 Phase 2: Publishing Modal Redesign (Weeks 5-8)

#### Sprint 3 (Week 5-6): Core Modal UI

**Tasks:**
1. Redesign modal layout to 3-column structure
2. Implement grouped profile selector
3. Create global composer with toolbar
4. Implement emoji picker
5. Implement saved captions (CRUD)
6. Implement hashtag manager
7. Implement character counters per platform

**Deliverables:**
- 3-column modal layout (desktop)
- Profile selector with groups
- Working toolbar with emoji and saved content
- Real-time character counting

**Acceptance Criteria:**
- Modal displays correctly on desktop and mobile
- Profiles grouped by Profile Group
- Emoji picker works with Arabic support
- Character counters accurate for each platform

#### Sprint 4 (Week 7-8): Per-Network Customization

**Tasks:**
1. Create per-network customization panel
2. Implement tab navigation between networks
3. Create platform-specific settings panels:
   - Instagram (Reel/Story/Post, location, collaborators)
   - Facebook (targeting, boost options)
   - Google Business (post types, CTAs, event/offer)
   - LinkedIn, Twitter, TikTok (basic settings)
4. Implement live preview for each network
5. Implement per-network caption override

**Deliverables:**
- Fully functional per-network customization
- Live previews for each platform
- Network-specific validation

**Acceptance Criteria:**
- Can customize content/settings per network
- Previews accurately reflect platform appearance
- Validation prevents invalid configurations

### 7.3 Phase 3: AI & Advanced Features (Weeks 9-12)

#### Sprint 5 (Week 9-10): AI Integration

**Tasks:**
1. Implement AI Brand Voice generator
2. Implement AI Brand Safety policy generator
3. Enhance AI Assistant with brand voice integration
4. Implement brand safety validation engine
5. Create suggestion system for banned word replacements

**Deliverables:**
- AI generators for brand voice and safety
- Enhanced AI Assistant
- Real-time content validation

**Acceptance Criteria:**
- AI generates appropriate brand voice from inputs
- Content validation catches policy violations
- Suggestions provided for safer alternatives

#### Sprint 6 (Week 11-12): Boost Rules & Approvals

**Tasks:**
1. Implement OAuth flow for ad accounts
2. Create boost rule configuration UI
3. Implement boost scheduling logic
4. Create approval workflow configuration
5. Implement approval request/approve/reject flow
6. Create notifications for approvals

**Deliverables:**
- Ad account connection working
- Boost rules functional
- Approval workflows operational

**Acceptance Criteria:**
- Can connect ad accounts via OAuth
- Boost rules trigger correctly after publish
- Approval workflows block/allow publishing correctly

### 7.4 Phase 4: Polish & Testing (Weeks 13-14)

#### Sprint 7 (Week 13): Polish

**Tasks:**
1. RTL/LTR refinement
2. Mobile responsiveness optimization
3. Error handling improvements
4. Performance optimization
5. Accessibility improvements (WCAG AA)

#### Sprint 8 (Week 14): Testing & Documentation

**Tasks:**
1. Comprehensive end-to-end testing
2. Multi-org isolation testing
3. Load testing (concurrent posts)
4. Documentation completion
5. User training materials

---

## 8. Concrete Artifacts

### 8.1 Database Migration Example

**File:** `database/migrations/2025_11_27_000001_create_profile_groups_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Create profile_groups table
        Schema::create('cmis.profile_groups', function (Blueprint $table) {
            $table->uuid('group_id')->primary();
            $table->uuid('org_id');

            // Basic info
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->jsonb('client_location')->nullable();

            // Visual identity
            $table->text('logo_url')->nullable();
            $table->string('color', 7)->default('#3B82F6');

            // Settings
            $table->string('default_link_shortener', 50)->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->string('language', 10)->default('ar');

            // Relationships
            $table->uuid('brand_voice_id')->nullable();
            $table->uuid('brand_safety_policy_id')->nullable();

            // Metadata
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.organizations')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('cmis.users');

            // Indexes
            $table->index('org_id');
            $table->index('created_by');
        });

        // Enable Row Level Security
        DB::statement('ALTER TABLE cmis.profile_groups ENABLE ROW LEVEL SECURITY');

        // Create RLS policy
        DB::statement("
            CREATE POLICY profile_groups_org_isolation ON cmis.profile_groups
            USING (org_id = current_setting('app.current_org_id', true)::uuid)
            WITH CHECK (org_id = current_setting('app.current_org_id', true)::uuid)
        ");

        // Add profile_group_id to social_integrations table
        Schema::table('cmis_social.social_integrations', function (Blueprint $table) {
            $table->uuid('profile_group_id')->nullable()->after('org_id');
            $table->foreign('profile_group_id')->references('group_id')->on('cmis.profile_groups')->onDelete('set null');
            $table->index('profile_group_id');
        });
    }

    public function down()
    {
        // Remove profile_group_id from social_integrations
        Schema::table('cmis_social.social_integrations', function (Blueprint $table) {
            $table->dropForeign(['profile_group_id']);
            $table->dropColumn('profile_group_id');
        });

        // Drop RLS policy
        DB::statement('DROP POLICY IF EXISTS profile_groups_org_isolation ON cmis.profile_groups');

        // Drop table
        Schema::dropIfExists('cmis.profile_groups');
    }
};
```

### 8.2 API Endpoint Examples

**POST /api/orgs/{org_id}/posts - Create Multi-Platform Post**

**Request:**
```json
{
  "content": "🎉 عرض خاص لفترة محدودة! احصل على خصم 20% على جميع خدماتنا.",
  "media": [
    {
      "asset_id": "550e8400-e29b-41d4-a716-446655440000",
      "type": "image"
    }
  ],
  "publish_type": "scheduled",
  "scheduled_at": "2025-11-30T14:00:00+04:00",
  "label_ids": [
    "660e8400-e29b-41d4-a716-446655440001"
  ],
  "networks": [
    {
      "integration_id": "770e8400-e29b-41d4-a716-446655440002",
      "platform": "instagram",
      "custom_content": null,
      "settings": {
        "post_type": "reel",
        "location_id": "213163652029963",
        "location_name": "Dubai Marina",
        "first_comment": "#دبي #صالون #حلاقة #grooming #dubai",
        "collaborators": [],
        "share_to_feed": true,
        "allow_comments": true
      }
    },
    {
      "integration_id": "880e8400-e29b-41d4-a716-446655440003",
      "platform": "facebook",
      "custom_content": "🎉 عرض خاص! خصم 20% لفترة محدودة على جميع خدمات الحلاقة والعناية.\n\nزورونا الآن واحجز موعدك!",
      "settings": {
        "post_type": "post",
        "location_id": null,
        "targeting": {
          "countries": ["AE", "SA"],
          "age_min": 25,
          "age_max": 45,
          "genders": ["male"]
        }
      }
    },
    {
      "integration_id": "990e8400-e29b-41d4-a716-446655440004",
      "platform": "google_business",
      "settings": {
        "post_type": "offer",
        "cta_type": "book_now",
        "cta_url": "https://3bs-salon.com/book",
        "offer_code": "SAVE20",
        "offer_terms": "صالح حتى 31 ديسمبر 2025. لا يمكن دمجه مع عروض أخرى."
      }
    }
  ]
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "message": "Post scheduled successfully",
  "data": {
    "post_id": "aa0e8400-e29b-41d4-a716-446655440005",
    "status": "scheduled",
    "scheduled_at": "2025-11-30T14:00:00+04:00",
    "networks": [
      {
        "integration_id": "770e8400-e29b-41d4-a716-446655440002",
        "platform": "instagram",
        "status": "scheduled",
        "scheduled_at": "2025-11-30T14:00:00+04:00"
      },
      {
        "integration_id": "880e8400-e29b-41d4-a716-446655440003",
        "platform": "facebook",
        "status": "scheduled",
        "scheduled_at": "2025-11-30T14:00:00+04:00"
      },
      {
        "integration_id": "990e8400-e29b-41d4-a716-446655440004",
        "platform": "google_business",
        "status": "scheduled",
        "scheduled_at": "2025-11-30T14:00:00+04:00"
      }
    ],
    "boost_rules_applicable": [
      {
        "rule_id": "bb0e8400-e29b-41d4-a716-446655440006",
        "rule_name": "Auto-boost Instagram after 2 hours",
        "will_trigger_at": "2025-11-30T16:00:00+04:00"
      }
    ]
  }
}
```

**POST /api/orgs/{org_id}/brand-voices - Create Brand Voice**

**Request:**
```json
{
  "profile_group_id": "cc0e8400-e29b-41d4-a716-446655440007",
  "name": "3BS Official Brand Voice",
  "description": "صالون 3BS يقدم تجربة حلاقة فاخرة للرجال في دبي مع التركيز على الاحترافية والعناية الشخصية",
  "tone": "friendly",
  "personality_traits": ["positive", "professional", "helpful"],
  "inspired_by": ["Rolex", "Emirates Airlines"],
  "target_audience": "رجال أعمال 25-45 في دبي يبحثون عن خدمة راقية",
  "keywords_to_use": ["فخامة", "احترافية", "عناية", "جودة", "تميز"],
  "keywords_to_avoid": ["رخيص", "عادي", "بسيط"],
  "emojis_preference": "moderate",
  "hashtag_strategy": "moderate",
  "primary_language": "ar",
  "dialect_preference": "gulf"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Brand voice created successfully",
  "data": {
    "voice_id": "dd0e8400-e29b-41d4-a716-446655440008",
    "profile_group_id": "cc0e8400-e29b-41d4-a716-446655440007",
    "name": "3BS Official Brand Voice",
    "tone": "friendly",
    "created_at": "2025-11-27T10:30:00+04:00"
  }
}
```

### 8.3 Laravel Model Example

**File:** `app/Models/ProfileGroup.php`

```php
<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileGroup extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.profile_groups';
    protected $primaryKey = 'group_id';

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'client_location',
        'logo_url',
        'color',
        'default_link_shortener',
        'timezone',
        'language',
        'brand_voice_id',
        'brand_safety_policy_id',
        'created_by',
    ];

    protected $casts = [
        'client_location' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships

    public function brandVoice(): BelongsTo
    {
        return $this->belongsTo(BrandVoice::class, 'brand_voice_id', 'voice_id');
    }

    public function brandSafetyPolicy(): BelongsTo
    {
        return $this->belongsTo(BrandSafetyPolicy::class, 'brand_safety_policy_id', 'policy_id');
    }

    public function socialProfiles(): HasMany
    {
        return $this->hasMany(SocialIntegration::class, 'profile_group_id', 'group_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProfileGroupMember::class, 'profile_group_id', 'group_id');
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class, 'profile_group_id', 'group_id');
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class, 'profile_group_id', 'group_id');
    }

    public function boostRules(): HasMany
    {
        return $this->hasMany(BoostRule::class, 'profile_group_id', 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Accessors & Mutators

    public function getProfileCountAttribute(): int
    {
        return $this->socialProfiles()->count();
    }

    public function getTotalFollowersAttribute(): int
    {
        return $this->socialProfiles()->sum('follower_count');
    }

    public function getPlatformsAttribute(): array
    {
        return $this->socialProfiles()
            ->select('platform')
            ->distinct()
            ->pluck('platform')
            ->toArray();
    }

    // Scopes

    public function scopeWithStats($query)
    {
        return $query->withCount('socialProfiles')
            ->withCount('members')
            ->with(['brandVoice', 'brandSafetyPolicy']);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('socialProfiles', function ($q) {
            $q->where('status', 'active');
        });
    }
}
```

### 8.4 Publishing Job Example

**File:** `app/Jobs/PublishPostToNetwork.php`

```php
<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostNetworkContent;
use App\Services\Platform\PlatformServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPostToNetwork implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(
        public PostNetworkContent $networkContent
    ) {}

    public function handle()
    {
        try {
            // Set RLS context
            \DB::statement("SET app.current_org_id = '{$this->networkContent->post->org_id}'");

            // Get platform service
            $platformService = PlatformServiceFactory::make($this->networkContent->platform);

            // Prepare post data
            $postData = [
                'content' => $this->networkContent->custom_content ?? $this->networkContent->post->content,
                'media' => $this->networkContent->custom_media ?? $this->networkContent->post->media,
                'settings' => $this->networkContent->platform_settings,
            ];

            // Publish to platform
            $result = $platformService->publishPost(
                $this->networkContent->integration,
                $postData
            );

            // Update network content status
            $this->networkContent->update([
                'status' => 'published',
                'published_at' => now(),
                'platform_post_id' => $result['platform_post_id'],
            ]);

            // Log success
            Log::info("Post published successfully", [
                'post_id' => $this->networkContent->post_id,
                'platform' => $this->networkContent->platform,
                'platform_post_id' => $result['platform_post_id'],
            ]);

            // Trigger boost if applicable
            dispatch(new CheckAndTriggerBoost($this->networkContent));

        } catch (\Exception $e) {
            // Update network content with error
            $this->networkContent->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Log error
            Log::error("Post publishing failed", [
                'post_id' => $this->networkContent->post_id,
                'platform' => $this->networkContent->platform,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        // Mark as permanently failed after all retries
        $this->networkContent->update([
            'status' => 'failed',
            'error_message' => "Failed after {$this->tries} attempts: " . $exception->getMessage(),
        ]);

        // Notify user
        dispatch(new SendPublishingFailureNotification($this->networkContent));
    }
}
```

---

## 9. RTL & Localization Considerations

### 9.1 Layout Direction

**CSS Strategy:**

```css
/* Use logical properties for RTL/LTR compatibility */
.publishing-modal {
  padding-inline-start: 1rem;  /* padding-left in LTR, padding-right in RTL */
  padding-inline-end: 1rem;
  margin-inline-start: auto;
  margin-inline-end: auto;
}

/* Flexbox with direction */
.modal-header {
  display: flex;
  flex-direction: row; /* Will automatically reverse in RTL */
  gap: 1rem;
}

/* Grid for 3-column layout */
.modal-body {
  display: grid;
  grid-template-columns: 280px 1fr 380px; /* LTR */
  direction: ltr; /* Force LTR for consistent layout */
}

[dir="rtl"] .modal-body {
  grid-template-columns: 380px 1fr 280px; /* RTL: reverse columns */
}

/* Icons that should NOT flip in RTL */
.icon-no-flip {
  transform: scaleX(1);
}

[dir="rtl"] .icon-no-flip {
  transform: scaleX(-1); /* Flip back */
}
```

### 9.2 Text Input Handling

**Automatic Direction Detection:**

```javascript
function detectTextDirection(text) {
  // Arabic Unicode range
  const arabicPattern = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/;

  // Check first strong character
  for (let char of text) {
    if (arabicPattern.test(char)) {
      return 'rtl';
    }
    if (/[a-zA-Z]/.test(char)) {
      return 'ltr';
    }
  }

  return 'ltr'; // Default
}

// Auto-apply direction to textarea
const contentTextarea = document.getElementById('post-content');
contentTextarea.addEventListener('input', (e) => {
  const direction = detectTextDirection(e.target.value);
  e.target.setAttribute('dir', direction);
});
```

### 9.3 Mixed Content Handling

**Example: Arabic caption with English hashtags**

```
النص بالعربية مع هاشتاقات بالإنجليزية #dubai #luxury
```

**Rendering Strategy:**

```html
<div dir="rtl" class="post-content">
  <span class="arabic-text">النص بالعربية مع هاشتاقات بالإنجليزية</span>
  <span dir="ltr" class="hashtags">#dubai #luxury</span>
</div>
```

### 9.4 Number Formatting

**Arabic vs English Numerals:**

```javascript
function formatNumber(num, locale = 'ar-AE') {
  return new Intl.NumberFormat(locale).format(num);
}

// Examples:
formatNumber(1234, 'ar-AE'); // "١٬٢٣٤" (Eastern Arabic numerals)
formatNumber(1234, 'en-US'); // "1,234" (Western Arabic numerals)

// For consistency in UI, use Western numerals even in Arabic:
formatNumber(1234, 'ar-u-nu-latn'); // "1,234" (Western in Arabic locale)
```

### 9.5 Date & Time Formatting

```javascript
function formatDateTime(date, locale = 'ar-AE', timeZone = 'Asia/Dubai') {
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZone: timeZone
  };

  return new Intl.DateTimeFormat(locale, options).format(date);
}

// Examples:
formatDateTime(new Date(), 'ar-AE');
// "٢٧ نوفمبر ٢٠٢٥، ١٠:٣٠"

formatDateTime(new Date(), 'en-US');
// "November 27, 2025, 10:30 AM"
```

---

## 10. Testing Strategy

### 10.1 Unit Tests

**Example: Brand Safety Validation Test**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\BrandSafetyPolicy;
use App\Services\BrandSafetyValidator;
use Tests\TestCase;

class BrandSafetyValidatorTest extends TestCase
{
    public function test_detects_banned_words()
    {
        $policy = BrandSafetyPolicy::factory()->create([
            'custom_banned_words' => ['رخيص', 'سيء'],
            'enforcement_level' => 'block'
        ]);

        $validator = new BrandSafetyValidator($policy);

        $result = $validator->validate('منتج رخيص للبيع');

        $this->assertFalse($result->is_valid);
        $this->assertCount(1, $result->violations);
        $this->assertEquals('banned_word', $result->violations[0]->type);
    }

    public function test_allows_clean_content()
    {
        $policy = BrandSafetyPolicy::factory()->create([
            'custom_banned_words' => ['رخيص'],
        ]);

        $validator = new BrandSafetyValidator($policy);

        $result = $validator->validate('منتج عالي الجودة');

        $this->assertTrue($result->is_valid);
        $this->assertCount(0, $result->violations);
    }
}
```

### 10.2 Integration Tests

**Example: Multi-Platform Publishing Test**

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialIntegration;
use App\Models\User;
use Tests\TestCase;

class MultiPlatformPublishingTest extends TestCase
{
    public function test_can_publish_to_multiple_platforms()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create social integrations
        $igProfile = SocialIntegration::factory()->create([
            'org_id' => $user->org_id,
            'platform' => 'instagram',
            'status' => 'active'
        ]);

        $fbProfile = SocialIntegration::factory()->create([
            'org_id' => $user->org_id,
            'platform' => 'facebook',
            'status' => 'active'
        ]);

        // Mock platform services
        $this->mock(InstagramService::class)
            ->shouldReceive('publishPost')
            ->once()
            ->andReturn(['platform_post_id' => 'ig_123']);

        $this->mock(FacebookService::class)
            ->shouldReceive('publishPost')
            ->once()
            ->andReturn(['platform_post_id' => 'fb_456']);

        // Send request
        $response = $this->postJson("/api/orgs/{$user->org_id}/posts", [
            'content' => 'Test post',
            'publish_type' => 'now',
            'networks' => [
                ['integration_id' => $igProfile->integration_id, 'platform' => 'instagram'],
                ['integration_id' => $fbProfile->integration_id, 'platform' => 'facebook'],
            ]
        ]);

        $response->assertStatus(202);

        // Assert post created
        $this->assertDatabaseHas('cmis_social.social_posts', [
            'org_id' => $user->org_id,
            'content' => 'Test post',
        ]);

        // Assert network content created for both platforms
        $this->assertDatabaseCount('cmis_social.post_network_content', 2);
    }
}
```

---

## 11. Summary & Next Steps

### 11.1 What This Specification Delivers

**Complete System:**
1. ✅ Profile Groups with brand voice and safety policies
2. ✅ Redesigned 3-column publishing modal
3. ✅ Per-network content customization
4. ✅ Advanced AI assistant with brand voice integration
5. ✅ Boost rules and automatic promotion
6. ✅ Approval workflows for team collaboration
7. ✅ Multi-platform publishing with partial failure handling
8. ✅ RTL-first design with perfect Arabic support

**Technical Deliverables:**
- 7 new database tables with RLS policies
- 25+ new API endpoints
- 5 new UI pages for profile groups
- Completely redesigned publishing modal
- Brand safety validation engine
- Boost rule execution system
- Approval workflow engine

### 11.2 Recommended Implementation Order

**Priority 1 (Must Have):**
1. Profile Groups foundation (tables, models, basic CRUD)
2. Publishing modal 3-column layout
3. Grouped profile selector
4. Per-network customization panels
5. Brand safety validation (basic)

**Priority 2 (Should Have):**
1. Brand Voice integration with AI
2. Saved captions, hashtags, mentions
3. Media library
4. Advanced AI assistant
5. Boost rules (basic)

**Priority 3 (Nice to Have):**
1. Approval workflows
2. Advanced targeting (Facebook)
3. Google Business post types
4. Performance-based boost triggers
5. Analytics integration

### 11.3 Success Metrics

**Post-Implementation Goals:**
- Reduce time to publish multi-platform post by 60%
- Increase content consistency across platforms by 80%
- Zero brand safety violations after AI integration
- 50% reduction in content approval time with workflows
- 30% increase in engagement through boost automation

---

*End of Part 2 - Implementation Details*
*For Part 1 (Gap Analysis, Profile Groups UX, API Architecture), see main document.*
