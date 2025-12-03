# CMIS Feature Toggle System Design Report
**Date:** 2025-11-20
**Project:** Cognitive Marketing Intelligence Suite (CMIS)
**Scope:** Comprehensive Feature Flag Architecture for Multi-Tenant Laravel Application

---

## Executive Summary

This report presents a **recommended architecture for implementing a feature toggle/feature flag system** in CMIS that integrates seamlessly with the existing multi-tenant PostgreSQL RLS architecture, platform connectors, and AI infrastructure.

### Key Recommendations:

1. **Use Laravel Pennant** (official Laravel feature flag library) as the base framework
2. **Extend with custom CMIS-specific scopes** (Organization, Platform, User, Feature groups)
3. **Store flags in PostgreSQL** with RLS policies for multi-tenant isolation
4. **Implement 4 toggle levels**: System-wide, Organization-level, Platform-specific, User-level
5. **Create admin dashboard** for feature management (minimal MVP)
6. **Phase rollout** with gradual platform and feature enablement

### Expected Benefits:

- Ability to launch with minimal features and gradually enable more
- Per-organization feature customization (different feature sets for different customers)
- Per-platform control (enable/disable Meta, Google, TikTok, etc. independently)
- Zero downtime feature rollout
- A/B testing support for new features
- Integrated with CMIS's multi-tenancy model (RLS policies)

### Implementation Timeline:

- **Phase 1 (Week 1-2):** Core feature flag infrastructure (database, service layer)
- **Phase 2 (Week 2-3):** Integration with platform connectors
- **Phase 3 (Week 3-4):** Admin dashboard and management UI
- **Phase 4 (Week 4+):** Feature-specific toggles and monitoring

---

## 1. Research Findings

### 1.1 Laravel Pennant (Official Solution)

**Why Pennant?**
- Official Laravel package (first-party, maintained by Laravel team)
- Simple, lightweight implementation
- Supports both in-memory and database storage
- Excellent multi-tenancy support through custom scopes
- Built-in event system for tracking feature usage
- Zero external dependencies

**Key Features:**
- **Closure-Based Definitions:** Define features inline with simple closures
- **Class-Based Features:** Create dedicated feature classes with dependency injection
- **Custom Scopes:** Support for any object type (User, Organization, Team, etc.)
- **Scope Customization:** Implement `FeatureScopeable` contract for advanced use cases
- **Scope Identifiers:** Ability to customize how scopes are stored (important for multi-tenancy)
- **Morph Maps:** Decouple stored features from application structure
- **Database Driver:** Persistent storage for feature flag state
- **Caching:** In-request caching for performance

**Multi-Tenancy Support:**
```php
// Example from documentation
Feature::define('billing-v2', function (Team $team) {
    if ($team->created_at->isAfter(new Carbon('1st Jan, 2023'))) {
        return true;
    }
    return Lottery::odds(1 / 100);
});

// Can be checked with:
Feature::for($organization)->active('feature-name')
```

### 1.2 Feature Toggle Patterns (Martin Fowler)

Four main categories of feature toggles:

1. **Release Toggles** (Short-lived: days to weeks)
   - Allow shipping incomplete features to production
   - Static toggling decisions
   - **Use Case in CMIS:** New platform connectors, new AI features
   - **Example:** Toggle Meta Ads connector on/off

2. **Experiment Toggles** (Medium-lived: hours to weeks)
   - A/B testing and multivariate analysis
   - Highly dynamic (per-user decision)
   - **Use Case in CMIS:** Testing new campaign optimization algorithms
   - **Example:** A/B test new budget allocation strategy

3. **Ops Toggles** (Variable duration: some temporary, some permanent)
   - Rapid degradation during production issues
   - "Kill switches" for system stability
   - **Use Case in CMIS:** Disable problematic platform APIs during outages
   - **Example:** Disable TikTok integration if API is unstable

4. **Permission Toggles** (Long-lived)
   - Control access by user groups (premium, beta testers, internal)
   - Per-request dynamic decisions
   - **Use Case in CMIS:** Premium features for higher-tier customers
   - **Example:** Enable AI-powered optimization only for Enterprise tier

### 1.3 PostgreSQL + RLS Integration

CMIS already has robust multi-tenancy via Row-Level Security:
- All data access respects `org_id` via RLS policies
- Context is set via `SetOrgContextMiddleware`
- PostgreSQL enforces isolation at database level (defense in depth)

**Feature toggles must integrate with this:**
- Store feature flag state in RLS-protected tables
- Feature flag access respects org_id context
- System-wide flags bypass org_id filtering
- Per-organization overrides override system defaults

---

## 2. CMIS Codebase Analysis

### 2.1 Current Architecture Insights

**Multi-Tenancy Foundation:**
- 12 PostgreSQL schemas with 197 tables
- RLS policies on all tables
- `SetOrgContextMiddleware` sets org context for each request
- `init_transaction_context(org_id)` available in migrations

**Platform Architecture:**
- `AdPlatformFactory` manages 6 platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- Each platform has dedicated service class: `MetaAdsPlatform`, `GoogleAdsPlatform`, etc.
- Platform connectors in `app/Services/Connectors/Providers/`
- Consistent `AdPlatformInterface` contract

**Service Layer:**
- Repository + Service pattern established
- 100+ service classes across domains
- Services handle business logic, repositories handle data access
- Existing permission system via `Permission` model and `CheckPermission` middleware

**Middleware Stack:**
- `SetOrgContextMiddleware`: Sets organization context
- `CheckPermission`: Validates user permissions
- `ThrottlePlatformRequests`: Rate limits platform APIs
- `ThrottleAI`: Rate limits AI operations
- These can be extended for feature toggle validation

