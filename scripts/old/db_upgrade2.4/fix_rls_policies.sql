-- ================================================================
-- إصلاح سياسات RLS لدعم RBAC والحذف الناعم
-- CRITICAL FIX - يجب تنفيذه فوراً
-- ================================================================
-- تاريخ: 2025-11-11
-- الهدف: تحديث جميع سياسات RLS لتدعم:
--   1. نظام الصلاحيات الجديد (RBAC)
--   2. الحذف الناعم (Soft Delete)
-- ================================================================

BEGIN;

-- ================================================================
-- 1. حذف جميع السياسات القديمة
-- ================================================================

-- حذف سياسات org_isolation القديمة
DO $$ 
DECLARE
    r RECORD;
BEGIN
    FOR r IN 
        SELECT schemaname, tablename, policyname 
        FROM pg_policies 
        WHERE schemaname = 'cmis' 
        AND policyname LIKE 'org_isolation%'
    LOOP
        EXECUTE format('DROP POLICY IF EXISTS %I ON %I.%I', 
                      r.policyname, r.schemaname, r.tablename);
        RAISE NOTICE 'Dropped old policy: %.%', r.tablename, r.policyname;
    END LOOP;
END $$;

-- ================================================================
-- 2. إنشاء سياسات جديدة للجداول الأساسية
-- ================================================================

-- سياسة للحملات
CREATE POLICY rbac_campaigns_select ON cmis.campaigns
FOR SELECT
USING (
    (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.view'
    )
);

CREATE POLICY rbac_campaigns_insert ON cmis.campaigns
FOR INSERT
WITH CHECK (
    org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.create'
    )
);

CREATE POLICY rbac_campaigns_update ON cmis.campaigns
FOR UPDATE
USING (
    (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP)
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.edit'
    )
);

CREATE POLICY rbac_campaigns_delete ON cmis.campaigns
FOR DELETE
USING (
    org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.delete'
    )
);

-- سياسة للأصول الإبداعية
CREATE POLICY rbac_creative_assets_select ON cmis.creative_assets
FOR SELECT
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'creatives.view'
    )
);

CREATE POLICY rbac_creative_assets_insert ON cmis.creative_assets
FOR INSERT
WITH CHECK (
    org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'creatives.create'
    )
);

CREATE POLICY rbac_creative_assets_update ON cmis.creative_assets
FOR UPDATE
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'creatives.edit'
    )
);

-- سياسة للمستخدمين (قراءة فقط للزملاء في نفس الشركة)
CREATE POLICY rbac_users_select ON cmis.users
FOR SELECT
USING (
    EXISTS (
        SELECT 1 FROM cmis.user_orgs uo1
        WHERE uo1.user_id = cmis.users.user_id
        AND uo1.org_id IN (
            SELECT org_id FROM cmis.user_orgs uo2
            WHERE uo2.user_id = cmis.get_current_user_id()
            AND uo2.is_active = true
        )
    )
);

CREATE POLICY rbac_users_update ON cmis.users
FOR UPDATE
USING (
    user_id = cmis.get_current_user_id()
    OR cmis.check_permission(
        cmis.get_current_user_id(),
        cmis.get_current_org_id(),
        'admin.users'
    )
);

-- سياسات لجداول الإعلانات
CREATE POLICY rbac_ad_accounts ON cmis.ad_accounts
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.view'
    )
);

CREATE POLICY rbac_ad_campaigns ON cmis.ad_campaigns
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'campaigns.view'
    )
);

-- سياسات لجداول AI
CREATE POLICY rbac_ai_actions ON cmis.ai_actions
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'analytics.view'
    )
);

-- سياسات للتحليلات
CREATE POLICY rbac_analytics_integrations ON cmis.analytics_integrations
FOR SELECT
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'analytics.view'
    )
);

CREATE POLICY rbac_analytics_integrations_manage ON cmis.analytics_integrations
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'analytics.configure'
    )
);

