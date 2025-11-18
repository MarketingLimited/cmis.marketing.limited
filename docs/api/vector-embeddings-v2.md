# 📡 Vector Embeddings v2.0 API Documentation
## دليل API الشامل لنظام Vector Embeddings

**الإصدار:** v2.0
**التاريخ:** 2025-11-15
**Base URL:** `/api/v2/vector`

---

## 📋 جدول المحتويات

1. [المقدمة](#المقدمة)
2. [Authentication](#authentication)
3. [Search Endpoints](#search-endpoints)
4. [Knowledge Management](#knowledge-management)
5. [Processing](#processing)
6. [Monitoring & Analytics](#monitoring--analytics)
7. [Artisan Commands](#artisan-commands)
8. [Web Interface](#web-interface)
9. [أمثلة كاملة](#أمثلة-كاملة)
10. [Error Handling](#error-handling)

---

## المقدمة

يوفر API نظام Vector Embeddings v2.0 إمكانيات متقدمة للبحث الدلالي وإدارة المعرفة باستخدام:
- 🔍 **البحث الدلالي المتقدم** مع النوايا والمقاصد والاتجاهات
- 🔀 **البحث الهجين** يجمع النص والـ vectors
- 🧠 **تحميل السياق الذكي** للـ AI models
- ⚙️ **معالجة تلقائية** للـ embeddings
- 📊 **مراقبة وتحليل** شاملة

---

## Authentication

جميع endpoints تتطلب authentication عبر Laravel Sanctum أو session-based auth.

```http
Authorization: Bearer {your-api-token}
```

أو استخدام session cookies.

---

## Search Endpoints

### 1. البحث الدلالي المتقدم

**Endpoint:** `POST /api/v2/vector/semantic-search`

**Description:** بحث دلالي متقدم مع دعم النوايا والمقاصد والاتجاهات.

**Request Body:**
```json
{
  "query": "استراتيجيات زيادة المبيعات",
  "intent": "increase_sales",
  "direction": "digital_transformation",
  "purpose": "roi_maximization",
  "category": "marketing",
  "limit": 10,
  "threshold": 0.7
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `query` | string | ✅ Yes | - | نص البحث (max: 2000) |
| `intent` | string | ❌ No | null | النية (مثل: increase_sales) |
| `direction` | string | ❌ No | null | الاتجاه (مثل: digital_transformation) |
| `purpose` | string | ❌ No | null | المقصد (مثل: roi_maximization) |
| `category` | string | ❌ No | null | الفئة (marketing, dev, etc.) |
| `limit` | integer | ❌ No | 10 | عدد النتائج (1-100) |
| `threshold` | float | ❌ No | 0.7 | حد التشابه (0.0-1.0) |

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "knowledge_id": "uuid-here",
      "domain": "cmis_marketing",
      "topic": "استراتيجية التسويق الرقمي",
      "content": "محتوى المعرفة...",
      "similarity_score": 0.89,
      "intent_match": 0.92,
      "direction_match": 0.85,
      "purpose_match": 0.88,
      "combined_score": 0.89,
      "metadata": {}
    }
  ],
  "count": 1,
  "query": "استراتيجيات زيادة المبيعات",
  "filters": {
    "intent": "increase_sales",
    "direction": "digital_transformation",
    "purpose": "roi_maximization",
    "category": "marketing"
  }
}
```

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/v2/vector/semantic-search \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "query": "استراتيجيات التسويق",
    "intent": "increase_sales",
    "limit": 5
  }'
```

---

### 2. البحث الهجين

**Endpoint:** `POST /api/v2/vector/hybrid-search`

**Description:** بحث يجمع بين البحث النصي التقليدي والبحث الدلالي بالـ vectors.

**Request Body:**
```json
{
  "text_query": "marketing campaigns",
  "vector_query": null,
  "weight_text": 0.3,
  "weight_vector": 0.7,
  "limit": 10
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `text_query` | string | ✅ Yes | - | استعلام البحث النصي |
| `vector_query` | string | ❌ No | null | استعلام منفصل للـ vector |
| `weight_text` | float | ❌ No | 0.3 | وزن البحث النصي (0.0-1.0) |
| `weight_vector` | float | ❌ No | 0.7 | وزن البحث الدلالي (0.0-1.0) |
| `limit` | integer | ❌ No | 10 | عدد النتائج |

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "knowledge_id": "uuid",
      "domain": "cmis_marketing",
      "category": "marketing",
      "topic": "Campaign Strategy",
      "content": "...",
      "text_score": 0.45,
      "vector_score": 0.82,
      "combined_score": 0.71,
      "rank": 1
    }
  ],
  "count": 1,
  "search_type": "hybrid",
  "weights": {
    "text": 0.3,
    "vector": 0.7
  }
}
```

---

### 3. تحميل السياق الذكي v2

**Endpoint:** `POST /api/v2/vector/smart-context`

**Description:** تحميل سياق ذكي محسّن مع حد للتوكينات، مثالي لتغذية AI models.

**Request Body:**
```json
{
  "query": "كيفية تحسين معدل التحويل",
  "intent": "increase_sales",
  "direction": "digital_transformation",
  "purpose": "roi_maximization",
  "domain": "cmis_marketing",
  "category": "marketing",
  "token_limit": 5000
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `query` | string | ✅ Yes | - | استعلام البحث |
| `intent` | string | ❌ No | null | النية |
| `direction` | string | ❌ No | null | الاتجاه |
| `purpose` | string | ❌ No | null | المقصد |
| `domain` | string | ❌ No | null | النطاق |
| `category` | string | ❌ No | 'dev' | الفئة |
| `token_limit` | integer | ❌ No | 5000 | حد التوكينات (100-20000) |

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "context": [
      {
        "knowledge_id": "uuid",
        "domain": "cmis_marketing",
        "topic": "...",
        "content": "...",
        "similarity_score": 0.89,
        "combined_score": 0.87,
        "metadata": {}
      }
    ],
    "total_items": 12,
    "estimated_tokens": 4850,
    "query": "كيفية تحسين معدل التحويل",
    "timestamp": "2025-11-15T..."
  },
  "metadata": {
    "total_items": 12,
    "estimated_tokens": 4850,
    "token_limit": 5000
  }
}
```

---

## Knowledge Management

### 4. تسجيل معرفة مع Vectors

**Endpoint:** `POST /api/v2/vector/register-knowledge`

**Description:** تسجيل معرفة جديدة مع إمكانية تحديد vectors مخصصة.

**Request Body:**
```json
{
  "domain": "cmis_marketing",
  "category": "marketing",
  "topic": "استراتيجية المحتوى Q1 2025",
  "content": "محتوى تفصيلي للاستراتيجية...",
  "intent_vector": null,
  "direction_vector": null,
  "purpose_vector": null
}
```

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `domain` | string | ✅ Yes | النطاق (مثل: cmis_marketing) |
| `category` | string | ✅ Yes | الفئة (marketing, dev, etc.) |
| `topic` | string | ✅ Yes | عنوان المعرفة |
| `content` | string | ✅ Yes | المحتوى |
| `intent_vector` | array | ❌ No | Vector مخصص للنية (768 dimensions) |
| `direction_vector` | array | ❌ No | Vector مخصص للاتجاه |
| `purpose_vector` | array | ❌ No | Vector مخصص للمقصد |

**Response 201 Created:**
```json
{
  "success": true,
  "knowledge_id": "new-uuid-here",
  "message": "Knowledge registered successfully",
  "data": {
    "domain": "cmis_marketing",
    "category": "marketing",
    "topic": "استراتيجية المحتوى Q1 2025",
    "has_custom_vectors": false
  }
}
```

---

## Processing

### 5. معالجة قائمة الانتظار

**Endpoint:** `POST /api/v2/vector/process-queue`

**Description:** معالجة دفعة من عناصر قائمة انتظار Embeddings.

**Request Body:**
```json
{
  "batch_size": 100
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `batch_size` | integer | ❌ No | 10 | عدد العناصر للمعالجة (1-500) |

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "status": "success",
    "processed": 100,
    "successful": 98,
    "failed": 2,
    "execution_time_ms": 12345.67,
    "timestamp": "2025-11-15T..."
  },
  "summary": "Processed 100 items: 98 successful, 2 failed"
}
```

---

## Monitoring & Analytics

### 6. حالة Embeddings

**Endpoint:** `GET /api/v2/vector/embedding-status`

**Description:** عرض حالة تغطية Embeddings حسب الفئة والنطاق.

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "الفئة": "marketing",
      "النطاق": "cmis_marketing",
      "إجمالي السجلات": 1500,
      "السجلات مع Embedding": 1450,
      "نسبة التغطية %": 96.67,
      "تغطية النوايا": 1200,
      "تغطية الاتجاهات": 1100,
      "تغطية المقاصد": 1150,
      "محدثة حديثاً (7 أيام)": 250,
      "التقييم": "🟢 ممتاز"
    }
  ],
  "count": 1
}
```

---

### 7. تحليل النوايا

**Endpoint:** `GET /api/v2/vector/intent-analysis`

**Description:** تحليل شامل لفعالية النوايا المختلفة.

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "النية": "زيادة المبيعات",
      "Intent Name": "increase_sales",
      "الوصف": "استراتيجيات لزيادة المبيعات",
      "عدد الاستخدامات": 145,
      "عمليات البحث (30 يوم)": 89,
      "متوسط الصلة %": 82.5,
      "تقييمات إيجابية": 72,
      "تقييمات سلبية": 5,
      "معدل النجاح %": 85.3,
      "التقييم": "⭐ ممتاز"
    }
  ],
  "count": 1
}
```

---

### 8. حالة قائمة الانتظار

**Endpoint:** `GET /api/v2/vector/queue-status`

**Description:** حالة قائمة انتظار معالجة Embeddings.

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "الحالة": "pending",
      "العدد": 15,
      "متوسط المحاولات": 0.5,
      "أقدم طلب": "2025-11-15T10:00:00Z",
      "أحدث طلب": "2025-11-15T12:30:00Z",
      "متوسط وقت الانتظار (دقيقة)": 45.2,
      "الوصف": "⏳ في الانتظار"
    }
  ],
  "count": 1
}
```

---

### 9. أداء البحث

**Endpoint:** `GET /api/v2/vector/search-performance`

**Description:** إحصائيات أداء البحث (آخر 24 ساعة).

**Response 200 OK:**
```json
{
  "success": true,
  "data": [
    {
      "الساعة": "2025-11-15 12:00:00",
      "عدد عمليات البحث": 45,
      "متوسط النتائج": 8.5,
      "متوسط التشابه": 0.78,
      "أعلى تشابه": 0.95,
      "أقل تشابه": 0.45,
      "الوسيط": 0.76,
      "متوسط وقت التنفيذ (ms)": 123.4,
      "تقييمات إيجابية": 38,
      "تقييمات سلبية": 2,
      "تقييمات محايدة": 5
    }
  ],
  "count": 24
}
```

---

### 10. تقرير شامل للنظام

**Endpoint:** `GET /api/v2/vector/system-report`

**Description:** تقرير شامل عن حالة النظام بالكامل.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "summary": {
      "total_knowledge": 15000,
      "total_embeddings": 14500,
      "coverage_percentage": 96.67,
      "pending_queue": 15,
      "failed_items": 2
    },
    "categories": [...]
  },
  "generated_at": "2025-11-15T12:34:56Z"
}
```

---

### 11. التحقق من التثبيت

**Endpoint:** `GET /api/v2/vector/verify-installation`

**Description:** التحقق من سلامة تثبيت النظام.

**Response 200 OK:**
```json
{
  "success": true,
  "data": {
    "status": "success",
    "version": "2.0",
    "functions": [
      "semantic_search_advanced",
      "hybrid_search",
      "smart_context_loader_v2",
      "process_embedding_queue",
      ...
    ],
    "tables": [...],
    "indexes": [...],
    "views": [...]
  },
  "verified_at": "2025-11-15T12:34:56Z"
}
```

---

## Artisan Commands

### معالجة قائمة الانتظار

```bash
# معالجة دفعة واحدة
php artisan vector:process-queue --batch=100

