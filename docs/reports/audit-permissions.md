# 🔐 نظام الصلاحيات لنظام التدقيق

هذا الدليل يشرح نظام الصلاحيات الكامل لنظام التدقيق CMIS Audit System.

---

## 📋 جدول الصلاحيات

| الصلاحية | الوصف | Admin | Manager | Editor | Viewer |
|----------|-------|-------|---------|--------|--------|
| `audit.view_dashboard` | عرض لوحة التحكم والنظرة الشاملة | ✅ | ✅ | ✅ | ✅ |
| `audit.view_realtime` | عرض الحالة اللحظية | ✅ | ✅ | ✅ | ✅ |
| `audit.view_reports` | عرض التقارير (يومي، أسبوعي، ملخص) | ✅ | ✅ | ❌ | ❌ |
| `audit.view_activity_log` | عرض سجل الأنشطة | ✅ | ✅ | ❌ | ❌ |
| `audit.log_event` | تسجيل الأحداث | ✅ | ✅ | ✅ | ❌ |
| `audit.view_alerts` | عرض التنبيهات والتحذيرات | ✅ | ✅ | ❌ | ❌ |
| `audit.export_reports` | تصدير التقارير إلى CSV | ✅ | ✅ | ❌ | ❌ |
| `audit.view_all` | عرض جميع بيانات المؤسسة | ✅ | ❌ | ❌ | ❌ |
| `audit.view_security_logs` | عرض سجلات الأمان | ✅ | ❌ | ❌ | ❌ |
| `audit.manage_settings` | إدارة إعدادات التدقيق | ✅ | ❌ | ❌ | ❌ |

---

## 🎭 الأدوار (Roles)

### 1. Admin & Owner (مدير النظام)
**الصلاحيات:** كامل الوصول ✅

```php
[
    'audit.view_dashboard',
    'audit.view_realtime',
    'audit.view_reports',
    'audit.view_activity_log',
    'audit.log_event',
    'audit.view_alerts',
    'audit.export_reports',
    'audit.view_all',
    'audit.view_security_logs',
    'audit.manage_settings'
]
```

**الاستخدامات:**
- مراقبة كاملة للنظام
- الوصول لجميع السجلات الأمنية
- تصدير التقارير
- إدارة إعدادات التدقيق

---

### 2. Manager (مدير)
**الصلاحيات:** وصول إداري (بدون الأمان والإعدادات)

```php
[
    'audit.view_dashboard',
    'audit.view_realtime',
    'audit.view_reports',
    'audit.view_activity_log',
    'audit.log_event',
    'audit.view_alerts',
    'audit.export_reports'
]
```

**الاستخدامات:**
- مراقبة الأداء
- تسجيل الأحداث
- تصدير التقارير
- **لا يمكن:** الوصول للسجلات الأمنية أو تغيير الإعدادات

---

### 3. Editor (محرر)
**الصلاحيات:** عرض محدود + تسجيل الأحداث

```php
[
    'audit.view_dashboard',
    'audit.view_realtime',
    'audit.log_event'
]
```

**الاستخدامات:**
- عرض الحالة العامة
- تسجيل الأحداث الخاصة بعمله
- **لا يمكن:** عرض التقارير التفصيلية أو التصدير

---

### 4. Viewer (مشاهد)
**الصلاحيات:** عرض محدود فقط

```php
[
    'audit.view_dashboard',
    'audit.view_realtime'
]
```

**الاستخدامات:**
- عرض الحالة العامة فقط
- **لا يمكن:** تسجيل الأحداث أو عرض التفاصيل

---

## 🔧 التطبيق

### 1. في Database Migration

تم إضافة جميع الصلاحيات تلقائياً عند تشغيل:

```bash
php artisan migrate --path=database/migrations/2025_11_15_000002_add_audit_permissions.php
```

### 2. في Policy Class

```php
// app/Policies/AuditPolicy.php

class AuditPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $this->hasPermission($user, 'audit.view_dashboard');
    }

    public function viewSecurityLogs(User $user): bool
    {
        // Only admins and owners
        return in_array($user->role, ['admin', 'owner']) ||
               $this->hasPermission($user, 'audit.view_security_logs');
    }

    // ... المزيد
}
```

### 3. في API Controller

كل endpoint محمي بالصلاحيات:

```php
public function dashboard(Request $request): JsonResponse
{
    $policy = new AuditPolicy();

    if (!$policy->viewDashboard($request->user())) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // ... Logic here
}
```

---

## 📝 أمثلة الاستخدام

### مثال 1: فحص صلاحية من الكود

```php
use App\Policies\AuditPolicy;

$policy = new AuditPolicy();

if ($policy->viewDashboard($user)) {
    // المستخدم لديه صلاحية
    $dashboard = $this->getAuditDashboard();
}
```

### مثال 2: استجابة API عند عدم وجود صلاحية

