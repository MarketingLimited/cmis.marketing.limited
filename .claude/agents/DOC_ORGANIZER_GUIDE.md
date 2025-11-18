# دليل استخدام Documentation Organizer Agent

## نظرة عامة

هذا ال Agent متخصص في تنظيم وترتيب وصيانة مستندات المشروع بشكل تلقائي. يمنع تراكم الوثائق الفوضوية في المجلد الجذر (root) ويحافظ على هيكل منظم للمستندات.

## المشكلة التي يحلها

### قبل استخدام ال Agent:
```
/
├── ACTION_PLAN.md
├── IMPLEMENTATION_PLAN.md
├── IMPLEMENTATION_COMPLETE.md
├── PROGRESS_REPORT.md
├── SESSION_SUMMARY.md
├── AUDIT_REPORT.md
├── ANALYSIS_SUMMARY.md
├── PHASE_1_COMPLETE.md
├── PHASE_2_COMPLETE.md
├── ...70+ ملف آخر في ال root!
```

### بعد استخدام ال Agent:
```
/
├── docs/
│   ├── active/           # المستندات النشطة الحالية
│   ├── archive/          # المستندات القديمة المكتملة
│   ├── api/              # وثائق API
│   ├── architecture/     # معمارية النظام
│   ├── guides/           # أدلة الاستخدام
│   └── README.md         # فهرس شامل
├── .claude/
├── app/
└── README.md
```

## كيفية الاستخدام

### 1. الاستخدام المباشر في Claude Code

```bash
# في Claude Code CLI أو Web
@cmis-doc-organizer organize all documentation in root directory
```

### 2. تنظيم أولي شامل

```markdown
@cmis-doc-organizer

أريدك أن تقوم بما يلي:
1. فحص جميع ملفات .md و .txt في المجلد الجذر
2. تصنيف كل مستند حسب نوعه (plan, report, analysis, guide, etc.)
3. نقل المستندات إلى المجلدات المنظمة المناسبة
4. أرشفة المستندات المكتملة والقديمة
5. إنشاء فهرس شامل في docs/README.md
```

### 3. أرشفة المستندات المكتملة

```markdown
@cmis-doc-organizer archive all completed phase documents and session summaries
```

### 4. دمج التقارير المكررة

```markdown
@cmis-doc-organizer

لدي العديد من ملفات PROGRESS_REPORT متعددة.
أريدك أن:
1. تجمع كل ملفات التقارير المشابهة
2. تدمجها في تقرير واحد شامل
3. تحتفظ بالنسخ القديمة في الأرشيف
```

### 5. صيانة دورية

```markdown
@cmis-doc-organizer

قم بعملية صيانة دورية:
1. افحص ال root للمستندات الجديدة
2. انقل أي مستندات في غير مكانها
3. حدّث فهرس المستندات
4. أرشف المستندات القديمة (أكثر من 30 يوم)
```

## الهيكل المنظم المعتمد

```
docs/
├── archive/                    # 📦 الأرشيف - المستندات المكتملة
│   ├── plans/                 # الخطط القديمة المنفذة
│   │   ├── implementation-plan-2024-11-01.md
│   │   └── action-plan-2024-10-15.md
│   ├── reports/               # التقارير التاريخية
│   │   ├── audit-report-2024-11.md
│   │   └── progress-report-oct.md
│   ├── sessions/              # ملخصات الجلسات السابقة
│   │   ├── session-summary-2024-11-10.md
│   │   └── session-progress-2024-11-12.md
│   └── analyses/              # التحليلات المكتملة
│       ├── gap-analysis-2024-10.md
│       └── performance-audit-2024-11.md
│
├── active/                     # 🔥 نشط - المستندات الحالية
│   ├── plans/                 # الخطط الجارية
│   │   ├── current-implementation-plan.md
│   │   └── sprint-action-plan.md
│   ├── reports/               # التقارير الحالية
│   │   └── weekly-progress-report.md
│   ├── analysis/              # التحليلات الجارية
│   │   └── ongoing-performance-analysis.md
│   └── progress/              # متابعة التقدم
│       └── current-sprint-progress.md
│
├── api/                        # 📚 API Documentation
│   ├── rest-api-reference.md
│   ├── graphql-schema.md
│   └── api-examples.md
│
├── architecture/               # 🏗️ معمارية النظام
│   ├── system-overview.md
│   ├── database-schema.md
│   ├── microservices-design.md
│   └── integration-patterns.md
│
├── guides/                     # 📖 الأدلة الإرشادية
│   ├── setup/                 # الإعداد والتنصيب
│   │   ├── local-setup.md
│   │   └── docker-setup.md
│   ├── development/           # التطوير
│   │   ├── coding-standards.md
│   │   ├── git-workflow.md
│   │   └── testing-guide.md
│   └── deployment/            # النشر
│       ├── production-deployment.md
│       └── ci-cd-pipeline.md
│
├── reference/                  # 📋 المراجع
│   ├── database/              # قواعد البيانات
│   │   ├── schema-docs.md
│   │   └── migrations-log.md
│   ├── models/                # نماذج البيانات
│   │   └── eloquent-models-reference.md
│   └── apis/                  # مراجع APIs
│       └── third-party-apis.md
│
└── README.md                   # 🗺️ الفهرس الرئيسي
```

