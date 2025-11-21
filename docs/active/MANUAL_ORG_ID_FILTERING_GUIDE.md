# Manual org_id Filtering Removal Guide
**Date:** November 21, 2025
**Phase:** Phase 1, Week 1 - Multi-Tenancy Optimization
**Priority:** P1 - HIGH

---

## Overview

This document provides guidance for removing manual `where('org_id')` filtering from repositories and services throughout the CMIS codebase. With proper RLS (Row-Level Security) policies in place, manual org_id filtering is redundant and creates anti-patterns.

---

## Why Remove Manual org_id Filtering?

### Problems with Manual Filtering:
1. **Redundant** - RLS already filters at the database level
2. **Error-prone** - Easy to forget, creating inconsistencies
3. **Performance overhead** - Filtering happens twice (RLS + application)
4. **Violates DRY principle** - Same logic in multiple places
5. **Harder to maintain** - Changes must be made in many files
6. **False sense of security** - Developers might rely on it instead of RLS

### Benefits of RLS-Only Approach:
1. **Single source of truth** - All filtering at database level
2. **Automatic** - No code changes needed for new queries
3. **Consistent** - Every query filtered the same way
4. **Secure by default** - Impossible to forget filtering
5. **Simpler code** - Less boilerplate

---

## Finding Manual org_id Filtering

### Search Commands

```bash
# Find all where('org_id') calls
grep -rn "where('org_id'" app/ --include="*.php"

# Find all where("org_id") calls
grep -rn 'where("org_id"' app/ --include="*.php"

# Find whereOrgId calls
grep -rn 'whereOrgId' app/ --include="*.php"

# Combined search
grep -rn "where.*org_id" app/ --include="*.php"
```

### Known Locations (from audit):

1. `app/Repositories/CMIS/CampaignRepository.php` (multiple locations)
2. `app/Services/CMIS/CampaignService.php`
3. `app/Http/Controllers/Campaigns/CampaignController.php`
4. Other repositories and services (20+ files)

---

## Pattern Recognition

### BEFORE (With Manual Filtering - WRONG):

```php
// Repository method
public function findByOrganization(string $orgId)
{
    return Campaign::where('org_id', $orgId)  // ❌ REDUNDANT
        ->where('status', 'active')
        ->get();
}

// Service method
public function getUserCampaigns(string $userId, string $orgId)
{
    return Campaign::where('org_id', $orgId)  // ❌ REDUNDANT
        ->where('user_id', $userId)
        ->get();
}

// Controller method
public function index(Request $request)
{
    $orgId = $request->user()->org_id;

    $campaigns = Campaign::where('org_id', $orgId)  // ❌ REDUNDANT
        ->paginate(20);

    return view('campaigns.index', compact('campaigns'));
}
```

### AFTER (RLS-Only - CORRECT):

```php
// Repository method
public function findActive()  // No $orgId parameter needed!
{
    return Campaign::where('status', 'active')  // ✅ RLS handles org filtering
        ->get();
}

// Service method
public function getUserCampaigns(string $userId)  // No $orgId parameter needed!
{
    return Campaign::where('user_id', $userId)  // ✅ RLS handles org filtering
        ->get();
}

// Controller method
public function index(Request $request)
{
    // No manual org_id filtering needed!
    $campaigns = Campaign::paginate(20);  // ✅ RLS handles org filtering

    return view('campaigns.index', compact('campaigns'));
}
```

---

## Step-by-Step Removal Process

### Step 1: Ensure RLS Context is Set

Before removing manual filtering, verify the route has `org.context` middleware:

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'org.context'])->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
    // Other routes...
});
```

### Step 2: Identify the Pattern

Look for these patterns:
```php
->where('org_id', $orgId)
->where('org_id', '=', $orgId)
->whereOrgId($orgId)
->where(['org_id' => $orgId])
```

### Step 3: Remove the Filtering

**Simple Removal:**
```php
// BEFORE
Campaign::where('org_id', $orgId)->where('status', 'active')->get();

// AFTER
Campaign::where('status', 'active')->get();
```

**Remove Parameter:**
```php
// BEFORE
public function getActiveCampaigns(string $orgId)
{
    return Campaign::where('org_id', $orgId)
        ->where('status', 'active')
        ->get();
}

// AFTER
public function getActiveCampaigns()  // Remove $orgId parameter
{
    return Campaign::where('status', 'active')
        ->get();
}
```

**Update Callers:**
```php
// BEFORE
$campaigns = $repository->getActiveCampaigns($request->user()->org_id);

