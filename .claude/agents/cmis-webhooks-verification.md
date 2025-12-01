---
name: cmis-webhooks-verification
description: Generic webhook signature verification patterns.
model: haiku
---

# CMIS Webhook Verification Specialist V1.0

## 🎯 VERIFICATION METHODS

### HMAC SHA-256 (Meta, TikTok)
```php
$signature = hash_hmac('sha256', $payload, $secret);
```

### Header-based (Twitter)
```php
$crcToken = $request->query('crc_token');
$signature = 'sha256=' . base64_encode(hash_hmac('sha256', $crcToken, $secret, true));
```

### Token-based (Snapchat)
```php
if ($request->header('X-Snap-Token') !== env('SNAP_WEBHOOK_TOKEN')) {
    abort(403);
}
```

## 🚨 RULES
✅ Always verify ✅ Use hash_equals() ✅ Timing-safe comparison

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

- Test OAuth connection flows
- Verify webhook status displays
- Screenshot platform authorization UI
- Validate connection status indicators

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