# معالجة مستمرة
php artisan vector:process-queue --continuous --delay=60

# عرض المساعدة
php artisan vector:process-queue --help
```

**Options:**
- `--batch=N` - عدد العناصر للمعالجة (افتراضي: 100)
- `--continuous` - معالجة مستمرة
- `--delay=N` - التأخير بين الدفعات بالثواني (افتراضي: 60)

---

### البحث الهجين

```bash
# بحث أساسي
php artisan vector:hybrid-search "marketing campaigns"

# بحث مع خيارات
php artisan vector:hybrid-search "استراتيجيات التسويق" \
  --text-weight=0.4 \
  --vector-weight=0.6 \
  --limit=15

# عرض بصيغة JSON
php artisan vector:hybrid-search "query" --json
```

**Options:**
- `--vector-query=TEXT` - استعلام منفصل للـ vector
- `--text-weight=N` - وزن البحث النصي (افتراضي: 0.3)
- `--vector-weight=N` - وزن البحث الدلالي (افتراضي: 0.7)
- `--limit=N` - عدد النتائج (افتراضي: 10)
- `--json` - عرض بصيغة JSON

---

### حالة النظام

```bash
# عرض الحالة الأساسية
php artisan vector:status

# عرض تفاصيل موسعة
php artisan vector:status --detailed

