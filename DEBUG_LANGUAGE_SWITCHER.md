# Language Switcher Debug Guide

---
## ⚠️ UPDATE: ISSUE RESOLVED! ✅

**The language switcher issue has been fixed!**

**Root Cause:** Laravel was encrypting the `app_locale` cookie, causing validation to fail.

**Solution:** Added cookie encryption exception in `bootstrap/app.php`.

**See:** `LANGUAGE_SWITCHER_FIX.md` for complete details and testing instructions.

**Quick Test:** Visit `/test-locale-cookie.php` to verify your cookie is unencrypted.

---

## 🔍 Debug Logging Added (Historical Reference)

I've added comprehensive logging to track exactly what's happening with cookies and locale detection.

### Client-Side Debug (Browser Console):

**What you'll see:**
```javascript
[LANGUAGE SWITCHER] ========== DEBUG START ==========
[LANGUAGE SWITCHER] Current locale: en
[LANGUAGE SWITCHER] All cookies: (all browser cookies)
[LANGUAGE SWITCHER] app_locale cookie: (value or null)
[LANGUAGE SWITCHER] ========== DEBUG END ==========
```

**When you click language switcher:**
```javascript
[LANGUAGE SWITCHER] 🔄 FORM SUBMITTED - Switching to Arabic...
[LANGUAGE SWITCHER] Form action: https://cmis-test.kazaaz.com/language/ar
[LANGUAGE SWITCHER] Cookies BEFORE submit: (cookies before form submission)
```

### Server-Side Debug (Laravel Logs):

**Language Switch Request:**
```
===== LANGUAGE SWITCH START =====
🔄 Requested locale: ar
📍 Current locale before switch: en
🆔 Session ID: xxxxx
👤 Is authenticated: yes/no
🍪 Incoming cookies: {...}
🍪 app_locale cookie from request: (value or NOT SET)
...
🍪 SETTING COOKIE: app_locale=ar
🍪 Cookie params: domain=.kazaaz.com, secure=true, httpOnly=false, duration=30days
✅ Response created with cookie attached
===== LANGUAGE SWITCH END =====
```

**SetLocale Middleware (Every Request):**
```
===== SET LOCALE MIDDLEWARE START =====
🌐 Request URL: https://cmis-test.kazaaz.com/...
🆔 Session ID: xxxxx
🍪 All incoming cookies: {...}
🍪 app_locale cookie: (value or NOT SET)
--- Determining locale ---
👤 User is authenticated/not authenticated
🍪 Cookie has locale: ar  (or NOT SET)
✅ Using locale from cookie: ar
✅ Determined locale: ar
App locale set to: ar
===== SET LOCALE MIDDLEWARE END =====
```

## 📋 Testing Steps

### Step 1: Clear Everything
```bash
# In browser:
1. Press Ctrl+Shift+Delete
2. Clear ALL cookies and cache
3. Close all tabs for cmis-test.kazaaz.com
4. Open a NEW incognito window
```

### Step 2: Open Browser Console
```
1. Press F12 (Developer Tools)
2. Go to "Console" tab
3. Keep it open during all testing
```

### Step 3: Test Sequence

1. **Visit:** https://cmis-test.kazaaz.com
   - Check console for `[LANGUAGE SWITCHER]` debug output
   - Note the current locale
   - Note if app_locale cookie exists

2. **Log in** with your credentials
   - Check console again after login
   - Note session ID and auth status

3. **Click language switcher** → Select "العربية" (Arabic)
   - Watch console for `🔄 FORM SUBMITTED` message
   - Note cookies BEFORE submit
   - Wait for page reload

4. **After page reload:**
   - Check console for new `[LANGUAGE SWITCHER]` init message
   - Check current locale - **should be 'ar'**
   - Check app_locale cookie - **should be 'ar'**

5. **Refresh the page** (F5 or Ctrl+R)
   - Check console again
   - Locale should STILL be 'ar'

6. **Check Browser Cookies Manually:**
   - F12 → Application tab → Cookies → https://cmis-test.kazaaz.com
   - Look for `app_locale` cookie
   - Note its value, domain, expiry

### Step 4: Collect Debug Info

**From Browser Console, copy and send me:**
```
1. Initial page load console output
2. After clicking Arabic - console output
3. After page reload - console output
4. Screenshot of Application → Cookies showing app_locale cookie details
```

**From Server Logs, send me:**
```bash
# Run this command and send me the output:
tail -200 storage/logs/laravel.log | grep -E "(LANGUAGE SWITCH|SET LOCALE|app_locale|Determined locale)"
```

## 🎯 Expected Behavior

### ✅ CORRECT Flow:

1. Click Arabic switcher
2. Console shows: `🔄 FORM SUBMITTED - Switching to Arabic...`
3. Page reloads
4. Console shows: `Current locale: ar`
5. Console shows: `app_locale cookie: ar`
6. Server logs show: `🍪 SETTING COOKIE: app_locale=ar`
7. Server logs show: `✅ Using locale from cookie: ar`
8. Page displays in Arabic
9. Refresh page → STAYS in Arabic

### ❌ WRONG Flow (Current Issue):

1. Click Arabic switcher
2. Console shows: `🔄 FORM SUBMITTED - Switching to Arabic...`
3. Page reloads
4. Console shows: `Current locale: en` ← **WRONG!**
5. Console shows: `app_locale cookie: (empty or missing)` ← **PROBLEM!**
6. Server logs show: Setting cookie but cookie not arriving in next request
7. Page stays in English

## 🔎 What We're Looking For:

**Key Questions:**
1. Is the `app_locale` cookie being SET by the server? (check server logs)
2. Is the `app_locale` cookie visible in the browser? (check Application → Cookies)
3. Is the cookie being SENT BACK in the next request? (check server logs for incoming cookies)
4. What domain is the cookie set for? (should be `.kazaaz.com`)
5. Is the cookie secure flag causing issues? (should be true for HTTPS)

**Likely Issues:**
- Cookie domain mismatch (`.kazaaz.com` vs `cmis-test.kazaaz.com`)
- Secure cookie not working with HTTPS configuration
- SameSite attribute blocking the cookie
- Browser blocking third-party cookies
- Session interference overriding locale cookie

## 📝 Information Needed

Please provide:

1. **Browser Console Output** (copy full console, especially the LANGUAGE SWITCHER logs)
2. **Server Logs** (run the tail command above)
3. **Cookie Details** (screenshot from F12 → Application → Cookies)
4. **Browser Version** (Chrome/Firefox/Safari and version number)
5. **Any Error Messages** (console errors or server errors)

Once I have this information, I can identify exactly where the cookie is being lost!

---

**Debug Mode Active - Ready for Testing**
