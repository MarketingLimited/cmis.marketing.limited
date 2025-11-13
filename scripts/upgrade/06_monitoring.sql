-- ====================================================================
-- CMIS Database Fixes - Part 6: Monitoring & Maintenance
-- الجزء السادس: المراقبة والصيانة
-- ====================================================================
-- Purpose: إنشاء views ودوال للمراقبة والصيانة
-- Depends on: 05_indexes.sql
-- Safe to rollback: YES (safe to drop and recreate)
-- Execution time: ~2-3 minutes
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

BEGIN;

-- ====================================================================
-- 1. إنشاء views المراقبة
-- ====================================================================

DO $$
BEGIN
END $$;

-- 1.1 عرض الجداول بدون updated_at
CREATE OR REPLACE VIEW operations.v_tables_without_updated_at AS
SELECT 
    t.table_schema,
    t.table_name,
    pg_size_pretty(pg_total_relation_size(
        quote_ident(t.table_schema)||'.'||quote_ident(t.table_name)
    )) as table_size
FROM information_schema.tables t
WHERE t.table_schema IN ('cmis', 'cmis_dev', 'cmis_analytics', 'cmis_knowledge')
AND t.table_type = 'BASE TABLE'
AND NOT EXISTS (
    SELECT 1 FROM information_schema.columns c
    WHERE c.table_schema = t.table_schema
    AND c.table_name = t.table_name
    AND c.column_name = 'updated_at'
)
ORDER BY pg_total_relation_size(
    quote_ident(t.table_schema)||'.'||quote_ident(t.table_name)
) DESC;

COMMENT ON VIEW operations.v_tables_without_updated_at IS 
'Lists tables missing updated_at column, sorted by size';


-- 1.2 عرض المفاتيح الخارجية المحتملة المفقودة
CREATE OR REPLACE VIEW operations.v_potential_missing_fks AS
SELECT DISTINCT
    c.table_schema,
    c.table_name,
    c.column_name,
    CASE 
        WHEN c.column_name LIKE '%_id' THEN 
            regexp_replace(c.column_name, '_id$', 's')
        ELSE NULL
    END as potential_reference_table,
    c.data_type
FROM information_schema.columns c
WHERE c.table_schema IN ('cmis', 'cmis_dev', 'cmis_analytics')
AND c.column_name LIKE '%_id'
AND NOT EXISTS (
    SELECT 1 FROM information_schema.key_column_usage kcu
    WHERE kcu.table_schema = c.table_schema
    AND kcu.table_name = c.table_name
    AND kcu.column_name = c.column_name
    AND EXISTS (
        SELECT 1 FROM information_schema.table_constraints tc
        WHERE tc.constraint_schema = kcu.constraint_schema
        AND tc.constraint_name = kcu.constraint_name
        AND tc.constraint_type = 'FOREIGN KEY'
    )
)
ORDER BY c.table_schema, c.table_name;

COMMENT ON VIEW operations.v_potential_missing_fks IS 
'Columns ending with _id that may need foreign key constraints';


-- 1.3 عرض الفهارس المكررة
CREATE OR REPLACE VIEW operations.v_duplicate_indexes AS
WITH index_columns AS (
    SELECT
        schemaname,
        tablename,
        indexname,
        string_agg(attname, ',' ORDER BY attnum) as columns,
        pg_relation_size(indexrelid) as index_size
    FROM pg_indexes
    JOIN pg_class ON pg_indexes.indexname = pg_class.relname
    JOIN pg_index ON pg_class.oid = pg_index.indexrelid
    JOIN pg_attribute ON pg_index.indrelid = pg_attribute.attrelid 
        AND pg_attribute.attnum = ANY(pg_index.indkey)
    WHERE schemaname IN ('cmis', 'cmis_dev', 'cmis_analytics')
    GROUP BY schemaname, tablename, indexname, indexrelid
)
SELECT 
    i1.schemaname,
    i1.tablename,
    i1.indexname as index1,
    i2.indexname as index2,
    i1.columns,
    pg_size_pretty(i1.index_size) as size1,
    pg_size_pretty(i2.index_size) as size2
