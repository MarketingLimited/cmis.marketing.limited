# Phase 5B - Manual Testing Guide
## Publish Modal: Queue Positioning & Platform Warnings

**Test URL:** https://cmis-test.kazaaz.com/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social
**Login:** admin@cmis.test / password
**Features to Test:**
1. Queue Positioning Feature
2. Platform Warnings Banner
3. RTL/LTR Layout Compatibility

---

## 🧪 Testing Checklist

### Prerequisites
- [ ] Login to CMIS at https://cmis-test.kazaaz.com/login
- [ ] Navigate to Social page: `/orgs/5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a/social`
- [ ] Click "Create New Post" button to open the publish modal

---

## Test Suite 1: Queue Positioning Feature (English - LTR)

### Setup
1. **Set Language to English:**
   - Click language switcher in top navigation
   - Select "English" 🇬🇧
   - Confirm page reloads with English text

2. **Open Publish Modal:**
   - Click "Create New Post" button
   - Verify modal opens successfully

### Test Cases

#### ✅ Test 1.1: Queue Radio Button Functionality
- [ ] Locate the publish mode section at bottom of modal
- [ ] Find "Add to queue" radio button
- [ ] **Expected:** Radio button is visible and clickable
- [ ] Click "Add to queue" radio button
- [ ] **Expected:** Radio button becomes selected

**Screenshot:** Take screenshot and name it `test-1.1-queue-radio-en.png`

#### ✅ Test 1.2: Queue Dropdown Visibility
- [ ] After selecting "Add to queue", observe the UI
- [ ] **Expected:** A dropdown appears below the radio button with smooth slide-down animation
- [ ] **Expected:** Dropdown has gradient background (blue-white gradient)
- [ ] **Expected:** Dropdown is indented slightly to the right

**Screenshot:** Take screenshot and name it `test-1.2-queue-dropdown-visible-en.png`

#### ✅ Test 1.3: Queue Dropdown Options
- [ ] Click the queue position dropdown
- [ ] **Expected:** Dropdown expands showing 3 options:
  - "Queue Next - Add to front of queue"
  - "Queue Available - Next available slot" (default selected)
  - "Queue Last - Add to end of queue"
- [ ] Verify all 3 options are present
- [ ] Verify text is clear and descriptive

**Screenshot:** Take screenshot and name it `test-1.3-dropdown-options-en.png`

#### ✅ Test 1.4: Option Selection - Queue Next
- [ ] Select "Queue Next" from dropdown
- [ ] **Expected:** Option is selected
- [ ] **Expected:** Dropdown shows "Queue Next - Add to front of queue"

**Screenshot:** Take screenshot and name it `test-1.4-queue-next-en.png`

#### ✅ Test 1.5: Option Selection - Queue Last
- [ ] Select "Queue Last" from dropdown
- [ ] **Expected:** Option is selected
- [ ] **Expected:** Dropdown shows "Queue Last - Add to end of queue"

**Screenshot:** Take screenshot and name it `test-1.5-queue-last-en.png`

#### ✅ Test 1.6: Dropdown Conditional Visibility
- [ ] Click "Publish Now" radio button (change from "Add to queue")
- [ ] **Expected:** Queue dropdown smoothly slides up and disappears
- [ ] Click "Schedule" radio button
- [ ] **Expected:** Queue dropdown remains hidden
- [ ] Click "Add to queue" again
- [ ] **Expected:** Queue dropdown slides down and reappears

**Screenshot:** Take screenshot showing dropdown hidden when "Publish Now" selected: `test-1.6-dropdown-hidden-en.png`

---

## Test Suite 2: Platform Warnings Banner (English - LTR)

### Setup
1. **Ensure English language is active**
2. **Ensure publish modal is open**
3. **Ensure modal is in clean state** (refresh page if needed)

### Test Cases

#### ✅ Test 2.1: No Warning Initially
- [ ] Observe the top of the modal (below header, above tabs)
- [ ] **Expected:** No orange warning banner is visible
- [ ] **Expected:** Clean layout with just header and tabs

**Screenshot:** Take screenshot and name it `test-2.1-no-warning-en.png`

#### ✅ Test 2.2: Customize Platform Content (Instagram)
- [ ] Select at least one Instagram account from the accounts list
- [ ] Type some text in the "Global Content" textarea, e.g., "This is my global post"
- [ ] Click on the "Instagram" tab in the platform tabs
- [ ] Type different text in the Instagram textarea, e.g., "This is Instagram-specific content"
- [ ] Wait 1-2 seconds

