CREATE OR REPLACE VIEW cmis_knowledge.v_embedding_queue_status AS
SELECT 
    status AS "الحالة",
    COUNT(*) AS "العدد",
    AVG(retry_count)::numeric(5,2) AS "متوسط المحاولات",
    MIN(created_at) AS "أقدم طلب",
    MAX(created_at) AS "أحدث طلب",
    AVG(EXTRACT(epoch FROM (now() - created_at))/60)::numeric(10,2) AS "متوسط وقت الانتظار (دقيقة)",
    CASE status
        WHEN 'pending' THEN '⏳ في الانتظار'
        WHEN 'processing' THEN '🔄 قيد المعالجة'
        WHEN 'completed' THEN '✅ مكتمل'
        WHEN 'failed' THEN '❌ فشل'
    END AS "الوصف"
FROM cmis_knowledge.embedding_update_queue
GROUP BY status
ORDER BY 
    CASE status
        WHEN 'failed' THEN 1
        WHEN 'pending' THEN 2
        WHEN 'processing' THEN 3
        WHEN 'completed' THEN 4
    END;