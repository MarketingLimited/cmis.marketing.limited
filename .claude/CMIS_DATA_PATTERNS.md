# CMIS Data Patterns & Real Examples
## Actual Data Structures from Database Backup & Seeders

**Source Files:**
- `database/backup-db-for-seeds.sql` - Database structure & constraints
- `database/seeders/DemoDataSeeder.php` - Real data examples
- `database/seeders/ExtendedDemoDataSeeder.php` - Extended data patterns

**Analyzed:** 2025-11-18
**Purpose:** Provide AI agents with REAL data examples and patterns

---

## 🎯 CRITICAL CONTEXT SYSTEM DISCOVERIES

### Three Context Types (CONFIRMED!)

From **ExtendedDemoDataSeeder** (lines 336-357):

```php
private function createContextsBase()
{
    foreach ($this->orgIds as $orgName => $orgId) {
        $contexts = [
            ['type' => 'creative', 'name' => "$orgName Creative Context"],
            ['type' => 'value', 'name' => "$orgName Value Proposition"],
            ['type' => 'offering', 'name' => "$orgName Product Context"],
        ];
    }
}
```

**Three Context Types:**
1. **`creative`** - Creative Context (brand guidelines, visual style, messaging)
2. **`value`** - Value Proposition (target audience, key messages, positioning)
3. **`offering`** - Product/Service Context (features, pricing, details)

### Value Contexts - Real Structure

From **ExtendedDemoDataSeeder** (lines 359-391):

```php
DB::table('cmis.value_contexts')->insert([
    'context_id' => Str::uuid(),
    'org_id' => $campaign->org_id,
    'offering_id' => null,
    'segment_id' => null,
    'campaign_id' => $campaignId,
    'channel_id' => $this->channelIds['instagram'] ?? null,
    'format_id' => null,
    'locale' => 'en',                              // Language
    'awareness_stage' => 'problem_aware',          // Awareness level
    'funnel_stage' => 'middle_of_funnel',          // Funnel position
    'framework' => 'AIDA',                          // Marketing framework
    'tone' => 'professional',                       // Brand tone
    'dataset_ref' => null,
    'variant_tag' => 'A',                           // A/B test variant
    'tags' => json_encode(['digital', 'b2b']),     // Context tags
    'market_id' => null,
    'industry_id' => null,
    'created_at' => now(),
    'context_fingerprint' => md5($campaignId . time()), // Unique identifier
]);
```

**Key Fields:**

| Field | Type | Example Values | Purpose |
|-------|------|----------------|---------|
| `locale` | string | 'en', 'ar' | Language/localization |
| `awareness_stage` | enum | 'problem_aware', 'solution_aware', 'product_aware' | Customer awareness level |
| `funnel_stage` | enum | 'top_of_funnel', 'middle_of_funnel', 'bottom_of_funnel' | Sales funnel position |
| `framework` | enum | 'AIDA', 'PAS', 'FAB' | Marketing framework used |
| `tone` | enum | 'professional', 'casual', 'friendly', 'formal' | Brand voice |
| `variant_tag` | string | 'A', 'B', 'C' | Test variation identifier |
| `tags` | json | ['digital', 'b2b'], ['social', 'b2c'] | Context tags |
| `context_fingerprint` | string | MD5 hash | Unique context identifier |

---

## 📝 CREATIVE BRIEFS - Real Data Structure

From **DemoDataSeeder** (lines 663-730):

### TechVision Solutions Example:

```php
'brief_data' => json_encode([
    'objective' => 'Drive awareness and trial signups for CloudSync Pro',
    'target_audience' => 'Enterprise IT Directors and CTOs',
    'key_messages' => [
        'Seamless cloud synchronization for enterprise teams',
        'Bank-grade security and compliance',
        '99.99% uptime guarantee'
    ],
    'brand_guidelines' => [
        'tone' => 'professional, trustworthy, innovative',
        'colors' => ['#0066CC', '#FFFFFF', '#F5F5F5'],
        'fonts' => ['Inter', 'Roboto']
    ],
    'deliverables' => [
        'Social media posts (Instagram, Facebook, LinkedIn)',
        'Ad creatives for Facebook/Instagram',
        'Landing page copy'
    ],
    'timeline' => '3 weeks',
    'budget' => '$25,000'
])
```

