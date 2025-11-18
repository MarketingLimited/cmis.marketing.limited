# كيفية استخدام cmis-doc-organizer Agent

## التشغيل السريع ⚡

### الاستدعاء المباشر
```bash
@cmis-doc-organizer organize all documentation in root directory
```

## الأوامر الشائعة

### 1. تنظيم شامل (أول مرة)
```markdown
@cmis-doc-organizer

قم بتنظيم كامل للمستندات:
1. افحص جميع ملفات .md و .txt في المجلد الجذر
2. صنف كل مستند حسب نوعه
3. انقلها إلى الهيكل المنظم
4. أرشف المستندات القديمة
5. أنشئ فهرس شامل
```

### 2. أرشفة المستندات المكتملة
```markdown
@cmis-doc-organizer archive all completed documents
```

### 3. دمج المستندات المكررة
```markdown
@cmis-doc-organizer consolidate duplicate progress reports
```

### 4. صيانة دورية
```markdown
@cmis-doc-organizer run weekly maintenance
```

## أمثلة على الاستخدام

### مثال 1: مشروع CMIS (الحالة الحالية)
```markdown
المشكلة: 70+ ملف .md في ال root

الحل:
@cmis-doc-organizer

لدي أكثر من 70 ملف documentation في ال root directory.
أريدك أن:
1. تفحص كل الملفات
2. تصنفها (plans, reports, analyses, etc.)
3. تنقل كل واحد للمكان المناسب
4. تأرشف القديم والمكتمل
5. تنشئ فهرس README.md شامل

النتيجة:
✅ Root نظيف
✅ 45 مستند في docs/archive/
✅ 28 مستند في docs/active/
✅ فهرس شامل في docs/README.md
```

### مثال 2: دمج التقارير المتعددة
```markdown
المشكلة:
- IMPLEMENTATION_PLAN.md
- IMPLEMENTATION_SUMMARY.md
- IMPLEMENTATION_ROADMAP.md
- IMPLEMENTATION_STATUS.md
- IMPLEMENTATION_COMPLETE.md

الحل:
@cmis-doc-organizer

لدي 5 ملفات implementation مختلفة.
قم بدمجها في:
- ملف واحد active في docs/active/plans/
- أرشف النسخ القديمة مع التواريخ
```

### مثال 3: صيانة شهرية
```markdown
@cmis-doc-organizer

قم بصيانة شهرية:
1. افحص ال root للملفات الجديدة
2. أرشف session summaries أقدم من 30 يوم
3. دمج progress reports المكررة
4. حدّث documentation index
5. قدم تقرير بالتغييرات
```

## الهيكل الناتج

```
docs/
├── README.md (فهرس تلقائي شامل)
├── active/
│   ├── plans/ (الخطط الجارية)
│   ├── reports/ (التقارير الحالية)
│   ├── analysis/ (التحليلات الجارية)
│   └── progress/ (متابعة التقدم)
├── archive/
│   ├── plans/ (الخطط المكتملة)
│   ├── reports/ (التقارير التاريخية)
│   ├── sessions/ (ملخصات الجلسات)
│   └── analyses/ (التحليلات السابقة)
├── api/ (وثائق API)
├── architecture/ (معمارية النظام)
├── guides/ (الأدلة الإرشادية)
└── reference/ (المراجع السريعة)
```

## القواعد التلقائية

### يتم الأرشفة تلقائياً:
- ✅ ملفات بها `COMPLETE` في الاسم
- ✅ ملفات `PHASE_*_COMPLETE`
- ✅ `SESSION_*` أقدم من 30 يوم
- ✅ ملفات تشير إلى الاكتمال في المحتوى

### التصنيف التلقائي:
```
*_PLAN.md → docs/active/plans/
*_COMPLETE.md → docs/archive/plans/
*_REPORT.md → docs/active/reports/ (أو archive)
*_ANALYSIS.md → docs/active/analysis/
SESSION_*.md → docs/archive/sessions/
API_*.md → docs/api/
```

## التكامل مع Agents الأخرى

جميع ال AI agents الآن يجب أن تنتج documentation في المسارات المنظمة:

```markdown
# ❌ قبل
/NEW_IMPLEMENTATION_PLAN.md

# ✅ بعد
/docs/active/plans/current-implementation.md
```

## الفهرس التلقائي (README.md)

ال Agent ينشئ تلقائياً `docs/README.md` مع:

- 📋 Quick Navigation
- 🔥 Active Documentation (plans, reports, analyses)
- 📚 API Documentation
- 🏗️ Architecture
- 📖 Guides
- 📦 Archive (مع عدد الملفات)

## الصيانة الموصى بها

### أسبوعياً:
```markdown
@cmis-doc-organizer

صيانة أسبوعية:
- نقل أي docs جديدة من root
- تحديث الفهرس
```

### شهرياً:
```markdown
@cmis-doc-organizer

صيانة شهرية:
- أرشفة session summaries القديمة
- دمج التقارير المكررة
- تنظيف التحليلات القديمة
```

### بعد كل مرحلة كبيرة:
```markdown
@cmis-doc-organizer

إنهاء توثيق المرحلة:
- أرشفة phase documents
- دمج التقارير النهائية
- تحديث المراجع
```

## تقرير ال Agent

بعد التنظيم، تحصل على تقرير مثل:

```markdown
# Documentation Organization Report

## Summary
- Files organized: 73
- Files archived: 45
- Files consolidated: 12 → 3
- Active documents: 28

## Actions Taken
1. Created organized structure
2. Moved 73 files from root
3. Archived 45 completed documents
4. Consolidated duplicates
5. Created comprehensive index

## Structure
docs/
├── archive/ (45 files)
├── active/ (15 files)
├── api/ (3 files)
├── architecture/ (5 files)
├── guides/ (8 files)
└── reference/ (4 files)
```

## الأسئلة الشائعة

**س: هل يحذف ال Agent الملفات؟**
ج: لا أبداً. فقط ينقل ويأرشف. كل شيء محفوظ.

**س: ماذا لو أردت ملف في ال root؟**
ج: الملفات الأساسية (README.md, LICENSE, etc.) مستثناة تلقائياً.

**س: هل يعمل تلقائياً؟**
ج: يمكن تفعيله للعمل التلقائي أو الاستدعاء اليدوي.

**س: كيف أتراجع؟**
ج: استخدم git للتراجع عن أي تغييرات.

## الملفات المرجعية

- **Agent Definition**: `.claude/agents/cmis-doc-organizer.md`
- **Detailed Guide**: `.claude/agents/DOC_ORGANIZER_GUIDE.md`
- **Structure Template**: `.claude/DOC_STRUCTURE_TEMPLATE.md`
- **This File**: `.claude/AGENT_USAGE_DOC_ORGANIZER.md`

## البدء الآن

```bash
# خطوة 1: تنظيم شامل أول مرة
@cmis-doc-organizer organize all documentation in root directory

# خطوة 2: راجع النتائج
ls -la docs/

# خطوة 3: اقرأ الفهرس
cat docs/README.md

# خطوة 4: استخدم للصيانة الدورية
@cmis-doc-organizer run weekly maintenance
```

---

**ملاحظة:** هذا ال Agent يوفر ساعات من العمل اليدوي ويحافظ على نظافة وتنظيم المشروع! 🚀
