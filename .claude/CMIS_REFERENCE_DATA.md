# CMIS Reference Data Tables
## Complete Reference Data from Seeders

**Source Files:**
- `database/seeders/ChannelsSeeder.php`
- `database/seeders/MarketsSeeder.php`
- `database/seeders/IndustriesSeeder.php`
- `database/seeders/PermissionsSeeder.php`
- `database/seeders/RolesSeeder.php`

**Purpose:** Provide AI agents with EXACT reference data values used in CMIS

---

## 📺 CHANNELS - Complete List

**Total Channels:** 10

### Social Media Channels

#### 1. Facebook
```json
{
    "code": "facebook",
    "name": "Facebook",
    "constraints": {
        "max_text_length": 63206,
        "max_link_caption": 30,
        "max_link_description": 90,
        "supported_formats": ["image", "video", "carousel", "collection"],
        "video_max_size_mb": 4096,
        "image_max_size_mb": 30
    }
}
```

#### 2. Instagram
```json
{
    "code": "instagram",
    "name": "Instagram",
    "constraints": {
        "max_caption_length": 2200,
        "max_hashtags": 30,
        "supported_formats": ["feed", "story", "reel", "carousel"],
        "video_max_duration_seconds": 90,
        "story_duration_seconds": 15,
        "reel_max_duration_seconds": 90
    }
}
```

#### 3. Twitter/X
```json
{
    "code": "twitter",
    "name": "Twitter/X",
    "constraints": {
        "max_text_length": 280,
        "max_video_duration_seconds": 140,
        "image_max_size_mb": 5,
        "video_max_size_mb": 512,
        "supported_formats": ["text", "image", "video", "poll"]
    }
}
```

#### 4. LinkedIn
```json
{
    "code": "linkedin",
    "name": "LinkedIn",
    "constraints": {
        "max_text_length": 3000,
        "max_video_duration_seconds": 600,
        "supported_formats": ["text", "image", "video", "document", "article"],
        "video_max_size_mb": 5120
    }
}
```

#### 5. TikTok
```json
{
    "code": "tiktok",
    "name": "TikTok",
    "constraints": {
        "max_caption_length": 2200,
        "max_video_duration_seconds": 600,
        "min_video_duration_seconds": 3,
        "supported_formats": ["video"],
        "video_max_size_mb": 287
    }
}
```

#### 6. YouTube
```json
{
    "code": "youtube",
    "name": "YouTube",
    "constraints": {
        "max_title_length": 100,
        "max_description_length": 5000,
        "max_video_duration_seconds": 43200,
        "video_max_size_mb": 256000,
        "supported_formats": ["video", "shorts"]
    }
}
```

#### 7. Snapchat
```json
{
    "code": "snapchat",
    "name": "Snapchat",
    "constraints": {
        "video_duration_seconds": 10,
        "supported_formats": ["image", "video"]
    }
}
```

#### 8. Pinterest
```json
{
    "code": "pinterest",
    "name": "Pinterest",
    "constraints": {
        "max_description_length": 500,
        "max_title_length": 100,
        "supported_formats": ["image", "video", "carousel"],
        "video_max_duration_seconds": 60
    }
}
```

### Advertising Platforms

#### 9. Google Ads
```json
{
    "code": "google_ads",
    "name": "Google Ads",
    "constraints": {
        "headline_max_length": 30,
        "max_headlines": 15,
        "description_max_length": 90,
        "max_descriptions": 4,
        "supported_formats": ["search", "display", "video", "shopping"]
    }
}
```

#### 10. Meta Ads
```json
{
    "code": "meta_ads",
    "name": "Meta Ads",
    "constraints": {
        "primary_text_max_length": 125,
        "headline_max_length": 40,
        "description_max_length": 30,
        "supported_formats": ["image", "video", "carousel", "collection"]
    }
}
```

---

## 🌍 MARKETS - Complete List

**Total Markets:** 20 markets across 4 regions

### Middle East & North Africa (10 markets)

| Market | Language | Currency | Text Direction |
|--------|----------|----------|----------------|
| Saudi Arabia | ar | SAR | rtl |
| United Arab Emirates | ar | AED | rtl |
| Egypt | ar | EGP | rtl |
| Kuwait | ar | KWD | rtl |
| Qatar | ar | QAR | rtl |
| Bahrain | ar | BHD | rtl |
| Oman | ar | OMR | rtl |
| Jordan | ar | JOD | rtl |
| Lebanon | ar | LBP | rtl |
| Morocco | ar | MAD | rtl |

### North America (2 markets)

