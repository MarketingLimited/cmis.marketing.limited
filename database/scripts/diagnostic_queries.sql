-- ============================================================
-- CMIS Database Diagnostic Queries
-- ============================================================
-- Purpose: Collection of diagnostic queries for monitoring
--          database health, performance, and integrity
-- Author: Laravel Database Architect Agent
-- Date: 2025-11-18
-- ============================================================

-- ============================================================
-- 1. DATABASE OVERVIEW
-- ============================================================

-- 1.1 Database Size
SELECT
    pg_database.datname as database_name,
    pg_size_pretty(pg_database_size(pg_database.datname)) AS size
FROM pg_database
WHERE datname = 'cmis';

-- 1.2 Schema Sizes
SELECT
    schemaname,
    COUNT(*) as table_count,
    pg_size_pretty(SUM(pg_total_relation_size(schemaname||'.'||tablename))::bigint) as total_size
FROM pg_tables
WHERE schemaname IN ('cmis', 'cmis_analytics', 'cmis_audit', 'cmis_knowledge', 'public')
GROUP BY schemaname
ORDER BY SUM(pg_total_relation_size(schemaname||'.'||tablename)) DESC;

-- 1.3 Largest Tables
SELECT
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS total_size,
    pg_size_pretty(pg_relation_size(schemaname||'.'||tablename)) AS table_size,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename) - pg_relation_size(schemaname||'.'||tablename)) AS index_size,
    n_live_tup as row_count
FROM pg_tables
JOIN pg_stat_user_tables ON pg_tables.tablename = pg_stat_user_tables.relname
WHERE schemaname IN ('cmis', 'cmis_analytics', 'cmis_audit')
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 20;

-- ============================================================
-- 2. FOREIGN KEY INTEGRITY
-- ============================================================

-- 2.1 Find Missing Foreign Keys
-- (Columns ending with _id but no FK constraint)
SELECT
    c.table_schema,
    c.table_name,
    c.column_name,
    c.data_type
FROM information_schema.columns c
LEFT JOIN (
    SELECT
        tc.table_schema,
        tc.table_name,
        kcu.column_name
    FROM information_schema.table_constraints tc
    JOIN information_schema.key_column_usage kcu
        ON tc.constraint_name = kcu.constraint_name
        AND tc.table_schema = kcu.table_schema
    WHERE tc.constraint_type = 'FOREIGN KEY'
) fk ON c.table_schema = fk.table_schema
    AND c.table_name = fk.table_name
    AND c.column_name = fk.column_name
WHERE c.table_schema = 'cmis'
AND c.column_name LIKE '%_id'
AND c.column_name NOT IN ('id', 'log_id', 'metric_id', 'activity_id') -- Exclude primary keys
AND fk.column_name IS NULL
ORDER BY c.table_name, c.column_name;

-- 2.2 Find Foreign Keys Without Indexes
SELECT
    tc.table_schema,
    tc.table_name,
    kcu.column_name,
    'CREATE INDEX idx_' || tc.table_name || '_' || kcu.column_name ||
    ' ON ' || tc.table_schema || '.' || tc.table_name || '(' || kcu.column_name || ');' as suggested_index
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
LEFT JOIN pg_indexes i
    ON i.schemaname = tc.table_schema
    AND i.tablename = tc.table_name
    AND i.indexdef LIKE '%' || kcu.column_name || '%'
WHERE tc.constraint_type = 'FOREIGN KEY'
AND tc.table_schema = 'cmis'
AND i.indexname IS NULL;

-- 2.3 Check for Orphaned Records
-- This will check each foreign key for orphaned records
DO $$
DECLARE
    r RECORD;
    orphans INTEGER;
    total_checked INTEGER := 0;
    total_violations INTEGER := 0;
