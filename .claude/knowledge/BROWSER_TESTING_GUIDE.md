# CMIS Browser Testing Guide

**Last Updated:** 2025-11-28
**Version:** 1.0
**Status:** Production Ready

---

## Overview

This guide covers the browser testing infrastructure for CMIS, including mobile responsive testing, cross-browser compatibility testing, and bilingual (AR/EN) verification.

---

## Testing Tools Available

### 1. Text-Based Browsers (Quick Checks)

| Tool | Command | Best For |
|------|---------|----------|
| `lynx` | `lynx -dump <url>` | Quick text content extraction |
| `w3m` | `w3m -dump <url>` | HTML structure validation |
| `links` | `links -dump <url>` | Simple page dumps |

### 2. Headless Browsers (Screenshots & Automation)

| Tool | Command | Best For |
|------|---------|----------|
| Chrome Headless | `google-chrome --headless --screenshot=file.png <url>` | Quick screenshots |
| Playwright | `npx playwright screenshot <url> file.png` | Multi-browser screenshots |
| Puppeteer | `node scripts/browser-tests/puppeteer-test.js <url>` | Full page testing |

### 3. CMIS Custom Test Suites

| Script | Location | Purpose |
|--------|----------|---------|
| Bilingual Comprehensive | `test-bilingual-comprehensive.cjs` | Tests all pages in AR/EN |
| Mobile Responsive | `scripts/browser-tests/mobile-responsive-comprehensive.js` | Multi-device testing |
| Cross-Browser | `scripts/browser-tests/cross-browser-test.js` | Chrome/Firefox/Safari |
| Responsive Simple | `scripts/browser-tests/responsive-test.js` | Quick viewport tests |

---

## Mobile Responsive Testing

### Device Profiles Supported

```javascript
const DEVICES = {
    iPhoneSE:        { width: 375,  height: 667,  type: 'mobile' },
    iPhone14:        { width: 390,  height: 844,  type: 'mobile' },
    iPhone14ProMax:  { width: 430,  height: 932,  type: 'mobile' },
    Pixel7:          { width: 412,  height: 915,  type: 'mobile' },
    GalaxyS21:       { width: 360,  height: 800,  type: 'mobile' },
    iPadMini:        { width: 768,  height: 1024, type: 'tablet' },
    iPadPro:         { width: 1024, height: 1366, type: 'tablet' }
};
```

### Running Mobile Tests

```bash
# Full test suite (all devices, all pages, both locales)
node scripts/browser-tests/mobile-responsive-comprehensive.js

# Quick mode (5 pages, 2 devices)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Limited pages
node scripts/browser-tests/mobile-responsive-comprehensive.js --pages 10
```

### Mobile Issues Checked

1. **Horizontal Overflow** - Content wider than viewport
2. **Touch Targets** - Interactive elements < 44x44px
3. **Font Sizes** - Text smaller than 12px
4. **Viewport Meta** - Missing or improper viewport tag
5. **RTL/LTR Consistency** - Direction matches locale

### Output Location

```
test-results/mobile-responsive/
├── screenshots/
│   ├── dashboard-iphone-se-ar.png
│   ├── dashboard-iphone-se-en.png
│   └── ...
├── results.json
└── REPORT.md
```

---

## Cross-Browser Testing

### Browsers Supported

| Browser | Engine | Launcher |
|---------|--------|----------|
| Chrome | Chromium | `chromium` |
| Firefox | Gecko | `firefox` |
| Safari | WebKit | `webkit` |

### Running Cross-Browser Tests

```bash
# Full test suite (all browsers, all pages, both locales)
node scripts/browser-tests/cross-browser-test.js

# Quick mode
node scripts/browser-tests/cross-browser-test.js --quick

# Single browser
node scripts/browser-tests/cross-browser-test.js --browser chrome
node scripts/browser-tests/cross-browser-test.js --browser firefox
node scripts/browser-tests/cross-browser-test.js --browser webkit
```

### Browser Issues Checked

1. **CSS Feature Support** - Flexbox, Grid, Custom Properties, Logical Properties
2. **Broken Images** - Images that fail to load
3. **SVG Rendering** - SVGs with zero dimensions
4. **Console Errors** - JavaScript errors captured
5. **Layout Metrics** - Document vs viewport dimensions

