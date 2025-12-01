---
name: cmis-snapchat-creatives-video
description: Snapchat video ad specifications (9:16 vertical).
model: haiku
---

# CMIS Snapchat Video Ads Specialist V1.0

## 🎯 VIDEO SPECS
- **Aspect Ratio:** 9:16 (vertical only)
- **Resolution:** 1080 x 1920 px
- **Duration:** 3-180 seconds (recommended: 5-10s)
- **File Size:** Max 1 GB
- **Format:** MP4, MOV

## 🎯 BEST PRACTICES
✅ Sound on by default (use audio)
✅ Fast-paced, attention-grabbing
✅ Gen Z tone/style
✅ Clear branding early

## 🚨 RULES
❌ No landscape videos ✅ Optimize for sound-on viewing

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

- Test Snapchat Ads Manager integration
- Verify Snap ad preview rendering
- Screenshot AR lens campaign setup
- Validate Snapchat pixel implementation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