# التحقق من التثبيت
php artisan vector:status --verify

# عرض بصيغة JSON
php artisan vector:status --json
```

**Options:**
- `--detailed` - عرض تفاصيل موسعة
- `--json` - عرض بصيغة JSON
- `--verify` - التحقق من سلامة التثبيت

---

## Web Interface

### الصفحات المتاحة

1. **Dashboard** - `/vector-embeddings/dashboard`
   - نظرة عامة على حالة النظام
   - إحصائيات فورية
   - رسوم بيانية تفاعلية

2. **Intent Analysis** - `/vector-embeddings/intent-analysis`
   - تحليل شامل للنوايا
   - فعالية كل نية
   - إحصائيات الاستخدام

3. **Queue Manager** - `/vector-embeddings/queue`
   - إدارة قائمة الانتظار
   - معالجة دفعية
   - عرض العناصر الفاشلة

4. **Search** - `/vector-embeddings/search`
   - واجهة بحث تفاعلية
   - دعم البحث الدلالي والهجين
   - معاينة النتائج

5. **Performance** - `/vector-embeddings/performance`
   - إحصائيات الأداء
   - رسوم بيانية للاستخدام
   - تحليل الأداء بالوقت

---

## أمثلة كاملة

### JavaScript/Fetch

```javascript
// بحث دلالي متقدم
async function semanticSearch(query, intent) {
  const response = await fetch('/api/v2/vector/semantic-search', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer YOUR_TOKEN'
    },
    body: JSON.stringify({
      query,
      intent,
      limit: 10,
      threshold: 0.7
    })
  });

  const data = await response.json();
  return data.data;
}