FROM index_columns i1
JOIN index_columns i2 ON 
    i1.schemaname = i2.schemaname
    AND i1.tablename = i2.tablename
    AND i1.columns = i2.columns
    AND i1.indexname < i2.indexname
ORDER BY i1.schemaname, i1.tablename;

COMMENT ON VIEW operations.v_duplicate_indexes IS 
'Identifies duplicate indexes that can be removed';


-- 1.4 عرض المخططات الاحتياطية
CREATE OR REPLACE VIEW operations.v_backup_schemas AS
SELECT 
    nspname as schema_name,
    pg_size_pretty(sum(pg_total_relation_size(C.oid))) as total_size,
    count(*) as table_count,
    CASE
        WHEN nspname ~ '\d{8}' THEN
            CURRENT_DATE - to_date(substring(nspname from '\d{8}'), 'YYYYMMDD')
        ELSE NULL
    END as age_days
FROM pg_class C
LEFT JOIN pg_namespace N ON (N.oid = C.relnamespace)
WHERE 
    (nspname LIKE '%backup%' OR nspname = 'archive')
    AND C.relkind = 'r'
GROUP BY nspname
ORDER BY sum(pg_total_relation_size(C.oid)) DESC;

COMMENT ON VIEW operations.v_backup_schemas IS 
'Lists backup and archive schemas with sizes and ages';


-- ====================================================================
-- 2. إنشاء دالة التقرير
-- ====================================================================

DO $$
BEGIN
END $$;

CREATE OR REPLACE FUNCTION operations.generate_fixes_report()
RETURNS TABLE(
    category TEXT,
    item TEXT,
    status TEXT,
    details TEXT
) AS $$
BEGIN
    -- الأعمدة المضافة
    RETURN QUERY
    SELECT 
        'Added Columns'::TEXT,
        table_name::TEXT || '.' || column_name::TEXT,
        'COMPLETED'::TEXT,
        'Added updated_at column'::TEXT
    FROM information_schema.columns
    WHERE table_schema = 'cmis'
    AND column_name = 'updated_at'
    AND table_name IN ('creative_assets', 'experiments');

    -- المفاتيح الخارجية المضافة
    RETURN QUERY
    SELECT 
        'Added Foreign Keys'::TEXT,
        tc.constraint_name::TEXT,
        'COMPLETED'::TEXT,
        tc.table_name::TEXT || ' constraint added'
    FROM information_schema.table_constraints tc
    WHERE tc.constraint_schema = 'cmis'
    AND tc.constraint_type = 'FOREIGN KEY'
    AND tc.constraint_name IN (
        'fk_content_items_org_id',
        'fk_content_items_creative_context',
        'fk_content_plans_org_id'
    );

    -- القيود الفريدة المضافة
    RETURN QUERY
    SELECT 
        'Added UNIQUE Constraints'::TEXT,
        tc.constraint_name::TEXT,
        'COMPLETED'::TEXT,
        tc.table_name::TEXT
    FROM information_schema.table_constraints tc
    WHERE tc.constraint_schema = 'cmis'
    AND tc.constraint_type = 'UNIQUE'
    AND tc.constraint_name = 'users_email_unique';

    -- الفهارس المضافة
    RETURN QUERY
    SELECT 
        'Added Indexes'::TEXT,
        indexname::TEXT,
        'COMPLETED'::TEXT,
        tablename::TEXT || ' index created'
    FROM pg_indexes
    WHERE schemaname = 'cmis'
    AND indexname IN (
        'idx_scheduled_posts_status_time',
        'idx_content_plans_org',
        'idx_content_items_creative_context',
        'idx_users_status',
        'idx_performance_metrics_campaign_time'
    );
    
    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION operations.generate_fixes_report() IS 
