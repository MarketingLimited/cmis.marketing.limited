<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CognitiveVitalityLog extends Command
{
    protected $signature = 'cognitive:vitality-hourly';
    protected $description = '🧠 تسجيل تلقائي لمؤشر الحيوية الإدراكية كل ساعة';

    public function handle()
    {
        try {
            DB::statement('SELECT log_cognitive_vitality();');
            $this->info('✅ تم تسجيل قراءة جديدة للحيوية الإدراكية بنجاح.');
        } catch (\Exception $e) {
            $this->error('⚠️ فشل تسجيل قراءة الحيوية الإدراكية: ' . $e->getMessage());
        }
    }
}