### FashionHub Retail Example:

```php
'brief_data' => json_encode([
    'objective' => 'Launch summer collection and drive online sales',
    'target_audience' => 'Fashion-forward women aged 18-35',
    'key_messages' => [
        'Trendy, affordable summer fashion',
        'New arrivals every week',
        'Free shipping on orders over €50'
    ],
    'brand_guidelines' => [
        'tone' => 'playful, inspiring, trendy',
        'colors' => ['#FF6B9D', '#FFA07A', '#FFFFFF'],
        'style' => 'bright, vibrant, lifestyle photography'
    ],
    'deliverables' => [
        'Instagram carousel posts',
        'Instagram Stories',
        'Product photography',
        'Lifestyle shots'
    ],
    'timeline' => '2 months',
    'budget' => '€15,000'
])
```

**Brief Structure (JSONB):**

```typescript
interface CreativeBrief {
    objective: string;              // What to achieve
    target_audience: string;        // Who to target
    key_messages: string[];         // Array of key messages
    brand_guidelines: {
        tone: string;               // Brand voice
        colors: string[];           // Hex color codes
        fonts?: string[];           // Font families
        style?: string;             // Visual style description
    };
    deliverables: string[];         // What to produce
    timeline: string;               // Project timeline
    budget: string;                 // Budget with currency
}
```

---

## 🎨 FIELD DEFINITIONS SYSTEM

From **ExtendedDemoDataSeeder** (lines 127-179):

### Campaign Module Fields:

```php
'campaign' => [
    [
        'name' => 'Campaign Name',
        'slug' => 'campaign_name',
        'data_type' => 'string',
        'description' => 'Name of the campaign',
        'required' => true,
        'validations' => ['max_length' => 255]
    ],
    [
        'name' => 'Objective',
        'slug' => 'objective',
        'data_type' => 'enum',
        'description' => 'Campaign objective',
        'options' => ['awareness', 'conversion', 'engagement'],
        'required' => true
    ],
    [
        'name' => 'Budget',
        'slug' => 'budget',
        'data_type' => 'decimal',
        'description' => 'Campaign budget',
        'required' => true,
        'validations' => ['min' => 0]
    ],
    [
        'name' => 'Target Audience',
        'slug' => 'target_audience',
        'data_type' => 'text',
        'description' => 'Description of target audience',
        'required' => false
    ],
]
```

### Creative Module Fields:

```php
'creative' => [
    [
        'name' => 'Headline',
        'slug' => 'headline',
        'data_type' => 'string',
        'description' => 'Main headline',
        'required' => true,
        'validations' => ['max_length' => 100]
    ],
    [
        'name' => 'Body Copy',
        'slug' => 'body_copy',
        'data_type' => 'text',
        'description' => 'Main body text',
        'required' => true
    ],
    [
        'name' => 'Call to Action',
        'slug' => 'cta',
        'data_type' => 'string',
        'description' => 'CTA button text',
        'required' => true,
        'validations' => ['max_length' => 30]
    ],
    [
        'name' => 'Brand Voice',
        'slug' => 'brand_voice',
        'data_type' => 'enum',
        'description' => 'Tone of voice',
        'options' => ['professional', 'friendly', 'casual', 'formal'],
        'required' => false
    ],
]
```

### Social Module Fields:

```php
'social' => [
    [
        'name' => 'Platform',
        'slug' => 'platform',
        'data_type' => 'enum',
        'description' => 'Social platform',
        'options' => ['facebook', 'instagram', 'twitter', 'linkedin'],
        'required' => true
    ],
    [
        'name' => 'Post Type',
        'slug' => 'post_type',
        'data_type' => 'enum',
        'description' => 'Type of post',
        'options' => ['image', 'video', 'carousel', 'story'],
        'required' => true
    ],
    [
        'name' => 'Hashtags',
        'slug' => 'hashtags',
        'data_type' => 'string',
        'description' => 'Post hashtags',
        'is_list' => true,  // Array field!
        'required' => false
    ],
]
```

