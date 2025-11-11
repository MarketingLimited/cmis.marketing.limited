-- ============================================================================
-- CMIS Security Enhancement Migration - PRODUCTION SAFE VERSION
-- تحسين الأمان وعزل المؤسسات - إصدار آمن للإنتاج
-- ============================================================================
-- التاريخ: 2025-11-11
-- الإصدار: 2.0 (بعد التحليل العميق)
-- 
-- تم تحليل:
-- - 96 جدول في schema cmis
-- - 22 سياسة RLS موجودة
-- - 19 جدول عليه RLS مفعّل
-- - 48 جدول يحتوي على org_id
-- ============================================================================

-- ============================================================================
-- 🔍 PRE-FLIGHT VALIDATION
-- التحقق الأولي قبل البدء
-- ============================================================================

DO $$
DECLARE
    v_user_count INTEGER;
    v_user_org_count INTEGER;
    v_orphaned_users INTEGER;
    v_current_policies INTEGER;
    v_tables_with_org_id INTEGER;
BEGIN
    RAISE NOTICE '================================================';
    RAISE NOTICE '🔍 بدء الفحص الأولي...';
    RAISE NOTICE '================================================';
    
    -- 1. Check users table
    SELECT COUNT(*) INTO v_user_count FROM cmis.users;
    RAISE NOTICE '✓ عدد المستخدمين: %', v_user_count;
    
    -- 2. Check user_orgs
    SELECT COUNT(*) INTO v_user_org_count FROM cmis.user_orgs;
    RAISE NOTICE '✓ عدد علاقات user_orgs: %', v_user_org_count;
    
    -- 3. Check for orphaned users (users with org_id but no user_orgs entry)
    SELECT COUNT(*) INTO v_orphaned_users
    FROM cmis.users u
    WHERE u.org_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM cmis.user_orgs uo
        WHERE uo.user_id = u.user_id
        AND uo.org_id = u.org_id
    );
    
    IF v_orphaned_users > 0 THEN
        RAISE WARNING '⚠️  وجدنا % مستخدم يحتاج ترحيل org_id', v_orphaned_users;
    ELSE
        RAISE NOTICE '✓ جميع المستخدمين لديهم إدخالات صحيحة في user_orgs';
    END IF;
    
    -- 4. Check existing RLS policies
    SELECT COUNT(*) INTO v_current_policies
    FROM pg_policies
    WHERE schemaname = 'cmis';
    RAISE NOTICE '✓ عدد سياسات RLS الحالية: %', v_current_policies;
    
    -- 5. Check tables with org_id
    SELECT COUNT(DISTINCT table_name) INTO v_tables_with_org_id
    FROM information_schema.columns
    WHERE table_schema = 'cmis'
    AND column_name = 'org_id';
    RAISE NOTICE '✓ عدد الجداول التي تحتوي org_id: %', v_tables_with_org_id;
    
    RAISE NOTICE '================================================';
    RAISE NOTICE '✅ اكتمل الفحص الأولي بنجاح';
    RAISE NOTICE '================================================';
    RAISE NOTICE '';
END $$;

-- ============================================================================
-- 📦 PHASE 0: CREATE BACKUP SCHEMA
-- المرحلة 0: إنشاء schema للنسخ الاحتياطي
-- ============================================================================

DO $$
DECLARE
    v_backup_schema TEXT := 'cmis_security_backup_' || to_char(CURRENT_TIMESTAMP, 'YYYYMMDD_HH24MISS');
