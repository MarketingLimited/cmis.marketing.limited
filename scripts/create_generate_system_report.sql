CREATE OR REPLACE FUNCTION cmis_knowledge.generate_system_report()
RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_report jsonb;
BEGIN
    WITH stats AS (
        SELECT 
            (SELECT COUNT(*) FROM cmis_knowledge.index) AS total_knowledge,
            (SELECT COUNT(*) FROM cmis_knowledge.index WHERE topic_embedding IS NOT NULL) AS embedded_knowledge,
            (SELECT COUNT(*) FROM cmis_knowledge.intent_mappings WHERE is_active) AS active_intents,
            (SELECT COUNT(*) FROM cmis_knowledge.direction_mappings WHERE is_active) AS active_directions,
            (SELECT COUNT(*) FROM cmis_knowledge.purpose_mappings WHERE is_active) AS active_purposes,
            (SELECT COUNT(*) FROM cmis_knowledge.embedding_update_queue WHERE status = 'pending') AS pending_updates,
            (SELECT COUNT(*) FROM cmis_knowledge.embedding_update_queue WHERE status = 'failed') AS failed_updates,
            (SELECT AVG(usage_count) FROM cmis_knowledge.embeddings_cache) AS avg_cache_usage,
            (SELECT COUNT(*) FROM cmis_knowledge.semantic_search_logs WHERE created_at > now() - interval '24 hours') AS searches_24h,
            (SELECT AVG(execution_time_ms) FROM cmis_knowledge.semantic_search_logs WHERE created_at > now() - interval '24 hours') AS avg_search_time
    )
    SELECT jsonb_build_object(
        'timestamp', now(),
        'knowledge_stats', jsonb_build_object(
            'total', total_knowledge,
            'embedded', embedded_knowledge,
            'coverage_percentage', ROUND((embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) * 100, 2),
            'pending', total_knowledge - embedded_knowledge
        ),
        'intent_system', jsonb_build_object(
            'active_intents', active_intents,
            'active_directions', active_directions,
            'active_purposes', active_purposes,
            'total_mappings', active_intents + active_directions + active_purposes
        ),
        'processing', jsonb_build_object(
            'pending_updates', pending_updates,
            'failed_updates', failed_updates,
            'avg_cache_usage', ROUND(avg_cache_usage::numeric, 2)
        ),
        'performance', jsonb_build_object(
            'searches_24h', searches_24h,
            'avg_search_time_ms', ROUND(avg_search_time::numeric, 2)
        ),
        'health_status', CASE 
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.8 
                AND pending_updates < 100 
                AND failed_updates < 10 THEN 'healthy'
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.5 
                AND pending_updates < 500 
                AND failed_updates < 50 THEN 'warning'
            ELSE 'critical'
        END
    ) INTO v_report
    FROM stats;
    
    RETURN v_report;
END;
$$;