## أمثلة على التصنيف التلقائي

| اسم الملف الأصلي | التصنيف | المسار الجديد |
|------------------|---------|---------------|
| `IMPLEMENTATION_PLAN.md` | Active Plan | `docs/active/plans/` |
| `IMPLEMENTATION_COMPLETE.md` | Archived Plan | `docs/archive/plans/` |
| `PHASE_1_COMPLETE.md` | Archived Report | `docs/archive/reports/` |
| `SESSION_SUMMARY.md` | Archived Session | `docs/archive/sessions/` |
| `PROGRESS_REPORT.md` | Active Report | `docs/active/reports/` أو Archive حسب التاريخ |
| `API_DOCUMENTATION.md` | API Docs | `docs/api/` |
| `QUICK_START.md` | Setup Guide | `docs/guides/setup/` |
| `AUDIT_REPORT.md` | Analysis | `docs/active/analysis/` أو Archive |

## القواعد التلقائية للأرشفة

### يتم الأرشفة تلقائياً إذا:
1. ✅ يحتوي الاسم على `COMPLETE` أو `COMPLETED`
2. ✅ يحتوي الاسم على `PHASE_X_COMPLETE`
3. ✅ ملفات `SESSION_*` أقدم من 30 يوم
4. ✅ تقارير `PROGRESS_*` أقدم من 30 يوم
5. ✅ المستند يشير إلى أنه مكتمل في المحتوى

### يبقى نشطاً إذا:
1. ✅ تم تعديله في آخر 30 يوم
2. ✅ يحتوي على "Current" أو "Active" في الاسم
3. ✅ لا يحتوي على إشارات للاكتمال

## دمج المستندات المكررة

### السيناريو الشائع:
```
IMPLEMENTATION_PLAN.md
IMPLEMENTATION_SUMMARY.md
IMPLEMENTATION_ROADMAP.md
IMPLEMENTATION_STATUS.md
IMPLEMENTATION_COMPLETE.md
```

### بعد الدمج:
```
docs/active/plans/current-implementation.md
docs/archive/plans/implementation-history-2024-11.md
```

ال Agent يقوم بـ:
1. قراءة كل المستندات المشابهة
2. استخراج المعلومات الفريدة من كل واحد
3. دمجها في مستند شامل ومحدث
4. أرشفة النسخ القديمة مع التواريخ

## الفهرس التلقائي (README.md)

ال Agent ينشئ فهرساً تلقائياً في `docs/README.md`:

```markdown
# CMIS Documentation Index

Last Updated: 2024-11-18

## 📋 Quick Navigation

- [Active Documentation](#active-documentation)
- [API Reference](#api-reference)
- [Architecture](#architecture)
- [Guides](#guides)
- [Archive](#archive)

## 🔥 Active Documentation

### Current Plans
- [Current Implementation Plan](active/plans/current-implementation.md) - Main implementation roadmap
- [Sprint Action Plan](active/plans/sprint-action-plan.md) - Current sprint goals

### Current Reports
- [Weekly Progress Report](active/reports/weekly-progress-report.md) - Updated weekly

## 📚 API Documentation
...

## 📦 Archive
- [Archived Plans](archive/plans/) - 12 documents
- [Historical Reports](archive/reports/) - 23 documents
- [Past Sessions](archive/sessions/) - 8 documents
```

## التكامل مع Agents الأخرى

### تلقائياً مع جميع ال Agents:

