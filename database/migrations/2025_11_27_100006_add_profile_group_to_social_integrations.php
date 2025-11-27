<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add profile_group_id column to integrations table (if not exists)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'cmis' AND table_name = 'integrations' AND column_name = 'profile_group_id') THEN
                    ALTER TABLE cmis.integrations ADD COLUMN profile_group_id UUID;
                END IF;
            END \$\$;
        ");

        // Create index on profile_group_id (if not exists)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_integrations_profile_group_id ON cmis.integrations(profile_group_id)');

        // Add foreign key constraint (if not exists)
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = 'integrations_profile_group_id_foreign') THEN
                    ALTER TABLE cmis.integrations ADD CONSTRAINT integrations_profile_group_id_foreign FOREIGN KEY (profile_group_id) REFERENCES cmis.profile_groups(group_id) ON DELETE SET NULL;
                END IF;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE cmis.integrations DROP CONSTRAINT IF EXISTS integrations_profile_group_id_foreign');
        DB::statement('DROP INDEX IF EXISTS cmis.idx_integrations_profile_group_id');
        DB::statement('ALTER TABLE cmis.integrations DROP COLUMN IF EXISTS profile_group_id');
    }
};
