# CMIS Data Pattern Discovery
## Learning Data Structures Through Real Examples

**Last Updated:** 2025-11-18
**Framework:** META_COGNITIVE_FRAMEWORK v2.0
**Philosophy:** Learn Patterns, Not Facts

---

## 🎓 PHILOSOPHY: PATTERNS OVER EXAMPLES

**Not:** "Here are the exact data structures"
**But:** "How do I discover data patterns in CMIS?"

**Not:** "This is what the data looks like"
**But:** "How do I find real examples to learn from?"

**Not:** "Memorize these structures"
**But:** "Recognize these patterns when you see them"

---

## 🔍 DISCOVERING DATA PATTERNS

### Where to Find Real Data Examples

**Step 1: Locate Seeders**

```bash
# Find all seeders
ls -la database/seeders/

# Key seeders for patterns
ls database/seeders/*Demo*Seeder.php
ls database/seeders/*Data*Seeder.php

# Read seeder to understand data structure
cat database/seeders/DemoDataSeeder.php | head -100
```

**Step 2: Check Database Backup**

```bash
# Find backup SQL files
find database -name "*backup*" -name "*.sql"

# Extract CREATE TABLE statements
grep -A 20 "CREATE TABLE" database/backup*.sql | head -100

# Extract INSERT examples
grep -A 5 "INSERT INTO" database/backup*.sql | head -50
```

**Step 3: Query Live Database**

```sql
-- Sample data from key tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT * FROM cmis.campaigns LIMIT 3;
"

-- JSONB structure examples
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    campaign_id,
    name,
    jsonb_pretty(brief_data) as brief_structure
FROM cmis.creative_briefs
LIMIT 2;
"
```

---

## 🎯 PATTERN 1: MULTI-CONTEXT SYSTEM

### Discovery: How Many Context Types Exist?

```bash
# Find context-related models
find app/Models -name "*Context*.php"

# Check seeder for context creation
grep -A 10 "type.*=>" database/seeders/*Seeder.php | grep -B2 -A2 "creative\|value\|offering"

# Find context tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT tablename
FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename LIKE '%context%'
ORDER BY tablename;
"
```

**Pattern Recognition:**

```php
// When you see this pattern in seeders:
$contexts = [
    ['type' => 'creative', 'name' => 'Creative Context'],
    ['type' => 'value', 'name' => 'Value Proposition'],
    ['type' => 'offering', 'name' => 'Product Context'],
];

// → Three-context system is a core pattern
// → Each serves different purpose
```

### Discovery: Value Context Structure

```sql
-- Discover value_contexts table structure
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.value_contexts
"

-- Sample real data
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    locale,
    awareness_stage,
    funnel_stage,
    framework,
    tone,
    variant_tag,
    tags
FROM cmis.value_contexts
LIMIT 5;
"
```

**Inferred Pattern:**

From examining real data, you'll discover:
- `awareness_stage`: Customer journey position (problem_aware, solution_aware, product_aware)
- `funnel_stage`: Marketing funnel (top_of_funnel, middle_of_funnel, bottom_of_funnel)
- `framework`: Marketing framework (AIDA, PAS, FAB)
- `tone`: Brand voice (professional, casual, friendly, formal)
- `variant_tag`: A/B test identifier (A, B, C...)
- `tags`: JSONB array for flexible categorization

**Key Insight:** Value contexts are rich marketing metadata, not simple lookups!

---

## 🎨 PATTERN 2: JSONB-HEAVY ARCHITECTURE

### Discovery: Which Tables Use JSONB?

```sql
-- Find all JSONB columns
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    table_schema,
    table_name,
    column_name,
    data_type
FROM information_schema.columns
WHERE table_schema LIKE 'cmis%'
  AND data_type = 'jsonb'
ORDER BY table_name, column_name;
"
```

### Discovery: Creative Brief JSONB Structure

```bash
# Find brief creation in seeder
grep -A 30 "brief_data.*=>" database/seeders/DemoDataSeeder.php | head -40

# Or query real examples
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    name,
    jsonb_pretty(brief_data)
FROM cmis.creative_briefs
LIMIT 2;
"
```

