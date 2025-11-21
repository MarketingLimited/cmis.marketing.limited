# Google AI Integration Guide for CMIS
**Created:** 2025-11-21
**APIs:** Gemini 3 (Image Generation) + Veo 3.1 (Video Generation)
**Purpose:** AI-powered ad design and video creation for multi-platform campaigns

---

## Overview

CMIS will integrate two Google AI APIs for automated creative content generation:

1. **Gemini 3** - High-quality ad design and image generation (4K output)
2. **Veo 3.1** - Professional video ad creation with native audio

Both APIs are accessible through the Gemini API and Vertex AI platforms.

---

## 🎨 Gemini 3 - Image/Ad Design Generation

### Model Versions

| Model | Context (In/Out) | Primary Use Case |
|-------|------------------|------------------|
| `gemini-3-pro-preview` | 1M / 64k tokens | General creative work |
| `gemini-3-pro-image-preview` | 65k / 32k tokens | Specialized image generation |

### Key Features for Ad Design

**Native 4K Generation:**
- Upscaling to 2K and 4K resolutions
- Sharp, legible text rendering in images
- High-quality diagrams and graphics

**Grounded Generation:**
- Google Search integration for fact verification
- Ensures brand-safe, accurate content
- Reduces hallucinations in factual content

**Multi-turn Editing:**
- Conversational editing through "thought signatures"
- Preserves visual context across iterations
- Refine designs based on feedback

**Media Resolution Control:**
- `media_resolution_low` - Fast, lower quality
- `media_resolution_medium` - Balanced (recommended for most)
- `media_resolution_high` - Maximum quality (recommended for final ads)

### API Access

**Authentication:**
```bash
# Get API key from https://aistudio.google.com/apikey
export GOOGLE_AI_API_KEY="your-api-key"
```

**SDKs Available:**
- Python
- JavaScript/Node.js
- Java
- REST API

### Pricing Structure

| Tier | Input Tokens | Output Tokens | Image Output |
|------|--------------|---------------|--------------|
| Standard (<200k) | $2/M | $12/M | $0.134+ (varies by resolution) |
| Large (>200k) | $4/M | $18/M | Same as above |

**Cost Estimation for CMIS:**
- Single ad design generation: ~$0.15 - $0.50 (depending on complexity)
- Batch generation (10 ads): ~$1.50 - $5.00

### Rate Limits

- **Context Caching:** Minimum 2,048-token threshold
- **Batch API:** Supported for bulk operations
- **Recommended:** Use batch API for campaign-level generation

### Sample Request (Python SDK)

```python
import google.generativeai as genai

genai.configure(api_key=os.environ["GOOGLE_AI_API_KEY"])

model = genai.GenerativeModel(
    model_name="gemini-3-pro-image-preview",
    generation_config={
        "temperature": 1.0,  # Keep default
        "media_resolution": "media_resolution_high"
    }
)

response = model.generate_content([
    "Create a 4K Facebook ad design for a fitness app launch. Include:",
    "- Bold headline: 'Transform Your Body in 30 Days'",
    "- Vibrant gradient background (blue to purple)",
    "- Mobile app mockup showcasing workout tracking",
    "- Call-to-action button: 'Download Now'",
    "- Modern, energetic typography"
])

# Extract image from response
image_data = response.parts[0].inline_data.data
```

### CMIS Integration Points

**Existing Service:** `app/Services/AI/AiContentOrchestrator.php`

**New Methods to Add:**
```php
public function generateAdDesign(
    string $campaignObjective,
    string $brandGuidelines,
    array $designRequirements,
    string $resolution = 'high'
): array {
    // Call Gemini 3 API
    // Return image URLs and metadata
}

public function batchGenerateDesigns(
    array $campaigns,
    int $variationsPerCampaign = 3
): array {
    // Use Batch API for bulk generation
}
```

**Database Storage:**
```sql
-- New table: cmis_ai.generated_media
CREATE TABLE cmis_ai.generated_media (
    id UUID PRIMARY KEY,
    org_id UUID NOT NULL,
    campaign_id UUID REFERENCES cmis.campaigns(id),
    media_type VARCHAR(20) CHECK (media_type IN ('image', 'video')),
    ai_model VARCHAR(50), -- 'gemini-3-pro-image-preview'
    prompt_text TEXT,
    media_url TEXT,
    resolution VARCHAR(20),
    generation_cost DECIMAL(10,4),
    metadata JSONB, -- Model version, parameters, etc.
    created_at TIMESTAMP DEFAULT NOW()
);

-- Add RLS policy
ALTER TABLE cmis_ai.generated_media ENABLE ROW LEVEL SECURITY;
CREATE POLICY org_isolation ON cmis_ai.generated_media
USING (org_id = current_setting('app.current_org_id')::uuid);
```