BEGIN
    RAISE NOTICE '=== Checking Foreign Key Integrity ===';
    RAISE NOTICE '';

    FOR r IN
        SELECT
            tc.table_schema,
            tc.table_name,
            kcu.column_name,
            ccu.table_schema AS foreign_table_schema,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
            AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
        AND tc.table_schema = 'cmis'
    LOOP
        total_checked := total_checked + 1;

        EXECUTE format(
            'SELECT COUNT(*) FROM %I.%I t
             WHERE t.%I IS NOT NULL
             AND NOT EXISTS (
                 SELECT 1 FROM %I.%I f WHERE f.%I = t.%I
             )',
            r.table_schema,
            r.table_name,
            r.column_name,
            r.foreign_table_schema,
            r.foreign_table_name,
            r.foreign_column_name,
            r.column_name
        ) INTO orphans;

        IF orphans > 0 THEN
            total_violations := total_violations + orphans;
            RAISE NOTICE '❌ %.%.% has % orphaned records (referencing %.%.%)',
                r.table_schema, r.table_name, r.column_name, orphans,
                r.foreign_table_schema, r.foreign_table_name, r.foreign_column_name;
        END IF;
    END LOOP;

    RAISE NOTICE '';
    RAISE NOTICE '=== Summary ===';
    RAISE NOTICE 'Foreign keys checked: %', total_checked;
    RAISE NOTICE 'Total violations: %', total_violations;

    IF total_violations = 0 THEN
        RAISE NOTICE '✅ All foreign key constraints valid!';
    ELSE
        RAISE NOTICE '❌ Fix data to respect foreign key constraints';
    END IF;
END $$;

-- ============================================================
-- 3. INDEX ANALYSIS
-- ============================================================

-- 3.1 Unused Indexes
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan as index_scans,
    pg_size_pretty(pg_relation_size(indexrelid)) as index_size,
    'DROP INDEX ' || schemaname || '.' || indexname || ';' as drop_statement
FROM pg_stat_user_indexes
WHERE schemaname = 'cmis'
AND idx_scan = 0  -- Never used
AND indexrelname NOT LIKE '%_pkey'
AND indexrelname NOT LIKE '%_unique'
ORDER BY pg_relation_size(indexrelid) DESC;

-- 3.2 Duplicate Indexes
SELECT
    pg_size_pretty(SUM(pg_relation_size(idx))::BIGINT) AS size,
    (array_agg(idx))[1] AS idx1,
    (array_agg(idx))[2] AS idx2,
    (array_agg(idx))[3] AS idx3,
    (array_agg(idx))[4] AS idx4
FROM (
    SELECT
        indexrelid::regclass AS idx,
        (indrelid::text ||E'\n'|| indclass::text ||E'\n'|| indkey::text ||E'\n'||
         COALESCE(indexprs::text,'')||E'\n' || COALESCE(indpred::text,'')) AS key
    FROM pg_index
) sub
GROUP BY key
HAVING COUNT(*) > 1
ORDER BY SUM(pg_relation_size(idx)) DESC;

-- 3.3 Missing Indexes Recommendations
-- Tables with high sequential scans
SELECT
    schemaname,
    tablename,
    seq_scan,
    idx_scan,
    ROUND(100.0 * idx_scan / NULLIF(seq_scan + idx_scan, 0), 2) AS index_usage_pct,
    n_live_tup AS row_count
FROM pg_stat_user_tables
WHERE schemaname = 'cmis'
AND seq_scan > 100  -- Many sequential scans
AND idx_scan < seq_scan  -- More seq scans than index scans
ORDER BY seq_scan DESC
LIMIT 20;

-- ============================================================
-- 4. PERFORMANCE ANALYSIS
-- ============================================================

-- 4.1 Slow Queries (requires pg_stat_statements)
SELECT
    substring(query, 1, 100) AS short_query,
    calls,
    ROUND(mean_exec_time::numeric, 2) AS avg_time_ms,
    ROUND(total_exec_time::numeric, 2) AS total_time_ms,
    ROUND((total_exec_time / sum(total_exec_time) OVER ()) * 100, 2) AS pct_total_time
FROM pg_stat_statements
WHERE query NOT LIKE '%pg_stat_statements%'
AND query NOT LIKE 'COMMIT%'
AND query NOT LIKE 'BEGIN%'
ORDER BY mean_exec_time DESC
LIMIT 20;

-- 4.2 Long-Running Queries
SELECT
    pid,
    now() - query_start AS duration,
    usename,
    state,
    substring(query, 1, 100) AS query
FROM pg_stat_activity
WHERE state = 'active'
AND query_start < now() - interval '1 minute'
AND query NOT LIKE '%pg_stat_activity%'
ORDER BY duration DESC;

-- 4.3 Table Bloat
SELECT
    schemaname,
    tablename,
    n_live_tup as live_tuples,
    n_dead_tup as dead_tuples,
    ROUND(100.0 * n_dead_tup / NULLIF(n_live_tup + n_dead_tup, 0), 2) AS dead_pct,
    last_vacuum,
    last_autovacuum