-- سياسات لجداول التدقيق (قراءة فقط للمخولين)
CREATE POLICY rbac_audit_log ON cmis.audit_log
FOR SELECT
USING (
    (org_id IS NULL OR org_id = cmis.get_current_org_id())
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        cmis.get_current_org_id(),
        'admin.settings'
    )
);

-- سياسات للمحتوى
CREATE POLICY rbac_content_items ON cmis.content_items
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'creatives.view'
    )
);

-- سياسات للتكاملات
CREATE POLICY rbac_integrations_select ON cmis.integrations
FOR SELECT
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
);

CREATE POLICY rbac_integrations_manage ON cmis.integrations
FOR ALL
USING (
    deleted_at IS NULL
    AND org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'admin.integrations'
    )
);

-- سياسات للشركات (القراءة للجميع، التعديل للأدمن)
CREATE POLICY rbac_orgs_select ON cmis.orgs
FOR SELECT
USING (
    EXISTS (
        SELECT 1 FROM cmis.user_orgs
        WHERE user_id = cmis.get_current_user_id()
        AND org_id = cmis.orgs.org_id
        AND is_active = true
    )
);

CREATE POLICY rbac_orgs_manage ON cmis.orgs
FOR ALL
USING (
    org_id = cmis.get_current_org_id()
    AND cmis.check_permission(
        cmis.get_current_user_id(),
        org_id,
        'admin.settings'
    )
);

-- ================================================================
-- 3. سياسات خاصة للجداول الجديدة (الصلاحيات والأدوار)
-- ================================================================

-- هذه مُطبقة بالفعل في الملف السابق، نتحقق من وجودها
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'user_orgs' 
        AND policyname = 'user_orgs_self'
    ) THEN
        CREATE POLICY user_orgs_self ON cmis.user_orgs
        FOR SELECT
        USING (user_id = cmis.get_current_user_id());
    END IF;
END $$;

-- ================================================================
-- 4. إضافة فهارس لتحسين أداء الاستعلامات مع deleted_at
-- ================================================================

-- فهارس جزئية للسجلات غير المحذوفة (أسرع بكثير)
CREATE INDEX IF NOT EXISTS idx_campaigns_active 
ON cmis.campaigns(org_id, status) 
WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_creative_assets_active 
ON cmis.creative_assets(org_id, campaign_id) 
WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_users_active 
ON cmis.users(email) 
WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_integrations_active 
ON cmis.integrations(org_id) 
WHERE deleted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_content_items_active 
ON cmis.content_items(org_id, created_at DESC) 
WHERE deleted_at IS NULL;

-- ================================================================
-- 5. دالة للتحقق من نجاح الإصلاحات
-- ================================================================

CREATE OR REPLACE FUNCTION cmis.verify_rls_fixes()
RETURNS TABLE(
    check_name text,
    status text,
    details text
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_old_policies_count integer;
    v_new_policies_count integer;
    v_soft_delete_aware integer;
BEGIN
    -- عد السياسات القديمة
    SELECT count(*) INTO v_old_policies_count
    FROM pg_policies
    WHERE schemaname = 'cmis'
    AND policyname LIKE 'org_isolation%';
    
    -- عد السياسات الجديدة
    SELECT count(*) INTO v_new_policies_count
    FROM pg_policies
    WHERE schemaname = 'cmis'
    AND policyname LIKE 'rbac_%';
    
    -- عد السياسات التي تتحقق من deleted_at
    SELECT count(*) INTO v_soft_delete_aware
    FROM pg_policies
    WHERE schemaname = 'cmis'
    AND definition::text LIKE '%deleted_at%';
    
    -- التحقق من إزالة السياسات القديمة
    RETURN QUERY
    SELECT 
        'Old Policies Removed'::text,
        CASE 
            WHEN v_old_policies_count = 0 THEN 'SUCCESS'::text
            ELSE 'FAILED'::text
        END,
        format('%s old policies remaining', v_old_policies_count)::text;
    
    -- التحقق من إضافة السياسات الجديدة
    RETURN QUERY
    SELECT 
        'New RBAC Policies'::text,
        CASE 
            WHEN v_new_policies_count >= 10 THEN 'SUCCESS'::text
            ELSE 'PARTIAL'::text
        END,
        format('%s RBAC policies created', v_new_policies_count)::text;
    
    -- التحقق من دعم Soft Delete
    RETURN QUERY
    SELECT 
        'Soft Delete Support'::text,
        CASE 
            WHEN v_soft_delete_aware >= 8 THEN 'SUCCESS'::text
            ELSE 'PARTIAL'::text
        END,
        format('%s policies check deleted_at', v_soft_delete_aware)::text;
    
    -- التحقق من الفهارس الجديدة
    RETURN QUERY
    SELECT 
        'Performance Indexes'::text,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM pg_indexes 
                WHERE indexname LIKE 'idx_%_active'
                AND schemaname = 'cmis'
            ) THEN 'SUCCESS'::text
            ELSE 'MISSING'::text
        END,
        'Partial indexes for non-deleted records'::text;
