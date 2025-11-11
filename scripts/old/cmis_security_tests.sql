-- =============================================================================
-- CMIS Security Testing - اختبارات سريعة للتحقق من الإصلاحات
-- =============================================================================

-- ----------------------------------------------------------------------------
-- الاختبار #1: التحقق من حالة السياسات
-- ----------------------------------------------------------------------------

\echo '==================== الاختبار #1: حالة السياسات ===================='
SELECT * FROM cmis.verify_rbac_policies() ORDER BY status DESC, table_name;

-- ----------------------------------------------------------------------------
-- الاختبار #2: التحقق من وجود الدوال
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #2: الدوال الموجودة ===================='
SELECT 
    p.proname as function_name,
    pg_get_function_arguments(p.oid) as arguments,
    CASE 
        WHEN p.proname = 'check_permission' THEN '⚠️ قديمة (سيتم حذفها)'
        WHEN p.proname = 'check_permission_optimized' THEN '✅ محدثة'
        ELSE '✓ أخرى'
    END as status,
    obj_description(p.oid) as description
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'cmis' 
  AND p.proname LIKE '%check_permission%'
ORDER BY p.proname;

-- ----------------------------------------------------------------------------
-- الاختبار #3: فحص جداول الصلاحيات (هل تحتوي على بيانات؟)
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #3: جداول الصلاحيات ===================='
SELECT 
    'permissions' as table_name,
    COUNT(*) as total_records,
    COUNT(*) FILTER (WHERE deleted_at IS NULL) as active_records
FROM cmis.permissions

UNION ALL

SELECT 
    'roles' as table_name,
    COUNT(*) as total_records,
    COUNT(*) FILTER (WHERE deleted_at IS NULL) as active_records
FROM cmis.roles

UNION ALL

SELECT 
    'role_permissions' as table_name,
    COUNT(*) as total_records,
    COUNT(*) FILTER (WHERE deleted_at IS NULL) as active_records
FROM cmis.role_permissions

UNION ALL

SELECT 
    'user_orgs' as table_name,
    COUNT(*) as total_records,
    COUNT(*) FILTER (WHERE deleted_at IS NULL) as active_records
FROM cmis.user_orgs

UNION ALL

SELECT 
    'user_permissions' as table_name,
    COUNT(*) as total_records,
    COUNT(*) FILTER (WHERE deleted_at IS NULL) as active_records
FROM cmis.user_permissions;

-- ----------------------------------------------------------------------------
-- الاختبار #4: فحص أداء Cache
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #4: أداء Cache ===================='
SELECT 
    permission_code,
    hit_count,
    last_used,
    EXTRACT(EPOCH FROM (CURRENT_TIMESTAMP - last_used))::INT as seconds_since_last_use,
    CASE 
        WHEN hit_count > 100 THEN '🔥 كثير الاستخدام'
        WHEN hit_count > 10 THEN '✅ استخدام جيد'
        WHEN hit_count > 0 THEN '⚠️ استخدام قليل'
        ELSE '❌ غير مستخدم'
    END as usage_level
FROM cmis.permissions_cache
ORDER BY hit_count DESC
LIMIT 10;

-- ----------------------------------------------------------------------------
-- الاختبار #5: اختبار الدالة المحسنة (اختبار وظيفي)
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #5: اختبار وظيفي ===================='

-- اختبار مع صلاحية غير موجودة (يجب أن ترجع false)
\echo 'اختبار صلاحية غير موجودة:'
SELECT 
    'nonexistent.permission' as test_case,
    cmis.check_permission_optimized(
        gen_random_uuid(),  -- مستخدم عشوائي
        gen_random_uuid(),  -- منظمة عشوائية
        'nonexistent.permission'
    ) as result,
    CASE 
        WHEN cmis.check_permission_optimized(gen_random_uuid(), gen_random_uuid(), 'nonexistent.permission') = false 
        THEN '✅ نجح'
        ELSE '❌ فشل'
    END as status;

-- ----------------------------------------------------------------------------
-- الاختبار #6: فحص Triggers
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #6: Triggers المهمة ===================='
SELECT 
    tgname as trigger_name,
    tgrelid::regclass as table_name,
    CASE tgenabled
        WHEN 'O' THEN '✅ مفعّل'
        WHEN 'D' THEN '❌ معطّل'
        ELSE 'غير معروف'
    END as status
FROM pg_trigger
WHERE tgname IN (
    'trg_refresh_fields_cache',
    'trg_update_timestamps',
    'auto_refresh_cache_on_field_change'
)
ORDER BY tgname;

-- ----------------------------------------------------------------------------
-- الاختبار #7: RLS Status (هل Row Level Security مفعّل؟)
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #7: Row Level Security ===================='
SELECT 
    schemaname,
    tablename,
    rowsecurity as rls_enabled,
    CASE 
        WHEN rowsecurity THEN '✅ مفعّل'
        ELSE '❌ معطّل'
    END as status,
    (SELECT COUNT(*) 
     FROM pg_policy 
     WHERE polrelid = (schemaname || '.' || tablename)::regclass) as policy_count
FROM pg_tables
WHERE schemaname = 'cmis'
  AND tablename IN (
      'campaigns', 'creative_assets', 'ad_accounts', 
      'organizations', 'users', 'integrations'
  )
ORDER BY tablename;

-- ----------------------------------------------------------------------------
-- الاختبار #8: Sample Permission Check (إذا كانت لديك بيانات)
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== الاختبار #8: Sample Permission Check ===================='
\echo 'إذا كانت لديك بيانات في الجداول، يمكنك اختبار الصلاحية الفعلية:'

-- هذا الاستعلام سيعمل فقط إذا كان لديك بيانات
WITH sample_data AS (
    SELECT 
        (SELECT user_id FROM cmis.users WHERE deleted_at IS NULL LIMIT 1) as test_user,
        (SELECT org_id FROM cmis.organizations WHERE deleted_at IS NULL LIMIT 1) as test_org,
        (SELECT permission_code FROM cmis.permissions LIMIT 1) as test_perm
)
SELECT 
    test_user,
    test_org,
    test_perm,
    CASE 
        WHEN test_user IS NULL THEN '⚠️ لا يوجد مستخدمين'
        WHEN test_org IS NULL THEN '⚠️ لا يوجد منظمات'
        WHEN test_perm IS NULL THEN '⚠️ لا يوجد صلاحيات'
        ELSE cmis.check_permission_optimized(test_user, test_org, test_perm)::text
    END as permission_check_result
FROM sample_data;

-- ----------------------------------------------------------------------------
-- النتيجة النهائية
-- ----------------------------------------------------------------------------

\echo ''
\echo '==================== النتيجة النهائية ===================='
\echo 'إذا رأيت "✅ محدث" في جميع السياسات في الاختبار #1، فقد نجح الإصلاح!'
\echo ''
\echo 'الخطوات التالية:'
\echo '1. تأكد من وجود بيانات في جداول: permissions, roles, role_permissions'
\echo '2. قم بتعيين المستخدمين للأدوار في جدول user_orgs'
\echo '3. اختبر الوصول الفعلي من التطبيق'
\echo ''
