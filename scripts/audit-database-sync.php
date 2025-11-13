#!/usr/bin/env php
<?php

/**
 * Laravel Database Sync Audit Script
 *
 * هذا السكريبت يقوم بفحص شامل لجميع النماذج (Models) للتأكد من توافقها مع قاعدة البيانات
 * ويولد تقرير مفصل بالمشاكل المكتشفة والحلول المقترحة
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSyncAuditor
{
    protected $report = [];
    protected $modelsPath;
    protected $schemas = ['cmis'];

    public function __construct()
    {
        $this->modelsPath = app_path('Models');
    }

    /**
     * تشغيل التدقيق الشامل
     */
    public function run()
    {
        echo "🔍 بدء التدقيق الشامل للنماذج وقاعدة البيانات...\n\n";

        $this->auditModels();
        $this->generateReport();
    }

    /**
     * فحص جميع النماذج
     */
    protected function auditModels()
    {
        $models = $this->discoverModels();

        foreach ($models as $modelClass) {
            try {
                $this->auditModel($modelClass);
            } catch (\Exception $e) {
                $this->report['errors'][] = [
                    'model' => $modelClass,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * اكتشاف جميع النماذج
     */
    protected function discoverModels(): array
    {
        $models = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->modelsPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($this->modelsPath . '/', '', $file->getPathname());
                $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    if (!$reflection->isAbstract() && $reflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                        $models[] = $className;
                    }
                }
            }
        }

        return $models;
    }

    /**
     * فحص نموذج واحد
     */
    protected function auditModel(string $modelClass)
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $primaryKey = $model->getKeyName();

        // التحقق من وجود الجدول
        if (!$this->tableExists($table)) {
            $this->report['missing_tables'][] = [
                'model' => $modelClass,
                'table' => $table,
                'fix' => "قم بإنشاء جدول $table في قاعدة البيانات",
            ];
            return;
        }

        // فحص المفتاح الأساسي
        $this->auditPrimaryKey($modelClass, $model, $table, $primaryKey);

        // فحص الأعمدة والـ fillable
        $this->auditFillableAttributes($modelClass, $model, $table);

        // فحص العلاقات
        $this->auditRelationships($modelClass, $model);

        // فحص الـ Casts
        $this->auditCasts($modelClass, $model, $table);

        // تسجيل النجاح
        $this->report['success'][] = [
            'model' => $modelClass,
            'table' => $table,
        ];
    }

    /**
     * التحقق من وجود الجدول
     */
    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * فحص المفتاح الأساسي
     */
    protected function auditPrimaryKey($modelClass, $model, $table, $primaryKey)
    {
        $columns = Schema::getColumns($table);
        $primaryKeyColumn = collect($columns)->firstWhere('name', $primaryKey);

        if (!$primaryKeyColumn) {
            $this->report['primary_key_issues'][] = [
                'model' => $modelClass,
                'table' => $table,
                'expected_key' => $primaryKey,
                'issue' => "المفتاح الأساسي '$primaryKey' غير موجود في الجدول",
                'fix' => "تحديث \$primaryKey في النموذج أو إضافة العمود في قاعدة البيانات",
            ];
            return;
        }

        // فحص نوع المفتاح
        $isUuid = str_contains(strtolower($primaryKeyColumn['type_name'] ?? ''), 'uuid');
        $modelIncrementing = $model->getIncrementing();

        if ($isUuid && $modelIncrementing) {
            $this->report['incrementing_issues'][] = [
                'model' => $modelClass,
                'table' => $table,
                'primary_key' => $primaryKey,
                'issue' => "المفتاح الأساسي UUID لكن \$incrementing = true",
                'fix' => "public \$incrementing = false;\nprotected \$keyType = 'string';",
            ];
        }
    }

    /**
     * فحص الأعمدة القابلة للتعبئة
     */
    protected function auditFillableAttributes($modelClass, $model, $table)
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();
        $columns = collect(Schema::getColumnListing($table));

        // تحذير: $guarded فارغة = خطر أمني
        if (empty($guarded) || $guarded === ['*']) {
            $this->report['security_risks'][] = [
                'model' => $modelClass,
                'issue' => "Mass Assignment غير محمي - \$guarded فارغة",
                'severity' => 'CRITICAL',
                'fix' => "حدد protected \$fillable = [...] صراحةً",
            ];
        }

        // التحقق من $fillable مقابل الأعمدة الفعلية
        $missingInDb = collect($fillable)->diff($columns);
        if ($missingInDb->isNotEmpty()) {
            $this->report['fillable_issues'][] = [
                'model' => $modelClass,
                'table' => $table,
                'missing_columns' => $missingInDb->toArray(),
                'fix' => "احذف هذه الحقول من \$fillable أو أضفها إلى قاعدة البيانات",
            ];
        }

        // أعمدة موجودة في DB لكن غير موجودة في $fillable
        $systemColumns = ['created_at', 'updated_at', 'deleted_at', 'id'];
        $potentiallyMissing = $columns->diff($fillable)->diff($systemColumns);

        if ($potentiallyMissing->isNotEmpty()) {
            $this->report['potential_missing_fillable'][] = [
                'model' => $modelClass,
                'table' => $table,
                'columns' => $potentiallyMissing->toArray(),
                'note' => "هذه الأعمدة موجودة في DB لكن غير موجودة في \$fillable (قد تكون مقصودة)",
            ];
        }
    }

    /**
     * فحص العلاقات
     */
    protected function auditRelationships($modelClass, $model)
    {
        $reflection = new ReflectionClass($modelClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $relationships = [];
        foreach ($methods as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();
            if ($returnType && str_contains($returnType->getName(), 'Illuminate\\Database\\Eloquent\\Relations')) {
                $relationships[] = $method->getName();
            }
        }

        if (count($relationships) === 0) {
            $this->report['missing_relationships'][] = [
                'model' => $modelClass,
                'warning' => "لم يتم العثور على علاقات معرفة (قد يكون نموذج بسيط)",
            ];
        }
    }

    /**
     * فحص الـ Casts
     */
    protected function auditCasts($modelClass, $model, $table)
    {
        $casts = $model->getCasts();
        $columns = collect(Schema::getColumns($table));

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $columnType = strtolower($column['type_name'] ?? '');

            // JSON columns
            if (str_contains($columnType, 'json')) {
                if (!isset($casts[$columnName]) || !in_array($casts[$columnName], ['array', 'object', 'json'])) {
                    $this->report['cast_issues'][] = [
                        'model' => $modelClass,
                        'table' => $table,
                        'column' => $columnName,
                        'issue' => "عمود JSON لكن لا يوجد cast مناسب",
                        'fix' => "'$columnName' => 'array',",
                    ];
                }
            }

            // Boolean columns
            if (str_contains($columnType, 'bool')) {
                if (!isset($casts[$columnName]) || $casts[$columnName] !== 'boolean') {
                    $this->report['cast_issues'][] = [
                        'model' => $modelClass,
                        'table' => $table,
                        'column' => $columnName,
                        'issue' => "عمود Boolean لكن لا يوجد cast مناسب",
                        'fix' => "'$columnName' => 'boolean',",
                    ];
                }
            }
        }
    }

    /**
     * إنشاء التقرير النهائي
     */
    protected function generateReport()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 تقرير التدقيق الشامل\n";
        echo str_repeat("=", 80) . "\n\n";

        // ملخص
        $totalModels = count($this->report['success'] ?? []);
        $totalIssues =
            count($this->report['missing_tables'] ?? []) +
            count($this->report['primary_key_issues'] ?? []) +
            count($this->report['incrementing_issues'] ?? []) +
            count($this->report['security_risks'] ?? []) +
            count($this->report['fillable_issues'] ?? []) +
            count($this->report['cast_issues'] ?? []);

        echo "✅ النماذج المفحوصة: $totalModels\n";
        echo ($totalIssues > 0 ? "⚠️  " : "✅ ") . "المشاكل المكتشفة: $totalIssues\n\n";

        // المشاكل الأمنية (أولوية قصوى)
        if (!empty($this->report['security_risks'])) {
            echo "🚨 مخاطر أمنية (CRITICAL)\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['security_risks'] as $risk) {
                echo "❌ {$risk['model']}\n";
                echo "   المشكلة: {$risk['issue']}\n";
                echo "   الحل: {$risk['fix']}\n\n";
            }
        }

        // الجداول المفقودة
        if (!empty($this->report['missing_tables'])) {
            echo "📋 جداول مفقودة\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['missing_tables'] as $issue) {
                echo "❌ {$issue['model']}\n";
                echo "   الجدول: {$issue['table']}\n";
                echo "   الحل: {$issue['fix']}\n\n";
            }
        }

        // مشاكل المفتاح الأساسي
        if (!empty($this->report['primary_key_issues'])) {
            echo "🔑 مشاكل المفتاح الأساسي\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['primary_key_issues'] as $issue) {
                echo "❌ {$issue['model']}\n";
                echo "   الجدول: {$issue['table']}\n";
                echo "   المشكلة: {$issue['issue']}\n";
                echo "   الحل: {$issue['fix']}\n\n";
            }
        }

        // مشاكل Incrementing
        if (!empty($this->report['incrementing_issues'])) {
            echo "🔢 مشاكل Incrementing\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['incrementing_issues'] as $issue) {
                echo "⚠️  {$issue['model']}\n";
                echo "   الجدول: {$issue['table']}\n";
                echo "   المشكلة: {$issue['issue']}\n";
                echo "   الحل:\n{$issue['fix']}\n\n";
            }
        }

        // مشاكل Fillable
        if (!empty($this->report['fillable_issues'])) {
            echo "📝 مشاكل \$fillable\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['fillable_issues'] as $issue) {
                echo "⚠️  {$issue['model']}\n";
                echo "   الجدول: {$issue['table']}\n";
                echo "   الأعمدة المفقودة: " . implode(', ', $issue['missing_columns']) . "\n";
                echo "   الحل: {$issue['fix']}\n\n";
            }
        }

        // مشاكل Casts
        if (!empty($this->report['cast_issues'])) {
            echo "🔄 مشاكل Casts\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($this->report['cast_issues'] as $issue) {
                echo "💡 {$issue['model']}\n";
                echo "   الجدول: {$issue['table']}\n";
                echo "   العمود: {$issue['column']}\n";
                echo "   المشكلة: {$issue['issue']}\n";
                echo "   الحل: {$issue['fix']}\n\n";
            }
        }

        // خلاصة
        echo str_repeat("=", 80) . "\n";
        echo "📝 خلاصة التدقيق\n";
        echo str_repeat("=", 80) . "\n\n";

        if ($totalIssues === 0) {
            echo "✅ رائع! جميع النماذج متوافقة مع قاعدة البيانات.\n";
            echo "   لا توجد مشاكل تحتاج إلى إصلاح.\n\n";
        } else {
            echo "⚠️  يرجى معالجة المشاكل المذكورة أعلاه لضمان:\n";
            echo "   • الأمان: حماية من ثغرات Mass Assignment\n";
            echo "   • الأداء: تجنب مشاكل N+1 Query\n";
            echo "   • السلامة: تطابق النماذج مع قاعدة البيانات\n\n";
        }

        echo "🔗 للمزيد من المعلومات، راجع التقارير المرفقة.\n\n";
    }
}

// تشغيل التدقيق
$auditor = new DatabaseSyncAuditor();
$auditor->run();
