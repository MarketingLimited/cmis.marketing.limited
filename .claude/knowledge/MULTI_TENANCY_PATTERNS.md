# Multi-Tenancy Patterns for CMIS
## PostgreSQL RLS-Based Multi-Tenancy Architecture

**Purpose:** Teach patterns for implementing and maintaining multi-tenant features using PostgreSQL Row-Level Security.

**Last Updated:** 2025-11-18
**Framework:** META_COGNITIVE_FRAMEWORK v2.0

---

## 🎯 CORE CONCEPT

**Multi-tenancy in CMIS = Database-Level Isolation via RLS**

Not application-level filtering. Not separate databases. Not schema-per-tenant.
**PostgreSQL Row-Level Security (RLS) enforces isolation automatically.**

---

## 📋 THE THREE PILLARS OF CMIS MULTI-TENANCY

### Pillar 1: Transaction Context

**Pattern:**
```sql
-- At request start, set context
SELECT cmis.init_transaction_context(user_id, org_id);

-- Context persists for entire transaction
-- All queries automatically filtered by RLS policies
```

**Implementation Pattern:**
```php
// Middleware
public function handle($request, Closure $next)
{
    $userId = auth()->id();
    $orgId = $request->route('org_id');

    // Validate access
    if (!$this->userBelongsToOrg($userId, $orgId)) {
        abort(403);
    }

    // Set context for this transaction
    DB::statement(
        'SELECT cmis.init_transaction_context(?, ?)',
        [$userId, $orgId]
    );

    return $next($request);
}
```

**Discovery:**
```sql
-- Find context initialization function
SELECT pg_get_functiondef(oid)
FROM pg_proc
WHERE proname = 'init_transaction_context';

-- Check current context
SELECT
    current_setting('cmis.current_user_id', true) as user_id,
    current_setting('cmis.current_org_id', true) as org_id;
```

### Pillar 2: RLS Policies

**Pattern: Standard 4-Policy Coverage**
```sql
-- Every multi-tenant table needs 4 policies

-- SELECT policy
CREATE POLICY rls_{table}_select ON cmis.{table}
FOR SELECT
USING (org_id = cmis.get_current_org_id());

-- INSERT policy
CREATE POLICY rls_{table}_insert ON cmis.{table}
FOR INSERT
WITH CHECK (org_id = cmis.get_current_org_id());

-- UPDATE policy
CREATE POLICY rls_{table}_update ON cmis.{table}
FOR UPDATE
USING (org_id = cmis.get_current_org_id())
WITH CHECK (org_id = cmis.get_current_org_id());

-- DELETE policy
CREATE POLICY rls_{table}_delete ON cmis.{table}
FOR DELETE
USING (org_id = cmis.get_current_org_id());
```

**Discovery:**
```sql
-- Check RLS coverage
SELECT
    schemaname,
    tablename,
    rowsecurity as rls_enabled,
    (SELECT COUNT(*)
     FROM pg_policies p
     WHERE p.schemaname = t.schemaname
       AND p.tablename = t.tablename) as policy_count
FROM pg_tables t
WHERE schemaname = 'cmis'
ORDER BY policy_count, tablename;

-- Tables without full coverage (should have 4 policies)
SELECT tablename, policy_count
FROM (
    SELECT tablename, COUNT(*) as policy_count
    FROM pg_policies
    WHERE schemaname = 'cmis'
    GROUP BY tablename
) sub
WHERE policy_count < 4;
```

### Pillar 3: Automatic Filtering

**Pattern: Never Manually Filter by org_id**
```php
// ❌ WRONG - Manual filtering defeats purpose of RLS
$campaigns = Campaign::where('org_id', $orgId)->get();

// ✅ CORRECT - RLS filters automatically
$campaigns = Campaign::all();

// ❌ WRONG - Bypassing RLS
$campaigns = Campaign::withoutGlobalScopes()->get();

// ✅ CORRECT - Trust RLS
$campaigns = Campaign::query()
    ->where('status', 'active')
    ->latest()
    ->get();
```

---

## 🏗️ IMPLEMENTATION PATTERNS

### Pattern 1: Adding Multi-Tenancy to New Table