**Pattern You'll Discover:**

```json
{
  "objective": "string describing goal",
  "target_audience": "specific audience description",
  "key_messages": [
    "Message 1",
    "Message 2",
    "Message 3"
  ],
  "brand_guidelines": {
    "tone": "professional/casual/friendly/formal",
    "colors": ["#HEX", "#HEX"],
    "fonts": ["Font1", "Font2"]
  },
  "deliverables": ["item1", "item2"],
  "timeline": "duration string",
  "budget": "amount with currency"
}
```

**Recognition Rule:** Creative briefs always have this structure. Don't invent new fields; follow this pattern.

---

## 📱 PATTERN 3: SOCIAL MEDIA POST STRUCTURE

### Discovery: Post Data Model

```sql
-- Discover social_posts structure
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.social_posts
"

-- Sample posts with metrics
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    caption,
    media_type,
    posted_at,
    jsonb_pretty(metrics) as metrics_structure
FROM cmis.social_posts
LIMIT 3;
"
```

**Pattern Recognition:**

```php
// Media types you'll find:
'IMAGE'          // Single image post
'VIDEO'          // Video post
'CAROUSEL_ALBUM' // Multi-image/video
'STORY'          // Instagram story

// Metrics structure (JSONB):
{
  "likes": 234,
  "comments": 18,
  "shares": 12,
  "saves": 45,
  "reach": 5000,       // Optional
  "impressions": 7500   // Optional
}
```

### Discovery: Carousel Posts

```bash
# Find carousel examples in seeder
grep -A 15 "CAROUSEL" database/seeders/DemoDataSeeder.php

# Check for children_media field
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    media_type,
    jsonb_pretty(children_media)
FROM cmis.social_posts
WHERE media_type = 'CAROUSEL_ALBUM'
LIMIT 1;
"
```

**Pattern:** Carousels have `children_media` JSONB array with media URLs

---

## 🤖 PATTERN 4: FIELD DEFINITION SYSTEM (EAV)

### Discovery: How Are Custom Fields Defined?

```sql
-- Find field definitions table
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.field_definitions
"

-- Sample field definitions
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    module_code,
    name,
    slug,
    data_type,
    required_default,
    jsonb_pretty(enum_options) as options,
    jsonb_pretty(validations) as validation_rules
FROM cmis.field_definitions
JOIN cmis.modules ON field_definitions.module_id = modules.module_id
LIMIT 10;
"
```

### Discovery: Module Organization

```bash
# Find modules in seeder
grep -A 5 "'module'" database/seeders/*Seeder.php | grep -o "'[a-z_]*'" | sort | uniq

# Query modules
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT code, name FROM cmis.modules ORDER BY code;
"
```

**Pattern Recognition:**

Field definitions are organized by modules:
- `campaign` → Campaign-specific fields
- `creative` → Creative asset fields
- `social` → Social media fields
- `ads` → Advertising fields
- `analytics` → Analytics fields

**Data Type Patterns:**
- `string` → Short text (with max_length validation)
- `text` → Long text (no length limit)
- `decimal` → Numbers with decimals
- `enum` → Fixed options list
- `boolean` → True/false
- `date` → Date values
- `datetime` → Date + time values

**Validation Structure (JSONB):**
```json
{
  "max_length": 255,
  "min": 0,
  "max": 100,
  "pattern": "regex pattern"
}
```

---

## 🎬 PATTERN 5: VIDEO TEMPLATE STRUCTURE

### Discovery: Video Templates

```sql
-- Find video template tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT tablename FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename LIKE '%video%'
ORDER BY tablename;
"

-- Sample video template
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    name,
    jsonb_pretty(steps) as template_steps
FROM cmis.video_templates
LIMIT 1;
"
```

**Pattern You'll Discover:**

Video templates use step-based structure:

```json
[
  {
    "step": 1,
    "duration": 3,
    "instruction": "Hook - grab attention"
  },
  {
    "step": 2,
    "duration": 5,
    "instruction": "Problem - state the problem"
  },
  {
    "step": 3,
    "duration": 7,
    "instruction": "Solution - present the solution"
  },
  {
    "step": 4,
    "duration": 3,
    "instruction": "CTA - call to action"
  }
]
```

