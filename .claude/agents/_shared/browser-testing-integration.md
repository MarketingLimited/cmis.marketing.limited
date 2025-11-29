# Browser Testing Integration

**Version:** 2.0 (Comprehensive Test Suites Added)
**Last Updated:** 2025-11-28

---

## Overview

This agent can utilize browser-based verification to ensure code changes render correctly in the live application. CMIS includes comprehensive test suites for mobile responsive testing, cross-browser compatibility, and bilingual (AR/EN) verification.

---

## CMIS Test Suites (RECOMMENDED)

| Test Suite | Command | Purpose |
|------------|---------|---------|
| **Mobile Responsive** | `node scripts/browser-tests/mobile-responsive-comprehensive.js` | 7 device profiles, both locales |
| **Cross-Browser** | `node scripts/browser-tests/cross-browser-test.js` | Chrome, Firefox, Safari (WebKit) |
| **Bilingual** | `node test-bilingual-comprehensive.cjs` | All pages in AR/EN with i18n checks |
| **Quick Responsive** | `node scripts/browser-tests/responsive-test.js [url]` | Single page, 4 viewports |

### Quick Mode Commands

```bash
# Mobile responsive (5 pages, 2 devices - fast)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (5 pages - fast)
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser only
node scripts/browser-tests/cross-browser-test.js --browser chrome
```

---

## Test Environment

- **Live URL**: https://cmis-test.kazaaz.com/
- **Test Scripts**: `/scripts/browser-tests/`
- **Languages**: Arabic (RTL, default) and English (LTR)
- **Test Credentials**: `admin@cmis.test` / `password`
- **Test Org ID**: `5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a`

---

## Device Profiles (Mobile Testing)

| Device | Resolution | Type |
|--------|------------|------|
| iPhone SE | 375x667 | Mobile |
| iPhone 14 | 390x844 | Mobile |
| iPhone 14 Pro Max | 430x932 | Mobile |
| Pixel 7 | 412x915 | Mobile |
| Galaxy S21 | 360x800 | Mobile |
| iPad Mini | 768x1024 | Tablet |
| iPad Pro | 1024x1366 | Tablet |

---

## Issues Automatically Checked

### Mobile Responsive Tests

- **Horizontal overflow** - Content wider than viewport
- **Touch targets** - Interactive elements < 44x44px
- **Font sizes** - Text smaller than 12px
- **Viewport meta** - Missing or improper viewport tag
- **RTL/LTR consistency** - Direction matches locale

### Cross-Browser Tests

- **CSS feature support** - Flexbox, Grid, Custom Properties, Logical Properties
- **Broken images** - Images that fail to load
- **SVG rendering** - SVGs with zero dimensions
- **Console errors** - JavaScript errors captured
- **Layout metrics** - Document vs viewport dimensions

---

## Quick Commands

```bash
# Quick text dump of a page
lynx -dump https://cmis-test.kazaaz.com/login

# Quick screenshot
npx playwright screenshot https://cmis-test.kazaaz.com/ screenshot.png

# Full page test with status code
node scripts/browser-tests/puppeteer-test.js https://cmis-test.kazaaz.com/

# Responsive testing (mobile, tablet, desktop, widescreen)
node scripts/browser-tests/responsive-test.js https://cmis-test.kazaaz.com/
```

---

## When Browser Testing is MANDATORY

| Scenario | Recommended Test |
|----------|------------------|
| UI/Layout changes | `mobile-responsive-comprehensive.js --quick` |
| i18n/locale changes | `test-bilingual-comprehensive.cjs` |
| Before release | All test suites (full mode) |
| RTL/LTR modifications | Mobile + Bilingual tests |
| New pages/features | Cross-browser + Mobile tests |
| Form implementations | Full bilingual test |
| Auth flow changes | Bilingual test |
| Responsive design | Mobile responsive test |
| Dashboard visualizations | All tests |

---

## Setting Locale for Testing

**CRITICAL:** Always set locale cookie BEFORE navigation:

```javascript
// Set Arabic locale
await page.setCookie({
    name: 'app_locale',
    value: 'ar',  // or 'en'
    domain: 'cmis-test.kazaaz.com',
    path: '/'
});
await page.goto(url);
```

---

## Test Output Locations

```
test-results/
├── mobile-responsive/
│   ├── screenshots/          # Device-specific screenshots
│   ├── results.json          # Machine-readable results
│   └── REPORT.md             # Human-readable report
├── cross-browser/
│   ├── screenshots/chrome/
│   ├── screenshots/firefox/
│   ├── screenshots/safari--webkit-/
│   ├── results.json
│   └── REPORT.md
└── bilingual-web/
    ├── screenshots/
    ├── test-report.json
    └── SUMMARY.md
```

---

## Integration with Development Workflow

### Pre-Implementation Checklist

```bash
# 1. Capture baseline (before changes)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick
```

### Post-Implementation Verification

```bash
# 2. After code changes - verify output
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# 3. Check for high-severity issues
grep -c '"severity": "high"' test-results/mobile-responsive/results.json
# Should output 0
```

### Before Marking Task Complete

1. Run relevant test suite (mobile/cross-browser/bilingual)
2. Verify no high-severity issues
3. Check screenshots for visual correctness
4. Test BOTH Arabic (RTL) and English (LTR) for i18n work

---

## Troubleshooting

### Browser Won't Launch

```bash
npx playwright install-deps
# or
npx puppeteer browsers install chrome
```

### Tests Timeout

Increase timeout in test config or use `--quick` mode for faster runs.

### Cookie Not Being Set

Ensure domain matches exactly: `cmis-test.kazaaz.com` (no https://, no trailing slash)

---

## Full Documentation

See: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`