**Screenshot:** Take screenshot and name it `test-2.2-instagram-customized-en.png`

#### ✅ Test 2.3: Warning Banner Appears
- [ ] Observe the top of the modal
- [ ] **Expected:** Orange/yellow gradient warning banner appears with smooth slide-down animation
- [ ] **Expected:** Banner shows:
  - Orange warning icon (circle with exclamation)
  - Title: "Platform Customizations Detected"
  - Warning message: "Instagram content has been customized and differs from the global content"
  - Instagram icon (blue badge) next to the warning
  - "Reset Customizations" button on the right

**Screenshot:** Take screenshot and name it `test-2.3-warning-banner-en.png`

#### ✅ Test 2.4: Multiple Platform Warnings
- [ ] Click on "Facebook" tab
- [ ] Type different text in Facebook textarea, e.g., "Facebook-specific post"
- [ ] Wait 1-2 seconds
- [ ] **Expected:** Warning banner now shows 2 warnings:
  - Instagram customization warning
  - Facebook customization warning
- [ ] Both should have their respective platform icons

**Screenshot:** Take screenshot and name it `test-2.4-multiple-warnings-en.png`

#### ✅ Test 2.5: Reset Customizations Button
- [ ] Locate the "Reset Customizations" button on the warning banner
- [ ] **Expected:** Button is white with orange border and text
- [ ] **Expected:** Button shows undo icon
- [ ] Hover over button
- [ ] **Expected:** Button background changes to light orange on hover

**Screenshot:** Take screenshot showing hover state: `test-2.5-reset-button-hover-en.png`

#### ✅ Test 2.6: Reset Functionality
- [ ] Click "Reset Customizations" button
- [ ] **Expected:** Confirmation dialog appears asking: "Reset all platform customizations to global content? This cannot be undone."
- [ ] Click "OK" to confirm
- [ ] **Expected:** Warning banner smoothly slides up and disappears
- [ ] **Expected:** Success notification appears: "All customizations have been reset to global content"
- [ ] Navigate to Instagram tab
- [ ] **Expected:** Instagram textarea is now empty (content reset)
- [ ] Navigate to Facebook tab
- [ ] **Expected:** Facebook textarea is now empty (content reset)

**Screenshot:** Take screenshot after reset showing no warnings: `test-2.6-warnings-reset-en.png`

---

## Test Suite 3: Arabic (RTL) Testing

### Setup
1. **Switch Language to Arabic:**
   - Click language switcher in top navigation
   - Select "العربية" 🇸🇦
   - Confirm page reloads with Arabic text
   - **Expected:** Page direction changes to RTL (text flows right-to-left)

2. **Open Publish Modal:**
   - Click "إنشاء منشور جديد" button
   - Verify modal opens successfully

### Test Cases

#### ✅ Test 3.1: Queue Positioning in Arabic
- [ ] Scroll to bottom of modal
- [ ] Locate publish mode radio buttons
- [ ] **Expected:** Radio buttons are aligned to the right (RTL layout)
- [ ] Click "إضافة إلى الطابور" (Add to queue) radio button
- [ ] **Expected:** Queue dropdown appears with smooth animation
- [ ] **Expected:** Dropdown is aligned to the right
- [ ] Click dropdown to expand
- [ ] **Expected:** 3 options visible with Arabic text:
  - "التالي في الطابور - إضافة في المقدمة"
  - "المكان المتاح - المكان المتاح التالي"
  - "الأخير في الطابور - إضافة في النهاية"

**Screenshots:**
- `test-3.1-queue-dropdown-ar.png` - Dropdown visible
- `test-3.1-queue-options-ar.png` - Options expanded

#### ✅ Test 3.2: Platform Warnings in Arabic
- [ ] Type text in global content: "هذا منشوري العام"
- [ ] Select Instagram tab
- [ ] Type different text: "محتوى مخصص لإنستغرام"
- [ ] Wait 1-2 seconds
- [ ] **Expected:** Warning banner appears at top
- [ ] **Expected:** Banner is right-aligned (RTL)
- [ ] **Expected:** Title shows: "تم اكتشاف تخصيصات للمنصات"
- [ ] **Expected:** Reset button is on the left side (RTL flip)
- [ ] **Expected:** Warning message in Arabic with Instagram icon

**Screenshots:**
- `test-3.2-warning-banner-ar.png` - Warning banner in Arabic
- `test-3.2-warning-layout-ar.png` - Full modal showing RTL layout

