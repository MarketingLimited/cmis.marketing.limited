<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditLogEvent extends Command
{
    protected $signature = 'audit:log
                            {action : The action to log}
                            {--actor= : The actor performing the action}
                            {--category=system : Event category (task|knowledge|security|system)}
                            {--context= : JSON context data}';

    protected $description = '📝 تسجيل حدث في نظام التدقيق';

    public function handle()
    {
        $action = $this->argument('action');
        $actor = $this->option('actor') ?? 'CLI';
        $category = $this->option('category');
        $context = $this->option('context');

        // Validate category
        if (!in_array($category, ['task', 'knowledge', 'security', 'system'])) {
            $this->error("❌ الفئة غير صالحة. استخدم: task, knowledge, security, أو system");
            return 1;
        }

        // Parse context if provided
        $contextData = null;
        if ($context) {
            $contextData = json_decode($context);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("❌ JSON غير صالح في context");
                return 1;
            }
        }

        try {
            DB::table('cmis_audit.activity_log')->insert([
                'actor' => $actor,
                'action' => $action,
                'context' => $contextData ? json_encode($contextData) : null,
                'category' => $category,
                'created_at' => now()
            ]);

            $icon = match($category) {
                'task' => '📋',
                'knowledge' => '🧠',
                'security' => '🔒',
                'system' => '⚙️',
                default => '📝'
            };

            $this->info("{$icon} تم تسجيل الحدث بنجاح");
            $this->line("   الفاعل: {$actor}");
            $this->line("   الإجراء: {$action}");
            $this->line("   الفئة: {$category}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ فشل تسجيل الحدث: " . $e->getMessage());
            return 1;
        }
    }
}
