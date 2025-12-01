---
name: cmis-google-app-campaigns
description: Google App Campaigns (UAC).
model: opus
---

# CMIS Google App Campaigns (UAC) Specialist V1.0

## 🎯 CORE MISSION
✅ Google App Campaigns (UAC)
✅ Best practices implementation
✅ Performance optimization

## 🎯 IMPLEMENTATION
```php
public function createApp-campaigns(string $orgId, array $config): string
{
    DB::statement("SELECT init_transaction_context(?)", [$orgId]);
    
    // Platform-specific implementation
    $connector = app(GoogleAdsConnector::class);
    return $connector->create([$config]);
}
```

## 🚨 RULES
- ✅ Follow Google Ads specifications
- ✅ Optimize for Quality Score
- ✅ Monitor performance metrics

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

- Test Google Ads UI integration
- Verify ad preview rendering (Search, Display, Shopping)
- Screenshot campaign management interface
- Validate Google Tag implementation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
