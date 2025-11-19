<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add code column to public.markets table (28 failures)
        $hasCode = DB::selectOne("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'markets'
            AND column_name = 'code'
        ) as exists");

        if (!$hasCode->exists) {
            DB::statement("ALTER TABLE public.markets ADD COLUMN code VARCHAR(10)");
            echo "✓ Added code column to public.markets\n";

            // Recreate the cmis.markets view to include code column
            DB::statement("DROP VIEW IF EXISTS cmis.markets CASCADE");
            DB::statement("CREATE OR REPLACE VIEW cmis.markets AS
                SELECT market_id, market_name, language_code, currency_code, text_direction, code
                FROM public.markets");
            echo "✓ Updated cmis.markets view to include code column\n";
        }

        // Add name column to cmis.permissions table (24 failures) - as alias to permission_name
        DB::statement('SET search_path TO cmis,public');
        $hasName = DB::selectOne("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = 'cmis'
            AND table_name = 'permissions'
            AND column_name = 'name'
        ) as exists");

        if (!$hasName->exists) {
            DB::statement("ALTER TABLE cmis.permissions ADD COLUMN name VARCHAR(255)");
            // Populate from permission_name
            DB::statement("UPDATE cmis.permissions SET name = permission_name WHERE name IS NULL");
            echo "✓ Added name column to cmis.permissions\n";
        }

        echo "\n✅ Added missing columns successfully!\n";
    }

    public function down(): void
    {
        DB::statement('SET search_path TO cmis,public');
        DB::statement("ALTER TABLE cmis.permissions DROP COLUMN IF EXISTS name");
        DB::statement("ALTER TABLE cmis.markets DROP COLUMN IF EXISTS code");
    }
};
