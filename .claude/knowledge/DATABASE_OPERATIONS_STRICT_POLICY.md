# Database Operations - Strict Policy for Claude Code Agents

**Last Updated:** 2025-11-30
**Priority:** CRITICAL - STRICTLY ENFORCED
**Applies To:** All Claude Code agents working on CMIS project

---

## 🚨 Core Principle

**NEVER EDIT THE DATABASE DIRECTLY. ALWAYS USE MIGRATIONS.**

This is not a suggestion - this is a **MANDATORY** requirement for all database operations in the CMIS project.

---

## ✅ Correct Workflow

### 1. Identify Database Change Needed
Examples:
- Adding a new table
- Modifying a column
- Adding an index
- Creating a constraint
- Fixing a schema issue

### 2. Create Migration File
```bash
php artisan make:migration descriptive_migration_name
```

### 3. Write Migration Code
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    public function up(): void
    {
        // Create or modify table
        Schema::create('cmis.table_name', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // ... columns
        });

        // Enable RLS
        $this->enableRLS('cmis.table_name');
    }

    public function down(): void
    {
        $this->disableRLS('cmis.table_name');
        Schema::dropIfExists('cmis.table_name');
    }
};
```

### 4. Run Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

### 5. Fix Any Errors
If migration fails:
- ✅ **DO:** Fix the migration file
- ✅ **DO:** Run `migrate:fresh --seed` again
- ✅ **DO:** Repeat until successful
- ❌ **DON'T:** Edit database directly to "fix" the migration

### 6. Verify Success
- Check all tables created
- Check RLS policies applied
- Check seeded data present
- Check application functionality

### 7. Commit Changes
```bash
git add database/migrations/your_migration_file.php
git commit -m "migration: descriptive message"
```

---

## ❌ Prohibited Actions

### NEVER Do These:

1. **Direct SQL Modifications:**
   ```bash
   # ❌ WRONG - Never do this
   php artisan tinker
   >>> DB::statement("ALTER TABLE ...");

   # ❌ WRONG - Never do this
   psql -c "ALTER TABLE ..."
   ```

2. **Schema Changes Outside Migrations:**
   ```php
   // ❌ WRONG - Never in controllers, services, or commands
   DB::statement("CREATE TABLE ...");
   DB::statement("ALTER TABLE ...");
   DB::statement("DROP TABLE ...");
   ```

3. **Modifying Data in Migrations:**
   ```php
   // ❌ WRONG - Use seeders for data
   public function up()
   {
       DB::table('users')->insert([...]);  // NO!
   }
   ```

4. **Skipping Fresh Migrations:**
   ```bash
   # ❌ WRONG - Don't just run migrate
   php artisan migrate

   # ✅ CORRECT - Always fresh with seed
   php artisan migrate:fresh --seed
   ```

---

## 🎯 Why This Policy Exists

### 1. **Reproducibility**
- Fresh migrations ensure the database can be recreated from scratch
- Critical for setting up new environments (dev, staging, production)
- Prevents "it works on my machine" issues

### 2. **Version Control**
- Migrations are tracked in git
- Direct database changes are lost and undocumented
- Team members can't reproduce your changes

### 3. **Production Safety**
- Migrations tested with fresh runs are guaranteed to work in production
- Direct changes might work locally but fail in production
- Prevents catastrophic production migration failures

### 4. **RLS Policy Integrity**
- Fresh migrations verify RLS policies are correctly applied
- Direct changes might bypass or break RLS
- Critical for multi-tenancy security

### 5. **Dependency Tracking**
- Fresh migrations catch missing foreign keys, indexes, etc.
- Direct changes might work only because of existing data
- Ensures correct migration order

---

## 🔧 Common Scenarios

### Scenario 1: "I need to add a column"
```php
// ✅ CORRECT
// Create migration: php artisan make:migration add_column_to_table
public function up()
{
    Schema::table('cmis.table_name', function (Blueprint $table) {
        $table->string('new_column')->nullable();
    });
}

// Then: php artisan migrate:fresh --seed
```

### Scenario 2: "Migration failed, table already exists"
```bash
# ❌ WRONG APPROACH
# Drop table manually and run migrate again

# ✅ CORRECT APPROACH
# 1. Fix migration to check if table exists:
if (!Schema::hasTable('cmis.table_name')) {
    Schema::create('cmis.table_name', ...);
}

# 2. Run fresh migration:
php artisan migrate:fresh --seed
```

### Scenario 3: "I need to fix existing data"
```php
// ✅ CORRECT - Use a seeder
// database/seeders/FixDataSeeder.php
public function run()
{
    DB::table('cmis.table_name')->where(...)->update(...);
}

// Then: php artisan migrate:fresh --seed
```

### Scenario 4: "Jobs table missing auto-increment"
```php
// ❌ WRONG
DB::statement("ALTER TABLE jobs ALTER COLUMN id SET DEFAULT nextval(...)");

// ✅ CORRECT
// Create migration:
public function up()
{
    Schema::table('cmis.jobs', function (Blueprint $table) {
        // Recreate table with proper structure
    });
}
// Run: php artisan migrate:fresh --seed
```

---

## 📋 Pre-Commit Checklist

Before committing any database-related changes:

- [ ] Created migration file (not direct SQL)
- [ ] Ran `php artisan migrate:fresh --seed` successfully
- [ ] Verified all tables created correctly
- [ ] Verified RLS policies applied
- [ ] Verified seeded data present
- [ ] Tested application functionality
- [ ] No manual database modifications made
- [ ] Migration includes `up()` and `down()` methods
- [ ] Migration uses `HasRLSPolicies` trait for new tables

---

## 🚫 Enforcement

Violations of this policy will result in:

1. **Code Review Rejection:** Pull requests with direct database modifications will be rejected
2. **Migration Rollback:** Any direct changes must be converted to migrations
3. **Re-work Required:** Changes must be implemented correctly with migrations

**No Exceptions.** This policy applies to:
- All Claude Code agents
- All developers
- All environments (dev, staging, production)
- All urgency levels (even "quick fixes")

---

## 💡 Quick Reference

```bash
# ✅ CORRECT WORKFLOW
1. php artisan make:migration descriptive_name
2. Edit migration file
3. php artisan migrate:fresh --seed
4. Fix errors if any (repeat step 3)
5. Verify success
6. git add database/migrations/...
7. git commit -m "migration: ..."

# ❌ NEVER DO THIS
php artisan tinker
>>> DB::statement("ALTER TABLE ...");  # FORBIDDEN
>>> DB::statement("CREATE TABLE ...");  # FORBIDDEN
psql -c "ALTER TABLE ..."               # FORBIDDEN
```

---

## 📞 Questions?

If you encounter a situation where you think direct database modification is necessary:

1. **Stop** - It's never necessary
2. **Think** - How can this be done with migrations?
3. **Ask** - If truly stuck, ask for guidance
4. **Migrate** - Always use migrations

**Remember:** If it's not in a migration, it doesn't exist in version control, and it will cause problems in production.

---

**Summary:** NEVER edit the database directly. ALWAYS use migrations. ALWAYS run `migrate:fresh --seed`. Fix errors by updating migrations, not by editing the database. This is not negotiable.