```http
GET /api/orgs/123/audit/dashboard
Authorization: Bearer {token}
```

**Response (403 Forbidden):**
```json
{
  "success": false,
  "message": "Unauthorized: You do not have permission to view audit dashboard"
}
```

### مثال 3: تحديد الصلاحية حسب الفئة

```php
// فحص إذا كان المستخدم يمكنه رؤية سجلات الأمان
if ($policy->viewCategoryLogs($user, 'security')) {
    // يمكنه الوصول
} else {
    // لا يمكنه الوصول
}
```

---

## 🔐 قواعد الأمان

### 1. السجلات الأمنية

```php
// فقط Admin و Owner يمكنهم الوصول
public function viewSecurityLogs(User $user): bool
{
    return in_array($user->role, ['admin', 'owner']) ||
           $this->hasPermission($user, 'audit.view_security_logs');
}
```

### 2. التصفية التلقائية

عند طلب Activity Log بفئة `security`:

```php
if ($request->get('category') === 'security') {
    if (!$policy->viewSecurityLogs($user)) {
        return response()->json(['success' => false], 403);
    }
}
```

### 3. تسجيل محاولات الوصول المرفوضة

```php
// يتم تسجيل كل محاولة وصول مرفوضة في AuditLogger middleware
DB::table('cmis_audit.activity_log')->insert([
    'actor' => $user->email,
    'action' => 'access_denied',
    'context' => json_encode([
        'endpoint' => $request->path(),
        'reason' => 'insufficient_permissions'
    ]),
    'category' => 'security'
]);
```

---

## ⚙️ إضافة صلاحيات مخصصة

### 1. في Migration جديد

```php
DB::table('cmis.permissions')->insert([
    'permission_id' => DB::raw('gen_random_uuid()'),
    'name' => 'audit.custom_action',
    'description' => 'Custom audit action',
    'category' => 'audit',
    'created_at' => now()
]);
```

### 2. في Policy

```php
public function customAction(User $user): bool
{
    return $this->hasPermission($user, 'audit.custom_action');
}
```

### 3. في Controller

```php
if (!$policy->customAction($request->user())) {
    return response()->json(['success' => false], 403);
}
```

---

## 📊 مصفوفة الوصول

| Endpoint | Permission | Admin | Manager | Editor | Viewer |
|----------|-----------|-------|---------|--------|--------|
| `GET /audit/dashboard` | `view_dashboard` | ✅ | ✅ | ✅ | ✅ |
| `GET /audit/realtime-status` | `view_realtime` | ✅ | ✅ | ✅ | ✅ |
| `GET /audit/daily-summary` | `view_reports` | ✅ | ✅ | ❌ | ❌ |
| `GET /audit/weekly-performance` | `view_reports` | ✅ | ✅ | ❌ | ❌ |
| `GET /audit/audit-summary` | `view_reports` | ✅ | ✅ | ❌ | ❌ |
| `GET /audit/activity-log` | `view_activity_log` | ✅ | ✅ | ❌ | ❌ |
| `POST /audit/log-event` | `log_event` | ✅ | ✅ | ✅ | ❌ |
| `GET /audit/check-alerts` | `view_alerts` | ✅ | ✅ | ❌ | ❌ |
| `POST /audit/export-report` | `export_reports` | ✅ | ✅ | ❌ | ❌ |

---

## 🧪 الاختبار

### اختبار الصلاحيات

```php
// Test: Admin can access everything
$admin = User::where('role', 'admin')->first();
$policy = new AuditPolicy();

$this->assertTrue($policy->viewDashboard($admin));
$this->assertTrue($policy->viewSecurityLogs($admin));
$this->assertTrue($policy->exportReports($admin));

// Test: Viewer has limited access
$viewer = User::where('role', 'viewer')->first();

$this->assertTrue($policy->viewDashboard($viewer));
$this->assertFalse($policy->viewReports($viewer));
$this->assertFalse($policy->exportReports($viewer));
```

---

## ✅ الخلاصة

نظام الصلاحيات يوفر:

1. ✅ **تحكم دقيق** في الوصول لكل endpoint
2. ✅ **حماية السجلات الأمنية** من الوصول غير المصرح
3. ✅ **صلاحيات مبنية على الأدوار** (Role-based)
4. ✅ **قابلية التوسع** لإضافة صلاحيات جديدة
5. ✅ **تسجيل محاولات الوصول المرفوضة**
6. ✅ **API آمنة** مع responses واضحة

---

📍 **الملفات ذات الصلة:**
- `app/Policies/AuditPolicy.php` - منطق الصلاحيات
- `database/migrations/2025_11_15_000002_add_audit_permissions.php` - الصلاحيات في DB
- `app/Http/Controllers/API/AuditController.php` - التطبيق في API

**نظام آمن ومُحكم!** 🔒
