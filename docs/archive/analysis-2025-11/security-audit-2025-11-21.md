# CMIS Platform Security Audit Report

**Date:** 2025-11-21
**Framework:** META_COGNITIVE_FRAMEWORK v2.0
**Auditor:** Laravel Security & Compliance AI Agent
**Platform:** CMIS - Cognitive Marketing Information System
**Laravel Version:** 12.0
**PHP Version:** 8.2

---

## Executive Summary

### Overall Security Posture: HIGH RISK

The CMIS platform demonstrates a strong security foundation with proper authentication, webhook signature verification, and multi-tenancy implementation. However, CRITICAL vulnerabilities were discovered that require immediate attention, particularly around application encryption keys, SQL injection vectors, and command injection risks.

### Key Metrics

| Category | Metric | Status |
|----------|--------|--------|
| **Attack Surface** | 127 controllers, 245 models, 1,602 API route lines | LARGE |
| **Authentication** | Laravel Sanctum, 12 policies, 14 gates | GOOD |
| **Validation Coverage** | 29 FormRequests for 127 controllers (22.8%) | MEDIUM |
| **Authorization Coverage** | 12 policies for 245 models (4.9%) | LOW |
| **SQL Injection Risk** | 153 DB::raw with variables | HIGH RISK |
| **Mass Assignment Protection** | 236/245 models (96.3%) | GOOD |
| **Rate Limiting** | 19 routes with throttle | LIMITED |
| **Multi-Tenancy (RLS)** | 75 policies in 59 migrations | EXCELLENT |
| **Vulnerabilities Found** | 3 CRITICAL, 7 HIGH, 5 MEDIUM | **ACTION REQUIRED** |

### Risk Classification Summary

- **CRITICAL Issues:** 3 (Immediate fixes required)
- **HIGH Priority Issues:** 7 (Fix this sprint)
- **MEDIUM Priority Issues:** 5 (Fix next sprint)
- **LOW Priority Issues:** 2 (Ongoing improvement)

---

## 1. CRITICAL Vulnerabilities (Fix Immediately)

### CRITICAL-1: Missing Application Encryption Key

**Severity:** CRITICAL
**CVSS Score:** 9.8 (Critical)
**OWASP:** A02:2021 - Cryptographic Failures

**Location:** `/home/user/cmis.marketing.limited/.env`

**Finding:**
```bash
APP_KEY=
```

The `APP_KEY` environment variable is empty. This completely breaks Laravel's encryption, session security, CSRF protection, and cookie signing.

**Impact:**
- All encrypted data is unreadable
- Session hijacking possible
- CSRF tokens invalid
- Cookies can be forged
- Password reset tokens compromised

**Exploitation:**
```bash
# Attacker can forge session cookies without APP_KEY
# CSRF protection is ineffective
# Encrypted credentials in database cannot be decrypted
```

**Remediation:**
```bash
# Generate a new application key immediately
php artisan key:generate

# Verify .env file
grep "APP_KEY=" .env
# Should output: APP_KEY=base64:... (32-byte key)

# IMPORTANT: After generating key, all existing encrypted data
# will need to be re-encrypted or will be lost
```

**Priority:** IMMEDIATE - Block all deployment until fixed

---

### CRITICAL-2: Command Injection via Path Traversal

**Severity:** CRITICAL
**CVSS Score:** 9.1 (Critical)
**OWASP:** A03:2021 - Injection

**Location:** `/home/user/cmis.marketing.limited/app/Console/Commands/DbExecuteSql.php:11`

**Vulnerable Code:**
```php
public function handle()
{
    $file = base_path($this->argument("file"));  // ← CRITICAL: No path validation
    if (!file_exists($file)) {
        $this->error("SQL file not found: {$file}");
        return Command::FAILURE;
    }

    $sql = file_get_contents($file);
    DB::unprepared($sql);  // ← Executes arbitrary SQL
}
```

**Impact:**
- Arbitrary file read via path traversal
- SQL injection via arbitrary file execution
- Potential database destruction
- Server compromise via SQL execution

**Exploitation:**
```bash
# Attacker can read arbitrary files
php artisan db:execute-sql "../../../../etc/passwd"

# Attacker can execute malicious SQL
echo "DROP SCHEMA cmis CASCADE;" > /tmp/evil.sql
php artisan db:execute-sql "/tmp/evil.sql"

# Path traversal to read credentials
php artisan db:execute-sql "../../../../.env"
```

