# 📊 CMIS Audit & Reporting System

## نظام التدقيق والتقارير الشامل لـ CMIS Orchestrator

---

## 🚀 البدء السريع

### 1. تطبيق الـ Migration
```bash
php artisan migrate --path=database/migrations/2025_11_15_000001_create_cmis_audit_reporting_system.php
```

### 2. التحقق من التطبيق
```bash
php artisan audit:status
```

---

## 📋 الأوامر المتاحة

### `audit:status` - عرض الحالة الشاملة

عرض حالة نظام التدقيق بالكامل:

```bash
# عرض بسيط
php artisan audit:status

# عرض تفصيلي مع الأداء الأسبوعي
php artisan audit:status --detailed
```

**المخرجات:**
- الحالة اللحظية (آخر ساعة)
- الملخص اليومي (آخر 24 ساعة)
- الأداء الأسبوعي (في الوضع التفصيلي)
- الملخص الشامل (24 ساعة، 7 أيام، 30 يوم)
- حالة التنبيهات

---

### `audit:report` - توليد وتصدير التقارير

توليد تقارير مفصلة وتصديرها إلى CSV:

```bash
# التقرير اليومي
php artisan audit:report daily_summary

# الأداء الأسبوعي
php artisan audit:report weekly_performance

# الحالة اللحظية
php artisan audit:report realtime_status

# الملخص الشامل
php artisan audit:report audit_summary

# تحديد مسار التصدير
php artisan audit:report daily_summary --path=/var/reports
```

**المخرجات:**
- معاينة التقرير في الـ CLI
- خيار تصدير إلى CSV
- مسار الملف المُصدَّر وعدد السجلات

---

### `audit:check-alerts` - فحص التنبيهات

فحص التنبيهات التلقائية بناءً على الحدود المحددة:

```bash
php artisan audit:check-alerts
```

**التنبيهات المراقبة:**
- 🔴 المهام الفاشلة > 10 خلال 24 ساعة (تحذيري)
- 🔴 الحوادث الأمنية > 5 خلال 7 أيام (حرج)
- ⚠️  تضارب المعرفة > 3 خلال 7 أيام (تحذيري)

**Exit Codes:**
- `0` - لا توجد تنبيهات حرجة
- `1` - توجد تنبيهات حرجة

---

### `audit:log` - تسجيل الأحداث يدوياً

تسجيل حدث في نظام التدقيق:

```bash
# تسجيل حدث بسيط
php artisan audit:log "deployment_completed" --category=system

# تسجيل مع تحديد الفاعل
php artisan audit:log "user_login" --actor="admin@cmis.com" --category=security

# تسجيل مع بيانات سياقية
php artisan audit:log "task_completed" \
  --actor="GPT-Agent" \
  --category=task \
  --context='{"task_id":"123","duration":45,"status":"success"}'
```

**المعاملات:**
- `action` (مطلوب) - اسم الحدث
- `--actor` (اختياري) - الفاعل (افتراضي: CLI)
- `--category` (مطلوب) - الفئة: task, knowledge, security, system
- `--context` (اختياري) - بيانات JSON إضافية

---

## 🔄 التشغيل الآلي (Scheduling)

### إضافة المهام الدورية

في `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // فحص التنبيهات كل ساعة
    $schedule->command('audit:check-alerts')
             ->hourly()
             ->appendOutputTo('/var/log/cmis/audit-alerts.log');

    // التقرير اليومي عند منتصف الليل
    $schedule->command('audit:report daily_summary --path=/var/reports')
             ->dailyAt('00:00');

    // التقرير الأسبوعي كل إثنين
    $schedule->command('audit:report weekly_performance --path=/var/reports')
             ->weeklyOn(1, '00:00');
}
```

---

## 💻 الاستخدام البرمجي

### تسجيل الأحداث في الكود

