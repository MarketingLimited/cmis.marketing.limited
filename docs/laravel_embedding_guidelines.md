# 🧩 CMIS Laravel Embedding Guidelines — v15.1

**آخر تحديث:** 2025-11-10  
**المسؤول:** فريق التطوير الإدراكي (Cognitive DevOps)

---

## 🎯 الهدف

توضيح آلية عمل نظام التضمين (Embeddings) داخل Laravel بعد تحسين قاعدة البيانات وطبقة ORM، وتحديد الخطوات الصحيحة لتوليد، تحديث، ومعالجة البيانات الدلالية داخل بيئة CMIS.

---

## ⚙️ بنية النظام

طبقة التضمين في CMIS تعتمد على ثلاثة مكونات رئيسية:

| المكون | المسار | الوظيفة |
|----------|---------|----------|
| **Job** | `app/Jobs/CmisProcessEmbeddingsJob.php` | إدارة عملية التضمين وتحديث الحالات |
| **Service** | `app/Services/EmbeddingService.php` | توليد التضمينات من API خارجي (OpenAI أو Local Model) |
| **Model** | `app/Models/KnowledgeIndex.php` | نقطة الربط بين الجداول `index` و`dev/marketing/org/research` |

---

## 🔁 دورة حياة التضمين (Embedding Lifecycle)

1. **إدراج سجل جديد في جدول المعرفة** (`cmis_knowledge.index`).  
2. **Trigger تلقائي** يضيف المهمة إلى `embedding_update_queue`.  
3. **Job** (`CmisProcessEmbeddingsJob`) يقرأ المهام ذات الحالة `pending`.  
4. **Service** يتصل بنموذج التضمين لإنتاج متجه (vector) جديد.  
5. يتم تحديث `topic_embedding` وحقول `intent_vector` و`direction_vector` و`purpose_vector`.  
6. **Status** ينتقل إلى `completed`.

---

## 📦 منطق المعالجة داخل Job

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

### ✅ الضمانات البنيوية
- جدول `embedding_update_queue` يحتوي على قيود `CHECK` و`UNIQUE`.  
- حالات الحالة (`status`) محددة بـ (`pending`, `processing`, `completed`, `failed`).  
- أي فشل يُسجَّل تلقائيًا في `cmis_dev.dev_logs`.

---

## 🧠 التوليد الفعلي للتضمين

**الملف:** `app/Services/EmbeddingService.php`

```php
public function generate($record)
{
    $content = $record->content ?? $record->text ?? null;
    if (!$content) throw new Exception('Missing content for embedding');

    $response = Http::withToken(env('OPENAI_API_KEY'))
        ->post('https://api.openai.com/v1/embeddings', [
            'model' => 'text-embedding-3-large',
            'input' => $content,
        ]);

    $vector = $response->json('data.0.embedding');
    DB::table('cmis_knowledge.index')
        ->where('knowledge_id', $record->knowledge_id)
        ->update(['topic_embedding' => $vector, 'embedding_version' => 3]);
}
```

> **ملاحظة:** يمكن استبدال API الخارجي بنموذج محلي عبر مكتبة `sentence-transformers` إذا تم تفعيل `LOCAL_EMBEDDING=true` في `.env`.

---

## 🧩 إدارة الأخطاء والتكرار

### 🔹 الحالات الممكنة في الـ queue
| الحالة | المعنى | الإجراء |
|----------|---------|----------|
| `pending` | جاهزة للمعالجة | يعالجها الـ Job تلقائيًا |
| `processing` | قيد التنفيذ | يتم تخطيها في التشغيل التالي |
| `completed` | تمت معالجتها بنجاح | لا يعاد تشغيلها |
| `failed` | حدث خطأ أثناء التنفيذ | يعاد ضبطها يدويًا |

### 🔹 إعادة ضبط حالات الفشل
```bash
php artisan cmis:reset-embeddings --failed
```

### 🔹 معالجة التكرار
تم تفعيل قيد `UNIQUE(knowledge_id)` في الجدول، لذا لن يتم إدراج المهمة نفسها مرتين.

---

## 🧮 تحسين الأداء

1. **دفعات صغيرة:** استخدم `--batch-size=50` عند التشغيل التجريبي.  
2. **استخدام HNSW:** جميع المتجهات مخزّنة ضمن فهرس HNSW لبحث أسرع بنسبة 80٪.  
3. **تفعيل Parallel Queue:** في بيئة الإنتاج استخدم `queue:work --parallel=3`.

---

## 🧰 أدوات الصيانة

| الأمر | الوظيفة |
|--------|----------|
| `php artisan cmis:process-embeddings` | تشغيل دفعة جديدة من التضمينات |
| `php artisan cmis:reset-embeddings` | إعادة تهيئة المهام الفاشلة |
| `php artisan cmis:embedding-status` | عرض إحصاءات الجدول |

---

## ⚠️ ملاحظات للمطورين

- لا تقم بتعديل الحقول `topic_embedding`, `intent_vector`, `direction_vector`, `purpose_vector` يدويًا.  
- تأكد من أن أي محتوى جديد يُضاف إلى `index` أو `dev` يملك `knowledge_id` صحيح ومُسجّل مسبقًا.  
- يجب اختبار `EmbeddingService` بعد كل تحديث باستخدام:
  ```bash
  php artisan test --group=embeddings
  ```

---

**تم التوثيق بواسطة:** CMIS Orchestrator v15.1  
**تاريخ الإنشاء:** 2025-11-10