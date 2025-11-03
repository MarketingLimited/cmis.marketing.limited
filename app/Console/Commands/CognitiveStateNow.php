<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CognitiveStateNow extends Command
{
    protected $signature = 'cognitive:state-now';
    protected $description = '🧠 عرض الحالة الإدراكية اللحظية للنظام عبر الطبقات الست';

    public function handle()
    {
        $this->info('🔎 تحليل الوعي الإدراكي الجاري...');

        $data = DB::select("
            WITH
            vital AS (
              SELECT vitality_index, cognitive_state, recorded_at
              FROM cmis_system_health.cognitive_vitality_log
              ORDER BY recorded_at DESC
              LIMIT 1
            ),
            watch AS (
              SELECT description AS last_watch_event, created_at AS last_watch_time
              FROM cmis_audit.logs
              WHERE event_source = 'CognitiveVitalityWatch'
              ORDER BY created_at DESC
              LIMIT 1
            ),
            manifest AS (
              SELECT layer_name, status, confidence, last_updated
              FROM cmis_knowledge.cognitive_manifest
              ORDER BY last_updated DESC
            )
            SELECT
              m.layer_name,
              m.status,
              m.confidence,
              v.vitality_index,
              v.cognitive_state,
              v.recorded_at,
              w.last_watch_event,
              w.last_watch_time
            FROM manifest m
            CROSS JOIN vital v
            CROSS JOIN watch w
            ORDER BY m.last_updated DESC
        ");

        foreach ($data as $row) {
            $this->line("🧩 [{$row->layer_name}]  | الحالة: {$row->status} | الثقة: {$row->confidence} | الحيوية: {$row->vitality_index} | الوعي: {$row->cognitive_state}");
        }

        DB::table('cmis_audit.logs')->insert([
            'event_type' => 'cognitive_snapshot',
            'event_source' => 'CognitiveStateNow',
            'description' => '📡 تم تنفيذ استعلام لحظي لحالة الوعي الإدراكي للنظام',
            'created_at' => now(),
        ]);

        $this->info('✅ تم تحليل الحالة الإدراكية الحالية بنجاح.');
    }
}