'Generates comprehensive report of all applied fixes';


-- ====================================================================
-- 3. إنشاء دالة تنظيف النسخ الاحتياطية القديمة
-- ====================================================================

CREATE OR REPLACE FUNCTION operations.cleanup_old_backups(
    p_retention_days INTEGER DEFAULT 90
)
RETURNS TABLE(
    schema_name TEXT,
    action TEXT,
    result TEXT
) AS $$
DECLARE
    v_schema RECORD;
    v_schema_age INTEGER;
BEGIN
    FOR v_schema IN 
        SELECT nspname 
        FROM pg_namespace 
        WHERE nspname LIKE '%backup%' 
        OR nspname = 'archive'
    LOOP
        -- استخراج التاريخ من اسم المخطط إن وجد
        BEGIN
            IF v_schema.nspname ~ '\d{8}' THEN
                v_schema_age := CURRENT_DATE - to_date(
                    substring(v_schema.nspname from '\d{8}'), 
                    'YYYYMMDD'
                );
                
                IF v_schema_age > p_retention_days THEN
                    schema_name := v_schema.nspname;
                    action := 'SHOULD_DROP';
                    result := format('Schema is %s days old (older than %s days)', 
                                   v_schema_age, p_retention_days);
                    RETURN NEXT;
                END IF;
            END IF;
        EXCEPTION WHEN OTHERS THEN
            -- تخطي المخططات التي لا تحتوي تاريخ في الاسم
            CONTINUE;
        END;
    END LOOP;
    
    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION operations.cleanup_old_backups IS 
'Identifies backup schemas older than retention period (default 90 days)';


-- ====================================================================
-- 4. إنشاء جدول أخطاء التدقيق
-- ====================================================================

CREATE TABLE IF NOT EXISTS operations.audit_errors (
    error_id BIGSERIAL PRIMARY KEY,
    table_name TEXT NOT NULL,
    operation TEXT NOT NULL,
    error_message TEXT NOT NULL,
    error_context JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_audit_errors_created_at 
ON operations.audit_errors (created_at DESC);

CREATE INDEX IF NOT EXISTS idx_audit_errors_table 
ON operations.audit_errors (table_name, created_at DESC);

COMMENT ON TABLE operations.audit_errors IS 
'Logs errors that occur during audit operations';


-- ====================================================================
-- 5. تسجيل الإنجاز
-- ====================================================================

INSERT INTO operations.fix_tracking (
    script_part, 
    fix_category, 
    fix_description,
    status,
    executed_at
)
VALUES 
    ('06_monitoring', 'monitoring', 'Created monitoring views', 'completed', CURRENT_TIMESTAMP),
    ('06_monitoring', 'functions', 'Created utility functions', 'completed', CURRENT_TIMESTAMP);

-- ====================================================================
-- 6. اختبار المراقبة
-- ====================================================================

DO $$
DECLARE
    v_view_count INTEGER;
    v_func_count INTEGER;
BEGIN
    
    -- عد الـ views
    SELECT COUNT(*) INTO v_view_count
    FROM information_schema.views
    WHERE table_schema = 'operations'
    AND table_name IN (
        'v_tables_without_updated_at',
        'v_potential_missing_fks',
        'v_duplicate_indexes',
        'v_backup_schemas'
    );
    
    
    -- عد الدوال
    SELECT COUNT(*) INTO v_func_count
    FROM information_schema.routines
    WHERE routine_schema = 'operations'
    AND routine_name IN (
        'generate_fixes_report',
        'cleanup_old_backups'
    );
    
    
    -- اختبار دالة التقرير
    FOR r IN 
        SELECT * FROM operations.generate_fixes_report() LIMIT 5
    LOOP
    END LOOP;
END $$;

-- ====================================================================
-- 7. ملخص نهائي
-- ====================================================================

DO $$
BEGIN
END $$;

COMMIT;

\timing off