**Total Duration Pattern:** Sum of all step durations (~15-20 seconds for Reels/TikTok)

---

## 📊 PATTERN 6: AD METRICS STRUCTURE

### Discovery: How Are Metrics Stored?

```sql
-- Discover metrics table
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.ad_metrics
"

-- Sample metrics with JSONB fields
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    entity_level,
    date_start,
    date_stop,
    spend,
    impressions,
    clicks,
    jsonb_pretty(actions) as actions_breakdown,
    jsonb_pretty(conversions) as conversion_events
FROM cmis.ad_metrics
LIMIT 3;
"
```

**Pattern Recognition:**

**Entity Levels:**
- `campaign` → Campaign-level metrics
- `adset` → Ad set-level metrics
- `ad` → Individual ad metrics

**Time Granularity:**
- Daily: `date_start == date_stop`
- Range: `date_start < date_stop`

**Actions Structure (JSONB):**
```json
{
  "link_click": 120,
  "post_engagement": 280,
  "page_engagement": 150,
  "like": 45,
  "comment": 12,
  "share": 8
}
```

**Conversions Structure (JSONB):**
```json
{
  "purchase": 15,
  "lead": 35,
  "add_to_cart": 67,
  "initiate_checkout": 42
}
```

---

## ✅ PATTERN 7: COMPLIANCE AND QUALITY SCORES

### Discovery: Compliance Rules

```bash
# Find compliance in seeder
grep -A 10 "compliance" database/seeders/*Seeder.php

# Query compliance rules
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    code,
    description,
    severity,
    jsonb_pretty(params) as rule_params
FROM cmis.compliance_rules
LIMIT 5;
"
```

**Pattern Recognition:**

**Severity Levels:**
- `error` → Blocks publication
- `warning` → Shows warning, allows continuation
- `info` → Informational only

**Common Rule Types:**
```json
// Text length rule
{
  "code": "text_length",
  "severity": "error",
  "params": {"max_length": 280}
}

// Prohibited words rule
{
  "code": "prohibited_words",
  "severity": "error",
  "params": {"words": ["guaranteed", "free money"]}
}

// Brand consistency rule
{
  "code": "brand_consistency",
  "severity": "warning",
  "params": {
    "check_colors": true,
    "check_fonts": true
  }
}
```

### Discovery: Quality Score Pattern

```bash
# Find tables with quality scores
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    table_name,
    column_name
FROM information_schema.columns
WHERE table_schema LIKE 'cmis%'
  AND column_name LIKE '%quality%'
ORDER BY table_name;
"
```

**Universal Pattern:**
- Quality scores are ALWAYS `decimal` type
- Range: 0.00 to 1.00 (not 0-100!)
- 0.90+ = Excellent
- 0.80-0.89 = Good
- 0.70-0.79 = Average
- <0.70 = Needs improvement

---

## 🔄 PATTERN 8: AUTOMATION FLOWS

### Discovery: Flow Structure

```sql
-- Discover flow tables
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT tablename FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename LIKE '%flow%'
ORDER BY tablename;
"

-- Sample flow with steps
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    f.name as flow_name,
    fs.ord as step_order,
    fs.type as step_type,
    fs.name as step_name,
    jsonb_pretty(fs.input_map) as inputs
FROM cmis.automation_flows f
JOIN cmis.automation_flow_steps fs ON f.flow_id = fs.flow_id
ORDER BY f.name, fs.ord
LIMIT 10;
"
```

**Pattern Recognition:**

**Flow Step Types:**
1. `trigger` → Event that initiates flow
2. `condition` → Decision/branching logic
3. `action` → Actual operation

**Step Ordering:**
- `ord` field determines execution order
- Steps execute sequentially by `ord`

**Input Map Structure:**
```json
// Trigger
{"event": "post.created"}

// Condition
{
  "field": "status",
  "operator": "equals",
  "value": "approved"
}

// Action
{"platform": "instagram", "operation": "publish"}
```

---

## 🎓 PATTERN RECOGNITION WORKFLOW

