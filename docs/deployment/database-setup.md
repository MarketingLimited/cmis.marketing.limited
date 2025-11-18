# إعداد قاعدة البيانات لمنصة CMIS

يوضح هذا الدليل كيفية تهيئة اتصال Laravel بقاعدة بيانات PostgreSQL المستخرجة من الملف `database/schema.sql`.

## 1. إنشاء قاعدة البيانات واستيراد المخطط
1. أنشئ قاعدة بيانات PostgreSQL فارغة (مثلًا باسم `cmis`).
2. استورد ملف المخطط:
   ```bash
   psql -h <HOST> -U <USER> -d cmis -f database/schema.sql
   ```
3. تأكد أن المستخدم يمتلك صلاحية القراءة على جميع المخططات (`cmis`, `cmis_refactored`, `cmis_analytics`, `cmis_ai_analytics`, `cmis_ops`).

## 2. ضبط متغيرات البيئة
قم بتحديث ملف `.env` أو متغيرات البيئة في الخادم:
```env
DB_CONNECTION=pgsql
DB_HOST=<HOST>
DB_PORT=5432
DB_DATABASE=cmis
DB_USERNAME=<USER>
DB_PASSWORD=<PASSWORD>
DB_SCHEMA_SEARCH_PATH=cmis,cmis_refactored,cmis_analytics,cmis_ai_analytics,cmis_ops,public
```
يمكن تعديل المتغيرات أعلاه لتناسب إعدادك المحلي.

## 3. التحقق من الاتصال
نفّذ الأمر التالي للتأكد من قدرة Laravel على الاتصال:
```bash
php artisan migrate:status
```
يجب أن يتم جلب البيانات بشكل صحيح من الجداول والعروض (Views) المعرفة داخل المخططات.

## 4. تحديثات إضافية
- يمكن ضبط `DB_CONNECT_TIMEOUT` و`DB_SSLMODE` عند الحاجة.
- يضبط التطبيق اسم الاتصال في PostgreSQL باستخدام `DB_APPLICATION_NAME` لتسهيل مراقبة الاتصالات.