| Market | Language | Currency | Text Direction |
|--------|----------|----------|----------------|
| United States | en | USD | ltr |
| Canada | en | CAD | ltr |

### Europe (5 markets)

| Market | Language | Currency | Text Direction |
|--------|----------|----------|----------------|
| United Kingdom | en | GBP | ltr |
| Germany | de | EUR | ltr |
| France | fr | EUR | ltr |
| Spain | es | EUR | ltr |
| Italy | it | EUR | ltr |

### Asia Pacific (3 markets)

| Market | Language | Currency | Text Direction |
|--------|----------|----------|----------------|
| India | en | INR | ltr |
| Singapore | en | SGD | ltr |
| Australia | en | AUD | ltr |

**Key Insights:**
- **Primary Focus:** Middle East (50% of markets)
- **Arabic Support:** 10 markets with RTL text direction
- **Multi-Currency:** 15 different currencies
- **Multi-Language:** Arabic, English, German, French, Spanish, Italian

---

## 🏭 INDUSTRIES - Complete List

**Total Industries:** 25

1. Technology & Software
2. E-commerce & Retail
3. Healthcare & Medical
4. Finance & Banking
5. Real Estate
6. Education & E-learning
7. Food & Beverage
8. Travel & Hospitality
9. Fashion & Apparel
10. Beauty & Cosmetics
11. Automotive
12. Entertainment & Media
13. Sports & Fitness
14. Home & Garden
15. Professional Services
16. Non-profit & Charity
17. Manufacturing
18. Construction
19. Agriculture
20. Energy & Utilities
21. Telecommunications
22. Insurance
23. Legal Services
24. Consulting
25. Marketing & Advertising

---

## 🔐 PERMISSIONS - Complete System

**Total Permissions:** 50+ permissions across 11 categories

### Permission Structure

```typescript
interface Permission {
    permission_code: string;      // e.g., "campaign.view"
    permission_name: string;      // e.g., "View Campaigns"
    category: string;             // e.g., "campaigns"
    is_dangerous: boolean;        // Security flag
}
```

### 1. Organization Management (3 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `org.view` | View Organization | ❌ |
| `org.edit` | Edit Organization | ❌ |
| `org.delete` | Delete Organization | ⚠️ Yes |

### 2. User Management (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `user.view` | View Users | ❌ |
| `user.create` | Create Users | ❌ |
| `user.edit` | Edit Users | ❌ |
| `user.delete` | Delete Users | ⚠️ Yes |
| `user.invite` | Invite Users | ❌ |

### 3. Access Control (6 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `role.view` | View Roles | ❌ |
| `role.create` | Create Roles | ❌ |
| `role.edit` | Edit Roles | ❌ |
| `role.delete` | Delete Roles | ⚠️ Yes |
| `permission.grant` | Grant Permissions | ⚠️ Yes |
| `permission.revoke` | Revoke Permissions | ⚠️ Yes |

### 4. Campaign Management (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `campaign.view` | View Campaigns | ❌ |
| `campaign.create` | Create Campaigns | ❌ |
| `campaign.edit` | Edit Campaigns | ❌ |
| `campaign.delete` | Delete Campaigns | ❌ |
| `campaign.publish` | Publish Campaigns | ❌ |

### 5. Creative Assets (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `creative.view` | View Creative Assets | ❌ |
| `creative.create` | Create Creative Assets | ❌ |
| `creative.edit` | Edit Creative Assets | ❌ |
| `creative.delete` | Delete Creative Assets | ❌ |
| `creative.approve` | Approve Creative Assets | ❌ |

### 6. Content Management (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `content.view` | View Content | ❌ |
| `content.create` | Create Content | ❌ |
| `content.edit` | Edit Content | ❌ |
| `content.delete` | Delete Content | ❌ |
| `content.publish` | Publish Content | ❌ |

### 7. Social Media Management (7 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `social.view` | View Social Posts | ❌ |
| `social.create` | Create Social Posts | ❌ |
| `social.edit` | Edit Social Posts | ❌ |
| `social.delete` | Delete Social Posts | ❌ |
| `social.publish` | Publish Social Posts | ❌ |
| `social.schedule` | Schedule Social Posts | ❌ |
| `social.respond` | Respond to Messages | ❌ |

### 8. Integration Management (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `integration.view` | View Integrations | ❌ |
| `integration.create` | Create Integrations | ❌ |
| `integration.edit` | Edit Integrations | ❌ |
| `integration.delete` | Delete Integrations | ⚠️ Yes |
| `integration.sync` | Sync Integration Data | ❌ |