### 2.2 Areas Requiring Feature Toggles

**Platform-Level Toggles** (by platform):
- `platform.meta.enabled` - Enable/disable Meta Ads integration
- `platform.google.enabled` - Enable/disable Google Ads integration
- `platform.tiktok.enabled` - Enable/disable TikTok Ads integration
- `platform.linkedin.enabled` - Enable/disable LinkedIn Ads integration
- `platform.twitter.enabled` - Enable/disable X/Twitter Ads integration
- `platform.snapchat.enabled` - Enable/disable Snapchat Ads integration

**Core Feature Toggles:**
- `campaign.creation.enabled` - Create new campaigns
- `campaign.editing.enabled` - Edit existing campaigns
- `campaign.publishing.enabled` - Publish to platforms
- `campaign.scheduling.enabled` - Schedule future posts
- `campaign.bulk-operations.enabled` - Bulk campaign operations

**AI/Analytics Features:**
- `ai.semantic-search.enabled` - Semantic search functionality
- `ai.auto-optimization.enabled` - AI-powered campaign optimization
- `ai.insights.enabled` - AI-generated insights and recommendations
- `analytics.platform-metrics.enabled` - Platform metrics collection
- `analytics.engagement.enabled` - Engagement analytics

**Advanced Features:**
- `team-management.enabled` - Team collaboration features
- `approval-workflow.enabled` - Approval workflows
- `custom-audiences.enabled` - Custom audience creation
- `budget-optimization.enabled` - Budget optimization algorithms
- `ad-creative-ai.enabled` - AI-generated ad creatives

**Social Media Features:**
- `social.instagram.enabled` - Instagram integration
- `social.facebook.enabled` - Facebook integration
- `social.pinterest.enabled` - Pinterest integration
- `social.linkedin-posts.enabled` - LinkedIn organic posting

---

## 3. Recommended Architecture

### 3.1 High-Level Design

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  (Controllers, Commands, Job classes)                       │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ Feature::active('feature-name')
                 │ Feature::for($organization)->active(...)
                 │
┌────────────────▼────────────────────────────────────────────┐
│            Laravel Pennant (Feature Facade)                  │
│  - Resolves feature status                                  │
│  - Caches decisions in-request                              │
│  - Dispatches events                                        │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ Reads flag values
                 │
┌────────────────▼────────────────────────────────────────────┐
│     CMIS Feature Flag Service Layer                          │
│  - FeatureToggleService (core)                              │
│  - PlatformFeatureService (platform-specific)               │
│  - OrganizationFeatureService (org-level)                   │
│  - UserFeatureService (user-level)                          │
│  - FeatureMetricsService (tracking)                         │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ Resolves scope & inheritance
                 │
┌────────────────▼────────────────────────────────────────────┐
│        Feature Flag Repository Layer                         │
│  - Reads from cmis.feature_flags table                       │
│  - Reads from cmis.feature_flag_overrides table              │
│  - Respects RLS policies                                    │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ RLS policies enforce access
                 │
┌────────────────▼────────────────────────────────────────────┐
│       PostgreSQL Database (RLS Protected)                    │
│  - cmis.feature_flags (system-wide and org-level)           │
│  - cmis.feature_flag_overrides (user/platform overrides)    │
│  - cmis.feature_flag_values (audit trail)                   │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Toggle Hierarchy & Resolution

Feature toggle values resolve in this order (cascading):

```
User Override (Highest Priority)
    ↑
Platform Override (for platform-specific features)
    ↑
Organization Override (org-level customization)
    ↑
System Default (lowest priority)
```

**Example Resolution Path:**

```
Feature: 'platform.meta.enabled' for User in Organization
1. Check: user_feature_flag_overrides WHERE user_id = ? AND feature = ?
2. Check: platform_feature_flag_overrides WHERE org_id = ? AND platform = ? AND feature = ?
3. Check: organization_feature_flag_overrides WHERE org_id = ? AND feature = ?
4. Check: feature_flags (system default) WHERE feature = ? AND scope = 'system'
5. Return: First match found, or false if none match
```

### 3.3 Scope Architecture

Four levels of scoping for CMIS:

**1. System Scope (Global)**
- Applies to all organizations and users
- Managed by system administrators only
- Examples: Platform enablement, major features

**2. Organization Scope**
- Applies to all users in an organization
- Set per-customer (SaaS multi-tenant)
- Examples: Feature availability tier, pilot features

**3. Platform Scope**
- Applies to specific platforms within an organization
- Examples: Enable/disable Meta for Org A, but keep Google enabled

**4. User Scope (Optional)**
- Individual user overrides
- Examples: Beta tester access, internal staff previews
- Can also use probabilistic toggles (Lottery::odds())

---

## 4. Database Schema Design

### 4.1 Core Tables

All tables follow CMIS conventions:
- Schema-qualified names (`cmis.table_name`)
- UUID primary keys
- RLS policies for multi-tenancy
- Audit logging via triggers

#### Table 1: `cmis.feature_flags`
System-wide and organization-level feature flag definitions.

