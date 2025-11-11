-- ================================================================
-- CMIS Database Fix Script - Phase 2: Advanced Permissions System
-- المرحلة الثانية: نظام الصلاحيات المتقدم
-- ================================================================
-- تاريخ: 2025-11-11
-- الهدف: تطبيق نظام صلاحيات متقدم يدعم تعدد المستخدمين والشركات
-- ================================================================

BEGIN;

-- ================================================================
-- 1. إنشاء جداول نظام الصلاحيات المتقدم
-- ================================================================

-- جدول العلاقة بين المستخدمين والشركات (many-to-many)
CREATE TABLE IF NOT EXISTS cmis.user_orgs (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id uuid NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
    org_id uuid NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
    role_id uuid NOT NULL, -- سيتم ربطه بجدول الأدوار
    is_active boolean DEFAULT true,
    joined_at timestamptz DEFAULT CURRENT_TIMESTAMP,
    invited_by uuid REFERENCES cmis.users(user_id),
    last_accessed timestamptz,
    UNIQUE(user_id, org_id)
);

-- جدول الأدوار
CREATE TABLE IF NOT EXISTS cmis.roles (
    role_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    org_id uuid REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
    role_name text NOT NULL,
    role_code text NOT NULL,
    description text,
    is_system boolean DEFAULT false, -- أدوار النظام الافتراضية
    is_active boolean DEFAULT true,
    created_at timestamptz DEFAULT CURRENT_TIMESTAMP,
    created_by uuid REFERENCES cmis.users(user_id),
    UNIQUE(org_id, role_code)
);

-- جدول الصلاحيات
CREATE TABLE IF NOT EXISTS cmis.permissions (
    permission_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    permission_code text UNIQUE NOT NULL,
    permission_name text NOT NULL,
    category text NOT NULL, -- campaigns, creatives, analytics, admin, etc.
    description text,
    is_dangerous boolean DEFAULT false -- صلاحيات حساسة
);

-- جدول ربط الأدوار بالصلاحيات
CREATE TABLE IF NOT EXISTS cmis.role_permissions (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    role_id uuid NOT NULL REFERENCES cmis.roles(role_id) ON DELETE CASCADE,
    permission_id uuid NOT NULL REFERENCES cmis.permissions(permission_id) ON DELETE CASCADE,
    granted_at timestamptz DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid REFERENCES cmis.users(user_id),
    UNIQUE(role_id, permission_id)
);

-- جدول الصلاحيات المباشرة للمستخدمين (تجاوز الأدوار)
CREATE TABLE IF NOT EXISTS cmis.user_permissions (
    id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id uuid NOT NULL REFERENCES cmis.users(user_id) ON DELETE CASCADE,
    org_id uuid NOT NULL REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
    permission_id uuid NOT NULL REFERENCES cmis.permissions(permission_id) ON DELETE CASCADE,
    is_granted boolean DEFAULT true, -- true للمنح، false للمنع
    granted_at timestamptz DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid REFERENCES cmis.users(user_id),
    expires_at timestamptz, -- صلاحيات مؤقتة
    UNIQUE(user_id, org_id, permission_id)
);

-- جدول سجل نشاطات المستخدمين
CREATE TABLE IF NOT EXISTS cmis.user_activities (
    activity_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id uuid NOT NULL REFERENCES cmis.users(user_id),
    org_id uuid NOT NULL REFERENCES cmis.orgs(org_id),
    session_id uuid REFERENCES cmis.user_sessions(session_id),
    action text NOT NULL,
    entity_type text,
    entity_id uuid,
    details jsonb,
    ip_address inet,
    created_at timestamptz DEFAULT CURRENT_TIMESTAMP
);

-- إضافة FK للأدوار في user_orgs
ALTER TABLE cmis.user_orgs 
ADD CONSTRAINT fk_user_orgs_role 
FOREIGN KEY (role_id) REFERENCES cmis.roles(role_id);

-- ================================================================
-- 2. ترحيل البيانات الموجودة
-- ================================================================