FROM pg_stat_user_tables
WHERE schemaname = 'cmis'
AND n_dead_tup > 1000
ORDER BY n_dead_tup DESC
LIMIT 20;

-- 4.4 Database Connections
SELECT
    datname,
    count(*) as connection_count,
    max_conn,
    ROUND(100.0 * count(*) / max_conn, 2) as pct_used
FROM pg_stat_activity
CROSS JOIN (SELECT setting::int AS max_conn FROM pg_settings WHERE name = 'max_connections') mc
WHERE datname IS NOT NULL
GROUP BY datname, max_conn
ORDER BY connection_count DESC;

-- ============================================================
-- 5. DATA INTEGRITY CHECKS
-- ============================================================

-- 5.1 Tables Without Primary Keys
SELECT
    t.table_schema,
    t.table_name
FROM information_schema.tables t
LEFT JOIN information_schema.table_constraints tc
    ON t.table_name = tc.table_name
    AND t.table_schema = tc.table_schema
    AND tc.constraint_type = 'PRIMARY KEY'
WHERE t.table_schema = 'cmis'
AND t.table_type = 'BASE TABLE'
AND tc.constraint_name IS NULL;

-- 5.2 Tables With Soft Deletes But Missing Indexes
SELECT
    c.table_schema,
    c.table_name,
    'CREATE INDEX idx_' || c.table_name || '_deleted_at ON ' ||
    c.table_schema || '.' || c.table_name || '(deleted_at) WHERE deleted_at IS NULL;' as suggested_index
FROM information_schema.columns c
LEFT JOIN pg_indexes i
    ON i.schemaname = c.table_schema
    AND i.tablename = c.table_name
    AND i.indexdef LIKE '%deleted_at%'
WHERE c.table_schema = 'cmis'
AND c.column_name = 'deleted_at'
AND i.indexname IS NULL;

-- 5.3 JSONB Columns Without GIN Indexes
SELECT
    c.table_schema,
    c.table_name,
    c.column_name,
    'CREATE INDEX idx_' || c.table_name || '_' || c.column_name || '_gin ON ' ||
    c.table_schema || '.' || c.table_name || ' USING gin (' || c.column_name || ' jsonb_path_ops);' as suggested_index
FROM information_schema.columns c
LEFT JOIN pg_indexes i
    ON i.schemaname = c.table_schema
    AND i.tablename = c.table_name
    AND i.indexdef LIKE '%' || c.column_name || '%'
    AND i.indexdef LIKE '%gin%'
WHERE c.table_schema = 'cmis'
AND c.data_type = 'jsonb'
AND i.indexname IS NULL
ORDER BY c.table_name, c.column_name;

-- ============================================================
-- 6. ROW LEVEL SECURITY (RLS)
-- ============================================================

-- 6.1 Tables With RLS Enabled
SELECT
    schemaname,
    tablename,
    rowsecurity
FROM pg_tables
WHERE schemaname = 'cmis'
AND rowsecurity = true
ORDER BY tablename;

-- 6.2 RLS Policies
SELECT
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual,
    with_check
FROM pg_policies
WHERE schemaname = 'cmis'
ORDER BY tablename, policyname;

-- ============================================================
-- 7. MAINTENANCE CHECKS
-- ============================================================

-- 7.1 Last Vacuum/Analyze Times
SELECT
    schemaname,
    relname,
    last_vacuum,
    last_autovacuum,
    last_analyze,
    last_autoanalyze,
    vacuum_count,
    autovacuum_count,
    analyze_count,
    autoanalyze_count
FROM pg_stat_user_tables
WHERE schemaname = 'cmis'
ORDER BY last_autovacuum NULLS FIRST
LIMIT 20;

-- 7.2 Tables Needing VACUUM
SELECT
    schemaname,
    relname,
    n_dead_tup,
    n_live_tup,
    ROUND(100.0 * n_dead_tup / NULLIF(n_live_tup + n_dead_tup, 0), 2) AS dead_pct,
    last_autovacuum
FROM pg_stat_user_tables
WHERE schemaname = 'cmis'
AND n_dead_tup > 1000
AND (last_autovacuum < now() - interval '1 day' OR last_autovacuum IS NULL)
ORDER BY n_dead_tup DESC;

