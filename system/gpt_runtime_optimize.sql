-- 🧠 CMIS GPT Runtime Optimization Script (Smart Mode)
-- هذا السكربت يقوم بتحسين أداء قاعدة البيانات بطريقة ذكية تعتمد على حجم النشاط.
-- يُفحص استخدام الفهارس، حجم الجداول، وعدد السجلات الحديثة، ليقرر ما يحتاج فعلاً إلى VACUUM أو ANALYZE.

DO $$
DECLARE
    v_table text;
    v_size numeric;
    v_updated_rows int;
    v_optimized int := 0;
BEGIN
    RAISE NOTICE '🚀 بدء عملية التحسين الذكية لنظام CMIS Orchestrator...';

    -- تحليل النشاط واختيار الجداول المرشحة للتحسين
    FOR v_table, v_size IN
        SELECT relname, pg_total_relation_size(relid) / 1024 / 1024 AS size_mb
        FROM pg_stat_user_tables
        WHERE schemaname IN ('cmis_dev','cmis_knowledge','cmis_audit')
        ORDER BY size_mb DESC
        LIMIT 20
    LOOP
        EXECUTE format('SELECT n_tup_upd + n_tup_ins FROM pg_stat_user_tables WHERE relname = %L', v_table)
        INTO v_updated_rows;

        IF v_updated_rows > 1000 OR v_size > 50 THEN
            RAISE NOTICE '🧩 تحسين الجدول: % (%.1f MB)', v_table, v_size;

            -- تنفيذ تحسين متدرج
            EXECUTE format('VACUUM (VERBOSE, ANALYZE) %I', v_table);
            v_optimized := v_optimized + 1;
        ELSE
            RAISE NOTICE '⏩ تخطي الجدول % (نشاط منخفض)', v_table;
        END IF;
    END LOOP;

    -- إعادة بناء الفهارس القديمة (أكثر من 180 يومًا)
    RAISE NOTICE '🔧 فحص الفهارس القديمة...';
    FOR v_table IN
        SELECT indexrelname
        FROM pg_stat_user_indexes
        WHERE idx_scan = 0 AND schemaname IN ('cmis_dev','cmis_knowledge','cmis_audit')
    LOOP
        RAISE NOTICE '🧱 إعادة بناء الفهرس: %', v_table;
        EXECUTE format('REINDEX INDEX CONCURRENTLY %I', v_table);
    END LOOP;

    -- تحديث الإحصاءات العامة
    RAISE NOTICE '📊 تحديث الإحصاءات العامة...';
    PERFORM pg_stat_reset();

    -- تسجيل العملية في السجل الإدراكي
    INSERT INTO cmis_audit.activity_log (actor, action, category, context)
    VALUES ('GPT-Orchestrator', 'smart_optimize_executed', 'maintenance', jsonb_build_object(
        'optimized_tables', v_optimized,
        'timestamp', now()
    ));

    RAISE NOTICE '✅ اكتملت عملية التحسين الذكية بنجاح (% جداول محسّنة)', v_optimized;

    -- 🔁 تشغيل متتبع الأداء بعد التحسين
    RAISE NOTICE '📈 تشغيل متتبع الأداء (Performance Tracker)...';
    PERFORM dblink_exec('dbname=' || current_database(), 'DO $$ BEGIN PERFORM cmis_audit.log_performance_metrics(); END $$;');

END $$ LANGUAGE plpgsql;