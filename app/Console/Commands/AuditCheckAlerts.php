<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCheckAlerts extends Command
{
    protected $signature = 'audit:check-alerts';

    protected $description = '🚨 فحص التنبيهات التلقائية لنظام التدقيق';

    public function handle()
    {
        $this->info('🔍 جاري فحص التنبيهات...');

        try {
            $alerts = DB::select("SELECT * FROM cmis_audit.check_alerts()");

            if (empty($alerts)) {
                $this->info('✅ لا توجد تنبيهات');
                return 0;
            }

            $this->line('');

            foreach ($alerts as $alert) {
                $icon = match($alert->severity) {
                    'critical' => '🔴',
                    'warning' => '⚠️ ',
                    'info' => '🟢',
                    default => '🔵'
                };

                $this->line("{$icon} [{$alert->severity}] {$alert->alert_type}");
                $this->line("   📝 {$alert->message}");

                if ($alert->current_count > 0) {
                    $this->line("   📊 العدد الحالي: {$alert->current_count} | الحد: {$alert->threshold}");
                }

                $this->line('');
            }

            // Log alert check
            DB::table('cmis_audit.activity_log')->insert([
                'actor' => 'system',
                'action' => 'alert_check_completed',
                'context' => json_encode([
                    'alert_count' => count($alerts),
                    'alerts' => array_map(fn($a) => [
                        'type' => $a->alert_type,
                        'severity' => $a->severity
                    ], $alerts)
                ]),
                'category' => 'system',
                'created_at' => now()
            ]);

            // Return error code if critical alerts exist
            $hasCritical = collect($alerts)->contains(fn($a) => $a->severity === 'critical');
            return $hasCritical ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("❌ خطأ في فحص التنبيهات: " . $e->getMessage());
            return 1;
        }
    }
}
