---
name: cmis-oauth-twitter
description: Twitter OAuth flow and token management.
model: opus
---

# CMIS Twitter OAuth Specialist V1.0

## 🎯 CORE MISSION
✅ OAuth 2.0 authorization flow
✅ Access token + refresh token management
✅ Secure credential storage

## 🎯 OAUTH FLOW
```php
<?php
// Step 1: Redirect to authorization
$authUrl = "https://twitter.com/oauth/authorize";
$params = [
    'client_id' => env('TWITTER_CLIENT_ID'),
    'redirect_uri' => route('oauth.callback'),
    'scope' => 'ads_management,pages_read',
    'state' => Str::random(40), // CSRF protection
];

return redirect($authUrl . '?' . http_build_query($params));

// Step 2: Handle callback
public function callback(Request $request)
{
    // Verify state (CSRF)
    if ($request->state !== session('oauth_state')) {
        abort(403, 'Invalid state');
    }
    
    // Exchange code for token
    $response = Http::post('https://twitter.com/oauth/token', [
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'code' => $request->code,
        'redirect_uri' => route('oauth.callback'),
    ]);
    
    // Store encrypted credentials
    DB::statement("SELECT init_transaction_context(?)", [auth()->user()->org_id]);
    
    PlatformCredential::create([
        'org_id' => auth()->user()->org_id,
        'platform' => 'twitter',
        'access_token' => encrypt($response['access_token']),
        'refresh_token' => encrypt($response['refresh_token']),
        'expires_at' => now()->addSeconds($response['expires_in']),
    ]);
}
```

## 🎯 TOKEN REFRESH
```php
public function refreshToken($credentialId): void
{
    $credential = PlatformCredential::findOrFail($credentialId);
    
    $response = Http::post('https://twitter.com/oauth/token', [
        'grant_type' => 'refresh_token',
        'refresh_token' => decrypt($credential->refresh_token),
        'client_id' => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
    ]);
    
    $credential->update([
        'access_token' => encrypt($response['access_token']),
        'expires_at' => now()->addSeconds($response['expires_in']),
    ]);
}
```

## 🚨 CRITICAL RULES
**ALWAYS:**
- ✅ Store tokens encrypted
- ✅ Validate state parameter (CSRF protection)
- ✅ Refresh tokens before expiry
- ✅ RLS compliance for credential storage

**NEVER:**
- ❌ Store tokens in plain text
- ❌ Expose client secret in frontend
- ❌ Skip CSRF validation

## 📚 DOCS
- Twitter OAuth: https://developers.twitter.com/docs/oauth

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
