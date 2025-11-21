<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * FIX: Properly create or recreate the cmis.markets view
     * This migration ensures the view exists and is properly configured
     * with all required columns from public.markets table.
     */
    public function up(): void
    {
        // Ensure public.markets table exists with timestamp columns
        if (Schema::hasTable('public.markets')) {
            // Add timestamp columns if they don't exist
            if (!Schema::hasColumn('public.markets', 'created_at')) {
                DB::statement("
                    ALTER TABLE public.markets
                    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ");
            }

            if (!Schema::hasColumn('public.markets', 'updated_at')) {
                DB::statement("
                    ALTER TABLE public.markets
                    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ");
            }
        }

        // Drop existing view if it exists (CASCADE to handle dependencies)
        DB::statement('DROP VIEW IF EXISTS cmis.markets CASCADE');

        // Create the view with all columns from public.markets
        DB::statement("
            CREATE VIEW cmis.markets AS
            SELECT
                market_id,
                market_name,
                language_code,
                currency_code,
                text_direction,
                created_at,
                updated_at
            FROM public.markets
        ");

        echo "✓ markets view created successfully\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS cmis.markets CASCADE');
    }
};
