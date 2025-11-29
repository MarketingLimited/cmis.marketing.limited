# Code Cleanup & Maintenance Plan - CMIS Platform

**Date:** 2025-11-28
**Purpose:** Identify files to remove, code to refactor, and cleanup actions needed

---

## 🗑️ Files to Remove/Clean

### 1. Test Reports (Can be archived or removed after review)

**Location:** `test-results/`

```bash
# These are generated test reports - can be removed after reviewing
test-results/bilingual-web/
test-results/bilingual-api/
test-results/all-authenticated-pages/
test-results/functional-interactions/
test-results/org-pages/  # If exists

# Total size: ~10-15 MB (screenshots mostly)
```

**Recommendation:**
```bash
# Archive important reports
mkdir -p docs/testing/reports/2025-11-28/
cp COMPREHENSIVE_QA_REPORT.md docs/testing/reports/2025-11-28/
cp SIDEBAR_NAVIGATION_ANALYSIS.md docs/testing/reports/2025-11-28/
cp test-results/bilingual-web/SUMMARY.md docs/testing/reports/2025-11-28/
cp test-results/bilingual-api/SUMMARY.md docs/testing/reports/2025-11-28/

# Then clean up test results
rm -rf test-results/
```

---

### 2. Temporary Test Scripts (Can be moved to dedicated testing directory)

**Files:**
```
test-all-authenticated.cjs
test-bilingual-comprehensive.cjs
test-bilingual-api.cjs
test-functional-interactions.cjs
test-language-switcher.cjs  # If exists
test-org-routes.sh  # If exists
test-all-routes.sh  # If exists
```

**Recommendation:**
```bash
# Create testing directory structure
mkdir -p tests/browser-automation/
mkdir -p tests/api/
mkdir -p tests/scripts/

# Move scripts
mv test-*.cjs tests/browser-automation/
mv test-*.sh tests/scripts/
mv *-test.cjs tests/browser-automation/
```

---

### 3. Temporary Documentation Files (Move to docs/)

**Files:**
```
BILINGUAL_TESTING_REPORT.md
COMPREHENSIVE_ACTION_PLAN.md
COMPREHENSIVE_BILINGUAL_TESTING_PLAN.md
COMPREHENSIVE_PLATFORM_TESTING_REPORT.md
COMPREHENSIVE_QA_REPORT.md
DEBUG_LANGUAGE_SWITCHER.md
LANGUAGE_SWITCHER_FIX.md
LANGUAGE_SWITCHER_RESOLUTION_SUMMARY.md
PROFILE_PAGE_IMPLEMENTATION.md
TESTING_PLAN_SUMMARY.md
TESTING_SUMMARY.txt
SIDEBAR_NAVIGATION_ANALYSIS.md
CODE_CLEANUP_PLAN.md  # This file
```

**Recommendation:**
```bash
# Organize into docs structure
mkdir -p docs/testing/reports/
mkdir -p docs/testing/plans/
mkdir -p docs/fixes/
mkdir -p docs/analysis/

# Move reports
mv COMPREHENSIVE_QA_REPORT.md docs/testing/reports/
mv COMPREHENSIVE_PLATFORM_TESTING_REPORT.md docs/testing/reports/
mv BILINGUAL_TESTING_REPORT.md docs/testing/reports/
mv SIDEBAR_NAVIGATION_ANALYSIS.md docs/analysis/

# Move plans
mv COMPREHENSIVE_ACTION_PLAN.md docs/testing/plans/
mv COMPREHENSIVE_BILINGUAL_TESTING_PLAN.md docs/testing/plans/
mv TESTING_PLAN_SUMMARY.md docs/testing/plans/

# Move fix documentation
mv LANGUAGE_SWITCHER_FIX.md docs/fixes/
mv LANGUAGE_SWITCHER_RESOLUTION_SUMMARY.md docs/fixes/
mv DEBUG_LANGUAGE_SWITCHER.md docs/fixes/
mv PROFILE_PAGE_IMPLEMENTATION.md docs/fixes/

# Move cleanup plan
mv CODE_CLEANUP_PLAN.md docs/maintenance/
```

