-- ====================================================================
-- CMIS Database Fixes - Part 2: Missing Columns & Triggers
-- الجزء الثاني: الأعمدة المفقودة والمحفزات
-- ====================================================================
-- Purpose: إضافة الأعمدة المفقودة وإنشاء المحفزات التلقائية
-- Depends on: 01_preflight_checks.sql
-- Safe to rollback: YES (within transaction)
-- Execution time: ~3-5 minutes
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

BEGIN;

-- ====================================================================
-- 1. إضافة الأعمدة المفقودة
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Adding Missing Columns';
    RAISE NOTICE 'إضافة الأعمدة المفقودة';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
END $$;

-- 1.1 إضافة updated_at لجدول creative_assets
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'cmis' 
        AND table_name = 'creative_assets' 
        AND column_name = 'updated_at'
    ) THEN
        ALTER TABLE cmis.creative_assets 
        ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP;
        
        -- تحديث السجلات الموجودة
        UPDATE cmis.creative_assets 
        SET updated_at = created_at 
        WHERE updated_at IS NULL;
        
        RAISE NOTICE '✅ Added updated_at to creative_assets';
    ELSE
        RAISE NOTICE 'ℹ️  updated_at already exists in creative_assets';
    END IF;
END $$;

-- 1.2 إضافة updated_at لجدول experiments
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'cmis' 
        AND table_name = 'experiments' 
        AND column_name = 'updated_at'
    ) THEN
        ALTER TABLE cmis.experiments 
        ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP;
        
        -- تحديث السجلات الموجودة
        UPDATE cmis.experiments 
        SET updated_at = created_at 
        WHERE updated_at IS NULL;
        
        RAISE NOTICE '✅ Added updated_at to experiments';
    ELSE
        RAISE NOTICE 'ℹ️  updated_at already exists in experiments';
    END IF;
END $$;

-- 1.3 التأكد من وجود updated_at في content_items (موجود بالفعل)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'cmis' 
        AND table_name = 'content_items' 
        AND column_name = 'updated_at'
    ) THEN
        RAISE NOTICE '✅ content_items.updated_at confirmed';
    ELSE
        RAISE WARNING 'content_items missing updated_at - this should not happen';
    END IF;
END $$;

-- 1.4 إضافة تعليقات توضيحية
COMMENT ON COLUMN cmis.creative_assets.updated_at IS 'Timestamp of last update - automatically maintained';
COMMENT ON COLUMN cmis.experiments.updated_at IS 'Timestamp of last update - automatically maintained';

-- ====================================================================
-- 2. إنشاء أو تحديث دالة تحديث updated_at
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Creating/Updating Trigger Function';
    RAISE NOTICE 'إنشاء دالة المحفز';
    RAISE NOTICE '';
END $$;

CREATE OR REPLACE FUNCTION cmis.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION cmis.update_updated_at_column() IS 
'Automatically updates updated_at column to current timestamp on UPDATE';

-- ====================================================================
-- 3. إنشاء المحفزات (Triggers)
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE 'Creating Triggers';
    RAISE NOTICE 'إنشاء المحفزات';
    RAISE NOTICE '';
END $$;

-- 3.1 Trigger لجدول creative_assets
DROP TRIGGER IF EXISTS trg_update_creative_assets_updated_at ON cmis.creative_assets;
CREATE TRIGGER trg_update_creative_assets_updated_at
    BEFORE UPDATE ON cmis.creative_assets
    FOR EACH ROW
    EXECUTE FUNCTION cmis.update_updated_at_column();

RAISE NOTICE '✅ Created trigger for creative_assets';

-- 3.2 Trigger لجدول experiments
DROP TRIGGER IF EXISTS trg_update_experiments_updated_at ON cmis.experiments;
CREATE TRIGGER trg_update_experiments_updated_at
    BEFORE UPDATE ON cmis.experiments
    FOR EACH ROW
    EXECUTE FUNCTION cmis.update_updated_at_column();