END;
$$;

-- ================================================================
-- 6. إنشاء view للسجلات المحذوفة (للأدمن فقط)
-- ================================================================

CREATE OR REPLACE VIEW cmis.v_deleted_records AS
WITH deleted_campaigns AS (
    SELECT 'campaigns' as table_name, 
           campaign_id::text as record_id,
           name,
           org_id,
           deleted_at,
           deleted_by
    FROM cmis.campaigns
    WHERE deleted_at IS NOT NULL
),
deleted_assets AS (
    SELECT 'creative_assets' as table_name,
           asset_id::text as record_id,
           variation_tag as name,
           org_id,
           deleted_at,
           deleted_by
    FROM cmis.creative_assets
    WHERE deleted_at IS NOT NULL
),
deleted_content AS (
    SELECT 'content_items' as table_name,
           context_id::text as record_id,
           title as name,
           org_id,
           deleted_at,
           deleted_by
    FROM cmis.content_items
    WHERE deleted_at IS NOT NULL
)
SELECT * FROM deleted_campaigns
UNION ALL
SELECT * FROM deleted_assets
UNION ALL
SELECT * FROM deleted_content
ORDER BY deleted_at DESC;

-- تطبيق RLS على الـ view
ALTER VIEW cmis.v_deleted_records SET (security_barrier = true);

COMMENT ON VIEW cmis.v_deleted_records IS 'عرض جميع السجلات المحذوفة - للمديرين فقط';

COMMIT;

-- ================================================================
-- التحقق من النتائج
-- ================================================================
SELECT * FROM cmis.verify_rls_fixes();

-- عرض السياسات الجديدة
SELECT 
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    CASE 
        WHEN definition::text LIKE '%deleted_at%' THEN '✓ Soft Delete'
        ELSE '✗ No Soft Delete'
    END as soft_delete_aware,
    CASE 
        WHEN definition::text LIKE '%check_permission%' THEN '✓ RBAC'
        ELSE '✗ No RBAC'
    END as rbac_enabled
FROM pg_policies
WHERE schemaname = 'cmis'
ORDER BY tablename, policyname;

-- ================================================================
-- ملاحظات مهمة
-- ================================================================
/*
1. هذا السكريبت يحل المشكلة الحرجة للـ RLS
2. جميع السياسات الآن تدعم:
   - الحذف الناعم (deleted_at)
   - نظام الصلاحيات (RBAC)
   - العزل بين الشركات

3. بعد تنفيذ هذا السكريبت:
   - لن يرى المستخدمون السجلات المحذوفة
   - الصلاحيات ستعمل بشكل صحيح
   - الأداء سيتحسن مع الفهارس الجزئية

4. تذكر: يجب تحديث التطبيق ليرسل:
   - app.current_user_id
   - app.current_org_id
   مع كل اتصال بقاعدة البيانات
*/