```sql
-- Create feature_flags table with RLS
CREATE TABLE cmis.feature_flags (
    -- Primary Key
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Organization context (NULL = system-wide)
    org_id UUID REFERENCES cmis.organizations(id) ON DELETE CASCADE,

    -- Feature metadata
    feature_name VARCHAR(255) NOT NULL,
    feature_category VARCHAR(100), -- 'platform', 'campaign', 'ai', 'analytics', 'social'
    display_name VARCHAR(255),
    description TEXT,

    -- Flag state and behavior
    is_enabled BOOLEAN NOT NULL DEFAULT false,
    toggle_type VARCHAR(50), -- 'release', 'ops', 'experiment', 'permission'

    -- Rollout strategy
    rollout_percentage INTEGER DEFAULT 100 CONSTRAINT valid_percentage CHECK (rollout_percentage >= 0 AND rollout_percentage <= 100),
    rollout_hash_algorithm VARCHAR(50), -- 'user_id', 'org_id', 'random'

    -- Lifecycle
    activated_at TIMESTAMP WITH TIME ZONE,
    deactivated_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE,

    -- Audit
    created_by UUID REFERENCES cmis.users(id),
    updated_by UUID REFERENCES cmis.users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE,

    -- Unique constraint on feature per organization
    CONSTRAINT unique_feature_per_org UNIQUE NULLS NOT DISTINCT (org_id, feature_name)
);

-- Enable RLS
ALTER TABLE cmis.feature_flags ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Users can view features for their org or system features
CREATE POLICY feature_flags_org_isolation ON cmis.feature_flags
    USING (
        org_id IS NULL -- System features visible to all
        OR org_id = current_setting('app.current_org_id')::uuid -- Or their org's features
    );

-- RLS Policy: Only admins can modify (enforced via permissions middleware)
CREATE POLICY feature_flags_org_modify ON cmis.feature_flags
    FOR UPDATE USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes for performance
CREATE INDEX idx_feature_flags_org_id ON cmis.feature_flags(org_id);
CREATE INDEX idx_feature_flags_name ON cmis.feature_flags(feature_name);
CREATE INDEX idx_feature_flags_org_name ON cmis.feature_flags(org_id, feature_name);
CREATE INDEX idx_feature_flags_category ON cmis.feature_flags(feature_category);
CREATE INDEX idx_feature_flags_active ON cmis.feature_flags(is_enabled, activated_at, deactivated_at);
```

#### Table 2: `cmis.feature_flag_overrides`
User-level and platform-specific overrides.

```sql
CREATE TABLE cmis.feature_flag_overrides (
    -- Primary Key
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Context
    org_id UUID NOT NULL REFERENCES cmis.organizations(id) ON DELETE CASCADE,
    feature_flag_id UUID NOT NULL REFERENCES cmis.feature_flags(id) ON DELETE CASCADE,

    -- Override target (one must be set)
    user_id UUID REFERENCES cmis.users(id) ON DELETE CASCADE,
    platform VARCHAR(50), -- 'meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'

    -- Override value
    is_enabled BOOLEAN NOT NULL,
    reason VARCHAR(255), -- 'beta_tester', 'paid_plan', 'pilot', 'temp_disable', etc.

    -- Lifecycle
    activated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP WITH TIME ZONE,

    -- Audit
    created_by UUID REFERENCES cmis.users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP WITH TIME ZONE,

    -- Constraint: Either user_id or platform must be set
    CONSTRAINT override_target_required CHECK (
        (user_id IS NOT NULL AND platform IS NULL)
        OR (user_id IS NULL AND platform IS NOT NULL)
    )
);

-- Enable RLS
ALTER TABLE cmis.feature_flag_overrides ENABLE ROW LEVEL SECURITY;

-- RLS Policy: Org isolation
CREATE POLICY feature_flag_overrides_org_isolation ON cmis.feature_flag_overrides
    USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes
CREATE INDEX idx_feature_flag_overrides_org ON cmis.feature_flag_overrides(org_id);
CREATE INDEX idx_feature_flag_overrides_flag ON cmis.feature_flag_overrides(feature_flag_id);
CREATE INDEX idx_feature_flag_overrides_user ON cmis.feature_flag_overrides(user_id);
CREATE INDEX idx_feature_flag_overrides_platform ON cmis.feature_flag_overrides(platform);
CREATE INDEX idx_feature_flag_overrides_active ON cmis.feature_flag_overrides(activated_at, expires_at);
```

#### Table 3: `cmis.feature_flag_values`
Audit trail of feature flag value changes (for tracking).

```sql
CREATE TABLE cmis.feature_flag_values (
    -- Primary Key
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Context
    org_id UUID REFERENCES cmis.organizations(id) ON DELETE CASCADE,
    feature_flag_id UUID NOT NULL REFERENCES cmis.feature_flags(id) ON DELETE CASCADE,

    -- What changed
    previous_value BOOLEAN,
    new_value BOOLEAN NOT NULL,
    previous_percentage INTEGER,
    new_percentage INTEGER,

    -- Why it changed
    change_reason VARCHAR(255),
    changed_by UUID REFERENCES cmis.users(id),

    -- Timestamp
    changed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Enable RLS (read-only for users)
ALTER TABLE cmis.feature_flag_values ENABLE ROW LEVEL SECURITY;

CREATE POLICY feature_flag_values_org_isolation ON cmis.feature_flag_values
    USING (org_id = current_setting('app.current_org_id')::uuid);

-- Indexes for audit trails
CREATE INDEX idx_feature_flag_values_flag ON cmis.feature_flag_values(feature_flag_id);
CREATE INDEX idx_feature_flag_values_org ON cmis.feature_flag_values(org_id);
CREATE INDEX idx_feature_flag_values_timestamp ON cmis.feature_flag_values(changed_at);
```

### 4.2 Migration File