RAISE NOTICE '✅ Created trigger for experiments';

-- 3.3 التحقق من وجود trigger لـ content_items (قد يكون موجود)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.triggers
        WHERE event_object_schema = 'cmis'
        AND event_object_table = 'content_items'
        AND trigger_name LIKE '%updated_at%'
    ) THEN
        CREATE TRIGGER trg_update_content_items_updated_at
            BEFORE UPDATE ON cmis.content_items
            FOR EACH ROW
            EXECUTE FUNCTION cmis.update_updated_at_column();
        
        RAISE NOTICE '✅ Created trigger for content_items';
    ELSE
        RAISE NOTICE 'ℹ️  Trigger already exists for content_items';
    END IF;
END $$;

-- ====================================================================
-- 4. اختبار المحفزات
-- ====================================================================

DO $$
DECLARE
    v_test_id UUID;
    v_old_timestamp TIMESTAMP;
    v_new_timestamp TIMESTAMP;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Testing Triggers';
    RAISE NOTICE 'اختبار المحفزات';
    RAISE NOTICE '';
    
    -- اختبار على creative_assets (إذا كان هناك سجلات)
    SELECT asset_id, updated_at INTO v_test_id, v_old_timestamp
    FROM cmis.creative_assets
    LIMIT 1;
    
    IF v_test_id IS NOT NULL THEN
        -- الانتظار قليلاً للتأكد من اختلاف الوقت
        PERFORM pg_sleep(0.1);
        
        -- تحديث السجل
        UPDATE cmis.creative_assets
        SET status = COALESCE(status, 'draft')
        WHERE asset_id = v_test_id;
        
        -- التحقق من التحديث
        SELECT updated_at INTO v_new_timestamp
        FROM cmis.creative_assets
        WHERE asset_id = v_test_id;
        
        IF v_new_timestamp > v_old_timestamp THEN
            RAISE NOTICE '✅ Trigger test PASSED for creative_assets';
        ELSE
            RAISE WARNING 'Trigger test FAILED for creative_assets';
        END IF;
    ELSE
        RAISE NOTICE 'ℹ️  No records in creative_assets to test trigger';
    END IF;
END $$;

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
    ('02_missing_columns', 'columns', 'Added updated_at to creative_assets', 'completed', CURRENT_TIMESTAMP),
    ('02_missing_columns', 'columns', 'Added updated_at to experiments', 'completed', CURRENT_TIMESTAMP),
    ('02_missing_columns', 'triggers', 'Created update triggers', 'completed', CURRENT_TIMESTAMP);

-- ====================================================================
-- 6. ملخص نهائي
-- ====================================================================

DO $$
DECLARE
    v_added_columns INTEGER := 0;
    v_created_triggers INTEGER := 0;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Part 2 Summary';
    RAISE NOTICE 'ملخص الجزء الثاني';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- عد الأعمدة المضافة
    SELECT COUNT(*) INTO v_added_columns
    FROM information_schema.columns
    WHERE table_schema = 'cmis'
    AND column_name = 'updated_at'
    AND table_name IN ('creative_assets', 'experiments');
    
    -- عد المحفزات المنشأة
    SELECT COUNT(*) INTO v_created_triggers
    FROM information_schema.triggers
    WHERE event_object_schema = 'cmis'
    AND trigger_name LIKE '%updated_at%'
    AND event_object_table IN ('creative_assets', 'experiments', 'content_items');
    
    RAISE NOTICE 'Columns added: %', v_added_columns;
    RAISE NOTICE 'Triggers created: %', v_created_triggers;
    RAISE NOTICE '';
    RAISE NOTICE '✅ Part 2 completed successfully';
    RAISE NOTICE '✅ اكتمل الجزء الثاني بنجاح';
    RAISE NOTICE '';
    RAISE NOTICE 'Next step: Run 03_foreign_keys.sql';
    RAISE NOTICE '=================================================================';
END $$;

COMMIT;

\timing off
