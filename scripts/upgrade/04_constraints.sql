-- ====================================================================
-- CMIS Database Fixes - Part 4: Constraints (UNIQUE & CHECK)
-- الجزء الرابع: القيود (UNIQUE و CHECK)
-- ====================================================================
-- Purpose: إضافة قيود UNIQUE و CHECK المفقودة
-- Depends on: 03_foreign_keys.sql
-- Safe to rollback: YES (within transaction)
-- Execution time: ~3-5 minutes
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

BEGIN;

-- ====================================================================
-- 1. معالجة البريد الإلكتروني المكرر
-- ====================================================================

DO $$
DECLARE
    v_dup_count INTEGER;
BEGIN
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Handling Duplicate Emails';
    RAISE NOTICE 'معالجة البريد الإلكتروني المكرر';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- عد البريد المكرر
    SELECT COUNT(*) INTO v_dup_count
    FROM (
        SELECT email, COUNT(*) as count
        FROM cmis.users
        WHERE email IS NOT NULL
        GROUP BY email
        HAVING COUNT(*) > 1
    ) dups;
    
    IF v_dup_count > 0 THEN
        RAISE NOTICE 'Found % duplicate email addresses', v_dup_count;
        RAISE NOTICE 'Renaming duplicates with _duplicate_UserID suffix';
        
        -- إعادة تسمية البريد المكرر
        WITH duplicates AS (
            SELECT email, MIN(user_id) as keep_id
            FROM cmis.users
            WHERE email IS NOT NULL
            GROUP BY email
            HAVING COUNT(*) > 1
        )
        UPDATE cmis.users u
        SET email = u.email || '_duplicate_' || u.user_id::text
        WHERE u.email IN (SELECT email FROM duplicates)
        AND u.user_id NOT IN (SELECT keep_id FROM duplicates);
        
        RAISE NOTICE '✅ Renamed duplicate emails';
    ELSE
        RAISE NOTICE '✅ No duplicate emails found';
    END IF;
END $$;

-- ====================================================================
-- 2. إضافة قيد UNIQUE على users.email
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Adding UNIQUE Constraints';
    RAISE NOTICE 'إضافة قيود UNIQUE';
    RAISE NOTICE '';
    
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_schema = 'cmis' 
        AND table_name = 'users' 
        AND constraint_name = 'users_email_unique'
    ) THEN
        ALTER TABLE cmis.users
        ADD CONSTRAINT users_email_unique 
        UNIQUE (email);
        
        RAISE NOTICE '✅ Added UNIQUE constraint on users.email';
    ELSE
        RAISE NOTICE 'ℹ️  UNIQUE constraint already exists on users.email';
    END IF;
END $$;

COMMENT ON CONSTRAINT users_email_unique ON cmis.users IS 
'Ensures each email address is unique across all users';

-- ====================================================================
-- 3. إضافة قيود CHECK
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Adding CHECK Constraints';
    RAISE NOTICE 'إضافة قيود CHECK';
    RAISE NOTICE '';
END $$;

-- 3.1 CHECK على post_approvals.status
DO $$
BEGIN
    -- التحقق من وجود الجدول أولاً
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'cmis'
        AND table_name = 'post_approvals'
    ) THEN
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.check_constraints 
            WHERE constraint_schema = 'cmis' 
            AND constraint_name = 'chk_post_approvals_status'
        ) THEN
            -- التأكد من عدم وجود قيم غير صحيحة
            UPDATE cmis.post_approvals
            SET status = 'pending'
            WHERE status NOT IN ('pending', 'approved', 'rejected');
            
            ALTER TABLE cmis.post_approvals
            ADD CONSTRAINT chk_post_approvals_status 
            CHECK (status IN ('pending', 'approved', 'rejected'));
            
            RAISE NOTICE '✅ Added CHECK constraint on post_approvals.status';
        ELSE
            RAISE NOTICE 'ℹ️  CHECK constraint already exists on post_approvals.status';
        END IF;
    ELSE
        RAISE NOTICE 'ℹ️  Table post_approvals does not exist, skipping';
    END IF;
END $$;

-- 3.2 CHECK على users.status (إن لم يكن موجوداً)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.check_constraints 
        WHERE constraint_schema = 'cmis' 
        AND constraint_name = 'chk_users_status'
    ) THEN
        -- تنظيف القيم غير الصحيحة
        UPDATE cmis.users
        SET status = 'active'
        WHERE status NOT IN ('active', 'inactive', 'suspended', 'deleted')
        OR status IS NULL;
        
        ALTER TABLE cmis.users
        ADD CONSTRAINT chk_users_status 
        CHECK (status IN ('active', 'inactive', 'suspended', 'deleted'));
        
        RAISE NOTICE '✅ Added CHECK constraint on users.status';
    ELSE
        RAISE NOTICE 'ℹ️  CHECK constraint already exists on users.status';
    END IF;
