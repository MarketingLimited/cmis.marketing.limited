# CMIS Database & SQL Insights
## Critical Discoveries from Database Analysis

**Source:** `database/backup-db-for-seeds.sql`
**Analyzed:** 2025-11-18
**Purpose:** Enhanced understanding for AI agents

---

## 🔐 RLS POLICIES (27 Policies Total)

### Permission-Based RLS Pattern

CMIS uses **BOTH org-level AND permission-level** isolation:

```sql
-- Example: campaigns RLS DELETE policy
CREATE POLICY rbac_campaigns_delete ON cmis.campaigns
FOR DELETE
USING (
    (org_id = cmis.get_current_org_id())
    AND
    cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.delete')
);
```

**Key Functions:**
- `cmis.get_current_org_id()` - Get current org from context
- `cmis.get_current_user_id()` - Get current user from context
- `cmis.check_permission(user_id, org_id, permission_code)` - Check granular permission

### Permission Codes Found

```
Core Permissions:
- campaigns.view
- campaigns.delete
- analytics.view
- analytics.configure
- admin.settings

Pattern: {domain}.{action}
```

### RLS Policy Pattern

**All tables follow this pattern:**

1. **SELECT Policy** - org + permission check
2. **INSERT Policy** - org + permission check
3. **UPDATE Policy** - org + permission check
4. **DELETE Policy** - org + permission check

**Special Cases:**
- Notifications: User-specific (not org-specific)
- Audit logs: Admin-only access

---

## 📊 CAMPAIGN STRUCTURE (Critical Discovery)

### Campaign Status Enum

```sql
CONSTRAINT campaigns_status_valid CHECK (
    status = ANY (ARRAY[
        'draft'::text,
        'active'::text,
        'paused'::text,
        'completed'::text,
        'archived'::text
    ])
)
```

**Lifecycle:**
```
draft → active → paused → completed → archived
           ↓         ↓
        (can return to active)
```

### Three Context System (CRITICAL!)

```sql
campaigns (
    context_id uuid,      -- Base context
    creative_id uuid,     -- Creative context
    value_id uuid,        -- Value proposition context
    ...
)
```

**This is unique to CMIS!**
- Base: Campaign fundamentals
- Creative: Creative brief, visuals, messaging
- Value: Value proposition, pricing, features

### Campaign Soft Deletes

```sql
deleted_at timestamp with time zone,
deleted_by uuid,  -- Tracks WHO deleted it
```

---

## 🌍 BILINGUAL SUPPORT (Arabic + English)

### Intent Mappings (Bilingual AI)

```sql
CREATE TABLE cmis_knowledge.intent_mappings (
    intent_name text NOT NULL,          -- English
    intent_name_ar text NOT NULL,       -- Arabic
    intent_description text,
    intent_embedding vector(768),       -- Shared embedding
    related_keywords text[],            -- English keywords
    related_keywords_ar text[],         -- Arabic keywords
    ...
)
```

**Key Insight:** CMIS is designed for Arabic-speaking markets!

**Similar bilingual pattern in:**
- direction_mappings (direction_name + direction_name_ar)
- purpose_mappings (purpose_name + purpose_name_ar)

---

## 🤖 AI & EMBEDDINGS SYSTEM

### Embeddings Cache (Optimized)

```sql
CREATE TABLE cmis_knowledge.embeddings_cache (
    source_table text NOT NULL,
    source_id uuid NOT NULL,
    source_field text NOT NULL,
    embedding vector(768) NOT NULL,
    embedding_norm double precision,        -- Pre-calculated norm
    model_version text DEFAULT 'gemini-text-embedding-004',
    quality_score numeric(3,2),             -- 0.00 to 1.00
    usage_count integer DEFAULT 0,          -- Optimization metric
    input_hash text,                        -- MD5 for cache lookup
    last_used_at timestamp with time zone,
    ...
)
```

**Caching Strategy:**
1. Hash input text → `input_hash`
2. Check cache by hash
3. If miss → Generate embedding → Store with hash
4. Track `usage_count` for popular embeddings
5. Calculate `quality_score` for filtering

