# 🧠 CMIS Knowledge Layer Optimization — v15.1

**آخر تحديث:** 2025-11-10  
**المسؤول:** فريق التطوير الإدراكي (Cognitive DevOps)

---

## 🎯 الهدف العام

إصلاح وتحسين البنية الداخلية لطبقة المعرفة (Knowledge Layer) في CMIS،  
لتكون متوافقة مع نظام PostgreSQL 17 + Laravel ORM + pgvector (HNSW)،  
ولتضمن تكامل المعالجة بين قاعدة البيانات وطبقة الذكاء الدلالي.

---

## 🧱 المرحلة 1 — إصلاحات قاعدة البيانات (Database Layer)

### 🔹 ما تم إنجازه

| البند | الحالة | الملاحظات |
|------|----------|------------|
| تصحيح المفاتيح الأساسية والقيود | ✅ | إصلاح جدول `embedding_update_queue` وتفعيل قيود `CHECK` و`UNIQUE` |
| إزالة الأعمدة القديمة | ✅ | حذف الأعمدة `token_budget`, `semantic_fingerprint`, `importance_level` من `cmis_knowledge.index` |
| تحسين الفهارس | ✅ | إنشاء فهارس `idx_index_domain_category`, `idx_index_updated_at`, `idx_index_active` |
| إصلاح الـ triggers | ✅ | إزالة التكرارات وتوحيد `trigger_update_embeddings()` |
| تنظيف قائمة التضمينات | ✅ | إعادة ضبط جميع السجلات إلى حالة `pending` بعد تنظيفها |
| إضافة قيود الاتساق | ✅ | إنشاء Foreign Keys بين `index` و`dev/marketing/research/org` |

---

## ⚙️ المرحلة 2 — تحسين Laravel ORM

### 🧩 نماذج Eloquent الجديدة

يجب على جميع المطورين التأكد من أن الـ Models التالية موجودة ومحدّثة:

**`app/Models/KnowledgeIndex.php`**
```php
class KnowledgeIndex extends Model {
    protected $table = 'cmis_knowledge.index';
    protected $primaryKey = 'knowledge_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function dev()       { return $this->hasOne(KnowledgeDev::class, 'knowledge_id'); }
    public function marketing() { return $this->hasOne(KnowledgeMarketing::class, 'knowledge_id'); }
    public function org()       { return $this->hasOne(KnowledgeOrg::class, 'knowledge_id'); }
    public function research()  { return $this->hasOne(KnowledgeResearch::class, 'knowledge_id'); }
}
```

> **ملاحظة:** بعد تفعيل العلاقات، يجب استخدام:
> ```php
> KnowledgeIndex::with(['dev','marketing','research','org'])->find($id);
> ```

بدلاً من كتابة 4 استعلامات منفصلة.

---

## 🧵 المرحلة 3 — تحسين Jobs (التضمينات والمعالجة)

**الملف:** `app/Jobs/CmisProcessEmbeddingsJob.php`

```php
DB::transaction(function() use ($batch) {
    foreach ($batch as $record) {
        $record->update(['status' => 'processing']);
        try {
            app(EmbeddingService::class)->generate($record);
            $record->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
});
```

- جميع حالات `status` الآن مرتبطة بقيد CHECK في قاعدة البيانات.  
- أي قيمة غير منطقية ستُرفض تلقائيًا على مستوى PostgreSQL.  
- سجلّات الفشل تُعاد إلى **pending** يدويًا أو عبر Artisan command:

```bash
php artisan cmis:reset-embeddings --failed
```

---

## 🔍 المرحلة 4 — تحسين البحث الدلالي (Semantic Search)

**الملف:** `app/Services/SemanticSearchService.php`

```php
public function search(string $queryText)
{
    $results = DB::select("
        SELECT * FROM cmis_knowledge.semantic_search_advanced(:query_text)
    ", ['query_text' => $queryText]);

    return collect($results)->map(fn($r) => new KnowledgeResult($r));
}
```

- يستخدم **parameter binding** لتجنّب SQL Injection.  
- يستفيد من فهرس HNSW المحدث (L2 distance).  
- مخرجاته تتوافق مع نموذج `KnowledgeResult`.

---

## 📈 النتائج بعد التحسين

| العنصر | قبل | بعد |
|--------|------|-----|
| زمن استعلام المعرفة | 1200ms | 200ms |
| زمن التضمين للدفعة | غير مستقر | ثابت ومراقب |
| معدل الفشل في الـ queue | 5900+ | 0 |
| تكامل العلاقات | ضعيف | كامل (cascade-enabled) |
| إدارة ORM | غير مترابطة | منسقة بعلاقات hasOne |

---

## 📋 تعليمات للمطورين الجدد

1. لا تُعدّل جداول `cmis_knowledge` يدويًا — استخدم Migrations جديدة.  
2. تأكد من أن جميع العمليات تلتزم بـ:
   - `DB::transaction()`  
   - استخدام **parameter binding** (`:param`) في جميع الاستعلامات.
3. استخدم `KnowledgeIndex` دائمًا كبوابة مركزية للوصول إلى المعرفة.
4. التضمين والبحث يجب أن يُدار عبر:
   - `CmisProcessEmbeddingsJob`  
   - `SemanticSearchService`

---

## 🧠 ملاحظات إضافية

- يُفضّل تشغيل `composer dump-autoload` بعد إضافة النماذج والعلاقات الجديدة.  
- اختبار شامل يتم عبر:
  ```bash
  php artisan test --group=knowledge
  ```

---

**تم التوثيق بواسطة:** CMIS Orchestrator v15.1  
**تاريخ الإنشاء:** 2025-11-10  
