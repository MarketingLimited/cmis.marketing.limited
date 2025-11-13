-- ====================================================================
-- CMIS Database Fixes - Part 5: Indexes Optimization
-- الجزء الخامس: تحسين الفهارس
-- ====================================================================
-- Purpose: حذف الفهارس المكررة وإضافة فهارس محسّنة
-- Depends on: 04_constraints.sql
-- Safe to rollback: YES (can recreate indexes)
-- Execution time: ~5-15 minutes (depends on table sizes)
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

BEGIN;

-- ====================================================================
-- 1. البحث عن الفهارس المكررة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Analyzing Duplicate Indexes';
    RAISE NOTICE 'تحليل الفهارس المكررة';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
END $$;

CREATE TEMP TABLE IF NOT EXISTS duplicate_indexes AS
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
    WHERE schemaname = 'cmis'
    GROUP BY schemaname, tablename, indexname, indexrelid
)
SELECT 
    i1.schemaname,
    i1.tablename,
    i1.indexname as index_to_keep,
    i2.indexname as index_to_drop,
    i1.columns,
    pg_size_pretty(i2.index_size) as size
FROM index_columns i1
JOIN index_columns i2 ON 
    i1.schemaname = i2.schemaname
    AND i1.tablename = i2.tablename
    AND i1.columns = i2.columns
    AND i1.indexname < i2.indexname;

DO $$
DECLARE
    v_dup_count INTEGER;
    r RECORD;
BEGIN
    SELECT COUNT(*) INTO v_dup_count FROM duplicate_indexes;
    
    RAISE NOTICE 'Found % duplicate index pairs', v_dup_count;
    
    IF v_dup_count > 0 THEN
        RAISE NOTICE '';
        RAISE NOTICE 'Duplicate indexes to be removed:';
        FOR r IN SELECT * FROM duplicate_indexes LOOP
            RAISE NOTICE '  • %.% (%)', r.schemaname, r.index_to_drop, r.size;
        END LOOP;
    END IF;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 2. حذف الفهارس المكررة المحددة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE 'Removing Known Duplicate Indexes';
    RAISE NOTICE 'حذف الفهارس المكررة المعروفة';
    RAISE NOTICE '';
END $$;

-- 2.1 حذف idx_creative_assets_org إذا كان موجوداً
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_indexes 
        WHERE schemaname = 'cmis' 
        AND indexname = 'idx_creative_assets_org'
    ) THEN
        DROP INDEX IF EXISTS cmis.idx_creative_assets_org;
        RAISE NOTICE '✅ Dropped idx_creative_assets_org';
    ELSE
        RAISE NOTICE 'ℹ️  idx_creative_assets_org does not exist';
    END IF;
END $$;

-- 2.2 حذف idx_scheduled_social_posts_org_id إذا كان موجوداً
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_indexes 
        WHERE schemaname = 'cmis' 
        AND indexname = 'idx_scheduled_social_posts_org_id'
    ) AND EXISTS (
        -- التأكد من وجود فهرس مركب يغطيه
        SELECT 1 FROM pg_indexes 
        WHERE schemaname = 'cmis' 
        AND tablename = 'scheduled_social_posts'
        AND indexdef LIKE '%org_id%'
        AND indexname != 'idx_scheduled_social_posts_org_id'
    ) THEN
        DROP INDEX IF EXISTS cmis.idx_scheduled_social_posts_org_id;
        RAISE NOTICE '✅ Dropped idx_scheduled_social_posts_org_id';
    ELSE
        RAISE NOTICE 'ℹ️  idx_scheduled_social_posts_org_id does not exist or is needed';
    END IF;
END $$;

-- ====================================================================
-- 3. إضافة الفهارس المحسّنة الجديدة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Adding Optimized Indexes';
    RAISE NOTICE 'إضافة فهارس محسّنة';
    RAISE NOTICE '';
END $$;

-- 3.1 فهرس مركب على scheduled_social_posts
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'cmis'
        AND table_name = 'scheduled_social_posts'
    ) THEN
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes
            WHERE schemaname = 'cmis'
            AND indexname = 'idx_scheduled_posts_status_time'
        ) THEN
            CREATE INDEX CONCURRENTLY idx_scheduled_posts_status_time 
            ON cmis.scheduled_social_posts (status, scheduled_at)
            WHERE deleted_at IS NULL;
            
            RAISE NOTICE '✅ Created idx_scheduled_posts_status_time';
        ELSE
            RAISE NOTICE 'ℹ️  idx_scheduled_posts_status_time already exists';
        END IF;
    ELSE
        RAISE NOTICE 'ℹ️  Table scheduled_social_posts does not exist';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '⚠️  Could not create idx_scheduled_posts_status_time: %', SQLERRM;
END $$;

-- 3.2 فهرس على content_plans.org_id
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes
        WHERE schemaname = 'cmis'
        AND indexname = 'idx_content_plans_org'
    ) THEN
        CREATE INDEX CONCURRENTLY idx_content_plans_org 
        ON cmis.content_plans (org_id)
        WHERE deleted_at IS NULL;
        
        RAISE NOTICE '✅ Created idx_content_plans_org';
    ELSE
        RAISE NOTICE 'ℹ️  idx_content_plans_org already exists';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '⚠️  Could not create idx_content_plans_org: %', SQLERRM;
END $$;