### 9. Advertising (5 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `ads.view` | View Ads | ❌ |
| `ads.create` | Create Ads | ❌ |
| `ads.edit` | Edit Ads | ❌ |
| `ads.delete` | Delete Ads | ❌ |
| `ads.publish` | Publish Ads | ❌ |

### 10. Analytics & Reporting (4 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `analytics.view` | View Analytics | ❌ |
| `analytics.export` | Export Analytics | ❌ |
| `report.view` | View Reports | ❌ |
| `report.create` | Create Reports | ❌ |

### 11. System Administration (3 permissions)

| Code | Name | Dangerous? |
|------|------|------------|
| `system.settings` | Manage System Settings | ⚠️ Yes |
| `system.logs` | View System Logs | ❌ |
| `system.audit` | View Audit Logs | ❌ |

**Dangerous Permissions:** 8 total (marked with ⚠️)

---

## 👥 ROLES - Complete System

**Total System Roles:** 7 predefined roles

### Role Structure

```typescript
interface Role {
    role_code: string;           // e.g., "owner"
    role_name: string;          // e.g., "Owner"
    description: string;
    is_system: boolean;         // System-defined role
    is_active: boolean;
    org_id: uuid | null;        // null = system role
}
```

### 1. Owner Role
```json
{
    "role_code": "owner",
    "role_name": "Owner",
    "description": "Organization owner with full permissions",
    "is_system": true,
    "permissions": "ALL"
}
```

**Typical Permissions:**
- All permissions (100%)
- Full administrative control
- Billing and subscription management
- Can delete organization

### 2. Admin Role
```json
{
    "role_code": "admin",
    "role_name": "Admin",
    "description": "Administrator with management permissions",
    "is_system": true
}
```

**Typical Permissions:**
- Most permissions except org deletion
- User management
- Integration management
- System settings (limited)

### 3. Marketing Manager Role
```json
{
    "role_code": "marketing_manager",
    "role_name": "Marketing Manager",
    "description": "Can manage campaigns, content, and creative assets",
    "is_system": true
}
```

**Typical Permissions:**
- `campaign.*` (all campaign permissions)
- `creative.*` (all creative permissions)
- `content.*` (all content permissions)
- `social.*` (all social permissions except delete)
- `ads.*` (all ad permissions)
- `analytics.view`

### 4. Content Creator Role
```json
{
    "role_code": "content_creator",
    "role_name": "Content Creator",
    "description": "Can create and edit content and social posts",
    "is_system": true
}
```

**Typical Permissions:**
- `content.view`, `content.create`, `content.edit`
- `creative.view`, `creative.create`, `creative.edit`
- `social.view`, `social.create`, `social.edit`
- NO publish permissions
- NO delete permissions

### 5. Social Media Manager Role
```json
{
    "role_code": "social_manager",
    "role_name": "Social Media Manager",
    "description": "Can manage social media accounts and posts",
    "is_system": true
}
```

**Typical Permissions:**
- `social.*` (all social media permissions)
- `integration.view`, `integration.sync`
- `analytics.view` (social metrics)

### 6. Analyst Role
```json
{
    "role_code": "analyst",
    "role_name": "Analyst",
    "description": "Can view analytics and create reports",
    "is_system": true
}
```

**Typical Permissions:**
- `analytics.view`, `analytics.export`
- `report.view`, `report.create`
- `campaign.view`
- `social.view`
- `ads.view`

### 7. Viewer Role
```json
{
    "role_code": "viewer",
    "role_name": "Viewer",
    "description": "Read-only access to campaigns and content",
    "is_system": true
}
```

**Typical Permissions:**
- `campaign.view`
- `content.view`
- `creative.view`
- `social.view`
- `analytics.view`
- NO create, edit, or delete permissions

---

## 🎓 PERMISSION PATTERNS

### Pattern: `{domain}.{action}`

**Domains:**
- `org` - Organization
- `user` - User management
- `role` - Role management
- `permission` - Permission management
- `campaign` - Campaigns
- `creative` - Creative assets
- `content` - Content
- `social` - Social media
- `integration` - Integrations
- `ads` - Advertising
- `analytics` - Analytics
- `report` - Reports
- `system` - System

**Actions:**
- `view` - Read access
- `create` - Create new
- `edit` - Modify existing
- `delete` - Remove
- `publish` - Publish/activate
- `approve` - Approve content
- `schedule` - Schedule for future
- `respond` - Respond to messages
- `export` - Export data
- `sync` - Synchronize data
- `grant` - Grant permissions
- `revoke` - Revoke permissions

---

## 🔒 DANGEROUS PERMISSIONS

**8 Dangerous Permissions Marked:**

