# 🧾 CMIS GPT Runtime Audit & Reporting Guide

هذا الملف يشرح كيفية جمع، تحليل، وتوليد التقارير التشغيلية والإدارية من داخل **CMIS Orchestrator**،
حيث يقوم الذكاء الصناعي (GPT) بمتابعة الأحداث والنتائج من كل من: المهام، الأخطاء، المعرفة، والأمان.

---

## 🧠 1. الغرض من نظام التدقيق (Audit Purpose)

الهدف من سجل التدقيق هو توثيق كل نشاط تنفيذي أو معرفي أو أمني بشكل قابل للتحليل اللاحق.

المكونات التي يُراقبها النظام:
- `cmis_dev.dev_logs` → خطوات التنفيذ، الأخطاء، النجاحات.
- `cmis_audit.security_logs` → الأحداث الأمنية.
- `cmis_knowledge_index` → تحديثات المعرفة.
- `cmis_dev.dev_tasks` → المهام المكتملة والفاشلة.

---

## 📊 2. بنية جداول التدقيق (Audit Schema)

```sql
CREATE SCHEMA IF NOT EXISTS cmis_audit;

CREATE TABLE IF NOT EXISTS cmis_audit.activity_log (
  log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  actor text,
  action text,
  context jsonb,
  category text CHECK (category IN ('task','knowledge','security','system')),
  created_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS cmis_audit.file_backups (
  backup_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  file_path text,
  backup_path text,
  created_at timestamptz DEFAULT now()
);
```

---

## 📈 3. أنواع الأحداث المسجلة

| الفئة | نوع الحدث | الوصف |
|--------|-------------|---------|
| **task** | `task_created` | عند إنشاء مهمة جديدة عبر GPT |
| | `task_completed` | عند نجاح تنفيذ المهمة |
| | `task_failed` | عند فشل المهمة |
| **knowledge** | `knowledge_added` | عند تسجيل معرفة جديدة |
| | `knowledge_updated` | عند تحديث سجل معرفي |
| **security** | `access_denied` | رفض صلاحيات وصول |
| | `integrity_warning` | فشل تحقق سلامة ملفات |
| **system** | `context_loaded` | تحميل السياق الإدراكي بنجاح |
| | `context_truncated` | تقليص محتوى بسبب تجاوز حد التوكنات |

---

## 🧩 4. التجميع التلقائي للتقارير (Automatic Aggregation)

### التقرير اليومي (Daily Summary)

```sql
CREATE OR REPLACE VIEW cmis_audit.daily_summary AS
SELECT 
  current_date AS report_date,
  COUNT(*) FILTER (WHERE category='task') AS total_tasks,
  COUNT(*) FILTER (WHERE category='knowledge') AS knowledge_events,
  COUNT(*) FILTER (WHERE category='security') AS security_incidents,
  COUNT(*) FILTER (WHERE category='system') AS system_operations
FROM cmis_audit.activity_log
WHERE created_at > now() - interval '24 hours';
```

### التقرير الأسبوعي (Weekly Performance)

```sql
CREATE OR REPLACE VIEW cmis_audit.weekly_performance AS
SELECT
  date_trunc('week', created_at) AS week_start,
  COUNT(*) FILTER (WHERE category='task') AS total_tasks,
  COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS failed_tasks,
  COUNT(*) FILTER (WHERE category='security') AS security_alerts,
  COUNT(*) FILTER (WHERE category='knowledge') AS new_knowledge,
  ROUND((COUNT(*) FILTER (WHERE category='task' AND action='task_completed') * 100.0 / NULLIF(COUNT(*) FILTER (WHERE category='task'), 0)),2) AS success_rate
FROM cmis_audit.activity_log
GROUP BY week_start
ORDER BY week_start DESC;
```

---

## 🧠 5. منطق التسجيل الآلي (GPT Logic)

عند تنفيذ أي عملية، يجب على GPT تسجيلها بهذا النمط:
```sql
INSERT INTO cmis_audit.activity_log (actor, action, context, category)
VALUES ('GPT-Agent', 'task_created', '{"task_name":"Meta Refresh"}', 'task');
```

بعد النجاح:
```sql
INSERT INTO cmis_audit.activity_log (actor, action, context, category)
VALUES ('GPT-Agent', 'task_completed', '{"status":"success","duration":32}', 'task');
```

---

## 🧩 6. تقارير الحالة اللحظية (Real-time Dashboards)

```sql
CREATE OR REPLACE VIEW cmis_audit.realtime_status AS
SELECT 
  COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS recent_failures,
  COUNT(*) FILTER (WHERE category='security') AS security_events,
  COUNT(*) FILTER (WHERE category='knowledge') AS knowledge_updates,
  MAX(created_at) AS last_update
FROM cmis_audit.activity_log
WHERE created_at > now() - interval '1 hour';
```

يمكن لـ GPT استدعاء التقرير اللحظي عبر:
```sql
SELECT * FROM cmis_audit.realtime_status;
```

---

## 📤 7. إخراج التقارير (Exporting Reports)

يمكن تصدير التقارير إلى ملفات CSV عبر وظيفة PL/pgSQL:

```sql
CREATE OR REPLACE FUNCTION export_audit_report(p_period text)
RETURNS void AS $$
DECLARE
  v_filename text;
BEGIN
  v_filename := '/httpdocs/reports/audit_' || p_period || '_' || to_char(now(), 'YYYYMMDD') || '.csv';
  EXECUTE format('COPY (SELECT * FROM cmis_audit.%I) TO %L WITH CSV HEADER;', p_period, v_filename);
END;
$$ LANGUAGE plpgsql;
```

