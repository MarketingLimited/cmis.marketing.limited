-- ============================================================================
-- CMIS Refactoring Verification Script v2.2
-- Purpose: Verify the success of refactoring migration
-- ============================================================================

\echo '============================================'
\echo 'CMIS Refactoring Verification v2.2'
\echo '============================================'
\echo ''

-- Check schemas
\echo 'Available Schemas:'
\echo '------------------'
SELECT 
    nspname as schema_name,
    CASE 
        WHEN nspname = 'cmis' THEN '✅ Target Schema'
        WHEN nspname = 'cmis_refactored' THEN '⚠️ Should be removed'
        ELSE '📋 System/Other'
    END as status,
    (SELECT COUNT(*) FROM pg_class c 
     WHERE c.relnamespace = n.oid) as object_count
FROM pg_namespace n
WHERE nspname NOT LIKE 'pg_%' 
    AND nspname NOT IN ('information_schema')
ORDER BY 
    CASE 
        WHEN nspname = 'cmis' THEN 1
        WHEN nspname = 'cmis_refactored' THEN 2
        ELSE 3
    END,
    nspname;

-- Check tables in cmis
\echo ''
\echo 'Tables in CMIS Schema:'
\echo '----------------------'
SELECT 
    t.tablename,
    CASE 
        WHEN t.tablename IN ('campaigns', 'integrations') THEN '🔄 Converted from View'
        WHEN t.tablename IN ('creative_assets', 'orgs', 'users') THEN '✅ Core Table'
        ELSE '📋 Additional Table'
    END as migration_status,
    pg_size_pretty(pg_total_relation_size(format('%I.%I', t.schemaname, t.tablename))) as size,
    (SELECT COUNT(*) FROM information_schema.columns 
     WHERE table_schema = t.schemaname AND table_name = t.tablename) as columns
FROM pg_tables t
WHERE t.schemaname = 'cmis'
ORDER BY 
    CASE 
        WHEN t.tablename IN ('campaigns', 'integrations') THEN 1
        WHEN t.tablename IN ('creative_assets', 'orgs', 'users') THEN 2
        ELSE 3
    END,
    t.tablename;

-- Check views in cmis
\echo ''
\echo 'Views in CMIS Schema:'
\echo '--------------------'
SELECT 
    v.viewname,
    CASE 
        WHEN v.definition ILIKE '%cmis_refactored%' THEN '❌ Still references cmis_refactored'
        ELSE '✅ Clean'
    END as status,
    pg_size_pretty(length(v.definition)::bigint) as definition_size
FROM pg_views v
WHERE v.schemaname = 'cmis'
ORDER BY v.viewname;

-- Check for required columns
\echo ''
\echo 'Required Migration Columns:'
\echo '---------------------------'
WITH required_columns AS (
    SELECT 'campaigns' as table_name, 'context_id' as column_name
    UNION SELECT 'campaigns', 'creative_id'
    UNION SELECT 'campaigns', 'value_id'
    UNION SELECT 'campaigns', 'created_by'
    UNION SELECT 'integrations', 'created_by'
    UNION SELECT 'integrations', 'updated_by'
)
SELECT 
    rc.table_name,
    rc.column_name,
    CASE 
        WHEN c.column_name IS NOT NULL THEN '✅ Present'
        ELSE '❌ Missing'
    END as status,
    c.data_type,
    c.is_nullable
FROM required_columns rc
LEFT JOIN information_schema.columns c
    ON c.table_schema = 'cmis'
    AND c.table_name = rc.table_name
    AND c.column_name = rc.column_name
ORDER BY rc.table_name, rc.column_name;

-- Check foreign keys
\echo ''
\echo 'Foreign Key Constraints:'
\echo '------------------------'
SELECT 
    tc.table_name,
    tc.constraint_name,
    kcu.column_name as from_column,
    ccu.table_schema || '.' || ccu.table_name as references_table,
    ccu.column_name as references_column,
    CASE 
        WHEN ccu.table_schema = 'cmis_refactored' THEN '❌ Points to cmis_refactored'
        ELSE '✅ OK'
    END as status
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY' 
    AND tc.table_schema = 'cmis'
ORDER BY 
    CASE WHEN ccu.table_schema = 'cmis_refactored' THEN 0 ELSE 1 END,
    tc.table_name, 
    tc.constraint_name;

-- Check permissions
\echo ''
\echo 'Table Permissions:'
\echo '-----------------'
SELECT 
    tablename,
    has_table_privilege('begin', schemaname||'.'||tablename, 'SELECT') as begin_select,
    has_table_privilege('begin', schemaname||'.'||tablename, 'INSERT') as begin_insert,
    has_table_privilege('begin', schemaname||'.'||tablename, 'UPDATE') as begin_update,
    has_table_privilege('begin', schemaname||'.'||tablename, 'DELETE') as begin_delete,
    CASE 
        WHEN has_table_privilege('begin', schemaname||'.'||tablename, 'SELECT,INSERT,UPDATE,DELETE') 
        THEN '✅ Full Access'
        ELSE '⚠️ Limited Access'
    END as status
FROM pg_tables
WHERE schemaname = 'cmis'
    AND tablename IN ('campaigns', 'integrations', 'orgs', 'users', 'creative_assets')
ORDER BY tablename;

-- Final health check
\echo ''
\echo '============================================'
\echo 'Overall Health Check:'
\echo '============================================'

WITH health_checks AS (
    SELECT 'cmis_refactored removed' as check_item,
           NOT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'cmis_refactored') as passed
    UNION ALL
    SELECT 'campaigns is a table',
           EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'campaigns')
    UNION ALL
    SELECT 'integrations is a table',
           EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'integrations')
    UNION ALL
    SELECT 'required columns exist',
           EXISTS (SELECT 1 FROM information_schema.columns 
                  WHERE table_schema = 'cmis' AND table_name = 'campaigns' 
                  AND column_name IN ('context_id', 'creative_id', 'value_id', 'created_by'))
    UNION ALL
    SELECT 'no views reference cmis_refactored',
           NOT EXISTS (SELECT 1 FROM pg_views 
                      WHERE schemaname = 'cmis' AND definition ILIKE '%cmis_refactored%')
)
SELECT 
    check_item,
    CASE WHEN passed THEN '✅ PASSED' ELSE '❌ FAILED' END as result
FROM health_checks
ORDER BY 
    CASE WHEN passed THEN 1 ELSE 0 END DESC,
    check_item;

\echo ''
\echo 'Verification Complete!'
\echo '============================================'