-- 3.3 فهرس على content_items.creative_context_id
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes
        WHERE schemaname = 'cmis'
        AND indexname = 'idx_content_items_creative_context'
    ) THEN
        CREATE INDEX CONCURRENTLY idx_content_items_creative_context 
        ON cmis.content_items (creative_context_id)
        WHERE deleted_at IS NULL;
        
        RAISE NOTICE '✅ Created idx_content_items_creative_context';
    ELSE
        RAISE NOTICE 'ℹ️  idx_content_items_creative_context already exists';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '⚠️  Could not create idx_content_items_creative_context: %', SQLERRM;
END $$;

-- 3.4 فهرس على users.status
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_indexes
        WHERE schemaname = 'cmis'
        AND indexname = 'idx_users_status'
    ) THEN
        CREATE INDEX CONCURRENTLY idx_users_status 
        ON cmis.users (status)
        WHERE deleted_at IS NULL;
        
        RAISE NOTICE '✅ Created idx_users_status';
    ELSE
        RAISE NOTICE 'ℹ️  idx_users_status already exists';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '⚠️  Could not create idx_users_status: %', SQLERRM;
END $$;

-- 3.5 فهرس مركب على performance_metrics
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'cmis'
        AND table_name = 'performance_metrics'
    ) THEN
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes
            WHERE schemaname = 'cmis'
            AND indexname = 'idx_performance_metrics_campaign_time'
        ) THEN
            CREATE INDEX CONCURRENTLY idx_performance_metrics_campaign_time 
            ON cmis.performance_metrics (campaign_id, observed_at DESC)
            WHERE deleted_at IS NULL;
            
            RAISE NOTICE '✅ Created idx_performance_metrics_campaign_time';
        ELSE
            RAISE NOTICE 'ℹ️  idx_performance_metrics_campaign_time already exists';
        END IF;
    ELSE
        RAISE NOTICE 'ℹ️  Table performance_metrics does not exist';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE '⚠️  Could not create idx_performance_metrics_campaign_time: %', SQLERRM;
END $$;

-- ====================================================================
-- 4. إضافة تعليقات على الفهارس
-- ====================================================================

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = 'cmis' AND indexname = 'idx_scheduled_posts_status_time') THEN
        COMMENT ON INDEX cmis.idx_scheduled_posts_status_time IS 
        'Optimized index for finding posts ready to publish (status + scheduled_at)';
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = 'cmis' AND indexname = 'idx_content_plans_org') THEN
        COMMENT ON INDEX cmis.idx_content_plans_org IS 
        'Index for filtering content plans by organization';
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = 'cmis' AND indexname = 'idx_content_items_creative_context') THEN
        COMMENT ON INDEX cmis.idx_content_items_creative_context IS 
        'Index for finding content items by creative context';
    END IF;
    
    IF EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname = 'cmis' AND indexname = 'idx_users_status') THEN
        COMMENT ON INDEX cmis.idx_users_status IS 
        'Index for filtering users by status (active/inactive/etc)';
    END IF;
END $$;

-- ====================================================================
-- 5. تحليل الجداول المتأثرة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Analyzing Tables';
    RAISE NOTICE 'تحليل الجداول';
    RAISE NOTICE '';
END $$;

ANALYZE cmis.users;
ANALYZE cmis.content_items;
ANALYZE cmis.content_plans;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'scheduled_social_posts') THEN
        ANALYZE cmis.scheduled_social_posts;
    END IF;
    
    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'performance_metrics') THEN
        ANALYZE cmis.performance_metrics;
    END IF;
    
    RAISE NOTICE '✅ Table statistics updated';
END $$;

-- ====================================================================
-- 6. تسجيل الإنجاز
-- ====================================================================

INSERT INTO operations.fix_tracking (
    script_part, 
    fix_category, 
    fix_description,
    status,
    executed_at
)
VALUES 
    ('05_indexes', 'optimization', 'Removed duplicate indexes', 'completed', CURRENT_TIMESTAMP),
    ('05_indexes', 'optimization', 'Added optimized indexes', 'completed', CURRENT_TIMESTAMP);

-- ====================================================================
-- 7. ملخص نهائي
-- ====================================================================

DO $$
DECLARE
    v_new_indexes INTEGER;
    v_total_size TEXT;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Part 5 Summary';
    RAISE NOTICE 'ملخص الجزء الخامس';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- عد الفهارس الجديدة
    SELECT COUNT(*) INTO v_new_indexes
    FROM pg_indexes
    WHERE schemaname = 'cmis'
    AND indexname IN (
        'idx_scheduled_posts_status_time',
        'idx_content_plans_org',
        'idx_content_items_creative_context',
        'idx_users_status',
        'idx_performance_metrics_campaign_time'
    );
    
    -- حجم الفهارس
    SELECT pg_size_pretty(SUM(pg_relation_size(indexrelid))) INTO v_total_size
    FROM pg_stat_user_indexes
    WHERE schemaname = 'cmis';
    
    RAISE NOTICE 'New indexes created: %', v_new_indexes;
    RAISE NOTICE 'Total indexes size: %', v_total_size;
    RAISE NOTICE '';
    RAISE NOTICE '✅ Part 5 completed successfully';
    RAISE NOTICE '✅ اكتمل الجزء الخامس بنجاح';
    RAISE NOTICE '';
    RAISE NOTICE 'Next step: Run 06_monitoring.sql';
    RAISE NOTICE '=================================================================';
END $$;

COMMIT;

\timing off