#### ✅ Test 3.3: Reset in Arabic
- [ ] Click "إعادة تعيين التخصيصات" button
- [ ] **Expected:** Confirmation in Arabic: "إعادة تعيين جميع تخصيصات المنصات إلى المحتوى العام؟ لا يمكن التراجع عن ذلك."
- [ ] Click "موافق" (OK)
- [ ] **Expected:** Warning disappears
- [ ] **Expected:** Success message in Arabic: "تم إعادة تعيين جميع التخصيصات إلى المحتوى العام"

**Screenshot:** `test-3.3-reset-success-ar.png`

#### ✅ Test 3.4: RTL Layout Visual Verification
- [ ] Verify overall modal layout is mirrored correctly:
  - [ ] Modal header text is right-aligned
  - [ ] Close button (X) is on the left side
  - [ ] Sidebar (accounts) is on the right side
  - [ ] Main content is on the left side
  - [ ] All buttons and inputs are right-aligned
  - [ ] Padding and margins respect RTL (me- instead of ml-)

**Screenshot:** `test-3.4-rtl-full-modal-ar.png` - Full page screenshot

---

## Test Suite 4: Cross-Language Consistency

### Test Cases

#### ✅ Test 4.1: Feature Parity
- [ ] Compare English and Arabic versions
- [ ] **Expected:** Both languages have identical features:
  - Queue positioning dropdown
  - Platform warnings banner
  - Reset button
  - All animations
- [ ] **Expected:** No features are missing in either language

#### ✅ Test 4.2: Translation Quality
- [ ] Review all new translation keys:
  - [ ] "Queue Next" → "التالي في الطابور"
  - [ ] "Queue Available" → "المكان المتاح"
  - [ ] "Queue Last" → "الأخير في الطابور"
  - [ ] "Platform Customizations Detected" → "تم اكتشاف تخصيصات للمنصات"
  - [ ] "Reset Customizations" → "إعادة تعيين التخصيصات"
- [ ] **Expected:** Translations are accurate and natural
- [ ] **Expected:** No English text appears in Arabic version
- [ ] **Expected:** No hardcoded text

---

## Test Suite 5: Mobile Responsive Testing

### Setup
1. **Use Browser DevTools:**
   - Open Chrome/Firefox DevTools (F12)
   - Click "Toggle Device Toolbar" (Ctrl+Shift+M)
   - Or test on actual mobile device

### Test Cases

#### ✅ Test 5.1: iPhone SE (375x667) - English
- [ ] Set viewport to iPhone SE (375 x 667)
- [ ] Open publish modal
- [ ] Click "Add to queue"
- [ ] **Expected:** Queue dropdown is fully visible and tappable
- [ ] **Expected:** No horizontal overflow
- [ ] **Expected:** Dropdown text is readable (not truncated)

**Screenshot:** `test-5.1-iphone-se-en.png`

#### ✅ Test 5.2: iPhone SE (375x667) - Arabic
- [ ] Switch to Arabic
- [ ] Set viewport to iPhone SE
- [ ] Open publish modal
- [ ] Add platform customization to trigger warning
- [ ] **Expected:** Warning banner fits within screen width
- [ ] **Expected:** Reset button is fully visible and tappable
- [ ] **Expected:** Warning text wraps properly

**Screenshot:** `test-5.2-iphone-se-ar.png`

#### ✅ Test 5.3: iPad Mini (768x1024) - Landscape
- [ ] Set viewport to iPad Mini landscape (1024 x 768)
- [ ] Test both queue dropdown and warnings in English
- [ ] Test both features in Arabic
- [ ] **Expected:** All elements scale appropriately
- [ ] **Expected:** No layout breaks

**Screenshots:**
- `test-5.3-ipad-landscape-en.png`
- `test-5.3-ipad-landscape-ar.png`

#### ✅ Test 5.4: Galaxy S21 (360x800) - Portrait
- [ ] Set viewport to Galaxy S21 (360 x 800)
- [ ] Test queue positioning
- [ ] Test platform warnings
- [ ] **Expected:** Minimum touch target size is 44x44px for all interactive elements
- [ ] **Expected:** Text remains readable (min 12px font size)

**Screenshot:** `test-5.4-galaxy-s21-en.png`

---

## Test Suite 6: Cross-Browser Testing

### Test Cases

#### ✅ Test 6.1: Chrome/Edge (Chromium)
- [ ] Open in Chrome or Edge
- [ ] Test all features in English
- [ ] Test all features in Arabic
- [ ] **Expected:** All features work identically
- [ ] **Expected:** Animations are smooth