### AI-Generated Creatives

```sql
CREATE TABLE cmis_marketing.generated_creatives (
    topic text NOT NULL,
    tone text NOT NULL,
    variant_index integer NOT NULL,    -- Multiple variants per topic/tone
    hook text,                         -- Attention-grabbing opener
    concept text,                      -- Core idea
    narrative text,                    -- Story/message
    slogan text,                       -- Tagline
    emotion_profile text[],            -- Multiple emotions (array)
    tags text[],
    ...
)
```

**AI Generation Pattern:**
- Topic + Tone → Multiple variants
- Each variant has: hook, concept, narrative, slogan
- Emotion profiling (array of emotions)

---

## 🔗 FOREIGN KEY CASCADE PATTERNS

### Critical Relationships

**ON DELETE CASCADE** (data deleted together):
```sql
campaigns → orgs  (org deleted → campaigns deleted)
campaign_offerings → campaigns (campaign deleted → offerings link deleted)
contexts → campaigns (campaign deleted → contexts deleted)
```

**ON DELETE SET NULL** (relationship broken, data kept):
```sql
content_plans → campaigns (campaign deleted → plan kept, campaign_id = NULL)
ad_campaigns → integration (integration deleted → ad data kept)
```

**ON DELETE RESTRICT** (default - prevents deletion):
```sql
-- Most relationships to prevent accidental data loss
```

---

## 📈 ANALYTICS & METRICS

### Metric Tracking Pattern

```sql
-- Metrics stored as JSONB for flexibility
metrics jsonb

-- Example structure:
{
  "impressions": 10000,
  "clicks": 500,
  "conversions": 25,
  "ctr": 0.05,
  "conversion_rate": 0.05
}
```

**Benefits:**
- Schema-less flexibility
- Easy to add new metrics
- Queryable with JSONB operators

---

## 🎯 KEY DISCOVERIES FOR AI AGENTS

### 1. Permission System is Two-Layered

❌ **Not just:** org_id filtering
✅ **Actually:** org_id + granular permissions

**Agents must understand:**
- User must belong to org (RLS)
- User must have specific permission (check_permission)

### 2. Three Context Types (Not One!)

❌ **Not just:** Single context per campaign
✅ **Actually:** Three context types (base, creative, value)

**Agents must understand:**
- context_id = Base context
- creative_id = Creative context
- value_id = Value proposition context

### 3. Bilingual by Design

❌ **Not just:** English system
✅ **Actually:** Arabic + English throughout

**Agents must provide:**
- Examples in both languages
- Understanding of Arabic market
- Bilingual keyword/intent mapping

### 4. Status Enum is Fixed

❌ **Not just:** Any status string
✅ **Actually:** Only 5 valid statuses

**Agents must enforce:**
- draft, active, paused, completed, archived
- No other values allowed (CHECK constraint)

### 5. Soft Deletes Track Deletor

❌ **Not just:** deleted_at timestamp
✅ **Actually:** deleted_at + deleted_by

**Agents must:**
- Never hard delete (respect soft deletes)
- Track WHO deleted (deleted_by = user_id)
- Respect deletion in queries (WHERE deleted_at IS NULL)

### 6. AI Quality Scoring

❌ **Not just:** Generate embeddings
✅ **Actually:** Track quality_score per embedding

**Agents must:**
- Consider quality_score when filtering results
- Track usage_count for optimization
- Use input_hash for caching

---

## 🔧 HELPER FUNCTIONS DISCOVERED

### RLS Helper Functions

```sql
cmis.get_current_org_id()     -- Gets org from transaction context
cmis.get_current_user_id()    -- Gets user from transaction context
cmis.check_permission(user_id, org_id, permission_code) -- Checks permission
cmis.init_transaction_context(user_id, org_id) -- Sets context
```

**SECURITY DEFINER** - Runs with elevated privileges

### Validation Functions

```sql
-- Campaign status validation
CONSTRAINT campaigns_status_valid CHECK (
    status = ANY (ARRAY['draft', 'active', 'paused', 'completed', 'archived'])
)

-- Similar constraints on other tables
```

