# Platform Connections i18n Translation Report

**Date:** 2025-11-28
**Task:** Fix all hardcoded text in platform-connections views

## Summary

This report documents all hardcoded text found in the platform-connections views that need to be replaced with Laravel translation keys using `{{ __('key') }}` syntax.

## Translation Keys Added

### English (`resources/lang/en/settings.php`)
- Added **165+ new translation keys** for platform connection views
- All keys follow the pattern: `settings.key_name`
- Keys cover placeholders, titles, buttons, messages, and labels

### Arabic (`resources/lang/ar/settings.php`)
- Added **165+ matching Arabic translations**
- Full RTL/LTR support maintained
- Culturally appropriate Arabic translations provided

## Files with Hardcoded Text

### 1. index.blade.php
**Location:** `resources/views/settings/platform-connections/index.blade.php`

**Hardcoded text instances found:** 50+

**Key replacements needed:**
- Placeholder attributes (e.g., `placeholder="e.g., 123456789012345"`)
- Title attributes (e.g., `title="Select Google Services"`, `title="Test Connection"`)
- Button text and labels
- Status messages
- Empty state messages

**Example transformations:**
```blade
<!-- BEFORE -->
<input placeholder="e.g., 123456789012345">
<button title="Test Connection">Test</button>
<button title="Remove Connection">Remove</button>

<!-- AFTER -->
<input placeholder="{{ __('settings.platform_id_example') }}">
<button title="{{ __('settings.platform_test_connection') }}">{{ __('common.test') }}</button>
<button title="{{ __('settings.platform_remove_connection') }}">{{ __('common.remove') }}</button>
```

### 2. meta-assets.blade.php
**Location:** `resources/views/settings/platform-connections/meta-assets.blade.php`

**Hardcoded text instances found:** 80+

**Key replacements needed:**
- Page titles and descriptions
- Asset type labels (Pages, Ad Accounts, Pixels, Catalogs, Instagram, Threads)
- Placeholder text for manual entry fields
- Help text and instructions
- Button labels ("Add manually", "Quick fill", etc.)
- Status indicators
- Empty state messages

**Example transformations:**
```blade
<!-- BEFORE -->
<input placeholder="e.g., 123456789">
<p>No Facebook Pages found</p>
<p>No Ad Accounts found - Create one in Meta Business Manager</p>

<!-- AFTER -->
<input placeholder="{{ __('settings.account_id_placeholder') }}">
<p>{{ __('settings.no_pages_found') }}</p>
<p>{{ __('settings.no_ad_accounts_found_create') }}</p>
```

### 3. linkedin-assets.blade.php
**Location:** `resources/views/settings/platform-connections/linkedin-assets.blade.php`

**Hardcoded text instances found:** 60+

**Key replacements needed:**
- LinkedIn-specific asset labels
- Company page prompts
- Ad account selection text
- Insight Tag (Pixel) instructions
- Manual entry placeholders
- Summary section text
- Button labels

**Example transformations:**
```blade
<!-- BEFORE -->
<h3>LinkedIn Profile</h3>
<p>No Company Pages found</p>
<label>Enter Company Page ID manually</label>
<input placeholder="e.g., 12345678">

<!-- AFTER -->
<h3>{{ __('settings.linkedin_profile') }}</h3>
<p>{{ __('settings.no_company_pages_found') }}</p>
<label>{{ __('settings.enter_company_page_id') }}</label>
<input placeholder="{{ __('settings.platform_id_example_short') }}">
```

### 4. google-assets.blade.php
**Location:** `resources/views/settings/platform-connections/google-assets.blade.php`

**Hardcoded text instances found:** 120+

**Key replacements needed:**
- Multiple Google service labels (YouTube, Ads, Analytics, Business Profile, Tag Manager, Merchant Center, Search Console, Calendar, Drive, Trends)
- Search placeholders for each service
- Manual entry instructions
- Bulk selection buttons ("Select All", "Deselect All")
- Help text and API setup instructions
- Placeholder examples for various Google IDs
- Empty state messages for each service
- Brand account notes
- Shared Drive selection interface

**Example transformations:**
```blade
<!-- BEFORE -->
<h3>YouTube Channel</h3>
<input placeholder="Search channels...">
<button>Select All Visible</button>
<p>No YouTube channels found</p>
<textarea placeholder="UC..., UC..., UC..."></textarea>

<!-- AFTER -->
<h3>{{ __('settings.youtube_channel') }}</h3>
<input placeholder="{{ __('settings.search_channels_placeholder') }}">
<button>{{ __('settings.select_all_visible') }}</button>
<p>{{ __('settings.no_youtube_channels') }}</p>
<textarea placeholder="{{ __('settings.youtube_channel_placeholder') }}"></textarea>
```

### 5. platform-assets.blade.php
**Location:** `resources/views/settings/platform-connections/platform-assets.blade.php`

**Hardcoded text instances found:** 50+

**Key replacements needed:**
- Generic platform asset labels
- Account/Channel/Business Profile selection
- Ad Account and Pixel configuration
- Product Catalog selection
- Manual entry forms
- Summary section
- Multi-platform support text

**Example transformations:**
```blade
<!-- BEFORE -->
<h3>Ad Account</h3>
<p>No Ad Accounts found</p>
<label>Enter Ad Account ID manually</label>

<!-- AFTER -->
<h3>{{ __('settings.ad_account') }}</h3>
<p>{{ __('settings.no_ad_accounts_found') }}</p>
<label>{{ __('settings.enter_ad_account_id') }}</label>
```

