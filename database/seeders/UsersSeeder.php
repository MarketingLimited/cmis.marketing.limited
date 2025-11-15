<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
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
        DB::table('cmis.users')->insert(
            array (
  0 => 
  array (
    'user_id' => 'd76b3d33-4d67-4dd6-9df9-845a18ba3435',
    'name' => 'Admin User',
    'email' => 'admin@cmis.test',
    'email_verified_at' => '2025-11-13 21:57:08',
    'password' => '$2y$12$tNv.zZwOlIdOdlaLWbVpP.BK5jsnJpQdORklssu1bo30EiGSI3jTK',
    'remember_token' => NULL,
    'created_at' => '2025-11-13 21:57:08',
    'updated_at' => '2025-11-13 21:57:08',
    'deleted_at' => NULL,
  ),
)
        );

    }
}