---

## 🎥 Veo 3.1 - Video Ad Generation

### Model Versions

| Model | Speed | Quality | Use Case |
|-------|-------|---------|----------|
| `veo-3.1` | Standard | Premium | High-quality campaign videos |
| `veo-3.1-fast` | 2x faster | Good | Quick previews, A/B testing |

### Key Features for Video Ads

**Native Audio Generation:**
- Synchronized sound effects
- Voiceover-ready audio tracks
- Cinematic audio mixing

**Enhanced Narrative Control:**
- Better cinematic style understanding
- Improved storytelling coherence
- Character consistency across scenes

**Image-to-Video:**
- Convert static ad designs to video
- Superior prompt adherence
- Better visual quality than Veo 3

**Advanced Capabilities:**

1. **Reference Image Guidance** (Up to 3 images)
   - Maintain character consistency
   - Apply specific visual styles
   - Brand guideline adherence

2. **Scene Extension**
   - Extend videos beyond initial duration
   - Create connected clips
   - Maintain visual continuity

3. **Frame Interpolation**
   - Smooth transitions between start/end frames
   - Generate intermediate frames
   - Create animated sequences

### API Access

**Platform Options:**
- Gemini API (Python SDK)
- Vertex AI Console
- Google AI Studio (Veo Studio demo)
- Gemini App and Flow platform

**Authentication (Vertex AI):**
```bash
# Set up Google Cloud credentials
gcloud auth application-default login
export GOOGLE_CLOUD_PROJECT="your-project-id"
```

### Request Format

**Endpoint:**
```
POST https://aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/publishers/google/models/{MODEL_ID}:predict
```

**Required Parameters:**
- `PROJECT_ID` - Google Cloud project ID
- `MODEL_ID` - `veo-3.1` or `veo-3.1-fast`
- `TEXT_PROMPT` - Video generation prompt

**Optional Parameters:**
- `OUTPUT_STORAGE_URI` - Cloud Storage bucket for output
- `RESPONSE_COUNT` - Number of videos (1-4)
- `DURATION` - Video length (5-8 seconds for Veo 3.1)
- `INPUT_IMAGE` - Base64-encoded image for image-to-video
- `ASPECT_RATIO` - 16:9 or 9:16 (recommend 720p+ input images)

**Sample Request (Python):**
```python
from google.cloud import aiplatform

aiplatform.init(project="your-project-id", location="us-central1")

endpoint = aiplatform.Endpoint(endpoint_name="veo-3.1-endpoint")

response = endpoint.predict(
    instances=[{
        "text_prompt": """
        Create a 7-second video ad for a luxury watch brand:
        - Opening: Close-up of watch mechanism in motion
        - Mid: Watch on wrist, elegant hand gestures
        - Closing: Brand logo reveal with sophisticated audio
        - Style: Cinematic, high-end, slow-motion
        - Color grading: Deep blacks, golden highlights
        """,
        "duration": 7,
        "aspect_ratio": "16:9",
        "output_storage_uri": "gs://cmis-video-ads/campaign-123/"
    }]
)

# Output stored in Cloud Storage
video_url = response.predictions[0]["video_uri"]
```

**Image-to-Video Example:**
```python
import base64

# Read ad design generated by Gemini 3
with open("ad_design_4k.png", "rb") as f:
    image_data = base64.b64encode(f.read()).decode()

response = endpoint.predict(
    instances=[{
        "text_prompt": "Animate this ad design with subtle zoom-in and parallax effects",
        "input_image": image_data,
        "duration": 6,
        "aspect_ratio": "9:16"  # Instagram Stories format
    }]
)
```

### Response Format

**If `OUTPUT_STORAGE_URI` provided:**
- Long-running operation initiated
- Videos saved to Cloud Storage bucket
- Response includes GCS URIs

**If no storage URI:**
- Base64-encoded video bytes returned in response
- Immediate but limited to smaller files

### Pricing

- Same pricing structure as Veo 3 (not publicly detailed yet)
- Estimated cost per video: $0.50 - $2.00 (based on duration and quality)
- Veo 3.1 Fast: Potentially 20-30% cheaper

