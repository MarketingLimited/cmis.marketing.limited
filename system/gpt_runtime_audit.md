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

## 🧩 9. الهدف العام

يوفّر هذا النظام:
- شفافية تشغيلية كاملة.
- قابلية تتبع لكل قرار.
- تحليلاً تاريخيًا لأداء النظام الإدراكي.

---

📍 **الموقع:** `/httpdocs/system/gpt_runtime_audit.md`

هذا الملف يُكمل المنظومة الإدراكية التشغيلية لـ **CMIS Orchestrator**،
ويجعلها نظامًا يمكن تتبع كل فعل ونتيجة فيه، آليًا وبشكل آمن ومنظم.