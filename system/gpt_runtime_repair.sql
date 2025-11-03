-- 🧩 CMIS GPT Runtime Repair Script (Interactive Mode)
-- هذا السكربت مصمم لإصلاح البنية التشغيلية لنظام CMIS Orchestrator عند اكتشاف خلل أو فقد في الجداول أو الدوال.
-- يعمل في الوضع التفاعلي — يصدر تحذيرات ويطلب تأكيد GPT قبل تطبيق أي تعديل هيكلي.

DO $$
DECLARE
    v_missing_tables text[] := ARRAY[]::text[];
    v_missing_functions text[] := ARRAY[]::text[];
    v_missing_views text[] := ARRAY[]::text[];
    v_table text;
    v_func text;
    v_view text;
BEGIN
    RAISE NOTICE '🔍 بدء فحص سلامة النظام الإدراكي CMIS Orchestrator...';

    -- فحص الجداول الأساسية
    FOR v_table IN SELECT unnest(ARRAY[
        'cmis_dev.dev_tasks',
        'cmis_dev.dev_logs',
        'cmis_knowledge.index',
        'cmis_audit.activity_log',
        'cmis_audit.security_logs'
    ]) LOOP
        PERFORM 1 FROM pg_tables WHERE schemaname || '.' || tablename = v_table;
        IF NOT FOUND THEN
            v_missing_tables := array_append(v_missing_tables, v_table);
        END IF;
    END LOOP;

    IF array_length(v_missing_tables, 1) IS NOT NULL THEN
        RAISE WARNING '⚠️ الجداول المفقودة: %', array_to_string(v_missing_tables, ', ');
        RAISE NOTICE 'هل ترغب في إعادة إنشائها الآن؟ (اكتب YES لتأكيد التنفيذ)';
    ELSE
        RAISE NOTICE '✅ جميع الجداول الأساسية موجودة وسليمة.';
    END IF;

    -- فحص الدوال الأساسية
    FOR v_func IN SELECT unnest(ARRAY[
        'load_context_by_priority',
        'register_knowledge',
        'create_dev_task'
    ]) LOOP
        PERFORM 1 FROM pg_proc WHERE proname = v_func;
        IF NOT FOUND THEN
            v_missing_functions := array_append(v_missing_functions, v_func);
        END IF;
    END LOOP;

    IF array_length(v_missing_functions, 1) IS NOT NULL THEN
        RAISE WARNING '⚠️ الدوال المفقودة: %', array_to_string(v_missing_functions, ', ');
        RAISE NOTICE 'هل ترغب في إعادة تحميلها من /httpdocs/functions ؟ (اكتب YES لتأكيد التنفيذ)';
    ELSE
        RAISE NOTICE '✅ جميع الدوال التشغيلية متوفرة.';
    END IF;

    -- فحص الـ Views
    FOR v_view IN SELECT unnest(ARRAY['cmis_audit.realtime_status']) LOOP
        PERFORM 1 FROM pg_views WHERE schemaname || '.' || viewname = v_view;
        IF NOT FOUND THEN
            v_missing_views := array_append(v_missing_views, v_view);
        END IF;
    END LOOP;

    IF array_length(v_missing_views, 1) IS NOT NULL THEN
        RAISE WARNING '⚠️ المشاهدات المفقودة: %', array_to_string(v_missing_views, ', ');
        RAISE NOTICE 'هل ترغب في إعادة إنشائها الآن؟ (اكتب YES لتأكيد التنفيذ)';
    ELSE
        RAISE NOTICE '✅ جميع المشاهدات موجودة.';
    END IF;

    -- فحص الفهارس الرئيسية
    PERFORM 1 FROM pg_indexes WHERE indexname = 'idx_dev_tasks_status';
    IF NOT FOUND THEN
        RAISE WARNING '⚠️ الفهرس idx_dev_tasks_status مفقود — هل ترغب في إعادة إنشائه؟';
    ELSE
        RAISE NOTICE '✅ الفهارس الرئيسية سليمة.';
    END IF;

    -- تسجيل نتيجة الفحص في سجل النظام
    INSERT INTO cmis_audit.activity_log (actor, action, context, category)
    VALUES ('GPT-Orchestrator', 'runtime_repair_check', jsonb_build_object(
        'missing_tables', v_missing_tables,
        'missing_functions', v_missing_functions,
        'missing_views', v_missing_views
    ), 'system');

    RAISE NOTICE '🔧 فحص النظام اكتمل. يمكنك الآن تأكيد الإصلاحات يدويًا أو تشغيل نسخة الصيانة الصامتة لاحقًا.';
END $$ LANGUAGE plpgsql;