### Rate Limits

- **Concurrent requests:** 10 per project
- **Daily quota:** 1,000 videos (can request increase)
- **Recommended:** Queue video generation jobs

### CMIS Integration Points

**New Service:** `app/Services/AI/VeoVideoService.php`

```php
<?php

namespace App\Services\AI;

use Google\Cloud\AIPlatform\V1\AIPlatformClient;
use Illuminate\Support\Facades\Storage;

class VeoVideoService
{
    private AIPlatformClient $client;
    private string $projectId;
    private string $location = 'us-central1';

    public function __construct()
    {
        $this->projectId = config('services.google.project_id');
        $this->client = new AIPlatformClient([
            'credentials' => config('services.google.credentials_path')
        ]);
    }

    /**
     * Generate video ad from text prompt
     */
    public function generateFromText(
        string $prompt,
        int $duration = 7,
        string $aspectRatio = '16:9',
        bool $useFastModel = false
    ): string {
        $model = $useFastModel ? 'veo-3.1-fast' : 'veo-3.1';
        $endpoint = $this->getEndpoint($model);

        $response = $endpoint->predict([
            'instances' => [[
                'text_prompt' => $prompt,
                'duration' => $duration,
                'aspect_ratio' => $aspectRatio,
                'output_storage_uri' => $this->getStorageUri()
            ]]
        ]);

        return $response->predictions[0]['video_uri'];
    }

    /**
     * Convert static ad image to video
     */
    public function imageToVideo(
        string $imagePath,
        string $animationPrompt,
        int $duration = 6
    ): string {
        $imageData = base64_encode(Storage::get($imagePath));

        $response = $this->client->predict([
            'instances' => [[
                'text_prompt' => $animationPrompt,
                'input_image' => $imageData,
                'duration' => $duration
            ]]
        ]);

        return $response->predictions[0]['video_uri'];
    }

    /**
     * Generate multiple video variations
     */
    public function batchGenerate(
        array $prompts,
        int $variationsPerPrompt = 2
    ): array {
        // Queue jobs for background processing
        foreach ($prompts as $prompt) {
            dispatch(new GenerateVideoJob($prompt, $variationsPerPrompt));
        }
    }

    private function getStorageUri(): string
    {
        $orgId = auth()->user()->org_id;
        return "gs://cmis-video-ads/{$orgId}/" . uniqid('video_');
    }
}
```

**New Job:** `app/Jobs/GenerateVideoJob.php`

```php
<?php

namespace App\Jobs;

use App\Services\AI\VeoVideoService;
use App\Models\AI\GeneratedMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $campaignId,
        private string $prompt,
        private array $options = []
    ) {}

    public function handle(VeoVideoService $veoService): void
    {
        $videoUrl = $veoService->generateFromText(
            $this->prompt,
            $this->options['duration'] ?? 7,
            $this->options['aspect_ratio'] ?? '16:9'
        );

        GeneratedMedia::create([
            'org_id' => auth()->user()->org_id,
            'campaign_id' => $this->campaignId,
            'media_type' => 'video',
            'ai_model' => 'veo-3.1',
            'prompt_text' => $this->prompt,
            'media_url' => $videoUrl,
            'metadata' => $this->options
        ]);
    }
}
```

---

## 🔄 Integration Workflow

### Campaign Creation Flow

```
1. User creates campaign in wizard (Step 1-2)
   ↓
2. User selects "Generate with AI" in Step 3 (Creative)
   ↓
3. System calls Gemini 3 API for ad design variations
   ├─ Generate 3 static designs (4K)
   └─ Store in cmis_ai.generated_media
   ↓
4. User selects preferred design
   ↓
5. System calls Veo 3.1 API for video conversion
   ├─ Generate 6-second animated version
   ├─ Generate 15-second extended version
   └─ Store videos in Cloud Storage + database
   ↓
6. User previews and approves
   ↓
7. Assets attached to campaign and published to platforms
```

### Controller Integration

**Update:** `app/Http/Controllers/Api/AiContentController.php`