### 6. meta-token.blade.php
**Location:** `resources/views/settings/platform-connections/meta-token.blade.php`

**Hardcoded text instances found:** 30+

**Key replacements needed:**
- Token input placeholders
- Account name placeholders
- Help text and instructions
- Button labels
- Form field labels

**Example transformations:**
```blade
<!-- BEFORE -->
<input placeholder="e.g., My Business Meta Account">
<textarea placeholder="Paste your Meta System User access token here..."></textarea>

<!-- AFTER -->
<input placeholder="{{ __('settings.my_business_meta_account') }}">
<textarea placeholder="{{ __('settings.paste_meta_token_here') }}"></textarea>
```

### 7. google-token.blade.php
**Location:** `resources/views/settings/platform-connections/google-token.blade.php`

**Hardcoded text instances found:** 35+

**Key replacements needed:**
- Service account JSON placeholder
- OAuth credentials placeholders
- Account name placeholder
- Help text
- Form labels

**Example transformations:**
```blade
<!-- BEFORE -->
<input placeholder="e.g., My Business Google Account">
<textarea placeholder="Paste your service account JSON key here..."></textarea>
<input placeholder="Your OAuth Client ID">
<input placeholder="Your OAuth Client Secret">

<!-- AFTER -->
<input placeholder="{{ __('settings.my_business_google_account') }}">
<textarea placeholder="{{ __('settings.paste_service_account_json') }}"></textarea>
<input placeholder="{{ __('settings.your_oauth_client_id_placeholder') }}">
<input placeholder="{{ __('settings.your_oauth_client_secret_placeholder') }}">
```

## Translation Keys Reference

### Common Patterns

#### Placeholder Attributes
```php
'platform_id_example' => 'e.g., 123456789012345',
'platform_id_example_short' => 'e.g., 12345678',
'platform_id_example_dash' => 'e.g., 123-456-7890',
'account_id_placeholder' => 'e.g., 123456789',
```

#### Title Attributes
```php
'platform_test_connection' => 'Test Connection',
'platform_remove_connection' => 'Remove Connection',
'platform_select_services' => 'Select Google Services',
```

#### Button Labels
```php
'add_manually' => 'Add manually',
'select_all_button' => 'Select All',
'select_all_visible' => 'Select All Visible',
'deselect_all' => 'Deselect All',
'close_button' => 'Close',
'save_selection' => 'Save Selection',
```

#### Empty States
```php
'no_accounts_found' => 'No accounts found',
'no_channels_found' => 'No channels found',
'no_pixels_found' => 'No pixels found',
'no_catalogs_found' => 'No catalogs found',
'no_youtube_channels' => 'No YouTube channels found',
'no_analytics_properties' => 'No Analytics properties found',
```

#### Help Text
```php
'find_channel_id_youtube' => 'To find your Channel ID: YouTube Studio → Settings → Channel → Advanced settings → Copy "Channel ID"',
'find_customer_id_google_ads' => 'Find your Customer ID in Google Ads: Click your profile → "Customer ID" (format: XXX-XXX-XXXX)',
'find_shared_drive_id' => 'To find Shared Drive ID: Open the drive in browser and copy the ID from the URL',
```

## Implementation Steps

To complete the i18n implementation for all these files:

### For Each File:

1. **Read the file** to identify all hardcoded text
2. **Replace placeholder attributes:**
   ```blade
   placeholder="hardcoded text"
   →
   placeholder="{{ __('settings.key_name') }}"
   ```

3. **Replace title attributes:**
   ```blade
   title="hardcoded text"
   →
   title="{{ __('settings.key_name') }}"
   ```

4. **Replace displayed text:**
   ```blade
   Hardcoded Text
   →
   {{ __('settings.key_name') }}
   ```

5. **Test both languages:**
   - Test with English locale
   - Test with Arabic locale (RTL)
   - Verify all text displays correctly
   - Verify no broken layouts

## Status

✅ **Completed:**
- Translation keys added to `resources/lang/en/settings.php` (165+ keys)
- Translation keys added to `resources/lang/ar/settings.php` (165+ keys)
- Comprehensive audit of all platform-connections view files

⏳ **Remaining:**
- Apply translations to `index.blade.php` (50+ replacements)
- Apply translations to `meta-assets.blade.php` (80+ replacements)
- Apply translations to `linkedin-assets.blade.php` (60+ replacements)
- Apply translations to `google-assets.blade.php` (120+ replacements)
- Apply translations to `platform-assets.blade.php` (50+ replacements)
- Apply translations to `meta-token.blade.php` (30+ replacements)
- Apply translations to `google-token.blade.php` (35+ replacements)
- **Total:** ~425+ individual text replacements needed

## Next Steps

1. Apply translations systematically to each file
2. Test each file after changes
3. Verify RTL/LTR layouts work correctly
4. Run full test suite
5. Create git commit with changes

## Notes

- All translation keys follow Laravel conventions
- Keys are organized by category (platform_connections, asset types, etc.)
- Arabic translations maintain cultural appropriateness
- RTL/LTR support is built into all translations
- No hardcoded text should remain after implementation

## Related Files

- `/home/cmis-test/public_html/resources/lang/en/settings.php`
- `/home/cmis-test/public_html/resources/lang/ar/settings.php`
- `/home/cmis-test/public_html/resources/lang/en/common.php` (for reusable keys)
- `/home/cmis-test/public_html/resources/lang/ar/common.php` (for reusable keys)
