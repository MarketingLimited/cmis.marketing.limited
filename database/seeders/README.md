# Database Seeders

This directory contains comprehensive Laravel database seeders for the CMIS marketing platform.

## Overview

The seeders create a complete demo environment with:
- **Reference data** (channels, industries, markets, marketing objectives)
- **Core entities** (organizations, users, roles, permissions)
- **Demo organizations** with complete workflows
- **Integrations** (Instagram, Facebook Ads)
- **Campaigns, content plans, creative assets**
- **Social media posts (published and scheduled)**
- **Ad accounts and campaigns**

## Complete Seeder List

### Level 1: Reference Data (No Dependencies)

| Seeder | Table(s) | Description |
|--------|----------|-------------|
| **ChannelsSeeder** | `public.channels` | Marketing channels (Facebook, Instagram, TikTok, YouTube, Google Ads, etc.) |
| **ChannelFormatsSeeder** | `public.channel_formats` | Format specifications for each channel (aspect ratios, durations) |
| **IndustriesSeeder** | `public.industries` | Industry categories for targeting |
| **MarketsSeeder** | `public.markets` | Geographic markets with language and currency |
| **MarketingObjectivesSeeder** | `public.marketing_objectives` | Campaign objectives (awareness, conversions, etc.) |
| **ReferenceDataSeeder** | `public.awareness_stages`, `funnel_stages`, `tones`, `strategies`, `kpis` | Additional reference data |

### Level 2: Core Entities (Depend on Reference Data)

| Seeder | Table(s) | Description |
|--------|----------|-------------|
| **OrgsSeeder** | `cmis.orgs` | 4 demo organizations |
| **PermissionsSeeder** | `cmis.permissions` | 54 comprehensive permissions across all categories |
| **RolesSeeder** | `cmis.roles` | 7 system roles (Owner, Admin, Marketing Manager, etc.) |
| **UsersSeeder** | `cmis.users` | 7 demo users across different organizations |

### Level 3: Demo Data (Depends on Core Entities)

| Seeder | Table(s) | Description |
|--------|----------|-------------|
| **DemoDataSeeder** | Multiple tables | Creates comprehensive interconnected demo data including:<br>• User-organization relationships<br>• Role permissions<br>• Offerings and segments<br>• Integrations (Instagram, Facebook Ads)<br>• Social accounts<br>• Ad accounts<br>• Campaigns<br>• Creative briefs<br>• Creative assets<br>• Content plans<br>• Social posts (published)<br>• Scheduled posts<br>• Ad campaigns with ad sets and ads<br>• Performance metrics<br>• Publishing queues<br>• Inbox items (comments & messages)<br>• Post approvals<br>• AB tests with variations<br>• Audience templates<br>• Notifications<br>• User activities<br>• Team invitations |

### Optional (Development Only)

| Seeder | Table(s) | Description |
|--------|----------|-------------|
| **SessionsSeeder** | `cmis.sessions` | Sample session data (only in local/development) |
| **MigrationsSeeder** | `cmis.migrations` | **Not used** - migrations managed by Laravel |

## Demo Organizations

### 1. TechVision Solutions 🚀
- **Industry**: Technology & Software
- **Currency**: USD
- **Team Members**: Sarah Johnson (Marketing Manager), Maria Garcia (Content Creator)
- **Features**:
  - CloudSync Pro product launch campaign
  - Instagram integration with published posts
  - Facebook Ads integration with active campaigns
  - Scheduled social posts
  - Creative assets and content plans

### 2. الشركة العربية للتسويق 🌍
- **Industry**: Marketing & Advertising
- **Currency**: SAR (Saudi Riyal)
- **Team Members**: محمد أحمد (Marketing Manager), Ahmed Al-Rashid (Social Media Manager)
- **Features**: Arabic language support, RTL text direction

### 3. FashionHub Retail 👗
- **Industry**: Fashion & Apparel
- **Currency**: EUR
- **Team Member**: Emma Williams (Social Media Manager)
- **Features**:
  - Summer Collection 2025 campaign
  - Instagram integration with carousel posts
  - Content plan with daily posting strategy
  - Scheduled social media posts

### 4. HealthWell Clinic 🏥
- **Industry**: Healthcare & Medical
- **Currency**: AED (UAE Dirham)
- **Team Member**: David Chen (Marketing Manager)

## Demo Users

All users have the password: **`password`**

| Email | Name | Organizations | Role |
|-------|------|---------------|------|
| admin@cmis.test | Admin User | All 4 orgs | Owner |
| sarah@techvision.com | Sarah Johnson | TechVision Solutions | Marketing Manager |
| mohamed@arabic-marketing.com | محمد أحمد | الشركة العربية للتسويق | Marketing Manager |
| emma@fashionhub.com | Emma Williams | FashionHub Retail | Social Media Manager |
| david@healthwell.com | David Chen | HealthWell Clinic | Marketing Manager |
| maria@techvision.com | Maria Garcia | TechVision Solutions | Content Creator |
| ahmed@arabic-marketing.com | Ahmed Al-Rashid | الشركة العربية للتسويق | Social Media Manager |

