<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Backup Permissions Seeder
 *
 * Seeds the 7 permissions required for the Organization Backup & Restore app.
 *
 * Permissions:
 * - backup.create       - Create backups (manual or scheduled)
 * - backup.download     - Download backup files
 * - backup.restore      - Restore data (selective/merge)
 * - backup.restore_full - Full restore (Super Admin only, dangerous)
 * - backup.schedule     - Manage backup schedules
 * - backup.upload       - Upload external backup files
 * - backup.view_logs    - View audit logs
 *
 * Usage:
 *   php artisan db:seed --class=BackupPermissionsSeeder
 */
class BackupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding backup permissions...');

        $permissions = [
            [
                'code' => 'backup.create',
                'name' => 'Create Backups',
                'description' => 'Create manual and scheduled backups of organization data',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
            [
                'code' => 'backup.download',
                'name' => 'Download Backups',
                'description' => 'Download backup files to local device',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
            [
                'code' => 'backup.restore',
                'name' => 'Restore Data (Selective/Merge)',
                'description' => 'Restore selected data categories or merge backup data with existing',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
            [
                'code' => 'backup.restore_full',
                'name' => 'Full Restore',
                'description' => 'Perform full data restore (replaces all existing data). Super Admin only.',
                'category' => 'backup',
                'is_dangerous' => true,
            ],
            [
                'code' => 'backup.schedule',
                'name' => 'Manage Schedules',
                'description' => 'Create, edit, and delete automatic backup schedules',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
            [
                'code' => 'backup.upload',
                'name' => 'Upload External Backups',
                'description' => 'Upload backup files from external sources for restoration',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
            [
                'code' => 'backup.view_logs',
                'name' => 'View Audit Logs',
                'description' => 'View backup and restore audit logs',
                'category' => 'backup',
                'is_dangerous' => false,
            ],
        ];

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($permissions as $permission) {
            // Check if permission already exists
            $exists = DB::table('cmis.permissions')
                ->where('permission_code', $permission['code'])
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            DB::table('cmis.permissions')->insert([
                'permission_id' => Str::uuid(),
                'permission_code' => $permission['code'],
                'permission_name' => $permission['name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'is_dangerous' => $permission['is_dangerous'],
                'provider' => 'backup',
                'deleted_at' => null,
            ]);

            $insertedCount++;
        }

        $this->command->info("Backup permissions seeded: {$insertedCount} inserted, {$skippedCount} skipped (already exist)");
    }
}
