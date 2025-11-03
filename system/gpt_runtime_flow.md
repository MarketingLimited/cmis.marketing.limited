# ⚙️ CMIS GPT Runtime Flow

هذا الملف يشرح التسلسل الإدراكي الكامل لعمل الذكاء الصناعي (GPT) داخل منظومة **CMIS Orchestrator**، بدءًا من استقبال البرومبت وحتى تسجيل النتيجة النهائية.

---

## 🧠 1. استقبال البرومبت (Prompt Intake)

عند استلام أي طلب من المستخدم:
1. يقوم GPT بتحليل النية (Intent Analysis):
   - تحديد **المجال (domain)** مثل `meta_api`, `instagram`, `ftp_automation`.
   - تحديد **الفئة (category)** مثل `dev`, `marketing`, `org`, `research`.
2. يتم تسجيل البرومبت كنص في سجل الجلسة التشغيلية.

---

## 🧩 2. تحميل السياق المعرفي (Context Loading)

يستدعي GPT الدالة التالية:
```sql
SELECT * FROM cmis_knowledge.smart_context_loader('<prompt>', '<domain>', '<category>', 5000);
```

الناتج:
- ملخص معرفي (summary)
- مقاطع معرفة ذات صلة (context_loaded)
- عدد التوكنات التقريبي (estimated_tokens)

يُستخدم هذا السياق لاحقًا لتوليد خطة التنفيذ المناسبة.

---

## 🛠️ 3. إعداد المهمة (Task Preparation)

يستدعي GPT:
```sql
SELECT * FROM cmis_dev.prepare_context_execution('<prompt>', '<domain>', '<category>');
```

الناتج:
- `task_id` — معرّف المهمة.
- `execution_plan` — خطة التنفيذ المفصلة.
- `context_summary` — ملخص المعرفة المرتبطة.

يتم تسجيل هذا الحدث في جدول `cmis_dev.dev_logs`.

---

## 🚀 4. تنفيذ الخطة (Execution Phase)

يقرأ GPT خطة التنفيذ (`execution_plan`) وينفذ الخطوات بالترتيب:

| نوع الخطوة | أداة التنفيذ | التصرف |
|-------------|----------------|----------|
| `sql` | `executeSqlQuery` | تنفيذ استعلامات SQL الآمنة فقط. |
| `api` | `runShellCommand` | تنفيذ استدعاءات API عبر أوامر `curl`. |
| `artisan` | `runShellCommand` | تشغيل أوامر Laravel Artisan. |
| `ftp` | `uploadFile` / `downloadFile` | إدارة الملفات على الخادم. |
| `analysis` | GPT داخلي | تحليل النتائج وتوليد الدروس. |
| `knowledge` | SQL Loader | استدعاء معرفة إضافية عند الحاجة. |

كل نتيجة تُسجل في `cmis_dev.dev_logs` بعد التنفيذ:
```sql
INSERT INTO cmis_dev.dev_logs (task_id, event, details)
VALUES ('<task_id>', 'step_executed', '{"step":<n>,"result":"OK"}');
```

---

## 🧪 5. التحقق (Validation)

بعد تنفيذ جميع الخطوات:
- يجري GPT اختبارًا للتحقق من نجاح العملية.
- عند النجاح:
```sql
UPDATE cmis_dev.dev_tasks
SET status='completed', confidence=0.95, effectiveness_score=90
WHERE task_id='<id>';
```
- عند الفشل:
```sql
UPDATE cmis_dev.dev_tasks
SET status='failed'
WHERE task_id='<id>';
```

---

## 📚 6. التعلم المعرفي (Knowledge Feedback)

إذا تم التوصل إلى تحسين أو تصحيح جديد:
```sql
SELECT register_knowledge(
    p_domain := '<domain>',
    p_category := '<category>',
    p_topic := '<lesson_title>',
    p_content := '<summary of outcome>',
    p_tier := 2,
    p_keywords := ARRAY['auto','learning']
);
```

يُضاف هذا السجل تلقائيًا إلى قاعدة المعرفة ليُستخدم في المهام المستقبلية.

---

## 🔄 7. الدورة الإدراكية الكاملة (Cognitive Cycle)

```text
[Prompt Received]
       ↓
[Intent Recognition]
       ↓
[Context Loading]
       ↓
[Task Preparation]
       ↓
[Execution Plan]
       ↓
[Validation]
       ↓
[Learning & Knowledge Update]
```

---

## 📦 موقع الملف
```
/httpdocs/system/gpt_runtime_flow.md
```

الغرض من الملف: توثيق المنطق التنفيذي الذي تتبعه طبقة GPT الخارجية لضمان أن كل خطوة في منظومة CMIS Orchestrator تتم بطريقة منهجية وقابلة للتتبع.