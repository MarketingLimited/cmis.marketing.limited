# 🧩 CMIS GPT Runtime Examples

هذا الملف يحتوي على أمثلة تشغيل واقعية تساعد الذكاء الصناعي (GPT) على فهم كيفية تطبيق منطق التنفيذ الإدراكي في مختلف السيناريوهات العملية ضمن نظام CMIS Orchestrator.

---

## 🎯 المثال 1: إصلاح تدفق Meta API Refresh Token

**البرومبت:**
> أصلح تدفق refresh_token في Meta API

**التحليل:**
- Domain: `meta_api`
- Category: `dev`
- Scope: `system_dev`

**السياق المستدعى:**
- وثائق Meta API (من جدول `cmis_knowledge_dev`)
- إعدادات `cmis.integrations`

**الخطة التنفيذية:**
```json
[
  {
    "order": 1,
    "action_type": "sql",
    "description": "تحليل جدول التكاملات الحالي",
    "action_body": "SELECT * FROM cmis.integrations WHERE platform='meta';"
  },
  {
    "order": 2,
    "action_type": "api",
    "description": "اختبار واجهة Meta Graph API",
    "action_body": "POST https://graph.facebook.com/v18.0/oauth/access_token"
  },
  {
    "order": 3,
    "action_type": "analysis",
    "description": "تحليل النتائج وتسجيل المعرفة الجديدة",
    "action_body": "GPT يقوم بتقييم النتيجة وإضافة درس معرفي في cmis_knowledge_dev"
  }
]
```

**النتيجة:**
```json
{
  "status": "completed",
  "confidence": 0.94,
  "effectiveness_score": 92,
  "new_knowledge_added": "meta_api.refresh_window_fix"
}
```

---

## 📈 المثال 2: إنشاء حملة Instagram لمنتج جديد

**البرومبت:**
> أنشئ حملة Instagram لترويج منتج جديد باسم CMIS Vision

**التحليل:**
- Domain: `instagram`
- Category: `marketing`
- Scope: `campaign_dev`

**السياق المستدعى:**
- معرفة تسويقية من `cmis_knowledge_marketing`
- بيانات المنظمة من `cmis_knowledge_org`

**الخطة التنفيذية:**
```json
[
  {
    "order": 1,
    "action_type": "knowledge",
    "description": "تحميل نماذج الحملات السابقة وتحليل الأداء",
    "action_body": "SELECT * FROM cmis_knowledge_marketing WHERE topic ILIKE '%instagram%';"
  },
  {
    "order": 2,
    "action_type": "analysis",
    "description": "توليد استراتيجية المحتوى بناءً على الجمهور المستهدف",
    "action_body": "GPT يقوم بإنشاء محتوى الحملة بصيغة نصية وصورية"
  },
  {
    "order": 3,
    "action_type": "api",
    "description": "نشر الحملة عبر Meta Graph API",
    "action_body": "POST https://graph.facebook.com/v18.0/{ig_account_id}/media_publish"
  }
]
```

**النتيجة:**
```json
{
  "status": "completed",
  "confidence": 0.91,
  "effectiveness_score": 88,
  "new_knowledge_added": "instagram.marketing.story_ad.success_case"
}
```

---

## 🧰 المثال 3: تحديث ملف عبر FTP

**البرومبت:**
> حدّث سكربت تكامل Instagram داخل مجلد /httpdocs/scripts

**التحليل:**
- Domain: `ftp_automation`
- Category: `dev`
- Scope: `system_maintenance`

**الخطة التنفيذية:**
```json
[
  {
    "order": 1,
    "action_type": "ftp",
    "description": "إنشاء نسخة احتياطية من السكربت",
    "action_body": "DOWNLOAD /httpdocs/scripts/instagram_api.php"
  },
  {
    "order": 2,
    "action_type": "ftp",
    "description": "رفع النسخة المحدّثة من السكربت",
    "action_body": "UPLOAD /httpdocs/scripts/instagram_api.php"
  },
  {
    "order": 3,
    "action_type": "analysis",
    "description": "التحقق من سلامة الملف بعد التحديث",
    "action_body": "GPT يقارن حجم الملف وتاريخ التعديل للتأكد من نجاح العملية"
  }
]
```

**النتيجة:**
```json
{
  "status": "completed",
  "confidence": 0.98,
  "effectiveness_score": 95,
  "new_knowledge_added": "ftp_automation.script_update_best_practices"
}
```

---

## 💡 الغرض من الملف

هذا الملف يُعد مرجعًا تشغيليًا لتدريب أي GPT جديد داخل نظام CMIS على كيفية فهم مخرجات الدوال التنفيذية والتصرف الصحيح بناءً عليها.

📍 **الموقع:** `/httpdocs/system/gpt_runtime_examples.md`