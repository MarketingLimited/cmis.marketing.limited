-- 📈 CMIS GPT Runtime Performance Tracker
-- هذا السكربت يُستخدم لتسجيل وتحليل مؤشرات الأداء بعد عمليات التحسين والصيانة.
-- يقوم بجمع بيانات زمن التنفيذ، سرعة الاستعلامات، وعدد الجداول المحسّنة.

DO $$
DECLARE
    v_start_time timestamptz := clock_timestamp();
    v_end_time timestamptz;
    v_exec_duration interval;
    v_avg_query_time numeric;
    v_optimized_tables int;
BEGIN
    RAISE NOTICE '⏳ بدء جمع مؤشرات الأداء للنظام الإدراكي CMIS...';

    -- جمع البيانات من جدول النشاط الأخير (logs)
    SELECT COUNT(*) INTO v_optimized_tables
    FROM cmis_audit.activity_log
    WHERE category = 'maintenance'
      AND action LIKE '%optimize%'
      AND created_at > now() - interval '1 day';

    -- قياس متوسط زمن الاستعلامات من pg_stat_statements
    IF EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'pg_stat_statements') THEN
        SELECT round(avg(total_exec_time/calls)::numeric, 2)
        INTO v_avg_query_time
        FROM pg_stat_statements
        WHERE dbid = (SELECT oid FROM pg_database WHERE datname = current_database());
    ELSE
        v_avg_query_time := 0;
    END IF;

    v_end_time := clock_timestamp();
    v_exec_duration := v_end_time - v_start_time;

    -- حفظ نتائج الأداء
    INSERT INTO cmis_audit.activity_log (actor, action, category, context)
    VALUES ('GPT-Orchestrator', 'performance_metrics_collected', 'monitoring', jsonb_build_object(
        'optimized_tables', v_optimized_tables,
        'avg_query_time_ms', v_avg_query_time,
        'execution_duration', v_exec_duration,
        'timestamp', now()
    ));

    RAISE NOTICE '✅ تم تسجيل مؤشرات الأداء: % جداول محسّنة | متوسط زمن الاستعلام %.2f ms | مدة التنفيذ %',
        v_optimized_tables, v_avg_query_time, v_exec_duration;
END $$ LANGUAGE plpgsql;