-- ====================================================================
-- CMIS Database Fixes - Part 1: Pre-flight Checks
-- الجزء الأول: فحوصات ما قبل التنفيذ
-- ====================================================================
-- Purpose: التحقق من الحالة الحالية وتجهيز البيئة
-- Safe to run: YES (read-only checks + preparation)
-- Execution time: ~2-3 minutes
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

-- ====================================================================
-- 1. التحقق من الإصدار والصلاحيات
-- ====================================================================

DO $$
DECLARE
    v_version TEXT;
    v_is_superuser BOOLEAN;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'CMIS Database Pre-flight Checks';
    RAISE NOTICE 'فحوصات ما قبل تطبيق إصلاحات قاعدة البيانات';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- فحص الإصدار
    SELECT version() INTO v_version;
    RAISE NOTICE 'PostgreSQL Version: %', split_part(v_version, ' ', 2);
    
    IF split_part(split_part(v_version, ' ', 2), '.', 1)::int < 14 THEN
        RAISE EXCEPTION 'PostgreSQL 14.0 or higher required. Current version: %', v_version;
    END IF;
    
    -- فحص الصلاحيات
    SELECT usesuper INTO v_is_superuser 
    FROM pg_user 
    WHERE usename = current_user;
    
    RAISE NOTICE 'Current User: %', current_user;
    RAISE NOTICE 'Is Superuser: %', v_is_superuser;
    
    IF NOT v_is_superuser THEN
        RAISE WARNING 'Current user is not superuser. Some operations may fail.';
    END IF;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 2. فحص حالة الجداول الرئيسية
-- ====================================================================

DO $$
DECLARE
    v_table_exists BOOLEAN;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking Main Tables';
    RAISE NOTICE '=================================================================';
    
    -- التحقق من وجود الجداول الأساسية
    SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'cmis' AND table_name = 'users'
    ) INTO v_table_exists;
    RAISE NOTICE 'cmis.users: %', CASE WHEN v_table_exists THEN '✅ EXISTS' ELSE '❌ MISSING' END;
    
    SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'cmis' AND table_name = 'creative_assets'
    ) INTO v_table_exists;
    RAISE NOTICE 'cmis.creative_assets: %', CASE WHEN v_table_exists THEN '✅ EXISTS' ELSE '❌ MISSING' END;
    
    SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'cmis' AND table_name = 'content_items'
    ) INTO v_table_exists;
    RAISE NOTICE 'cmis.content_items: %', CASE WHEN v_table_exists THEN '✅ EXISTS' ELSE '❌ MISSING' END;
    
    SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'cmis' AND table_name = 'experiments'
    ) INTO v_table_exists;
    RAISE NOTICE 'cmis.experiments: %', CASE WHEN v_table_exists THEN '✅ EXISTS' ELSE '❌ MISSING' END;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 3. فحص الأعمدة الموجودة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking Existing Columns';
    RAISE NOTICE '=================================================================';
    
    -- creative_assets
    RAISE NOTICE 'creative_assets.created_at: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'cmis' AND table_name = 'creative_assets' 
            AND column_name = 'created_at'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING' END;
    
    RAISE NOTICE 'creative_assets.updated_at: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'cmis' AND table_name = 'creative_assets' 
            AND column_name = 'updated_at'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    -- content_items
    RAISE NOTICE 'content_items.updated_at: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'cmis' AND table_name = 'content_items' 
            AND column_name = 'updated_at'
        ) THEN '✅ EXISTS' ELSE '⚠️ WILL CHECK' END;
    
    -- experiments
    RAISE NOTICE 'experiments.updated_at: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'cmis' AND table_name = 'experiments' 
            AND column_name = 'updated_at'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 4. فحص المفاتيح الخارجية الموجودة
-- ====================================================================

CREATE TEMP TABLE IF NOT EXISTS existing_fks AS
SELECT
    tc.constraint_name,
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name
FROM information_schema.table_constraints AS tc 
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
AND tc.table_schema = 'cmis';

DO $$
DECLARE
    v_fk_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking Foreign Keys';
    RAISE NOTICE '=================================================================';
    
    SELECT COUNT(*) INTO v_fk_count FROM existing_fks;
    RAISE NOTICE 'Total existing foreign keys: %', v_fk_count;
    
    -- content_items.org_id FK
    RAISE NOTICE 'content_items -> orgs FK: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM existing_fks 
            WHERE table_name = 'content_items' AND column_name = 'org_id'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    -- content_plans.org_id FK
    RAISE NOTICE 'content_plans -> orgs FK: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM existing_fks 
            WHERE table_name = 'content_plans' AND column_name = 'org_id'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 5. فحص الفهارس المكررة
-- ====================================================================

