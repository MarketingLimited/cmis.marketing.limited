<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Total rows: 1
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Chunk 1
        DB::table('cmis.roles')->insert(
            array (
  0 => 
  array (
    'role_id' => '90def48b-062e-4c13-a8d9-a0c6361d6057',
    'org_id' => NULL,
    'role_name' => 'Owner',
    'role_code' => 'owner',
    'description' => 'Organization owner with full permissions',
    'is_system' => true,
    'is_active' => true,
    'created_at' => '2025-11-13 23:00:46.099641+01',
    'created_by' => NULL,
    'deleted_at' => NULL,
    'provider' => NULL,
  ),
)
        );

    }
}