**Step-by-Step:**
```sql
-- 1. Create table with org_id column
CREATE TABLE cmis.your_table (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    org_id UUID NOT NULL REFERENCES cmis.orgs(org_id),
    name VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);

-- 2. Enable RLS
ALTER TABLE cmis.your_table ENABLE ROW LEVEL SECURITY;

-- 3. Create policies (use template from Pillar 2)
-- 4. Create indexes
CREATE INDEX idx_your_table_org_id ON cmis.your_table(org_id);

-- 5. Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON cmis.your_table TO cmis_app;
```

**Laravel Migration:**
```php
public function up()
{
    Schema::create('cmis.your_table', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('org_id')->references('org_id')->on('cmis.orgs');
        $table->string('name');
        $table->timestamps();

        $table->index('org_id');
    });

    // Enable RLS
    DB::statement('ALTER TABLE cmis.your_table ENABLE ROW LEVEL SECURITY');

    // Create policies
    DB::unprepared("
        CREATE POLICY rls_your_table_select ON cmis.your_table
        FOR SELECT USING (org_id = cmis.get_current_org_id());

        CREATE POLICY rls_your_table_insert ON cmis.your_table
        FOR INSERT WITH CHECK (org_id = cmis.get_current_org_id());

        CREATE POLICY rls_your_table_update ON cmis.your_table
        FOR UPDATE
        USING (org_id = cmis.get_current_org_id())
        WITH CHECK (org_id = cmis.get_current_org_id());

        CREATE POLICY rls_your_table_delete ON cmis.your_table
        FOR DELETE USING (org_id = cmis.get_current_org_id());
    ");
}
```

### Pattern 2: System Operations (Jobs, Commands)

**Problem:** Background jobs don't have user session context.

**Solution:** Use system user and target org_id.

```php
class ProcessDataJob implements ShouldQueue
{
    protected $orgId;
    protected $dataId;

    public function __construct(string $orgId, string $dataId)
    {
        $this->orgId = $orgId;
        $this->dataId = $dataId;
    }

    public function handle()
    {
        // Set context using system user + target org
        DB::statement(
            'SELECT cmis.init_transaction_context(?, ?)',
            [config('cmis.system_user_id'), $this->orgId]
        );

        // Now all queries are scoped to this org
        $data = YourModel::find($this->dataId);
        // Process...
    }
}
```

**Discovery:**
```bash
# Find system user ID configuration
grep -r "SYSTEM_USER\|system.*user" config/ .env.example

# Check job patterns
grep -A 10 "init_transaction_context" app/Jobs/*.php
```

### Pattern 3: Cross-Org Operations (Admin)

**Pattern:** Platform admin needs to see all orgs.

```sql
-- Create admin bypass policy
CREATE POLICY rls_admin_bypass ON cmis.your_table
FOR ALL
USING (
    cmis.is_platform_admin(cmis.get_current_user_id())
);

-- Helper function
CREATE OR REPLACE FUNCTION cmis.is_platform_admin(p_user_id UUID)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM cmis.users
        WHERE user_id = p_user_id
        AND role = 'platform_admin'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER STABLE;
```

### Pattern 4: Shared Reference Data

**Pattern:** Some data is shared across all orgs.

```sql
-- Option 1: No org_id column (global data)
CREATE TABLE cmis.currencies (
    code VARCHAR(3) PRIMARY KEY,
    name VARCHAR(100),
    symbol VARCHAR(10)
);
-- No RLS needed - globally accessible

-- Option 2: Special org_id value
CREATE TABLE cmis.templates (
    id UUID PRIMARY KEY,
    org_id UUID,  -- NULL = global template
    name VARCHAR(255)
);

-- RLS policy allowing NULL org_id
CREATE POLICY rls_templates_select ON cmis.templates
FOR SELECT
USING (
    org_id IS NULL OR
    org_id = cmis.get_current_org_id()
);
```

---

## 🔍 DIAGNOSTIC PATTERNS

### Diagnosis 1: Data Leaking Between Orgs

**Symptoms:**
- User sees data from other organizations
- Counts include wrong org's data

**Discovery:**
```sql
-- Check if RLS is enabled
SELECT tablename, rowsecurity
FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename = 'problematic_table';

-- Check policies exist
SELECT COUNT(*) as policy_count
FROM pg_policies
WHERE tablename = 'problematic_table';

-- Verify context is set
SELECT
    current_setting('cmis.current_org_id', true) as org_context,
    current_setting('cmis.current_user_id', true) as user_context;
```