END $$;

-- 3.3 CHECK على scheduled_reports.frequency (إن وجد الجدول)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = 'cmis'
        AND table_name = 'scheduled_reports'
    ) THEN
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.check_constraints 
            WHERE constraint_schema = 'cmis' 
            AND constraint_name = 'chk_scheduled_reports_frequency'
        ) THEN
            -- تنظيف القيم غير الصحيحة
            UPDATE cmis.scheduled_reports
            SET frequency = 'weekly'
            WHERE frequency NOT IN ('daily', 'weekly', 'monthly', 'quarterly', 'yearly')
            OR frequency IS NULL;
            
            ALTER TABLE cmis.scheduled_reports
            ADD CONSTRAINT chk_scheduled_reports_frequency 
            CHECK (frequency IN ('daily', 'weekly', 'monthly', 'quarterly', 'yearly'));
            
            RAISE NOTICE '✅ Added CHECK constraint on scheduled_reports.frequency';
        ELSE
            RAISE NOTICE 'ℹ️  CHECK constraint already exists on scheduled_reports.frequency';
        END IF;
    ELSE
        RAISE NOTICE 'ℹ️  Table scheduled_reports does not exist, skipping';
    END IF;
END $$;

-- ====================================================================
-- 4. اختبار القيود
-- ====================================================================

DO $$
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE 'Testing Constraints';
    RAISE NOTICE 'اختبار القيود';
    RAISE NOTICE '';
    
    -- اختبار UNIQUE على email
    BEGIN
        INSERT INTO cmis.users (email, role, status)
        VALUES ('test_unique_constraint@example.com', 'viewer', 'active');
        
        -- محاولة إدراج نفس البريد مرة أخرى
        INSERT INTO cmis.users (email, role, status)
        VALUES ('test_unique_constraint@example.com', 'viewer', 'active');
        
        -- إذا نجح، هذا خطأ
        RAISE WARNING 'UNIQUE constraint test FAILED';
    EXCEPTION 
        WHEN unique_violation THEN
            RAISE NOTICE '✅ UNIQUE constraint test PASSED';
            -- تنظيف
            DELETE FROM cmis.users WHERE email = 'test_unique_constraint@example.com';
    END;
    
    -- اختبار CHECK على users.status
    BEGIN
        INSERT INTO cmis.users (email, role, status)
        VALUES ('test_check_constraint@example.com', 'viewer', 'invalid_status');
        
        -- إذا نجح، هذا خطأ
        RAISE WARNING 'CHECK constraint test FAILED';
        DELETE FROM cmis.users WHERE email = 'test_check_constraint@example.com';
    EXCEPTION 
        WHEN check_violation THEN
            RAISE NOTICE '✅ CHECK constraint test PASSED';
    END;
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
    ('04_constraints', 'unique', 'Added UNIQUE on users.email', 'completed', CURRENT_TIMESTAMP),
    ('04_constraints', 'check', 'Added CHECK constraints', 'completed', CURRENT_TIMESTAMP);

-- ====================================================================
-- 6. ملخص نهائي
-- ====================================================================

DO $$
DECLARE
    v_unique_count INTEGER;
    v_check_count INTEGER;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE 'Part 4 Summary';
    RAISE NOTICE 'ملخص الجزء الرابع';
    RAISE NOTICE '=================================================================';
    RAISE NOTICE '';
    
    -- عد قيود UNIQUE
    SELECT COUNT(*) INTO v_unique_count
    FROM information_schema.table_constraints
    WHERE constraint_schema = 'cmis'
    AND constraint_type = 'UNIQUE'
    AND constraint_name = 'users_email_unique';
    
    -- عد قيود CHECK
    SELECT COUNT(*) INTO v_check_count
    FROM information_schema.check_constraints
    WHERE constraint_schema = 'cmis'
    AND constraint_name IN (
        'chk_post_approvals_status',
        'chk_users_status',
        'chk_scheduled_reports_frequency'
    );
    
    RAISE NOTICE 'UNIQUE constraints added: %', v_unique_count;
    RAISE NOTICE 'CHECK constraints added: %', v_check_count;
    RAISE NOTICE '';
    RAISE NOTICE '✅ Part 4 completed successfully';
    RAISE NOTICE '✅ اكتمل الجزء الرابع بنجاح';
    RAISE NOTICE '';
    RAISE NOTICE 'Next step: Run 05_indexes.sql';
    RAISE NOTICE '=================================================================';
END $$;

COMMIT;

\timing off