// استخدام
const results = await semanticSearch('استراتيجيات التسويق', 'increase_sales');
console.log(results);
```

---

### PHP/Laravel

```php
use Illuminate\Support\Facades\Http;

// بحث هجين
$response = Http::post('/api/v2/vector/hybrid-search', [
    'text_query' => 'marketing campaigns',
    'weight_text' => 0.3,
    'weight_vector' => 0.7,
    'limit' => 15
]);

$results = $response->json()['data'];

// معالجة قائمة الانتظار
$result = Http::post('/api/v2/vector/process-queue', [
    'batch_size' => 100
]);

echo "Processed: " . $result->json()['data']['processed'];
```

---

### Python

```python
import requests

# تحميل السياق الذكي
url = "https://your-domain.com/api/v2/vector/smart-context"
headers = {
    "Content-Type": "application/json",
    "Authorization": "Bearer YOUR_TOKEN"
}
data = {
    "query": "improve conversion rate",
    "intent": "increase_sales",
    "token_limit": 5000
}

response = requests.post(url, headers=headers, json=data)
context = response.json()['data']

print(f"Loaded {context['total_items']} items")
print(f"Estimated tokens: {context['estimated_tokens']}")
```

---

## Error Handling

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Internal Server Error |

### Error Response Format

```json
{
  "success": false,
  "message": "Search failed: Connection timeout",
  "errors": {
    "query": ["The query field is required"]
  }
}
```

### Common Errors

**Validation Error (422):**
```json
{
  "success": false,
  "errors": {
    "query": ["The query field is required"],
    "limit": ["The limit must be between 1 and 100"]
  }
}
```

**Server Error (500):**
```json
{
  "success": false,
  "message": "Search failed: Database connection error"
}
```

---

## Rate Limiting

- **API Endpoints:** 60 requests/minute
- **Gemini API Integration:** 60 embeddings/minute
- **Batch Processing:** 100 items/batch recommended

---

## Support & Documentation

- 📖 **Full Documentation:** `/docs/VECTOR_EMBEDDINGS_V2_STATUS_REPORT.md`
- 🔧 **Migration Script:** `/scripts/apply-vector-v2-upgrade.sh`
- 💻 **Source Code:** `/app/Http/Controllers/API/VectorEmbeddingsV2Controller.php`
- 📊 **Database Functions:** `/database/sql/all_functions.sql`

---

**آخر تحديث:** 2025-11-15
**الإصدار:** v2.0
**الحالة:** ✅ Production Ready

---

🎉 **Happy Coding!**