عندما يقوم أي agent آخر بإنشاء documentation، ال `cmis-doc-organizer` يضمن:

```markdown
# Example: laravel-documentation agent

# ❌ قبل:
/ARCHITECTURE_DOCUMENTATION.md  # في ال root!

# ✅ بعد:
/docs/architecture/system-architecture.md  # منظم!
```

## أوامر CLI مباشرة

```bash
# تنظيم شامل
claude-code task @cmis-doc-organizer "organize all documentation"

# أرشفة فقط
claude-code task @cmis-doc-organizer "archive completed documents"

# دمج التقارير
claude-code task @cmis-doc-organizer "consolidate all progress reports"

# تحديث الفهرس
claude-code task @cmis-doc-organizer "update documentation index"

# فحص صحة المستندات
claude-code task @cmis-doc-organizer "check documentation health"
```

## الصيانة الدورية الموصى بها

### أسبوعياً:
```markdown
@cmis-doc-organizer run weekly maintenance:
- Move any new docs from root to organized locations
- Update documentation index
```

### شهرياً:
```markdown
@cmis-doc-organizer run monthly maintenance:
- Archive old session summaries (>30 days)
- Consolidate duplicate progress reports
- Clean up outdated analyses
- Update documentation map
```

### بعد كل مشروع كبير:
```markdown
@cmis-doc-organizer finalize project documentation:
- Archive all completed phase documents
- Consolidate final reports
- Create comprehensive project summary
- Update reference documentation
```

## تقارير ال Agent

بعد التنظيم، ال Agent يقدم تقرير مثل:

```markdown
# Documentation Organization Report

## Summary
- Files organized: 73
- Files archived: 45
- Files consolidated: 12 → 3
- Active documents: 28
- Documentation health: ✅ Good

## Actions Taken
1. Created organized directory structure
2. Moved 73 files from root to appropriate locations
3. Archived 45 completed/old documents
4. Consolidated 12 duplicate reports into 3 authoritative versions
5. Created comprehensive documentation index

## Structure
docs/
├── archive/ (45 files)
├── active/ (15 files)
├── api/ (3 files)
├── architecture/ (5 files)
├── guides/ (8 files)
└── reference/ (4 files)

## Next Steps
- Review active documentation for accuracy
- Update any broken links in code
- Schedule monthly maintenance
```

## الأسئلة الشائعة

### س: هل سيحذف ال Agent أي مستندات؟
**ج:** لا، ال Agent لا يحذف أبداً. يقوم فقط بالنقل والأرشفة. جميع المستندات محفوظة في `docs/archive/`.

### س: ماذا لو أردت الاحتفاظ بملف معين في ال root؟
**ج:** يمكنك إضافة الملفات الأساسية مثل `README.md`, `LICENSE`, `.gitignore` إلى قائمة الاستثناءات.

### س: هل يعمل ال Agent تلقائياً؟
**ج:** يمكن تفعيله ليعمل تلقائياً بعد كل جلسة، أو استدعاؤه يدوياً عند الحاجة.

### س: كيف أتراجع عن التنظيم؟
**ج:** جميع العمليات موثقة ويمكن التراجع عنها باستخدام git إذا لزم الأمر.

## أمثلة من الاستخدام الواقعي

### مثال 1: مشروع CMIS الحالي
```markdown
# الحالة الحالية:
- 73 ملف .md في ال root
- مستندات مكررة ومتناثرة
- صعوبة في إيجاد المستندات الحديثة

# بعد استخدام ال Agent:
@cmis-doc-organizer organize all documentation

# النتيجة:
- Root نظيف (فقط README.md الأساسي)
- 45 مستند في الأرشيف
- 28 مستند نشط منظم
- فهرس شامل سهل التصفح
```

## الخلاصة

هذا ال Agent يوفر:
- ⏱️ **الوقت**: لا حاجة للتنظيم اليدوي
- 🎯 **الوضوح**: سهولة إيجاد المستندات
- 📦 **الأرشفة**: الاحتفاظ بالتاريخ منظماً
- 🔄 **الصيانة**: تنظيم مستمر تلقائي
- 📊 **الشفافية**: تقارير واضحة عن التغييرات

---

**نصيحة احترافية**: استخدم هذا ال Agent بعد كل مرحلة رئيسية من المشروع للحفاظ على نظافة وتنظيم المستندات! 🚀
