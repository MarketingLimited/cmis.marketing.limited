<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create default super admin user.
     *
     * Default Super Admin Credentials:
     * Email: admin@cmis.test
     * Password: password
     */
    public function run(): void
    {
        // Set the admin user as super admin
        DB::statement("
            UPDATE cmis.users
            SET is_super_admin = true
            WHERE user_id = ?
        ", [SeederConstants::USER_ADMIN]);

        $this->command->info('Super Admin configured successfully!');
        $this->command->info('');
        $this->command->info('╔════════════════════════════════════════════════╗');
        $this->command->info('║       SUPER ADMIN LOGIN CREDENTIALS            ║');
        $this->command->info('╠════════════════════════════════════════════════╣');
        $this->command->info('║  Email:    admin@cmis.test                     ║');
        $this->command->info('║  Password: password                            ║');
        $this->command->info('║  URL:      /super-admin/dashboard              ║');
        $this->command->info('╚════════════════════════════════════════════════╝');
    }
}