**Field Definition Structure:**

```typescript
interface FieldDefinition {
    field_id: uuid;
    module_id: uuid;                // Links to module
    name: string;                   // Display name
    slug: string;                   // Programmatic name
    data_type: 'string' | 'text' | 'decimal' | 'enum' | 'boolean';
    is_list: boolean;               // Is this an array?
    description: string;
    enum_options?: string[];        // For enum types
    required_default: boolean;
    validations: {
        max_length?: number;
        min?: number;
        max?: number;
        pattern?: string;
    };
}
```

---

## 🎯 CAMPAIGN EXAMPLES

From **DemoDataSeeder** (lines 349-402):

### Enterprise Tech Campaign:

```php
[
    'campaign_id' => Str::uuid(),
    'org_id' => $this->orgIds['TechVision Solutions'],
    'name' => 'CloudSync Pro Launch Campaign',
    'objective' => 'conversions',
    'status' => 'active',
    'start_date' => now()->subDays(15),
    'end_date' => now()->addDays(45),
    'budget' => 25000.00,
    'currency' => 'USD',
    'description' => 'Product launch campaign for CloudSync Pro targeting enterprise customers',
    'context_id' => null,       // Base context
    'creative_id' => null,      // Creative context
    'value_id' => null,         // Value context
    'created_by' => $userId,
]
```

### Fashion Retail Campaign:

```php
[
    'campaign_id' => Str::uuid(),
    'org_id' => $this->orgIds['FashionHub Retail'],
    'name' => 'Summer Collection 2025',
    'objective' => 'catalog_sales',
    'status' => 'active',
    'start_date' => now()->subDays(30),
    'end_date' => now()->addDays(60),
    'budget' => 15000.00,
    'currency' => 'EUR',
    'description' => 'Promote summer fashion collection across social media',
    'context_id' => null,
    'creative_id' => null,
    'value_id' => null,
    'created_by' => $userId,
]
```

**Campaign Objectives (Real Examples):**
- `conversions` - Drive conversions
- `catalog_sales` - E-commerce sales
- `awareness` - Brand awareness
- `engagement` - Social engagement

**Campaign Status Enum (From SQL Backup):**
- `draft` - Not yet started
- `active` - Currently running
- `paused` - Temporarily stopped
- `completed` - Finished
- `archived` - Historical record

---

## 🎨 CREATIVE ASSETS - Real Examples

From **DemoDataSeeder** (lines 404-443):

```php
[
    'asset_id' => Str::uuid(),
    'org_id' => $orgId,
    'campaign_id' => $campaignId,
    'channel_id' => $this->channelIds['instagram'],
    'format_id' => $this->formatIds['instagram']['feed_square'],
    'variation_tag' => 'A',
    'copy_block' => 'Transform your enterprise with CloudSync Pro',
    'art_direction' => json_encode([
        'theme' => 'professional',
        'colors' => ['#0066CC', '#FFFFFF'],
        'style' => 'modern_minimal',
    ]),
    'compliance_meta' => json_encode(['approved' => true]),
    'final_copy' => json_encode([
        'headline' => 'CloudSync Pro - Enterprise Cloud Platform',
        'body' => 'Seamless collaboration for modern teams. Start your free trial today.',
        'cta' => 'Learn More',
    ]),
    'used_fields' => json_encode(['headline', 'body', 'cta']),
    'compliance_report' => json_encode(['status' => 'approved']),
    'status' => 'approved',
]
```

**Art Direction Structure:**

```typescript
interface ArtDirection {
    theme: string;          // 'professional', 'playful', 'minimalist'
    colors: string[];       // Hex codes
    style: string;          // 'modern_minimal', 'vibrant', 'classic'
}
```

**Final Copy Structure:**

```typescript
interface FinalCopy {
    headline: string;       // Main headline
    body: string;          // Body text
    cta: string;           // Call to action
    subheadline?: string;  // Optional subheadline
    description?: string;   // Optional description
}
```

