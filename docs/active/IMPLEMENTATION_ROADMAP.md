# CMIS Implementation Roadmap
**Version:** 1.0
**Date:** November 21, 2025
**Status:** Ready for Execution

This document provides detailed, actionable steps for implementing all improvements identified in the comprehensive platform analysis.

---

## Phase 0: Emergency Security Fixes (4 hours) ⚡

### Task 0.1: Generate Application Key (1 minute)

**Priority:** P0 - CRITICAL BLOCKER
**File:** `.env`

```bash
# Generate APP_KEY
php artisan key:generate

# Verify it was set
php artisan tinker --execute="echo config('app.key') ? '✅ KEY SET' : '❌ KEY MISSING';"
```

### Task 0.2: Fix Command Injection Vulnerability (30 minutes)

**Priority:** P0 - CRITICAL (CVSS 9.1)
**File:** `app/Console/Commands/DbExecuteSql.php`

**Current vulnerable code:**
```php
public function handle()
{
    $file = base_path($this->argument("file"));  // NO VALIDATION!
    $sql = file_get_contents($file);
    DB::unprepared($sql);
}
```

**Secure replacement:**
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DbExecuteSql extends Command
{
    protected $signature = 'db:execute-sql {file}';
    protected $description = 'Execute SQL from a file (SECURE VERSION)';

    public function handle()
    {
        $filename = $this->argument('file');

        // Security: Restrict to specific directory
        $allowedDir = database_path('sql');
        $filePath = realpath($allowedDir . '/' . $filename);

        // Validate path
        if (!$filePath || !str_starts_with($filePath, realpath($allowedDir))) {
            $this->error('❌ Invalid file path. Only files in database/sql/ are allowed.');
            return 1;
        }

        if (!File::exists($filePath)) {
            $this->error('❌ File not found: ' . $filename);
            return 1;
        }

        // Require confirmation in production
        if (app()->environment('production')) {
            if (!$this->confirm('⚠️  Execute SQL in PRODUCTION?')) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        $sql = File::get($filePath);

        $this->info('Executing SQL from: ' . $filename);

        try {
            DB::unprepared($sql);
            $this->info('✅ SQL executed successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
```

**Create allowed directory:**
```bash
mkdir -p database/sql
echo "-- SQL scripts only" > database/sql/README.md
```

### Task 0.3: Fix SQL Injection in Array Construction (2-3 hours)

**Priority:** P0 - CRITICAL (CVSS 8.8)

#### Location 1: CampaignRepository.php:39

**File:** `app/Repositories/CMIS/CampaignRepository.php`

**Current vulnerable code:**
```php
DB::raw("ARRAY['" . implode("','", $tags) . "']")
```

**Secure replacement:**
```php
// Use JSON binding instead
$query->whereRaw("tags @> ?::jsonb", [json_encode($tags)]);
```

#### Location 2: PublicUtilityRepository.php:322

**File:** `app/Repositories/PublicUtilityRepository.php`

**Current vulnerable code:**
```php
DB::raw("ARRAY['" . implode("','", $values) . "']")
```

**Secure replacement:**
```php
// Option 1: Use JSON binding
$query->whereRaw("column_name @> ?::jsonb", [json_encode($values)]);

// Option 2: Use parameterized array literal
$placeholders = implode(',', array_fill(0, count($values), '?'));
$query->whereRaw("column_name = ANY(ARRAY[$placeholders])", $values);
```

#### Location 3: KnowledgeRepository.php:42

**File:** `app/Repositories/Knowledge/KnowledgeRepository.php`

**Same fix as above.**

**Complete fix script:**
```bash
# Create a migration to add GIN indexes for JSON operations (if needed)
php artisan make:migration add_gin_indexes_for_json_columns
```

**Migration content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add GIN indexes for better JSON query performance
        DB::statement('CREATE INDEX IF NOT EXISTS idx_campaigns_tags_gin ON cmis.campaigns USING GIN (tags);');

        // Add more as needed for other tables
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS cmis.idx_campaigns_tags_gin;');
    }
};
```

### Task 0.4: Verification & Deployment (30 minutes)

```bash
# 1. Run security scan
./vendor/bin/phpstan analyse app/Console/Commands/DbExecuteSql.php --level=8

# 2. Search for remaining SQL injection patterns
grep -rn "DB::raw.*implode" app/ --include="*.php"
grep -rn "DB::raw.*\$" app/ --include="*.php" | wc -l

# 3. Run tests
php artisan test --testsuite=Security

# 4. Deploy to staging
git add .
git commit -m "fix: resolve P0 security vulnerabilities (APP_KEY, command injection, SQL injection)"
git push -u origin claude/platform-analysis-optimization-013forsMg43VpdoBqySkLkHQ

# 5. Re-scan
# (Manual security review)
```

---

## Phase 1: Critical Infrastructure (Weeks 1-3)

### Week 1: Database & Multi-Tenancy

#### Task 1.1: Add Primary Keys to 151 Tables (4 hours)

**Create migration generator:**

```bash
php artisan make:command GeneratePrimaryKeyMigrations
```

**File:** `app/Console/Commands/GeneratePrimaryKeyMigrations.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneratePrimaryKeyMigrations extends Command
{
    protected $signature = 'db:generate-pk-migrations';
    protected $description = 'Generate migrations for tables missing primary keys';

    public function handle()
    {
        // Get tables without primary keys
        $tables = DB::select("
            SELECT schemaname, tablename
            FROM pg_tables t
            WHERE schemaname LIKE 'cmis%'
            AND NOT EXISTS (
                SELECT 1 FROM pg_constraint c
                WHERE c.conrelid = (schemaname||'.'||tablename)::regclass
                AND c.contype = 'p'
            )
            ORDER BY schemaname, tablename
        ");

        $this->info('Found ' . count($tables) . ' tables without primary keys.');

        foreach ($tables as $table) {
            $schema = $table->schemaname;
            $tableName = $table->tablename;
            $migrationName = 'add_primary_key_to_' . $tableName . '_table';

            $migrationContent = $this->generateMigration($schema, $tableName);

            $filename = database_path('migrations/' . date('Y_m_d_His') . '_' . $migrationName . '.php');
            file_put_contents($filename, $migrationContent);

            $this->info("✅ Created: $filename");
            sleep(1); // Ensure unique timestamps
        }

        $this->info('✅ All migrations generated.');
    }

    protected function generateMigration($schema, $table)
    {
        $className = Str::studly('add_primary_key_to_' . $table . '_table');
        $fullTable = "$schema.$table";

        // Determine likely PK column name
        $pkColumn = $table . '_id';

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Add primary key to $fullTable
        DB::statement('ALTER TABLE $fullTable ADD PRIMARY KEY ($pkColumn);');
    }

    public function down()
    {
        DB::statement('ALTER TABLE $fullTable DROP CONSTRAINT {$table}_pkey;');
    }
};
PHP;
    }
}
```

**Execute:**
```bash
php artisan db:generate-pk-migrations
php artisan migrate --pretend  # Preview
php artisan migrate  # Execute
```

#### Task 1.2: Fix 7 Broken Migrations (4 hours)

**Migration 1: Markets View**

**File:** `database/migrations/XXXX_XX_XX_create_markets_view.php`

```php
public function up()
{
    DB::statement('DROP VIEW IF EXISTS cmis.markets CASCADE;');
    DB::statement("
        CREATE VIEW cmis.markets AS
        SELECT market_id, market_name, language_code, currency_code,
               text_direction, created_at, updated_at
        FROM public.markets
    ");
}
```

**Migration 2-7:** Review and fix based on specific errors in audit report.

#### Task 1.3: Add RLS Policies to Critical Tables (20 hours)

**Create RLS policy generator:**

```bash
php artisan make:command GenerateRLSPolicies
```

**File:** `app/Console/Commands/GenerateRLSPolicies.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRLSPolicies extends Command
{
    protected $signature = 'db:generate-rls-policies {--schema=cmis}';
    protected $description = 'Generate RLS policies for tables missing them';

    protected $criticalTables = [
        'users', 'roles', 'permissions', 'campaigns', 'budgets',
        'social_posts', 'leads', 'contacts', 'invoices', 'payments'
    ];

    public function handle()
    {
        $schema = $this->option('schema');

        // Get tables without RLS
        $tables = DB::select("
            SELECT tablename
            FROM pg_tables
            WHERE schemaname = ?
            AND rowsecurity = false
            ORDER BY tablename
        ", [$schema]);

        $this->info('Found ' . count($tables) . ' tables without RLS in schema: ' . $schema);

        $migrationContent = $this->generateMigration($schema, $tables);

        $filename = database_path('migrations/' . date('Y_m_d_His') . '_add_rls_policies_to_' . $schema . '_tables.php');
        file_put_contents($filename, $migrationContent);

        $this->info("✅ Created: $filename");
    }

    protected function generateMigration($schema, $tables)
    {
        $upStatements = [];
        $downStatements = [];

        foreach ($tables as $table) {
            $tableName = $table->tablename;
            $fullTable = "$schema.$tableName";
            $policyName = "org_isolation_" . $tableName;

            $upStatements[] = "        // Enable RLS on $fullTable\n" .
                            "        DB::statement('ALTER TABLE $fullTable ENABLE ROW LEVEL SECURITY;');\n" .
                            "        DB::statement(\"\n" .
                            "            CREATE POLICY $policyName ON $fullTable\n" .
                            "            USING (org_id = current_setting('app.current_org_id')::uuid)\n" .
                            "            WITH CHECK (org_id = current_setting('app.current_org_id')::uuid);\n" .
                            "        \");\n";

            $downStatements[] = "        DB::statement('DROP POLICY IF EXISTS $policyName ON $fullTable;');\n" .
                              "        DB::statement('ALTER TABLE $fullTable DISABLE ROW LEVEL SECURITY;');\n";
        }

        $upCode = implode("\n", $upStatements);
        $downCode = implode("\n", $downStatements);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
$upCode
    }

    public function down()
    {
$downCode
    }
};
PHP;
    }
}
```

**Execute:**
```bash
php artisan db:generate-rls-policies --schema=cmis
php artisan migrate
```

#### Task 1.4: Remove RLS Bypass Function (2 hours)

**Create migration:**

```bash
php artisan make:migration remove_rls_bypass_function
```

**File:** `database/migrations/XXXX_XX_XX_remove_rls_bypass_function.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop the dangerous bypass function
        DB::statement('DROP FUNCTION IF EXISTS cmis.bypass_rls(BOOLEAN);');

        // Update all policies to remove bypass clauses
        $tables = ['ad_campaigns', 'ad_accounts', 'ad_sets', 'ad_entities', 'ad_metrics', 'ad_audiences'];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS org_isolation_$table ON cmis.$table;");
            DB::statement("
                CREATE POLICY org_isolation_$table ON cmis.$table
                USING (org_id = current_setting('app.current_org_id')::uuid)
                WITH CHECK (org_id = current_setting('app.current_org_id')::uuid);
            ");
        }
    }

    public function down()
    {
        // Don't recreate the bypass - it's a security vulnerability
        $this->warn('⚠️  RLS bypass function will NOT be recreated (security risk)');
    }
};
```

#### Task 1.5: Consolidate Middleware (4 hours)

**File:** `app/Http/Middleware/SetOrganizationContext.php` (NEW - Consolidated)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationContext
{
    /**
     * Handle an incoming request and set organization context for RLS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get org_id from user's current_org_id property
        $orgId = $user->current_org_id ?? $user->org_id;

        if (!$orgId) {
            Log::error('User has no organization', ['user_id' => $user->id]);
            return response()->json(['error' => 'No organization assigned'], 403);
        }

        // Validate org_id is a valid UUID
        if (!$this->isValidUuid($orgId)) {
            Log::error('Invalid organization ID', ['user_id' => $user->id, 'org_id' => $orgId]);
            return response()->json(['error' => 'Invalid organization'], 403);
        }

        try {
            // Set RLS context using the init_transaction_context function
            DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
                $user->id,
                $orgId
            ]);

            // Verify context was set
            $currentOrg = DB::selectOne('SELECT current_setting(\'app.current_org_id\', true) as org_id');

            if ($currentOrg->org_id !== $orgId) {
                Log::error('RLS context mismatch', [
                    'expected' => $orgId,
                    'actual' => $currentOrg->org_id
                ]);
                return response()->json(['error' => 'Context initialization failed'], 500);
            }

            // Add org_id to request for easy access
            $request->merge(['_org_id' => $orgId]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Failed to set RLS context', [
                'user_id' => $user->id,
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Failed to initialize context'], 500);
        }
    }

    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
```

**Update routes to use consolidated middleware:**

**File:** `routes/api.php`

```php
// Replace all instances of:
// ->middleware(['auth:sanctum', 'set.rls.context'])
// OR
// ->middleware(['auth:sanctum', 'set.database.context'])
// OR
// ->middleware(['auth:sanctum', 'set.org.context'])

// With single consolidated middleware:
->middleware(['auth:sanctum', 'org.context'])
```

**Register middleware in:** `bootstrap/app.php` or `app/Http/Kernel.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'org.context' => \App\Http\Middleware\SetOrganizationContext::class,
    ]);
})
```

**Delete old middleware files:**
```bash
rm app/Http/Middleware/SetRLSContext.php
rm app/Http/Middleware/SetDatabaseContext.php
rm app/Http/Middleware/SetOrgContextMiddleware.php
```

#### Task 1.6: Remove Manual org_id Filtering (6 hours)

**Pattern to remove:**

```php
// BEFORE (WRONG - bypasses RLS):
$campaigns = Campaign::where('org_id', $orgId)->get();

// AFTER (CORRECT - trust RLS):
$campaigns = Campaign::all();  // RLS automatically filters
```

**Files to fix** (20+ files):
- `app/Repositories/CMIS/CampaignRepository.php:143`
- All service files with manual org_id filtering
- All controller files with manual org_id filtering

**Search and identify:**
```bash
grep -rn "where('org_id'" app/ --include="*.php" > manual_org_filtering.txt
grep -rn 'where("org_id"' app/ --include="*.php" >> manual_org_filtering.txt
```

**Fix each file manually, example:**

**File:** `app/Repositories/CMIS/CampaignRepository.php`

```php
// BEFORE:
public function getActiveCampaigns($orgId)
{
    return Campaign::where('org_id', $orgId)
        ->where('status', 'active')
        ->get();
}

// AFTER:
public function getActiveCampaigns()  // Remove $orgId parameter
{
    return Campaign::where('status', 'active')
        ->get();  // RLS handles org_id filtering
}
```

### Week 2: Performance & Security

#### Task 2.1: Switch to Redis Cache (30 minutes)

**File:** `.env`

```bash
# Change these values:
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Verify Redis is running:
redis-cli ping
# Should return: PONG

# If not installed:
# sudo apt-get install redis-server
# sudo systemctl start redis
```

**File:** `config/database.php` (ensure Redis config exists)

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

**Clear old cache and test:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan tinker --execute="Cache::put('test', 'redis_works', 60); echo Cache::get('test');"
```

#### Task 2.2: Add Eager Loading (12 hours)

**Top 20 controllers to fix:**

1. `app/Http/Controllers/DashboardController.php`
2. `app/Http/Controllers/Campaigns/CampaignController.php`
3. `app/Http/Controllers/Content/ContentController.php`
4. ... (see audit report for full list)

**Example fix:**

**File:** `app/Http/Controllers/DashboardController.php`

```php
// BEFORE (N+1 queries):
public function index()
{
    $campaigns = Campaign::all();  // 1 query

    foreach ($campaigns as $campaign) {
        $org = $campaign->org;  // +N queries
        $creator = $campaign->creator;  // +N queries
        $contentPlans = $campaign->contentPlans;  // +N queries
    }
}

// AFTER (single query with joins):
public function index()
{
    $campaigns = Campaign::with([
        'org',
        'creator',
        'contentPlans.items',
        'contentPlans.approvals'
    ])->get();  // 1-2 queries total

    // Now all relationships are already loaded
}
```

**Add query count monitoring:**

**File:** `app/Providers/AppServiceProvider.php`

```php
public function boot()
{
    if (app()->environment('local')) {
        DB::listen(function ($query) {
            Log::channel('query')->info($query->sql, [
                'bindings' => $query->bindings,
                'time' => $query->time
            ]);
        });

        // Count queries per request
        DB::enableQueryLog();
    }
}
```

**Add test to catch N+1:**

**File:** `tests/Feature/Performance/N1QueryTest.php`

```php
<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class N1QueryTest extends TestCase
{
    public function test_dashboard_has_no_n1_queries()
    {
        $user = $this->createUserWithOrg();
        $this->actingAs($user['user']);

        // Create test data
        Campaign::factory()->count(10)->create(['org_id' => $user['org']->org_id]);

        DB::enableQueryLog();

        $response = $this->get('/dashboard');

        $queryCount = count(DB::getQueryLog());

        $this->assertLessThan(20, $queryCount,
            "Dashboard executed $queryCount queries. Expected < 20. Possible N+1 issue."
        );
    }
}
```

#### Task 2.3: Cache Analytics Queries (4 hours)

**File:** `app/Repositories/Analytics/AnalyticsRepository.php`

```php
<?php

namespace App\Repositories\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository
{
    /**
     * Get campaign performance metrics with caching
     */
    public function getCampaignMetrics(string $campaignId, string $dateRange = '30d')
    {
        $cacheKey = "analytics:campaign:$campaignId:$dateRange";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($campaignId) {
            return DB::select("
                SELECT
                    date_trunc('day', metric_date) as date,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(conversions) as conversions,
                    SUM(spend) as spend
                FROM cmis.ad_metrics
                WHERE campaign_id = ?
                AND metric_date >= NOW() - INTERVAL '30 days'
                GROUP BY date_trunc('day', metric_date)
                ORDER BY date
            ", [$campaignId]);
        });
    }

    /**
     * Get organization-level dashboard metrics
     */
    public function getOrgDashboard(string $orgId)
    {
        $cacheKey = "analytics:org:$orgId:dashboard";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            // Note: No need to filter by org_id - RLS handles it!
            return [
                'total_campaigns' => DB::table('cmis.campaigns')->count(),
                'active_campaigns' => DB::table('cmis.campaigns')
                    ->where('status', 'active')->count(),
                'total_spend_today' => DB::table('cmis.ad_metrics')
                    ->whereDate('metric_date', today())
                    ->sum('spend'),
                'total_impressions_today' => DB::table('cmis.ad_metrics')
                    ->whereDate('metric_date', today())
                    ->sum('impressions'),
            ];
        });
    }

    /**
     * Invalidate cache for a campaign
     */
    public function invalidateCampaignCache(string $campaignId)
    {
        $patterns = [
            "analytics:campaign:$campaignId:*",
        ];

        foreach ($patterns as $pattern) {
            // Use Redis KEYS command (use scan in production)
            $keys = Cache::getRedis()->keys($pattern);
            if ($keys) {
                Cache::getRedis()->del($keys);
            }
        }
    }
}
```

**Add cache invalidation to campaign updates:**

**File:** `app/Services/CMIS/CampaignService.php`

```php
use App\Repositories\Analytics\AnalyticsRepository;

public function __construct(
    private CampaignRepository $campaignRepository,
    private AnalyticsRepository $analyticsRepository
) {}

public function update(string $campaignId, array $data)
{
    $campaign = $this->campaignRepository->update($campaignId, $data);

    // Invalidate caches
    $this->analyticsRepository->invalidateCampaignCache($campaignId);

    return $campaign;
}
```

#### Task 2.4: Queue AI Operations (4 hours)

**Create job:**

```bash
php artisan make:job GenerateAdDesignJob
```

**File:** `app/Jobs/GenerateAdDesignJob.php`

```php
<?php

namespace App\Jobs;

use App\Services\AI\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAdDesignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;  // 2 minutes
    public $tries = 3;

    public function __construct(
        public string $userId,
        public string $orgId,
        public array $params,
        public string $jobId
    ) {}

    public function handle(GeminiService $geminiService)
    {
        // Set RLS context for this job
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [
            $this->userId,
            $this->orgId
        ]);

        try {
            $result = $geminiService->generateAdDesign($this->params);

            // Store result
            DB::table('cmis.ai_generation_results')->insert([
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'status' => 'completed',
                'result' => json_encode($result),
                'completed_at' => now()
            ]);

            Log::info('Ad design generated', ['job_id' => $this->jobId]);

        } catch (\Exception $e) {
            Log::error('Ad design generation failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage()
            ]);

            DB::table('cmis.ai_generation_results')->insert([
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now()
            ]);

            throw $e;
        }
    }
}
```

**Update controller to dispatch job:**

**File:** `app/Http/Controllers/AI/AIGenerationController.php`

```php
use App\Jobs\GenerateAdDesignJob;
use Illuminate\Support\Str;