### When You See a New Table

**Step 1: Discover Structure**

```sql
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
\d cmis.new_table_name
"
```

**Step 2: Find Real Examples**

```sql
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT * FROM cmis.new_table_name LIMIT 3;
"
```

**Step 3: Check Seeder**

```bash
grep -A 20 "new_table_name" database/seeders/*.php
```

**Step 4: Identify Patterns**

Ask yourself:
- Does it have JSONB fields? → Flexible structure
- Does it have org_id? → Multi-tenant
- Does it have quality_score or confidence_level? → AI/quality tracking
- Does it have created_at/updated_at? → Timestamped
- Does it have deleted_at? → Soft deletes
- Does it have *_id fields? → Relationships

**Step 5: Find Similar Tables**

```sql
-- Find tables with similar columns
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT table_name
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND column_name = 'similar_column_name'
GROUP BY table_name;
"
```

---

## 📋 COMMON DATA PATTERNS CHEAT SHEET

### UUID Pattern

```bash
# Check if table uses UUID
grep -A 5 "class.*Model" app/Models/Core/YourModel.php | grep "HasUuids"

# UUID primary keys don't auto-increment
# Must generate in code: Str::uuid()
```

### Enum Pattern

```sql
-- Discover enum values
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    enumlabel as allowed_value
FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'your_enum_type'
ORDER BY enumsortorder;
"
```

### JSONB Pattern

```sql
-- Explore JSONB structure
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    jsonb_pretty(your_jsonb_column)
FROM cmis.your_table
WHERE your_jsonb_column IS NOT NULL
LIMIT 3;
"

-- Extract keys
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT DISTINCT jsonb_object_keys(your_jsonb_column)
FROM cmis.your_table
WHERE your_jsonb_column IS NOT NULL;
"
```

### Timestamp Pattern

```sql
-- Check timestamp usage
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT
    column_name,
    data_type
FROM information_schema.columns
WHERE table_schema = 'cmis'
  AND table_name = 'your_table'
  AND data_type LIKE '%timestamp%'
ORDER BY ordinal_position;
"
```

---

## 🎯 APPLYING PATTERNS

### Example 1: Creating a Campaign with Context

```php
// Discovered pattern from seeders and database:

// 1. Value context must include marketing metadata
$valueContext = ValueContext::create([
    'org_id' => $orgId,
    'locale' => 'en',                      // Discovered: always include locale
    'awareness_stage' => 'solution_aware',  // Discovered: enum values
    'funnel_stage' => 'middle_of_funnel',   // Discovered: enum values
    'framework' => 'AIDA',                  // Discovered: marketing frameworks
    'tone' => 'professional',               // Discovered: brand voice options
    'variant_tag' => 'A',                   // Discovered: A/B test pattern
    'tags' => json_encode(['b2b']),        // Discovered: JSONB array
    'context_fingerprint' => md5($orgId . time()), // Discovered: uniqueness pattern
]);

// 2. Campaign links to context
$campaign = Campaign::create([
    'org_id' => $orgId,
    'name' => 'New Campaign',
    'objective' => 'conversions',          // Discovered: enum from seeder
    'status' => 'draft',                   // Discovered: must be lowercase
    'budget' => 10000.00,                  // Discovered: decimal type
    'currency' => 'USD',                   // Discovered: 3-letter code
    'value_id' => $valueContext->context_id, // Discovered: foreign key pattern
]);
```

### Example 2: Creating a Creative Brief

```php
// Pattern discovered from DemoDataSeeder:

$brief = CreativeBrief::create([
    'org_id' => $orgId,
    'name' => 'Launch Brief',
    'brief_data' => json_encode([
        // Discovered: required structure
        'objective' => 'Clear goal statement',
        'target_audience' => 'Specific audience',
        'key_messages' => [               // Discovered: always an array
            'Message 1',
            'Message 2',
            'Message 3'
        ],
        'brand_guidelines' => [           // Discovered: nested object
            'tone' => 'professional',
            'colors' => ['#0066CC', '#FFFFFF'],
            'fonts' => ['Inter']
        ],
        'deliverables' => ['Ad creatives', 'Landing page'],
        'timeline' => '4 weeks',
        'budget' => '$10,000'
    ]),
]);
```