---

### 4. Temporary Image Files (Screenshots - Archive or Remove)

**Files:**
```
profile-page-arabic.png  # Screenshot from testing
```

**Recommendation:**
```bash
# Move to test results archive or remove
mv profile-page-arabic.png docs/testing/screenshots/
# OR
rm profile-page-arabic.png  # If not needed
```

---

### 5. Temporary View Files (Testing Only)

**Files:**
```
resources/views/components/locale-debug.blade.php
resources/views/language-test.blade.php
resources/views/locale-diagnostic.blade.php
```

**Recommendation:**
```bash
# Remove debug views (only needed during development)
rm resources/views/components/locale-debug.blade.php
rm resources/views/language-test.blade.php
rm resources/views/locale-diagnostic.blade.php
```

---

### 6. Temporary PHP Test Files

**Files:**
```
test-locale-cookie.php
```

**Recommendation:**
```bash
# Move to tests directory or remove
mv test-locale-cookie.php tests/manual/
# OR
rm test-locale-cookie.php  # If not needed
```

---

## 🔧 Code Refactoring Needed

### 1. Fix Social Posts Controller (CRITICAL)

**File:** `app/Http/Controllers/SocialPostsController.php` (or similar)

**Issue:** Missing `$currentOrg` variable passed to view

**Fix:**
```php
// Before (broken)
public function index()
{
    $posts = SocialPost::all();
    return view('social.posts', compact('posts'));
}

// After (fixed)
public function index()
{
    $currentOrg = auth()->user()->currentOrganization;
    $posts = SocialPost::where('org_id', $currentOrg->id)->get();

    return view('social.posts', [
        'currentOrg' => $currentOrg,
        'posts' => $posts
    ]);
}
```

---

### 2. Fix Hardcoded Translation Keys

**Files to audit and fix:**
```
resources/views/campaigns/*.blade.php
resources/views/dashboard/*.blade.php
resources/views/analytics/*.blade.php
resources/views/social/*.blade.php
```

**Issue:** Translation keys appearing as literal text instead of being translated

**Example Fix:**
```blade
<!-- Before (wrong) -->
<p class="text-gray-600">campaigns.manage_all_campaigns</p>

<!-- After (correct) -->
<p class="text-gray-600">{{ __('campaigns.manage_all_campaigns') }}</p>
```

**Automated Fix Script:**
```bash
# Find all instances
grep -r "campaigns\.manage_all_campaigns" resources/views/

# Fix with sed (backup first!)
find resources/views/ -name "*.blade.php" -exec sed -i.bak 's/campaigns\.manage_all_campaigns/{{ __("campaigns.manage_all_campaigns") }}/g' {} \;
```

---

### 3. API Middleware Configuration

**File:** `routes/api.php`

**Issue:** Missing `auth:sanctum` middleware on protected routes

**Fix:**
```php
// Add middleware to API route groups
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [UserController::class, 'show']);

    Route::prefix('orgs/{org}')->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index']);
        Route::get('/social/posts', [SocialPostsController::class, 'index']);
        // ... other protected routes
    });
});
```

---

## 📁 File Organization

### Current Structure Issues
```
Root directory cluttered with:
- Test scripts (*.cjs, *.sh)
- Documentation files (*.md)
- Temporary files (*.png, *.php)
- Test results (test-results/)
```

### Proposed Clean Structure
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
│   ├── testing/
│   │   ├── reports/
│   │   │   └── 2025-11-28/
│   │   │       ├── COMPREHENSIVE_QA_REPORT.md
│   │   │       └── ...
│   │   ├── plans/
│   │   └── screenshots/
│   ├── fixes/
│   ├── analysis/
│   └── maintenance/
├── public/
├── resources/
├── routes/
├── storage/
├── tests/
│   ├── browser-automation/
│   │   ├── test-bilingual-comprehensive.cjs
│   │   └── ...
│   ├── api/
│   │   └── test-bilingual-api.cjs
│   ├── manual/
│   └── scripts/
├── vendor/
├── .claude/
├── CLAUDE.md
├── README.md
└── package.json
```

---

## 🧹 Cleanup Commands

### Full Cleanup Script

```bash
#!/bin/bash