-- إنشاء الأدوار الافتراضية لكل شركة
INSERT INTO cmis.roles (org_id, role_name, role_code, description, is_system)
SELECT DISTINCT 
    org_id,
    'مدير النظام',
    'admin',
    'صلاحيات كاملة على النظام',
    true
FROM cmis.orgs
ON CONFLICT (org_id, role_code) DO NOTHING;

INSERT INTO cmis.roles (org_id, role_name, role_code, description, is_system)
SELECT DISTINCT 
    org_id,
    'محرر',
    'editor',
    'إنشاء وتعديل المحتوى',
    true
FROM cmis.orgs
ON CONFLICT (org_id, role_code) DO NOTHING;

INSERT INTO cmis.roles (org_id, role_name, role_code, description, is_system)
SELECT DISTINCT 
    org_id,
    'مشاهد',
    'viewer',
    'عرض المحتوى فقط',
    true
FROM cmis.orgs
ON CONFLICT (org_id, role_code) DO NOTHING;

-- ترحيل المستخدمين الحاليين إلى النظام الجديد
INSERT INTO cmis.user_orgs (user_id, org_id, role_id)
SELECT 
    u.user_id,
    u.org_id,
    r.role_id
FROM cmis.users u
JOIN cmis.roles r ON r.org_id = u.org_id AND r.role_code = u.role
ON CONFLICT (user_id, org_id) DO NOTHING;

-- ================================================================
-- 3. إضافة الصلاحيات الأساسية
-- ================================================================

-- صلاحيات الحملات
INSERT INTO cmis.permissions (permission_code, permission_name, category, description, is_dangerous) VALUES
('campaigns.view', 'عرض الحملات', 'campaigns', 'عرض قائمة الحملات وتفاصيلها', false),
('campaigns.create', 'إنشاء حملات', 'campaigns', 'إنشاء حملات جديدة', false),
('campaigns.edit', 'تعديل حملات', 'campaigns', 'تعديل الحملات الموجودة', false),
('campaigns.delete', 'حذف حملات', 'campaigns', 'حذف الحملات', true),
('campaigns.publish', 'نشر حملات', 'campaigns', 'نشر الحملات للجمهور', false);

-- صلاحيات المحتوى الإبداعي
INSERT INTO cmis.permissions (permission_code, permission_name, category, description, is_dangerous) VALUES
('creatives.view', 'عرض المحتوى الإبداعي', 'creatives', 'عرض المحتوى الإبداعي', false),
('creatives.create', 'إنشاء محتوى إبداعي', 'creatives', 'إنشاء محتوى إبداعي جديد', false),
('creatives.edit', 'تعديل محتوى إبداعي', 'creatives', 'تعديل المحتوى الإبداعي', false),
('creatives.delete', 'حذف محتوى إبداعي', 'creatives', 'حذف المحتوى الإبداعي', true),
('creatives.approve', 'اعتماد محتوى إبداعي', 'creatives', 'اعتماد المحتوى للنشر', false);

-- صلاحيات التحليلات
INSERT INTO cmis.permissions (permission_code, permission_name, category, description, is_dangerous) VALUES
('analytics.view', 'عرض التحليلات', 'analytics', 'عرض التقارير والتحليلات', false),
('analytics.export', 'تصدير التحليلات', 'analytics', 'تصدير التقارير', false),
('analytics.configure', 'إعداد التحليلات', 'analytics', 'تغيير إعدادات التحليلات', false);

-- صلاحيات الإدارة
INSERT INTO cmis.permissions (permission_code, permission_name, category, description, is_dangerous) VALUES
('admin.users', 'إدارة المستخدمين', 'admin', 'إضافة وتعديل المستخدمين', true),
('admin.roles', 'إدارة الأدوار', 'admin', 'إنشاء وتعديل الأدوار', true),
('admin.settings', 'إدارة الإعدادات', 'admin', 'تغيير إعدادات النظام', true),
('admin.integrations', 'إدارة التكاملات', 'admin', 'إدارة التكاملات الخارجية', true);

-- ================================================================
-- 4. ربط الصلاحيات بالأدوار الافتراضية
-- ================================================================