BEGIN
    RAISE NOTICE '📦 إنشاء schema للنسخ الاحتياطي: %', v_backup_schema;
    EXECUTE format('CREATE SCHEMA IF NOT EXISTS %I', v_backup_schema);
    
    -- Backup critical tables
    EXECUTE format('CREATE TABLE %I.users_backup AS SELECT * FROM cmis.users', v_backup_schema);
    EXECUTE format('CREATE TABLE %I.user_orgs_backup AS SELECT * FROM cmis.user_orgs', v_backup_schema);
    
    -- Save existing functions
    EXECUTE format('
        CREATE TABLE %I.existing_functions AS
        SELECT 
            p.proname as function_name,
            pg_get_functiondef(p.oid) as function_definition
        FROM pg_proc p
        JOIN pg_namespace n ON n.oid = p.pronamespace
        WHERE n.nspname = ''cmis''
        AND p.proname IN (''check_permission'', ''get_current_org_id'', ''get_current_user_id'')
    ', v_backup_schema);
    
    -- Save existing policies
    EXECUTE format('
        CREATE TABLE %I.existing_policies AS
        SELECT * FROM pg_policies WHERE schemaname = ''cmis''
    ', v_backup_schema);
    
    RAISE NOTICE '✅ تم إنشاء النسخة الاحتياطية في: %', v_backup_schema;
    
    -- Store backup schema name for later use
    CREATE TEMP TABLE IF NOT EXISTS migration_context (
        backup_schema TEXT,
        migration_start TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
    );
    INSERT INTO migration_context (backup_schema) VALUES (v_backup_schema);
END $$;

-- ============================================================================
-- 🔧 PHASE 1: CREATE NEW HELPER FUNCTIONS
-- المرحلة 1: إنشاء الدوال المساعدة الجديدة (بدون تعديل القديمة)
-- ============================================================================

RAISE NOTICE '';
RAISE NOTICE '================================================';
RAISE NOTICE '🔧 المرحلة 1: إنشاء الدوال الجديدة';
RAISE NOTICE '================================================';

-- Function 1: Initialize transaction context with SET LOCAL
CREATE OR REPLACE FUNCTION cmis.init_transaction_context(
    p_user_id UUID,
    p_org_id UUID
) RETURNS VOID
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    -- Validate inputs
    IF p_user_id IS NULL THEN
        RAISE EXCEPTION 'user_id cannot be NULL';
    END IF;
    
    IF p_org_id IS NULL THEN
        RAISE EXCEPTION 'org_id cannot be NULL';
    END IF;
    
    -- Verify user belongs to org
    IF NOT EXISTS (
        SELECT 1 FROM cmis.user_orgs
        WHERE user_id = p_user_id
        AND org_id = p_org_id
        AND is_active = true
        AND deleted_at IS NULL
    ) THEN
        RAISE EXCEPTION 'User % does not belong to org % or relationship is not active', 
            p_user_id, p_org_id;
    END IF;
    
    -- Set LOCAL context (transaction-scoped only)
    PERFORM set_config('app.current_user_id', p_user_id::TEXT, TRUE);
    PERFORM set_config('app.current_org_id', p_org_id::TEXT, TRUE);
    PERFORM set_config('app.context_initialized', 'true', TRUE);
    PERFORM set_config('app.context_version', '2.0', TRUE);
    
    -- Log initialization (optional)
    RAISE DEBUG 'Transaction context initialized: user=%, org=%', p_user_id, p_org_id;
END;
$$;

COMMENT ON FUNCTION cmis.init_transaction_context IS 
'تهيئة سياق الأمان للمعاملة الحالية باستخدام SET LOCAL - v2.0';

RAISE NOTICE '✓ تم إنشاء: init_transaction_context';

-- Function 2: Validate transaction context
CREATE OR REPLACE FUNCTION cmis.validate_transaction_context()
RETURNS TABLE(
    is_valid BOOLEAN,
    user_id UUID,
    org_id UUID,
    error_message TEXT,
    context_version TEXT
)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_user_id TEXT;
    v_org_id TEXT;
    v_initialized TEXT;
    v_version TEXT;
BEGIN
    -- Check if context is initialized
    BEGIN
        v_initialized := current_setting('app.context_initialized', TRUE);
        v_version := current_setting('app.context_version', TRUE);
    EXCEPTION WHEN OTHERS THEN
        v_initialized := NULL;
        v_version := NULL;
    END;
    
    IF v_initialized IS NULL OR v_initialized != 'true' THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Transaction context not initialized. Call init_transaction_context() first.'::TEXT,
            NULL::TEXT;
        RETURN;
    END IF;
    
    -- Get user_id and org_id
    BEGIN
        v_user_id := current_setting('app.current_user_id', TRUE);
        v_org_id := current_setting('app.current_org_id', TRUE);
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Failed to read context settings'::TEXT,
            NULL::TEXT;
        RETURN;
    END;
    
    -- Validate they exist
    IF v_user_id IS NULL OR v_org_id IS NULL THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Missing user_id or org_id in context'::TEXT,
            v_version;
        RETURN;
    END IF;
    
    -- Return valid context
    RETURN QUERY SELECT 
        TRUE, 
        v_user_id::UUID, 
        v_org_id::UUID, 
        NULL::TEXT,
        v_version;
END;
$$;

COMMENT ON FUNCTION cmis.validate_transaction_context IS 
'التحقق من صحة سياق المعاملة وإرجاع معلومات المستخدم والمؤسسة';

RAISE NOTICE '✓ تم إنشاء: validate_transaction_context';

-- Function 3: Enhanced check_permission using transaction context
CREATE OR REPLACE FUNCTION cmis.check_permission_tx(
    p_permission TEXT
) RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_context RECORD;
    v_has_permission BOOLEAN;
BEGIN
    -- Validate transaction context first
    SELECT * INTO v_context 
    FROM cmis.validate_transaction_context() 
    LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    -- Check permission using existing function
    SELECT cmis.check_permission(
        v_context.user_id,
        v_context.org_id,
        p_permission
    ) INTO v_has_permission;
    
    RETURN v_has_permission;
END;
$$;

COMMENT ON FUNCTION cmis.check_permission_tx IS 
'التحقق من صلاحية باستخدام السياق المحلي للمعاملة - أسهل للاستخدام من API';

RAISE NOTICE '✓ تم إنشاء: check_permission_tx';

-- Function 4: Get current org_id from LOCAL context
CREATE OR REPLACE FUNCTION cmis.get_current_org_id_tx() RETURNS UUID
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    v_context RECORD;
BEGIN
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    RETURN v_context.org_id;
END;
$$;

COMMENT ON FUNCTION cmis.get_current_org_id_tx IS 
'الحصول على org_id من السياق المحلي للمعاملة';

RAISE NOTICE '✓ تم إنشاء: get_current_org_id_tx';

-- Function 5: Get current user_id from LOCAL context
CREATE OR REPLACE FUNCTION cmis.get_current_user_id_tx() RETURNS UUID
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    v_context RECORD;
BEGIN
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    RETURN v_context.user_id;
END;
$$;

COMMENT ON FUNCTION cmis.get_current_user_id_tx IS 
'الحصول على user_id من السياق المحلي للمعاملة';

RAISE NOTICE '✓ تم إنشاء: get_current_user_id_tx';

RAISE NOTICE '✅ اكتملت المرحلة 1: تم إنشاء 5 دوال جديدة';

-- ============================================================================
-- 🔄 PHASE 2: DATA MIGRATION - users.org_id to user_orgs
-- المرحلة 2: ترحيل البيانات (بحذر شديد)
-- ============================================================================

RAISE NOTICE '';
RAISE NOTICE '================================================';
RAISE NOTICE '🔄 المرحلة 2: ترحيل البيانات';
RAISE NOTICE '================================================';

DO $$
DECLARE
    v_affected_users INTEGER := 0;
    v_migrated INTEGER := 0;
    v_errors INTEGER := 0;
    v_user RECORD;
    v_default_role_id UUID;
BEGIN
    -- Get default role (admin)
    SELECT role_id INTO v_default_role_id
    FROM cmis.roles
    WHERE name = 'admin'
    LIMIT 1;
    
    IF v_default_role_id IS NULL THEN
        RAISE WARNING '⚠️  لم يتم العثور على دور "admin" - سيتم استخدام أول دور متاح';
        SELECT role_id INTO v_default_role_id
        FROM cmis.roles
        LIMIT 1;
    END IF;
    
    IF v_default_role_id IS NULL THEN
        RAISE EXCEPTION 'لا توجد أدوار في جدول roles! يجب إضافة أدوار أولاً';
    END IF;
    
    -- Find users that need migration
    FOR v_user IN 
        SELECT 
            u.user_id,
            u.org_id,
            u.email
        FROM cmis.users u
        WHERE u.org_id IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM cmis.user_orgs uo
            WHERE uo.user_id = u.user_id
            AND uo.org_id = u.org_id
        )
    LOOP
        v_affected_users := v_affected_users + 1;
        
        BEGIN
            -- Insert into user_orgs
            INSERT INTO cmis.user_orgs (
                user_id,
                org_id,
                role_id,
                is_active,
                joined_at
            ) VALUES (
                v_user.user_id,
                v_user.org_id,
                v_default_role_id,
                TRUE,
                CURRENT_TIMESTAMP
            );
            
            v_migrated := v_migrated + 1;
            RAISE DEBUG 'Migrated user %: % -> org %', v_affected_users, v_user.email, v_user.org_id;
            
        EXCEPTION WHEN OTHERS THEN
            v_errors := v_errors + 1;
            RAISE WARNING 'فشل ترحيل المستخدم %: %', v_user.email, SQLERRM;
        END;
    END LOOP;
    
    IF v_affected_users = 0 THEN
        RAISE NOTICE '✓ لا توجد بيانات تحتاج للترحيل';
    ELSE
        RAISE NOTICE '📊 نتائج الترحيل:';
        RAISE NOTICE '   - مستخدمين تم فحصهم: %', v_affected_users;
        RAISE NOTICE '   - تم الترحيل بنجاح: %', v_migrated;
        RAISE NOTICE '   - فشل: %', v_errors;
        
        IF v_errors > 0 THEN
            RAISE WARNING '⚠️  حدثت أخطاء أثناء الترحيل - يرجى المراجعة';
        ELSE
            RAISE NOTICE '✅ اكتمل الترحيل بنجاح لجميع المستخدمين';
        END IF;
    END IF;
END $$;

-- ⚠️ IMPORTANT: Do NOT drop users.org_id yet!
-- We'll keep it for backward compatibility until Phase 4
RAISE NOTICE '';
RAISE NOTICE '⚠️  ملاحظة: لم يتم حذف عمود users.org_id بعد';
RAISE NOTICE '   سيتم حذفه في المرحلة 4 بعد التأكد من نجاح كل شيء';

RAISE NOTICE '✅ اكتملت المرحلة 2: ترحيل البيانات';

-- ============================================================================
-- 🛡️ PHASE 3: CREATE NEW RLS POLICIES (Side-by-side)
-- المرحلة 3: إنشاء سياسات RLS جديدة (بدون حذف القديمة)
-- ============================================================================

RAISE NOTICE '';
RAISE NOTICE '================================================';
RAISE NOTICE '🛡️  المرحلة 3: إنشاء سياسات RLS الجديدة';
RAISE NOTICE '================================================';

-- We'll create NEW policies with "_v2" suffix
-- This allows testing without breaking existing policies

-- Example: campaigns table
DO $$
BEGIN
    -- SELECT policy
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE schemaname = 'cmis' 
        AND tablename = 'campaigns' 
        AND policyname = 'rbac_campaigns_select_v2'
    ) THEN
        CREATE POLICY rbac_campaigns_select_v2 ON cmis.campaigns
            FOR SELECT
            USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND cmis.check_permission_tx('campaigns.view')
            );
        RAISE NOTICE '✓ تم إنشاء: rbac_campaigns_select_v2';
    END IF;
    
    -- INSERT policy
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE schemaname = 'cmis' 
        AND tablename = 'campaigns' 
        AND policyname = 'rbac_campaigns_insert_v2'
    ) THEN
        CREATE POLICY rbac_campaigns_insert_v2 ON cmis.campaigns
            FOR INSERT
            WITH CHECK (cmis.check_permission_tx('campaigns.create'));
        RAISE NOTICE '✓ تم إنشاء: rbac_campaigns_insert_v2';
    END IF;
    
    -- UPDATE policy
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE schemaname = 'cmis' 
        AND tablename = 'campaigns' 
        AND policyname = 'rbac_campaigns_update_v2'
    ) THEN
        CREATE POLICY rbac_campaigns_update_v2 ON cmis.campaigns
            FOR UPDATE
            USING (
                (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
                AND cmis.check_permission_tx('campaigns.edit')
            );
        RAISE NOTICE '✓ تم إنشاء: rbac_campaigns_update_v2';
    END IF;
    
    -- DELETE policy
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE schemaname = 'cmis' 
        AND tablename = 'campaigns' 
        AND policyname = 'rbac_campaigns_delete_v2'
    ) THEN
        CREATE POLICY rbac_campaigns_delete_v2 ON cmis.campaigns
            FOR DELETE
            USING (cmis.check_permission_tx('campaigns.delete'));
        RAISE NOTICE '✓ تم إنشاء: rbac_campaigns_delete_v2';
    END IF;