// AFTER
$campaigns = $repository->getActiveCampaigns();  // No org_id needed
```

### Step 4: Update PHPDoc

```php
// BEFORE
/**
 * Get active campaigns for an organization
 *
 * @param string $orgId Organization UUID
 * @return Collection
 */
public function getActiveCampaigns(string $orgId)

// AFTER
/**
 * Get active campaigns (automatically filtered by RLS)
 *
 * @return Collection
 */
public function getActiveCampaigns()
```

### Step 5: Test Thoroughly

After removing manual filtering:

```php
// Test that RLS is working
public function test_campaigns_are_filtered_by_organization()
{
    // Create two organizations
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    // Create campaigns for each
    $campaign1 = Campaign::factory()->create(['org_id' => $org1->id]);
    $campaign2 = Campaign::factory()->create(['org_id' => $org2->id]);

    // Set context to org1
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $this->user->id,
        $org1->id
    ]);

    // Should only see org1's campaign
    $campaigns = Campaign::all();
    $this->assertCount(1, $campaigns);
    $this->assertEquals($campaign1->id, $campaigns->first()->id);

    // Set context to org2
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $this->user->id,
        $org2->id
    ]);

    // Should only see org2's campaign
    $campaigns = Campaign::all();
    $this->assertCount(1, $campaigns);
    $this->assertEquals($campaign2->id, $campaigns->first()->id);
}
```

---

## Exceptions (When to Keep Manual Filtering)

### Keep Manual Filtering If:

1. **Cross-Organization Queries (Admin Only)**
   ```php
   // Admins viewing all organizations' data
   public function getAllCampaignsForAdmin()
   {
       // This is legitimate - admin needs cross-org view
       return Campaign::all();  // No filtering needed for admins
   }
   ```

2. **Reporting Across Organizations**
   ```php
   // Super admin report
   public function getOrganizationReport()
   {
       return DB::table('cmis.campaigns')
           ->selectRaw('org_id, COUNT(*) as campaign_count')
           ->groupBy('org_id')
           ->get();
   }
   ```

3. **Migration/Seeding Scripts**
   ```php
   // Database seeders that need to create data for multiple orgs
   public function run()
   {
       foreach ($organizations as $org) {
           Campaign::create([
               'org_id' => $org->id,  // OK in seeders
               // ...
           ]);
       }
   }
   ```

### How to Handle Exceptions:

```php
// Use explicit bypass or different connection
public function getAdminCrossOrgData()
{
    // Clear RLS context for this specific query
    DB::statement('SELECT cmis.clear_transaction_context()');

    $data = Campaign::all();  // Gets all orgs

    // Restore context
    DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
        $this->userId,
        $this->orgId
    ]);

    return $data;
}
```

---

## Automated Refactoring Script

### Find and Review Script

```bash
#!/bin/bash
# save as: scripts/find_manual_org_filtering.sh

echo "Searching for manual org_id filtering..."
echo ""

grep -rn "where('org_id'" app/ --include="*.php" > /tmp/org_filtering.txt
grep -rn 'where("org_id"' app/ --include="*.php" >> /tmp/org_filtering.txt

echo "Found $(wc -l < /tmp/org_filtering.txt) instances"
echo ""
echo "Results saved to: /tmp/org_filtering.txt"
echo ""
echo "Top 10 files with most instances:"
cut -d: -f1 /tmp/org_filtering.txt | sort | uniq -c | sort -rn | head -10
```

Run with:
```bash
chmod +x scripts/find_manual_org_filtering.sh
./scripts/find_manual_org_filtering.sh
```

---

## Priority Order for Removal

### High Priority (Week 1):
1. **Repositories** - Core data access layer
   - CampaignRepository
   - ContentRepository
   - UserRepository

2. **Services** - Business logic layer
   - CampaignService
   - ContentService

### Medium Priority (Week 2):
3. **Controllers** - Request handlers
   - CampaignController
   - DashboardController

4. **Models** - Query scopes
   - Campaign model scopes
   - Content model scopes

### Low Priority (Week 3):
5. **API Controllers**
6. **Admin Controllers** (review for legitimate cross-org needs)
7. **Commands/Jobs**

---

## Checklist for Each File

When removing manual filtering from a file:

- [ ] Verified route has `org.context` middleware
- [ ] Removed all `where('org_id')` calls
- [ ] Removed `$orgId` parameters from methods
- [ ] Updated all callers to not pass `$orgId`
- [ ] Updated PHPDoc comments
- [ ] Added/updated tests for RLS filtering
- [ ] Verified no functionality broken
- [ ] Code review completed
- [ ] Deployed to staging and tested

---

## Testing Checklist

After removing manual filtering:

- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Integration tests pass
- [ ] Multi-tenancy isolation tests pass
- [ ] Manual testing in development
- [ ] Manual testing in staging
- [ ] Performance testing (should be faster)
- [ ] Security testing (no cross-org data leaks)

---

## Common Pitfalls

### Pitfall 1: Forgetting to Update Callers

```php
// Repository method updated
public function getActiveCampaigns()  // Removed $orgId
{
    return Campaign::where('status', 'active')->get();
}