DO $$
DECLARE
    v_dup_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking for Duplicate Indexes';
    RAISE NOTICE '=================================================================';
    
    WITH index_columns AS (
        SELECT
            schemaname,
            tablename,
            indexname,
            string_agg(attname, ',' ORDER BY attnum) as columns
        FROM pg_indexes
        JOIN pg_class ON pg_indexes.indexname = pg_class.relname
        JOIN pg_index ON pg_class.oid = pg_index.indexrelid
        JOIN pg_attribute ON pg_index.indrelid = pg_attribute.attrelid 
            AND pg_attribute.attnum = ANY(pg_index.indkey)
        WHERE schemaname = 'cmis'
        GROUP BY schemaname, tablename, indexname
    ),
    duplicates AS (
        SELECT 
            i1.tablename,
            i1.indexname as index1,
            i2.indexname as index2,
            i1.columns
        FROM index_columns i1
        JOIN index_columns i2 ON 
            i1.schemaname = i2.schemaname
            AND i1.tablename = i2.tablename
            AND i1.columns = i2.columns
            AND i1.indexname < i2.indexname
    )
    SELECT COUNT(*) INTO v_dup_count FROM duplicates;
    
    RAISE NOTICE 'Duplicate indexes found: %', v_dup_count;
    
    IF v_dup_count > 0 THEN
        RAISE NOTICE 'These will be cleaned in later steps';
    END IF;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 6. فحص القيود الموجودة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking Constraints';
    RAISE NOTICE '=================================================================';
    
    -- UNIQUE على email
    RAISE NOTICE 'users.email UNIQUE: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_schema = 'cmis' 
            AND table_name = 'users'
            AND constraint_type = 'UNIQUE'
            AND constraint_name LIKE '%email%'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    -- CHECK على post_approvals.status
    RAISE NOTICE 'post_approvals.status CHECK: %', 
        CASE WHEN EXISTS (
            SELECT 1 FROM information_schema.check_constraints
            WHERE constraint_schema = 'cmis'
            AND constraint_name LIKE '%post_approvals%status%'
        ) THEN '✅ EXISTS' ELSE '❌ MISSING (will add)' END;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 7. فحص السجلات اليتيمة (Orphaned Records)
-- ====================================================================

DO $$
DECLARE
    v_orphan_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking for Orphaned Records';
    RAISE NOTICE '=================================================================';
    
    -- فحص content_items بدون org_id صحيح
    SELECT COUNT(*) INTO v_orphan_count
    FROM cmis.content_items ci
    WHERE ci.org_id IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM cmis.orgs o WHERE o.org_id = ci.org_id);
    
    RAISE NOTICE 'content_items orphaned records: %', v_orphan_count;
    
    IF v_orphan_count > 0 THEN
        RAISE WARNING 'Found % orphaned records in content_items', v_orphan_count;
        RAISE NOTICE 'These will be cleaned before adding FK constraint';
    END IF;
    
    -- فحص content_plans بدون org_id صحيح
    SELECT COUNT(*) INTO v_orphan_count
    FROM cmis.content_plans cp
    WHERE cp.org_id IS NOT NULL
    AND NOT EXISTS (SELECT 1 FROM cmis.orgs o WHERE o.org_id = cp.org_id);
    
    RAISE NOTICE 'content_plans orphaned records: %', v_orphan_count;
    
    IF v_orphan_count > 0 THEN
        RAISE WARNING 'Found % orphaned records in content_plans', v_orphan_count;
        RAISE NOTICE 'These will be cleaned before adding FK constraint';
    END IF;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 8. فحص البريد الإلكتروني المكرر
-- ====================================================================

DO $$
DECLARE
    v_dup_email_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Checking for Duplicate Emails';
    RAISE NOTICE '=================================================================';
    
    SELECT COUNT(*) INTO v_dup_email_count
    FROM (
        SELECT email, COUNT(*) as count
        FROM cmis.users
        WHERE email IS NOT NULL
        GROUP BY email
        HAVING COUNT(*) > 1
    ) dups;
    
    RAISE NOTICE 'Duplicate email addresses: %', v_dup_email_count;
    
    IF v_dup_email_count > 0 THEN
        RAISE WARNING 'Found duplicate emails that will be handled';
        RAISE NOTICE 'Duplicates will be renamed with _duplicate_UserID suffix';
    END IF;
    
    RAISE NOTICE '';
END $$;

-- ====================================================================
-- 9. إنشاء جدول لتتبع التغييرات
-- ====================================================================

CREATE SCHEMA IF NOT EXISTS operations;

CREATE TABLE IF NOT EXISTS operations.fix_tracking (
    fix_id SERIAL PRIMARY KEY,
    script_part TEXT NOT NULL,
    fix_category TEXT NOT NULL,
    fix_description TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    executed_at TIMESTAMP WITH TIME ZONE,
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO operations.fix_tracking (script_part, fix_category, fix_description)
VALUES ('01_preflight_checks', 'preparation', 'Pre-flight checks completed');

-- ====================================================================
-- 10. ملخص نهائي
-- ====================================================================

DO $$
DECLARE
    v_ready BOOLEAN := true;
    v_warnings TEXT := '';
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Pre-flight Check Summary';
    RAISE NOTICE 'ملخص الفحوصات';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- التحقق من الجاهزية
    IF NOT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'users') THEN
        v_ready := false;
        v_warnings := v_warnings || '❌ users table missing' || E'\n';
    END IF;
    
    IF v_ready THEN
        RAISE NOTICE '✅ All pre-flight checks passed';
        RAISE NOTICE '✅ جميع الفحوصات نجحت';
        RAISE NOTICE '';
        RAISE NOTICE 'System is ready for fixes to be applied';
        RAISE NOTICE 'النظام جاهز لتطبيق الإصلاحات';
        RAISE NOTICE '';
        RAISE NOTICE 'Next step: Run 02_missing_columns.sql';
    ELSE
        RAISE EXCEPTION 'Pre-flight checks failed:%', v_warnings;
    END IF;
    
    RAISE NOTICE '=================================================================';
END $$;

\timing off