```php
// database/migrations/2025_11_21_000000_create_feature_flags_tables.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create feature_flags table
        Schema::create('cmis.feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->nullable()->references('id')->on('cmis.organizations')->cascadeOnDelete();

            $table->string('feature_name', 255);
            $table->string('feature_category', 100)->nullable();
            $table->string('display_name', 255)->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_enabled')->default(false);
            $table->string('toggle_type', 50)->default('release'); // release, ops, experiment, permission

            $table->integer('rollout_percentage')->default(100);
            $table->string('rollout_hash_algorithm', 50)->nullable();

            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreignUuid('created_by')->nullable()->references('id')->on('cmis.users');
            $table->foreignUuid('updated_by')->nullable()->references('id')->on('cmis.users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['org_id', 'feature_name'], 'unique_feature_per_org');
            $table->index('org_id');
            $table->index('feature_name');
            $table->index(['org_id', 'feature_name']);
            $table->index('feature_category');
            $table->index(['is_enabled', 'activated_at', 'deactivated_at']);
        });

        // Create feature_flag_overrides table
        Schema::create('cmis.feature_flag_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->references('id')->on('cmis.organizations')->cascadeOnDelete();
            $table->foreignUuid('feature_flag_id')->references('id')->on('cmis.feature_flags')->cascadeOnDelete();

            $table->foreignUuid('user_id')->nullable()->references('id')->on('cmis.users')->cascadeOnDelete();
            $table->string('platform', 50)->nullable();

            $table->boolean('is_enabled');
            $table->string('reason', 255)->nullable();

            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->foreignUuid('created_by')->nullable()->references('id')->on('cmis.users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('org_id');
            $table->index('feature_flag_id');
            $table->index('user_id');
            $table->index('platform');
            $table->index(['activated_at', 'expires_at']);
        });

        // Create feature_flag_values table
        Schema::create('cmis.feature_flag_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('org_id')->nullable()->references('id')->on('cmis.organizations')->cascadeOnDelete();
            $table->foreignUuid('feature_flag_id')->references('id')->on('cmis.feature_flags')->cascadeOnDelete();

            $table->boolean('previous_value')->nullable();
            $table->boolean('new_value');
            $table->integer('previous_percentage')->nullable();
            $table->integer('new_percentage')->nullable();

            $table->string('change_reason', 255)->nullable();
            $table->foreignUuid('changed_by')->nullable()->references('id')->on('cmis.users');

            $table->timestamp('changed_at')->useCurrent();

            $table->index('feature_flag_id');
            $table->index('org_id');
            $table->index('changed_at');
        });

        // Enable RLS
        DB::statement("ALTER TABLE cmis.feature_flags ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE cmis.feature_flag_overrides ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE cmis.feature_flag_values ENABLE ROW LEVEL SECURITY");

        // Create RLS policies for feature_flags
        DB::statement("
            CREATE POLICY feature_flags_org_isolation ON cmis.feature_flags
            USING (
                org_id IS NULL
                OR org_id = current_setting('app.current_org_id')::uuid
            )
        ");

        DB::statement("
            CREATE POLICY feature_flags_org_modify ON cmis.feature_flags
            FOR UPDATE USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create RLS policies for feature_flag_overrides
        DB::statement("
            CREATE POLICY feature_flag_overrides_org_isolation ON cmis.feature_flag_overrides
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");

        // Create RLS policies for feature_flag_values
        DB::statement("
            CREATE POLICY feature_flag_values_org_isolation ON cmis.feature_flag_values
            USING (org_id = current_setting('app.current_org_id')::uuid)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cmis.feature_flag_values');
        Schema::dropIfExists('cmis.feature_flag_overrides');
        Schema::dropIfExists('cmis.feature_flags');
    }
};
```

---

## 5. Service Layer Implementation

### 5.1 Core Feature Toggle Service