// But caller not updated - will cause error!
$campaigns = $repository->getActiveCampaigns($orgId);  // ❌ Too many arguments
```

**Solution:** Use IDE search/replace or PHPStan to find all callers.

### Pitfall 2: Breaking Admin Functions

```php
// This breaks admin cross-org views
public function adminDashboard()
{
    // RLS is active, admin only sees their own org!
    $allCampaigns = Campaign::all();  // ❌ Only current org
}
```

**Solution:** Implement proper admin bypass pattern.

### Pitfall 3: Not Testing Multi-Tenancy

```php
// Test passes but doesn't verify isolation
public function test_get_campaigns()
{
    $campaign = Campaign::factory()->create();
    $this->assertDatabaseHas('cmis.campaigns', [
        'id' => $campaign->id
    ]);
}
```

**Solution:** Always test with multiple organizations.

---

## Progress Tracking

Track progress in a spreadsheet or project management tool:

| File | Type | Instances | Status | Tested | PR |
|------|------|-----------|--------|--------|----|
| CampaignRepository.php | Repo | 5 | ✅ Done | ✅ | #123 |
| ContentRepository.php | Repo | 3 | 🔄 In Progress | ⏳ | - |
| CampaignService.php | Service | 2 | ⏳ Todo | ⏳ | - |
| ... | ... | ... | ... | ... | ... |

---

## Monitoring After Deployment

### Query Monitoring

```sql
-- Check for queries with manual org_id filtering
-- (This would require query logging)
SELECT query, count(*)
FROM pg_stat_statements
WHERE query LIKE '%WHERE org_id%'
AND query NOT LIKE '%RLS%'
GROUP BY query
ORDER BY count DESC;
```

### RLS Context Monitoring

```php
// Add to monitoring/alerting
if (!DB::selectOne("SELECT current_setting('app.current_org_id', true)")->current_setting) {
    \Log::error('RLS context not set for authenticated request', [
        'user_id' => auth()->id(),
        'route' => request()->path()
    ]);
}
```

---

## Rollback Plan

If issues occur after removing manual filtering:

```bash
# 1. Revert the changes
git revert <commit-hash>

# 2. Or restore manual filtering temporarily
# Add back where('org_id') in critical paths

# 3. Investigate the issue
# - Was RLS context not set?
# - Was it an admin function?
# - Was it a legitimate cross-org need?

# 4. Fix the root cause

# 5. Re-remove manual filtering
```

---

## Conclusion

Removing manual org_id filtering is a systematic process that improves code quality, security, and performance. By following this guide, you can safely remove redundant filtering while ensuring multi-tenancy isolation remains intact.

**Key Principles:**
1. **Trust RLS** - It's designed for this purpose
2. **Test thoroughly** - Verify isolation with tests
3. **Remove systematically** - One file at a time
4. **Update documentation** - Help future developers understand

**Expected Benefits:**
- Cleaner, more maintainable code
- Consistent filtering across the application
- Improved performance (no double filtering)
- Reduced risk of filtering bugs

---

**Next Steps:**
1. Review this guide with the team
2. Start with high-priority repositories
3. Create PRs for each file or group of related files
4. Run comprehensive tests
5. Deploy to staging and verify
6. Monitor for issues
7. Continue to next priority tier

**For questions or issues, refer to:**
- Multi-Tenancy RLS Audit: `docs/active/analysis/multi-tenancy-rls-audit-2025-11-21.md`
- Implementation Roadmap: `docs/active/IMPLEMENTATION_ROADMAP.md`