### Output Location

```
test-results/cross-browser/
├── screenshots/
│   ├── chrome/
│   │   ├── dashboard-ar.png
│   │   └── dashboard-en.png
│   ├── firefox/
│   └── safari--webkit-/
├── results.json
└── REPORT.md
```

---

## Bilingual Testing (AR/EN)

### Locale Cookie Method

The proper way to test locales is to set the `app_locale` cookie BEFORE navigation:

```javascript
// Set locale cookie before visiting page
await page.setCookie({
    name: 'app_locale',
    value: 'ar',  // or 'en'
    domain: 'cmis-test.kazaaz.com',
    path: '/',
    httpOnly: false,
    secure: true,
    sameSite: 'Lax'
});

// Then navigate
await page.goto(url);
```

### What to Verify for Each Locale

| Aspect | Arabic (ar) | English (en) |
|--------|-------------|--------------|
| `<html lang>` | `ar` | `en` |
| `<html dir>` | `rtl` | `ltr` |
| Text Direction | Right-to-left | Left-to-right |
| CSS Properties | `ms-`, `me-`, `text-start` | Same (logical) |
| Font | Arabic-compatible | Standard |

### Running Bilingual Tests

```bash
# Comprehensive bilingual test
node test-bilingual-comprehensive.cjs

# Output in test-results/bilingual-web/
```

---

## When to Use Browser Testing

### MANDATORY Testing Scenarios

| Scenario | Test Type | Command |
|----------|-----------|---------|
| UI changes | Screenshot comparison | `node scripts/browser-tests/responsive-test.js <url>` |
| Form implementations | Full page test | `node scripts/browser-tests/puppeteer-test.js <url>` |
| Auth flow changes | Bilingual test | `node test-bilingual-comprehensive.cjs` |
| Layout modifications | Mobile responsive | `node scripts/browser-tests/mobile-responsive-comprehensive.js --quick` |
| i18n changes | Bilingual + Mobile | Both tests |
| Before releases | Full suite | All test suites |

### Quick Verification Commands

```bash
# Quick screenshot of a specific page
npx playwright screenshot https://cmis-test.kazaaz.com/login screenshot.png

# Check page in Arabic
node -e "
const puppeteer = require('puppeteer');
(async () => {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();
    await page.setCookie({name: 'app_locale', value: 'ar', domain: 'cmis-test.kazaaz.com', path: '/'});
    await page.goto('https://cmis-test.kazaaz.com/login');
    await page.screenshot({path: 'login-ar.png', fullPage: true});
    await browser.close();
})();
"

# Text dump for quick content check
lynx -dump https://cmis-test.kazaaz.com/login
```

---

## Integration with Development Workflow

### Pre-Commit Checks

Before committing UI changes:

```bash
# 1. Run quick responsive test
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# 2. Verify no high-severity issues
grep -c '"severity": "high"' test-results/mobile-responsive/results.json
# Should output 0
```

### CI/CD Integration

```yaml
# Example GitHub Actions step
- name: Run Browser Tests
  run: |
    npm install playwright
    npx playwright install
    node scripts/browser-tests/mobile-responsive-comprehensive.js --quick
    node scripts/browser-tests/cross-browser-test.js --quick
```

### Post-Deployment Verification

```bash
# Full test suite after deployment
node scripts/browser-tests/mobile-responsive-comprehensive.js
node scripts/browser-tests/cross-browser-test.js
node test-bilingual-comprehensive.cjs
```

---

## Common Issues and Fixes

### 1. Horizontal Overflow on Mobile

**Symptom:** Content wider than viewport
**Common Causes:**
- Fixed-width elements
- Long unbroken text
- Images without max-width

**Fix:**
```css
/* Add to problematic elements */
max-width: 100%;
overflow-x: hidden;

/* For text */
word-break: break-word;
```

### 2. Small Touch Targets

**Symptom:** Buttons/links < 44x44px
**Fix:**
```css
/* Minimum touch target */
.button, .link {
    min-height: 44px;
    min-width: 44px;
    padding: 12px;
}
```

