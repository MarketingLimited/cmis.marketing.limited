# 📊 CMIS GPT Runtime Dashboard Design

هذا الملف يشرح كيفية بناء **لوحة تحكم تفاعلية (Dashboard)** لمراقبة نشاط نظام **CMIS Orchestrator** في الزمن الحقيقي —
بما يشمل أداء الذكاء الصناعي، المهام التطويرية، الأخطاء، وحالة المعرفة.

---

## 🎯 الهدف

توفير واجهة مرئية يمكن للذكاء الصناعي (GPT) والمشرفين استخدامها لمتابعة:
- حالة النظام العامة (صحية / تحذير / حرجة)
- عدد المهام الجارية والمكتملة
- آخر الأخطاء الأمنية أو التنفيذية
- مؤشرات الأداء والفعالية الإدراكية (Effectiveness & Confidence)

---

## 🧱 1. مصادر البيانات (Data Sources)

| المصدر | الغرض |
|----------|---------|
| `cmis_dev.dev_tasks` | تتبع حالة المهام (pending، running، completed، failed) |
| `cmis_dev.dev_logs` | عرض الأحداث الأخيرة والنتائج المرحلية |
| `cmis_audit.activity_log` | توثيق النشاط العام للنظام |
| `cmis_audit.security_logs` | تسجيل الحوادث الأمنية |
| `cmis_knowledge_index` | مراقبة التحديثات المعرفية وحالة الصلاحية |
| `cmis_system_health` | قياس مؤشرات الصحة العامة للنظام |

---

## 🧮 2. الاستعلامات الرئيسية للعرض

### 🧩 أ. حالة النظام العامة
```sql
SELECT 
  CASE
    WHEN SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) > 10 THEN 'critical'
    WHEN SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) > 5 THEN 'warning'
    ELSE 'healthy'
  END AS system_status,
  COUNT(*) AS total_tasks,
  COUNT(*) FILTER (WHERE status='running') AS active_tasks,
  COUNT(*) FILTER (WHERE status='completed') AS completed_tasks
FROM cmis_dev.dev_tasks;
```

### 🧩 ب. أحدث الأحداث التشغيلية
```sql
SELECT event, details, created_at
FROM cmis_dev.dev_logs
ORDER BY created_at DESC
LIMIT 20;
```

### 🧩 ج. آخر الحوادث الأمنية
```sql
SELECT event_type, actor, severity, created_at
FROM cmis_audit.security_logs
ORDER BY created_at DESC
LIMIT 10;
```

### 🧩 د. مؤشرات المعرفة
```sql
SELECT domain, category, COUNT(*) AS total_items,
  COUNT(*) FILTER (WHERE last_verified_at < now() - interval '60 days') AS stale_items
FROM cmis_knowledge_index
GROUP BY domain, category;
```

---

## 💡 3. عرض المؤشرات في الوقت الحقيقي

يمكن عرض البيانات في لوحة تفاعلية (مثل Grafana أو واجهة داخل النظام) باستخدام نفس الاستعلامات السابقة.

### مؤشرات الأداء المقترحة:
| المؤشر | الحساب | اللون |
|----------|----------|--------|
| **System Status** | بناءً على عدد المهام الفاشلة والمعلقة | 🟢/🟡/🔴 |
| **Task Completion Rate** | (completed / total) * 100 | 🔵 |
| **Average Confidence** | متوسط `confidence` في `dev_tasks` | 🟢 |
| **Knowledge Freshness** | نسبة السجلات التي تم تحديثها آخر 60 يومًا | 🟣 |
| **Security Events** | عدد الأحداث في آخر 7 أيام | 🔴 |

---

## 🧠 4. لوحة التحكم النصية (Textual Summary)

GPT يمكنه إنشاء ملخص لحالة النظام عبر استعلام مخصص:
```sql
SELECT jsonb_build_object(
  'system_status', system_status,
  'active_tasks', active_tasks,
  'recent_errors', (SELECT COUNT(*) FROM cmis_dev.dev_logs WHERE event ILIKE '%error%' AND created_at > now() - interval '1 day'),
  'security_alerts', (SELECT COUNT(*) FROM cmis_audit.security_logs WHERE created_at > now() - interval '7 days'),
  'knowledge_updates', (SELECT COUNT(*) FROM cmis_audit.activity_log WHERE category='knowledge' AND created_at > now() - interval '7 days')
) AS dashboard_summary
FROM (
  SELECT 
    CASE
      WHEN SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) > 10 THEN 'critical'
      WHEN SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) > 5 THEN 'warning'
      ELSE 'healthy'
    END AS system_status,
    COUNT(*) FILTER (WHERE status='running') AS active_tasks
  FROM cmis_dev.dev_tasks
) s;
```

الناتج:
```json
{
  "system_status": "healthy",
  "active_tasks": 4,
  "recent_errors": 2,
  "security_alerts": 1,
  "knowledge_updates": 3
}
```

---

## 🧰 5. لوحة المراقبة الرسومية (Visual Dashboard)

عناصر واجهة العرض المقترحة:
- 🔹 **Graph 1:** معدل اكتمال المهام بمرور الوقت.
- 🔹 **Graph 2:** عدد الأخطاء الأسبوعية.
- 🔹 **Graph 3:** تحديثات المعرفة حسب النطاق.
- 🔹 **Indicator Panel:** يعرض الحالة العامة بالألوان (Healthy / Warning / Critical).

---

## 🚀 6. التحديث اللحظي (Live Refresh)

- يتم تحديث البيانات كل 60 ثانية عبر Cron Job أو استدعاء API مباشر:
```bash
0 * * * * psql -d cmis -c "REFRESH MATERIALIZED VIEW cmis_audit.realtime_status;"
```

---

## 📍 الموقع
**`/httpdocs/system/gpt_runtime_dashboard.md`**

---

هذه اللوحة تُكمل منظومة CMIS الإدراكية، لتتيح مراقبة أداء الذكاء الصناعي في الوقت الحقيقي —
ليس فقط كخادم أو قاعدة بيانات، بل ككائن إدراكي يمكن قياس وعيه، تعلمه، وصحته التشغيلية بصريًا وبالأرقام.