-- Admin = كل الصلاحيات
INSERT INTO cmis.role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM cmis.roles r
CROSS JOIN cmis.permissions p
WHERE r.role_code = 'admin'
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- Editor = كل شيء عدا الإدارة والحذف
INSERT INTO cmis.role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM cmis.roles r
CROSS JOIN cmis.permissions p
WHERE r.role_code = 'editor'
  AND p.permission_code NOT LIKE 'admin.%'
  AND p.permission_code NOT LIKE '%.delete'
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- Viewer = عرض فقط
INSERT INTO cmis.role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM cmis.roles r
CROSS JOIN cmis.permissions p
WHERE r.role_code = 'viewer'
  AND p.permission_code LIKE '%.view'
ON CONFLICT (role_id, permission_id) DO NOTHING;

-- ================================================================
-- 5. دوال مساعدة للتحقق من الصلاحيات
-- ================================================================

-- دالة للتحقق من صلاحية المستخدم
CREATE OR REPLACE FUNCTION cmis.check_permission(
    p_user_id uuid,
    p_org_id uuid,
    p_permission_code text
) RETURNS boolean
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_has_permission boolean := false;
BEGIN
    -- التحقق من الصلاحيات المباشرة أولاً
    SELECT EXISTS (
        SELECT 1 
        FROM cmis.user_permissions up
        JOIN cmis.permissions p ON p.permission_id = up.permission_id
        WHERE up.user_id = p_user_id
          AND up.org_id = p_org_id
          AND p.permission_code = p_permission_code
          AND up.is_granted = true
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
        JOIN cmis.permissions p ON p.permission_id = rp.permission_id
        WHERE uo.user_id = p_user_id
          AND uo.org_id = p_org_id
          AND uo.is_active = true
          AND p.permission_code = p_permission_code
    ) INTO v_has_permission;
    
    -- التحقق من المنع المباشر (override)
    IF v_has_permission THEN
        SELECT NOT EXISTS (
            SELECT 1 
            FROM cmis.user_permissions up
            JOIN cmis.permissions p ON p.permission_id = up.permission_id
            WHERE up.user_id = p_user_id
              AND up.org_id = p_org_id
              AND p.permission_code = p_permission_code
              AND up.is_granted = false
        ) INTO v_has_permission;
    END IF;
    
    RETURN v_has_permission;
END;
$$;

-- ================================================================
-- 11. دالة للتحقق من نجاح المرحلة 2
-- ================================================================

CREATE OR REPLACE FUNCTION cmis.verify_phase2_permissions()
RETURNS TABLE(
    check_name text,
    status text,
    count bigint,
    details text
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- جدول user_orgs
    RETURN QUERY
    SELECT 
        'User-Org Relationships'::text,
        'CREATED'::text,
        count(*),
        'Multi-org support enabled'::text
    FROM cmis.user_orgs;
    
    -- الأدوار
    RETURN QUERY
    SELECT 
        'Roles Created'::text,
        'READY'::text,
        count(*),
        'Role-based access control'::text
    FROM cmis.roles;
    
    -- الصلاحيات
    RETURN QUERY
    SELECT 
        'Permissions Defined'::text,
        'CONFIGURED'::text,
        count(*),
        'Granular permissions'::text
    FROM cmis.permissions;
    
    -- ربط الأدوار بالصلاحيات
    RETURN QUERY
    SELECT 
        'Role Permissions'::text,
        'ASSIGNED'::text,
        count(*),
        'Permissions assigned to roles'::text
    FROM cmis.role_permissions;
    
    -- السياسات المحدثة
    RETURN QUERY
    SELECT 
        'RLS Policies'::text,
        CASE 
            WHEN count(*) > 0 THEN 'UPDATED'::text
            ELSE 'PENDING'::text
        END,
        count(*),
        'Row-level security policies'::text
    FROM pg_policies
    WHERE schemaname = 'cmis'
      AND policyname LIKE '%_orgs_%' OR policyname LIKE '%role%';
END;
$$;

COMMIT;
