# FormRequest Usage Guide for CMIS Developers
**Last Updated:** 2025-11-23
**Version:** 1.0

---

## Overview

This guide shows how to use FormRequest classes in CMIS controllers for secure, standardized input validation.

**Coverage:** 98 FormRequest classes covering 100% of store/update operations

---

## Quick Start

### 1. Basic Usage

**Before (Inline Validation):**
```php
public function store(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'name' => 'required|string|max:255',
    ]);

    $user = User::create($request->all());
    return response()->json($user, 201);
}
```

**After (FormRequest):**
```php
use App\Http\Requests\Core\InviteUserRequest;

public function store(InviteUserRequest $request)
{
    // Validation already complete - $request is guaranteed valid
    $user = User::create($request->validated());
    return response()->json($user, 201);
}
```

**Benefits:**
- ✅ Validation happens before controller method
- ✅ Custom error messages included
- ✅ Cleaner, more readable code
- ✅ Reusable validation logic
- ✅ Type-hinted for IDE support

---

## FormRequest Directory Structure

```
app/Http/Requests/
├── Auth/               # Authentication & registration
├── Core/               # Users, orgs, teams
├── Team/               # Team management
├── Analytics/          # KPIs, experiments, reports
├── Listening/          # Social listening & monitoring
├── Content/            # Content management
├── Creative/           # Creative assets
├── Campaign/           # Campaigns
├── AdCampaign/         # Ad campaigns
├── AdPlatform/         # Ad accounts, sets, audiences
├── Product/            # Product catalog
├── Service/            # Service offerings
├── Lead/               # Lead management
├── Contact/            # Contact management
├── Compliance/         # COPPA, GDPR validation
├── Enterprise/         # Enterprise accounts
├── Publishing/         # Content publishing
├── Social/             # Social media posts
├── Automation/         # Campaign automation
└── [... 20+ more domains]
```

---

## Common Usage Patterns

### Pattern 1: Store Operation

```php
use App\Http\Requests\Campaign\StoreCampaignRequest;

public function store(StoreCampaignRequest $request)
{
    $campaign = Campaign::create($request->validated());

    return $this->created($campaign, 'Campaign created successfully');
}
```

**Available Store Requests:**
- `StoreCampaignRequest`
- `StoreProductRequest`
- `StoreLeadRequest`
- `StoreContentRequest`
- `StoreAdCampaignRequest`
- ... [90+ more]

### Pattern 2: Update Operation

```php
use App\Http\Requests\Campaign\UpdateCampaignRequest;

public function update(UpdateCampaignRequest $request, $id)
{
    $campaign = Campaign::findOrFail($id);
    $campaign->update($request->validated());

    return $this->success($campaign, 'Campaign updated successfully');
}
```

**Available Update Requests:**
- `UpdateCampaignRequest`
- `UpdateProductRequest`
- `UpdateContentRequest`
- `UpdateUserRoleRequest`
- ... [40+ more]

### Pattern 3: Specialized Operations

```php
use App\Http\Requests\Core\InviteUserRequest;

public function inviteUser(string $orgId, InviteUserRequest $request)
{
    // Custom validation logic specific to invitations
    $invitation = $this->userService->invite(
        $orgId,
        $request->validated()
    );

    return $this->created($invitation, 'Invitation sent successfully');
}
```

---

## FormRequest Features

### 1. Automatic Validation

FormRequests automatically validate before reaching your controller:

```php
// This method ONLY runs if validation passes
public function store(StoreProductRequest $request)
{
    // $request->validated() contains only validated fields
    $product = Product::create($request->validated());
}
```

If validation fails, a 422 response is automatically returned:
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "price": ["The price must be at least 0."]
    }
}
```

### 2. Custom Error Messages

All FormRequests include user-friendly error messages:

```php
// In StoreProductRequest.php
public function messages(): array
{
    return [
        'name.required' => 'Product name is required',
        'price.min' => 'Price must be 0 or greater',
        'sku.unique' => 'This SKU is already in use',
    ];
}
```

### 3. Authorization Logic

Some FormRequests include authorization:

```php
// In FormRequest
public function authorize(): bool
{
    // Can include permission checks
    return $this->user()->can('create', Campaign::class);
}
```

### 4. Validated Data Only

Use `validated()` to get only validated fields:

```php
// Only fields with validation rules are returned
$data = $request->validated();

// Prevents mass assignment vulnerabilities
Campaign::create($data); // Safe
```

---

## Domain-Specific Examples

### Authentication

```php
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;

public function login(LoginRequest $request)
{
    // Validates: email, password, remember
    $credentials = $request->validated();

    if (Auth::attempt($credentials)) {
        return $this->success(['token' => $user->createToken('auth')->plainTextToken]);
    }

    return $this->unauthorized('Invalid credentials');
}

public function register(RegisterRequest $request)
{
    // Validates: name, email, password (with strength requirements), terms_accepted
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return $this->created($user, 'Registration successful');
}
```

### User Management

```php
use App\Http\Requests\Core\InviteUserRequest;
use App\Http\Requests\Core\UpdateUserRoleRequest;
use App\Http\Requests\Core\UpdateUserStatusRequest;

