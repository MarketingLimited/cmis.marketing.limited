CREATE OR REPLACE FUNCTION cmis_knowledge.cleanup_old_embeddings()
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
    -- حذف نتائج البحث المخزنة مؤقتًا والمنتهية الصلاحية
    DELETE FROM cmis_knowledge.semantic_search_results_cache
    WHERE expires_at < now();

    -- حذف السجلات المكتملة الأقدم من 7 أيام في قائمة التحديث
    DELETE FROM cmis_knowledge.embedding_update_queue
    WHERE status = 'completed' AND processed_at < now() - interval '7 days';

    -- حذف سجلات البحث الأقدم من 30 يومًا
    DELETE FROM cmis_knowledge.semantic_search_logs
    WHERE created_at < now() - interval '30 days';

    -- إعادة تعيين إحصائيات الاستخدام للـ cache القديم
    UPDATE cmis_knowledge.embeddings_cache
    SET usage_count = 0
    WHERE last_accessed < now() - interval '30 days';
END;
$$;