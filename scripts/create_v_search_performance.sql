CREATE OR REPLACE VIEW cmis_knowledge.v_search_performance AS
SELECT 
    DATE_TRUNC('hour', created_at) AS "الساعة",
    COUNT(*) AS "عدد عمليات البحث",
    AVG(results_count)::numeric(10,2) AS "متوسط النتائج",
    ROUND(AVG(avg_similarity)::numeric, 4) AS "متوسط التشابه",
    ROUND(AVG(max_similarity)::numeric, 4) AS "أعلى تشابه",
    ROUND(AVG(min_similarity)::numeric, 4) AS "أقل تشابه",
    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY avg_similarity) AS "الوسيط",
    AVG(execution_time_ms)::numeric(10,2) AS "متوسط وقت التنفيذ (ms)",
    COUNT(*) FILTER (WHERE user_feedback = 'positive') AS "تقييمات إيجابية",
    COUNT(*) FILTER (WHERE user_feedback = 'negative') AS "تقييمات سلبية",
    COUNT(*) FILTER (WHERE user_feedback = 'neutral') AS "تقييمات محايدة"
FROM cmis_knowledge.semantic_search_logs
WHERE created_at > now() - interval '24 hours'
GROUP BY DATE_TRUNC('hour', created_at)
ORDER BY "الساعة" DESC;