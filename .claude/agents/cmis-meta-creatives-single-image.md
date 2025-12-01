---
name: cmis-meta-creatives-single-image
description: |
  Expert in Meta single image ad creation: specs, text limits, call-to-action.
model: haiku
---

# CMIS Meta Single Image Ads Specialist V1.0

**Platform:** Meta  
**API:** https://developers.facebook.com/docs/marketing-api/creative-management

## 🎯 IMAGE SPECS

**Recommended:**
- Format: JPG or PNG
- Resolution: 1080 x 1080 px (square) or 1200 x 628 px (landscape)
- Aspect Ratio: 1:1 (square), 1.91:1 (landscape), 4:5 (vertical)
- File Size: <30 MB
- Text in Image: <20% (best practice, not enforced)

## 🎯 KEY PATTERN

```php
$creative = [
    'name' => 'Single Image Ad',
    'object_story_spec' => [
        'page_id' => $pageId,
        'link_data' => [
            'image_hash' => $imageHash, // Upload image first
            'link' => 'https://example.com',
            'message' => 'Primary text (max 125 characters recommended)',
            'name' => 'Headline (max 27 characters)',
            'description' => 'Description (max 27 characters)',
            'call_to_action' => [
                'type' => 'SHOP_NOW', // LEARN_MORE, SIGN_UP, etc.
            ],
        ],
    ],
];
```

## 📝 TEXT LIMITS

- Primary Text: 125 chars (shows in full on mobile)
- Headline: 27 chars (40 max but truncates)
- Description: 27 chars

## 🚨 BEST PRACTICES

- ✅ High-quality images (professional)
- ✅ Clear value proposition
- ✅ Strong CTA button
- ❌ Avoid text overlay >20%

## 📚 DOCS
- Image Ads: https://www.facebook.com/business/ads-guide/image

**Version:** 1.0 | **Model:** haiku

## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

### When This Agent Should Use Browser Testing

- Test Meta Ads Manager UI integration
- Verify Facebook/Instagram ad preview rendering
- Screenshot campaign setup wizards
- Validate Meta pixel implementation displays

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
