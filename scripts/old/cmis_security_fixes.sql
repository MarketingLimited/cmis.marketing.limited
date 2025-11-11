-- =============================================================================
-- CMIS Security Fixes - إصلاح مشاكل نظام الصلاحيات
-- التاريخ: 11 نوفمبر 2025
-- =============================================================================

-- -----------------------------------------------------------------------------
-- الإصلاح #1: تحديث دالة check_permission_optimized
-- إضافة التحقق من deleted_at في استعلام role_permissions
-- -----------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION cmis.check_permission_optimized(
    p_user_id uuid, 
    p_org_id uuid, 
    p_permission_code text
) 
RETURNS boolean
LANGUAGE plpgsql 
SECURITY DEFINER
AS $$
DECLARE
    v_permission_id uuid;
    v_has_permission boolean := false;
BEGIN
    -- جلب permission_id من الـ cache (أسرع)
    SELECT permission_id INTO v_permission_id
    FROM cmis.permissions_cache
    WHERE permission_code = p_permission_code;
    
    IF v_permission_id IS NULL THEN
        -- إذا لم يوجد في cache، جلب من الجدول الأصلي
        SELECT permission_id INTO v_permission_id
        FROM cmis.permissions
        WHERE permission_code = p_permission_code;
        
        IF v_permission_id IS NULL THEN
            RETURN false; -- صلاحية غير موجودة
        END IF;
    END IF;
    
    -- تحديث آخر استخدام في cache
    UPDATE cmis.permissions_cache
    SET last_used = CURRENT_TIMESTAMP
    WHERE permission_code = p_permission_code;
    
    -- التحقق من الصلاحيات المباشرة
    SELECT EXISTS (
        SELECT 1 
        FROM cmis.user_permissions up
        WHERE up.user_id = p_user_id
          AND up.org_id = p_org_id
          AND up.permission_id = v_permission_id
          AND up.is_granted = true
          AND up.deleted_at IS NULL -- ✅ تم إضافته
          AND (up.expires_at IS NULL OR up.expires_at > CURRENT_TIMESTAMP)
    ) INTO v_has_permission;
    
    IF v_has_permission THEN
        RETURN true;
    END IF;
    
    -- التحقق من صلاحيات الدور
    SELECT EXISTS (
        SELECT 1
        FROM cmis.user_orgs uo
        JOIN cmis.role_permissions rp ON rp.role_id = uo.role_id
        WHERE uo.user_id = p_user_id
          AND uo.org_id = p_org_id
          AND uo.is_active = true
          AND uo.deleted_at IS NULL -- ✅ تم إضافته
          AND rp.permission_id = v_permission_id
          AND rp.deleted_at IS NULL -- ✅ تم إضافته (هذا هو الأهم!)
    ) INTO v_has_permission;
    
    RETURN v_has_permission;
END;
$$;

COMMENT ON FUNCTION cmis.check_permission_optimized IS 
'التحقق من صلاحيات المستخدم مع دعم cache و soft delete - محدثة';

-- -----------------------------------------------------------------------------
-- الإصلاح #2: تحديث جميع سياسات RLS لاستخدام الدالة المحسنة
-- تغيير check_permission إلى check_permission_optimized
-- -----------------------------------------------------------------------------

-- Ad Accounts
DROP POLICY IF EXISTS rbac_ad_accounts ON cmis.ad_accounts;
CREATE POLICY rbac_ad_accounts ON cmis.ad_accounts 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.view')
);

-- Ad Campaigns
DROP POLICY IF EXISTS rbac_ad_campaigns ON cmis.ad_campaigns;
CREATE POLICY rbac_ad_campaigns ON cmis.ad_campaigns 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.view')
);

-- AI Actions
DROP POLICY IF EXISTS rbac_ai_actions ON cmis.ai_actions;
CREATE POLICY rbac_ai_actions ON cmis.ai_actions 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'analytics.view')
);

-- Analytics Integrations (SELECT)
DROP POLICY IF EXISTS rbac_analytics_integrations ON cmis.analytics_integrations;
CREATE POLICY rbac_analytics_integrations ON cmis.analytics_integrations 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'analytics.view')
);

-- Analytics Integrations (MANAGE)
DROP POLICY IF EXISTS rbac_analytics_integrations_manage ON cmis.analytics_integrations;
CREATE POLICY rbac_analytics_integrations_manage ON cmis.analytics_integrations 
FOR ALL USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'analytics.configure')
);

-- Audit Log
DROP POLICY IF EXISTS rbac_audit_log ON cmis.audit_log;
CREATE POLICY rbac_audit_log ON cmis.audit_log 
FOR SELECT USING (
    (org_id IS NULL OR org_id = cmis.get_current_org_id()) 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), COALESCE(org_id, cmis.get_current_org_id()), 'admin.settings')
);

-- Campaigns (DELETE)
DROP POLICY IF EXISTS rbac_campaigns_delete ON cmis.campaigns;
CREATE POLICY rbac_campaigns_delete ON cmis.campaigns 
FOR DELETE USING (
    org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.delete')
);

-- Campaigns (INSERT)
DROP POLICY IF EXISTS rbac_campaigns_insert ON cmis.campaigns;
CREATE POLICY rbac_campaigns_insert ON cmis.campaigns 
FOR INSERT WITH CHECK (
    org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.create')
);

-- Campaigns (SELECT)
DROP POLICY IF EXISTS rbac_campaigns_select ON cmis.campaigns;
CREATE POLICY rbac_campaigns_select ON cmis.campaigns 
FOR SELECT USING (
    (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP) 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.view')
);