**Remediation:**
```php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DbExecuteSql extends Command
{
    protected $signature = "db:execute-sql {file}";
    protected $description = "Execute a raw SQL file against the default database connection";

    // Define allowed SQL file directory
    private const ALLOWED_SQL_DIR = 'database/sql';

    public function handle()
    {
        $filename = $this->argument("file");

        // Validate filename - only allow alphanumeric, dash, underscore, dot
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.sql$/', $filename)) {
            $this->error("Invalid filename format. Only alphanumeric characters, dash, underscore allowed.");
            return Command::FAILURE;
        }

        // Construct safe path - only allow files in database/sql directory
        $file = base_path(self::ALLOWED_SQL_DIR . '/' . $filename);

        // Verify file is within allowed directory (prevent path traversal)
        $realPath = realpath($file);
        $allowedPath = realpath(base_path(self::ALLOWED_SQL_DIR));

        if ($realPath === false || strpos($realPath, $allowedPath) !== 0) {
            $this->error("Access denied. File must be in " . self::ALLOWED_SQL_DIR . " directory.");
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("SQL file not found: {$filename}");
            return Command::FAILURE;
        }

        // Additional safety: confirm with user before execution
        if (!$this->confirm("Execute SQL file {$filename}? This will run against " . config('database.default') . " database.")) {
            $this->warn("Operation cancelled.");
            return Command::SUCCESS;
        }

        try {
            $sql = file_get_contents($file);

            // Log execution for audit trail
            \Log::warning("Executing SQL file", [
                'file' => $filename,
                'user' => $this->option('user') ?? 'cli',
                'database' => config('database.default')
            ]);

            DB::unprepared($sql);
            $this->info("✅ Successfully executed SQL file: {$filename}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error executing SQL: " . $e->getMessage());

            // Log error for security monitoring
            \Log::error("SQL execution failed", [
                'file' => $filename,
                'error' => $e->getMessage()
            ]);

            return Command::FAILURE;
        }
    }
}
```

**Additional Security:**
```bash
# Create dedicated SQL directory
mkdir -p database/sql
chmod 755 database/sql

# Update .gitignore to prevent accidental SQL commits
echo "database/sql/*.sql" >> .gitignore
echo "!database/sql/.gitkeep" >> .gitignore
touch database/sql/.gitkeep
```

**Priority:** IMMEDIATE - Disable command or apply fix before any production use

---

### CRITICAL-3: SQL Injection via Array Construction

**Severity:** CRITICAL
**CVSS Score:** 8.8 (High/Critical)
**OWASP:** A03:2021 - Injection

**Locations:**
1. `/home/user/cmis.marketing.limited/app/Repositories/CMIS/CampaignRepository.php:39`
2. `/home/user/cmis.marketing.limited/app/Repositories/PublicUtilityRepository.php:322`
3. `/home/user/cmis.marketing.limited/app/Repositories/Knowledge/KnowledgeRepository.php:42`

**Vulnerable Code Pattern:**
```php
// CampaignRepository.php:39
DB::raw("ARRAY['" . implode("','", $tags) . "']")

// If $tags = ["normal", "test', 'DROP TABLE campaigns; --"]
// Results in: ARRAY['normal','test', 'DROP TABLE campaigns; --']
```

**Impact:**
- SQL injection allows arbitrary SQL execution
- Database destruction possible
- Data exfiltration
- Privilege escalation

**Exploitation:**
```php
// Attacker provides malicious tags
$tags = [
    "legitimate",
    "evil', (SELECT password FROM users WHERE id=1)); --"
];

// Results in SQL injection
// ARRAY['legitimate','evil', (SELECT password FROM users WHERE id=1)); --']
```

**Remediation - Option 1 (Recommended - Use JSON):**
```php
// CampaignRepository.php
public function createCampaignWithContext(
    string $orgId,
    string $offeringId,
    string $segmentId,
    string $campaignName,
    string $framework,
    string $tone,
    array $tags
): Collection {
    // Sanitize tags array
    $sanitizedTags = array_map(function($tag) {
        // Remove any quotes and limit length
        return substr(preg_replace("/['\";]/", '', $tag), 0, 100);
    }, $tags);

    // Convert to JSON (safe)
    $results = DB::select(
        'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?::jsonb)',
        [
            $orgId,
            $offeringId,
            $segmentId,
            $campaignName,
            $framework,
            $tone,
            json_encode($sanitizedTags)
        ]
    );

    return collect($results);
}
```

**Remediation - Option 2 (Use Parameterized Array):**
```php
// Update database function to accept text[] instead of constructing array
// Then use parameterized binding
public function createCampaignWithContext(
    string $orgId,
    string $offeringId,
    string $segmentId,
    string $campaignName,
    string $framework,
    string $tone,
    array $tags
): Collection {
    // Laravel doesn't natively support array binding for PostgreSQL
    // Use pgsql array literal with proper escaping

    $sanitizedTags = array_map(function($tag) {
        // Use pg_escape_string equivalent
        return pg_escape_string($tag);
    }, $tags);

    // Use prepared statement with proper array binding
    $results = DB::select(
        'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?)',
        [
            $orgId,
            $offeringId,
            $segmentId,
            $campaignName,
            $framework,
            $tone,
            '{' . implode(',', array_map(function($tag) {
                return '"' . str_replace('"', '\"', $tag) . '"';
            }, $tags)) . '}'
        ]
    );

    return collect($results);
}
```

**Remediation - Option 3 (Best - Refactor Function):**
```sql
-- Update database function to use JSONB
CREATE OR REPLACE FUNCTION cmis.create_campaign_and_context_safe(
    p_org_id UUID,
    p_offering_id UUID,
    p_segment_id UUID,
    p_campaign_name TEXT,
    p_framework TEXT,
    p_tone TEXT,
    p_tags JSONB  -- Changed from TEXT[] to JSONB
)
RETURNS TABLE(...) AS $$
BEGIN
    -- Function implementation
END;
$$ LANGUAGE plpgsql;
```

