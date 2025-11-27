# CMIS Reference Data Discovery
**Version:** 2.1
**Last Updated:** 2025-11-27
**Purpose:** Finding and using platform reference data through live database queries
**Prerequisites:** Read `.claude/knowledge/DISCOVERY_PROTOCOLS.md` for discovery methodology
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## ⚠️ IMPORTANT: Environment Configuration

**ALWAYS read from `.env` for database credentials when querying reference data.**

```bash
# Read database configuration
cat .env | grep DB_

# Extract for use in reference data queries
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)

# Query reference data
PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" \
  -c "SELECT * FROM cmis.channels;"
```

---

## 📑 Table of Contents

1. [Philosophy: Live Data Over Static Lists](#-philosophy-live-data-over-static-lists)
2. [Discovering Reference Data](#-discovering-reference-data)
3. [Channels Reference](#-channels-reference)
4. [Markets Reference](#-markets-reference)
5. [Permissions System](#-permissions-system)
6. [Roles System](#-roles-system)
7. [Industries Reference](#-industries-reference)
8. [Using Reference Data in Code](#-using-reference-data-in-code)
9. [Quick Reference Queries](#-quick-reference-queries)
10. [Key Takeaways](#-key-takeaways)

---

## 🎓 PHILOSOPHY: LIVE DATA OVER STATIC LISTS

**Not:** "Here are all 20 markets"
**But:** "How do I query available markets?"

**Not:** "Memorize channel constraints"
**But:** "How do I fetch current channel limits?"

**Not:** "These are the exact permissions"
**But:** "How do I discover the permission system?"

---

## 🔍 DISCOVERING REFERENCE DATA

### Where Reference Data Lives

**Step 1: Find Reference Seeders**

```bash
# List all seeders
ls -la database/seeders/

# Find reference data seeders
ls database/seeders/*Seeder.php | grep -E "Channel|Market|Industry|Permission|Role"

# Common reference seeders:
# - ChannelsSeeder.php
# - MarketsSeeder.php
# - IndustriesSeeder.php
# - PermissionsSeeder.php
# - RolesSeeder.php
```

**Step 2: Query Database for Current Data**

```sql
-- List all channels
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT code, name FROM cmis.channels ORDER BY code;
"

-- List all markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT market_name, language_code, currency_code, text_direction
FROM cmis.markets
ORDER BY market_name;
"

-- List all industries
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT name FROM cmis.industries ORDER BY name;
"

-- List all permissions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT permission_code, permission_name, category, is_dangerous
FROM cmis.permissions
ORDER BY category, permission_code;
"

-- List all roles
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT role_code, role_name, is_system
FROM cmis.roles
WHERE is_system = true
ORDER BY role_name;
"
```

---

## 📺 PATTERN 1: CHANNEL CONSTRAINTS

### Discovery: How to Get Channel Limits

```sql
-- Get channel with constraints
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    code,
    name,
    jsonb_pretty(constraints) as channel_constraints
FROM cmis.channels
WHERE code = 'instagram'
LIMIT 1;
"
```

**Pattern Recognition:**

When you query a channel, you'll see JSONB constraints like:

```json
{
  "max_caption_length": 2200,
  "max_hashtags": 30,
  "supported_formats": ["feed", "story", "reel", "carousel"],
  "video_max_duration_seconds": 90,
  "story_duration_seconds": 15,
  "reel_max_duration_seconds": 90
}
```

**Key Insight:** Constraints are stored as JSONB, allowing flexible platform-specific limits

### Discovery: All Available Channels

```bash
# Quick count
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT COUNT(*) as total_channels FROM cmis.channels;
"

# List all with key info
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    code,
    name,
    constraints->>'max_text_length' as text_limit,
    constraints->>'supported_formats' as formats
FROM cmis.channels
ORDER BY name;
"
```

### Pattern: Validating Against Channel Constraints

```php
// DISCOVERY-ORIENTED: Always query current constraints
$channel = Channel::where('code', 'instagram')->first();
$constraints = json_decode($channel->constraints);

// Validate caption length
if (strlen($caption) > $constraints->max_caption_length) {
    throw new ValidationException(
        "Caption exceeds Instagram's {$constraints->max_caption_length} character limit"
    );
}

// Validate hashtags
$hashtags = extractHashtags($caption);
if (isset($constraints->max_hashtags) && count($hashtags) > $constraints->max_hashtags) {
    throw new ValidationException(
        "Exceeds Instagram's {$constraints->max_hashtags} hashtag limit"
    );
}

// Validate format
if (!in_array($format, $constraints->supported_formats)) {
    throw new ValidationException(
        "Format '{$format}' not supported on Instagram"
    );
}
```

**Representative Examples:**

```json
// Instagram
{
  "max_caption_length": 2200,
  "max_hashtags": 30,
  "supported_formats": ["feed", "story", "reel", "carousel"]
}

// Twitter/X
{
  "max_text_length": 280,
  "max_video_duration_seconds": 140,
  "supported_formats": ["text", "image", "video", "poll"]
}

// LinkedIn
{
  "max_text_length": 3000,
  "max_video_duration_seconds": 600,
  "supported_formats": ["text", "image", "video", "document", "article"]
}

// TikTok
{
  "max_caption_length": 2200,
  "max_video_duration_seconds": 600,
  "min_video_duration_seconds": 3,
  "supported_formats": ["video"]
}
```

---

## 🌍 PATTERN 2: MARKETS & LOCALIZATION

### Discovery: Available Markets

```sql
-- Count markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT COUNT(*) as total_markets FROM cmis.markets;
"

-- Group by region
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    region_code,
    COUNT(*) as market_count
FROM cmis.markets
GROUP BY region_code
ORDER BY market_count DESC;
"

-- RTL vs LTR markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    text_direction,
    COUNT(*) as count
FROM cmis.markets
GROUP BY text_direction;
"
```

### Pattern Recognition: Market Structure

```sql
-- Sample market data
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    market_name,
    language_code,
    currency_code,
    text_direction,
    region_code
FROM cmis.markets
LIMIT 5;
"
```

**Pattern You'll Discover:**

| Field | Type | Examples | Purpose |
|-------|------|----------|---------|
| `market_name` | string | 'Saudi Arabia', 'United States' | Display name |
| `language_code` | string | 'ar', 'en', 'fr' | ISO language code |
| `currency_code` | string | 'SAR', 'USD', 'EUR' | ISO currency code |
| `text_direction` | enum | 'ltr', 'rtl' | Text flow direction |
| `region_code` | string | 'mena', 'na', 'eu' | Geographic region |

### Pattern: Market-Specific Formatting

```php
// DISCOVERY-ORIENTED: Query market data
$market = Market::where('market_name', 'Saudi Arabia')->first();

// Format currency based on market
$amount = 1000;
$formatted = formatCurrency($amount, $market->currency_code); // "1,000 SAR"

// Set UI text direction
$textDirection = $market->text_direction; // "rtl" for Arabic markets
echo "<div dir='{$textDirection}'>{$content}</div>";

// Use appropriate language
$language = $market->language_code; // "ar"
App::setLocale($language);
```

**Key Markets by Region (Examples):**

```
Middle East & North Africa (MENA):
- Saudi Arabia (ar, SAR, rtl)
- UAE (ar, AED, rtl)
- Egypt (ar, EGP, rtl)

North America (NA):
- United States (en, USD, ltr)
- Canada (en, CAD, ltr)

Europe (EU):
- United Kingdom (en, GBP, ltr)
- Germany (de, EUR, ltr)
- France (fr, EUR, ltr)

Asia Pacific (APAC):
- India (en, INR, ltr)
- Singapore (en, SGD, ltr)
```

**Discovery Pattern:**

```sql
-- Find Arabic markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT market_name, currency_code
FROM cmis.markets
WHERE language_code = 'ar'
ORDER BY market_name;
"

-- Find EUR markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT market_name
FROM cmis.markets
WHERE currency_code = 'EUR'
ORDER BY market_name;
"
```

---

## 🏭 PATTERN 3: INDUSTRIES

### Discovery: Industry List

```sql
-- Count industries
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT COUNT(*) as total_industries FROM cmis.industries;
"

-- List all
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    industry_id,
    name,
    description
FROM cmis.industries
ORDER BY name;
"
```

**Representative Industries (Pattern):**

- Technology & Software
- E-commerce & Retail
- Healthcare & Medical
- Finance & Banking
- Real Estate
- Education & E-learning
- Food & Beverage
- Travel & Hospitality
- Fashion & Apparel
- Beauty & Cosmetics
- Automotive
- Entertainment & Media
- Sports & Fitness
- Professional Services
- Marketing & Advertising

### Pattern: Industry-Based Filtering

```php
// Query industry
$industry = Industry::where('name', 'Technology & Software')->first();

// Filter campaigns by industry
$campaigns = Campaign::whereHas('segments', function ($query) use ($industry) {
    $query->where('industry_id', $industry->industry_id);
})->get();
```

---

## 🔐 PATTERN 4: PERMISSION SYSTEM

### Discovery: Permission Structure

```sql
-- Discover permission pattern
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    permission_code,
    permission_name,
    category,
    is_dangerous
FROM cmis.permissions
ORDER BY category, permission_code
LIMIT 20;
"

-- Count by category
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    category,
    COUNT(*) as permission_count
FROM cmis.permissions
GROUP BY category
ORDER BY permission_count DESC;
"

-- Find dangerous permissions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT permission_code, permission_name
FROM cmis.permissions
WHERE is_dangerous = true
ORDER BY permission_code;
"
```

### Pattern Recognition: Permission Naming

**Pattern:** `{domain}.{action}`

**Domains:**
- `org` - Organization management
- `user` - User management
- `role` - Role management
- `campaign` - Campaign operations
- `creative` - Creative assets
- `content` - Content management
- `social` - Social media
- `integration` - Platform integrations
- `ads` - Advertising
- `analytics` - Analytics & reporting
- `system` - System administration

**Actions:**
- `view` - Read access
- `create` - Create new records
- `edit` - Modify existing
- `delete` - Remove records
- `publish` - Activate/publish
- `approve` - Approve content
- `schedule` - Schedule for future
- `export` - Export data
- `sync` - Synchronize data

**Examples:**
```
campaign.view
campaign.create
campaign.edit
campaign.delete
campaign.publish

social.view
social.create
social.publish
social.schedule

analytics.view
analytics.export
```

### Pattern: Permission Checking

```php
// Check if user has permission
if (!auth()->user()->hasPermission('campaign.publish')) {
    return response()->json(['error' => 'Permission denied'], 403);
}

// Check for dangerous permission
$permission = Permission::where('permission_code', 'org.delete')->first();
if ($permission->is_dangerous) {
    // Require additional confirmation
    return response()->json([
        'warning' => 'This is a dangerous operation',
        'requires_confirmation' => true
    ]);
}

// Check multiple permissions
$required = ['campaign.create', 'campaign.publish'];
if (!auth()->user()->hasAllPermissions($required)) {
    return response()->json(['error' => 'Insufficient permissions'], 403);
}
```

### Dangerous Permissions (Pattern)

```sql
-- Discover all dangerous permissions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    permission_code,
    permission_name,
    category
FROM cmis.permissions
WHERE is_dangerous = true
ORDER BY category;
"
```

**Typical Dangerous Permissions:**
- `org.delete` - Delete organization
- `user.delete` - Remove users
- `role.delete` - Delete roles
- `permission.grant` - Escalate privileges
- `permission.revoke` - Remove access
- `integration.delete` - Disconnect platforms
- `system.settings` - Modify system config

---

## 👥 PATTERN 5: ROLE SYSTEM

### Discovery: System Roles

```sql
-- List system roles
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    role_code,
    role_name,
    description,
    is_system
FROM cmis.roles
WHERE is_system = true
ORDER BY role_name;
"

-- Count roles
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    COUNT(*) FILTER (WHERE is_system = true) as system_roles,
    COUNT(*) FILTER (WHERE is_system = false) as custom_roles
FROM cmis.roles;
"
```

### Pattern: Role Hierarchy

**Discovered Role Codes:**
- `owner` - Full access (highest privilege)
- `admin` - Administrative access
- `marketing_manager` - Campaign management
- `content_creator` - Content creation
- `social_manager` - Social media management
- `analyst` - Analytics & reporting
- `viewer` - Read-only access (lowest privilege)

### Discovery: Role Permissions

```sql
-- Get permissions for a role
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "
SELECT
    p.permission_code,
    p.permission_name
FROM cmis.role_permissions rp
JOIN cmis.permissions p ON rp.permission_id = p.permission_id
JOIN cmis.roles r ON rp.role_id = r.role_id
WHERE r.role_code = 'marketing_manager'
ORDER BY p.category, p.permission_code;
"
```

### Pattern: Role-Based Access

```php
// Get user's role in organization
$userOrg = auth()->user()->organizations()->find($orgId);
$role = $userOrg->pivot->role;

// Check role capabilities
if ($role->role_code === 'viewer') {
    // Read-only mode
    $canEdit = false;
    $canDelete = false;
    $canPublish = false;
} elseif ($role->role_code === 'owner') {
    // Full access
    $canEdit = true;
    $canDelete = true;
    $canPublish = true;
} else {
    // Check specific permissions
    $canEdit = $role->hasPermission('campaign.edit');
    $canDelete = $role->hasPermission('campaign.delete');
    $canPublish = $role->hasPermission('campaign.publish');
}

// System roles cannot be deleted
if ($role->is_system) {
    // Cannot modify or delete
    $canModify = false;
}
```

---

## 🎯 PRACTICAL USAGE PATTERNS

### Pattern 1: Multi-Platform Post Validation

```php
// Validate post for each platform
foreach ($platforms as $platform) {
    $channel = Channel::where('code', $platform)->first();
    $constraints = json_decode($channel->constraints);

    // Check text length
    if (isset($constraints->max_caption_length)) {
        if (strlen($caption) > $constraints->max_caption_length) {
            throw new ValidationException(
                "{$channel->name}: Caption exceeds {$constraints->max_caption_length} chars"
            );
        }
    }

    // Check video duration
    if ($mediaType === 'video' && isset($constraints->max_video_duration_seconds)) {
        if ($videoDuration > $constraints->max_video_duration_seconds) {
            throw new ValidationException(
                "{$channel->name}: Video exceeds {$constraints->max_video_duration_seconds}s"
            );
        }
    }

    // Check format support
    if (!in_array($format, $constraints->supported_formats)) {
        throw new ValidationException(
            "{$channel->name}: Format '{$format}' not supported"
        );
    }
}
```

### Pattern 2: Market-Aware UI Rendering

```php
// Get user's market
$market = auth()->user()->market;

// Set locale and direction
App::setLocale($market->language_code);
$dir = $market->text_direction;
$currency = $market->currency_code;

// Render market-aware UI
return view('dashboard', [
    'textDirection' => $dir,
    'currency' => $currency,
    'locale' => $market->language_code,
    'market' => $market->market_name
]);
```

### Pattern 3: Permission-Based Menu

```php
// Build menu based on permissions
$menu = [];

if (auth()->user()->hasPermission('campaign.view')) {
    $menu[] = ['label' => 'Campaigns', 'url' => '/campaigns'];
}

if (auth()->user()->hasPermission('campaign.create')) {
    $menu[] = ['label' => 'New Campaign', 'url' => '/campaigns/create'];
}

if (auth()->user()->hasPermission('social.view')) {
    $menu[] = ['label' => 'Social Media', 'url' => '/social'];
}

if (auth()->user()->hasPermission('analytics.view')) {
    $menu[] = ['label' => 'Analytics', 'url' => '/analytics'];
}

if (auth()->user()->hasPermission('system.settings')) {
    $menu[] = ['label' => 'Settings', 'url' => '/settings'];
}
```

---

## 📋 DISCOVERY COMMANDS CHEAT SHEET

### Quick Reference Queries

```bash
# List all channels
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT code, name FROM cmis.channels ORDER BY code;"

# List all markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT market_name, currency_code FROM cmis.markets ORDER BY market_name;"

# List all industries
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT name FROM cmis.industries ORDER BY name;"

# List permissions by category
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT category, COUNT(*) FROM cmis.permissions GROUP BY category ORDER BY category;"

# List system roles
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT role_code, role_name FROM cmis.roles WHERE is_system = true ORDER BY role_name;"

# Get channel constraints
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT code, jsonb_pretty(constraints) FROM cmis.channels WHERE code = 'instagram';"

# Find dangerous permissions
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT permission_code, permission_name FROM cmis.permissions WHERE is_dangerous = true;"

# Find RTL markets
PGPASSWORD="$(grep DB_PASSWORD .env | cut -d '=' -f2)" psql \
  -h "$(grep DB_HOST .env | cut -d '=' -f2)" \
  -U "$(grep DB_USERNAME .env | cut -d '=' -f2)" \
  -d "$(grep DB_DATABASE .env | cut -d '=' -f2)" \
  -c "SELECT market_name FROM cmis.markets WHERE text_direction = 'rtl';"
```

---

## ⚠️ CRITICAL RULES

### Rule 1: Always Query Current Constraints

```php
// ❌ WRONG - Hardcoded limits
if (strlen($caption) > 2200) { ... }

// ✅ RIGHT - Query current limits
$channel = Channel::where('code', $platform)->first();
$maxLength = json_decode($channel->constraints)->max_caption_length;
if (strlen($caption) > $maxLength) { ... }
```

### Rule 2: Respect Market Text Direction

```php
// ❌ WRONG - Assume LTR
<div style="text-align: left">

// ✅ RIGHT - Use market direction
<div dir="{{ $market->text_direction }}">
```

### Rule 3: Permission Codes Are Case-Sensitive

```php
// ❌ WRONG
$user->hasPermission('Campaign.View');

// ✅ RIGHT
$user->hasPermission('campaign.view');
```

### Rule 4: System Roles Cannot Be Modified

```php
// ❌ WRONG
if ($role->is_system) {
    $role->delete(); // Will fail
}

// ✅ RIGHT
if (!$role->is_system) {
    $role->delete();
}
```

### Rule 5: Validate Dangerous Operations

```php
// When using dangerous permission
$permission = Permission::where('permission_code', $permissionCode)->first();
if ($permission->is_dangerous) {
    // Require confirmation
    // Log audit trail
    // Send notification
}
```

---

## 🎓 LEARNING WORKFLOW

### When Working with Reference Data

1. **Query current data** - Don't assume static values
   ```sql
   SELECT * FROM cmis.channels WHERE code = 'platform';
   ```

2. **Check constraints** - Validate against platform limits
   ```php
   $constraints = json_decode($channel->constraints);
   ```

3. **Respect permissions** - Always check user access
   ```php
   if (!auth()->user()->hasPermission('action')) { ... }
   ```

4. **Consider markets** - Account for localization
   ```php
   $market = auth()->user()->market;
   App::setLocale($market->language_code);
   ```

5. **Use appropriate industry** - Filter by business domain
   ```php
   $industry = Industry::where('name', $industryName)->first();
   ```

---

## 🔍 Quick Reference

| I Need To... | Discovery Command | Section |
|--------------|-------------------|---------|
| List all channels | `SELECT code, name FROM cmis.channels` (use .env) | Channels |
| Find markets | `SELECT market_name, currency_code FROM cmis.markets` (use .env) | Markets |
| Check permissions | `SELECT permission_code FROM cmis.permissions` (use .env) | Permissions |
| View system roles | `SELECT role_code FROM cmis.roles WHERE is_system = true` (use .env) | Roles |
| Get channel constraints | `SELECT code, constraints FROM cmis.channels WHERE code = 'instagram'` (use .env) | Channel Constraints |
| Find dangerous permissions | `SELECT permission_code FROM cmis.permissions WHERE is_dangerous = true` (use .env) | Dangerous Permissions |
| List RTL markets | `SELECT market_name FROM cmis.markets WHERE text_direction = 'rtl'` (use .env) | RTL Markets |
| Find industries | `SELECT name FROM cmis.industries ORDER BY name` (use .env) | Industries |

---

## 📚 Related Knowledge

**Prerequisites:**
- **DISCOVERY_PROTOCOLS.md** - Discovery methodology and executable commands
- **META_COGNITIVE_FRAMEWORK.md** - Adaptive intelligence principles

**Related Files:**
- **CMIS_DISCOVERY_GUIDE.md** - General discovery methodology
- **CMIS_DATA_PATTERNS.md** - Data structure and modeling patterns
- **PATTERN_RECOGNITION.md** - Architectural patterns across codebase
- **MULTI_TENANCY_PATTERNS.md** - RLS and multi-tenancy patterns
- **CMIS_SQL_INSIGHTS.md** - SQL patterns and database queries

**See Also:**
- **CLAUDE.md** - Main project guidelines
- **CMIS_PROJECT_KNOWLEDGE.md** - Core project architecture

---

## 🎯 KEY TAKEAWAYS

1. **Always query current data** - Reference data can change
2. **Channel constraints are JSONB** - Flexible, platform-specific
3. **Markets determine localization** - Language, currency, direction
4. **Permission naming follows pattern** - `domain.action`
5. **Dangerous permissions require care** - Additional validation needed
6. **System roles are protected** - Cannot be deleted or modified
7. **Validate against platform limits** - Don't exceed constraints

---

**Last Updated:** 2025-11-27
**Version:** 2.1
**Maintained By:** CMIS AI Agent Development Team
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** Query Current, Don't Memorize Static

*"Reference data should be queried, not memorized. Use .env for all database access."*