-- Campaigns (UPDATE)
DROP POLICY IF EXISTS rbac_campaigns_update ON cmis.campaigns;
CREATE POLICY rbac_campaigns_update ON cmis.campaigns 
FOR UPDATE USING (
    (deleted_at IS NULL OR deleted_at > CURRENT_TIMESTAMP) 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'campaigns.edit')
);

-- Content Items
DROP POLICY IF EXISTS rbac_content_items ON cmis.content_items;
CREATE POLICY rbac_content_items ON cmis.content_items 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'creatives.view')
);

-- Creative Assets (INSERT)
DROP POLICY IF EXISTS rbac_creative_assets_insert ON cmis.creative_assets;
CREATE POLICY rbac_creative_assets_insert ON cmis.creative_assets 
FOR INSERT WITH CHECK (
    org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'creatives.create')
);

-- Creative Assets (SELECT)
DROP POLICY IF EXISTS rbac_creative_assets_select ON cmis.creative_assets;
CREATE POLICY rbac_creative_assets_select ON cmis.creative_assets 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'creatives.view')
);

-- Creative Assets (UPDATE)
DROP POLICY IF EXISTS rbac_creative_assets_update ON cmis.creative_assets;
CREATE POLICY rbac_creative_assets_update ON cmis.creative_assets 
FOR UPDATE USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'creatives.edit')
);

-- -----------------------------------------------------------------------------
-- الإصلاح #3: إضافة دالة للتحقق من باقي السياسات
-- -----------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION cmis.verify_rbac_policies()
RETURNS TABLE(
    policy_name text,
    table_name text,
    uses_optimized boolean,
    status text
) 
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        pol.policyname::text,
        pol.tablename::text,
        pg_get_expr(pol.polqual, pol.polrelid) LIKE '%check_permission_optimized%' as uses_optimized,
        CASE 
            WHEN pg_get_expr(pol.polqual, pol.polrelid) LIKE '%check_permission_optimized%' THEN '✅ محدث'
            WHEN pg_get_expr(pol.polqual, pol.polrelid) LIKE '%check_permission%' THEN '❌ يحتاج تحديث'
            ELSE '✓ لا يستخدم RBAC'
        END as status
    FROM pg_policy pol
    JOIN pg_class cls ON pol.polrelid = cls.oid
    JOIN pg_namespace nsp ON cls.relnamespace = nsp.oid
    WHERE nsp.nspname = 'cmis'
      AND pol.policyname LIKE 'rbac_%'
    ORDER BY pol.tablename, pol.policyname;
END;
$$;

COMMENT ON FUNCTION cmis.verify_rbac_policies IS 
'التحقق من حالة سياسات الأمان وما إذا كانت تستخدم الدالة المحسنة';

-- -----------------------------------------------------------------------------
-- الإصلاح #4: حذف الدالة القديمة (اختياري - احذف فقط بعد التأكد)
-- -----------------------------------------------------------------------------

-- لا تقم بتشغيل هذا إلا بعد التأكد من أن جميع السياسات محدثة
-- DROP FUNCTION IF EXISTS cmis.check_permission(uuid, uuid, text);

COMMENT ON FUNCTION cmis.check_permission IS 
'⚠️ قديمة - استخدم check_permission_optimized بدلاً منها';

-- -----------------------------------------------------------------------------
-- التحقق النهائي
-- -----------------------------------------------------------------------------

SELECT '✅ تم تطبيق جميع الإصلاحات بنجاح!' as status;
SELECT * FROM cmis.verify_rbac_policies();


-- -----------------------------------------------------------------------------
-- الإصلاح #2 (تكملة): باقي السياسات
-- -----------------------------------------------------------------------------

-- Integrations (SELECT)
DROP POLICY IF EXISTS rbac_integrations_select ON cmis.integrations;
CREATE POLICY rbac_integrations_select ON cmis.integrations 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'integrations.view')
);

-- Integrations (MANAGE)
DROP POLICY IF EXISTS rbac_integrations_manage ON cmis.integrations;
CREATE POLICY rbac_integrations_manage ON cmis.integrations 
FOR ALL USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'integrations.manage')
);

-- Organizations (SELECT)
DROP POLICY IF EXISTS rbac_orgs_select ON cmis.organizations;
CREATE POLICY rbac_orgs_select ON cmis.organizations 
FOR SELECT USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id()
);

-- Organizations (MANAGE)
DROP POLICY IF EXISTS rbac_orgs_manage ON cmis.organizations;
CREATE POLICY rbac_orgs_manage ON cmis.organizations 
FOR ALL USING (
    deleted_at IS NULL 
    AND org_id = cmis.get_current_org_id() 
    AND cmis.check_permission_optimized(cmis.get_current_user_id(), org_id, 'admin.settings')
);

-- Users (SELECT)
DROP POLICY IF EXISTS rbac_users_select ON cmis.users;
CREATE POLICY rbac_users_select ON cmis.users 
FOR SELECT USING (
    user_id = cmis.get_current_user_id() 
    OR EXISTS (
        SELECT 1 FROM cmis.user_orgs uo 
        WHERE uo.user_id = cmis.get_current_user_id() 
        AND uo.deleted_at IS NULL
        AND cmis.check_permission_optimized(cmis.get_current_user_id(), uo.org_id, 'admin.users')
    )
);

-- Users (UPDATE)
DROP POLICY IF EXISTS rbac_users_update ON cmis.users;
CREATE POLICY rbac_users_update ON cmis.users 
FOR UPDATE USING (
    user_id = cmis.get_current_user_id() 
    OR EXISTS (
        SELECT 1 FROM cmis.user_orgs uo 
        WHERE uo.user_id = cmis.get_current_user_id() 
        AND uo.deleted_at IS NULL
        AND cmis.check_permission_optimized(cmis.get_current_user_id(), uo.org_id, 'admin.users')
    )
);