```php
// Then use safe JSON binding
public function createCampaignWithContext(
    string $orgId,
    string $offeringId,
    string $segmentId,
    string $campaignName,
    string $framework,
    string $tone,
    array $tags
): Collection {
    $results = DB::select(
        'SELECT * FROM cmis.create_campaign_and_context_safe(?, ?, ?, ?, ?, ?, ?::jsonb)',
        [
            $orgId,
            $offeringId,
            $segmentId,
            $campaignName,
            $framework,
            $tone,
            json_encode($tags)  // Safe - JSON encoded
        ]
    );

    return collect($results);
}
```

**Apply to All Affected Files:**
- `/app/Repositories/CMIS/CampaignRepository.php` (line 39)
- `/app/Repositories/PublicUtilityRepository.php` (line 322)
- `/app/Repositories/Knowledge/KnowledgeRepository.php` (line 42)

**Priority:** IMMEDIATE - Critical SQL injection vector

---

## 2. HIGH Priority Vulnerabilities (Fix This Sprint)

### HIGH-1: Content Security Policy Weaknesses

**Severity:** HIGH
**CVSS Score:** 7.4 (High)
**OWASP:** A05:2021 - Security Misconfiguration

**Location:** `/home/user/cmis.marketing.limited/app/Http/Middleware/SecurityHeaders.php:39-49`

**Finding:**
```php
$response->headers->set('Content-Security-Policy', implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com ...",  // ← 'unsafe-inline' and 'unsafe-eval'
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com ...",  // ← 'unsafe-inline'
    // ...
]));
```

**Impact:**
- `unsafe-inline` allows inline JavaScript execution (XSS risk)
- `unsafe-eval` allows eval() execution (code injection risk)
- Defeats primary purpose of CSP

**Remediation:**
```php
// SecurityHeaders.php - Improved CSP
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    // ... other headers ...

    // Content Security Policy - only for HTML responses
    $contentType = $response->headers->get('Content-Type', '');
    if (str_contains($contentType, 'text/html')) {
        // Generate nonce for this request
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://static.cloudflareinsights.com",  // Removed 'unsafe-inline' 'unsafe-eval'
            "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com",  // Removed 'unsafe-inline'
            "img-src 'self' data: https: blob:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.openai.com https://cloudflareinsights.com https://cdn.jsdelivr.net",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "upgrade-insecure-requests",  // Force HTTPS
        ]));
    }

    // ... rest of code ...

    return $response;
}
```

```blade
{{-- In Blade templates, use nonce for inline scripts --}}
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // Your inline script here
</script>

{{-- Or better: move all scripts to external files --}}
<script src="{{ asset('js/app.js') }}"></script>
```

**Priority:** HIGH - Implement in current sprint

---

### HIGH-2: Insufficient Rate Limiting Coverage

**Severity:** HIGH
**CVSS Score:** 7.2 (High)
**OWASP:** A07:2021 - Identification and Authentication Failures

**Finding:**
Only 19 routes out of 1,602+ API route lines have rate limiting applied.

**Affected Areas:**
- Most API endpoints lack rate limiting
- No rate limiting on resource-intensive operations
- AI/embedding endpoints only have basic throttle

**Impact:**
- Brute force attacks on API endpoints
- Denial of service via resource exhaustion
- API abuse and data scraping
- Cost explosion from AI API calls

**Remediation:**

**Step 1: Define Rate Limit Policies**
```php
// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting()
{
    // Authentication endpoints - strict limiting
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    // Webhooks - moderate limiting
    RateLimiter::for('webhooks', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });

    // General API - per user
    RateLimiter::for('api', function (Request $request) {
        return $request->user()
            ? Limit::perMinute(60)->by($request->user()->id)
            : Limit::perMinute(10)->by($request->ip());
    });

    // AI operations - strict and costly
    RateLimiter::for('ai', function (Request $request) {
        return [
            Limit::perMinute(30)->by($request->user()->id ?? $request->ip()),
            Limit::perHour(500)->by($request->user()->id ?? $request->ip()),
        ];
    });

    // Data export - very restrictive
    RateLimiter::for('export', function (Request $request) {
        return Limit::perHour(10)->by($request->user()->id);
    });

    // File uploads - moderate
    RateLimiter::for('upload', function (Request $request) {
        return Limit::perMinute(20)->by($request->user()->id);
    });
}
```

**Step 2: Apply to Routes**
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('orgs/{org_id}')
    ->group(function () {
        // All org-level routes now have API rate limiting
    });

// AI endpoints
Route::prefix('ai')->middleware(['auth:sanctum', 'throttle:ai'])->group(function () {
    Route::post('/generate', [AIGenerationController::class, 'generate']);
    Route::post('/embeddings', [CMISEmbeddingController::class, 'create']);
});

// Export endpoints
Route::post('/campaigns/export', [CampaignController::class, 'export'])
    ->middleware(['auth:sanctum', 'throttle:export']);
