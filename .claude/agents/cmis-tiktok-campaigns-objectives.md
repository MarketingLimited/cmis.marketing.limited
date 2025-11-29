---
name: cmis-tiktok-campaigns-objectives
description: TikTok campaign objectives (REACH, TRAFFIC, CONVERSIONS, APP, SALES).
model: haiku
---

# CMIS TikTok Campaign Objectives Specialist V1.0
**API:** https://business-api.tiktok.com/

## 🎯 OBJECTIVES
- REACH: Maximum impressions
- TRAFFIC: Drive to website/app
- VIDEO_VIEWS: Video engagement
- ENGAGEMENT: Likes, comments, shares
- APP_INSTALL: Mobile app downloads
- CONVERSIONS: Website conversions (Purchase, Lead)
- PRODUCT_SALES: TikTok Shop sales

## 🚨 RULES
✅ Match objective to goal ✅ Install TikTok Pixel for conversions

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

- Test TikTok Ads Manager integration
- Verify video ad preview rendering
- Screenshot campaign creation flows
- Validate TikTok pixel implementation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