END $$;

RAISE NOTICE '✅ تم إنشاء سياسات جديدة لجدول campaigns';
RAISE NOTICE '';
RAISE NOTICE '📋 الخطوات التالية:';
RAISE NOTICE '1. اختبار السياسات الجديدة (_v2)';
RAISE NOTICE '2. بعد التأكد، تفعيل السياسات الجديدة وحذف القديمة';
RAISE NOTICE '3. تطبيق نفس النمط على باقي الجداول';

-- ============================================================================
-- 📊 PHASE 4: MONITORING AND AUDIT SETUP
-- المرحلة 4: إعداد المراقبة والتدقيق
-- ============================================================================

RAISE NOTICE '';
RAISE NOTICE '================================================';
RAISE NOTICE '📊 المرحلة 4: إعداد المراقبة';
RAISE NOTICE '================================================';

-- Create audit table if not exists
CREATE TABLE IF NOT EXISTS cmis.security_context_audit (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    transaction_id BIGINT DEFAULT txid_current(),
    user_id UUID,
    org_id UUID,
    action TEXT,
    success BOOLEAN,
    error_message TEXT,
    context_version TEXT,
    session_id TEXT,
    ip_address INET,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_security_audit_user_created 
    ON cmis.security_context_audit(user_id, created_at DESC);
    
CREATE INDEX IF NOT EXISTS idx_security_audit_org_created 
    ON cmis.security_context_audit(org_id, created_at DESC);

COMMENT ON TABLE cmis.security_context_audit IS 
'سجل تدقيق لجميع عمليات تهيئة سياق الأمان';

RAISE NOTICE '✓ تم إنشاء جدول التدقيق: security_context_audit';

-- Create monitoring view
CREATE OR REPLACE VIEW cmis.v_security_context_summary AS
SELECT 
    DATE_TRUNC('hour', created_at) as hour,
    COUNT(*) as total_contexts,
    COUNT(*) FILTER (WHERE success = true) as successful,
    COUNT(*) FILTER (WHERE success = false) as failed,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT org_id) as unique_orgs,
    context_version