## Usage

### Run All Seeders

```bash
php artisan db:seed
```

### Run Specific Seeder

```bash
php artisan db:seed --class=DemoDataSeeder
```

### Fresh Migration with Seeding

```bash
php artisan migrate:fresh --seed
```

### Run Only Reference Data

```bash
php artisan db:seed --class=ChannelsSeeder
php artisan db:seed --class=IndustriesSeeder
# etc.
```

## Seeding Order (Dependency Chain)

The `DatabaseSeeder` orchestrates the seeding in this order:

```
1. Reference Data (Level 1)
   ├── ChannelsSeeder
   ├── ChannelFormatsSeeder
   ├── IndustriesSeeder
   ├── MarketsSeeder
   ├── MarketingObjectivesSeeder
   └── ReferenceDataSeeder

2. Core Entities (Level 2)
   ├── OrgsSeeder
   ├── PermissionsSeeder
   ├── RolesSeeder
   └── UsersSeeder

3. Demo Data (Level 3)
   └── DemoDataSeeder
       ├── User-Org relationships
       ├── Role permissions
       ├── Offerings & Segments
       ├── Integrations
       ├── Social Accounts
       ├── Ad Accounts
       ├── Campaigns
       ├── Creative Assets
       ├── Content Plans
       ├── Social Posts (published)
       ├── Scheduled Posts
       ├── Ad Campaigns (with ad sets & ads)
       ├── Creative Briefs
       ├── Performance Metrics
       ├── Publishing Queues
       ├── Inbox Items
       ├── Post Approvals
       ├── AB Tests & Variations
       ├── Audience Templates
       ├── Notifications
       ├── User Activities
       └── Team Invitations
```

## What Gets Created

### Reference Data
- **10 Marketing Channels** with platform constraints
- **29 Channel Formats** with aspect ratios and durations
- **25 Industries**
- **20 Geographic Markets** (MENA, Europe, North America, Asia Pacific)
- **12 Marketing Objectives**
- **12 KPIs**
- **5 Awareness Stages**, **5 Funnel Stages**, **12 Tones**, **10 Strategies**

### Core Entities
- **4 Organizations** (diverse industries and markets)
- **54 Permissions** (organization, user management, campaigns, creative, content, social media, integrations, ads, analytics, system)
- **7 System Roles** with appropriate permissions
- **7 Users** with realistic profiles

### Demo Data (TechVision Solutions Example)
- ✅ **1 Active Campaign**: CloudSync Pro Launch ($25,000 budget)
- ✅ **1 Creative Brief** with objectives, target audience, brand guidelines
- ✅ **1 Instagram Integration** (12,543 followers)
- ✅ **1 Facebook Ads Integration**
- ✅ **2 Published Instagram Posts** (with engagement metrics)
- ✅ **1 Scheduled Instagram Post** (multi-platform)
- ✅ **1 Ad Account** with active campaigns
- ✅ **1 Ad Campaign** ($10,000 budget, 156 leads generated)
- ✅ **1 Ad Set** targeting IT Directors in US/UK
- ✅ **1 Individual Ad** (Variant A)
- ✅ **1 AB Test** (Headline variation testing - running)
- ✅ **1 Creative Asset** (approved)
- ✅ **2 Product Offerings**
- ✅ **1 Audience Segment**
- ✅ **1 Audience Template** (Enterprise IT Decision Makers)
- ✅ **3 Performance Metrics** (impressions, CTR, conversion rate)
- ✅ **1 Publishing Queue** (Mon-Fri, 4 time slots)
- ✅ **2 Inbox Items** (comment & message awaiting response)
- ✅ **1 Post Approval** (pending approval workflow)
- ✅ **Multiple Notifications** (welcome, campaign updates)
- ✅ **4 User Activities** (login, view campaign, create post, edit creative)
- ✅ **1 Team Invitation** (pending)

### Demo Data (FashionHub Retail Example)
- ✅ **1 Active Campaign**: Summer Collection 2025 (€15,000 budget)
- ✅ **1 Creative Brief** with collection launch strategy
- ✅ **1 Instagram Integration** (45,621 followers)
- ✅ **1 Published Carousel Post** (1,253 likes, 87 comments)
- ✅ **1 Scheduled Carousel Post** (3 images)
- ✅ **1 Content Plan** with daily posting strategy
- ✅ **2 Product Offerings** (Summer Collection, Premium Accessories)
- ✅ **1 Audience Segment** (Fashion Enthusiasts 18-35)
- ✅ **1 Audience Template** (reusable targeting)
- ✅ **3 Performance Metrics** with targets and baselines
- ✅ **1 Publishing Queue** (auto-scheduling system)
- ✅ **2 Inbox Items** (customer inquiries)
- ✅ **1 Post Approval** (in workflow)
- ✅ **Multiple Notifications** per user
- ✅ **User Activities** tracking
- ✅ **1 Team Invitation** (pending)

