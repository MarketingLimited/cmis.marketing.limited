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
        // Skip if required tables don't exist yet (migration ordering)
        if (!Schema::hasTable('cmis.orgs') || !Schema::hasTable('cmis.users')) {
            return;
        }

        // Create api_logs table for API performance tracking
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.api_logs (
                log_id UUID PRIMARY KEY,
                org_id UUID,
                user_id UUID,
                endpoint VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                response_time_ms INTEGER NOT NULL,
                status_code INTEGER,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE SET NULL
            )
        ");

        // Create index on org_id and created_at for performance queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_api_logs_org_date
            ON cmis.api_logs(org_id, created_at DESC)
        ");

        // Create index on endpoint for endpoint-specific analysis
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_api_logs_endpoint
            ON cmis.api_logs(endpoint)
        ");

        // Create index on response_time for slow request queries
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_api_logs_response_time
            ON cmis.api_logs(response_time_ms DESC)
        ");

        // Create slow_query_log table for database performance tracking
        DB::statement("
            CREATE TABLE IF NOT EXISTS cmis.slow_query_log (
                query_id UUID PRIMARY KEY,
                org_id UUID,
                query_type VARCHAR(100) NOT NULL,
                query_text TEXT,
                execution_time_ms INTEGER NOT NULL,
                logged_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE
            )
        ");

        // Create index on org_id and logged_at
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_slow_query_log_org_date
            ON cmis.slow_query_log(org_id, logged_at DESC)
        ");

        // Create index on execution_time for slowest query analysis
        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_slow_query_log_execution_time
            ON cmis.slow_query_log(execution_time_ms DESC)
        ");

        // Add comments to tables
        DB::statement("
            COMMENT ON TABLE cmis.api_logs IS 'API request performance logging - Sprint 6.1'
        ");

        DB::statement("
            COMMENT ON TABLE cmis.slow_query_log IS 'Slow database query logging - Sprint 6.1'
        ");

        // Create a function to clean old logs automatically (optional)
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.clean_old_performance_logs()
            RETURNS void AS $$
            BEGIN
                -- Delete API logs older than 30 days
                DELETE FROM cmis.api_logs WHERE created_at < NOW() - INTERVAL '30 days';

                -- Delete slow query logs older than 30 days
                DELETE FROM cmis.slow_query_log WHERE logged_at < NOW() - INTERVAL '30 days';
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            COMMENT ON FUNCTION cmis.clean_old_performance_logs() IS 'Cleanup function for performance logs - Sprint 6.1'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS cmis.clean_old_performance_logs() CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.slow_query_log CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.api_logs CASCADE");
    }
};
