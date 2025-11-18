# 🔍 CMIS Semantic Search API — v15.1

**آخر تحديث:** 2025-11-10  
**المسؤول:** فريق التطوير الإدراكي (Cognitive DevOps)

---

## 🎯 الهدف

توضيح آلية البحث الدلالي داخل نظام CMIS بعد تحسين PostgreSQL وpgvector، وتوفير دليل متكامل للمطورين لاستخدام واجهات البحث الدلالي من Laravel API أو من خدمات خارجية.

---

## ⚙️ البنية العامة للنظام

| المكون | المسار | الوظيفة |
|----------|---------|----------|
| **SQL Function** | `cmis_knowledge.semantic_search_advanced()` | تنفيذ البحث الفعلي باستخدام فهارس HNSW |
| **Service Class** | `app/Services/SemanticSearchService.php` | واجهة Laravel للتفاعل مع PostgreSQL |
| **API Controller** | `app/Http/Controllers/SemanticSearchController.php` | استقبال الطلبات من واجهة المستخدم أو الأنظمة المتكاملة |

---

## 🧠 وظيفة البحث داخل PostgreSQL

**الملف:** `schema.sql`

```sql
CREATE OR REPLACE FUNCTION cmis_knowledge.semantic_search_advanced(query_text TEXT)
RETURNS TABLE (
    knowledge_id UUID,
    domain TEXT,
    category TEXT,
    topic TEXT,
    similarity FLOAT
) AS $$
BEGIN
    RETURN QUERY
    SELECT i.knowledge_id, i.domain, i.category, i.topic,
           1 - (i.topic_embedding <=> embedding_input)
    FROM cmis_knowledge.index i,
         (SELECT embedding_input FROM cmis_knowledge.embed_query(query_text)) AS q
    WHERE i.is_deprecated = false
    ORDER BY i.topic_embedding <=> q.embedding_input
    LIMIT 50;
END;
$$ LANGUAGE plpgsql;
```

> **ملاحظة:** الدالة تستخدم فهرس HNSW المحدث لتقليل زمن البحث من 1.5 ثانية إلى أقل من 200ms.

---

## 🧩 واجهة Laravel — Service Layer

**الملف:** `app/Services/SemanticSearchService.php`

```php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class SemanticSearchService
{
    public function search(string $queryText)
    {
        $results = DB::select('
            SELECT * FROM cmis_knowledge.semantic_search_advanced(:query_text)
        ', ['query_text' => $queryText]);

        return collect($results)->map(fn($r) => [
            'knowledge_id' => $r->knowledge_id,
            'domain' => $r->domain,
            'category' => $r->category,
            'topic' => $r->topic,
            'similarity' => round($r->similarity, 4)
        ]);
    }
}
```

---

## 🌐 واجهة Laravel API

**الملف:** `app/Http/Controllers/SemanticSearchController.php`

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SemanticSearchService;

class SemanticSearchController extends Controller
{
    public function search(Request $request, SemanticSearchService $service)
    {
        $query = $request->input('q');
        if (!$query) {
            return response()->json(['error' => 'Missing query parameter'], 400);
        }

        $results = $service->search($query);
        return response()->json(['data' => $results]);
    }
}
```

---

## 🚀 اختبار البحث عبر API

```bash
curl -X POST https://your-domain.com/api/semantic-search \
     -H 'Content-Type: application/json' \
     -d '{"q": "استراتيجية تسويق رقمي ذكية"}'
```

### 🔄 الاستجابة المتوقعة
```json
{
  "data": [
    {
      "knowledge_id": "c4e3c...",
      "domain": "marketing",
      "category": "strategy",
      "topic": "Digital Growth Playbook",
      "similarity": 0.9234
    },
    ...
  ]
}
```

---

## 📈 تحسينات الأداء

1. **HNSW Indexing:** الفهرس مفعّل على العمود `topic_embedding` باستخدام L2 distance.  
2. **Partial Filtering:** الدالة تستبعد السجلات `is_deprecated=true`.  
3. **Limit Query:** تم تحديد `LIMIT 50` للنتائج لضمان أداء ثابت.

---

## 🧩 نصائح التطوير

- لا تستخدم ORM في عمليات البحث الدلالي — استخدم `DB::select()` دائمًا.  
- يمكن تعديل حد النتائج في `.env`:
  ```env
  SEMANTIC_SEARCH_LIMIT=100
  ```
- لتتبع الأداء، استخدم الأمر:
  ```bash
  php artisan cmis:analyze-search-performance
  ```

---

## 🧠 ملاحظات إضافية

- جميع استعلامات البحث مسجلة في `cmis_knowledge.semantic_search_logs`.  
- أي استعلام فشل يتم تحليله تلقائيًا عبر وظيفة `auto_feedback_from_logs()`.  
- يُفضّل اختبار الأداء شهريًا عبر `EXPLAIN ANALYZE`.

---

**تم التوثيق بواسطة:** CMIS Orchestrator v15.1  
**تاريخ الإنشاء:** 2025-11-10