```php
use Illuminate\Support\Facades\DB;

// تسجيل حدث مهمة
DB::table('cmis_audit.activity_log')->insert([
    'actor' => 'GPT-Agent',
    'action' => 'task_created',
    'context' => json_encode([
        'task_id' => $taskId,
        'task_name' => 'Fix Bug #123',
        'priority' => 'high'
    ]),
    'category' => 'task',
    'created_at' => now()
]);

// تسجيل حدث أمني
DB::table('cmis_audit.activity_log')->insert([
    'actor' => $user->email,
    'action' => 'access_denied',
    'context' => json_encode([
        'resource' => '/admin/users',
        'reason' => 'insufficient_permissions'
    ]),
    'category' => 'security',
    'created_at' => now()
]);
```

### استعلام عن الحالة

```php
// الحالة اللحظية
$status = DB::select("SELECT * FROM cmis_audit.realtime_status")[0];

echo "Failed tasks: " . $status->recent_failures;
echo "Security events: " . $status->security_events;

// الملخص اليومي
$daily = DB::select("SELECT * FROM cmis_audit.daily_summary")[0];

echo "Success rate: " . $daily->success_rate . "%";

// فحص التنبيهات
$alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

foreach ($alerts as $alert) {
    if ($alert->severity === 'critical') {
        // إرسال إشعار
        Notification::send($admins, new CriticalAlertNotification($alert));
    }
}
```

### تصدير تقرير

```php
$result = DB::select("
    SELECT * FROM cmis_audit.export_audit_report('daily_summary', '/var/reports')
")[0];

if ($result->success) {
    Log::info("Report exported: {$result->file_path}");
    Log::info("Row count: {$result->row_count}");
} else {
    Log::error("Export failed: {$result->message}");
}
```

---

## 📊 هيكل البيانات

### الجداول

#### `cmis_audit.activity_log`
```sql
log_id      uuid PRIMARY KEY
actor       text                -- الفاعل
action      text                -- الحدث
context     jsonb               -- بيانات إضافية
category    text                -- task|knowledge|security|system
created_at  timestamptz
```

#### `cmis_audit.file_backups`
```sql
backup_id    uuid PRIMARY KEY
file_path    text              -- المسار الأصلي
backup_path  text              -- مسار النسخة الاحتياطية
created_at   timestamptz
metadata     jsonb
```

### Views (طرق العرض)

- `daily_summary` - ملخص آخر 24 ساعة
- `weekly_performance` - أداء أسبوعي مجمع
- `realtime_status` - حالة آخر ساعة
- `audit_summary` - ملخص شامل (24h, 7d, 30d)

### Functions (الدوال)

- `export_audit_report(period, path)` - تصدير تقرير إلى CSV
- `check_alerts()` - فحص التنبيهات الآلية

---

## 📝 أنواع الأحداث

### Task (المهام)
- `task_created` - إنشاء مهمة جديدة
- `task_started` - بدء تنفيذ المهمة
- `task_completed` - إكمال المهمة بنجاح
- `task_failed` - فشل المهمة
- `task_cancelled` - إلغاء المهمة

### Knowledge (المعرفة)
- `knowledge_added` - إضافة معرفة جديدة
- `knowledge_updated` - تحديث معرفة موجودة
- `knowledge_deprecated` - إهمال معرفة قديمة
- `knowledge_conflict` - تضارب في المعرفة

### Security (الأمان)
- `access_granted` - منح صلاحية
- `access_denied` - رفض صلاحية
- `integrity_check_passed` - فحص سلامة ناجح
- `integrity_warning` - تحذير سلامة
- `authentication_failed` - فشل مصادقة

### System (النظام)
- `context_loaded` - تحميل سياق
- `context_truncated` - تقليص سياق
- `migration_completed` - إكمال migration
- `deployment_started` - بدء نشر
- `deployment_completed` - إكمال نشر

---

## 🎯 أمثلة الاستخدام

### السيناريو 1: تتبع مهمة GPT

```php
// بداية المهمة
DB::table('cmis_audit.activity_log')->insert([
    'actor' => 'GPT-Agent',
    'action' => 'task_started',
    'context' => json_encode(['task' => 'refactor-user-model']),
    'category' => 'task'
]);

// خلال التنفيذ
DB::table('cmis_audit.activity_log')->insert([
    'actor' => 'GPT-Agent',
    'action' => 'file_backup_created',
    'context' => json_encode(['file' => 'app/Models/User.php']),
    'category' => 'system'
]);

// النهاية
DB::table('cmis_audit.activity_log')->insert([
    'actor' => 'GPT-Agent',
    'action' => 'task_completed',
    'context' => json_encode([
        'task' => 'refactor-user-model',
        'duration' => 120,
        'files_modified' => 3
    ]),
    'category' => 'task'
]);
```