```php
// app/Services/FeatureToggle/FeatureToggleService.php

<?php

namespace App\Services\FeatureToggle;

use App\Models\Core\Organization;
use App\Models\Core\User;
use App\Models\FeatureFlag;
use App\Models\FeatureFlag\FeatureFlag as FeatureFlagModel;
use App\Models\FeatureFlag\FeatureFlagOverride;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Core feature toggle service for CMIS
 * Handles resolution of feature flags with multi-level hierarchy
 */
class FeatureToggleService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'feature_toggle:';

    /**
     * Check if a feature is active for current organization
     */
    public function isActive(string $featureName, ?Organization $organization = null): bool
    {
        $organization = $organization ?? auth()->user()->organization;
        return $this->resolveFeature($featureName, $organization, null, null);
    }

    /**
     * Check if a feature is active for a specific user
     */
    public function isActiveForUser(string $featureName, User $user): bool
    {
        return $this->resolveFeature($featureName, $user->organization, $user, null);
    }

    /**
     * Check if a feature is active for a specific platform
     */
    public function isActiveForPlatform(string $featureName, string $platform, ?Organization $organization = null): bool
    {
        $organization = $organization ?? auth()->user()->organization;
        return $this->resolveFeature($featureName, $organization, null, $platform);
    }

    /**
     * Resolve feature flag with cascading logic
     *
     * Resolution order:
     * 1. User override (if user provided)
     * 2. Platform override (if platform provided)
     * 3. Organization override
     * 4. System default
     */
    private function resolveFeature(
        string $featureName,
        Organization $organization,
        ?User $user = null,
        ?string $platform = null
    ): bool {
        $cacheKey = $this->getCacheKey($featureName, $organization->id, $user?->id, $platform);

        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 1. Check user override
        if ($user) {
            $userOverride = $this->getUserOverride($featureName, $user, $organization);
            if ($userOverride !== null) {
                Cache::put($cacheKey, $userOverride, self::CACHE_TTL);
                return $userOverride;
            }
        }

        // 2. Check platform override
        if ($platform) {
            $platformOverride = $this->getPlatformOverride($featureName, $platform, $organization);
            if ($platformOverride !== null) {
                Cache::put($cacheKey, $platformOverride, self::CACHE_TTL);
                return $platformOverride;
            }
        }

        // 3. Check organization override
        $orgOverride = $this->getOrganizationOverride($featureName, $organization);
        if ($orgOverride !== null) {
            Cache::put($cacheKey, $orgOverride, self::CACHE_TTL);
            return $orgOverride;
        }

        // 4. Check system default
        $systemDefault = $this->getSystemDefault($featureName);
        Cache::put($cacheKey, $systemDefault, self::CACHE_TTL);

        return $systemDefault;
    }

    /**
     * Get user-level override
     */
    private function getUserOverride(string $featureName, User $user, Organization $organization): ?bool
    {
        $override = FeatureFlagOverride::query()
            ->whereHas('featureFlag', function ($query) use ($featureName) {
                $query->where('feature_name', $featureName);
            })
            ->where('org_id', $organization->id)
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $override?->is_enabled;
    }

    /**
     * Get platform-specific override
     */
    private function getPlatformOverride(string $featureName, string $platform, Organization $organization): ?bool
    {
        $override = FeatureFlagOverride::query()
            ->whereHas('featureFlag', function ($query) use ($featureName) {
                $query->where('feature_name', $featureName);
            })
            ->where('org_id', $organization->id)
            ->where('platform', $platform)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $override?->is_enabled;
    }

    /**
     * Get organization-level override
     */
    private function getOrganizationOverride(string $featureName, Organization $organization): ?bool
    {
        $flag = FeatureFlagModel::query()
            ->where('feature_name', $featureName)
            ->where('org_id', $organization->id)
            ->where('is_enabled', true)
            ->where(function ($query) {
                $query->whereNull('deactivated_at')
                    ->orWhere('deactivated_at', '>', now());
            })
            ->first();

        if ($flag && $flag->rollout_percentage < 100) {
            return $this->shouldRollout($flag, $organization->id);
        }

        return $flag?->is_enabled;
    }

    /**
     * Get system default flag value
     */
    private function getSystemDefault(string $featureName): bool
    {
        $flag = FeatureFlagModel::query()
            ->where('feature_name', $featureName)
            ->whereNull('org_id')
            ->where('is_enabled', true)
            ->where(function ($query) {
                $query->whereNull('deactivated_at')
                    ->orWhere('deactivated_at', '>', now());
            })
            ->first();

        if ($flag && $flag->rollout_percentage < 100) {
            return $this->shouldRollout($flag);
        }

        return $flag?->is_enabled ?? false;
    }

    /**
     * Determine if feature should be rolled out based on percentage
     */
    private function shouldRollout(FeatureFlagModel $flag, ?string $hashValue = null): bool
    {
        $hashValue = $hashValue ?? uniqid();
        $hash = crc32($hashValue) % 100;
        return $hash < $flag->rollout_percentage;
    }

    /**
     * Invalidate cache for a feature
     */
    public function invalidateCache(string $featureName, ?string $organizationId = null): void
    {
        if ($organizationId) {
            Cache::forget(self::CACHE_PREFIX . "{$featureName}:{$organizationId}:*");
        } else {
            // Invalidate all
            Cache::flush();
        }
    }

    /**
     * Generate cache key
     */
    private function getCacheKey(
        string $featureName,
        string $organizationId,
        ?string $userId = null,
        ?string $platform = null
    ): string {
        $parts = [self::CACHE_PREFIX, $featureName, $organizationId];
        if ($userId) {
            $parts[] = "user:{$userId}";
        }
        if ($platform) {
            $parts[] = "platform:{$platform}";
        }
        return implode(':', $parts);
    }

    /**
     * Enable a feature
     */
    public function enableFeature(string $featureName, ?Organization $organization = null): void
    {
        $flag = $this->getOrCreateFlag($featureName, $organization);
        $flag->update([
            'is_enabled' => true,
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
        $this->recordChange($flag, true);
        $this->invalidateCache($featureName, $organization?->id);
    }

    /**
     * Disable a feature
     */
    public function disableFeature(string $featureName, ?Organization $organization = null): void
    {
        $flag = $this->getOrCreateFlag($featureName, $organization);
        $flag->update([
            'is_enabled' => false,
            'deactivated_at' => now(),
        ]);
        $this->recordChange($flag, false);
        $this->invalidateCache($featureName, $organization?->id);
    }

    /**
     * Get or create feature flag
     */
    private function getOrCreateFlag(string $featureName, ?Organization $organization): FeatureFlagModel
    {
        return FeatureFlagModel::firstOrCreate(
            [
                'feature_name' => $featureName,
                'org_id' => $organization?->id,
            ],
            [
                'is_enabled' => false,
                'created_by' => auth()->user()?->id,
            ]
        );
    }

    /**
     * Record feature flag change in audit trail
     */
    private function recordChange(FeatureFlagModel $flag, bool $newValue): void
    {
        // Log to feature_flag_values for audit trail
        DB::table('cmis.feature_flag_values')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $flag->org_id,
            'feature_flag_id' => $flag->id,
            'previous_value' => $flag->getOriginal('is_enabled'),
            'new_value' => $newValue,
            'change_reason' => 'Manual update',
            'changed_by' => auth()->user()?->id,
            'changed_at' => now(),
        ]);
    }
}
```

### 5.2 Platform Feature Service

