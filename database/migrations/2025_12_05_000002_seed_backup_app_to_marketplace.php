<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Register Backup & Restore App in Marketplace
 *
 * This migration adds the backup application to the marketplace
 * so organizations can enable/disable it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if marketplace_apps table exists
        $tableExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables
                WHERE table_schema = 'cmis'
                AND table_name = 'marketplace_apps'
            ) as exists
        ");

        if (!$tableExists->exists) {
            return;
        }

        // Check if app already exists
        $appExists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM cmis.marketplace_apps
                WHERE slug = 'backup-restore'
            ) as exists
        ");

        if ($appExists->exists) {
            return;
        }

        // Insert the backup app
        DB::table('cmis.marketplace_apps')->insert([
            'app_id' => Str::uuid()->toString(),
            'slug' => 'backup-restore',
            'name_key' => 'apps.backup_restore.name',
            'description_key' => 'apps.backup_restore.description',
            'icon' => 'shield-check',
            'category' => 'administration',
            'route_name' => 'backup.index',
            'route_prefix' => 'backup',
            'is_core' => false,
            'is_premium' => false,
            'sort_order' => 100,
            'dependencies' => json_encode([]),
            'required_permissions' => json_encode([
                'backup.view',
                'backup.create',
                'backup.download',
                'backup.restore',
                'backup.schedule',
                'backup.settings',
            ]),
            'metadata' => json_encode([
                'version' => '1.0.0',
                'author' => 'CMIS',
                'min_cmis_version' => '3.0.0',
                'features' => [
                    'backup_data' => true,
                    'backup_files' => true,
                    'scheduled_backup' => true,
                    'selective_restore' => true,
                    'merge_restore' => true,
                    'full_restore' => true,
                    'encryption' => true,
                    'api_access' => true,
                ],
                'pricing_plans' => [
                    'free' => [
                        'monthly_backups' => 2,
                        'max_size_mb' => 500,
                        'retention_days' => 7,
                        'scheduled_backup' => false,
                        'restore_types' => ['selective'],
                        'upload_external' => false,
                        'api_access' => false,
                    ],
                    'basic' => [
                        'price_monthly' => 29,
                        'monthly_backups' => 10,
                        'max_size_mb' => 5120,
                        'retention_days' => 30,
                        'scheduled_backup' => 'weekly',
                        'restore_types' => ['selective', 'merge'],
                        'upload_external' => false,
                        'api_access' => false,
                    ],
                    'professional' => [
                        'price_monthly' => 79,
                        'monthly_backups' => -1,
                        'max_size_mb' => 51200,
                        'retention_days' => 90,
                        'scheduled_backup' => 'daily',
                        'restore_types' => ['selective', 'merge', 'full'],
                        'upload_external' => true,
                        'api_access' => false,
                    ],
                    'enterprise' => [
                        'price_monthly' => 199,
                        'monthly_backups' => -1,
                        'max_size_mb' => 512000,
                        'retention_days' => 365,
                        'scheduled_backup' => 'hourly',
                        'restore_types' => ['selective', 'merge', 'full'],
                        'upload_external' => true,
                        'api_access' => true,
                        'custom_encryption_key' => true,
                    ],
                ],
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'backup-restore')
            ->delete();
    }
};
