CREATE OR REPLACE FUNCTION cmis_knowledge.batch_update_embeddings(
    p_batch_size integer DEFAULT 100,
    p_category text DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_count integer := 0;
    v_success integer := 0;
    v_failed integer := 0;
    v_rec record;
    v_result jsonb;
    v_start_time timestamp := clock_timestamp();
BEGIN
    -- إضافة السجلات إلى قائمة الانتظار إذا لم تكن موجودة
    INSERT INTO cmis_knowledge.embedding_update_queue (
        knowledge_id, source_table, source_field, priority
    )
    SELECT 
        knowledge_id, 
        'index', 
        'topic',
        CASE tier
            WHEN 1 THEN 10
            WHEN 2 THEN 7
            ELSE 5
        END
    FROM cmis_knowledge.index
    WHERE 
        topic_embedding IS NULL
        AND (p_category IS NULL OR category = p_category)
        AND is_deprecated = false
    ORDER BY tier ASC, last_verified_at DESC
    LIMIT p_batch_size
    ON CONFLICT DO NOTHING;

    -- معالجة السجلات في قائمة الانتظار
    FOR v_rec IN 
        SELECT queue_id, knowledge_id
        FROM cmis_knowledge.embedding_update_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT p_batch_size
    LOOP
        BEGIN
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing', processing_started_at = now()
            WHERE queue_id = v_rec.queue_id;

            -- استدعاء الدالة التي تحدّث embedding لسجل واحد
            v_result := cmis_knowledge.update_single_embedding(v_rec.knowledge_id);

            IF v_result->>'status' = 'success' THEN
                UPDATE cmis_knowledge.embedding_update_queue
                SET status = 'completed', processed_at = now()
                WHERE queue_id = v_rec.queue_id;
                v_success := v_success + 1;
            ELSE
                UPDATE cmis_knowledge.embedding_update_queue
                SET 
                    status = 'failed',
                    error_message = v_result->>'message',
                    retry_count = retry_count + 1
                WHERE queue_id = v_rec.queue_id;
                v_failed := v_failed + 1;
            END IF;

        EXCEPTION WHEN OTHERS THEN
            UPDATE cmis_knowledge.embedding_update_queue
            SET 
                status = 'failed',
                error_message = SQLERRM,
                retry_count = retry_count + 1
            WHERE queue_id = v_rec.queue_id;
            v_failed := v_failed + 1;
        END;

        v_count := v_count + 1;
    END LOOP;

    RETURN jsonb_build_object(
        'status', 'completed',
        'total_processed', v_count,
        'successful', v_success,
        'failed', v_failed,
        'execution_time_seconds', EXTRACT(EPOCH FROM (clock_timestamp() - v_start_time)),
        'timestamp', now()
    );
END;
$$;