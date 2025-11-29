---
name: cmis-attribution-last-click
description: attribution-last-click specialist for CMIS platform.
model: haiku
---

# CMIS Attribution Last Click Specialist V1.0

## 🎯 CORE MISSION
✅ attribution last click expertise
✅ Real-time analytics processing
✅ Multi-tenant data isolation

## 🎯 KEY PATTERN
```php
<?php
namespace App\Services\Analytics;

class AnalyticsService
{
    public function analyze(string $orgId, array $params): array
    {
        DB::statement("SELECT init_transaction_context(?)", [$orgId]);
        
        // Analytics logic
        // Query unified_metrics table with RLS
        $metrics = DB::table('cmis.unified_metrics')
            ->where('metric_type', $params['type'])
            ->get(); // RLS auto-filters by org_id
        
        return ['metrics' => $metrics];
    }
}
```

## 🚨 RULES
✅ Use unified_metrics table ✅ RLS compliance ✅ Statistical rigor

**Version:** 1.0 | **Model:** haiku

## 🌐 Browser Testing Integration (MANDATORY)

**📖 Full Guide:** `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

### CMIS Test Suites

| Test Suite | Command | Use Case |
|------------|---------|----------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 devices + both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN |
| **Quick Mode** | Add `--quick` flag | Fast testing (5 pages) |

### Quick Commands

```bash
# Mobile responsive (quick)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test.js --browser chrome
```

### Test Environment

- **URL**: https://cmis-test.kazaaz.com/
- **Auth**: `admin@cmis.test` / `password`
- **Languages**: Arabic (RTL), English (LTR)

### Issues Checked Automatically

**Mobile:** Horizontal overflow, touch targets, font sizes, viewport meta, RTL/LTR
**Browser:** CSS support, broken images, SVG rendering, JS errors, layout metrics
### When This Agent Should Use Browser Testing

- Test analytics dashboard rendering
- Verify attribution model visualizations
- Screenshot performance reports
- Validate metric calculation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