**Solution:**
```sql
-- If RLS disabled:
ALTER TABLE cmis.problematic_table ENABLE ROW LEVEL SECURITY;

-- If policies missing:
-- Create standard 4 policies (see Pillar 2)

-- If context not set:
-- Check middleware is applied to route
```

### Diagnosis 2: Cannot Insert/Update Data

**Symptoms:**
- Inserts fail silently
- Updates don't work
- Permission denied errors

**Discovery:**
```sql
-- Check INSERT policy
SELECT policyname, with_check
FROM pg_policies
WHERE tablename = 'problematic_table'
  AND cmd = 'INSERT';

-- Verify org_id is being set
\d cmis.problematic_table

-- Test manually
SELECT cmis.init_transaction_context(
    'user-uuid',
    'org-uuid'
);

INSERT INTO cmis.problematic_table (id, org_id, name)
VALUES (uuid_generate_v4(), current_setting('cmis.current_org_id')::uuid, 'test');
```

**Common Causes:**
- Missing WITH CHECK policy for INSERT
- org_id column nullable but not provided
- Context not set before query
- Using wrong org_id value

### Diagnosis 3: Queries Returning Empty

**Discovery:**
```sql
-- Check data exists
SELECT COUNT(*) FROM cmis.problematic_table;  -- May show 0 due to RLS

-- Bypass RLS temporarily (only for diagnosis!)
SET ROLE postgres;
SELECT COUNT(*), org_id FROM cmis.problematic_table GROUP BY org_id;
RESET ROLE;

-- Check current context
SELECT current_setting('cmis.current_org_id', true);

-- Verify context matches data
SELECT COUNT(*)
FROM cmis.problematic_table
WHERE org_id = current_setting('cmis.current_org_id')::uuid;
```

---

## 🎓 BEST PRACTICES

### DO:
✅ Always set context at middleware level
✅ Use standard 4-policy pattern
✅ Trust RLS, don't manually filter by org_id
✅ Pass org_id to background jobs
✅ Test with multiple orgs
✅ Monitor RLS policy performance

### DON'T:
❌ Skip context initialization
❌ Bypass RLS with `withoutGlobalScopes()`
❌ Manually filter queries by org_id
❌ Forget org_id in job constructors
❌ Assume RLS when policies aren't created
❌ Use system user for user-facing requests

---

## 📊 PERFORMANCE PATTERNS

### Pattern: Index Optimization for RLS

```sql
-- ALWAYS index org_id column
CREATE INDEX idx_{table}_org_id ON cmis.{table}(org_id);

-- Composite indexes for common queries
CREATE INDEX idx_{table}_org_status ON cmis.{table}(org_id, status);
CREATE INDEX idx_{table}_org_created ON cmis.{table}(org_id, created_at DESC);

-- Partial indexes for specific org queries
CREATE INDEX idx_{table}_active ON cmis.{table}(org_id, id)
WHERE status = 'active';
```

### Pattern: Query Performance

```sql
-- Check query plan respects RLS
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM cmis.your_table
WHERE status = 'active';

-- Should show:
-- Filter: (org_id = current_setting('cmis.current_org_id')::uuid)
```

---

## 🚀 MIGRATION PATTERNS

### Migrating Existing Table to Multi-Tenant

```sql
-- 1. Add org_id column
ALTER TABLE existing_table ADD COLUMN org_id UUID;

-- 2. Populate org_id (determine strategy based on data)
UPDATE existing_table SET org_id = (
    SELECT org_id FROM cmis.users u
    WHERE u.user_id = existing_table.created_by
);

-- 3. Make NOT NULL
ALTER TABLE existing_table ALTER COLUMN org_id SET NOT NULL;

-- 4. Add foreign key
ALTER TABLE existing_table ADD CONSTRAINT fk_org
    FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id);

-- 5. Enable RLS and create policies
ALTER TABLE existing_table ENABLE ROW LEVEL SECURITY;
-- (Create 4 policies...)

-- 6. Create index
CREATE INDEX idx_existing_org ON existing_table(org_id);
```

---

**Remember:** Multi-tenancy is enforced at the DATABASE level, not application level. Trust RLS, discover policies, never bypass security.

**Version:** 2.0 - Pattern-Based Multi-Tenancy
**Framework:** META_COGNITIVE_FRAMEWORK
**Approach:** RLS-First Architecture

*"Perfect isolation at the database level."*