FROM cmis.security_context_audit
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY DATE_TRUNC('hour', created_at), context_version
ORDER BY hour DESC;

COMMENT ON VIEW cmis.v_security_context_summary IS 
'ملخص نشاط سياقات الأمان خلال آخر 24 ساعة';

RAISE NOTICE '✓ تم إنشاء View للمراقبة: v_security_context_summary';

RAISE NOTICE '✅ اكتملت المرحلة 4: إعداد المراقبة';

-- ============================================================================
-- 🧪 PHASE 5: TESTING UTILITIES
-- المرحلة 5: أدوات الاختبار
-- ============================================================================

RAISE NOTICE '';
RAISE NOTICE '================================================';
RAISE NOTICE '🧪 المرحلة 5: إنشاء أدوات الاختبار';
RAISE NOTICE '================================================';

-- Test function for transaction isolation
CREATE OR REPLACE FUNCTION cmis.test_new_security_context()
RETURNS TABLE(
    test_name TEXT,
    passed BOOLEAN,
    details TEXT
) 
LANGUAGE plpgsql
AS $$
DECLARE
    v_test_user_id UUID;
    v_test_org_id UUID;
    v_context RECORD;
    v_permission BOOLEAN;
BEGIN
    -- Get a real user and org for testing
    SELECT u.user_id, uo.org_id 
    INTO v_test_user_id, v_test_org_id
    FROM cmis.users u
    JOIN cmis.user_orgs uo ON uo.user_id = u.user_id
    WHERE uo.is_active = true
    AND uo.deleted_at IS NULL
    LIMIT 1;
    
    IF v_test_user_id IS NULL THEN
        RETURN QUERY SELECT 
            'Prerequisites'::TEXT,
            FALSE,
            'No active users found in database'::TEXT;
        RETURN;
    END IF;
    
    -- Test 1: Initialize context
    BEGIN
        PERFORM cmis.init_transaction_context(v_test_user_id, v_test_org_id);
        RETURN QUERY SELECT 
            'Context Initialization'::TEXT,
            TRUE,
            format('Successfully initialized for user %s', v_test_user_id)::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'Context Initialization'::TEXT,
            FALSE,
            SQLERRM::TEXT;
        RETURN;
    END;
    
    -- Test 2: Validate context
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    RETURN QUERY SELECT 
        'Context Validation'::TEXT,
        v_context.is_valid,
        CASE 
            WHEN v_context.is_valid THEN format('Valid context v%s', v_context.context_version)
            ELSE v_context.error_message
        END::TEXT;
    
    -- Test 3: Check permission
    BEGIN
        v_permission := cmis.check_permission_tx('orgs.view');
        RETURN QUERY SELECT 
            'Permission Check'::TEXT,
            TRUE,
            format('Permission check returned: %s', v_permission)::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'Permission Check'::TEXT,
            FALSE,
            SQLERRM::TEXT;
    END;
    
    -- Test 4: Query with RLS
    BEGIN
        PERFORM COUNT(*) FROM cmis.campaigns;
        RETURN QUERY SELECT 
            'RLS Query'::TEXT,
            TRUE,
            'Successfully queried campaigns table with RLS'::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'RLS Query'::TEXT,
            FALSE,
            SQLERRM::TEXT;
    END;