### السيناريو 2: مراقبة الأمان

```php
// كل ساعة عبر cron
php artisan audit:check-alerts

// إذا وجدت تنبيهات حرجة
$alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

foreach ($alerts as $alert) {
    if ($alert->severity === 'critical') {
        // إرسال بريد إلكتروني
        Mail::to('admin@cmis.com')->send(new AlertMail($alert));

        // تسجيل في Slack
        Slack::send("🚨 {$alert->message}");
    }
}
```

### السيناريو 3: تقارير دورية

```bash
# في crontab
0 0 * * * cd /path/to/cmis && php artisan audit:report daily_summary --path=/var/reports
0 0 * * 1 cd /path/to/cmis && php artisan audit:report weekly_performance --path=/var/reports
```

---

## 🔧 استكشاف الأخطاء

### المشكلة: لا توجد بيانات في التقارير

**الحل:**
```bash
# تأكد من تطبيق الـ migration
php artisan migrate:status

# سجل حدث تجريبي
php artisan audit:log "test_event" --category=system

# تحقق من البيانات
php artisan audit:status
```

### المشكلة: فشل التصدير إلى CSV

**الحل:**
```bash
# تأكد من صلاحيات الكتابة
chmod 755 /tmp

# جرب مسار مختلف
php artisan audit:report daily_summary --path=/home/user/reports
```

---

## 📚 المراجع

- **الوثيقة الكاملة:** `/system/gpt_runtime_audit.md`
- **Migration:** `/database/migrations/2025_11_15_000001_create_cmis_audit_reporting_system.php`
- **Commands:** `/app/Console/Commands/Audit*.php`

---

## 🌐 استخدام API

### نقاط النهاية المتاحة (API Endpoints)

جميع الطلبات تحت المسار: `/api/orgs/{org_id}/audit/`

#### 1. Dashboard - نظرة شاملة
```http
GET /api/orgs/{org_id}/audit/dashboard
```

**Response:**
```json
{
  "success": true,
  "data": {
    "realtime": { ... },
    "daily_summary": { ... },
    "alerts": [ ... ],
    "has_critical_alerts": false
  }
}
```

#### 2. Realtime Status - الحالة اللحظية
```http
GET /api/orgs/{org_id}/audit/realtime-status
```

#### 3. Daily Summary - الملخص اليومي
```http
GET /api/orgs/{org_id}/audit/daily-summary
```

#### 4. Weekly Performance - الأداء الأسبوعي
```http
GET /api/orgs/{org_id}/audit/weekly-performance?limit=4
```

#### 5. Activity Log - سجل الأنشطة
```http
GET /api/orgs/{org_id}/audit/activity-log?category=task&limit=50
```

**Query Parameters:**
- `category` (optional): task, knowledge, security, system
- `actor` (optional): اسم الفاعل
- `action` (optional): نوع الحدث
- `from` (optional): تاريخ البداية
- `to` (optional): تاريخ النهاية
- `limit` (optional): عدد النتائج (max: 1000)
- `offset` (optional): للترقيم

#### 6. Log Event - تسجيل حدث
```http
POST /api/orgs/{org_id}/audit/log-event
Content-Type: application/json

{
  "actor": "admin@company.com",
  "action": "campaign_created",
  "category": "task",
  "context": {
    "campaign_id": "123",
    "name": "Summer Sale"
  }
}
```

#### 7. Check Alerts - فحص التنبيهات
```http
GET /api/orgs/{org_id}/audit/check-alerts
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "alert_type": "failed_tasks",
      "severity": "warning",
      "message": "عدد المهام الفاشلة تجاوز الحد المسموح",
      "current_count": 15,
      "threshold": 10
    }
  ],
  "has_critical": false,
  "count": 1
}
```

