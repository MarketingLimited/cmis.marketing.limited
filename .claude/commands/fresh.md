---
description: Fresh migrate and seed database with safety checks
---

Perform fresh database migration with comprehensive safety checks:

## Step 1: Safety Verification (CRITICAL)

```bash
# Check environment - NEVER run on production
APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
echo "Current environment: $APP_ENV"

if [ "$APP_ENV" = "production" ]; then
    echo "❌ FATAL: Cannot run fresh migration on production!"
    echo "This command is for development/testing only."
    exit 1
fi
```

**STOP and ask for confirmation if:**
- Environment is not clearly development/testing
- User hasn't explicitly confirmed

## Step 2: Pre-Migration Checks

```bash
# Check database connection
php artisan db:show 2>/dev/null | head -5

# Show pending migrations
php artisan migrate:status | grep -E "Pending|No"

# Count existing records (for awareness)
echo "Current database state:"
php artisan tinker --execute="echo 'Organizations: ' . \App\Models\Core\Organization::count();"
```

## Step 3: Confirmation

**ASK USER:** "This will DELETE all data and recreate the database. Are you sure? (yes/no)"

Only proceed if user explicitly confirms with "yes".

## Step 4: Execute Fresh Migration

```bash
# Run fresh migration with seed
php artisan migrate:fresh --seed 2>&1

# Capture exit code
RESULT=$?
```

## Step 5: Verify Success

```bash
# Check migration status
php artisan migrate:status | tail -10

# Verify RLS policies exist
php artisan tinker --execute="
\$tables = DB::select(\"SELECT tablename FROM pg_tables WHERE schemaname LIKE 'cmis%'\");
echo 'Tables created: ' . count(\$tables);
"

# Check seeded data
php artisan tinker --execute="
echo 'Organizations: ' . \App\Models\Core\Organization::count();
echo 'Users: ' . \App\Models\Core\User::count();
"
```

## Step 6: Post-Migration Actions

If migration successful:
1. Clear all caches: `php artisan optimize:clear`
2. Regenerate IDE helpers: `php artisan ide-helper:generate` (if available)
3. Report completion status

If migration failed:
1. Show error message
2. Analyze which migration failed
3. Suggest fixes based on error type
4. Common issues:
   - Missing extensions (uuid-ossp, pgvector)
   - RLS policy syntax errors
   - Foreign key constraint issues

## Step 7: Summary Report

```
=== Fresh Migration Complete ===
Environment: [local/testing]
Status: ✅ Success / ❌ Failed

Database State:
  Tables: XX created
  RLS Policies: XX applied
  Seeded Records:
    - Organizations: X
    - Users: X
    - [Other models]: X

Caches: Cleared ✅
Next Steps: [Recommendations]
```

## Safety Notes

- NEVER run on production
- Always backup important data before running
- This is a DESTRUCTIVE operation
- All existing data will be lost
- Use for development reset only