### Example 3: Recording Metrics

```php
// Pattern discovered from ExtendedDemoDataSeeder:

$metric = AdMetric::create([
    'org_id' => $orgId,
    'entity_level' => 'ad',                // Discovered: campaign/adset/ad
    'entity_external_id' => $adId,
    'date_start' => '2025-11-18',         // Discovered: YYYY-MM-DD format
    'date_stop' => '2025-11-18',          // Discovered: same date for daily
    'spend' => 125.50,                     // Discovered: decimal for currency
    'impressions' => 5000,                 // Discovered: integer
    'clicks' => 150,                       // Discovered: integer
    'actions' => json_encode([            // Discovered: flexible JSONB
        'link_click' => 50,
        'post_engagement' => 100
    ]),
    'conversions' => json_encode([        // Discovered: flexible JSONB
        'purchase' => 5,
        'lead' => 15
    ]),
]);
```

---

## ⚠️ CRITICAL PATTERN RULES

### Rule 1: Enum Values Must Match Exactly

```bash
# Discover allowed values first
PGPASSWORD='123@Marketing@321' psql -h 127.0.0.1 -U begin -d cmis -c "
SELECT enumlabel FROM pg_enum
JOIN pg_type ON pg_enum.enumtypid = pg_type.oid
WHERE pg_type.typname = 'campaign_status'
ORDER BY enumsortorder;
"

# Don't assume or invent values
# ❌ 'Active' (wrong case)
# ✅ 'active' (exact match)
```

### Rule 2: Quality Scores Are 0-1, Not 0-100

```php
// ❌ WRONG
$component->quality_score = 85;

// ✅ RIGHT
$component->quality_score = 0.85;
```

### Rule 3: JSONB Fields Must Be Encoded

```php
// ❌ WRONG
$brief->brief_data = ['objective' => 'Goal'];

// ✅ RIGHT
$brief->brief_data = json_encode(['objective' => 'Goal']);
```

### Rule 4: Context Types Are Distinct

```php
// ❌ WRONG - mixing context types
$campaign->value_id = $creativeContext->context_id;

// ✅ RIGHT - use correct context type
$campaign->creative_id = $creativeContext->context_id;
$campaign->value_id = $valueContext->context_id;
```

---

## 🎓 LEARNING WORKFLOW

### When Implementing a New Feature

1. **Find similar existing feature**
   ```bash
   grep -r "similar_feature" database/seeders/
   ```

2. **Study its data patterns**
   ```sql
   SELECT * FROM cmis.similar_table LIMIT 5;
   ```

3. **Identify JSONB structures**
   ```sql
   SELECT jsonb_pretty(jsonb_column) FROM cmis.similar_table LIMIT 1;
   ```

4. **Check for enum constraints**
   ```sql
   SELECT enumlabel FROM pg_enum WHERE enumtypid = (
     SELECT oid FROM pg_type WHERE typname = 'similar_enum'
   );
   ```

5. **Copy the pattern, adapt the data**

---

## 📚 RELATED KNOWLEDGE

- **MULTI_TENANCY_PATTERNS.md** - RLS and org_id patterns
- **PATTERN_RECOGNITION.md** - Architectural patterns
- **LARAVEL_CONVENTIONS.md** - Model, controller, migration patterns
- **CMIS_DISCOVERY_GUIDE.md** - General discovery methodology

---

## 🎯 KEY TAKEAWAYS

1. **Always check seeders first** - They show real usage patterns
2. **Query database for examples** - See actual data structures
3. **JSONB is everywhere** - Flexible structures for complex data
4. **Quality scores are 0-1** - Never percentages
5. **Enums are strict** - Must match exact values
6. **Multi-context is core** - creative/value/offering separation
7. **Patterns repeat** - Once you learn one, you know them all

---

**Version:** 2.0 - Pattern Discovery Approach
**Framework:** META_COGNITIVE_FRAMEWORK
**Methodology:** Learn by Example, Apply by Pattern

*"Real data teaches patterns better than documentation."*