END;
$$;

COMMENT ON FUNCTION cmis.test_new_security_context IS 
'اختبار شامل للنظام الأمني الجديد';

RAISE NOTICE '✓ تم إنشاء دالة الاختبار: test_new_security_context';

RAISE NOTICE '✅ اكتملت المرحلة 5: أدوات الاختبار';

-- ============================================================================
-- 📝 FINAL SUMMARY AND NEXT STEPS
-- الملخص النهائي والخطوات القادمة
-- ============================================================================

DO $$
DECLARE
    v_backup_schema TEXT;
    v_summary JSONB;
BEGIN
    SELECT backup_schema INTO v_backup_schema FROM migration_context;
    
    SELECT jsonb_build_object(
        'migration_date', CURRENT_TIMESTAMP,
        'backup_schema', v_backup_schema,
        'new_functions_created', 5,
        'new_functions', ARRAY[
            'init_transaction_context',
            'validate_transaction_context',
            'check_permission_tx',
            'get_current_org_id_tx',
            'get_current_user_id_tx'
        ],
        'test_functions', ARRAY['test_new_security_context'],
        'audit_table', 'security_context_audit',
        'monitoring_view', 'v_security_context_summary',
        'old_functions_status', 'KEPT for backward compatibility',
        'users_org_id_status', 'KEPT for backward compatibility',
        'status', 'READY_FOR_TESTING'
    ) INTO v_summary;
    
    RAISE NOTICE '';
    RAISE NOTICE '================================================';
    RAISE NOTICE '🎉 اكتمل تنفيذ السكربت بنجاح!';
    RAISE NOTICE '================================================';
    RAISE NOTICE '';
    RAISE NOTICE '📋 الملخص:';
    RAISE NOTICE '%', jsonb_pretty(v_summary);
    RAISE NOTICE '';
    RAISE NOTICE '🧪 اختبار النظام الجديد:';
    RAISE NOTICE '   SELECT * FROM cmis.test_new_security_context();';
    RAISE NOTICE '';
    RAISE NOTICE '⚠️  ملاحظات هامة:';
    RAISE NOTICE '1. تم الاحتفاظ بالدوال القديمة (get_current_*) للتوافق';
    RAISE NOTICE '2. تم الاحتفاظ بعمود users.org_id مؤقتاً';
    RAISE NOTICE '3. السياسات القديمة لا تزال نشطة';
    RAISE NOTICE '4. تم إنشاء سياسات جديدة بلاحقة _v2';
    RAISE NOTICE '';
    RAISE NOTICE '📦 النسخة الاحتياطية:';
    RAISE NOTICE '   Schema: %', v_backup_schema;
    RAISE NOTICE '   - users_backup';
    RAISE NOTICE '   - user_orgs_backup';
    RAISE NOTICE '   - existing_functions';
    RAISE NOTICE '   - existing_policies';
    RAISE NOTICE '';
    RAISE NOTICE '🚀 الخطوات التالية:';
    RAISE NOTICE '1. تشغيل الاختبارات: SELECT * FROM cmis.test_new_security_context();';
    RAISE NOTICE '2. تحديث Backend لاستخدام init_transaction_context()';
    RAISE NOTICE '3. اختبار شامل في بيئة التطوير';
    RAISE NOTICE '4. بعد التأكد، تشغيل سكربت Phase 2 لتفعيل السياسات الجديدة';
    RAISE NOTICE '5. بعد نجاح كل شيء، تشغيل cleanup script';
    RAISE NOTICE '';
    RAISE NOTICE '================================================';
END $$;
