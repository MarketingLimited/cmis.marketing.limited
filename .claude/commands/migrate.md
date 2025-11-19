---
description: Run database migrations with safety checks
---

Execute database migrations for CMIS with safety checks:

1. First, check current migration status: `php artisan migrate:status`
2. Review pending migrations in database/migrations/
3. Verify that new tables include RLS policies
4. Ask user for confirmation before running migrations
5. Run migrations: `php artisan migrate`
6. Verify migration success
7. Check that RLS policies are active on new tables

Safety checks:
- Warn if migrations will modify production data
- Ensure backup exists for production environments
- Verify RLS policies on all new tables
- Check foreign key constraints are compatible with multi-tenancy