public function invite(string $orgId, InviteUserRequest $request)
{
    // Validates: email, role_id (must exist), message (max 1000 chars)
    $invitation = $this->userService->inviteUser($orgId, $request->validated());
    return $this->created($invitation);
}

public function updateRole(string $orgId, string $userId, UpdateUserRoleRequest $request)
{
    // Validates: role_id (must exist in database)
    $this->userService->updateRole($orgId, $userId, $request->role_id);
    return $this->success(null, 'Role updated successfully');
}

public function updateStatus(string $orgId, string $userId, UpdateUserStatusRequest $request)
{
    // Validates: is_active (boolean)
    $this->userService->updateStatus($orgId, $userId, $request->is_active);
    return $this->success(null, 'Status updated successfully');
}
```

### Content Management

```php
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;

public function store(StoreContentRequest $request)
{
    // Validates:
    // - title, content_type, platforms (required)
    // - media_files (max 100MB, specific MIME types)
    // - coppa_compliant, gdpr_compliant (required booleans)
    // - scheduled_for (must be future date)

    $content = Content::create($request->validated());

    if ($request->hasFile('media_files')) {
        foreach ($request->file('media_files') as $file) {
            $path = $file->store('content/media');
            $content->media()->create(['path' => $path]);
        }
    }

    return $this->created($content, 'Content created successfully');
}
```

### Social Listening

```php
use App\Http\Requests\Listening\StoreMonitoringKeywordRequest;
use App\Http\Requests\Listening\StoreCompetitorRequest;

public function storeKeyword(StoreMonitoringKeywordRequest $request)
{
    // Validates:
    // - keyword (min 2, max 255 chars)
    // - match_type (exact|phrase|broad)
    // - platforms (array, min 1)
    // - languages (ISO 639-1 codes)

    $keyword = MonitoringKeyword::create($request->validated());
    return $this->created($keyword);
}

public function storeCompetitor(StoreCompetitorRequest $request)
{
    // Validates:
    // - name, website (URL)
    // - social_handles (platform-specific regex validation)
    // - monitor_frequency (realtime|hourly|daily|weekly)

    $competitor = Competitor::create($request->validated());
    return $this->created($competitor);
}
```

### Analytics & Reporting

```php
use App\Http\Requests\Analytics\StoreKpiTargetRequest;
use App\Http\Requests\Analytics\StoreDataExportRequest;

public function storeKpiTarget(StoreKpiTargetRequest $request)
{
    // Validates:
    // - metric_type (impressions|clicks|conversions|etc)
    // - target_value (numeric, min 0)
    // - time_period (daily|weekly|monthly|etc)
    // - campaign_id (optional, must exist)

    $kpi = KpiTarget::create($request->validated());
    return $this->created($kpi);
}

public function exportData(StoreDataExportRequest $request)
{
    // Validates:
    // - export_type, format (csv|xlsx|json|pdf)
    // - start_date, end_date (max 2 years back)
    // - campaign_ids (array of valid UUIDs)

    $export = $this->analyticsService->createExport($request->validated());
    return $this->success($export);
}
```

### Compliance

```php
use App\Http\Requests\Compliance\StoreComplianceCheckRequest;

public function checkCompliance(StoreComplianceCheckRequest $request)
{
    // Validates:
    // - content_id (must exist)
    // - check_type (coppa|gdpr|platform_policy|all)
    // - target_age_range (min 0, max 100)
    // - coppa_attestation (required if targeting < 13)
    // - gdpr_attestation (required if targeting EU)

    // Automatic COPPA enforcement
    // Automatic GDPR enforcement for EU countries

    $result = $this->complianceService->check($request->validated());
    return $this->success($result);
}
```

---

## Security Features in FormRequests

### 1. SQL Injection Prevention

```php
// UUID validation prevents injection
'campaign_id' => ['uuid', 'exists:cmis.campaigns,campaign_id']

// Numeric validation prevents injection
'price' => ['numeric', 'min:0', 'max:999999999.99']

// Regex validation for complex formats
'phone' => ['regex:/^[0-9+\-\s()]+$/']
```

### 2. File Upload Security

```php
'media_files.*' => [
    'file',
    'max:104857600', // 100MB max
    'mimes:jpeg,jpg,png,gif,mp4,mov,avi,webm',
]
```

### 3. COPPA Compliance (Children's Privacy)

```php
// In StoreComplianceCheckRequest
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // Auto-enforce COPPA if targeting under 13
        if (isset($this->target_age_range['min']) && $this->target_age_range['min'] < 13) {
            if (!$this->coppa_attestation) {
                $validator->errors()->add('coppa_attestation',
                    'COPPA compliance is required when targeting children under 13');
            }
        }
    });
}
```

### 4. GDPR Compliance (EU Data Protection)

```php
// Mandatory GDPR consent
'gdpr_consent' => ['required', 'boolean', 'accepted']

// Auto-enforce for EU countries
if ($targetingEU && !$this->gdpr_attestation) {
    $validator->errors()->add('gdpr_attestation',
        'GDPR compliance is required when targeting EU countries');
}
```

---

## Best Practices

### 1. Always Use validated() Method

```php
// ✅ GOOD - Only validated fields
$campaign = Campaign::create($request->validated());