```php
public function generateAdCreative(GenerateAdCreativeRequest $request): JsonResponse
{
    $validated = $request->validated();

    // Step 1: Generate static design with Gemini 3
    $geminiService = new GeminiImageService();
    $designs = $geminiService->generateAdDesign(
        campaignObjective: $validated['objective'],
        brandGuidelines: $validated['brand_guidelines'],
        designRequirements: $validated['requirements'],
        variationCount: 3
    );

    // Step 2: (Optional) Convert best design to video
    if ($validated['generate_video']) {
        $veoService = new VeoVideoService();
        $video = $veoService->imageToVideo(
            imagePath: $designs[0]['url'],
            animationPrompt: $validated['animation_style'],
            duration: $validated['video_duration'] ?? 6
        );

        return response()->json([
            'designs' => $designs,
            'video' => $video,
            'quota_used' => $this->quotaService->getRemainingQuota()
        ]);
    }

    return response()->json(['designs' => $designs]);
}
```

---

## 💰 Cost Management

### Quota System Integration

**Update:** `cmis.ai_usage_quotas` table

```sql
-- Add new columns for image/video generation
ALTER TABLE cmis.ai_usage_quotas
ADD COLUMN image_quota_daily INTEGER DEFAULT 50,
ADD COLUMN image_quota_monthly INTEGER DEFAULT 500,
ADD COLUMN video_quota_daily INTEGER DEFAULT 10,
ADD COLUMN video_quota_monthly INTEGER DEFAULT 100;
```

**Middleware:** `app/Http/Middleware/CheckAiQuota.php`

```php
public function handle(Request $request, Closure $next, string $quotaType)
{
    $user = auth()->user();
    $quotaService = app(AiQuotaService::class);

    $quotaMap = [
        'image' => ['daily' => 'image_quota_daily', 'monthly' => 'image_quota_monthly'],
        'video' => ['daily' => 'video_quota_daily', 'monthly' => 'video_quota_monthly']
    ];

    if (!$quotaService->hasQuotaRemaining($user->org_id, $quotaMap[$quotaType])) {
        return response()->json([
            'error' => 'AI quota exceeded',
            'upgrade_url' => route('subscription.upgrade')
        ], 429);
    }

    return $next($request);
}
```

### Subscription Tier Limits

| Plan | Daily Images | Daily Videos | Monthly Images | Monthly Videos |
|------|--------------|--------------|----------------|----------------|
| Free | 5 | 0 | 50 | 0 |
| Pro | 50 | 10 | 500 | 100 |
| Enterprise | Unlimited | Unlimited | Unlimited | Unlimited |

---

## 🧪 Testing Strategy

### Unit Tests

```php
// tests/Unit/Services/GeminiImageServiceTest.php
public function test_generates_ad_design_with_correct_parameters()
{
    $service = new GeminiImageService();

    $result = $service->generateAdDesign(
        campaignObjective: 'awareness',
        brandGuidelines: 'Modern, minimalist',
        designRequirements: ['4K', 'Blue color scheme']
    );

    $this->assertIsArray($result);
    $this->assertArrayHasKey('url', $result[0]);
    $this->assertStringContainsString('4K', $result[0]['metadata']['resolution']);
}
```

### Feature Tests

```php
// tests/Feature/AiContentGenerationTest.php
public function test_authenticated_user_can_generate_ad_creative()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/ai/generate-ad-creative', [
        'objective' => 'conversions',
        'brand_guidelines' => 'Bold, energetic',
        'requirements' => ['Include product mockup', 'Call-to-action']
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['designs', 'quota_used']);
}

public function test_quota_limit_prevents_excessive_generation()
{
    $user = User::factory()->create();
    // Exhaust daily quota
    AiUsageLog::factory()->count(50)->create(['org_id' => $user->org_id]);

    $response = $this->actingAs($user)->postJson('/api/ai/generate-ad-creative', [...]);

    $response->assertStatus(429)
             ->assertJson(['error' => 'AI quota exceeded']);
}
```

---

## 🔒 Security Considerations

### API Key Management

```php
// config/services.php
return [
    'google' => [
        'ai_api_key' => env('GOOGLE_AI_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),

        // Encrypted storage for organization-specific keys
        'use_org_keys' => env('GOOGLE_USE_ORG_KEYS', false),
    ]
];
```

**NEVER commit API keys to git.** Store in `.env` file:

```bash
GOOGLE_AI_API_KEY="AIza..."
GOOGLE_CLOUD_PROJECT="cmis-production"
GOOGLE_APPLICATION_CREDENTIALS="/path/to/credentials.json"
```

### Content Moderation

