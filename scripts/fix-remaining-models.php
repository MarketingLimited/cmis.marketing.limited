<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 Fixing Remaining Model Issues...\n\n";

// Models to fix with their tables
$modelsToFix = [
    'App\Models\Tones' => 'cmis.tones',
    'App\Models\Strategies' => 'cmis.strategies',
    'App\Models\NamingTemplates' => 'public.naming_templates',
    'App\Models\Kpis' => 'cmis.kpis',
    'App\Models\FunnelStages' => 'cmis.funnel_stages',
    'App\Models\AwarenessStages' => 'cmis.awareness_stages',
    'App\Models\Channels' => 'cmis.channels',
    'App\Models\PlaybookSteps' => 'cmis.playbook_steps',
    'App\Models\Industries' => 'cmis.industries',
    'App\Models\ProofLayers' => 'cmis.proof_layers',
    'App\Models\Playbooks' => 'cmis.playbooks',
    'App\Models\Frameworks' => 'cmis.frameworks',
    'App\Models\SystemHealth' => 'cmis.system_health',
    'App\Models\Modules' => 'public.modules',
    'App\Models\Markets' => 'cmis.markets',
    'App\Models\ChannelFormats' => 'cmis.channel_formats',
    'App\Models\ComponentTypes' => 'cmis.component_types',
];

// Views that should have $guarded = ['*'] instead of $fillable
$viewModels = [
    'App\Models\VPredictiveCognitiveHorizon',
    'App\Models\VCreativeEfficiency',
    'App\Models\VSecurityContextSummary',
    'App\Models\VUnifiedAdTargeting',
    'App\Models\VCacheStatus',
    'App\Models\VEmbeddingQueueStatus',
    'App\Models\VGlobalCognitiveIndex',
    'App\Models\VKpiSummary',
    'App\Models\VContextImpact',
    'App\Models\VCognitiveKpiTimeseries',
    'App\Models\VTemporalDashboard',
    'App\Models\VSearchPerformance',
    'App\Models\VCognitiveDashboard',
    'App\Models\VChronoEvolution',
    'App\Models\VisualDashboardView',
    'App\Models\VDeletedRecords',
    'App\Models\VCognitiveActivity',
    'App\Models\VSystemMonitoring',
    'App\Models\VCognitiveKpiGraph',
    'App\Models\VMarketingReference',
    'App\Models\VCognitiveKpi',
    'App\Models\VAiInsights',
    'App\Models\VCognitiveAdminLog',
    'App\Models\VCognitiveVitality',
];

// Models that don't exist in DB (should set guarded)
$nonExistentModels = [
    'App\Models\ContextsUnified',
    'App\Models\AuditSummary',
    'App\Models\MarketingObjectives',
];

$fixed = 0;
$errors = 0;

// Fix regular models
foreach ($modelsToFix as $modelClass => $table) {
    try {
        if (strpos($table, '.') !== false) {
            [$schema, $tableName] = explode('.', $table, 2);
        } else {
            $schema = 'public';
            $tableName = $table;
        }

        // Get columns
        $columns = DB::select("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
        ", [$schema, $tableName]);

        if (empty($columns)) {
            echo "⚠️  Skipped $modelClass: table not found\n";
            continue;
        }

        $columnNames = array_diff(
            array_column($columns, 'column_name'),
            ['created_at', 'updated_at', 'deleted_at']
        );

        // Update model
        $modelPath = str_replace(['App\\', '\\'], ['app/', '/'], $modelClass) . '.php';
        $fullPath = __DIR__ . '/../' . $modelPath;

        if (!file_exists($fullPath)) {
            echo "⚠️  Skipped $modelClass: file not found\n";
            continue;
        }

        $content = file_get_contents($fullPath);

        $fillableArray = "protected \$fillable = [\n";
        foreach ($columnNames as $col) {
            $fillableArray .= "        '{$col}',\n";
        }
        $fillableArray .= "    ];";

        if (preg_match('/protected\s+\$fillable\s*=/', $content)) {
            $content = preg_replace(
                '/protected\s+\$fillable\s*=\s*\[([^\]]*)\];/s',
                $fillableArray,
                $content,
                1
            );
        } elseif (preg_match('/(protected\s+\$table\s*=\s*[^;]+;)/', $content, $matches)) {
            $content = str_replace(
                $matches[1],
                $matches[1] . "\n\n    " . $fillableArray,
                $content
            );
        }

        file_put_contents($fullPath, $content);
        echo "✅ Fixed: $modelClass (" . count($columnNames) . " columns)\n";
        $fixed++;

    } catch (\Exception $e) {
        echo "❌ Error: $modelClass - " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Fix view models
foreach ($viewModels as $modelClass) {
    $modelPath = str_replace(['App\\', '\\'], ['app/', '/'], $modelClass) . '.php';
    $fullPath = __DIR__ . '/../' . $modelPath;

    if (!file_exists($fullPath)) {
        continue;
    }

    $content = file_get_contents($fullPath);

    // Add $guarded = ['*'] for read-only views
    if (!preg_match('/protected\s+\$guarded\s*=/', $content)) {
        if (preg_match('/(protected\s+\$table\s*=\s*[^;]+;)/', $content, $matches)) {
            $content = str_replace(
                $matches[1],
                $matches[1] . "\n    protected \$guarded = ['*'];",
                $content
            );
            file_put_contents($fullPath, $content);
            echo "✅ Fixed view: $modelClass (added \$guarded)\n";
            $fixed++;
        }
    }
}

// Fix non-existent models
foreach ($nonExistentModels as $modelClass) {
    $modelPath = str_replace(['App\\', '\\'], ['app/', '/'], $modelClass) . '.php';
    $fullPath = __DIR__ . '/../' . $modelPath;

    if (!file_exists($fullPath)) {
        continue;
    }

    $content = file_get_contents($fullPath);

    if (!preg_match('/protected\s+\$guarded\s*=/', $content) && !preg_match('/protected\s+\$fillable\s*=/', $content)) {
        if (preg_match('/(protected\s+\$table\s*=\s*[^;]+;)/', $content, $matches)) {
            $content = str_replace(
                $matches[1],
                $matches[1] . "\n    protected \$guarded = ['*'];",
                $content
            );
            file_put_contents($fullPath, $content);
            echo "✅ Fixed non-existent: $modelClass (added \$guarded)\n";
            $fixed++;
        }
    }
}

echo "\n📊 Summary:\n";
echo "✅ Fixed: $fixed models\n";
echo "❌ Errors: $errors models\n";