### 3. RTL Layout Issues

**Symptom:** Direction mismatch, reversed layouts
**Fix:**
```css
/* Use logical properties */
.element {
    margin-inline-start: 1rem;  /* NOT margin-left */
    padding-inline-end: 0.5rem; /* NOT padding-right */
    text-align: start;          /* NOT text-left */
}
```

### 4. Browser-Specific CSS Failures

**Symptom:** Layout breaks in specific browser
**Fix:**
```css
/* Add fallbacks */
.element {
    display: flex;
    display: -webkit-flex; /* Safari fallback */
}

/* Or use @supports */
@supports not (backdrop-filter: blur(10px)) {
    .element {
        background: rgba(255, 255, 255, 0.9);
    }
}
```

---

## Test Result Interpretation

### Severity Levels

| Severity | Action Required | Examples |
|----------|-----------------|----------|
| **High** | Fix before release | Horizontal overflow, JS errors, direction mismatch |
| **Medium** | Fix soon | Small touch targets, missing viewport meta |
| **Low** | Nice to fix | SVG rendering warnings, CSS feature unsupported |

### Pass/Fail Criteria

- **Pass:** No high-severity issues
- **Fail:** One or more high-severity issues
- **Error:** Test could not complete (page error, timeout)

### Reading the Reports

```markdown
# In REPORT.md

## Summary
| Metric | Value |
|--------|-------|
| Total Tests | 168 |      <- Total test combinations
| Passed | 160 |            <- No high-severity issues
| Failed | 8 |              <- Has high-severity issues
| Pass Rate | 95.2% |       <- Target: > 90%

## Issues Found
### HORIZONTAL-OVERFLOW (3)
- **Dashboard** (iPhone SE, ar): Horizontal overflow detected: 24px extra width
```

---

## Test URLs Reference

### Base URL
```
https://cmis-test.kazaaz.com
```

### Key Pages to Test

| Category | Path | Auth Required |
|----------|------|---------------|
| Guest | `/login` | No |
| Guest | `/register` | No |
| Auth | `/orgs` | Yes |
| Auth | `/profile` | Yes |
| Org | `/orgs/{id}/dashboard` | Yes |
| Org | `/orgs/{id}/campaigns` | Yes |
| Org | `/orgs/{id}/analytics` | Yes |
| Org | `/orgs/{id}/social` | Yes |
| Settings | `/orgs/{id}/settings/platforms` | Yes |

### Test Organization ID
```
5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a
```

### Test Credentials
```
Email: admin@cmis.test
Password: password
```

---

## Troubleshooting

### Browser Won't Launch

```bash
# Install browser dependencies
npx playwright install-deps

# Or for Puppeteer
npx puppeteer browsers install chrome
```

### Tests Timeout

```javascript
// Increase timeout in test config
const CONFIG = {
    timeout: 60000, // 60 seconds instead of 30
};
```

### Screenshots Are Blank

```bash
# Ensure page fully loads
await page.waitForLoadState('networkidle');
await page.waitForTimeout(1000); // Extra wait for JS
```

### Cookie Not Being Set

```javascript
// Ensure domain matches exactly
await page.setCookie({
    name: 'app_locale',
    value: 'ar',
    domain: 'cmis-test.kazaaz.com', // No https://, no trailing slash
    path: '/',
    secure: true // Required for HTTPS
});
```

---

## Quick Reference Card

```bash
# Mobile responsive (quick)
node scripts/browser-tests/mobile-responsive-comprehensive.js --quick

# Cross-browser (quick)
node scripts/browser-tests/cross-browser-test.js --quick

# Bilingual (full)
node test-bilingual-comprehensive.cjs

# Single page screenshot
npx playwright screenshot https://cmis-test.kazaaz.com/login login.png

# Text dump
lynx -dump https://cmis-test.kazaaz.com/login
```

---

## Related Documentation

- **i18n Guide:** `.claude/knowledge/I18N_RTL_REQUIREMENTS.md`
- **Project Guidelines:** `CLAUDE.md`
- **Test Scripts:** `scripts/browser-tests/README.md`
- **Issue Tracker:** `UNIFIED_ISSUE_TRACKER.md`