echo "🧹 Starting CMIS Cleanup..."

# 1. Create documentation structure
echo "📁 Creating documentation directories..."
mkdir -p docs/testing/{reports,plans,screenshots}/2025-11-28
mkdir -p docs/{fixes,analysis,maintenance}
mkdir -p tests/{browser-automation,api,manual,scripts}

# 2. Move documentation files
echo "📄 Moving documentation files..."
mv COMPREHENSIVE_QA_REPORT.md docs/testing/reports/2025-11-28/
mv COMPREHENSIVE_PLATFORM_TESTING_REPORT.md docs/testing/reports/2025-11-28/
mv BILINGUAL_TESTING_REPORT.md docs/testing/reports/2025-11-28/
mv SIDEBAR_NAVIGATION_ANALYSIS.md docs/analysis/
mv COMPREHENSIVE_ACTION_PLAN.md docs/testing/plans/2025-11-28/
mv COMPREHENSIVE_BILINGUAL_TESTING_PLAN.md docs/testing/plans/2025-11-28/
mv TESTING_PLAN_SUMMARY.md docs/testing/plans/2025-11-28/
mv TESTING_SUMMARY.txt docs/testing/reports/2025-11-28/
mv LANGUAGE_SWITCHER_FIX.md docs/fixes/
mv LANGUAGE_SWITCHER_RESOLUTION_SUMMARY.md docs/fixes/
mv DEBUG_LANGUAGE_SWITCHER.md docs/fixes/
mv PROFILE_PAGE_IMPLEMENTATION.md docs/fixes/
mv CODE_CLEANUP_PLAN.md docs/maintenance/

# 3. Move test scripts
echo "🧪 Moving test scripts..."
mv test-*.cjs tests/browser-automation/ 2>/dev/null || true
mv test-*.sh tests/scripts/ 2>/dev/null || true
mv *-test.cjs tests/browser-automation/ 2>/dev/null || true
mv comprehensive-platform-test.cjs tests/browser-automation/ 2>/dev/null || true

# 4. Move or remove test results
echo "📊 Archiving test results..."
if [ -d "test-results" ]; then
    # Archive summaries
    cp test-results/*/SUMMARY.md docs/testing/reports/2025-11-28/ 2>/dev/null || true

    # Remove test results (or move to archive)
    # rm -rf test-results/  # Uncomment to delete
    mv test-results docs/testing/archived-results-2025-11-28 # Or archive
fi

# 5. Clean temporary files
echo "🗑️  Removing temporary files..."
rm test-locale-cookie.php 2>/dev/null || true
rm profile-page-arabic.png 2>/dev/null || true

# 6. Clean debug views
echo "🔧 Removing debug views..."
rm resources/views/components/locale-debug.blade.php 2>/dev/null || true
rm resources/views/language-test.blade.php 2>/dev/null || true
rm resources/views/locale-diagnostic.blade.php 2>/dev/null || true

# 7. Clean up scripts directory
echo "📂 Organizing scripts..."
if [ -d "scripts" ]; then
    mv scripts tests/ 2>/dev/null || true
fi

echo "✅ Cleanup complete!"
echo ""
echo "📋 Summary:"
echo "  - Documentation organized in docs/"
echo "  - Test scripts moved to tests/"
echo "  - Temporary files removed"
echo "  - Debug views removed"
echo ""
echo "⚠️  Manual tasks remaining:"
echo "  1. Review docs/testing/reports/2025-11-28/ and delete if not needed"
echo "  2. Fix social posts controller (see docs/testing/reports/2025-11-28/COMPREHENSIVE_QA_REPORT.md)"
echo "  3. Fix hardcoded translation keys"
echo "  4. Add API middleware authentication"