---

## 📱 SOCIAL MEDIA POST EXAMPLES

From **DemoDataSeeder** (lines 472-537):

### Instagram Tech Post:

```php
[
    'org_id' => $techOrgId,
    'integration_id' => $instagramIntegrationId,
    'post_external_id' => '18123456789012345',
    'caption' => '🚀 Introducing CloudSync Pro! The enterprise cloud platform built for modern teams. #CloudSync #Enterprise #Technology',
    'media_url' => 'https://example.com/media1.jpg',
    'permalink' => 'https://instagram.com/p/ABC123',
    'media_type' => 'IMAGE',
    'posted_at' => now()->subDays(10),
    'metrics' => json_encode([
        'likes' => 234,
        'comments' => 18,
        'shares' => 12,
        'saves' => 45,
    ]),
]
```

### Instagram Fashion Carousel:

```php
[
    'org_id' => $fashionOrgId,
    'integration_id' => $instagramIntegrationId,
    'post_external_id' => '18123456789012346',
    'caption' => '✨ Summer vibes are here! Check out our new collection. Link in bio! #SummerFashion #OOTD #Style',
    'media_url' => 'https://example.com/media2.jpg',
    'permalink' => 'https://instagram.com/p/DEF456',
    'media_type' => 'CAROUSEL_ALBUM',
    'posted_at' => now()->subDays(5),
    'metrics' => json_encode([
        'likes' => 1253,
        'comments' => 87,
        'shares' => 34,
        'saves' => 156,
    ]),
    'children_media' => json_encode([
        ['media_url' => 'https://example.com/media2-1.jpg'],
        ['media_url' => 'https://example.com/media2-2.jpg'],
        ['media_url' => 'https://example.com/media2-3.jpg'],
    ]),
]
```

**Media Types:**
- `IMAGE` - Single image
- `VIDEO` - Video post
- `CAROUSEL_ALBUM` - Multiple images/videos
- `STORY` - Instagram story

**Metrics Structure:**

```typescript
interface SocialMetrics {
    likes: number;
    comments: number;
    shares: number;
    saves: number;
    reach?: number;
    impressions?: number;
}
```

---

## 🤖 AI-POWERED FEATURES - Real Data

From **ExtendedDemoDataSeeder**:

### AI Models (lines 563-582):

```php
[
    'model_id' => Str::uuid(),
    'org_id' => $orgId,
    'name' => 'Content Generation Model',
    'engine' => 'openai',
    'version' => '4.0',
    'model_name' => 'gpt-4',
    'model_family' => 'gpt',
    'description' => 'AI model for generating marketing content',
    'status' => 'active',
    'trained_at' => now()->subDays(30),
]
```

### AI Actions (lines 584-604):

```php
[
    'action_id' => Str::uuid(),
    'org_id' => $orgId,
    'campaign_id' => $campaignId,
    'prompt_used' => 'Generate campaign copy for product launch targeting enterprise customers',
    'sql_executed' => null,
    'result_summary' => 'Generated 5 headline variations and 3 body copy options',
    'confidence_score' => 0.92,
]
```

### Predictive Visual Engine (lines 646-668):

```php
[
    'prediction_id' => Str::uuid(),
    'org_id' => $orgId,
    'campaign_id' => $campaignId,
    'predicted_ctr' => 3.45,                      // Predicted click-through rate
    'predicted_engagement' => 5.67,               // Predicted engagement rate
    'predicted_trust_index' => 0.85,              // Trust score (0-1)
    'confidence_level' => 0.92,                   // AI confidence (0-1)
    'visual_factor_weight' => json_encode([
        'color' => 0.3,
        'composition' => 0.4,
        'imagery' => 0.3
    ]),
    'prediction_summary' => 'High engagement predicted based on visual analysis',
]
```

---

## 📊 COPY COMPONENTS - Reusable Content

From **ExtendedDemoDataSeeder** (lines 447-480):