```php
// app/Services/FeatureToggle/PlatformFeatureService.php

<?php

namespace App\Services\FeatureToggle;

use App\Models\Core\Organization;
use App\Models\Core\User;

/**
 * Platform-specific feature toggle service
 * Handles platform enablement/disablement
 */
class PlatformFeatureService
{
    public function __construct(
        private FeatureToggleService $featureToggleService
    ) {}

    /**
     * Check if a platform is enabled for an organization
     */
    public function isPlatformEnabled(string $platform, Organization $organization): bool
    {
        $featureName = "platform.{$platform}.enabled";
        return $this->featureToggleService->isActiveForPlatform($featureName, $platform, $organization);
    }

    /**
     * Check if platform is enabled for a user
     */
    public function isPlatformEnabledForUser(string $platform, User $user): bool
    {
        $featureName = "platform.{$platform}.enabled";
        return $this->featureToggleService->isActiveForUser($featureName, $user);
    }

    /**
     * Get list of enabled platforms for organization
     */
    public function getEnabledPlatforms(Organization $organization): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        return array_filter($platforms, fn($p) => $this->isPlatformEnabled($p, $organization));
    }

    /**
     * Enable platform for organization
     */
    public function enablePlatform(string $platform, Organization $organization): void
    {
        $featureName = "platform.{$platform}.enabled";
        $this->featureToggleService->enableFeature($featureName, $organization);
    }

    /**
     * Disable platform for organization
     */
    public function disablePlatform(string $platform, Organization $organization): void
    {
        $featureName = "platform.{$platform}.enabled";
        $this->featureToggleService->disableFeature($featureName, $organization);
    }

    /**
     * Override platform for specific user (beta access)
     */
    public function overridePlatformForUser(string $platform, User $user, bool $enabled): void
    {
        // Create override record
        // Implementation details...
    }
}
```

---

## 6. Integration Points

### 6.1 Middleware Integration

Create middleware to prevent access to disabled features:

```php
// app/Http/Middleware/CheckFeatureAccess.php

<?php

namespace App\Http\Middleware;

use App\Services\FeatureToggle\FeatureToggleService;
use Closure;
use Illuminate\Http\Request;

class CheckFeatureAccess
{
    public function __construct(private FeatureToggleService $featureToggleService) {}

    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        if (!$this->featureToggleService->isActive($feature)) {
            abort(403, "Feature '{$feature}' is not available.");
        }

        return $next($request);
    }
}
```

Usage in routes:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'feature:campaign.creation.enabled'])
    ->post('/campaigns', [CampaignController::class, 'store']);
