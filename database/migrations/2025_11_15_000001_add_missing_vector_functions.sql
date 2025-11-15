-- ========================================
-- CMIS Vector Embeddings v2.0 - Missing Functions & Views
-- Created: 2025-11-15
-- Description: إضافة الدوال والـ Views المفقودة من الوثيقة الرسمية
-- ========================================

-- ========================================
-- 1. دالة process_embedding_queue
-- معالجة قائمة انتظار Embeddings
-- ========================================
CREATE OR REPLACE FUNCTION cmis_knowledge.process_embedding_queue(
    p_batch_size INTEGER DEFAULT 10
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    v_processed INTEGER := 0;
    v_successful INTEGER := 0;
    v_failed INTEGER := 0;
    v_rec RECORD;
    v_result JSONB;
    v_start_time TIMESTAMP := clock_timestamp();
BEGIN
    -- معالجة العناصر في قائمة الانتظار
    FOR v_rec IN
        SELECT queue_id, knowledge_id, source_table, source_field, priority
        FROM cmis_knowledge.embedding_update_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT p_batch_size
    LOOP
        BEGIN
            -- تحديث حالة العنصر إلى "processing"
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing',
                processing_started_at = NOW()
            WHERE queue_id = v_rec.queue_id;

            -- محاولة تحديث embedding للعنصر
            -- هنا يجب استدعاء دالة update_single_embedding
            PERFORM cmis_knowledge.update_single_embedding(v_rec.knowledge_id);

            -- تحديث حالة العنصر إلى "completed"
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'completed',
                processed_at = NOW()
            WHERE queue_id = v_rec.queue_id;

            v_successful := v_successful + 1;

        EXCEPTION WHEN OTHERS THEN
            -- في حالة الفشل، تحديث العنصر وزيادة retry_count
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'failed',
                retry_count = retry_count + 1,
                error_message = SQLERRM,
                processing_started_at = NULL
            WHERE queue_id = v_rec.queue_id;

            v_failed := v_failed + 1;
        END;

        v_processed := v_processed + 1;
    END LOOP;

    -- إعداد النتيجة
    v_result := jsonb_build_object(
        'status', 'success',
        'processed', v_processed,
        'successful', v_successful,
        'failed', v_failed,
        'execution_time_ms', EXTRACT(EPOCH FROM (clock_timestamp() - v_start_time)) * 1000,
        'timestamp', NOW()
    );

    -- تسجيل في السجل
    INSERT INTO cmis_knowledge.embedding_api_logs (
        api_call_type,
        response_data,
        success
    ) VALUES (
        'process_queue',
        v_result,
        TRUE
    );

    RETURN v_result;
END;
$$;

COMMENT ON FUNCTION cmis_knowledge.process_embedding_queue IS 'معالجة قائمة انتظار Embeddings بشكل دفعي';


-- ========================================
-- 2. دالة hybrid_search
-- البحث الهجين (نصي + vector)
-- ========================================
CREATE OR REPLACE FUNCTION cmis_knowledge.hybrid_search(
    p_text_query TEXT,
    p_vector_query TEXT DEFAULT NULL,
    p_weight_text NUMERIC DEFAULT 0.3,
    p_weight_vector NUMERIC DEFAULT 0.7,
    p_limit INTEGER DEFAULT 10
)
RETURNS TABLE (
    knowledge_id UUID,
    domain TEXT,
    category TEXT,
    topic TEXT,
    content TEXT,
    text_score NUMERIC,
    vector_score NUMERIC,
    combined_score NUMERIC,
    rank INTEGER
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_query_embedding VECTOR(768);
BEGIN
    -- توليد embedding للاستعلام إذا تم توفيره
    IF p_vector_query IS NOT NULL THEN
        v_query_embedding := cmis_knowledge.generate_embedding_mock(p_vector_query);
    ELSE
        v_query_embedding := cmis_knowledge.generate_embedding_mock(p_text_query);
    END IF;

    RETURN QUERY
    WITH text_search AS (
        SELECT
            ki.knowledge_id,
            ts_rank(
                to_tsvector('arabic', COALESCE(ki.topic, '') || ' ' || COALESCE(ki.keywords::text, '')),
                plainto_tsquery('arabic', p_text_query)
            ) AS text_rank
        FROM cmis_knowledge.index ki
        WHERE to_tsvector('arabic', COALESCE(ki.topic, '') || ' ' || COALESCE(ki.keywords::text, ''))
              @@ plainto_tsquery('arabic', p_text_query)
    ),
    vector_search AS (
        SELECT
            ki.knowledge_id,
            1 - (ki.topic_embedding <=> v_query_embedding) AS similarity
        FROM cmis_knowledge.index ki
        WHERE ki.topic_embedding IS NOT NULL
    ),
    combined AS (
        SELECT
            COALESCE(ts.knowledge_id, vs.knowledge_id) AS knowledge_id,
            COALESCE(ts.text_rank, 0) AS text_rank,
            COALESCE(vs.similarity, 0) AS similarity,
            (COALESCE(ts.text_rank, 0) * p_weight_text) +
            (COALESCE(vs.similarity, 0) * p_weight_vector) AS combined_rank
        FROM text_search ts
        FULL OUTER JOIN vector_search vs ON ts.knowledge_id = vs.knowledge_id
    )
    SELECT
        ki.knowledge_id,
        ki.domain,
        ki.category,
        ki.topic,
        COALESCE(kd.content, km.content, ko.content, kr.content) AS content,
        c.text_rank::NUMERIC,
        c.similarity::NUMERIC,
        c.combined_rank::NUMERIC,
        ROW_NUMBER() OVER (ORDER BY c.combined_rank DESC)::INTEGER AS rank
    FROM combined c
    JOIN cmis_knowledge.index ki ON c.knowledge_id = ki.knowledge_id
    LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
    LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
    LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
    LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
    WHERE c.combined_rank > 0
        AND ki.is_deprecated = FALSE
    ORDER BY c.combined_rank DESC
    LIMIT p_limit;
END;
$$;

COMMENT ON FUNCTION cmis_knowledge.hybrid_search IS 'البحث الهجين الذي يجمع بين البحث النصي التقليدي والبحث الدلالي بالـ embeddings';


-- ========================================
-- 3. دالة smart_context_loader_v2
-- تحميل السياق الذكي - الإصدار 2 مع دعم Embeddings
-- ========================================
CREATE OR REPLACE FUNCTION cmis_knowledge.smart_context_loader_v2(
    p_query TEXT,
    p_intent TEXT DEFAULT NULL,
    p_direction TEXT DEFAULT NULL,
    p_purpose TEXT DEFAULT NULL,
    p_domain TEXT DEFAULT NULL,
    p_category TEXT DEFAULT 'dev',
    p_token_limit INTEGER DEFAULT 5000
)
RETURNS JSONB
LANGUAGE plpgsql
AS $$
DECLARE
    v_context JSONB := '[]'::JSONB;
    v_token_count INTEGER := 0;
    v_rec RECORD;
BEGIN
    -- استخدام البحث الدلالي المتقدم
    FOR v_rec IN
        SELECT *
        FROM cmis_knowledge.semantic_search_advanced(
            p_query,
            p_intent,
            p_direction,
            p_purpose,
            p_category,
            20,  -- limit أكبر للحصول على نتائج أكثر
            0.5  -- threshold أقل لتغطية أوسع
        )
    LOOP
        -- حساب عدد التوكينات التقريبي (كل 4 أحرف = token)
        IF v_token_count + (LENGTH(v_rec.content) / 4) <= p_token_limit THEN
            v_context := v_context || jsonb_build_object(
                'knowledge_id', v_rec.knowledge_id,
                'domain', v_rec.domain,
                'topic', v_rec.topic,
                'content', v_rec.content,
                'similarity_score', v_rec.similarity_score,
                'combined_score', v_rec.combined_score,
                'metadata', v_rec.metadata
            );

            v_token_count := v_token_count + (LENGTH(v_rec.content) / 4);
        ELSE
            EXIT;  -- توقف عند الوصول للحد
        END IF;
    END LOOP;

    RETURN jsonb_build_object(
        'context', v_context,
        'total_items', jsonb_array_length(v_context),
        'estimated_tokens', v_token_count,
        'query', p_query,
        'intent', p_intent,
        'direction', p_direction,
        'purpose', p_purpose,
        'timestamp', NOW()
    );
END;
$$;

COMMENT ON FUNCTION cmis_knowledge.smart_context_loader_v2 IS 'تحميل السياق الذكي v2 مع دعم البحث الدلالي المتقدم والنوايا والمقاصد';


-- ========================================
-- 4. دالة register_knowledge_with_vectors
-- تسجيل معرفة مع vectors مخصصة
-- ========================================
CREATE OR REPLACE FUNCTION cmis_knowledge.register_knowledge_with_vectors(
    p_domain TEXT,
    p_category TEXT,
    p_topic TEXT,
    p_content TEXT,
    p_intent_vector VECTOR(768) DEFAULT NULL,
    p_direction_vector VECTOR(768) DEFAULT NULL,
    p_purpose_vector VECTOR(768) DEFAULT NULL
)
RETURNS UUID
LANGUAGE plpgsql
AS $$
DECLARE
    v_knowledge_id UUID;
    v_topic_embedding VECTOR(768);
BEGIN
    -- استخدام دالة register_knowledge الموجودة
    v_knowledge_id := public.register_knowledge(
        p_domain,
        p_category,
        p_topic,
        p_content
    );

    -- إذا لم يتم توفير vectors، توليدها
    IF p_intent_vector IS NULL THEN
        p_intent_vector := cmis_knowledge.generate_embedding_mock(p_topic);
    END IF;

    IF p_direction_vector IS NULL THEN
        p_direction_vector := cmis_knowledge.generate_embedding_mock(p_topic);
    END IF;

    IF p_purpose_vector IS NULL THEN
        p_purpose_vector := cmis_knowledge.generate_embedding_mock(p_topic);
    END IF;

    -- تحديث vectors في الـ index
    UPDATE cmis_knowledge.index
    SET
        intent_vector = p_intent_vector,
        direction_vector = p_direction_vector,
        purpose_vector = p_purpose_vector,
        topic_embedding = cmis_knowledge.generate_embedding_mock(p_topic)
    WHERE knowledge_id = v_knowledge_id;

    -- تحديث timestamp
    UPDATE cmis_knowledge.index
    SET embedding_updated_at = NOW()
    WHERE knowledge_id = v_knowledge_id;

    RETURN v_knowledge_id;
END;
$$;

COMMENT ON FUNCTION cmis_knowledge.register_knowledge_with_vectors IS 'تسجيل معرفة جديدة مع إمكانية تحديد vectors مخصصة للنية والاتجاه والمقصد';


-- ========================================
-- 5. VIEW: v_embedding_status
-- عرض حالة تغطية Embeddings
-- ========================================
CREATE OR REPLACE VIEW cmis_knowledge.v_embedding_status AS
WITH embedding_stats AS (
    SELECT
        category AS "الفئة",
        domain AS "النطاق",
        COUNT(*) AS total_records,
        COUNT(topic_embedding) AS embedded_records,
        COUNT(intent_vector) AS intent_covered,
        COUNT(direction_vector) AS direction_covered,
        COUNT(purpose_vector) AS purpose_covered,
        COUNT(topic_embedding) FILTER (WHERE embedding_updated_at > NOW() - INTERVAL '7 days') AS recently_updated
    FROM cmis_knowledge.index
    WHERE is_deprecated = FALSE
    GROUP BY category, domain
)
SELECT
    "الفئة",
    "النطاق",
    total_records AS "إجمالي السجلات",
    embedded_records AS "السجلات مع Embedding",
    ROUND((embedded_records::NUMERIC / NULLIF(total_records, 0)) * 100, 2) AS "نسبة التغطية %",
    intent_covered AS "تغطية النوايا",
    direction_covered AS "تغطية الاتجاهات",
    purpose_covered AS "تغطية المقاصد",
    recently_updated AS "محدثة حديثاً (7 أيام)",
    CASE
        WHEN (embedded_records::NUMERIC / NULLIF(total_records, 0)) >= 0.9 THEN '🟢 ممتاز'
        WHEN (embedded_records::NUMERIC / NULLIF(total_records, 0)) >= 0.7 THEN '🟡 جيد'
        WHEN (embedded_records::NUMERIC / NULLIF(total_records, 0)) >= 0.5 THEN '🟠 متوسط'
        ELSE '🔴 ضعيف'
    END AS "التقييم"
FROM embedding_stats
ORDER BY "نسبة التغطية %" DESC, "إجمالي السجلات" DESC;

COMMENT ON VIEW cmis_knowledge.v_embedding_status IS 'عرض شامل لحالة تغطية Embeddings حسب الفئة والنطاق';


-- ========================================
-- 6. VIEW: v_intent_analysis
-- تحليل النوايا وفعاليتها
-- ========================================
CREATE OR REPLACE VIEW cmis_knowledge.v_intent_analysis AS
WITH intent_usage AS (
    SELECT
        im.intent_id,
        im.intent_name,
        im.intent_name_ar,
        im.intent_description,
        im.usage_count,
        im.success_rate,
        COUNT(DISTINCT ssl.log_id) AS search_count,
        AVG(ssl.avg_similarity) AS avg_relevance,
        COUNT(CASE WHEN ssl.user_feedback = 'positive' THEN 1 END) AS positive_feedback,
        COUNT(CASE WHEN ssl.user_feedback = 'negative' THEN 1 END) AS negative_feedback
    FROM cmis_knowledge.intent_mappings im
    LEFT JOIN cmis_knowledge.semantic_search_logs ssl
        ON ssl.intent = im.intent_name
        AND ssl.created_at > NOW() - INTERVAL '30 days'
    GROUP BY im.intent_id, im.intent_name, im.intent_name_ar,
             im.intent_description, im.usage_count, im.success_rate
)
SELECT
    intent_name_ar AS "النية",
    intent_name AS "Intent Name",
    intent_description AS "الوصف",
    usage_count AS "عدد الاستخدامات",
    search_count AS "عمليات البحث (30 يوم)",
    ROUND(avg_relevance::NUMERIC * 100, 2) AS "متوسط الصلة %",
    positive_feedback AS "تقييمات إيجابية",
    negative_feedback AS "تقييمات سلبية",
    ROUND(success_rate::NUMERIC * 100, 2) AS "معدل النجاح %",
    CASE
        WHEN positive_feedback::FLOAT / NULLIF(search_count, 0) > 0.7 THEN '⭐ ممتاز'
        WHEN positive_feedback::FLOAT / NULLIF(search_count, 0) > 0.5 THEN '👍 جيد'
        WHEN positive_feedback::FLOAT / NULLIF(search_count, 0) > 0.3 THEN '📊 متوسط'
        ELSE '⚠️ يحتاج تحسين'
    END AS "التقييم"
FROM intent_usage
ORDER BY search_count DESC, avg_relevance DESC;

COMMENT ON VIEW cmis_knowledge.v_intent_analysis IS 'تحليل شامل لفعالية النوايا المختلفة وأدائها في عمليات البحث';


-- ========================================
-- إنشاء الفهارس اللازمة للأداء
-- ========================================

-- فهرس لتسريع البحث في قائمة الانتظار
CREATE INDEX IF NOT EXISTS idx_queue_status_priority
ON cmis_knowledge.embedding_update_queue(status, priority DESC, created_at);

-- فهرس للبحث النصي
CREATE INDEX IF NOT EXISTS idx_knowledge_text_search
ON cmis_knowledge.index USING gin(to_tsvector('arabic', COALESCE(topic, '') || ' ' || COALESCE(keywords::text, '')));

-- ========================================
-- Grant الصلاحيات
-- ========================================
GRANT EXECUTE ON FUNCTION cmis_knowledge.process_embedding_queue TO begin;
GRANT EXECUTE ON FUNCTION cmis_knowledge.hybrid_search TO begin;
GRANT EXECUTE ON FUNCTION cmis_knowledge.smart_context_loader_v2 TO begin;
GRANT EXECUTE ON FUNCTION cmis_knowledge.register_knowledge_with_vectors TO begin;

GRANT SELECT ON cmis_knowledge.v_embedding_status TO begin;
GRANT SELECT ON cmis_knowledge.v_intent_analysis TO begin;

-- ========================================
-- تسجيل في السجل
-- ========================================
INSERT INTO cmis_audit.logs (event_type, event_source, description, created_at)
VALUES (
    'system_upgrade',
    'vector_embeddings_v2',
    '✨ تم إضافة الدوال والـ Views المفقودة من نظام Vector Embeddings v2.0',
    NOW()
);
