# Browser Testing Scripts

Automated browser testing tools for CMIS application verification.

## 📁 Scripts

### playwright-screenshot.js
Multi-browser screenshot tool using Playwright.

**Usage:**
```bash
node playwright-screenshot.js <url> [output] [browser]
```

**Examples:**
```bash
# Default (Chromium)
node playwright-screenshot.js https://cmis-test.kazaaz.com/

# Custom output name
node playwright-screenshot.js https://cmis-test.kazaaz.com/ dashboard.png

# Firefox
node playwright-screenshot.js https://cmis-test.kazaaz.com/ test.png firefox

# WebKit (Safari)
node playwright-screenshot.js https://cmis-test.kazaaz.com/ test.png webkit
```

**Supported browsers:** `chromium`, `firefox`, `webkit`

---

### puppeteer-test.js
Full page testing with status codes and page information.

**Usage:**
```bash
node puppeteer-test.js <url> [output]
```

**Examples:**
```bash
# Default
node puppeteer-test.js https://cmis-test.kazaaz.com/

# Custom output
node puppeteer-test.js https://cmis-test.kazaaz.com/ test-result.png
```

**Returns:**
- HTTP status code
- Page title
- Full-page screenshot

---

### responsive-test.js
Captures screenshots at multiple viewport sizes.

**Usage:**
```bash
node responsive-test.js <url> [outputPrefix]
```

**Examples:**
```bash
# Default
node responsive-test.js https://cmis-test.kazaaz.com/

# Custom prefix
node responsive-test.js https://cmis-test.kazaaz.com/ dashboard
```

**Viewports tested:**
- **Mobile**: 375x667 (iPhone SE/8)
- **Tablet**: 768x1024 (iPad Mini/iPad)
- **Desktop**: 1920x1080 (Full HD)
- **Widescreen**: 2560x1440 (2K Monitor)

**Output files:**
- `{prefix}-mobile.png`
- `{prefix}-tablet.png`
- `{prefix}-desktop.png`
- `{prefix}-widescreen.png`

---

## 🔧 Installation

### Install Playwright:
```bash
npm install -D playwright
npx playwright install
```

### Install Puppeteer:
```bash
npm install -D puppeteer
```

### Or install both:
```bash
npm install -D playwright puppeteer
npx playwright install
```

---

## 🎯 Common Testing Scenarios

### Test Language Switcher
```bash
# Before fix
node playwright-screenshot.js https://cmis-test.kazaaz.com/ before.png

# After fix
node playwright-screenshot.js https://cmis-test.kazaaz.com/ after.png
```

### Test Arabic (RTL) Layout
```bash
node playwright-screenshot.js https://cmis-test.kazaaz.com/?lang=ar arabic-rtl.png
```

### Test Dashboard Responsiveness
```bash
node responsive-test.js https://cmis-test.kazaaz.com/dashboard dashboard
```

### Verify Form Submission
```bash
node puppeteer-test.js https://cmis-test.kazaaz.com/campaigns/create campaign-form.png
```

### Cross-browser Testing
```bash
# Chrome
node playwright-screenshot.js https://cmis-test.kazaaz.com/ chrome.png chromium

# Firefox
node playwright-screenshot.js https://cmis-test.kazaaz.com/ firefox.png firefox

# Safari (WebKit)
node playwright-screenshot.js https://cmis-test.kazaaz.com/ safari.png webkit
```

---

## 📋 Required for All UI Changes

Before marking any UI task as complete:

1. ✅ Take before/after screenshots
2. ✅ Test responsive layouts (mobile, tablet, desktop)
3. ✅ Verify both languages (Arabic RTL, English LTR)
4. ✅ Cross-browser testing (Chrome, Firefox, Safari)

---

## 🚨 Mandatory Testing Scenarios

**ALWAYS test these with browser automation:**

- Language switcher changes
- RTL/LTR layout modifications
- Responsive design implementations
- Form submissions (especially multi-step)
- Dashboard visualizations
- Campaign creation wizards
- Authentication flows
- API response rendering

---

## 📝 Example Workflow

```bash
# 1. Test current state
node responsive-test.js https://cmis-test.kazaaz.com/campaigns before

# 2. Make code changes
# ... your changes here ...

# 3. Test new state
node responsive-test.js https://cmis-test.kazaaz.com/campaigns after

# 4. Compare screenshots
ls -lh before-*.png after-*.png
```

---

## 🔍 Troubleshooting

### Playwright not found
```bash
npx playwright install
```

### Puppeteer Chrome download fails
```bash
PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true npm install puppeteer
# Use system Chrome instead
```

### Permission errors
```bash
chmod +x scripts/browser-tests/*.js
```

---

**Created:** 2025-11-28
**Purpose:** Browser testing automation for CMIS application
**Maintained by:** Claude Code agents & development team
