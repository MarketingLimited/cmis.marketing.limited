# CMIS Feature Toggle System - Quick Start Guide

**Date:** 2025-11-20
**Status:** Ready to Implement
**Complexity:** Medium
**Estimated Time:** 4-6 weeks (all phases)

---

## TL;DR - Executive Summary

Implement a **multi-level feature toggle system** using:
1. Laravel Pennant (base framework)
2. Custom CMIS service layer (for RLS integration)
3. PostgreSQL tables (audit trail and overrides)
4. Admin dashboard (flag management)

**Core benefit:** Launch with minimal features, enable gradually per-organization and per-platform.

---

## Quick Decision Matrix

### Should We Use Laravel Pennant?

| Question | Yes/No | Notes |
|----------|--------|-------|
| Need multi-tenancy support? | Yes | CMIS uses RLS |
| Need audit trail? | Yes | Required for compliance |
| Need platform-specific toggles? | Yes | 6 platforms to control |
| Need A/B testing? | Maybe | Optional, Phase 4 |
| Need admin UI? | Yes | Custom built |
| Have time for custom extensions? | Yes | 4-6 weeks available |

**Verdict:** YES - Use Pennant with CMIS extensions

---

## Implementation Checklist

### Phase 1: Foundation (Week 1-2)
- [ ] Review and approve architecture design
- [ ] Create migration files
- [ ] Implement `FeatureToggleService`
- [ ] Create models (`FeatureFlag`, `FeatureFlagOverride`)
- [ ] Create service classes
- [ ] Write unit tests
- [ ] Document API

**Effort:** 20-30 hours
**Deliverable:** Working feature flag system (no UI yet)

### Phase 2: Integration (Week 2-3)
- [ ] Update `AdPlatformFactory`
- [ ] Add platform feature checks
- [ ] Update connectors
- [ ] Add to seeder
- [ ] Write integration tests
- [ ] Update documentation

**Effort:** 15-20 hours
**Deliverable:** Platform toggles working in code

### Phase 3: Admin UI (Week 3-4)
- [ ] Create management controllers
- [ ] Build feature list page
- [ ] Build flag editor
- [ ] Build org override UI
- [ ] Build audit log viewer
- [ ] Add status dashboard

**Effort:** 20-25 hours
**Deliverable:** Admin dashboard for managing flags

### Phase 4: Polish (Week 4+)
- [ ] Gradual rollout (percentage)
- [ ] Scheduled enablement
- [ ] Metrics collection
- [ ] Slack integration
- [ ] Advanced analytics

**Effort:** 15-20 hours
**Deliverable:** Production-ready system

---

## Five-Minute Setup Overview

### 1. Database Schema
Create 3 tables:
- `cmis.feature_flags` - System and org-level flags
- `cmis.feature_flag_overrides` - User and platform overrides
- `cmis.feature_flag_values` - Audit trail

### 2. Service Layer
Create 2 main services:
- `FeatureToggleService` - Core logic
- `PlatformFeatureService` - Platform-specific helpers

### 3. Models
Create 2 models:
- `FeatureFlag` - Maps to feature_flags table
- `FeatureFlagOverride` - Maps to feature_flag_overrides table

### 4. Middleware
Create 1 middleware:
- `CheckFeatureAccess` - Protect routes/features

### 5. Admin Controller
Create 1 controller:
- `FeatureFlagController` - CRUD operations

---

## File Structure (After Implementation)

```
app/
├── Models/
│   └── FeatureFlag/
│       ├── FeatureFlag.php
│       ├── FeatureFlagOverride.php
│       └── FeatureFlagValue.php
├── Services/
│   └── FeatureToggle/
│       ├── FeatureToggleService.php
│       ├── PlatformFeatureService.php
│       └── FeatureMetricsService.php
├── Http/
│   ├── Middleware/
│   │   └── CheckFeatureAccess.php
│   └── Controllers/
│       └── Admin/
│           └── FeatureFlagController.php
└── Console/
    └── Commands/
        ├── BootstrapFeatures.php
        ├── EnableFeature.php
        └── RolloutFeature.php

database/
└── migrations/
    └── 2025_11_21_000000_create_feature_flags_tables.php

resources/
└── views/
    └── admin/
        └── features/
            ├── index.blade.php
            ├── create.blade.php
            ├── edit.blade.php
            └── log.blade.php
```

---

## Critical Integration Points

### 1. AdPlatformFactory (HIGHEST PRIORITY)
**Before:** Accepts any platform
**After:** Check platform feature toggle first

```php
public static function make(Integration $integration): AdPlatformInterface
{
    $featureToggle = app(FeatureToggleService::class);
    if (!$featureToggle->isActiveForPlatform("platform.{$integration->platform}.enabled", $integration->platform)) {
        throw new \Exception("Platform disabled");
    }
    // ... rest of method
}
```

### 2. Campaign Creation Routes
**Before:** Anyone can create campaigns
**After:** Check campaign.creation.enabled

```php
Route::middleware(['feature:campaign.creation.enabled'])
    ->post('/campaigns', [CampaignController::class, 'store']);
```

### 3. Platform OAuth Flow
**Before:** All platforms available
**After:** Only enabled platforms show