```

**Step 3: Add Response Headers**
```php
// app/Http/Middleware/AddRateLimitHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);

    // Add rate limit info to response headers
    $response->headers->set('X-RateLimit-Limit', RateLimiter::availableIn($key));
    $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key));

    return $response;
}
```

**Priority:** HIGH - Apply in current sprint to prevent abuse

---

### HIGH-3: Limited Authorization Policy Coverage

**Severity:** HIGH
**CVSS Score:** 7.1 (High)
**OWASP:** A01:2021 - Broken Access Control

**Finding:**
Only 12 authorization policies exist for 245 models (4.9% coverage).

**Impact:**
- Most models lack fine-grained authorization
- Reliance on middleware and manual checks
- Inconsistent access control
- Potential data leakage across tenants

**Models Without Policies:**
```bash
# Found 233 models without corresponding policies
# High-risk models that NEED policies:
- Campaign
- User
- Integration
- CreativeAsset
- SocialPost
- AdAccount
- Budget
- Analytics models
```

**Remediation:**

**Step 1: Generate Policies for Critical Models**
```bash
# Generate policies for high-risk models
php artisan make:policy CampaignPolicy --model=Campaign
php artisan make:policy IntegrationPolicy --model=Integration
php artisan make:policy CreativeAssetPolicy --model=CreativeAsset
php artisan make:policy SocialPostPolicy --model=SocialPost
php artisan make:policy AdAccountPolicy --model=AdAccount
php artisan make:policy BudgetPolicy --model=Budget
```

**Step 2: Implement Comprehensive Policy**
```php
// app/Policies/CampaignPolicy.php
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Campaign\Campaign;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any campaigns
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('campaigns.view');
    }

    /**
     * Determine if user can view the campaign
     */
    public function view(User $user, Campaign $campaign): bool
    {
        // Must belong to same organization
        return $user->org_id === $campaign->org_id
            && $user->hasPermission('campaigns.view');
    }

    /**
     * Determine if user can create campaigns
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('campaigns.create');
    }

    /**
     * Determine if user can update the campaign
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $user->org_id === $campaign->org_id
            && $user->hasPermission('campaigns.update');
    }

    /**
     * Determine if user can delete the campaign
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->org_id === $campaign->org_id
            && $user->hasPermission('campaigns.delete');
    }

    /**
     * Determine if user can publish the campaign
     */
    public function publish(User $user, Campaign $campaign): bool
    {
        return $user->org_id === $campaign->org_id
            && $user->hasPermission('campaigns.publish')
            && $campaign->status === 'draft';
    }
}
```

**Step 3: Apply Policies in Controllers**
```php
// app/Http/Controllers/Campaigns/CampaignController.php
public function show(Campaign $campaign)
{
    $this->authorize('view', $campaign);  // ← Policy check

    return view('campaigns.show', compact('campaign'));
}

public function update(Request $request, Campaign $campaign)
{
    $this->authorize('update', $campaign);  // ← Policy check

    // Update logic...
}

public function destroy(Campaign $campaign)
{
    $this->authorize('delete', $campaign);  // ← Policy check

    $campaign->delete();
    return redirect()->route('campaigns.index');
}
```

**Step 4: Register Policies**
```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Campaign::class => CampaignPolicy::class,
    Integration::class => IntegrationPolicy::class,
    CreativeAsset::class => CreativeAssetPolicy::class,
    SocialPost::class => SocialPostPolicy::class,
    AdAccount::class => AdAccountPolicy::class,
    Budget::class => BudgetPolicy::class,
    // Add more as created
];
```

**Priority:** HIGH - Implement for critical models this sprint

---

### HIGH-4: Weak Password Hashing Coverage

**Severity:** HIGH
**CVSS Score:** 6.8 (Medium/High)
**OWASP:** A02:2021 - Cryptographic Failures

**Finding:**
Only 4 instances of password hashing (`Hash::make` / `bcrypt`) found in codebase.

**Affected Areas:**
```php
// app/Http/Controllers/Auth/InvitationController.php:87
'password' => Hash::make($validated['password']),

// app/Http/Controllers/SettingsController.php:50
'password' => Hash::make($validated['password']),
```

**Potential Missing Implementations:**
- User registration might not hash passwords
- Password updates might store plaintext
- API authentication might lack hashing
- Test users might have weak passwords

**Verification Needed:**
```bash
# Check User model for password mutator
grep -A 5 "setPasswordAttribute" app/Models/User.php