-- ============================================================
-- 8. BACKUP & RECOVERY
-- ============================================================

-- 8.1 WAL Status
SELECT
    pg_current_wal_lsn() as current_wal,
    pg_wal_lsn_diff(pg_current_wal_lsn(), '0/0') / 1024 / 1024 / 1024 as wal_gb;

-- 8.2 Replication Status (if applicable)
SELECT
    client_addr,
    state,
    sync_state,
    pg_wal_lsn_diff(pg_current_wal_lsn(), sent_lsn) AS send_lag_bytes,
    pg_wal_lsn_diff(sent_lsn, write_lsn) AS write_lag_bytes,
    pg_wal_lsn_diff(write_lsn, flush_lsn) AS flush_lag_bytes,
    pg_wal_lsn_diff(flush_lsn, replay_lsn) AS replay_lag_bytes
FROM pg_stat_replication;

-- ============================================================
-- 9. QUICK HEALTH CHECK (Run This Daily)
-- ============================================================

-- Comprehensive health check
DO $$
DECLARE
    db_size TEXT;
    table_count INT;
    active_connections INT;
    slow_queries INT;
    bloated_tables INT;
    missing_fks INT;
    unused_indexes INT;
BEGIN
    -- Database size
    SELECT pg_size_pretty(pg_database_size('cmis')) INTO db_size;

    -- Table count
    SELECT COUNT(*) INTO table_count FROM pg_tables WHERE schemaname = 'cmis';

    -- Active connections
    SELECT COUNT(*) INTO active_connections FROM pg_stat_activity WHERE state = 'active';

    -- Slow queries (>1s)
    SELECT COUNT(*) INTO slow_queries
    FROM pg_stat_statements
    WHERE mean_exec_time > 1000;

    -- Bloated tables (>10% dead tuples)
    SELECT COUNT(*) INTO bloated_tables
    FROM pg_stat_user_tables
    WHERE schemaname = 'cmis'
    AND n_dead_tup > 1000
    AND ROUND(100.0 * n_dead_tup / NULLIF(n_live_tup + n_dead_tup, 0), 2) > 10;

    -- Missing foreign keys
    SELECT COUNT(*) INTO missing_fks
    FROM information_schema.columns c
    LEFT JOIN (
        SELECT table_schema, table_name, column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu USING (constraint_name, table_schema)
        WHERE constraint_type = 'FOREIGN KEY'
    ) fk USING (table_schema, table_name, column_name)
    WHERE c.table_schema = 'cmis'
    AND c.column_name LIKE '%_id'
    AND c.column_name NOT IN ('id', 'log_id', 'metric_id')
    AND fk.column_name IS NULL;

    -- Unused indexes
    SELECT COUNT(*) INTO unused_indexes
    FROM pg_stat_user_indexes
    WHERE schemaname = 'cmis'
    AND idx_scan = 0
    AND indexrelname NOT LIKE '%_pkey';

    -- Report
    RAISE NOTICE '=================================';
    RAISE NOTICE '   CMIS DATABASE HEALTH CHECK   ';
    RAISE NOTICE '=================================';
    RAISE NOTICE '';
    RAISE NOTICE 'Database Size: %', db_size;
    RAISE NOTICE 'Tables: %', table_count;
    RAISE NOTICE 'Active Connections: %', active_connections;
    RAISE NOTICE '';
    RAISE NOTICE '⚠️ Issues:';
    RAISE NOTICE '  Slow Queries (>1s): %', slow_queries;
    RAISE NOTICE '  Bloated Tables: %', bloated_tables;
    RAISE NOTICE '  Missing FKs: %', missing_fks;
    RAISE NOTICE '  Unused Indexes: %', unused_indexes;
    RAISE NOTICE '';

    IF slow_queries = 0 AND bloated_tables = 0 AND missing_fks = 0 THEN
        RAISE NOTICE '✅ Database Health: GOOD';
    ELSIF slow_queries < 5 AND bloated_tables < 5 AND missing_fks < 5 THEN
        RAISE NOTICE '⚠️ Database Health: FAIR - Monitor closely';
    ELSE
        RAISE NOTICE '❌ Database Health: NEEDS ATTENTION';
    END IF;

    RAISE NOTICE '=================================';
END $$;

-- ============================================================
-- END OF DIAGNOSTIC QUERIES
-- ============================================================