```php
public function redirectToProvider(string $platform)
{
    if (!$this->featureToggle->isPlatformEnabled($platform)) {
        abort(403, 'Platform not available');
    }
    // ... redirect logic
}
```

### 4. Connector Services
**Before:** Any connector can be instantiated
**After:** Check before instantiating

```php
if ($featureToggle->isActiveForPlatform("platform.google.enabled", "google")) {
    $connector = new GoogleConnector();
}
```

---

## Database Setup Commands

### Create Migration
```bash
php artisan make:migration create_feature_flags_tables
# Use the migration provided in the design document
```

### Run Migration
```bash
php artisan migrate
```

### Seed Initial Flags
```bash
php artisan db:seed --class=FeatureFlagSeeder
```

---

## Service Layer Skeleton

Copy this code and build out:

```php
// app/Services/FeatureToggle/FeatureToggleService.php
<?php

namespace App\Services\FeatureToggle;

class FeatureToggleService
{
    public function isActive(string $featureName): bool
    {
        // TODO: Implement resolution logic
        return false;
    }

    public function isActiveForUser(string $featureName, $user): bool
    {
        // TODO: Implement with user override check
        return false;
    }

    public function isActiveForPlatform(string $featureName, string $platform): bool
    {
        // TODO: Implement with platform override check
        return false;
    }

    public function enableFeature(string $featureName): void
    {
        // TODO: Implement
    }

    public function disableFeature(string $featureName): void
    {
        // TODO: Implement
    }
}
```

---

## Testing Strategy

### Unit Tests (Phase 1)
- Test each service method
- Test caching behavior
- Test hierarchy resolution
- Test flag enable/disable

### Integration Tests (Phase 2)
- Test platform integration checks
- Test route middleware
- Test controller logic
- Test database transactions

### E2E Tests (Phase 3)
- Test admin UI workflows
- Test feature enable/disable via UI
- Test org-level overrides
- Test user overrides

---

## Minimum Features to Start

**Initially all DISABLED except:**
- `campaign.creation.enabled` = true
- `campaign.editing.enabled` = true
- `campaign.publishing.enabled` = true

**Enable per-organization as needed:**
- `platform.meta.enabled` = true (for Meta customers)
- `platform.google.enabled` = true (for Google customers)
- etc.

---

## Configuration

Add to `config/features.php`:

```php
return [
    'driver' => 'database',
    'cache_ttl' => 3600,
    'platforms' => ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'],
    'defaults' => [
        'platform.meta.enabled' => false,
        'campaign.creation.enabled' => true,
        // ... more defaults
    ],
];
```

---

## Key Decisions Made

1. **Use PostgreSQL tables** (not Redis/Memcached)
   - Native RLS support
   - Audit trail built-in
   - ACID guarantees
   - Fits CMIS architecture

2. **Cascade hierarchy**
   - User override > Platform override > Org override > System default
   - Allows granular control
   - Simple to reason about

3. **RLS protection**
   - Respects multi-tenancy
   - Prevents data leaks
   - Defense in depth

4. **Caching strategy**
   - In-request caching (sub-millisecond)
   - Redis 1-hour TTL (distributed)
   - Auto-invalidate on change

5. **Gradual rollout**
   - Percentage-based enablement
   - Time-based scheduling
   - Minimal production risk

---

## Risks & Mitigations

| Risk | Mitigation |
|------|-----------|
| Platform goes down if toggle fails | Use try-catch, default to false (disable) |
| Users access disabled features | Middleware and service checks (defense in depth) |
| Cache invalidation fails | Fallback to cache miss, query DB |
| Database performance impact | Indexes on all query columns, caching |
| Wrong flag values in production | RLS + audit trail + careful permissions |
| Users confused by feature unavailability | Clear error messages, admin dashboard |

---

## Success Metrics

- [ ] All platforms can be enabled/disabled independently
- [ ] Each organization can have different feature sets
- [ ] Feature changes take effect < 1 second (cached)
- [ ] Zero downtime during feature rollout
- [ ] 100% audit trail of all changes
- [ ] Admin can manage flags from UI
- [ ] Performance impact < 5ms per feature check

---

## Maintenance Plan

### Daily
- Monitor feature flag dashboard
- Check for errors in logs
- Verify platform integrations working

### Weekly
- Review audit log
- Check for stale overrides
- Validate feature usage metrics

### Monthly
- Remove deprecated flags
- Review and optimize cache settings
- Audit user overrides

---

## Go/No-Go Checklist

Before production launch:

- [ ] All 3 database tables created and tested
- [ ] RLS policies working correctly
- [ ] Service layer tested with 95%+ coverage
- [ ] Platform integration working (tested on Meta, Google)
- [ ] Admin dashboard functional
- [ ] Deployment process documented
- [ ] Rollback plan tested
- [ ] Team trained on usage
- [ ] Monitoring alerts configured
- [ ] Load testing completed

---

## References

- Main Design Document: `feature-toggle-system-design-2025-11-20.md`
- Laravel Pennant Docs: https://laravel.com/docs/12.x/pennant
- Martin Fowler Feature Toggles: https://martinfowler.com/articles/feature-toggles.html
- PostgreSQL RLS: https://www.postgresql.org/docs/current/ddl-rowsecurity.html

---

**Status:** Ready for team review and implementation approval
**Questions?** Review design document or conduct architecture review meeting
