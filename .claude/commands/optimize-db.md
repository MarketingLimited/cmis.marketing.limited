---
description: Analyze and optimize database performance
---

Perform database performance analysis and optimization:

1. Check slow query log for problematic queries
2. Analyze table sizes and index usage
3. Identify missing indexes on foreign keys
4. Check for N+1 query problems in recent code
5. Analyze pgvector index performance
6. Review query execution plans for slow queries
7. Suggest optimizations:
   - Missing indexes
   - Query rewrites
   - Eager loading opportunities
   - Caching strategies

SQL queries to run:
```sql
-- Find largest tables
SELECT schemaname, tablename,
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname LIKE 'cmis%'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 20;

-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname LIKE 'cmis%'
AND idx_scan = 0
ORDER BY pg_relation_size(indexrelid) DESC;

-- Analyze table statistics
ANALYZE;
```

Generate optimization report with actionable recommendations.