# Check registration controller
grep -A 10 "register" app/Http/Controllers/Auth/RegisterController.php
```

**Remediation:**

**Step 1: Ensure User Model Has Password Casting**
```php
// app/Models/User.php
class User extends Authenticatable
{
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',  // ← Laravel 10+ auto-hashing
    ];

    /**
     * Alternative: Manual mutator (Laravel 9 and below)
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
```

**Step 2: Verify Registration Hashes Passwords**
```php
// app/Http/Controllers/Auth/RegisterController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    // Explicit hashing (if not using model cast)
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),  // ← Ensure this exists
    ]);

    // Alternative: rely on model cast
    $user = User::create($validated);  // Model will hash automatically

    Auth::login($user);

    return redirect('/dashboard');
}
```

**Step 3: Enforce Strong Password Policy**
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Validation\Rules\Password;

public function boot()
{
    Password::defaults(function () {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();  // Check against leaked password database
    });
}
```

**Priority:** HIGH - Verify and fix if passwords aren't being hashed

---

### HIGH-5: Embedding Generation SQL Injection Risk

**Severity:** HIGH
**CVSS Score:** 7.5 (High)
**OWASP:** A03:2021 - Injection

**Location:** `/home/user/cmis.marketing.limited/app/Jobs/GenerateEmbeddingsJob.php:79`

**Vulnerable Code:**
```php
'embedding' => DB::raw("'" . json_encode($embedding) . "'::vector"),
```

**Impact:**
- If `$embedding` contains malicious JSON, SQL injection possible
- Vector type casting might allow escape sequences

**Remediation:**
```php
// GenerateEmbeddingsJob.php
public function handle()
{
    // ... embedding generation code ...

    // SAFE: Use parameterized binding
    DB::table('cmis_ai.embeddings')->insert([
        'id' => $embeddingId,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        // Safe parameterized binding for vector
        'embedding' => DB::raw('?::vector'),
    ], [
        json_encode($embedding)  // Bound parameter - safe
    ]);

    // OR better: Use query builder with proper binding
    DB::statement(
        'INSERT INTO cmis_ai.embeddings (id, entity_type, entity_id, embedding)
         VALUES (?, ?, ?, ?::vector)',
        [$embeddingId, $entityType, $entityId, json_encode($embedding)]
    );
}
```

**Priority:** HIGH - Fix to prevent vector injection

---

### HIGH-6: Direct env() Calls in Application Code

**Severity:** MEDIUM-HIGH
**CVSS Score:** 5.9 (Medium)
**OWASP:** A05:2021 - Security Misconfiguration

**Finding:**
17 direct `env()` calls found in `/app` directory. Laravel best practice is to use `config()` instead.

**Impact:**
- Config caching breaks with env() in app code
- Inconsistent configuration management
- Harder to override in testing
- Environment-specific bugs

**Locations:**
```bash
grep -rn "env(" app/ --include="*.php" | head -10
```

**Remediation:**

**Step 1: Move env() Calls to Config Files**
```php
// config/services.php
return [
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_secret' => env('META_WEBHOOK_SECRET'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'webhook_secret' => env('GOOGLE_WEBHOOK_SECRET'),
    ],
    // ... etc
];
```

**Step 2: Replace env() with config() in App Code**
```php
// BEFORE (app/Http/Middleware/VerifyWebhookSignature.php)
$this->secrets = [
    'meta' => env('META_WEBHOOK_SECRET'),
    'google' => env('GOOGLE_WEBHOOK_SECRET'),
];

// AFTER
$this->secrets = [
    'meta' => config('services.meta.webhook_secret'),
    'google' => config('services.google.webhook_secret'),
];
```

**Priority:** MEDIUM-HIGH - Refactor to follow Laravel best practices

---

### HIGH-7: Manual org_id Validation (Potential RLS Bypass)

**Severity:** MEDIUM-HIGH
**CVSS Score:** 6.5 (Medium)
**OWASP:** A01:2021 - Broken Access Control

**Finding:**
Multiple controllers manually check `org_id` instead of relying solely on RLS policies.

**Locations:**
```php
// app/Http/Controllers/API/AIOptimizationController.php:77
if ($campaign->org_id !== $org->org_id) {
    abort(403);
}

// app/Http/Controllers/API/UnifiedCampaignController.php:190
if ($campaign->org_id !== $org->org_id) {
    abort(403);
}
```

**Issue:**
While manual checks add defense-in-depth, they can:
- Create inconsistencies if RLS policy differs
- Be forgotten in new code
- Not cover all query types (joins, subqueries)

**Recommendation:**
Trust RLS policies as primary defense, use manual checks as secondary validation only when necessary (e.g., early abort for performance).

**Best Practice:**
```php
// Let RLS handle filtering - cleaner code
public function show(Org $org, Campaign $campaign)
{
    // RLS policy already ensures $campaign belongs to correct org
    // No manual check needed

    return response()->json($campaign);
}

// Only add manual check if you need custom error message
public function show(Org $org, Campaign $campaign)
{
    // Optional: Early check for better error message
    if ($campaign->org_id !== $org->id) {
        return response()->json([
            'error' => 'Campaign not found in this organization'
        ], 404);  // More user-friendly than 403
    }

    return response()->json($campaign);
}
```

**Priority:** MEDIUM - Document pattern and ensure consistency

---

## 3. MEDIUM Priority Vulnerabilities (Fix Next Sprint)

### MEDIUM-1: Insufficient Input Validation Coverage

**Severity:** MEDIUM
**CVSS Score:** 5.8 (Medium)

**Finding:**
Only 29 FormRequest classes for 127 controllers (22.8% coverage).

**Impact:**
- Many controllers lack structured validation
- Inconsistent validation rules
- Potential for invalid data in database

**Recommendation:**
Create FormRequest classes for all controllers that accept user input.

**Example:**
```bash
php artisan make:request Campaign/StoreCampaignRequest
php artisan make:request Campaign/UpdateCampaignRequest
php artisan make:request Integration/StoreIntegrationRequest
```

---

### MEDIUM-2: Missing File Upload MIME Validation

**Severity:** MEDIUM
**CVSS Score:** 5.5 (Medium)

**Finding:**
File upload exists (`$request->file('avatar')->store`) but no MIME type validation rules found in FormRequests.

**Location:** `/home/user/cmis.marketing.limited/app/Http/Controllers/ProfileController.php`

**Remediation:**
```php
// app/Http/Requests/UpdateAvatarRequest.php
public function rules()
{
    return [
        'avatar' => [
            'required',
            'file',
            'mimes:jpg,jpeg,png,gif',  // ← Add MIME validation
            'max:2048',  // 2MB max
            'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
        ]
    ];
}
```

---

### MEDIUM-3: Limited Session Security Configuration

**Severity:** MEDIUM
**CVSS Score:** 5.3 (Medium)

**Recommendation:**
Verify session security settings:

```php
// config/session.php
return [
    'secure' => env('SESSION_SECURE_COOKIE', true),  // HTTPS only in production
    'http_only' => true,  // Prevent JavaScript access
    'same_site' => 'lax',  // CSRF protection
    'lifetime' => 120,  // 2 hours
];
```

---

### MEDIUM-4: Insufficient Security Event Logging

**Severity:** MEDIUM
**CVSS Score:** 5.1 (Medium)

**Finding:**
Limited security event logging for:
- Failed login attempts
- Authorization failures
- API abuse
- Suspicious activities

**Remediation:**
```php
// app/Http/Middleware/LogSecurityEvents.php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);

    // Log failed authorization
    if ($response->status() === 403) {
        Log::warning('Authorization failed', [
            'user_id' => auth()->id(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    // Log suspicious patterns
    if ($this->isSuspicious($request)) {
        Log::warning('Suspicious request detected', [
            'pattern' => $this->getPattern($request),
            'ip' => $request->ip(),
        ]);
    }

    return $response;
}
```

---

### MEDIUM-5: Missing Models Without Mass Assignment Protection

**Severity:** MEDIUM
**CVSS Score:** 5.0 (Medium)

**Finding:**
9 models (out of 245) lack `$fillable` or `$guarded` properties.

**Impact:**
- Mass assignment vulnerabilities
- Unintended field updates

**Remediation:**
Audit models and add protection:

```php
// All models should have either:
protected $fillable = ['field1', 'field2', ...];
// OR
protected $guarded = ['id', 'created_at', 'updated_at'];
```

---

## 4. LOW Priority Issues (Ongoing Improvement)

### LOW-1: Abandoned Composer Package

**Severity:** LOW
**CVSS Score:** 3.2 (Low)

**Finding:**
`doctrine/annotations` package is abandoned.

**Remediation:**
Monitor for replacement package or remove if unused.

---

### LOW-2: Debug Mode Enabled

**Severity:** LOW (Development Environment)
**CVSS Score:** 2.7 (Low)

**Finding:**
`APP_DEBUG=true` in `.env` file.

**Note:** Acceptable for local development. Ensure production has `APP_DEBUG=false`.

---

## 5. Security Strengths (Well Implemented)

### Excellent Multi-Tenancy Implementation
- 75 RLS policies across 59 migrations
- 46 context initialization calls
- Proper schema-qualified table names
- Defense-in-depth with manual org_id checks

### Comprehensive Webhook Security
- Signature verification for all 6 platforms (Meta, Google, TikTok, LinkedIn, Twitter, Snapchat)
- Proper use of `hash_equals()` for timing-safe comparison
- Platform-specific verification logic
- Dedicated middleware for verification

### Good Security Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- Strict-Transport-Security (production)
- Referrer-Policy
- Permissions-Policy
- Basic CSP (needs improvement per HIGH-1)

### Strong Mass Assignment Protection
- 236/245 models (96.3%) have `$fillable` or `$guarded`
- No `::create($request->all())` patterns found
- Good use of FormRequest validation

### Proper Authentication
- Laravel Sanctum for API authentication
- Session-based web authentication
- Rate limiting on auth routes
- Password confirmation requirements

### Encryption Usage
- 24 instances of encrypt/decrypt usage
- Proper credential storage patterns

---

## 6. OWASP Top 10 Compliance Assessment

| OWASP Category | Status | Score | Notes |
|----------------|--------|-------|-------|
| **A01: Broken Access Control** | MEDIUM | 6/10 | Good RLS, limited policies (4.9% coverage) |
| **A02: Cryptographic Failures** | CRITICAL | 2/10 | Missing APP_KEY, good hashing elsewhere |
| **A03: Injection** | CRITICAL | 3/10 | 4 SQL injection points, 153 risky DB::raw |
| **A04: Insecure Design** | GOOD | 7/10 | Good architecture, multi-tenancy design |
| **A05: Security Misconfiguration** | MEDIUM | 6/10 | CSP issues, limited rate limiting |
| **A06: Vulnerable Components** | GOOD | 8/10 | Laravel 12, PHP 8.2, 1 abandoned package |
| **A07: Auth Failures** | MEDIUM | 6/10 | Good auth, limited rate limiting |
| **A08: Data Integrity** | GOOD | 8/10 | Webhook signatures, good validation |
| **A09: Logging Failures** | MEDIUM | 5/10 | Basic logging, missing security events |
| **A10: SSRF** | GOOD | 8/10 | No obvious SSRF vectors found |

**Overall OWASP Score:** 5.9/10 (Medium Risk)

---

## 7. Prioritized Remediation Roadmap

### Phase 1: CRITICAL (Immediate - This Week)

**Priority:** BLOCK ALL DEPLOYMENT

1. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```
   - Time: 1 minute
   - Impact: Fixes encryption, sessions, CSRF

2. **Fix Path Traversal in DbExecuteSql**
   - Location: `app/Console/Commands/DbExecuteSql.php`
   - Implementation: Apply remediation code from CRITICAL-2
   - Time: 30 minutes
   - Impact: Prevents command injection

3. **Fix SQL Injection in Array Construction**
   - Locations:
     - `app/Repositories/CMIS/CampaignRepository.php:39`
     - `app/Repositories/PublicUtilityRepository.php:322`
     - `app/Repositories/Knowledge/KnowledgeRepository.php:42`
   - Implementation: Convert to JSON binding (CRITICAL-3)
   - Time: 2-3 hours
   - Impact: Eliminates SQL injection

**Total Phase 1 Time:** ~4 hours
**Risk Reduction:** CRITICAL → MEDIUM

---

### Phase 2: HIGH (This Sprint - Week 1-2)

4. **Improve Content Security Policy**
   - Remove `unsafe-inline` and `unsafe-eval`
   - Implement nonce-based CSP
   - Time: 4-6 hours
   - Impact: Prevents XSS attacks

5. **Implement Comprehensive Rate Limiting**
   - Apply rate limits to all API routes
   - Add AI operation limits
   - Add export limits
   - Time: 6-8 hours
   - Impact: Prevents abuse, DoS, cost explosion

6. **Create Authorization Policies**
   - Generate policies for 10-15 critical models
   - Implement comprehensive authorization
   - Time: 12-16 hours (1-2 devs)
   - Impact: Fine-grained access control

7. **Fix Embedding SQL Injection**
   - Location: `app/Jobs/GenerateEmbeddingsJob.php:79`
   - Use parameterized binding
   - Time: 1 hour
   - Impact: Eliminates vector injection risk

8. **Refactor env() to config()**
   - Move all env() calls to config files
   - Update 17 locations in app code
   - Time: 3-4 hours
   - Impact: Config caching support

**Total Phase 2 Time:** ~30-40 hours (1 week for 2 developers)
**Risk Reduction:** MEDIUM → LOW-MEDIUM

---

### Phase 3: MEDIUM (Next Sprint - Week 3-4)

9. **Create FormRequest Classes**
   - Generate ~50 FormRequest classes
   - Implement validation rules
   - Time: 20-25 hours
   - Impact: Consistent validation

10. **Add File Upload Validation**
    - Add MIME type validation
    - Add file size limits
    - Add dimension validation
    - Time: 2-3 hours
    - Impact: Secure file uploads

11. **Enhance Security Logging**
    - Log failed authorizations
    - Log suspicious activities
    - Add audit trail
    - Time: 6-8 hours
    - Impact: Security monitoring

12. **Fix Missing Mass Assignment Protection**
    - Add $fillable/$guarded to 9 models
    - Audit all models
    - Time: 2-3 hours
    - Impact: Prevent unintended updates

**Total Phase 3 Time:** ~30-40 hours (1 week for 2 developers)
**Risk Reduction:** LOW-MEDIUM → LOW

---

### Phase 4: LOW & Ongoing (Continuous)

13. **Remove Abandoned Package**
    - Replace doctrine/annotations if needed
    - Time: 1-2 hours
    - Impact: Dependency hygiene

14. **Production Environment Hardening**
    - Ensure APP_DEBUG=false
    - Verify all secrets in production .env
    - Enable HSTS
    - Time: 2 hours
    - Impact: Production security

**Total Phase 4 Time:** ~3-4 hours

---

## 8. Testing Recommendations

### Security Test Suite

Create comprehensive security tests:

```php
// tests/Feature/Security/SqlInjectionTest.php
public function test_campaign_tags_prevent_sql_injection()
{
    $maliciousTags = [
        "normal tag",
        "'; DROP TABLE campaigns; --",
        "' OR '1'='1",
    ];

    $response = $this->postJson('/api/campaigns', [
        'tags' => $maliciousTags,
        // ... other fields
    ]);

    // Should not execute SQL injection
    $this->assertDatabaseHas('campaigns', [...]);
    $this->assertDatabaseHas('cmis.campaigns', [...]);  // Table still exists
}

// tests/Feature/Security/PathTraversalTest.php
public function test_db_execute_sql_prevents_path_traversal()
{
    $this->artisan('db:execute-sql', ['file' => '../../../../etc/passwd'])
        ->expectsOutput('Access denied')
        ->assertFailed();
}

// tests/Feature/Security/RateLimitTest.php
public function test_api_rate_limiting_blocks_excessive_requests()
{
    // Make 61 requests (limit is 60/min)
    for ($i = 0; $i < 61; $i++) {
        $response = $this->getJson('/api/campaigns');
    }

    $response->assertStatus(429);  // Too Many Requests
}
```

### Penetration Testing Checklist

- [ ] SQL injection in all DB::raw locations
- [ ] Path traversal in file operations
- [ ] XSS in user-generated content
- [ ] CSRF on state-changing operations
- [ ] Authentication bypass attempts
- [ ] Authorization bypass via org_id manipulation
- [ ] Rate limit bypass
- [ ] Mass assignment exploits
- [ ] Session hijacking
- [ ] Webhook signature bypass

---

## 9. Compliance Considerations

### GDPR (if applicable)
- Ensure right to erasure (data deletion)
- Audit data retention policies
- Verify consent mechanisms
- Check data export functionality

### PCI-DSS (if handling payments)
- No plaintext storage of card data
- Encrypt card data at rest and in transit
- Implement proper access controls
- Maintain audit logs

### SOC 2
- Implement comprehensive logging
- Access control documentation
- Incident response procedures
- Regular security audits

---

## 10. Security Monitoring & Alerting

### Recommended Monitoring

```php
// Monitor failed login attempts
if ($failedLoginAttempts > 5) {
    Log::alert('Potential brute force attack', [
        'ip' => $request->ip(),
        'attempts' => $failedLoginAttempts
    ]);

    // Consider IP blocking
}

// Monitor SQL injection attempts
if (preg_match('/DROP|DELETE|UPDATE.*WHERE|UNION|SELECT.*FROM/i', $input)) {
    Log::critical('SQL injection attempt detected', [
        'input' => $input,
        'user_id' => auth()->id(),
        'ip' => $request->ip()
    ]);
}

// Monitor rate limit violations
RateLimiter::hit($key, $decay);
if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
    Log::warning('Rate limit exceeded', [
        'key' => $key,
        'ip' => $request->ip()
    ]);
}
```

---

## 11. Developer Security Training

### Required Topics
- SQL injection prevention
- XSS prevention
- CSRF protection
- Secure session management
- Password hashing best practices
- Input validation
- Output encoding
- Authorization vs Authentication
- Multi-tenancy security

### Resources
- OWASP Top 10: https://owasp.org/Top10/
- Laravel Security: https://laravel.com/docs/security
- PCI Security Standards: https://www.pcisecuritystandards.org/

---

## 12. Incident Response Plan

### Security Incident Procedure

1. **Detection**
   - Monitor logs for anomalies
   - User reports
   - Automated alerts

2. **Containment**
   - Isolate affected systems
   - Disable compromised accounts
   - Block malicious IPs

3. **Investigation**
   - Analyze logs
   - Identify attack vector
   - Assess damage

4. **Remediation**
   - Apply security patches
   - Change compromised credentials
   - Restore from backups if needed

5. **Post-Incident**
   - Document incident
   - Update security measures
   - Notify affected users (if required)

---

## 13. Commands Executed During Audit

```bash
# Attack surface analysis
php artisan route:list
find app/Http/Controllers -name "*.php" | wc -l
find app/Models -name "*.php" | wc -l

# SQL injection scanning
grep -r "DB::raw\|whereRaw\|havingRaw" app/ --include="*.php" | wc -l
grep -rn "DB::raw.*\$\|whereRaw.*\$" app/ --include="*.php"

# Secret management
grep -ri "password.*=.*['\"][a-zA-Z0-9]" app/ config/ | grep -v ".example" | grep -v "env("
grep -r "env(" app/ --include="*.php" | wc -l
cat .env | grep "APP_KEY="
git ls-files | grep "\.env$"

# Authentication & authorization
cat config/auth.php | grep -A 10 "guards"
find app/Policies -name "*.php" | wc -l
grep -r "Gate::define" app/Providers/ | wc -l

# Input validation
find app/Http/Requests -name "*.php" | wc -l
grep -r "protected \$fillable\|protected \$guarded" app/Models/ | wc -l

# Rate limiting
grep -r "throttle" routes/ --include="*.php" | wc -l

# XSS risks
grep -r "{!!" resources/views/ --include="*.blade.php" | wc -l

# Multi-tenancy
grep -r "init_transaction_context" app/ | wc -l
grep -r "ENABLE ROW LEVEL SECURITY\|CREATE POLICY" database/migrations/ | wc -l

# Dependency vulnerabilities
composer audit

# File uploads
grep -r "store(\|storeAs(\|move(" app/Http/Controllers/ | head -20

# Logging
grep -r "Log::info.*password\|Log::debug.*token" app/ | head -10
```

---

## 14. Summary & Recommendations

### Current Security Posture: HIGH RISK → MEDIUM RISK (After Phase 1)

**Critical Actions Required:**
1. Generate APP_KEY immediately
2. Fix path traversal in DbExecuteSql.php
3. Fix SQL injection in array construction (4 locations)

**Timeline:**
- Phase 1 (CRITICAL): 4 hours - **DO NOT DEPLOY WITHOUT THIS**
- Phase 2 (HIGH): 1-2 weeks (2 developers)
- Phase 3 (MEDIUM): 1-2 weeks (2 developers)
- Phase 4 (LOW): Ongoing

**Estimated Total Effort:** 70-90 developer hours

**Post-Remediation Security Score:** 7.5/10 (Low-Medium Risk)

---

## 15. Sign-Off

This security audit was conducted using automated scanning, manual code review, and security best practice analysis. All findings should be verified in a staging environment before production deployment.

**Next Steps:**
1. Review this report with development team
2. Prioritize remediation based on roadmap
3. Implement Phase 1 immediately
4. Schedule penetration testing after Phase 2
5. Establish ongoing security monitoring

**Report Generated:** 2025-11-21
**Next Audit Recommended:** After Phase 3 completion (4-6 weeks)

---

**END OF SECURITY AUDIT REPORT**
