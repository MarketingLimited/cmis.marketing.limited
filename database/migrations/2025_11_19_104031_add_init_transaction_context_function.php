<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create init_transaction_context function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.init_transaction_context(p_user_id UUID, p_org_id UUID)
            RETURNS VOID
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                PERFORM set_config('app.current_user_id', p_user_id::text, false);
                PERFORM set_config('app.current_org_id', p_org_id::text, false);
            END;
            $$;
        ");

        // Create clear_transaction_context function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.clear_transaction_context()
            RETURNS VOID
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                PERFORM set_config('app.current_user_id', '', false);
                PERFORM set_config('app.current_org_id', '', false);
            END;
            $$;
        ");

        // Create get_current_user_id function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.get_current_user_id()
            RETURNS UUID
            LANGUAGE plpgsql
            STABLE
            AS $$
            DECLARE
                v_user_id TEXT;
            BEGIN
                v_user_id := current_setting('app.current_user_id', true);
                IF v_user_id IS NULL OR v_user_id = '' THEN
                    RETURN NULL;
                END IF;
                RETURN v_user_id::UUID;
            END;
            $$;
        ");

        // Create current_org_id function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.current_org_id()
            RETURNS UUID
            LANGUAGE plpgsql
            STABLE
            AS $$
            DECLARE
                v_org_id TEXT;
            BEGIN
                v_org_id := current_setting('app.current_org_id', true);
                IF v_org_id IS NULL OR v_org_id = '' THEN
                    RETURN NULL;
                END IF;
                RETURN v_org_id::UUID;
            END;
            $$;
        ");

        echo "✓ Created transaction context functions\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS cmis.init_transaction_context(UUID, UUID)");
        DB::statement("DROP FUNCTION IF EXISTS cmis.clear_transaction_context()");
        DB::statement("DROP FUNCTION IF EXISTS cmis.get_current_user_id()");
        DB::statement("DROP FUNCTION IF EXISTS cmis.current_org_id()");
    }
};