```

### 6.2 Service Integration

Example in `AdPlatformFactory`:

```php
public static function make(Integration $integration): AdPlatformInterface
{
    $featureToggleService = app(FeatureToggleService::class);

    // Check if platform is enabled
    if (!$featureToggleService->isActiveForPlatform(
        "platform.{$integration->platform}.enabled",
        $integration->platform
    )) {
        throw new \Exception("Platform {$integration->platform} is not enabled.");
    }

    return match ($integration->platform) {
        'meta' => new MetaAdsPlatform($integration),
        // ... other platforms
    };
}
```

### 6.3 Controller Integration

```php
// Example in CampaignController
public function index(FeatureToggleService $featureToggleService)
{
    if (!$featureToggleService->isActive('campaign.editing.enabled')) {
        return response()->json(['message' => 'Feature not available'], 403);
    }

    // ... rest of logic
}
```

---

## 7. Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
**Deliverables:** Core infrastructure, database, service layer

Tasks:
1. Create migration for feature flag tables
2. Implement `FeatureToggleService` core class
3. Create `FeatureFlagModel` and `FeatureFlagOverride` models
4. Create `PlatformFeatureService`
5. Create `CheckFeatureAccess` middleware
6. Write unit tests for service layer
7. Document API and configuration

**Estimated Effort:** 20-30 hours

### Phase 2: Platform Integration (Week 2-3)
**Deliverables:** Platform-level feature toggles working

Tasks:
1. Update `AdPlatformFactory` to check feature toggles
2. Add platform feature flags to system seeder
3. Update platform connectors to respect toggles
4. Update OAuth flow to check platform enablement
5. Update webhook handlers for disabled platforms
6. Write integration tests

**Estimated Effort:** 15-20 hours

### Phase 3: Admin Dashboard (Week 3-4)
**Deliverables:** UI for managing feature toggles

Tasks:
1. Create feature flag management controllers
2. Build admin dashboard (Blade/Alpine.js)
3. Add flag creation/editing/deletion endpoints
4. Add organization-level flag overrides UI
5. Add user override management
6. Create audit log viewer
7. Add feature flag status dashboard

**Estimated Effort:** 20-25 hours

### Phase 4: Advanced Features (Week 4+)
**Deliverables:** A/B testing, percentage rollout, scheduling

Tasks:
1. Implement gradual rollout (percentage-based)
2. Add A/B testing framework
3. Add scheduled feature enablement/disablement
4. Add feature usage metrics collection
5. Add notifications for flag changes
6. Create feature flag report/analytics
7. Add Slack integration for flag changes

**Estimated Effort:** 15-20 hours

### Total Timeline: 4-6 weeks

---

## 8. Security Considerations

### 8.1 Access Control

- Only users with `feature_flag.manage` permission can modify flags
- System-wide flags require elevated permissions
- Organization admins can manage org-level flags
- Users can't modify their own overrides (prevent privilege escalation)

### 8.2 RLS Protection

- All feature flag queries respect RLS policies
- Organization data isolation enforced at database level
- User cannot see flags for other organizations

### 8.3 Sensitive Features

Features like platform toggles must require:
- 2FA authentication for modification
- Audit logging of all changes
- Notification to organization admins
- 24-hour change approval window (optional)

### 8.4 Data Validation

- Feature names must be lowercase with dots: `platform.meta.enabled`
- Rollout percentage must be 0-100
- Platform names must be in allowed list
- Dates must be valid and in future

---

## 9. Performance Implications

### 9.1 Caching Strategy

- **In-request caching:** Feature decisions cached per request (< 1ms)
- **Redis caching:** 1-hour TTL for feature flag values
- **Database caching:** Lazy-loaded on first access
- **Cache invalidation:** Automatic on flag change

**Performance impact:** < 5ms per feature check with caching

### 9.2 Database Performance

- **Indexes:** Created on all frequently queried columns
- **Queries:** Optimized to use indexed lookups
- **Soft deletes:** No hard deletes (audit trail preserved)
- **N+1 prevention:** Use eager loading for related overrides

**Impact:** Single flag check = 1 DB query (cached on repeat)

### 9.3 Optimization Tips

1. Cache feature decisions in user session
2. Use `Cache::remember()` for frequently checked flags
3. Batch-load flags for multiple platforms
4. Use Redis for distributed caching

---

## 10. Feature Toggle Catalog (Initial Setup)

### Platform Features
```
platform.meta.enabled           - Meta Ads (Facebook/Instagram)
platform.google.enabled         - Google Ads
platform.tiktok.enabled         - TikTok Ads
platform.linkedin.enabled       - LinkedIn Ads
platform.twitter.enabled        - X/Twitter Ads
platform.snapchat.enabled       - Snapchat Ads
```

### Campaign Features
```
campaign.creation.enabled       - Create new campaigns
campaign.editing.enabled        - Edit existing campaigns
campaign.publishing.enabled     - Publish to platforms
campaign.scheduling.enabled     - Schedule posts
campaign.bulk-operations.enabled - Bulk actions
campaign.duplication.enabled    - Clone campaigns
campaign.templates.enabled      - Use saved templates
```

### AI & Analytics
```
ai.semantic-search.enabled      - Semantic search
ai.auto-optimization.enabled    - Campaign optimization
ai.insights.enabled             - AI insights
ai.best-time-analysis.enabled   - Best time analysis
ai.audience-expansion.enabled   - Audience expansion
analytics.platform-metrics.enabled
analytics.engagement.enabled
analytics.roi-tracking.enabled
analytics.predictions.enabled
```

### Social Features
```
social.instagram.enabled        - Instagram sync
social.facebook.enabled         - Facebook sync
social.pinterest.enabled        - Pinterest sync
social.linkedin-posts.enabled   - LinkedIn organic posts
social.comments.enabled         - Comments management
social.dms.enabled              - Direct messages
```

### Advanced Features
```
team-management.enabled         - Team collaboration
approval-workflow.enabled       - Approval workflows
custom-audiences.enabled        - Custom audience creation
budget-optimization.enabled     - Budget optimization
ad-creative-ai.enabled          - AI ad creative generation
content-calendar.enabled        - Content calendar
compliance-checks.enabled       - Compliance validation
```

---

## 11. Testing Strategy

### 11.1 Unit Tests

```php
// tests/Unit/FeatureToggle/FeatureToggleServiceTest.php

<?php

namespace Tests\Unit\FeatureToggle;

use App\Models\Core\Organization;
use App\Models\Core\User;
use App\Services\FeatureToggle\FeatureToggleService;
use Tests\TestCase;

class FeatureToggleServiceTest extends TestCase
{
    private FeatureToggleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureToggleService::class);
    }

    /** @test */
    public function it_returns_false_for_disabled_feature()
    {
        $org = Organization::factory()->create();
        $result = $this->service->isActive('non-existent-feature', $org);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_respects_organization_level_flags()
    {
        $org = Organization::factory()->create();
        $this->service->enableFeature('test.feature', $org);

        $result = $this->service->isActive('test.feature', $org);
        $this->assertTrue($result);
    }

    /** @test */
    public function it_respects_user_overrides()
    {
        $user = User::factory()->create();
        // Create override logic...

        $result = $this->service->isActiveForUser('test.feature', $user);
        $this->assertTrue($result);
    }

    // ... more tests
}
```

### 11.2 Integration Tests

```php
// tests/Feature/FeatureToggle/PlatformToggleTest.php

class PlatformToggleTest extends TestCase
{
    /** @test */
    public function disabled_platform_cannot_create_integration()
    {
        $org = Organization::factory()->create();
        $this->disablePlatform('meta', $org);

        $response = $this->actingAs($this->user)
            ->postJson('/api/integrations', [
                'platform' => 'meta',
                'org_id' => $org->id,
            ]);

        $response->assertStatus(403);
    }
}
```

---

## 12. Monitoring & Observability

### 12.1 Metrics to Track

- Feature toggle activation rate
- Time since last flag change
- Number of users affected by each flag
- Feature flag check latency (p50, p95, p99)
- Cache hit rate

### 12.2 Logging

Log all flag state changes:

```php
Log::info('Feature flag updated', [
    'feature' => 'platform.meta.enabled',
    'org_id' => $organization->id,
    'previous_value' => false,
    'new_value' => true,
    'changed_by' => auth()->user()->id,
    'timestamp' => now(),
]);
```

### 12.3 Alerts

Create alerts for:
- Critical platform disabled
- Unexpected flag value changes
- Cache failures
- High number of flag checks

---

## 13. Comparison: Laravel Pennant vs Custom Implementation

| Aspect | Laravel Pennant | Custom CMIS System |
|--------|-----------------|-------------------|
| **Setup Time** | 1-2 hours | 1-2 weeks (full system) |
| **Multi-tenancy** | Good (via custom scopes) | Excellent (RLS-integrated) |
| **Database** | Requires Pennant migration | Direct CMIS integration |
| **Customization** | Limited | Full control |
| **Performance** | Good | Optimized for CMIS |
| **Learning Curve** | Low | Medium |
| **Community Support** | Excellent | Internal only |
| **Audit Trail** | Via events | Native tables |
| **A/B Testing** | Built-in | Custom implementation |
| **Platform-specific** | Via custom scopes | Native support |
| **Admin UI** | No (external tools) | Can build custom |

### Recommendation: Hybrid Approach

Use **Laravel Pennant as foundation** with **CMIS-specific extensions**:

```php
// Leverage Pennant's core functionality
Feature::define('platform.meta.enabled', function (Organization $org) {
    return $cmisFeatureService->isPlatformEnabled('meta', $org);
});

