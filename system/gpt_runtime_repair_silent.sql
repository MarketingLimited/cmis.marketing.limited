-- 🤖 CMIS GPT Runtime Auto-Repair Script (Silent Mode)
-- هذا السكربت يقوم بفحص وإصلاح النظام الإدراكي CMIS Orchestrator تلقائيًا دون تدخل يدوي.
-- يُستخدم في مهام الصيانة الدورية عبر Cron Job.

DO $$
DECLARE
    v_repaired_tables int := 0;
    v_repaired_functions int := 0;
    v_repaired_views int := 0;
BEGIN
    RAISE NOTICE '🔍 بدء الفحص التلقائي للنظام الإدراكي CMIS Orchestrator (Silent Mode)...';

    -- ✅ إصلاح الجداول المفقودة
    PERFORM 1 FROM pg_tables WHERE schemaname='cmis_dev' AND tablename='dev_tasks';
    IF NOT FOUND THEN
        EXECUTE 'CREATE TABLE IF NOT EXISTS cmis_dev.dev_tasks (
            task_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
            name text,
            description text,
            scope_code text,
            status text DEFAULT ''pending'',
            priority smallint DEFAULT 3,
            execution_plan jsonb,
            confidence numeric(3,2),
            effectiveness_score smallint,
            created_at timestamptz DEFAULT now(),
            updated_at timestamptz,
            result_summary text
        );';
        v_repaired_tables := v_repaired_tables + 1;
    END IF;

    PERFORM 1 FROM pg_tables WHERE schemaname='cmis_dev' AND tablename='dev_logs';
    IF NOT FOUND THEN
        EXECUTE 'CREATE TABLE IF NOT EXISTS cmis_dev.dev_logs (
            log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
            task_id uuid REFERENCES cmis_dev.dev_tasks(task_id),
            event text,
            details jsonb,
            created_at timestamptz DEFAULT now()
        );';
        v_repaired_tables := v_repaired_tables + 1;
    END IF;

    PERFORM 1 FROM pg_tables WHERE schemaname='cmis_knowledge' AND tablename='index';
    IF NOT FOUND THEN
        EXECUTE 'CREATE TABLE IF NOT EXISTS cmis_knowledge.index (
            knowledge_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
            domain text NOT NULL,
            category text NOT NULL CHECK (category IN (''dev'', ''marketing'', ''org'', ''research'')),
            topic text NOT NULL,
            keywords text[],
            tier smallint DEFAULT 2 CHECK (tier IN (1,2,3)),
            token_budget int DEFAULT 1200,
            supersedes_knowledge_id uuid,
            is_deprecated boolean DEFAULT false,
            last_verified_at timestamptz DEFAULT now()
        );';
        v_repaired_tables := v_repaired_tables + 1;
    END IF;

    -- ✅ إعادة تحميل الدوال المفقودة
    PERFORM 1 FROM pg_proc WHERE proname='load_context_by_priority';
    IF NOT FOUND THEN
        EXECUTE format($fn$
            \i '/httpdocs/functions/load_context_by_priority.sql'
        $fn$);
        v_repaired_functions := v_repaired_functions + 1;
    END IF;

    PERFORM 1 FROM pg_proc WHERE proname='register_knowledge';
    IF NOT FOUND THEN
        EXECUTE format($fn$
            \i '/httpdocs/functions/register_knowledge.sql'
        $fn$);
        v_repaired_functions := v_repaired_functions + 1;
    END IF;

    PERFORM 1 FROM pg_proc WHERE proname='create_dev_task';
    IF NOT FOUND THEN
        EXECUTE format($fn$
            \i '/httpdocs/functions/create_dev_task.sql'
        $fn$);
        v_repaired_functions := v_repaired_functions + 1;
    END IF;

    -- ✅ إصلاح المشاهدات المفقودة
    PERFORM 1 FROM pg_views WHERE viewname='realtime_status' AND schemaname='cmis_audit';
    IF NOT FOUND THEN
        EXECUTE 'CREATE OR REPLACE VIEW cmis_audit.realtime_status AS
            SELECT 
              COUNT(*) FILTER (WHERE category=''task'' AND action=''task_failed'') AS recent_failures,
              COUNT(*) FILTER (WHERE category=''security'') AS security_events,
              COUNT(*) FILTER (WHERE category=''knowledge'') AS knowledge_updates,
              MAX(created_at) AS last_update
            FROM cmis_audit.activity_log
            WHERE created_at > now() - interval ''1 hour'';';
        v_repaired_views := v_repaired_views + 1;
    END IF;

    -- ✅ تسجيل عملية الإصلاح
    INSERT INTO cmis_audit.activity_log (actor, action, context, category)
    VALUES ('GPT-Orchestrator', 'auto_repair_executed', jsonb_build_object(
        'tables_repaired', v_repaired_tables,
        'functions_repaired', v_repaired_functions,
        'views_repaired', v_repaired_views,
        'timestamp', now()
    ), 'system');

    RAISE NOTICE '✅ الإصلاح التلقائي اكتمل: تم تصحيح % جداول، % دوال، % مشاهدات.',
        v_repaired_tables, v_repaired_functions, v_repaired_views;
END $$ LANGUAGE plpgsql;