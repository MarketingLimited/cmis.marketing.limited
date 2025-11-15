<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SessionsSeeder extends Seeder
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
        DB::table('cmis.sessions')->insert(
            array (
  0 => 
  array (
    'id' => 'QO1nkBQpCi7bYWcVAntzewXhB0Jbyn5PkbpdE0Dv',
    'user_id' => 'd76b3d33-4d67-4dd6-9df9-845a18ba3435',
    'ip_address' => '162.158.28.211',
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
    'payload' => 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQ1JiTE1XT2VsblRGTGd6TDlxTDhJYmNINkszMUxsN3JPOFhpVzVHQSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHBzOi8vY21pcy5rYXphYXouY29tL25vdGlmaWNhdGlvbnMvbGF0ZXN0IjtzOjU6InJvdXRlIjtzOjIwOiJub3RpZmljYXRpb25zLmxhdGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czozNjoiZDc2YjNkMzMtNGQ2Ny00ZGQ2LTlkZjktODQ1YTE4YmEzNDM1Ijt9',
    'last_activity' => '1763075441',
  ),
)
        );

    }
}
