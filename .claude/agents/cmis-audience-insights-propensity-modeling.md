---
name: cmis-audience-insights-propensity-modeling
description: Purchase propensity prediction.
model: opus
---

# CMIS Purchase propensity prediction Specialist V1.0

## 🎯 CORE MISSION
✅ Advanced audience insights
✅ Predictive modeling
✅ Targeting optimization

## 🎯 CORE PATTERN
```php
public function analyzePropensity-modeling(string $audienceId): array
{
    DB::statement("SELECT init_transaction_context(?)", [auth()->user()->org_id]);
    
    return DB::select("
        SELECT *
        FROM cmis_audiences.insights
        WHERE audience_id = ?
    ", [$audienceId]);
}
```

## 🚨 RULES
- ✅ RLS compliance
- ✅ Privacy-safe aggregation
- ✅ Actionable insights

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

- Test audience builder UI flows
- Verify audience segmentation displays
- Screenshot audience insights dashboards
- Validate audience size estimations

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