1. `org.delete` - Can delete entire organization
2. `user.delete` - Can remove users
3. `role.delete` - Can delete roles
4. `permission.grant` - Can elevate privileges
5. `permission.revoke` - Can remove privileges
6. `integration.delete` - Can disconnect integrations
7. `system.settings` - Can modify system settings

**Security Note:** These permissions should be granted carefully and may require additional confirmation.

---

## 💡 USAGE EXAMPLES FOR AGENTS

### Example 1: Validating Channel Constraints

```php
// Get channel constraints
$channel = Channel::where('code', 'instagram')->first();
$constraints = json_decode($channel->constraints);

// Validate caption length
if (strlen($caption) > $constraints->max_caption_length) {
    throw new ValidationException("Caption exceeds Instagram's {$constraints->max_caption_length} character limit");
}

// Validate hashtag count
$hashtags = extractHashtags($caption);
if (count($hashtags) > $constraints->max_hashtags) {
    throw new ValidationException("Exceeds Instagram's {$constraints->max_hashtags} hashtag limit");
}
```

### Example 2: Market-Specific Formatting

```php
// Get market information
$market = Market::where('market_name', 'Saudi Arabia')->first();

// Format currency
$amount = 1000;
$formatted = formatCurrency($amount, $market->currency_code); // "1,000 SAR"

// Set text direction for UI
$textDirection = $market->text_direction; // "rtl" for Arabic markets

// Use appropriate language
$language = $market->language_code; // "ar"
```

### Example 3: Permission Checking

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
```

### Example 4: Role-Based Access

```php
// Get user's role
$userOrg = auth()->user()->organizations()->first();
$role = $userOrg->pivot->role;

// Check role capabilities
if ($role->role_code === 'viewer') {
    // Read-only mode
    $canEdit = false;
    $canDelete = false;
} elseif ($role->role_code === 'owner') {
    // Full access
    $canEdit = true;
    $canDelete = true;
}
```

### Example 5: Industry-Based Segmentation

```php
// Get campaigns for specific industry
$industry = Industry::where('name', 'Technology & Software')->first();

$campaigns = Campaign::whereHas('segments', function ($query) use ($industry) {
    $query->where('industry_id', $industry->industry_id);
})->get();
```

---

## ⚠️ CRITICAL WARNINGS

### 1. Channel Constraints Are HARD LIMITS

```php
// WRONG - Exceeding platform limits
$caption = str_repeat('a', 3000);  // Exceeds Instagram's 2200 limit ❌

// CORRECT - Validate against constraints
$channel = Channel::where('code', 'instagram')->first();
$maxLength = json_decode($channel->constraints)->max_caption_length;
$caption = substr($caption, 0, $maxLength);  ✅
```

### 2. Market Text Direction Affects UI

```php
// WRONG - Assuming LTR everywhere
<div style="text-align: left">  ❌

// CORRECT - Use market text direction
<div style="text-align: {{ $market->text_direction === 'rtl' ? 'right' : 'left' }}">  ✅
```

### 3. Permission Codes Are Case-Sensitive

```php
// WRONG
$user->hasPermission('Campaign.View');  ❌

// CORRECT
$user->hasPermission('campaign.view');  ✅
```

### 4. System Roles Cannot Be Deleted

```php
// WRONG - Trying to delete system role
if ($role->is_system) {
    $role->delete();  ❌ Will fail
}

// CORRECT - Check before deletion
if (!$role->is_system && $role->can_delete) {
    $role->delete();  ✅
}
```

---

## 📊 REFERENCE DATA STATISTICS

### Channels
- Total: 10
- Social Media: 8
- Advertising: 2
- Video-First: 4 (Instagram, TikTok, YouTube, Snapchat)
- Text-Heavy: 3 (Twitter, LinkedIn, Facebook)

### Markets
- Total: 20
- Arabic Markets: 10 (50%)
- Currencies: 15 unique
- Text Directions: 2 (LTR: 10, RTL: 10)

### Industries
- Total: 25
- Service-Based: 8
- Product-Based: 12
- Mixed: 5

### Permissions
- Total: 50+
- Categories: 11
- Dangerous: 8 (16%)
- Safe: 42+ (84%)

### Roles
- Total System Roles: 7
- Management Roles: 3 (Owner, Admin, Marketing Manager)
- Operational Roles: 3 (Content Creator, Social Manager, Analyst)
- Read-Only: 1 (Viewer)

---

**This document provides EXACT reference data that AI agents must use when working with CMIS!**

**Last Updated:** 2025-11-18
**Sources:** ChannelsSeeder.php, MarketsSeeder.php, IndustriesSeeder.php, PermissionsSeeder.php, RolesSeeder.php