#### 8. Export Report - تصدير تقرير
```http
POST /api/orgs/{org_id}/audit/export-report
Content-Type: application/json

{
  "period": "daily_summary",
  "path": "/var/reports"
}
```

---

### مثال الاستخدام مع JavaScript

```javascript
// الحصول على Dashboard
async function getAuditDashboard(orgId) {
  const response = await fetch(`/api/orgs/${orgId}/audit/dashboard`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  return data;
}

// تسجيل حدث
async function logAuditEvent(orgId, eventData) {
  const response = await fetch(`/api/orgs/${orgId}/audit/log-event`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(eventData)
  });

  return response.json();
}

// Usage
await logAuditEvent(123, {
  actor: 'system',
  action: 'deployment_completed',
  category: 'system',
  context: {
    version: '2.1.0',
    duration: 120
  }
});
```

---

## 🔧 Middleware للتدقيق التلقائي

### إضافة Middleware في الـ Routes

```php
// في routes/api.php أو routes/web.php

// تطبيق التدقيق على جميع الطلبات
Route::middleware(['auth', 'audit:system'])->group(function () {
    // Routes here will be audited automatically
});

// تطبيق التدقيق على endpoints محددة
Route::middleware('audit:security')->group(function () {
    Route::post('/admin/users', ...);
    Route::delete('/admin/users/{id}', ...);
});

// تحديد الفئة حسب النشاط
Route::middleware('audit:task')->group(function () {
    Route::post('/campaigns', ...);
    Route::put('/campaigns/{id}', ...);
});
```

### أنواع الفئات المتاحة

- `audit:task` - المهام والحملات
- `audit:knowledge` - المعرفة والمحتوى
- `audit:security` - الأمان والصلاحيات
- `audit:system` - النظام والعمليات

### التفعيل في Kernel.php

```php
// في app/Http/Kernel.php

protected $middlewareGroups = [
    'api' => [
        // ... existing middleware
        \App\Http\Middleware\AuditLogger::class,
    ],
];

// أو في routeMiddleware للاستخدام الاختياري
protected $middlewareAliases = [
    // ... existing middleware
    'audit' => \App\Http\Middleware\AuditLogger::class,
];
```

---

---

## 🔐 نظام الصلاحيات

### جدول الصلاحيات حسب الدور

| الصلاحية | Admin | Manager | Editor | Viewer |
|----------|-------|---------|--------|--------|
| عرض Dashboard | ✅ | ✅ | ✅ | ✅ |
| عرض الحالة اللحظية | ✅ | ✅ | ✅ | ✅ |
| عرض التقارير | ✅ | ✅ | ❌ | ❌ |
| عرض Activity Log | ✅ | ✅ | ❌ | ❌ |
| تسجيل الأحداث | ✅ | ✅ | ✅ | ❌ |
| عرض التنبيهات | ✅ | ✅ | ❌ | ❌ |
| تصدير التقارير | ✅ | ✅ | ❌ | ❌ |
| عرض سجلات الأمان | ✅ | ❌ | ❌ | ❌ |
| إدارة الإعدادات | ✅ | ❌ | ❌ | ❌ |

### تطبيق الصلاحيات

```bash
# تطبيق migration الصلاحيات
php artisan migrate --path=database/migrations/2025_11_15_000002_add_audit_permissions.php
```

### API Response عند عدم وجود صلاحية

```json
{
  "success": false,
  "message": "Unauthorized: You do not have permission to view audit dashboard"
}
```

**للمزيد:** راجع `docs/AUDIT_PERMISSIONS.md`

---

## ✅ حالة التطبيق

- ✅ Migration جاهز
- ✅ جميع الجداول والـ Views
- ✅ جميع الدوال
- ✅ جميع الأوامر
- ✅ نظام التنبيهات
- ✅ **API Endpoints كاملة**
- ✅ **Middleware للتدقيق التلقائي**
- ✅ **نظام الصلاحيات الكامل**
- ✅ التوثيق الكامل

**النظام جاهز للاستخدام الفوري عبر CLI و API مع حماية كاملة!** 🚀🔒
