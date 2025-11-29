# Functional Interaction Testing Report

**Date:** 2025-11-28T20:55:56.235Z
**Total Tests:** 36

## Summary

- ✅ Passed: 2 (5.6%)
- ❌ Failed: 24 (66.7%)
- ⏭️  Skipped: 10 (27.8%)

## Test Results


### 1.1. Language Switcher - Click language switcher to open dropdown

- **Action:** click_language_switcher
- **Selector:** `button:has-text("English"), button:has-text("العربية")`
- **Expected:** Dropdown menu appears with language options
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("English"), button:has-text("العربية")



### 1.1. Language Switcher - Click language switcher to open dropdown

- **Action:** click_language_switcher
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 1.2. Language Switcher - Switch language to Arabic

- **Action:** switch_to_arabic
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 2.1. Campaign Creation Flow - Select Meta platform

- **Action:** select_platform_meta
- **Selector:** `input[type="radio"][value="meta"], label:has-text("Meta")`
- **Expected:** Meta platform card selected
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: input[type="radio"][value="meta"], label:has-text("Meta")



### 2.1. Campaign Creation Flow - Select Meta platform

- **Action:** select_platform_meta
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 2.2. Campaign Creation Flow - Click Next button to proceed to step 2

- **Action:** click_next
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 3.1. User Settings Form - Fill display name field

- **Action:** fill_display_name
- **Selector:** `input[name="display_name"], input[placeholder*="name"]`
- **Expected:** Display name field filled
- **Status:** ✅ PASSED

- **Screenshot:** test-results/functional-interactions/screenshots/03-user-settings-form-01.png


### 3.1. User Settings Form - Fill display name field

- **Action:** fill_display_name
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 3.2. User Settings Form - Change language dropdown

- **Action:** change_language
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 3.3. User Settings Form - Click Save Changes button

- **Action:** click_save
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 4.1. Search Functionality - Type in search field

- **Action:** fill_search
- **Selector:** `input[placeholder*="Search"], input[type="search"]`
- **Expected:** Search field accepts input
- **Status:** ✅ PASSED

- **Screenshot:** test-results/functional-interactions/screenshots/04-search-functionality-01.png


### 4.1. Search Functionality - Type in search field

- **Action:** fill_search
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 4.2. Search Functionality - Verify search results or empty state

- **Action:** verify_search_results
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 5.1. Filter Dropdowns - Click status filter dropdown

- **Action:** click_status_filter
- **Selector:** `select:has-text("All Statuses"), button:has-text("All Statuses")`
- **Expected:** Status filter dropdown opens
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: select:has-text("All Statuses"), button:has-text("All Statuses")



### 5.1. Filter Dropdowns - Click status filter dropdown

- **Action:** click_status_filter
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 5.2. Filter Dropdowns - Click Filter button

- **Action:** click_filter_button
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 6.1. Modal Interactions - Create Campaign - Click New Campaign button

- **Action:** click_new_campaign
- **Selector:** `button:has-text("New Campaign"), a:has-text("New Campaign")`
- **Expected:** Navigates to campaign creation page or opens modal
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("New Campaign"), a:has-text("New Campaign")



### 6.1. Modal Interactions - Create Campaign - Click New Campaign button

- **Action:** click_new_campaign
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 7.1. Social Post Creation - Click New Post button

- **Action:** click_new_post
- **Selector:** `button:has-text("New Post"), button:has-text("Create New Post")`
- **Expected:** Opens post creation modal or page
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("New Post"), button:has-text("Create New Post")



### 7.1. Social Post Creation - Click New Post button

- **Action:** click_new_post
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 8.1. Creative Asset Upload - Click Upload Asset button

- **Action:** click_upload
- **Selector:** `button:has-text("Upload Asset")`
- **Expected:** Opens file upload dialog or modal
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("Upload Asset")



### 8.1. Creative Asset Upload - Click Upload Asset button

- **Action:** click_upload
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 9.1. View Toggles - Click list view toggle

- **Action:** click_list_view
- **Selector:** `button[aria-label*="list"], button:has-text("List")`
- **Expected:** Changes to list view layout
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button[aria-label*="list"], button:has-text("List")



### 9.1. View Toggles - Click list view toggle

- **Action:** click_list_view
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 9.2. View Toggles - Click grid view toggle

- **Action:** click_grid_view
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 10.1. Sidebar Navigation - Click Campaigns in sidebar

- **Action:** click_campaigns_nav
- **Selector:** `a[href*="/campaigns"]:not([href*="create"])`
- **Expected:** Navigates to campaigns page
- **Status:** ❌ FAILED
- **Error:** Node is either not clickable or not an Element
- **Screenshot:** test-results/functional-interactions/screenshots/ERROR-10-sidebar-navigation-01.png


### 10.1. Sidebar Navigation - Click Campaigns in sidebar

- **Action:** click_campaigns_nav
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 10.2. Sidebar Navigation - Click Analytics in sidebar

- **Action:** click_analytics_nav
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 10.3. Sidebar Navigation - Click Social Media in sidebar

- **Action:** click_social_nav
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 11.1. Refresh Button - Click Refresh button

- **Action:** click_refresh
- **Selector:** `button:has-text("Refresh")`
- **Expected:** Page data refreshes
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("Refresh")



