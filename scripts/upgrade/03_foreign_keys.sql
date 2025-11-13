-- ====================================================================
-- CMIS Database Fixes - Part 3: Foreign Keys
-- الجزء الثالث: المفاتيح الخارجية
-- ====================================================================
-- Purpose: إضافة المفاتيح الخارجية المفقودة
-- Depends on: 02_missing_columns.sql
-- Safe to rollback: YES (within transaction)
-- Execution time: ~5-10 minutes (depends on data size)
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

BEGIN;

-- ====================================================================
-- 1. تنظيف السجلات اليتيمة
-- ====================================================================

DO $$
DECLARE
    v_deleted_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Cleaning Orphaned Records';
    RAISE NOTICE 'تنظيف السجلات اليتيمة';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    RAISE NOTICE 'This step will delete records that reference non-existent parent records';
    RAISE NOTICE 'هذه الخطوة ستحذف السجلات التي تشير إلى سجلات أب غير موجودة';
    RAISE NOTICE '';
END $$;

-- 1.1 تنظيف content_items.org_id
DO $$
DECLARE
    v_deleted_count INTEGER;
BEGIN
    -- حذف السجلات اليتيمة
    WITH deleted AS (
        DELETE FROM cmis.content_items 
        WHERE org_id IS NOT NULL 
        AND org_id NOT IN (SELECT org_id FROM cmis.orgs)
        RETURNING item_id
    )
    SELECT COUNT(*) INTO v_deleted_count FROM deleted;
    
    IF v_deleted_count > 0 THEN
        RAISE WARNING 'Deleted % orphaned records from content_items (invalid org_id)', v_deleted_count;
        
        -- تسجيل في جدول التتبع
        INSERT INTO operations.fix_tracking (
            script_part, 
            fix_category, 
            fix_description,
            status,
            executed_at
        ) VALUES (
            '03_foreign_keys',
            'cleanup',
            format('Deleted %s orphaned content_items records', v_deleted_count),
            'completed',
            CURRENT_TIMESTAMP
        );
    ELSE
        RAISE NOTICE '✅ No orphaned records found in content_items.org_id';
    END IF;
END $$;

-- 1.2 تنظيف content_items.creative_context_id
DO $$
DECLARE
    v_cleaned_count INTEGER;
BEGIN
    -- تعيين NULL للقيم غير الصحيحة (لأن العمود nullable)
    WITH updated AS (
        UPDATE cmis.content_items 
        SET creative_context_id = NULL 
        WHERE creative_context_id IS NOT NULL 
        AND creative_context_id NOT IN (
            SELECT context_id FROM cmis.creative_contexts
        )
        RETURNING item_id
    )
    SELECT COUNT(*) INTO v_cleaned_count FROM updated;
    
    IF v_cleaned_count > 0 THEN
        RAISE WARNING 'Set creative_context_id to NULL for % records (invalid reference)', v_cleaned_count;
    ELSE
        RAISE NOTICE '✅ No invalid creative_context_id found in content_items';
    END IF;
END $$;

-- 1.3 تنظيف content_plans.org_id
DO $$
DECLARE
    v_deleted_count INTEGER;
BEGIN
    WITH deleted AS (
        DELETE FROM cmis.content_plans 
        WHERE org_id IS NOT NULL 
        AND org_id NOT IN (SELECT org_id FROM cmis.orgs)
        RETURNING plan_id
    )
    SELECT COUNT(*) INTO v_deleted_count FROM deleted;
    
    IF v_deleted_count > 0 THEN
        RAISE WARNING 'Deleted % orphaned records from content_plans (invalid org_id)', v_deleted_count;
    ELSE
        RAISE NOTICE '✅ No orphaned records found in content_plans.org_id';
    END IF;
END $$;

-- ====================================================================
-- 2. إضافة المفاتيح الخارجية
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Adding Foreign Keys';
    RAISE NOTICE 'إضافة المفاتيح الخارجية';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
END $$;

-- 2.1 FK: content_items.org_id -> orgs.org_id
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_schema = 'cmis' 
        AND table_name = 'content_items' 
        AND constraint_name = 'fk_content_items_org_id'
    ) THEN
        ALTER TABLE cmis.content_items
        ADD CONSTRAINT fk_content_items_org_id 
        FOREIGN KEY (org_id) 
        REFERENCES cmis.orgs(org_id) 
        ON DELETE CASCADE;
        
        RAISE NOTICE '✅ Added FK: content_items.org_id -> orgs.org_id';
    ELSE
        RAISE NOTICE 'ℹ️  FK already exists: content_items.org_id';
    END IF;
END $$;