مثال:
```sql
SELECT export_audit_report('weekly_performance');
```

---

## 🚨 8. التنبيهات التلقائية (Alerts)

النظام يرسل إشعارات إذا تجاوز عدد الأخطاء أو الحوادث الأمنية الحدود التالية:

| الحالة | الحد | الإجراء |
|----------|-------|----------|
| فشل المهام | > 10 خلال يوم | إشعار تحذيري إلى مدير النظام |
| حوادث الأمان | > 5 أسبوعيًا | إرسال تنبيه عاجل |
| تضارب المعرفة | > 3 أسبوعيًا | تفعيل مراجعة بشرية للمعرفة |

---

## 🧩 9. استخدام Artisan Commands

تم تطبيق نظام التدقيق كاملاً وتوفير أوامر CLI للتعامل معه:

### عرض حالة النظام الشاملة
```bash
php artisan audit:status

# عرض تفصيلي مع الأداء الأسبوعي
php artisan audit:status --detailed
```

### توليد وتصدير التقارير
```bash
# التقرير اليومي
php artisan audit:report daily_summary

# الأداء الأسبوعي
php artisan audit:report weekly_performance

# الحالة اللحظية
php artisan audit:report realtime_status

# تحديد مسار التصدير
php artisan audit:report daily_summary --path=/home/user/reports
```

### فحص التنبيهات التلقائية
```bash
php artisan audit:check-alerts
```

### تسجيل حدث يدوياً
```bash
# تسجيل حدث بسيط
php artisan audit:log "deployment_completed" --category=system

# تسجيل حدث مع بيانات إضافية
php artisan audit:log "task_completed" \
  --actor="GPT-Agent" \
  --category=task \
  --context='{"task":"fix-bug-123","duration":45}'
```

---

## 🎯 10. التكامل مع العمليات الآلية

### إضافة إلى Schedule (Kernel.php)
```php
// في app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // فحص التنبيهات كل ساعة
    $schedule->command('audit:check-alerts')
             ->hourly()
             ->appendOutputTo('/var/log/cmis/audit-alerts.log');

    // توليد التقرير اليومي كل منتصف ليل
    $schedule->command('audit:report daily_summary --path=/var/reports')
             ->dailyAt('00:00');

    // التقرير الأسبوعي كل إثنين
    $schedule->command('audit:report weekly_performance --path=/var/reports')
             ->weeklyOn(1, '00:00');
}
```

### استخدام في الكود
```php
use Illuminate\Support\Facades\DB;

// تسجيل حدث
DB::table('cmis_audit.activity_log')->insert([
    'actor' => 'GPT-Agent',
    'action' => 'task_created',
    'context' => json_encode(['task_name' => 'Meta Refresh']),
    'category' => 'task',
    'created_at' => now()
]);

// استعلام عن الحالة اللحظية
$status = DB::select("SELECT * FROM cmis_audit.realtime_status")[0];

// تصدير تقرير
$result = DB::select("
    SELECT * FROM cmis_audit.export_audit_report('daily_summary', '/tmp')
")[0];
```

---

## 🗂️ 11. الهيكل الكامل للنظام

### الجداول (Tables)
- ✅ `cmis_audit.activity_log` - سجل الأحداث التفصيلي
- ✅ `cmis_audit.file_backups` - تتبع النسخ الاحتياطية للملفات
- ✅ `cmis_audit.logs` - السجل القديم (للتوافق مع الأنظمة الموجودة)

### طرق العرض (Views)
- ✅ `cmis_audit.daily_summary` - ملخص يومي
- ✅ `cmis_audit.weekly_performance` - أداء أسبوعي
- ✅ `cmis_audit.realtime_status` - حالة لحظية
- ✅ `cmis_audit.audit_summary` - ملخص شامل (24 ساعة، 7 أيام، 30 يوم)

### الدوال (Functions)
- ✅ `cmis_audit.export_audit_report(period, path)` - تصدير التقارير
- ✅ `cmis_audit.check_alerts()` - فحص التنبيهات

### الأوامر (Commands)
- ✅ `audit:status` - عرض حالة النظام
- ✅ `audit:report` - توليد وتصدير التقارير
- ✅ `audit:check-alerts` - فحص التنبيهات
- ✅ `audit:log` - تسجيل الأحداث

---

## 🧩 12. الهدف العام

يوفّر هذا النظام:
- ✅ شفافية تشغيلية كاملة
- ✅ قابلية تتبع لكل قرار ونشاط
- ✅ تحليلاً تاريخيًا لأداء النظام الإدراكي
- ✅ تنبيهات آلية للمشاكل المحتملة
- ✅ تقارير قابلة للتصدير والمشاركة
- ✅ واجهة CLI سهلة الاستخدام

---

📍 **الموقع:** `/system/gpt_runtime_audit.md`
📁 **Migration:** `/database/migrations/2025_11_15_000001_create_cmis_audit_reporting_system.php`
⚙️ **Commands:** `/app/Console/Commands/Audit*.php`

هذا الملف يُكمل المنظومة الإدراكية التشغيلية لـ **CMIS Orchestrator**،
ويجعلها نظامًا يمكن تتبع كل فعل ونتيجة فيه، آليًا وبشكل آمن ومنظم.

✅ **حالة التطبيق:** مُطبَّق كاملاً ✅