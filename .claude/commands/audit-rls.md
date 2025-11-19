---
description: Audit Row-Level Security policies across all CMIS tables
---

Perform comprehensive RLS audit for CMIS multi-tenancy:

1. Connect to database and list all tables in cmis.* schemas
2. For each table, check if RLS is enabled
3. Verify RLS policies exist and are correct
4. Check for tables missing RLS policies
5. Test RLS isolation with sample queries
6. Generate audit report with:
   - Tables with RLS enabled ✓
   - Tables missing RLS policies ✗
   - Policy definitions for review
   - Recommendations for fixes

SQL to run:
```sql
-- Check RLS enabled status
SELECT schemaname, tablename, rowsecurity
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
ORDER BY schemaname, tablename;

-- List all RLS policies
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE schemaname LIKE 'cmis%'
ORDER BY schemaname, tablename;
```

After audit, provide recommendations for any tables missing RLS.