#### ✅ Test 6.2: Firefox
- [ ] Open in Firefox
- [ ] Test all features in English
- [ ] Test all features in Arabic
- [ ] **Expected:** All features work identically
- [ ] **Expected:** Gradient backgrounds render correctly

#### ✅ Test 6.3: Safari (if available)
- [ ] Open in Safari
- [ ] Test all features in English
- [ ] Test all features in Arabic
- [ ] **Expected:** All features work identically

---

## 📸 Screenshot Organization

Save all screenshots in: `/home/cmis-test/public_html/test-results/publish-modal-phase5b/screenshots/`

Create subdirectories:
```
screenshots/
├── english/
│   ├── queue-positioning/
│   └── platform-warnings/
├── arabic/
│   ├── queue-positioning/
│   └── platform-warnings/
└── mobile/
    ├── english/
    └── arabic/
```

---

## ✅ Sign-Off Checklist

### Feature Completion
- [ ] Queue positioning feature works in English (LTR)
- [ ] Queue positioning feature works in Arabic (RTL)
- [ ] Platform warnings feature works in English (LTR)
- [ ] Platform warnings feature works in Arabic (RTL)
- [ ] All translations are accurate and natural
- [ ] No hardcoded text found
- [ ] No English text in Arabic version
- [ ] No Arabic text in English version

### Layout & Responsiveness
- [ ] RTL layout is properly mirrored
- [ ] LTR layout is correct
- [ ] Mobile responsive on iPhone SE (375px)
- [ ] Mobile responsive on Galaxy S21 (360px)
- [ ] Tablet responsive on iPad Mini (768px)
- [ ] No horizontal overflow on any device
- [ ] All touch targets meet minimum 44x44px size

### Cross-Browser Compatibility
- [ ] Chrome/Edge: All features working
- [ ] Firefox: All features working
- [ ] Safari: All features working (if tested)

### User Experience
- [ ] Animations are smooth
- [ ] Transitions work correctly
- [ ] Confirmation dialogs appear as expected
- [ ] Success notifications display correctly
- [ ] Error states are handled gracefully
- [ ] Loading states work properly

### Performance
- [ ] Modal opens quickly (<500ms)
- [ ] No JavaScript errors in console
- [ ] No CSS layout shifts
- [ ] Dropdown animations are smooth (60fps)

---

## 🐛 Bug Report Template

If you find any issues during testing, use this template:

```markdown
### Bug: [Short Description]

**Environment:**
- Browser: [Chrome 120 / Firefox 119 / Safari 17]
- Device: [Desktop / iPhone SE / iPad Mini]
- Language: [English / Arabic]
- URL: https://cmis-test.kazaaz.com/orgs/.../social

**Steps to Reproduce:**
1. [First step]
2. [Second step]
3. [Third step]

**Expected Behavior:**
[What should happen]

**Actual Behavior:**
[What actually happened]

**Screenshot:**
![Bug Screenshot](path/to/screenshot.png)

**Severity:**
- [ ] Critical (Feature completely broken)
- [ ] High (Major functionality impaired)
- [ ] Medium (Minor issue, has workaround)
- [ ] Low (Cosmetic issue)

**Console Errors:**
```
[Any JavaScript errors from browser console]
```
```

---

## 📊 Test Results Summary

After completing all tests, fill in this summary:

**Test Date:** _____________
**Tester Name:** _____________
**Total Test Cases:** 30+
**Passed:** _____ / 30+
**Failed:** _____ / 30+
**Blocked:** _____ / 30+

**Overall Status:**
- [ ] ✅ All tests passed - Ready for production
- [ ] ⚠️ Minor issues found - Review required
- [ ] ❌ Major issues found - Fix required

**Notes:**
_________________________________________
_________________________________________
_________________________________________

---

## 🎯 Next Steps

After successful testing:

1. **Update Documentation:**
   - Mark Phase 5B features as tested and verified
   - Update PHASE_5B_IMPLEMENTATION_SUMMARY.md with test results

2. **Deploy to Production:**
   - Merge feature branch to main
   - Deploy to production environment
   - Monitor for any issues

3. **User Communication:**
   - Announce new features to users
   - Create user guide for queue positioning
   - Create user guide for understanding platform warnings

---

**Document Version:** 1.0
**Last Updated:** 2025-11-29
**Prepared By:** Claude Code
**Status:** Ready for Testing
