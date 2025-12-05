<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backup App Seeder
 *
 * Seeds the Organization Backup & Restore app in the marketplace.
 * This app is a premium system tool that allows organizations to
 * backup and restore their data.
 *
 * Usage:
 *   php artisan db:seed --class=BackupAppSeeder
 */
class BackupAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Backup & Restore app to marketplace...');

        $now = now();

        // Add the backup app to marketplace
        $app = [
            'slug' => 'org-backup-restore',
            'name_key' => 'backup.app_name',
            'description_key' => 'backup.app_description',
            'icon' => 'fa-database',
            'category' => 'system',
            'route_name' => 'orgs.backup.index',
            'route_prefix' => 'backup',
            'is_core' => false,
            'is_premium' => true,
            'sort_order' => 10, // After other system apps
            'dependencies' => '[]',
            'required_permissions' => json_encode([
                'backup.create',
                'backup.view_logs',
            ]),
            'metadata' => json_encode([
                'version' => '1.0.0',
                'min_cmis_version' => '3.0',
                'sub_routes' => [
                    'orgs.backup.create',
                    'orgs.backup.restore.index',
                    'orgs.backup.schedule.index',
                    'orgs.backup.settings',
                    'orgs.backup.logs',
                ],
                'features' => [
                    'manual_backup',
                    'scheduled_backup',
                    'selective_restore',
                    'merge_restore',
                    'full_restore',
                    'cloud_storage',
                    'encryption',
                    'rollback',
                ],
            ]),
            'is_active' => true,
            'updated_at' => $now,
            'created_at' => $now,
        ];

        // Use updateOrInsert to be idempotent
        DB::table('cmis.marketplace_apps')->updateOrInsert(
            ['slug' => $app['slug']],
            $app
        );

        $this->command->info('Backup & Restore app seeded successfully!');
    }
}
