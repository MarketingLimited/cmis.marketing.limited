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

## 🌐 Browser Testing

**📖 See:** `.claude/agents/_shared/browser-testing-integration.md`

### When This Agent Should Use Browser Testing

- Test OAuth connection flows
- Verify webhook status displays
- Screenshot platform authorization UI
- Validate connection status indicators

**Documentation**: `CLAUDE.md` → Browser Testing Environment
**Full Guide**: `.claude/knowledge/BROWSER_TESTING_GUIDE.md`

---

**Updated**: 2025-11-28 - Comprehensive Browser Testing Suites