```php
$components = [
    [
        'type' => 'headline',
        'content' => 'Transform Your Business Today',
        'quality' => 0.9
    ],
    [
        'type' => 'headline',
        'content' => 'Discover the Future of Marketing',
        'quality' => 0.85
    ],
    [
        'type' => 'body_copy',
        'content' => 'Join thousands of businesses already seeing results with our platform.',
        'quality' => 0.88
    ],
    [
        'type' => 'cta',
        'content' => 'Get Started Free',
        'quality' => 0.92
    ],
    [
        'type' => 'cta',
        'content' => 'Learn More',
        'quality' => 0.87
    ],
];
```

**Component Types:**
- `headline` - Main headlines (max 100 chars)
- `subheadline` - Secondary headlines (max 150 chars)
- `body_copy` - Body text (max 500 chars)
- `cta` - Call to action (max 30 chars)
- `description` - Descriptions (max 250 chars)

**Quality Scores:** 0.00 to 1.00 (decimal)
- `>= 0.9` - Excellent
- `0.8 - 0.89` - Good
- `0.7 - 0.79` - Average
- `< 0.7` - Needs improvement

---

## 🎬 VIDEO TEMPLATES

From **ExtendedDemoDataSeeder** (lines 482-502):

```php
[
    'vtpl_id' => Str::uuid(),
    'org_id' => $orgId,
    'channel_id' => $instagramChannelId,
    'format_id' => null,
    'name' => 'Instagram Reel Template',
    'steps' => json_encode([
        [
            'step' => 1,
            'duration' => 3,
            'instruction' => 'Hook - attention grabber'
        ],
        [
            'step' => 2,
            'duration' => 5,
            'instruction' => 'Problem - state the problem'
        ],
        [
            'step' => 3,
            'duration' => 7,
            'instruction' => 'Solution - present solution'
        ],
        [
            'step' => 4,
            'duration' => 3,
            'instruction' => 'CTA - call to action'
        ],
    ]),
    'version' => '1.0.0',
]
```

**Video Template Pattern:**
1. **Hook** (3 seconds) - Grab attention
2. **Problem** (5 seconds) - State the problem
3. **Solution** (7 seconds) - Present the solution
4. **CTA** (3 seconds) - Call to action

**Total Duration:** ~18 seconds (perfect for Instagram Reels)

---

## 🎭 SCENE LIBRARY

From **ExtendedDemoDataSeeder** (lines 520-539):

```php
[
    'scene_id' => Str::uuid(),
    'org_id' => $orgId,
    'name' => 'Product Showcase',
    'goal' => 'Highlight product features and benefits',
    'duration_sec' => 5,
    'visual_spec' => json_encode([
        'style' => 'modern',
        'lighting' => 'bright',
        'angle' => 'close-up'
    ]),
    'audio_spec' => json_encode([
        'music' => 'upbeat',
        'voiceover' => 'professional'
    ]),
    'overlay_rules' => json_encode([
        'text_position' => 'bottom_third',
        'logo' => 'top_right'
    ]),
    'anchor' => 'product_features',
    'quality_score' => 0.89,
    'tags' => json_encode(['product', 'features', 'benefits']),
]
```

---

## 📈 AD METRICS - Real Examples

From **ExtendedDemoDataSeeder** (lines 897-922):

```php
[
    'entity_level' => 'ad',
    'entity_external_id' => $adExternalId,
    'date_start' => '2025-11-15',
    'date_stop' => '2025-11-15',
    'spend' => 245.67,
    'impressions' => 15000,
    'clicks' => 345,
    'actions' => json_encode([
        'link_click' => 120,
        'post_engagement' => 280
    ]),
    'conversions' => json_encode([
        'purchase' => 15,
        'lead' => 35
    ]),
]
```

**Metrics by Day Pattern:**
- Daily granularity (date_start == date_stop)
- Spend in decimal (currency)
- Impressions, clicks as integers
- Actions as JSONB (flexible)
- Conversions as JSONB (flexible)

---

## ✅ COMPLIANCE RULES

From **ExtendedDemoDataSeeder** (lines 670-689):