---

## 📊 SCHEMA STATISTICS

```
Total Schemas: 12
├── cmis (Core) - ~150 tables
├── cmis_knowledge (AI) - ~20 tables
├── cmis_marketing (Marketing) - ~6 tables
├── cmis_analytics (Analytics) - ~15 tables
├── cmis_ai_analytics (AI Analytics) - ~10 tables
├── cmis_audit (Audit) - ~8 tables
├── cmis_ops (Operations) - ~12 tables
├── cmis_security (Security) - ~5 tables
├── cmis_system_health (Health) - ~3 tables
├── cmis_dev (Development) - ~5 tables
├── archive (Archive) - ~5 tables
└── lab (Experimental) - ~5 tables

Total Tables: ~189 tables
Total RLS Policies: 27 policies
```

---

## ⚠️ CRITICAL WARNINGS FOR AGENTS

### 1. Never Use Manual org_id Filtering

❌ **WRONG:**
```php
Campaign::where('org_id', $orgId)->get();
```

✅ **CORRECT:**
```php
// RLS filters automatically after context is set
Campaign::get();
```

### 2. Always Check Permissions in Code

Even though RLS filters at DB level, check permissions in application for better error messages:

```php
if (!$user->can('campaigns.delete')) {
    return response()->json(['error' => 'Permission denied'], 403);
}

Campaign::destroy($id);  // RLS also enforces
```

### 3. Respect Three Context Types

```php
// Wrong: Single context
$campaign->context_id = $contextId;

// Correct: Specify context type
$campaign->context_id = $baseContextId;      // Base
$campaign->creative_id = $creativeContextId; // Creative
$campaign->value_id = $valueContextId;       // Value
```

### 4. Use Status Enum Constants

```php
// Define constants in Campaign model
const STATUS_DRAFT = 'draft';
const STATUS_ACTIVE = 'active';
const STATUS_PAUSED = 'paused';
const STATUS_COMPLETED = 'completed';
const STATUS_ARCHIVED = 'archived';

// Use constants, not strings
$campaign->status = Campaign::STATUS_ACTIVE;
```

### 5. Soft Delete with Deletor

```php
// Wrong: Just timestamp
$campaign->delete();

// Correct: Track deletor
$campaign->update([
    'deleted_at' => now(),
    'deleted_by' => auth()->id()
]);
```

---

## 🎓 IMPLICATIONS FOR AGENTS

### For cmis-multi-tenancy Agent

**Must add:**
- Two-layer isolation (org + permissions)
- Permission checking examples
- Helper functions documentation

### For cmis-campaign-expert Agent

**Must add:**
- Status enum enforcement
- Three context types explanation
- Soft delete with deletor

### For cmis-ai-semantic Agent

**Must add:**
- quality_score filtering
- usage_count optimization
- input_hash caching strategy
- Bilingual support (Arabic + English)

### For cmis-context-awareness Agent

**Must add:**
- Bilingual examples
- Arabic market understanding
- Permission system explanation

---

## 📝 RECOMMENDED AGENT UPDATES

1. **cmis-multi-tenancy.md**
   - Add permission-based RLS section
   - Add helper functions list
   - Add two-layer security explanation

2. **cmis-campaign-expert.md**
   - Add status enum constants
   - Add three context types deep-dive
   - Add soft delete with deletor pattern

3. **cmis-ai-semantic.md**
   - Add quality scoring section
   - Add usage optimization
   - Add bilingual support details

4. **cmis-context-awareness.md**
   - Add bilingual examples throughout
   - Add Arabic market context
   - Add permission codes catalog

5. **CMIS_PROJECT_KNOWLEDGE.md**
   - Add this file's insights
   - Update permission system section
   - Add bilingual support section

---

**This document provides the missing pieces for truly comprehensive CMIS understanding!**

**Last Updated:** 2025-11-18
**Based on:** database/backup-db-for-seeds.sql (6.2MB, 23,398 lines)
