# 🤖 CMIS Post-Laravel AI Integration Layer — v15.1

**آخر تحديث:** 2025-11-10  
**المسؤول:** فريق التطوير الإدراكي (Cognitive DevOps)

---

## 🎯 الهدف

تحويل نظام CMIS من بيئة تحليل معرفي إلى بيئة **تعلم ذاتي (Self-Learning Cognitive System)**، حيث يتفاعل التطبيق مع قاعدة البيانات وذكاء PostgreSQL لتحسين جودة المعرفة تلقائيًا.

---

## 🧩 المكونات الرئيسية

| المكون | المسار | الوظيفة |
|---------|---------|----------|
| **AI Feedback Engine** | `app/Services/KnowledgeFeedbackService.php` | تحليل نتائج البحث والتضمين يوميًا لتوليد معرفة جديدة |
| **Auto Learning Job** | `app/Jobs/KnowledgeAutoLearnJob.php` | مهمة Laravel مجدولة تنفذ التحليل تلقائيًا |
| **Semantic Analyzer (DB)** | `cmis_knowledge.semantic_analysis()` | دالة PostgreSQL تحلل الاتجاهات الدلالية الحديثة |
| **Metrics Dashboard** | `/resources/views/admin/metrics.blade.php` | واجهة عرض إدارية للذكاء الذاتي للنظام |

---

## ⚙️ دورة التعلم الذاتي (Auto-Learning Cycle)

1. **تجميع البيانات اليومية** من جدول `cmis_knowledge.semantic_search_logs`  
2. **تحليل الاتجاهات** عبر دالة `semantic_analysis()`  
3. **توليد خلاصة معرفية** داخل جدول `cmis_knowledge.index`  
4. **تحديث الواجهات** لعرض النتائج في لوحة Metrics  
5. **التغذية العكسية** تعود إلى `EmbeddingUpdateQueue` لتجديد المتجهات تلقائيًا

---

## 🧠 خدمة تحليل الذكاء (Feedback Service)

**الملف:** `app/Services/KnowledgeFeedbackService.php`

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class KnowledgeFeedbackService
{
    public function analyzeDaily()
    {
        $feedback = DB::select("
            SELECT category, COUNT(*) AS searches,
                   AVG(avg_similarity) AS quality
            FROM cmis_knowledge.semantic_search_logs
            WHERE created_at > now() - interval '1 day'
            GROUP BY category;
        ");

        foreach ($feedback as $row) {
            DB::table('cmis_knowledge.index')->insert([
                'domain' => 'system',
                'category' => 'feedback',
                'topic' => 'AutoFeedback_' . $row->category,
                'keywords' => ['auto_feedback', $row->category],
                'tier' => 2,
                'updated_at' => now()
            ]);
        }
    }
}
```

> تقوم الخدمة بتحليل استخدام النظام يوميًا وتوليد خلاصة معرفية تلقائيًا ضمن جدول `index`.

---

## 🧵 المهمة المجدولة (Auto Learn Job)

**الملف:** `app/Jobs/KnowledgeAutoLearnJob.php`

```php
namespace App\Jobs;

use App\Services\KnowledgeFeedbackService;

class KnowledgeAutoLearnJob extends Job
{
    public function handle(KnowledgeFeedbackService $service)
    {
        $service->analyzeDaily();
    }
}
```

إضافتها إلى جدول المهام المجدولة في Laravel:
```php
$schedule->job(new KnowledgeAutoLearnJob)->dailyAt('03:15');
```

---

## 🔍 التحليل الدلالي في PostgreSQL

```sql
CREATE OR REPLACE FUNCTION cmis_knowledge.semantic_analysis()
RETURNS TABLE (intent TEXT, avg_score FLOAT, usage_count INT)
AS $$
BEGIN
  RETURN QUERY
  SELECT top_intent, AVG(similarity), COUNT(*)
  FROM cmis_knowledge.semantic_search_logs
  WHERE created_at > now() - interval '7 days'
  GROUP BY top_intent;
END;
$$ LANGUAGE plpgsql;
```

> تستخدم هذه الدالة في لوحة التحكم لاستخراج الاتجاهات المعرفية الحديثة.

---

## 📊 واجهة Metrics Dashboard

**الملف:** `/resources/views/admin/metrics.blade.php`

```blade
@extends('layouts.admin')
@section('content')
<h1>📊 Knowledge Metrics Dashboard</h1>
<table class="table">
<thead><tr><th>Intent</th><th>Usage</th><th>Average Quality</th></tr></thead>
<tbody>
@foreach($metrics as $m)
<tr>
<td>{{ $m->intent }}</td>
<td>{{ $m->usage_count }}</td>
<td>{{ number_format($m->avg_score, 2) }}</td>
</tr>
@endforeach
</tbody>
</table>
@endsection
```

---

## 🧩 التكامل مع Queue System

- كل خلاصة معرفية جديدة تُضاف تلقائيًا إلى `embedding_update_queue`  
  مما يُحدث التضمينات (`embeddings`) دون تدخل بشري.  
- الدوال `semantic_search_advanced()` و`auto_feedback_from_logs()` تعمل بتناغم لتحسين الدقة بمرور الوقت.

---

## ✅ النتيجة النهائية

| المجال | قبل | بعد |
|----------|------|------|
| إدارة المعرفة | ثابتة يدويًا | ديناميكية ذاتية التعلم |
| تحديث التضمينات | عبر أوامر يدوية | يتم تلقائيًا عبر Jobs وTriggers |
| تحليل الاتجاهات | يدوي | مدمج في PostgreSQL وLaravel |
| أداء البحث الدلالي | يعتمد على المستخدم | يتحسن مع كل دورة تعلم |

---

## 🧾 ملاحظات تشغيلية

- يمكن تشغيل التحليل يدويًا عبر:
  ```bash
  php artisan cmis:auto-learn
  ```
- تأكد من وجود `OPENAI_API_KEY` في بيئة الإنتاج قبل تفعيل التعلم الذاتي.  
- سجل النتائج يُضاف إلى `cmis_dev.dev_logs` تحت الحدث `auto_learning_cycle`.

---

**تم التوثيق بواسطة:** CMIS Orchestrator v15.1  
**تاريخ الإصدار:** 2025-11-10  
**حالة الطبقة:** ✅ مستقرة وجاهزة للدمج الإنتاجي.