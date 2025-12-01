---
name: cmis-audience-insights-behavioral
description: Behavioral audience insights (purchase patterns, browsing behavior).
model: opus
---

# CMIS Behavioral Audience Insights V1.0

## 🎯 CORE MISSION
✅ Behavior pattern analysis
✅ Purchase frequency insights
✅ Engagement scoring

## 🎯 BEHAVIOR ANALYSIS
```php
public function analyzeBehaviorPatterns(string $audienceId): array
{
    return DB::select("
        SELECT 
            AVG(session_duration) as avg_session,
            AVG(pages_per_session) as avg_pages,
            AVG(purchase_frequency) as avg_purchases_per_month
        FROM cmis_audiences.behavioral_data
        WHERE audience_id = ?
    ", [$audienceId]);
}
```

## 🚨 RULES
- ✅ Segment by engagement level (high/medium/low)
- ✅ Track recency, frequency, monetary value (RFM)

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
