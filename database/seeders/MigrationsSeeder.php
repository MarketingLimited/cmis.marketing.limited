<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MigrationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Total rows: 19
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Chunk 1
        DB::table('cmis.migrations')->insert(
            array (
  0 => 
  array (
    'id' => '1',
    'migration' => '0001_01_01_000000_create_users_table',
    'batch' => '1',
  ),
  1 => 
  array (
    'id' => '2',
    'migration' => '0001_01_01_000001_create_cache_table',
    'batch' => '1',
  ),
  2 => 
  array (
    'id' => '3',
    'migration' => '0001_01_01_000002_create_jobs_table',
    'batch' => '1',
  ),
  3 => 
  array (
    'id' => '4',
    'migration' => '2025_11_13_000001_create_publishing_queues_table',
    'batch' => '2',
  ),
  4 => 
  array (
    'id' => '5',
    'migration' => '2025_11_13_000002_create_post_approvals_table',
    'batch' => '2',
  ),
  5 => 
  array (
    'id' => '6',
    'migration' => '2025_11_13_000003_create_audience_templates_table',
    'batch' => '2',
  ),
  6 => 
  array (
    'id' => '7',
    'migration' => '2025_11_13_000004_create_ad_variants_table',
    'batch' => '2',
  ),
  7 => 
  array (
    'id' => '8',
    'migration' => '2025_11_13_000005_create_inbox_items_table',
    'batch' => '2',
  ),
  8 => 
  array (
    'id' => '9',
    'migration' => '2025_11_13_100000_create_publishing_queue_functions',
    'batch' => '2',
  ),
  9 => 
  array (
    'id' => '10',
    'migration' => '2025_11_13_130000_create_scheduled_reports_table',
    'batch' => '2',
  ),
  10 => 
  array (
    'id' => '11',
    'migration' => '2025_11_13_140000_create_ab_tests_table',
    'batch' => '2',
  ),
  11 => 
  array (
    'id' => '12',
    'migration' => '2025_11_13_150000_create_team_management_tables',
    'batch' => '2',
  ),
  12 => 
  array (
    'id' => '13',
    'migration' => '2025_11_13_160000_create_comments_tables',
    'batch' => '2',
  ),
  13 => 
  array (
    'id' => '14',
    'migration' => '2025_11_13_170000_create_content_library_tables',
    'batch' => '2',
  ),
  14 => 
  array (
    'id' => '15',
    'migration' => '2025_11_13_180000_create_performance_monitoring_tables',
    'batch' => '2',
  ),
  15 => 
  array (
    'id' => '16',
    'migration' => '2025_11_13_190000_create_automation_rules_table',
    'batch' => '2',
  ),
  16 => 
  array (
    'id' => '17',
    'migration' => '2025_11_13_200000_create_recurring_templates_table',
    'batch' => '2',
  ),
  17 => 
  array (
    'id' => '18',
    'migration' => '2025_11_13_210000_create_integration_hub_tables',
    'batch' => '2',
  ),
  18 => 
  array (
    'id' => '19',
    'migration' => '2025_11_13_220000_create_notifications_table',
    'batch' => '2',
  ),
)
        );

    }
}