## Database Relationships Demonstrated

The demo data shows real-world relationships:

- **Users** ↔ **Organizations** (via `user_orgs` with roles)
- **Roles** ↔ **Permissions** (via `role_permissions`)
- **Campaigns** → **Organizations** (owned by org)
- **Campaigns** → **Users** (created by user)
- **Campaigns** ↔ **Offerings** (promoting products/services)
- **Integrations** → **Organizations** (connected platforms)
- **Social Accounts** → **Integrations** (linked accounts)
- **Social Posts** → **Social Accounts** (published content)
- **Scheduled Posts** → **Campaigns** (planned content)
- **Ad Accounts** → **Integrations** (ad platform connections)
- **Ad Campaigns** → **Ad Accounts** (ad hierarchy)
- **Ad Sets** → **Ad Campaigns** (targeting groups)
- **Ad Entities** → **Ad Sets** (individual ads)
- **Creative Assets** → **Campaigns** (campaign materials)
- **Content Plans** → **Campaigns** (content strategy)

## Permission System

The seeder creates a comprehensive RBAC (Role-Based Access Control) system:

### Permission Categories
- **organization**: View/edit/delete organization
- **user_management**: User CRUD and invitations
- **access_control**: Role and permission management
- **campaigns**: Campaign lifecycle management
- **creative**: Creative asset management
- **content**: Content management
- **social_media**: Social media management
- **integrations**: Platform integrations
- **advertising**: Ad platform management
- **analytics**: Analytics and reporting
- **system**: System administration

### Role Hierarchy (permissions increase downward)
1. **Viewer** - Read-only access
2. **Analyst** - Analytics and reporting
3. **Content Creator** - Create content and creative assets
4. **Social Media Manager** - Full social media management
5. **Marketing Manager** - Campaigns, content, creative, social, ads
6. **Admin** - All management permissions
7. **Owner** - All permissions including dangerous ones

## Regenerating Seeders from Backup

If you need to regenerate seeders from the backup SQL file:

```bash
php scripts/generate-seeders-from-backup.php
```

This will parse `database/backup-db-for-seeds.sql` and create/update seeder classes.

## Technical Notes

- **Foreign Key Handling**: All seeders use `SET CONSTRAINTS ALL DEFERRED` for PostgreSQL
- **UUID Generation**: Uses Laravel's `Str::uuid()` for new records
- **Timestamps**: Uses Laravel's `now()` helper with realistic date offsets
- **Password Hashing**: Uses Laravel's `Hash::make()` (all demo passwords: "password")
- **JSON Fields**: Properly encodes arrays/objects for JSONB columns
- **Soft Deletes**: Respects `deleted_at` columns (all seeded data is active)
- **Multi-language**: Supports Arabic content with RTL direction
- **Realistic Metrics**: Social posts and ads include engagement metrics

## Testing the Data

After seeding, you can verify the data:

```bash
# Check organizations
php artisan tinker
>>> DB::table('cmis.orgs')->count();

# Check users
>>> DB::table('cmis.users')->get(['name', 'email']);

# Check campaigns
>>> DB::table('cmis.campaigns')->get(['name', 'status', 'budget']);

# Check social posts with metrics
>>> DB::table('cmis.social_posts')->get(['caption', 'metrics']);

# Check role permissions
>>> DB::table('cmis.role_permissions')->join('cmis.roles', 'role_permissions.role_id', '=', 'roles.role_id')->count();
```

## Understanding the Application

This demo data helps you understand:

1. **Multi-tenancy**: How multiple organizations share the platform
2. **User Access Control**: Role-based permissions across organizations
3. **Campaign Workflow**: From creation → content planning → creative assets → publishing
4. **Social Media Management**: Integration → account connection → post publishing → scheduling
5. **Ad Platform Integration**: Ad account → campaign → ad set → individual ad hierarchy
6. **Analytics**: Metrics collection for posts, campaigns, and ads
7. **Multi-language Support**: Arabic and English content examples

## Next Steps

After seeding:

1. **Login** with any demo user (password: `password`)
2. **Explore** the different organizations
3. **View** campaigns and their relationships
4. **Check** social media posts and scheduled content
5. **Review** ad campaigns and performance metrics
6. **Test** permission system by logging in as different roles

---

**Note**: This is demo data for development and testing. Do not use in production without reviewing and customizing for your needs.