```php
$rules = [
    [
        'code' => 'text_length',
        'description' => 'Text must not exceed maximum length',
        'severity' => 'error',
        'params' => json_encode(['max_length' => 280])
    ],
    [
        'code' => 'prohibited_words',
        'description' => 'Contains prohibited words',
        'severity' => 'error',
        'params' => json_encode([
            'words' => ['guaranteed', 'free money']
        ])
    ],
    [
        'code' => 'brand_consistency',
        'description' => 'Must follow brand guidelines',
        'severity' => 'warning',
        'params' => json_encode([
            'check_colors' => true,
            'check_fonts' => true
        ])
    ],
];
```

**Severity Levels:**
- `error` - Blocks publication
- `warning` - Shows warning but allows
- `info` - Informational only

---

## 🔄 AUTOMATION FLOWS

From **ExtendedDemoDataSeeder** (lines 1018-1058):

```php
// Flow definition
[
    'flow_id' => $flowId,
    'org_id' => $orgId,
    'name' => 'Auto-Publish Workflow',
    'description' => 'Automated content publishing workflow',
    'version' => '1.0.0',
    'tags' => json_encode(['automation', 'publishing']),
    'enabled' => true,
]

// Flow steps
$steps = [
    [
        'ord' => 1,
        'type' => 'trigger',
        'name' => 'New Post Created',
        'input_map' => json_encode(['event' => 'post.created'])
    ],
    [
        'ord' => 2,
        'type' => 'condition',
        'name' => 'Check Approval Status',
        'input_map' => json_encode(['field' => 'status']),
        'condition' => json_encode([
            'operator' => 'equals',
            'value' => 'approved'
        ])
    ],
    [
        'ord' => 3,
        'type' => 'action',
        'name' => 'Publish to Platform',
        'input_map' => json_encode(['platform' => 'instagram'])
    ],
];
```

**Flow Step Types:**
- `trigger` - Event that starts the flow
- `condition` - Decision point
- `action` - Actual operation

---

## 🎓 KEY INSIGHTS FOR AGENTS

### 1. Context System is Three-Layered

❌ **WRONG Assumption:**
```php
$campaign->context_id = $contextId;  // Single context
```

✅ **CORRECT Pattern:**
```php
$campaign->context_id = $baseContextId;      // Base context
$campaign->creative_id = $creativeContextId; // Creative context
$campaign->value_id = $valueContextId;       // Value context
```

### 2. Value Contexts Are Rich Objects

**Must include:**
- `awareness_stage` - Where customer is in journey
- `funnel_stage` - Top, middle, or bottom funnel
- `framework` - Marketing framework (AIDA, PAS, FAB)
- `tone` - Brand voice
- `variant_tag` - For A/B testing

### 3. Creative Briefs Are Structured JSONB

**Always include:**
- `objective` - Clear goal
- `target_audience` - Specific audience
- `key_messages` - Array of messages
- `brand_guidelines` - Tone, colors, fonts
- `deliverables` - What to produce
- `timeline` & `budget` - Constraints

### 4. Field Definitions Support Modules

**Module-based organization:**
- `campaign` module → Campaign fields
- `creative` module → Creative fields
- `social` module → Social fields
- `ads` module → Advertising fields
- `analytics` module → Analytics fields

### 5. Quality Scores Are Everywhere

**Track quality for:**
- Copy components: `quality_score` (0-1)
- AI predictions: `confidence_level` (0-1)
- Embeddings: `quality_score` (0-1)
- Scenes: `quality_score` (0-1)

### 6. JSONB Is Used Extensively

**Common JSONB fields:**
- `brief_data` - Creative brief
- `art_direction` - Visual specs
- `final_copy` - Copy components
- `metrics` - Performance data
- `actions` - User actions
- `conversions` - Conversion events
- `visual_spec` - Scene visuals
- `audio_spec` - Audio specs
- `tags` - Context tags

### 7. Automation Uses Step-Based Flows

**Flow pattern:**
1. Trigger (event-based)
2. Condition (decision)
3. Action (operation)
4. (Repeat 2-3 as needed)

---