-- 2.2 FK: content_items.creative_context_id -> creative_contexts.context_id
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_schema = 'cmis' 
        AND table_name = 'content_items' 
        AND constraint_name = 'fk_content_items_creative_context'
    ) THEN
        ALTER TABLE cmis.content_items
        ADD CONSTRAINT fk_content_items_creative_context 
        FOREIGN KEY (creative_context_id) 
        REFERENCES cmis.creative_contexts(context_id) 
        ON DELETE SET NULL;
        
        RAISE NOTICE '✅ Added FK: content_items.creative_context_id -> creative_contexts.context_id';
    ELSE
        RAISE NOTICE 'ℹ️  FK already exists: content_items.creative_context_id';
    END IF;
END $$;

-- 2.3 FK: content_plans.org_id -> orgs.org_id
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_schema = 'cmis' 
        AND table_name = 'content_plans' 
        AND constraint_name = 'fk_content_plans_org_id'
    ) THEN
        ALTER TABLE cmis.content_plans
        ADD CONSTRAINT fk_content_plans_org_id 
        FOREIGN KEY (org_id) 
        REFERENCES cmis.orgs(org_id) 
        ON DELETE CASCADE;
        
        RAISE NOTICE '✅ Added FK: content_plans.org_id -> orgs.org_id';
    ELSE
        RAISE NOTICE 'ℹ️  FK already exists: content_plans.org_id';
    END IF;
END $$;

-- ====================================================================
-- 3. إضافة تعليقات توضيحية
-- ====================================================================

COMMENT ON CONSTRAINT fk_content_items_org_id ON cmis.content_items IS 
'Ensures content items belong to valid organizations';

COMMENT ON CONSTRAINT fk_content_items_creative_context ON cmis.content_items IS 
'Links content items to their creative context (optional)';

COMMENT ON CONSTRAINT fk_content_plans_org_id ON cmis.content_plans IS 
'Ensures content plans belong to valid organizations';

-- ====================================================================
-- 4. اختبار المفاتيح الخارجية
-- ====================================================================

DO $$
DECLARE
    v_test_passed BOOLEAN := true;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Testing Foreign Keys';
    RAISE NOTICE 'اختبار المفاتيح الخارجية';
    RAISE NOTICE '';
    
    -- اختبار 1: محاولة إدراج سجل بـ org_id غير موجود
    BEGIN
        INSERT INTO cmis.content_items (org_id, plan_id, title)
        VALUES ('00000000-0000-0000-0000-000000000000'::uuid, 
                '00000000-0000-0000-0000-000000000001'::uuid,
                'TEST_DELETE_ME');
        
        -- إذا نجح الإدراج، هذا خطأ
        v_test_passed := false;
        RAISE WARNING 'FK test FAILED: Should not allow invalid org_id';
        
        -- تنظيف
        DELETE FROM cmis.content_items WHERE title = 'TEST_DELETE_ME';
    EXCEPTION 
        WHEN foreign_key_violation THEN
            RAISE NOTICE '✅ FK test PASSED: Correctly rejected invalid org_id';
    END;
    
    IF NOT v_test_passed THEN
        RAISE EXCEPTION 'Foreign key tests failed';
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
    ('03_foreign_keys', 'fk', 'Added FK: content_items.org_id', 'completed', CURRENT_TIMESTAMP),
    ('03_foreign_keys', 'fk', 'Added FK: content_items.creative_context_id', 'completed', CURRENT_TIMESTAMP),
    ('03_foreign_keys', 'fk', 'Added FK: content_plans.org_id', 'completed', CURRENT_TIMESTAMP);

-- ====================================================================
-- 6. ملخص نهائي
-- ====================================================================

DO $$
DECLARE
    v_fk_count INTEGER;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Part 3 Summary';
    RAISE NOTICE 'ملخص الجزء الثالث';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- عد المفاتيح الخارجية المضافة
    SELECT COUNT(*) INTO v_fk_count
    FROM information_schema.table_constraints
    WHERE constraint_schema = 'cmis'
    AND constraint_type = 'FOREIGN KEY'
    AND constraint_name IN (
        'fk_content_items_org_id',
        'fk_content_items_creative_context',
        'fk_content_plans_org_id'
    );
    
    RAISE NOTICE 'Foreign keys added: %', v_fk_count;
    RAISE NOTICE '';
    
    IF v_fk_count >= 3 THEN
        RAISE NOTICE '✅ All foreign keys added successfully';
        RAISE NOTICE '✅ تمت إضافة جميع المفاتيح الخارجية بنجاح';
    ELSE
        RAISE WARNING 'Some foreign keys may be missing (expected 3, got %)', v_fk_count;
    END IF;
    
    RAISE NOTICE '';
    RAISE NOTICE 'Next step: Run 04_constraints.sql';
    RAISE NOTICE '=================================================================';
END $$;

COMMIT;

\timing off
