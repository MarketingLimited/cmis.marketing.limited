<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CMIS Audit & Reporting System
 *
 * This migration implements the full audit and reporting system as documented in:
 * system/gpt_runtime_audit.md
 *
 * Components:
 * - activity_log table for detailed event tracking
 * - file_backups table for file backup tracking
 * - daily_summary view for daily aggregation
 * - weekly_performance view for weekly metrics
 * - realtime_status view for real-time monitoring
 * - export_audit_report function for report export
 * - alert_check function for automatic alerts
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create activity_log table (as per documentation)
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS cmis_audit.activity_log (
                log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
                actor text,
                action text,
                context jsonb,
                category text CHECK (category IN ('task','knowledge','security','system')),
                created_at timestamptz DEFAULT now()
            )
        ");

        // Create file_backups table
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS cmis_audit.file_backups (
                backup_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
                file_path text NOT NULL,
                backup_path text NOT NULL,
                created_at timestamptz DEFAULT now(),
                metadata jsonb DEFAULT '{}'::jsonb
            )
        ");

        // Create indexes for performance
        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_activity_log_category_created
            ON cmis_audit.activity_log(category, created_at DESC);
        ");

        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_activity_log_action
            ON cmis_audit.activity_log(action, created_at DESC);
        ");

        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_activity_log_created
            ON cmis_audit.activity_log(created_at DESC);
        ");

        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_file_backups_created
            ON cmis_audit.file_backups(created_at DESC);
        ");

        // Create daily_summary view
        DB::unprepared("
            CREATE OR REPLACE VIEW cmis_audit.daily_summary AS
            SELECT
                current_date AS report_date,
                COUNT(*) FILTER (WHERE category='task') AS total_tasks,
                COUNT(*) FILTER (WHERE category='knowledge') AS knowledge_events,
                COUNT(*) FILTER (WHERE category='security') AS security_incidents,
                COUNT(*) FILTER (WHERE category='system') AS system_operations,
                COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS failed_tasks,
                COUNT(*) FILTER (WHERE category='task' AND action='task_completed') AS completed_tasks,
                ROUND(
                    (COUNT(*) FILTER (WHERE category='task' AND action='task_completed') * 100.0 /
                    NULLIF(COUNT(*) FILTER (WHERE category='task'), 0)), 2
                ) AS success_rate
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval '24 hours'
        ");

        // Create weekly_performance view
        DB::unprepared("
            CREATE OR REPLACE VIEW cmis_audit.weekly_performance AS
            SELECT
                date_trunc('week', created_at) AS week_start,
                COUNT(*) FILTER (WHERE category='task') AS total_tasks,
                COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS failed_tasks,
                COUNT(*) FILTER (WHERE category='security') AS security_alerts,
                COUNT(*) FILTER (WHERE category='knowledge') AS new_knowledge,
                ROUND(
                    (COUNT(*) FILTER (WHERE category='task' AND action='task_completed') * 100.0 /
                    NULLIF(COUNT(*) FILTER (WHERE category='task'), 0)), 2
                ) AS success_rate,
                MIN(created_at) AS period_start,
                MAX(created_at) AS period_end
            FROM cmis_audit.activity_log
            GROUP BY date_trunc('week', created_at)
            ORDER BY week_start DESC
        ");

        // Update realtime_status view (already exists in bootstrap but recreate for consistency)
        DB::unprepared("
            CREATE OR REPLACE VIEW cmis_audit.realtime_status AS
            SELECT
                COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS recent_failures,
                COUNT(*) FILTER (WHERE category='security') AS security_events,
                COUNT(*) FILTER (WHERE category='knowledge') AS knowledge_updates,
                COUNT(*) FILTER (WHERE category='task' AND action='task_completed') AS completed_tasks,
                COUNT(*) FILTER (WHERE category='system') AS system_operations,
                MAX(created_at) AS last_update
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval '1 hour'
        ");

        // Create export_audit_report function
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis_audit.export_audit_report(
                p_period text,
                p_export_path text DEFAULT '/tmp'
            )
            RETURNS TABLE(
                success boolean,
                message text,
                file_path text,
                row_count bigint
            ) AS $$
            DECLARE
                v_filename text;
                v_full_path text;
                v_query text;
                v_count bigint;
            BEGIN
                -- Generate filename with timestamp
                v_filename := 'audit_' || p_period || '_' || to_char(now(), 'YYYYMMDD_HH24MISS') || '.csv';
                v_full_path := p_export_path || '/' || v_filename;

                -- Determine which view to export
                v_query := format('SELECT * FROM cmis_audit.%I', p_period);

                -- Export to CSV
                BEGIN
                    EXECUTE format('COPY (%s) TO %L WITH CSV HEADER DELIMITER '',''', v_query, v_full_path);

                    -- Get row count
                    EXECUTE format('SELECT COUNT(*) FROM cmis_audit.%I', p_period) INTO v_count;

                    -- Log the export
                    INSERT INTO cmis_audit.activity_log (actor, action, context, category)
                    VALUES (
                        'system',
                        'report_exported',
                        jsonb_build_object(
                            'period', p_period,
                            'file_path', v_full_path,
                            'row_count', v_count
                        ),
                        'system'
                    );

                    RETURN QUERY SELECT true, 'Report exported successfully', v_full_path, v_count;
                EXCEPTION WHEN OTHERS THEN
                    RETURN QUERY SELECT false, SQLERRM, v_full_path, 0::bigint;
                END;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Create alert_check function for automatic monitoring
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis_audit.check_alerts()
            RETURNS TABLE(
                alert_type text,
                severity text,
                message text,
                current_count bigint,
                threshold bigint
            ) AS $$
            DECLARE
                v_failed_tasks bigint;
                v_security_incidents bigint;
                v_knowledge_conflicts bigint;
            BEGIN
                -- Check failed tasks (daily)
                SELECT COUNT(*) INTO v_failed_tasks
                FROM cmis_audit.activity_log
                WHERE category = 'task'
                  AND action = 'task_failed'
                  AND created_at > now() - interval '24 hours';

                IF v_failed_tasks > 10 THEN
                    RETURN QUERY SELECT
                        'failed_tasks'::text,
                        'warning'::text,
                        'عدد المهام الفاشلة تجاوز الحد المسموح'::text,
                        v_failed_tasks,
                        10::bigint;
                END IF;

                -- Check security incidents (weekly)
                SELECT COUNT(*) INTO v_security_incidents
                FROM cmis_audit.activity_log
                WHERE category = 'security'
                  AND created_at > now() - interval '7 days';

                IF v_security_incidents > 5 THEN
                    RETURN QUERY SELECT
                        'security_incidents'::text,
                        'critical'::text,
                        'حوادث أمنية تجاوزت الحد الأسبوعي'::text,
                        v_security_incidents,
                        5::bigint;
                END IF;

                -- Check knowledge conflicts (weekly)
                SELECT COUNT(*) INTO v_knowledge_conflicts
                FROM cmis_audit.activity_log
                WHERE category = 'knowledge'
                  AND action LIKE '%conflict%'
                  AND created_at > now() - interval '7 days';

                IF v_knowledge_conflicts > 3 THEN
                    RETURN QUERY SELECT
                        'knowledge_conflicts'::text,
                        'warning'::text,
                        'تضارب المعرفة يتطلب مراجعة بشرية'::text,
                        v_knowledge_conflicts,
                        3::bigint;
                END IF;

                -- If no alerts, return success message
                IF NOT FOUND THEN
                    RETURN QUERY SELECT
                        'status'::text,
                        'info'::text,
                        'جميع المؤشرات ضمن الحدود الطبيعية'::text,
                        0::bigint,
                        0::bigint;
                END IF;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Create comprehensive audit summary view
        DB::unprepared("
            CREATE OR REPLACE VIEW cmis_audit.audit_summary AS
            SELECT
                'last_24_hours' AS period,
                COUNT(*) AS total_events,
                COUNT(*) FILTER (WHERE category='task') AS tasks,
                COUNT(*) FILTER (WHERE category='knowledge') AS knowledge,
                COUNT(*) FILTER (WHERE category='security') AS security,
                COUNT(*) FILTER (WHERE category='system') AS system,
                COUNT(DISTINCT actor) AS unique_actors,
                MIN(created_at) AS period_start,
                MAX(created_at) AS period_end
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval '24 hours'

            UNION ALL

            SELECT
                'last_7_days' AS period,
                COUNT(*) AS total_events,
                COUNT(*) FILTER (WHERE category='task') AS tasks,
                COUNT(*) FILTER (WHERE category='knowledge') AS knowledge,
                COUNT(*) FILTER (WHERE category='security') AS security,
                COUNT(*) FILTER (WHERE category='system') AS system,
                COUNT(DISTINCT actor) AS unique_actors,
                MIN(created_at) AS period_start,
                MAX(created_at) AS period_end
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval '7 days'

            UNION ALL

            SELECT
                'last_30_days' AS period,
                COUNT(*) AS total_events,
                COUNT(*) FILTER (WHERE category='task') AS tasks,
                COUNT(*) FILTER (WHERE category='knowledge') AS knowledge,
                COUNT(*) FILTER (WHERE category='security') AS security,
                COUNT(*) FILTER (WHERE category='system') AS system,
                COUNT(DISTINCT actor) AS unique_actors,
                MIN(created_at) AS period_start,
                MAX(created_at) AS period_end
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval '30 days'
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS cmis_audit.check_alerts()');
        DB::unprepared('DROP FUNCTION IF EXISTS cmis_audit.export_audit_report(text, text)');
        DB::unprepared('DROP VIEW IF EXISTS cmis_audit.audit_summary');
        DB::unprepared('DROP VIEW IF EXISTS cmis_audit.realtime_status');
        DB::unprepared('DROP VIEW IF EXISTS cmis_audit.weekly_performance');
        DB::unprepared('DROP VIEW IF EXISTS cmis_audit.daily_summary');
        DB::unprepared('DROP INDEX IF EXISTS cmis_audit.idx_file_backups_created');
        DB::unprepared('DROP INDEX IF EXISTS cmis_audit.idx_activity_log_created');
        DB::unprepared('DROP INDEX IF EXISTS cmis_audit.idx_activity_log_action');
        DB::unprepared('DROP INDEX IF EXISTS cmis_audit.idx_activity_log_category_created');
        DB::unprepared('DROP TABLE IF EXISTS cmis_audit.file_backups');
        DB::unprepared('DROP TABLE IF EXISTS cmis_audit.activity_log');
    }
};