### 11.1. Refresh Button - Click Refresh button

- **Action:** click_refresh
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 12.1. Platform Connection - Click Google Connect button

- **Action:** click_google_connect
- **Selector:** `button:has-text("Connect"):has-text("Google"), button:has-text("ربط"):has-text("جوجل")`
- **Expected:** Opens Google OAuth flow or shows connection modal
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("Connect"):has-text("Google"), button:has-text("ربط"):has-text("جوجل")



### 12.1. Platform Connection - Click Google Connect button

- **Action:** click_google_connect
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 13.1. Tab Switching - Click KPI Dashboard tab

- **Action:** click_kpi_tab
- **Selector:** `button:has-text("KPI Dashboard")`
- **Expected:** Switches to KPI Dashboard view
- **Status:** ⏭️  SKIPPED
- **Error:** Element not found: button:has-text("KPI Dashboard")



### 13.1. Tab Switching - Click KPI Dashboard tab

- **Action:** click_kpi_tab
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



### 13.2. Tab Switching - Click Real-Time Dashboard tab

- **Action:** click_realtime_tab
- **Selector:** `undefined`
- **Expected:** undefined
- **Status:** ❌ FAILED
- **Error:** Page load failed: page.waitForTimeout is not a function



## Functional vs Non-Functional Elements

### ✅ Functional Elements (Passed Tests)
- User Settings Form: Fill display name field
- Search Functionality: Type in search field

### ❌ Non-Functional Elements (Failed Tests)
- Language Switcher: Click language switcher to open dropdown - Page load failed: page.waitForTimeout is not a function
- Language Switcher: Switch language to Arabic - Page load failed: page.waitForTimeout is not a function
- Campaign Creation Flow: Select Meta platform - Page load failed: page.waitForTimeout is not a function
- Campaign Creation Flow: Click Next button to proceed to step 2 - Page load failed: page.waitForTimeout is not a function
- User Settings Form: Fill display name field - Page load failed: page.waitForTimeout is not a function
- User Settings Form: Change language dropdown - Page load failed: page.waitForTimeout is not a function
- User Settings Form: Click Save Changes button - Page load failed: page.waitForTimeout is not a function
- Search Functionality: Type in search field - Page load failed: page.waitForTimeout is not a function
- Search Functionality: Verify search results or empty state - Page load failed: page.waitForTimeout is not a function
- Filter Dropdowns: Click status filter dropdown - Page load failed: page.waitForTimeout is not a function
- Filter Dropdowns: Click Filter button - Page load failed: page.waitForTimeout is not a function
- Modal Interactions - Create Campaign: Click New Campaign button - Page load failed: page.waitForTimeout is not a function
- Social Post Creation: Click New Post button - Page load failed: page.waitForTimeout is not a function
- Creative Asset Upload: Click Upload Asset button - Page load failed: page.waitForTimeout is not a function
- View Toggles: Click list view toggle - Page load failed: page.waitForTimeout is not a function
- View Toggles: Click grid view toggle - Page load failed: page.waitForTimeout is not a function
- Sidebar Navigation: Click Campaigns in sidebar - Node is either not clickable or not an Element
- Sidebar Navigation: Click Campaigns in sidebar - Page load failed: page.waitForTimeout is not a function
- Sidebar Navigation: Click Analytics in sidebar - Page load failed: page.waitForTimeout is not a function
- Sidebar Navigation: Click Social Media in sidebar - Page load failed: page.waitForTimeout is not a function
- Refresh Button: Click Refresh button - Page load failed: page.waitForTimeout is not a function
- Platform Connection: Click Google Connect button - Page load failed: page.waitForTimeout is not a function
- Tab Switching: Click KPI Dashboard tab - Page load failed: page.waitForTimeout is not a function
- Tab Switching: Click Real-Time Dashboard tab - Page load failed: page.waitForTimeout is not a function

### ⏭️  Not Found Elements (Skipped Tests)
- Language Switcher: Click language switcher to open dropdown - Element not found: button:has-text("English"), button:has-text("العربية")
- Campaign Creation Flow: Select Meta platform - Element not found: input[type="radio"][value="meta"], label:has-text("Meta")
- Filter Dropdowns: Click status filter dropdown - Element not found: select:has-text("All Statuses"), button:has-text("All Statuses")
- Modal Interactions - Create Campaign: Click New Campaign button - Element not found: button:has-text("New Campaign"), a:has-text("New Campaign")
- Social Post Creation: Click New Post button - Element not found: button:has-text("New Post"), button:has-text("Create New Post")
- Creative Asset Upload: Click Upload Asset button - Element not found: button:has-text("Upload Asset")
- View Toggles: Click list view toggle - Element not found: button[aria-label*="list"], button:has-text("List")
- Refresh Button: Click Refresh button - Element not found: button:has-text("Refresh")
- Platform Connection: Click Google Connect button - Element not found: button:has-text("Connect"):has-text("Google"), button:has-text("ربط"):has-text("جوجل")
- Tab Switching: Click KPI Dashboard tab - Element not found: button:has-text("KPI Dashboard")

---

**Screenshots:** ./test-results/functional-interactions/screenshots/
**Full Report:** ./test-results/functional-interactions/test-report.json
