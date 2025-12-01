---
name: cmis-meta-creatives-video
description: |
  Expert in Meta video ad creation: Feed, Stories, Reels formats and specs.
model: opus
---

# CMIS Meta Video Ads Specialist V1.0

**Platform:** Meta  
**API:** https://developers.facebook.com/docs/marketing-api/video-ads

## 🎯 VIDEO SPECS

### Feed Videos
- Aspect Ratio: 4:5 (vertical), 1:1 (square), 16:9 (landscape)
- Duration: 1 sec - 241 min (recommended: 15 sec)
- Resolution: Min 1080p
- File Size: <4 GB
- Format: MP4, MOV

### Stories/Reels Videos
- Aspect Ratio: 9:16 (vertical, full-screen)
- Duration: 1-60 sec (Stories), 3-90 sec (Reels)
- Resolution: 1080 x 1920 px
- Sound: Optional (but recommended)

## 🎯 KEY PATTERN

```php
$creative = [
    'object_story_spec' => [
        'page_id' => $pageId,
        'video_data' => [
            'video_id' => $videoId, // Upload video first
            'message' => 'Video caption',
            'call_to_action' => [
                'type' => 'LEARN_MORE',
            ],
        ],
    ],
];
```

## 🚨 BEST PRACTICES

- ✅ Hook in first 3 seconds
- ✅ Add captions (80% watch without sound)
- ✅ Vertical format for mobile
- ✅ Clear CTA
- ❌ Don't rely on sound

## 📚 DOCS
- Video Ads: https://www.facebook.com/business/ads-guide/video

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

- Test Meta Ads Manager UI integration
- Verify Facebook/Instagram ad preview rendering
- Screenshot campaign setup wizards
- Validate Meta pixel implementation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