// Define custom features
Feature::define('campaign.creation', function (User $user) {
    return $cmisFeatureService->isActiveForUser('campaign.creation.enabled', $user);
});
```

This gives you:
- Pennant's proven, well-tested foundation
- CMIS's deep multi-tenancy integration
- Custom admin UI and audit trail
- Best of both worlds

---

## 14. Migration Path (Existing CMIS Installation)

### Step 1: Database Migration
```bash
php artisan migrate
# Runs create_feature_flags_tables migration
```

### Step 2: Bootstrap System Features
```bash
php artisan cmis:bootstrap-features
# Creates system-wide feature flags
# Sets all platforms to DISABLED by default
# Enables core campaign features
```

### Step 3: Enable Per Organization
```bash
php artisan cmis:enable-feature platform.meta.enabled --org=<org-id>
# Enables Meta for specific organization
```

### Step 4: Gradual Rollout
```bash
php artisan cmis:rollout-feature platform.google.enabled --percentage=50
# Enables Google Ads for 50% of organizations (random hash-based)
```

---

## 15. Example Usage Scenarios

### Scenario 1: Gradual Platform Rollout
```php
// Initially, all platforms disabled
Feature::define('platform.tiktok.enabled', fn(Org $org) => false);

// Week 1: Enable for internal team
$this->service->overridePlatformForUser('tiktok', $internalUser, true);

// Week 2: Enable for 25% of customers (random)
$this->service->enablePlatform('tiktok', percentage: 25);

// Week 3: Enable for 50%
$this->service->enablePlatform('tiktok', percentage: 50);

// Week 4: Enable for all
$this->service->enablePlatform('tiktok', percentage: 100);
```

### Scenario 2: Beta Testing Feature
```php
// New AI feature in beta for premium customers
Feature::define('ai.auto-optimization', function (User $user) {
    return $user->organization->plan === 'enterprise';
});

// Add specific beta testers
$this->service->overridePlatformForUser(
    'ai.auto-optimization',
    $betaTester,
    true
);
```

### Scenario 3: Emergency Disable
```php
// If Google Ads API is down
$this->service->disablePlatform('google');

// Notify users
Notification::send($users, new PlatformUnavailable('google'));

// Re-enable when stable
$this->service->enablePlatform('google');
```

---

## 16. Conclusion

The proposed **CMIS Feature Toggle System** provides:

1. **Multi-level control:** System, Organization, Platform, User levels
2. **Deep RLS integration:** Leverages CMIS's existing security model
3. **Gradual rollout:** Percentage-based and time-based enablement
4. **Audit trail:** Full change history and compliance logging
5. **Performance:** Cached decisions with minimal overhead
6. **Flexibility:** Supports release, ops, experiment, and permission toggle patterns

**Next Steps:**
1. Review and approve architecture
2. Create implementation task list
3. Begin Phase 1 database migration
4. Schedule service layer development
5. Plan admin dashboard iteration

---

## Appendix A: Configuration File Template

```php
// config/features.php

return [
    'driver' => env('FEATURE_DRIVER', 'database'),

    'cache_ttl' => 3600, // 1 hour

    'platforms' => [
        'meta' => ['label' => 'Meta Ads'],
        'google' => ['label' => 'Google Ads'],
        'tiktok' => ['label' => 'TikTok Ads'],
        'linkedin' => ['label' => 'LinkedIn Ads'],
        'twitter' => ['label' => 'X/Twitter Ads'],
        'snapchat' => ['label' => 'Snapchat Ads'],
    ],

    'categories' => [
        'platform' => 'Platform Integrations',
        'campaign' => 'Campaign Management',
        'ai' => 'AI & Automation',
        'analytics' => 'Analytics & Reporting',
        'social' => 'Social Media',
        'advanced' => 'Advanced Features',
    ],

    'defaults' => [
        'platform.meta.enabled' => false,
        'platform.google.enabled' => false,
        'platform.tiktok.enabled' => false,
        'platform.linkedin.enabled' => false,
        'platform.twitter.enabled' => false,
        'platform.snapchat.enabled' => false,

        'campaign.creation.enabled' => true,
        'campaign.editing.enabled' => true,
        'campaign.publishing.enabled' => true,
        'campaign.scheduling.enabled' => true,

        'ai.semantic-search.enabled' => false,
        'ai.auto-optimization.enabled' => false,
        'ai.insights.enabled' => false,
    ],
];
```

---

**Report Generated:** 2025-11-20
**Status:** Ready for Implementation
**Next Review:** Post-Phase 1 Completion