public function generateAdDesign(Request $request)
{
    $jobId = (string) Str::uuid();

    // Dispatch to queue (returns immediately)
    GenerateAdDesignJob::dispatch(
        $request->user()->id,
        $request->user()->current_org_id,
        $request->validated(),
        $jobId
    );

    return response()->json([
        'message' => 'Generation started',
        'job_id' => $jobId,
        'status_url' => route('ai.status', ['job_id' => $jobId])
    ], 202);  // 202 Accepted
}

public function checkStatus(Request $request, string $jobId)
{
    $result = DB::table('cmis.ai_generation_results')
        ->where('job_id', $jobId)
        ->first();

    if (!$result) {
        return response()->json([
            'status' => 'processing',
            'message' => 'Generation in progress...'
        ]);
    }

    return response()->json([
        'status' => $result->status,
        'result' => $result->result ? json_decode($result->status) : null,
        'error' => $result->error,
        'completed_at' => $result->completed_at
    ]);
}
```

**Create results table migration:**

```bash
php artisan make:migration create_ai_generation_results_table
```

```php
public function up()
{
    Schema::create('cmis.ai_generation_results', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('job_id')->unique();
        $table->uuid('user_id');
        $table->string('status');
        $table->json('result')->nullable();
        $table->text('error')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    // Add RLS
    DB::statement('ALTER TABLE cmis.ai_generation_results ENABLE ROW LEVEL SECURITY;');
    DB::statement("
        CREATE POLICY org_isolation_ai_generation_results ON cmis.ai_generation_results
        USING (user_id IN (SELECT user_id FROM cmis.users WHERE org_id = current_setting('app.current_org_id')::uuid));
    ");
}
```

### Week 3: AI & Testing

#### Task 3.1: Create Vector Indexes (2 hours)

**Create migration:**

```bash
php artisan make:migration create_vector_indexes_for_embeddings
```

**File:** `database/migrations/XXXX_XX_XX_create_vector_indexes_for_embeddings.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Create IVFFlat indexes for all embedding columns

        $tables = [
            'cmis.embeddings_cache' => 'embedding',
            'cmis_ai.campaign_embeddings' => 'embedding',
            'cmis_ai.content_embeddings' => 'embedding',
            'cmis_ai.creative_embeddings' => 'embedding',
        ];

        foreach ($tables as $table => $column) {
            $tableName = str_replace('.', '_', $table);
            $indexName = "idx_{$tableName}_{$column}_ivfflat";

            // Create IVFFlat index for fast cosine similarity search
            DB::statement("
                CREATE INDEX $indexName
                ON $table
                USING ivfflat ($column vector_cosine_ops)
                WITH (lists = 100);
            ");
        }
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_cmis_embeddings_cache_embedding_ivfflat;');
        DB::statement('DROP INDEX IF EXISTS idx_cmis_ai_campaign_embeddings_embedding_ivfflat;');
        DB::statement('DROP INDEX IF EXISTS idx_cmis_ai_content_embeddings_embedding_ivfflat;');
        DB::statement('DROP INDEX IF EXISTS idx_cmis_ai_creative_embeddings_embedding_ivfflat;');
    }
};
```

**Run migration:**
```bash
php artisan migrate
```

**Verify index creation:**
```sql
SELECT
    schemaname,
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE indexname LIKE '%vector%' OR indexname LIKE '%embedding%'
ORDER BY schemaname, tablename;
```

#### Task 3.2: Implement Real SemanticSearchService (8 hours)

**File:** `app/Services/CMIS/SemanticSearchService.php`

```php
<?php

namespace App\Services\CMIS;

use App\Services\AI\GeminiEmbeddingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SemanticSearchService
{
    public function __construct(
        private GeminiEmbeddingService $embeddingService
    ) {}

    /**
     * Perform semantic search across campaigns
     *
     * @param string $query The search query
     * @param int $limit Number of results to return
     * @param float $threshold Similarity threshold (0.0-1.0)
     * @return array
     */
    public function searchCampaigns(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        try {
            // Generate embedding for search query
            $queryEmbedding = $this->embeddingService->generateEmbedding($query);

            if (!$queryEmbedding) {
                Log::error('Failed to generate query embedding');
                return ['success' => false, 'error' => 'Failed to generate embedding'];
            }

            // Format as PostgreSQL vector literal
            $vectorLiteral = '[' . implode(',', $queryEmbedding) . ']';

            // Perform vector similarity search
            $results = DB::select("
                WITH ranked_campaigns AS (
                    SELECT
                        c.campaign_id,
                        c.campaign_name,
                        c.description,
                        c.status,
                        c.created_at,
                        e.embedding,
                        1 - (e.embedding <=> ?::vector) AS similarity
                    FROM cmis_ai.campaign_embeddings e
                    JOIN cmis.campaigns c ON e.campaign_id = c.campaign_id
                    WHERE 1 - (e.embedding <=> ?::vector) >= ?
                )
                SELECT *
                FROM ranked_campaigns
                ORDER BY similarity DESC
                LIMIT ?
            ", [$vectorLiteral, $vectorLiteral, $threshold, $limit]);

            return [
                'success' => true,
                'query' => $query,
                'results' => array_map(function($result) {
                    return [
                        'campaign_id' => $result->campaign_id,
                        'campaign_name' => $result->campaign_name,
                        'description' => $result->description,
                        'status' => $result->status,
                        'similarity' => round($result->similarity, 4),
                        'created_at' => $result->created_at
                    ];
                }, $results),
                'count' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find similar campaigns based on a campaign ID
     */
    public function findSimilarCampaigns(string $campaignId, int $limit = 5): array
    {
        try {
            $results = DB::select("
                WITH target_campaign AS (
                    SELECT embedding
                    FROM cmis_ai.campaign_embeddings
                    WHERE campaign_id = ?
                )
                SELECT
                    c.campaign_id,
                    c.campaign_name,
                    c.description,
                    1 - (e.embedding <=> t.embedding) AS similarity
                FROM cmis_ai.campaign_embeddings e
                JOIN cmis.campaigns c ON e.campaign_id = c.campaign_id
                CROSS JOIN target_campaign t
                WHERE c.campaign_id != ?
                ORDER BY e.embedding <=> t.embedding
                LIMIT ?
            ", [$campaignId, $campaignId, $limit]);

            return [
                'success' => true,
                'campaign_id' => $campaignId,
                'similar_campaigns' => $results,
                'count' => count($results)
            ];

        } catch (\Exception $e) {
            Log::error('Similar campaigns search failed', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search with caching for frequently used queries
     */
    public function searchWithCache(string $query, int $limit = 10, float $threshold = 0.7): array
    {
        $cacheKey = "semantic_search:" . md5($query . $limit . $threshold);

        return Cache::remember($cacheKey, now()->addHours(1), function() use ($query, $limit, $threshold) {
            return $this->searchCampaigns($query, $limit, $threshold);
        });
    }

    /**
     * Perform search across multiple entity types
     */
    public function universalSearch(string $query, int $limit = 10): array
    {
        $queryEmbedding = $this->embeddingService->generateEmbedding($query);

        if (!$queryEmbedding) {
            return ['success' => false, 'error' => 'Failed to generate embedding'];
        }

        $vectorLiteral = '[' . implode(',', $queryEmbedding) . ']';

        // Search across multiple tables
        $results = [
            'campaigns' => $this->searchInTable('cmis_ai.campaign_embeddings', 'cmis.campaigns', $vectorLiteral, $limit),
            'content' => $this->searchInTable('cmis_ai.content_embeddings', 'cmis.content_plans', $vectorLiteral, $limit),
            'creatives' => $this->searchInTable('cmis_ai.creative_embeddings', 'cmis.ad_creatives', $vectorLiteral, $limit),
        ];

        return [
            'success' => true,
            'query' => $query,
            'results' => $results
        ];
    }

    private function searchInTable(string $embeddingTable, string $dataTable, string $vectorLiteral, int $limit): array
    {
        try {
            return DB::select("
                SELECT *,
                       1 - (embedding <=> ?::vector) AS similarity
                FROM $embeddingTable
                WHERE 1 - (embedding <=> ?::vector) >= 0.6
                ORDER BY embedding <=> ?::vector
                LIMIT ?
            ", [$vectorLiteral, $vectorLiteral, $vectorLiteral, $limit]);
        } catch (\Exception $e) {
            Log::error("Search failed in $embeddingTable", ['error' => $e->getMessage()]);
            return [];
        }
    }
}
```

**Add API endpoint:**

**File:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'org.context'])->group(function () {
    Route::post('/search/semantic', [SearchController::class, 'semantic']);
    Route::get('/campaigns/{campaign}/similar', [CampaignController::class, 'similar']);
});
```

**File:** `app/Http/Controllers/SearchController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SemanticSearchService $searchService
    ) {}

    public function semantic(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
            'threshold' => 'sometimes|numeric|min:0|max:1',
            'use_cache' => 'sometimes|boolean'
        ]);

        $useCache = $validated['use_cache'] ?? true;

        $results = $useCache
            ? $this->searchService->searchWithCache(
                $validated['query'],
                $validated['limit'] ?? 10,
                $validated['threshold'] ?? 0.7
              )
            : $this->searchService->searchCampaigns(
                $validated['query'],
                $validated['limit'] ?? 10,
                $validated['threshold'] ?? 0.7
              );

        return response()->json($results);
    }
}
```

#### Task 3.3: Fix Testing Infrastructure (2.5 hours)

**Fix markets view migration:**

```bash
# File: database/migrations/XXXX_XX_XX_fix_markets_view.php
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test << 'EOF'
DROP VIEW IF EXISTS cmis.markets CASCADE;
CREATE VIEW cmis.markets AS
SELECT market_id, market_name, language_code, currency_code,
       text_direction, created_at, updated_at
FROM public.markets;
EOF
```

**Create parallel test databases (optional):**

```bash
for i in {1..15}; do
    PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d postgres \
        -c "CREATE DATABASE cmis_test_$i TEMPLATE cmis_test;"
done
```

**Run tests:**

```bash
# Refresh test database
PGPASSWORD="123@Marketing@321" psql -h 127.0.0.1 -U begin -d cmis_test -c "DROP SCHEMA IF EXISTS cmis CASCADE;"
php artisan migrate:fresh --env=testing --force

# Run tests
php artisan test

# Run parallel (if databases created)
php artisan test --parallel --processes=15
```

---

## Quick Command Reference

```bash
# Phase 0: Security
php artisan key:generate
php artisan test --testsuite=Security

# Phase 1: Database & RLS
php artisan db:generate-pk-migrations
php artisan db:generate-rls-policies
php artisan migrate

# Phase 1: Performance
# Update .env: CACHE_STORE=redis
php artisan cache:clear
php artisan config:cache

# Phase 1: AI
php artisan migrate  # Run vector index migration
php artisan test --filter=SemanticSearch

# Test everything
php artisan test
php artisan test --parallel --processes=15

# Monitor performance
php artisan horizon  # Queue monitoring
php artisan tinker --execute="DB::enableQueryLog(); /* run code */; dd(DB::getQueryLog());"
```

---

## Next Steps

1. **Review this implementation roadmap** with your team
2. **Execute Phase 0 immediately** (4 hours - blocking deployment)
3. **Schedule Phase 1 Week 1** with assigned developers
4. **Set up daily standups** to track progress
5. **Update task completion** in project management tool

**This roadmap provides every command, code change, and migration needed to execute the full optimization plan.**

For high-level overview, see: `docs/active/MASTER_PLATFORM_ANALYSIS_2025-11-21.md`