```php
// Before generating content, validate prompts
public function validatePrompt(string $prompt): bool
{
    $blockedTerms = ['violence', 'explicit', 'illegal'];

    foreach ($blockedTerms as $term) {
        if (stripos($prompt, $term) !== false) {
            throw new InvalidPromptException("Prompt contains prohibited content");
        }
    }

    return true;
}
```

### Rate Limiting

```php
// app/Http/Middleware/AiRateLimit.php
public function handle(Request $request, Closure $next, string $model)
{
    $key = "ai_rate_limit:{$model}:" . auth()->id();

    $attempts = Cache::get($key, 0);

    if ($attempts >= config("services.google.rate_limits.{$model}")) {
        return response()->json(['error' => 'Rate limit exceeded'], 429);
    }

    Cache::put($key, $attempts + 1, now()->addMinutes(1));

    return $next($request);
}
```

---

## 📊 Monitoring & Analytics

### Track AI Usage

```php
// app/Models/AI/AiUsageLog.php (existing model)
// Add new columns for image/video generation

Schema::table('cmis_ai.ai_usage_logs', function (Blueprint $table) {
    $table->string('generation_type')->nullable(); // 'image', 'video'
    $table->decimal('cost_usd', 10, 4)->nullable();
    $table->integer('media_resolution')->nullable();
    $table->integer('video_duration')->nullable();
});
```

### Dashboard Metrics

```php
// Display in analytics dashboard
public function getAiUsageStats(string $orgId, string $period = 'month'): array
{
    return [
        'images_generated' => AiUsageLog::where('org_id', $orgId)
            ->where('generation_type', 'image')
            ->count(),
        'videos_generated' => AiUsageLog::where('org_id', $orgId)
            ->where('generation_type', 'video')
            ->count(),
        'total_cost' => AiUsageLog::where('org_id', $orgId)
            ->sum('cost_usd'),
        'quota_remaining' => [
            'images' => $this->getRemainingQuota($orgId, 'image'),
            'videos' => $this->getRemainingQuota($orgId, 'video')
        ]
    ];
}
```

---

## 🚀 Deployment Checklist

### Before Production

- [ ] Obtain Google Cloud Project ID and enable APIs
- [ ] Generate API keys for Gemini API
- [ ] Set up Vertex AI authentication (service account)
- [ ] Configure Cloud Storage bucket for video output
- [ ] Add environment variables to `.env`
- [ ] Test API connectivity in staging
- [ ] Set up quota monitoring alerts
- [ ] Implement cost tracking dashboard
- [ ] Add rate limiting middleware
- [ ] Configure content moderation rules
- [ ] Write comprehensive tests (unit + feature)
- [ ] Document API usage for team
- [ ] Set up error logging for API failures
- [ ] Create backup plan for API downtime

### Environment Variables

```bash
# .env.production
GOOGLE_AI_API_KEY="your-production-key"
GOOGLE_CLOUD_PROJECT="cmis-production"
GOOGLE_APPLICATION_CREDENTIALS="/var/www/cmis/credentials/google-service-account.json"
GOOGLE_STORAGE_BUCKET="cmis-video-ads-prod"
GOOGLE_USE_ORG_KEYS=false
```

---

## 📚 Resources

- **Gemini 3 Documentation:** https://ai.google.dev/gemini-api/docs/gemini-3
- **Veo 3.1 Blog Post:** https://developers.googleblog.com/en/introducing-veo-3-1-and-new-creative-capabilities-in-the-gemini-api/
- **Vertex AI Veo Reference:** https://docs.cloud.google.com/vertex-ai/generative-ai/docs/model-reference/veo-video-generation
- **Google AI Studio:** https://aistudio.google.com/
- **Pricing Calculator:** https://cloud.google.com/products/calculator

---

## ✅ Next Steps

1. **Phase 1:** Set up Google Cloud project and API access
2. **Phase 2:** Implement `GeminiImageService` with basic image generation
3. **Phase 3:** Add database migrations for `generated_media` table
4. **Phase 4:** Integrate Gemini 3 into Campaign Wizard (Step 3)
5. **Phase 5:** Implement `VeoVideoService` for video generation
6. **Phase 6:** Add quota management and rate limiting
7. **Phase 7:** Build AI usage analytics dashboard
8. **Phase 8:** Production testing and optimization

**Estimated Timeline:** 4-6 weeks for full integration

---

**Last Updated:** 2025-11-21
**Author:** Claude Code AI Assistant
**Status:** Research Complete - Ready for Implementation
