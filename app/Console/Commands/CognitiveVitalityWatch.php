<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CognitiveVitalityWatch extends Command
{
    protected $signature = 'cognitive:vitality-watch';
    protected $description = '🔍 مراقبة نبض الوعي الإدراكي للنظام والتأكد من استمرار الجدولة';

    public function handle()
    {
        $last = DB::table('cmis_system_health.cognitive_vitality_log')
            ->orderByDesc('recorded_at')
            ->first();

        if (!$last) {
            Log::warning('⚠️ لا توجد أي قراءات في سجل الحيوية الإدراكية.');
            DB::table('cmis_audit.logs')->insert([
                'event_type' => 'cognitive_alert',
                'event_source' => 'CognitiveVitalityWatch',
                'description' => 'لم يتم العثور على قراءات حيوية إدراكية.',
                'created_at' => now(),
            ]);
            return;
        }

        $minutesSinceLast = now()->diffInMinutes($last->recorded_at);

        if ($minutesSinceLast > 90) {
            Log::error('🚨 توقف نبض الوعي الإدراكي منذ أكثر من 90 دقيقة!');
            DB::table('cmis_audit.logs')->insert([
                'event_type' => 'cognitive_alert',
                'event_source' => 'CognitiveVitalityWatch',
                'description' => 'توقف نبض الوعي الإدراكي لأكثر من 90 دقيقة.',
                'created_at' => now(),
            ]);
        } else {
            Log::info('✅ نبض الوعي الإدراكي طبيعي. آخر قراءة منذ ' . $minutesSinceLast . ' دقيقة.');
        }
    }
}