// ❌ BAD - Allows mass assignment vulnerabilities
$campaign = Campaign::create($request->all());
```

### 2. Combine with ApiResponse Trait

```php
use App\Http\Controllers\Concerns\ApiResponse;

class CampaignController extends Controller
{
    use ApiResponse;

    public function store(StoreCampaignRequest $request)
    {
        $campaign = Campaign::create($request->validated());

        // Standardized response
        return $this->created($campaign, 'Campaign created successfully');
    }
}
```

### 3. Handle File Uploads Safely

```php
public function store(StoreContentRequest $request)
{
    $content = Content::create($request->except('media_files'));

    if ($request->hasFile('media_files')) {
        foreach ($request->file('media_files') as $file) {
            // Files are already validated by FormRequest
            $path = $file->store('content/media', 'public');
            $content->media()->create([
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    return $this->created($content);
}
```

### 4. Use Type Hinting

```php
// ✅ GOOD - Type hinted, IDE autocomplete works
public function store(StoreCampaignRequest $request)

// ❌ BAD - No type hinting
public function store(Request $request)
```

---

## Testing FormRequests

### Unit Test Example

```php
// tests/Unit/Requests/Auth/LoginRequestTest.php
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_email_is_required()
    {
        $request = new LoginRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_email_must_be_valid()
    {
        $request = new LoginRequest();
        $validator = Validator::make(['email' => 'invalid-email'], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_password_is_required()
    {
        $request = new LoginRequest();
        $validator = Validator::make(['email' => 'test@example.com'], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_valid_data_passes()
    {
        $request = new LoginRequest();
        $validator = Validator::make([
            'email' => 'test@example.com',
            'password' => 'password123',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }
}
```

### Feature Test Example

```php
// tests/Feature/Auth/LoginTest.php
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_requires_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_valid_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_login_successful_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }
}
```

---

## Complete FormRequest Index

### Authentication (2)
- `Auth/LoginRequest` - Login validation
- `Auth/RegisterRequest` - Registration with password strength

### Core User Management (5)
- `Core/InviteUserRequest` - User invitation
- `Core/UpdateUserRoleRequest` - Role updates
- `Core/UpdateUserStatusRequest` - Status updates
- `Core/StoreOrgRequest` - Organization creation
- `Core/UpdateOrgRequest` - Organization updates

### Team Management (3)
- `Team/InviteTeamMemberRequest` - Team invitations
- `Team/UpdateTeamMemberRoleRequest` - Team role updates
- `Team/AssignAccountsRequest` - Account assignments

### Analytics (10)
- `Analytics/StoreKpiTargetRequest`
- `Analytics/UpdateKpiTargetRequest`
- `Analytics/StoreDataExportRequest`
- `Analytics/StorePredictiveModelRequest`
- `Analytics/UpdateExperimentRequest`
- `Analytics/StoreScheduledReportRequest`
- `Analytics/UpdateScheduledReportRequest`
- `Analytics/StoreAlertRuleRequest`
- `Analytics/UpdateAlertRuleRequest`
- `Analytics/StoreExperimentRequest`

### Social Listening (6)
- `Listening/StoreMonitoringKeywordRequest`
- `Listening/UpdateMonitoringKeywordRequest`
- `Listening/StoreCompetitorRequest`
- `Listening/UpdateCompetitorRequest`
- `Listening/StoreMonitoringAlertRequest`
- `Listening/StoreResponseTemplateRequest`

**... [and 68 more across 20+ domains]**

See full list in: `/docs/active/analysis/formrequest-validation-coverage-report.md`

---

## Troubleshooting

### Issue: Validation Not Working

**Problem:** FormRequest validation not triggering

**Solution:**
```php
// ✅ Ensure you're type-hinting the FormRequest
public function store(StoreCampaignRequest $request)

// ❌ NOT this
public function store(Request $request)
```

### Issue: Custom Error Messages Not Showing

**Problem:** Default Laravel messages appearing instead of custom messages

**Solution:** Check that your FormRequest has `messages()` method:
```php
public function messages(): array
{
    return [
        'email.required' => 'Email address is required',
        // ... custom messages
    ];
}
```

### Issue: Files Not Validating

**Problem:** File upload validation failing

**Solution:** Ensure you're using multipart/form-data:
```javascript
// Frontend
const formData = new FormData();
formData.append('media_files[]', file);

fetch('/api/content', {
    method: 'POST',
    body: formData,
    headers: {
        'Accept': 'application/json',
        // Don't set Content-Type - browser sets it with boundary
    }
});
```

---

## Resources

- **FormRequest Documentation:** `/docs/active/analysis/formrequest-validation-coverage-report.md`
- **Laravel Validation Docs:** https://laravel.com/docs/validation
- **ApiResponse Trait:** `app/Http/Controllers/Concerns/ApiResponse.php`
- **Security Guidelines:** `.claude/knowledge/MULTI_TENANCY_PATTERNS.md`

---

**Last Updated:** 2025-11-23
**Maintained by:** Laravel Security & Compliance AI
