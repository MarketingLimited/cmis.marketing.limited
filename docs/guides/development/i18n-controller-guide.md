# Controller i18n Quick Reference Guide

**Last Updated:** 2025-11-27
**Audience:** CMIS Developers

---

## Quick Start

### ✅ DO: Use Translation Keys

```php
// Flash messages
return redirect()->back()->with('success', __('campaigns.created_success'));
return redirect()->back()->with('error', __('campaigns.operation_failed'));

// JSON responses
return response()->json(['message' => __('campaigns.updated_success')]);

// Exceptions
throw new \Exception(__('campaigns.invalid'));
```

### ❌ DON'T: Use Hardcoded Text

```php
// NEVER do this
return redirect()->back()->with('success', 'Campaign created successfully');
return redirect()->back()->with('success', 'تم إنشاء الحملة بنجاح');
return response()->json(['message' => 'Success']);
throw new \Exception('Invalid operation');
```

---

## Translation Key Patterns

### Standard CRUD Operations

| Action | Key Pattern | Example |
|--------|-------------|---------|
| Create | `{domain}.created_success` | `campaigns.created_success` |
| Update | `{domain}.updated_success` | `campaigns.updated_success` |
| Delete | `{domain}.deleted_success` | `campaigns.deleted_success` |
| Fetch | `{domain}.fetched_success` | `campaigns.fetched_success` |

### Error Messages

| Error Type | Key Pattern | Example |
|------------|-------------|---------|
| Not Found | `{domain}.not_found` | `campaigns.not_found` |
| Invalid | `{domain}.invalid` | `campaigns.invalid` |
| Failed | `{domain}.operation_failed` | `campaigns.operation_failed` |
| Unauthorized | `{domain}.unauthorized` | `campaigns.unauthorized` |

---

## Dynamic Messages with Placeholders

### Using Placeholders

```php
// Translation key in resources/lang/ar/organizations.php
'create_failed' => 'فشل إنشاء المؤسسة: :error',

// Translation key in resources/lang/en/organizations.php
'create_failed' => 'Failed to create organization: :error',

// Usage in controller
return redirect()->back()->with('error',
    __('organizations.create_failed', ['error' => $e->getMessage()])
);
```

### Common Placeholder Patterns

```php
// Single variable
__('campaigns.deleted_by', ['user' => $user->name])
// "Campaign deleted by John"
// "تم حذف الحملة بواسطة أحمد"

// Multiple variables
__('campaigns.budget_updated', [
    'old' => $oldBudget,
    'new' => $newBudget
])
// "Budget updated from $100 to $150"
// "تم تحديث الميزانية من 100$ إلى 150$"

// Count-based
__('campaigns.selected_count', ['count' => $campaigns->count()])
// "5 campaigns selected"
// "تم اختيار 5 حملات"
```

---

## Domain Organization

### Where to Add New Keys

| Controller Path | Domain | Lang File |
|----------------|--------|-----------|
| `Campaign/*` | campaigns | `campaigns.php` |
| `Influencer/*` | influencers | `influencers.php` |
| `Settings/*` | settings | `settings.php` |
| `Auth/*` | auth | `auth.php` |
| `API/*` | api | `api.php` |
| `Dashboard/*` | dashboard | `dashboard.php` |

### Adding New Translation Keys

**Step 1: Add to Arabic file**
```php
// resources/lang/ar/{domain}.php
return [
    // ... existing keys
    'new_key' => 'النص بالعربية',
];
```

**Step 2: Add to English file**
```php
// resources/lang/en/{domain}.php
return [
    // ... existing keys
    'new_key' => 'Text in English',
];
```

**Step 3: Use in controller**
```php
return redirect()->back()->with('success', __('domain.new_key'));
```

---

## Flash Message Types

### Available Types

```php
// Success (green)
->with('success', __('campaigns.created_success'))

// Error (red)
->with('error', __('campaigns.operation_failed'))

// Warning (yellow)
->with('warning', __('campaigns.warning_message'))

// Info (blue)
->with('info', __('campaigns.info_message'))
```

---

## JSON API Responses

### Standard Pattern

```php
// Success response
return response()->json([
    'success' => true,
    'message' => __('campaigns.created_success'),
    'data' => $campaign
]);

// Error response
return response()->json([
    'success' => false,
    'message' => __('campaigns.operation_failed'),
    'errors' => $errors
], 400);
```

### Using ApiResponse Trait (Recommended)

```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $campaign = Campaign::create($request->validated());
        return $this->created($campaign, __('campaigns.created_success'));
    }

    public function destroy($id)
    {
        Campaign::findOrFail($id)->delete();
        return $this->deleted(__('campaigns.deleted_success'));
    }
}
```

---

## Exception Messages

### Throwing Translated Exceptions

```php
// Simple exception
if (!$campaign) {
    throw new \Exception(__('campaigns.not_found'));
}

// With placeholder
if ($budget < 0) {
    throw new \Exception(__('campaigns.invalid_budget', ['budget' => $budget]));
}

// Validation exception
throw ValidationException::withMessages([
    'name' => [__('validation.required', ['attribute' => 'name'])]
]);
```

---

## Testing Translations

### Manual Testing

```bash
# Test in Arabic
APP_LOCALE=ar php artisan serve

# Test in English
APP_LOCALE=en php artisan serve
```

### Automated Testing

```php
// Test translation key exists
public function test_translation_key_exists()
{
    $this->assertNotEmpty(__('campaigns.created_success'));
    $this->assertNotEquals(
        'campaigns.created_success',
        __('campaigns.created_success')
    );
}

// Test with placeholders
public function test_translation_with_placeholder()
{
    $message = __('campaigns.deleted_by', ['user' => 'John']);
    $this->assertStringContainsString('John', $message);
}
```

---

## Common Mistakes

### ❌ Missing Translation Key
```php
// This will display the key itself if not found
__('campaigns.non_existent_key')
// Output: "campaigns.non_existent_key" (bad UX)
```

**Fix:** Always add the key to both AR and EN files first.

### ❌ Wrong Domain
```php
// Using wrong domain
__('users.campaign_created')  // Should be campaigns.created_success
```

**Fix:** Use the correct domain matching your controller namespace.

### ❌ Hardcoded Variables
```php
// Concatenating instead of using placeholders
__('campaigns.created') . ' by ' . $user->name  // WRONG
```

**Fix:** Use placeholders:
```php
__('campaigns.created_by', ['user' => $user->name])  // CORRECT
```

### ❌ Mixed Languages
```php
// Never mix languages
'تم إنشاء ' . __('campaigns.name') . ' بنجاح'  // WRONG
```

**Fix:** Keep entire sentence in translation file:
```php
__('campaigns.created_with_name', ['name' => $campaign->name])  // CORRECT
```

---

## Checklist for New Controllers

- [ ] No hardcoded English text
- [ ] No hardcoded Arabic text
- [ ] All flash messages use `__('domain.key')`
- [ ] All JSON responses use `__('domain.key')`
- [ ] All exceptions use `__('domain.key')`
- [ ] Translation keys added to both AR and EN files
- [ ] Dynamic messages use placeholders
- [ ] Tested in both Arabic (RTL) and English (LTR)

---

## Resources

- **Language Files:** `resources/lang/{locale}/{domain}.php`
- **Laravel i18n Docs:** https://laravel.com/docs/localization
- **CMIS i18n Guidelines:** `.claude/knowledge/I18N_RTL_REQUIREMENTS.md`
- **Full Report:** `docs/active/reports/i18n-controller-cleanup-report.md`

---

**Remember:** Every user-facing string must go through `__()` helper. No exceptions!