## 📊 USAGE EXAMPLES FOR AGENTS

### Example 1: Creating a Campaign with Full Context

```php
// 1. Create value context
$valueContext = ValueContext::create([
    'org_id' => $orgId,
    'campaign_id' => null,  // Will link after campaign creation
    'locale' => 'en',
    'awareness_stage' => 'solution_aware',
    'funnel_stage' => 'middle_of_funnel',
    'framework' => 'AIDA',
    'tone' => 'professional',
    'variant_tag' => 'A',
    'tags' => json_encode(['b2b', 'enterprise']),
    'context_fingerprint' => md5($orgId . time()),
]);

// 2. Create campaign
$campaign = Campaign::create([
    'org_id' => $orgId,
    'name' => 'Enterprise Product Launch',
    'objective' => 'conversions',
    'status' => 'draft',
    'budget' => 50000.00,
    'currency' => 'USD',
    'value_id' => $valueContext->context_id,  // Link value context
    'created_by' => auth()->id(),
]);

// 3. Create creative brief
$brief = CreativeBrief::create([
    'org_id' => $orgId,
    'name' => 'Enterprise Launch Brief',
    'brief_data' => json_encode([
        'objective' => 'Drive enterprise sign-ups',
        'target_audience' => 'CTOs and IT Directors',
        'key_messages' => [
            'Enterprise-grade security',
            'Scalable infrastructure',
            '24/7 support'
        ],
        'brand_guidelines' => [
            'tone' => 'professional, trustworthy',
            'colors' => ['#0066CC', '#FFFFFF'],
            'fonts' => ['Inter', 'Roboto']
        ],
        'deliverables' => [
            'LinkedIn ads',
            'Landing page',
            'Email campaign'
        ],
        'timeline' => '6 weeks',
        'budget' => '$50,000'
    ]),
]);
```

### Example 2: Using Field Definitions

```php
// Get campaign module
$campaignModule = Module::where('code', 'campaign')->first();

// Get all campaign fields
$fields = FieldDefinition::where('module_id', $campaignModule->module_id)->get();

// Build dynamic form
foreach ($fields as $field) {
    echo "<label>{$field->name}</label>";

    if ($field->data_type === 'enum') {
        $options = json_decode($field->enum_options);
        // Render select dropdown
    } elseif ($field->data_type === 'text') {
        // Render textarea
    } else {
        // Render input
    }

    if ($field->required_default) {
        echo " *";  // Required indicator
    }
}
```

### Example 3: Quality-Based Filtering

```php
// Get high-quality copy components
$highQualityCopy = CopyComponent::where('quality_score', '>=', 0.85)
    ->where('type_code', 'headline')
    ->orderBy('quality_score', 'desc')
    ->get();

// Get confident AI predictions
$confidentPredictions = PredictiveVisualEngine::where('confidence_level', '>=', 0.90)
    ->where('campaign_id', $campaignId)
    ->get();
```

---

## ⚠️ CRITICAL WARNINGS

### 1. Always Respect Context Types

**Don't mix context types:**
```php
// WRONG - using creative context as value context
$campaign->value_id = $creativeContextId;  ❌
```

### 2. Validate Enum Values

**Status must be exact:**
```php
// WRONG
$campaign->status = 'Active';  ❌ (capitalized)

// CORRECT
$campaign->status = 'active';  ✅ (lowercase)
```

**Valid statuses:** `draft`, `active`, `paused`, `completed`, `archived`

### 3. Quality Scores Are Decimals (0-1)

```php
// WRONG
$component->quality_score = 85;  ❌ (should be 0.85)

// CORRECT
$component->quality_score = 0.85;  ✅
```

### 4. JSONB Fields Must Be Valid JSON

```php
// WRONG
$brief->brief_data = "some string";  ❌

// CORRECT
$brief->brief_data = json_encode([...]);  ✅
```

---

**This document provides REAL data examples that AI agents can reference when working with CMIS!**

**Last Updated:** 2025-11-18
**Sources:** backup-db-for-seeds.sql, DemoDataSeeder.php, ExtendedDemoDataSeeder.php
