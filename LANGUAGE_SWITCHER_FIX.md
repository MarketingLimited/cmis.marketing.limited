# Language Switcher Fix - RESOLVED ✅

## 🔍 Root Cause Identified

The `app_locale` cookie was being **encrypted by Laravel**, causing the SetLocale middleware to read encrypted values like:
```
eyJpdiI6InBEMzlDeTZLTEErd3dvV0ZWYlNGM2c9PSIsInZhbHVlIjoia1RZd2tSK09xamN0WnFqYzlNaWhBVHJGOHFaMHpuZnhkcUFPSFRwdFEyQlR1VlNoUkhvRzlJT0tpWmlVM3ZtQSIsIm1hYyI6IjQ3MmZkNWUxODkyNDU0NzcyY2M3YmRlYzc0MGE5NzhkMzE2ZmVhZGE3YjIzMTkwZTFkZGUxNjE5YmY0ZjgwNTIiLCJ0YWciOiIifQ==
```

Instead of plain text values: `'ar'` or `'en'`

This caused the validation check to fail:
```php
if (in_array($cookieLocale, ['ar', 'en'])) { // Never matched!
    return $cookieLocale;
}
```

## ✅ Fix Applied

**Modified File:** `bootstrap/app.php` (lines 43-46)

Added cookie encryption exception for `app_locale`:
```php
->withMiddleware(function (Middleware $middleware): void {
    // Use custom EncryptCookies middleware to exclude 'app_locale' from encryption
    $middleware->encryptCookies(except: [
        'app_locale', // Locale cookie must be readable as plain text
    ]);
    // ...
})
```

**What This Does:**
- Prevents Laravel from encrypting the `app_locale` cookie
- Future language switches will set **unencrypted** cookies with plain 'ar' or 'en' values
- SetLocale middleware can now properly validate the cookie value

## 📋 Testing Steps

### Option 1: Quick Test (Recommended)
1. **Clear your browser cookies** for `cmis-test.kazaaz.com`:
   - Press F12 → Application tab → Cookies → cmis-test.kazaaz.com
   - Delete the `app_locale` cookie
   - Refresh the page

2. **Test the language switcher:**
   - Click "العربية" (Arabic)
   - Page should reload in Arabic
   - Check F12 console - should show: `app_locale cookie: ar` (plain text, not encrypted!)
   - Refresh page → **Should stay in Arabic** ✅

### Option 2: Let It Fix Itself Automatically
1. **Just click the language switcher** → Arabic
2. First click may still show English (old encrypted cookie)
3. Second click → **Should switch to Arabic successfully** ✅
4. Page will stay in Arabic on refresh

The old encrypted cookie will be **automatically replaced** with an unencrypted one on the first language switch.

## 🔄 What Changed

### Before Fix:
```
User clicks Arabic
  → LanguageController sets cookie (Laravel encrypts it)
  → Browser stores: eyJpdiI6I... (encrypted)
  → Next request: SetLocale reads encrypted value
  → Validation fails: not 'ar' or 'en'
  → Falls back to browser locale (English)
  → ❌ STAYS IN ENGLISH
```

### After Fix:
```
User clicks Arabic
  → LanguageController sets cookie (NOT encrypted due to exception)
  → Browser stores: ar (plain text)
  → Next request: SetLocale reads 'ar'
  → Validation succeeds: in_array('ar', ['ar', 'en']) ✅
  → Sets locale to Arabic
  → ✅ SWITCHES TO ARABIC
```

## 🎯 Expected Behavior (CORRECT Flow)

1. ✅ Click Arabic switcher
2. ✅ Console shows: `🔄 FORM SUBMITTED - Switching to Arabic...`
3. ✅ Page reloads
4. ✅ Console shows: `app_locale cookie: ar` (PLAIN TEXT, not encrypted)
5. ✅ Current locale: `ar`
6. ✅ Page displays in Arabic
7. ✅ Refresh page → **STAYS in Arabic**
8. ✅ Switch to English → Works perfectly
9. ✅ Switch back to Arabic → Works perfectly

## 🧹 Cleanup Done

- ✅ Caches cleared (`config`, `cache`, `route`, `view`)
- ✅ Cookie encryption exception configured
- ✅ All debugging logs still active (can be removed later if desired)

## 🎉 Status: FIXED

The language switcher should now work correctly.

**To verify immediately:** Clear browser cookies for the site and test the language switcher.

**Or wait for automatic fix:** The next time you switch languages, the old encrypted cookie will be replaced with an unencrypted one.

---

**Date Fixed:** 2025-11-28
**Root Cause:** Laravel cookie encryption
**Solution:** Cookie encryption exception for `app_locale`
