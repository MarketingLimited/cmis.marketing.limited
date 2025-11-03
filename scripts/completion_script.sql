-- =====================================================
-- CMIS Knowledge Vector Embeddings - Completion Script
-- سكريبت إكمال تنفيذ Vector Embeddings
-- Version: 1.1
-- Date: November 2024
-- Description: إضافة المكونات المفقودة وإكمال التنفيذ
-- =====================================================

-- =====================================================
-- PHASE 1: إضافة الفهارس المفقودة (حرج للأداء)
-- =====================================================

DO $$
BEGIN
    RAISE NOTICE '=== بدء إضافة الفهارس المفقودة ===';
END $$;

-- فهارس HNSW على جدول الفهرس الرئيسي
CREATE INDEX IF NOT EXISTS idx_knowledge_topic_embedding 
ON cmis_knowledge.index 
USING hnsw (topic_embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_knowledge_intent_vector 
ON cmis_knowledge.index 
USING hnsw (intent_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_knowledge_direction_vector 
ON cmis_knowledge.index 
USING hnsw (direction_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_knowledge_purpose_vector 
ON cmis_knowledge.index 
USING hnsw (purpose_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

-- فهرس على التحقق والثقة
CREATE INDEX IF NOT EXISTS idx_knowledge_verification 
ON cmis_knowledge.index (verification_confidence, is_verified_by_ai);

-- فهرس مركب للبحث المتقدم
CREATE INDEX IF NOT EXISTS idx_knowledge_composite 
ON cmis_knowledge.index (category, tier, is_deprecated)
WHERE is_deprecated = false;

-- =====================================================
-- PHASE 2: إنشاء جداول الدعم المفقودة
-- =====================================================

-- جدول Intent Mappings (النوايا)
CREATE TABLE IF NOT EXISTS cmis_knowledge.intent_mappings (
    intent_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    intent_name text NOT NULL UNIQUE,
    intent_name_ar text NOT NULL,
    intent_description text,
    intent_embedding vector(768),
    parent_intent_id uuid REFERENCES cmis_knowledge.intent_mappings(intent_id),
    intent_level integer DEFAULT 1,
    related_keywords text[],
    related_keywords_ar text[],
    confidence_threshold numeric(3,2) DEFAULT 0.75,
    usage_count integer DEFAULT 0,
    last_used timestamp with time zone,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);

-- جدول Direction Mappings (الاتجاهات)
CREATE TABLE IF NOT EXISTS cmis_knowledge.direction_mappings (
    direction_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    direction_name text NOT NULL UNIQUE,
    direction_name_ar text NOT NULL,
    direction_type text CHECK (direction_type IN ('strategic', 'tactical', 'operational')),
    direction_embedding vector(768),
    parent_direction_id uuid REFERENCES cmis_knowledge.direction_mappings(direction_id),
    associated_intents uuid[],
    confidence_score numeric(3,2) DEFAULT 0.80,
    metadata jsonb DEFAULT '{}',
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);

-- جدول Purpose Mappings (المقاصد)
CREATE TABLE IF NOT EXISTS cmis_knowledge.purpose_mappings (
    purpose_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    purpose_name text NOT NULL UNIQUE,
    purpose_name_ar text NOT NULL,
    purpose_category text,
    purpose_embedding vector(768),
    related_intents uuid[],
    related_directions uuid[],
    achievement_criteria jsonb,
    confidence_threshold numeric(3,2) DEFAULT 0.70,
    usage_count integer DEFAULT 0,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);

-- جدول Cache للـ Embeddings
CREATE TABLE IF NOT EXISTS cmis_knowledge.embeddings_cache (
    cache_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    source_table text NOT NULL,
    source_id uuid NOT NULL,
    source_field text NOT NULL,
    embedding vector(768) NOT NULL,
    embedding_norm float GENERATED ALWAYS AS (sqrt((embedding <# embedding)::float)) STORED,
    metadata jsonb DEFAULT '{}',
    model_version text DEFAULT 'gemini-text-embedding-004',
    quality_score numeric(3,2),
    usage_count integer DEFAULT 0,
    last_accessed timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    UNIQUE(source_table, source_id, source_field)
);

-- جدول قائمة انتظار التحديث
CREATE TABLE IF NOT EXISTS cmis_knowledge.embedding_update_queue (
    queue_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    knowledge_id uuid NOT NULL,
    source_table text NOT NULL,
    source_field text NOT NULL,
    priority integer DEFAULT 5 CHECK (priority BETWEEN 1 AND 10),
    status text DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    retry_count integer DEFAULT 0,
    max_retries integer DEFAULT 3,
    error_message text,
    created_at timestamp with time zone DEFAULT now(),
    processing_started_at timestamp with time zone,
    processed_at timestamp with time zone
);

-- جدول سجل البحث الدلالي
CREATE TABLE IF NOT EXISTS cmis_knowledge.semantic_search_logs (
    log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    query_text text NOT NULL,
    intent text,
    direction text,
    purpose text,
    category text,
    results_count integer,
    avg_similarity numeric(5,4),
    max_similarity numeric(5,4),
    min_similarity numeric(5,4),
    execution_time_ms integer,
    user_feedback text CHECK (user_feedback IN ('positive', 'negative', 'neutral')),
    user_id uuid,
    session_id text,
    created_at timestamp with time zone DEFAULT now()
);

-- جدول نتائج البحث المخزنة مؤقتاً
CREATE TABLE IF NOT EXISTS cmis_knowledge.semantic_search_results_cache (
    cache_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    query_hash text NOT NULL,
    query_text text NOT NULL,
    intent text,
    direction text,
    purpose text,
    results jsonb NOT NULL,
    result_count integer,
    avg_similarity numeric(5,4),
    created_at timestamp with time zone DEFAULT now(),
    expires_at timestamp with time zone DEFAULT (now() + interval '1 hour')
);

-- فهارس على جداول الدعم
CREATE INDEX IF NOT EXISTS idx_intent_embedding 
ON cmis_knowledge.intent_mappings 
USING hnsw (intent_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_direction_embedding 
ON cmis_knowledge.direction_mappings 
USING hnsw (direction_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_purpose_embedding 
ON cmis_knowledge.purpose_mappings 
USING hnsw (purpose_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_embeddings_cache_vector 
ON cmis_knowledge.embeddings_cache 
USING hnsw (embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_embeddings_cache_source 
ON cmis_knowledge.embeddings_cache (source_table, source_id);

CREATE INDEX IF NOT EXISTS idx_embedding_queue_status 
ON cmis_knowledge.embedding_update_queue (status, priority DESC)
WHERE status IN ('pending', 'processing');

CREATE INDEX IF NOT EXISTS idx_search_logs_time 
ON cmis_knowledge.semantic_search_logs (created_at DESC);

CREATE INDEX IF NOT EXISTS idx_search_cache_hash 
ON cmis_knowledge.semantic_search_results_cache (query_hash);

-- =====================================================
-- PHASE 3: إضافة أعمدة Embeddings للجداول المحتوى
-- =====================================================

-- جدول dev
ALTER TABLE cmis_knowledge.dev 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS chunk_embeddings jsonb,
ADD COLUMN IF NOT EXISTS semantic_summary_embedding vector(768),
ADD COLUMN IF NOT EXISTS intent_analysis jsonb,
ADD COLUMN IF NOT EXISTS embedding_metadata jsonb DEFAULT '{}',
ADD COLUMN IF NOT EXISTS embedding_updated_at timestamp with time zone;

-- جدول marketing
ALTER TABLE cmis_knowledge.marketing 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS audience_embedding vector(768),
ADD COLUMN IF NOT EXISTS tone_embedding vector(768),
ADD COLUMN IF NOT EXISTS campaign_intent_vector vector(768),
ADD COLUMN IF NOT EXISTS emotional_direction_vector vector(768),
ADD COLUMN IF NOT EXISTS embedding_updated_at timestamp with time zone;

-- جدول org
ALTER TABLE cmis_knowledge.org 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS org_context_embedding vector(768),
ADD COLUMN IF NOT EXISTS strategic_intent_vector vector(768),
ADD COLUMN IF NOT EXISTS embedding_updated_at timestamp with time zone;

-- جدول research
ALTER TABLE cmis_knowledge.research 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS source_context_embedding vector(768),
ADD COLUMN IF NOT EXISTS research_direction_vector vector(768),
ADD COLUMN IF NOT EXISTS insight_embedding vector(768),
ADD COLUMN IF NOT EXISTS embedding_updated_at timestamp with time zone;

-- إضافة فهارس على أعمدة المحتوى
CREATE INDEX IF NOT EXISTS idx_dev_content_embedding 
ON cmis_knowledge.dev 
USING hnsw (content_embedding vector_cosine_ops)
WHERE content_embedding IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_marketing_content_embedding 
ON cmis_knowledge.marketing 
USING hnsw (content_embedding vector_cosine_ops)
WHERE content_embedding IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_org_content_embedding 
ON cmis_knowledge.org 
USING hnsw (content_embedding vector_cosine_ops)
WHERE content_embedding IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_research_content_embedding 
ON cmis_knowledge.research 
USING hnsw (content_embedding vector_cosine_ops)
WHERE content_embedding IS NOT NULL;

-- =====================================================
-- PHASE 4: إنشاء Views للمراقبة والتحليل
-- =====================================================

-- View حالة Embeddings
CREATE OR REPLACE VIEW cmis_knowledge.v_embedding_status AS
WITH category_stats AS (
    SELECT 
        category,
        COUNT(*) AS total_records,
        COUNT(topic_embedding) AS embedded_records,
        COUNT(*) FILTER (WHERE topic_embedding IS NULL) AS pending_records,
        COUNT(*) FILTER (WHERE 
            topic_embedding IS NOT NULL 
            AND last_verified_at > COALESCE(
                (SELECT MAX(embedding_updated_at) FROM cmis_knowledge.dev WHERE knowledge_id = index.knowledge_id),
                '1900-01-01'::timestamp
            )
        ) AS outdated_records,
        AVG(CASE 
            WHEN topic_embedding IS NOT NULL 
            THEN EXTRACT(epoch FROM (now() - last_verified_at))/3600 
        END)::numeric(10,2) AS avg_hours_since_update
    FROM cmis_knowledge.index
    GROUP BY category
)
SELECT 
    category AS "الفئة",
    total_records AS "إجمالي السجلات",
    embedded_records AS "سجلات مع Embeddings",
    pending_records AS "في الانتظار",
    outdated_records AS "تحتاج تحديث",
    ROUND((embedded_records::numeric / NULLIF(total_records, 0)) * 100, 1) AS "نسبة التغطية %",
    avg_hours_since_update AS "متوسط ساعات منذ التحديث",
    CASE 
        WHEN (embedded_records::numeric / NULLIF(total_records, 0)) >= 0.9 THEN '🟢 ممتاز'
        WHEN (embedded_records::numeric / NULLIF(total_records, 0)) >= 0.7 THEN '🟡 جيد'
        WHEN (embedded_records::numeric / NULLIF(total_records, 0)) >= 0.5 THEN '🟠 متوسط'
        ELSE '🔴 يحتاج تحسين'
    END AS "الحالة"
FROM category_stats
ORDER BY total_records DESC;

-- View للبحث الدلالي الموحد
CREATE OR REPLACE VIEW cmis_knowledge.v_semantic_search_index AS
SELECT 
    ki.knowledge_id,
    ki.domain,
    ki.category,
    ki.topic,
    ki.keywords,
    ki.tier,
    ki.topic_embedding,
    ki.intent_vector,
    ki.direction_vector,
    ki.purpose_vector,
    ki.verification_confidence,
    ki.is_verified_by_ai,
    CASE 
        WHEN ki.topic_embedding IS NULL THEN 'pending'
        WHEN ki.last_verified_at > COALESCE(kd.embedding_updated_at, '1900-01-01') THEN 'outdated'
        ELSE 'current'
    END AS embedding_status,
    COALESCE(kd.content, km.content, ko.content, kr.content) AS content,
    COALESCE(kd.content_embedding, km.content_embedding, ko.content_embedding, kr.content_embedding) AS content_embedding,
    jsonb_build_object(
        'has_topic_embedding', ki.topic_embedding IS NOT NULL,
        'has_intent', ki.intent_vector IS NOT NULL,
        'has_direction', ki.direction_vector IS NOT NULL,
        'has_purpose', ki.purpose_vector IS NOT NULL,
        'last_verified', ki.last_verified_at,
        'is_deprecated', ki.is_deprecated,
        'verification_confidence', ki.verification_confidence
    ) AS metadata
FROM cmis_knowledge.index ki
LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
LEFT JOIN cmis_knowledge.research kr USING (knowledge_id);

-- View لتحليل النوايا
CREATE OR REPLACE VIEW cmis_knowledge.v_intent_analysis AS
WITH intent_usage AS (
    SELECT 
        im.intent_id,
        im.intent_name,
        im.intent_name_ar,
        im.intent_description,
        im.usage_count,
        im.last_used,
        COUNT(DISTINCT ki.knowledge_id) AS matched_knowledge_count,
        AVG(CASE 
            WHEN ki.intent_vector IS NOT NULL AND im.intent_embedding IS NOT NULL
            THEN 1 - (ki.intent_vector <=> im.intent_embedding)
            ELSE 0
        END)::numeric(5,4) AS avg_similarity
    FROM cmis_knowledge.intent_mappings im
    LEFT JOIN cmis_knowledge.index ki 
        ON ki.intent_vector IS NOT NULL 
        AND im.intent_embedding IS NOT NULL
        AND 1 - (ki.intent_vector <=> im.intent_embedding) > 0.6
    WHERE im.is_active = true
    GROUP BY im.intent_id, im.intent_name, im.intent_name_ar, im.intent_description, im.usage_count, im.last_used
)
SELECT 
    intent_name AS "Intent",
    intent_name_ar AS "النية",
    intent_description AS "الوصف",
    usage_count AS "عدد الاستخدامات",
    matched_knowledge_count AS "عدد المعارف المرتبطة",
    ROUND(avg_similarity * 100, 2) AS "متوسط التشابه %",
    last_used AS "آخر استخدام",
    CASE 
        WHEN usage_count > 100 THEN '🔥 عالي جداً'
        WHEN usage_count > 50 THEN '🟢 نشط'
        WHEN usage_count > 10 THEN '🟡 متوسط'
        WHEN usage_count > 0 THEN '🟠 منخفض'
        ELSE '🔴 غير مستخدم'
    END AS "مستوى النشاط"
FROM intent_usage
ORDER BY usage_count DESC;

-- View لأداء البحث الدلالي
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

-- View لقائمة انتظار التحديث
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

-- =====================================================
-- PHASE 5: دوال المعالجة المحسنة
-- =====================================================

-- دالة تحديث embedding لسجل واحد
CREATE OR REPLACE FUNCTION cmis_knowledge.update_single_embedding(
    p_knowledge_id uuid
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_rec record;
    v_topic_embedding vector(768);
    v_keywords_embedding vector(768);
    v_content_embedding vector(768);
BEGIN
    -- الحصول على البيانات
    SELECT 
        ki.*,
        COALESCE(kd.content, km.content, ko.content, kr.content) AS content
    INTO v_rec
    FROM cmis_knowledge.index ki
    LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
    LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
    LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
    LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
    WHERE ki.knowledge_id = p_knowledge_id;
    
    IF NOT FOUND THEN
        RETURN jsonb_build_object(
            'status', 'error',
            'message', 'Knowledge ID not found'
        );
    END IF;
    
    -- توليد embeddings
    v_topic_embedding := cmis_knowledge.generate_embedding_mock(v_rec.topic);
    
    IF array_length(v_rec.keywords, 1) > 0 THEN
        v_keywords_embedding := cmis_knowledge.generate_embedding_mock(
            array_to_string(v_rec.keywords, ' ')
        );
    END IF;
    
    IF v_rec.content IS NOT NULL THEN
        v_content_embedding := cmis_knowledge.generate_embedding_mock(v_rec.content);
    END IF;
    
    -- تحديث جدول الفهرس
    UPDATE cmis_knowledge.index 
    SET 
        topic_embedding = v_topic_embedding,
        intent_vector = COALESCE(intent_vector, v_topic_embedding),
        direction_vector = COALESCE(direction_vector, v_keywords_embedding),
        purpose_vector = COALESCE(purpose_vector, v_content_embedding)
    WHERE knowledge_id = p_knowledge_id;
    
    -- تحديث جدول المحتوى المناسب
    CASE v_rec.category
        WHEN 'dev' THEN
            UPDATE cmis_knowledge.dev 
            SET 
                content_embedding = v_content_embedding,
                embedding_updated_at = now()
            WHERE knowledge_id = p_knowledge_id;
        
        WHEN 'marketing' THEN
            UPDATE cmis_knowledge.marketing 
            SET 
                content_embedding = v_content_embedding,
                embedding_updated_at = now()
            WHERE knowledge_id = p_knowledge_id;
        
        WHEN 'org' THEN
            UPDATE cmis_knowledge.org 
            SET 
                content_embedding = v_content_embedding,
                embedding_updated_at = now()
            WHERE knowledge_id = p_knowledge_id;
        
        WHEN 'research' THEN
            UPDATE cmis_knowledge.research 
            SET 
                content_embedding = v_content_embedding,
                embedding_updated_at = now()
            WHERE knowledge_id = p_knowledge_id;
    END CASE;
    
    -- تحديث cache
    INSERT INTO cmis_knowledge.embeddings_cache (
        source_table, source_id, source_field, embedding
    ) VALUES 
        ('index', p_knowledge_id, 'topic', v_topic_embedding)
    ON CONFLICT (source_table, source_id, source_field) 
    DO UPDATE SET 
        embedding = EXCLUDED.embedding,
        updated_at = now(),
        usage_count = cmis_knowledge.embeddings_cache.usage_count + 1;
    
    RETURN jsonb_build_object(
        'status', 'success',
        'knowledge_id', p_knowledge_id,
        'topic', v_rec.topic,
        'category', v_rec.category,
        'embeddings_updated', jsonb_build_object(
            'topic', v_topic_embedding IS NOT NULL,
            'keywords', v_keywords_embedding IS NOT NULL,
            'content', v_content_embedding IS NOT NULL
        ),
        'timestamp', now()
    );
END;
$$;

-- دالة معالجة دفعية محسنة
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
    -- إضافة السجلات إلى قائمة الانتظار
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
    
    -- معالجة السجلات
    FOR v_rec IN 
        SELECT queue_id, knowledge_id
        FROM cmis_knowledge.embedding_update_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT p_batch_size
    LOOP
        BEGIN
            -- تحديث الحالة
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing', processing_started_at = now()
            WHERE queue_id = v_rec.queue_id;
            
            -- معالجة السجل
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

-- دالة معالجة قائمة الانتظار
CREATE OR REPLACE FUNCTION cmis_knowledge.process_embedding_queue(
    p_batch_size integer DEFAULT 10
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_processed integer := 0;
    v_failed integer := 0;
    v_rec record;
BEGIN
    FOR v_rec IN
        SELECT queue_id, knowledge_id
        FROM cmis_knowledge.embedding_update_queue
        WHERE 
            status IN ('pending', 'failed')
            AND retry_count < max_retries
        ORDER BY 
            CASE status WHEN 'failed' THEN 0 ELSE 1 END,
            priority DESC,
            created_at ASC
        LIMIT p_batch_size
        FOR UPDATE SKIP LOCKED
    LOOP
        BEGIN
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing', processing_started_at = now()
            WHERE queue_id = v_rec.queue_id;
            
            PERFORM cmis_knowledge.update_single_embedding(v_rec.knowledge_id);
            
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'completed', processed_at = now()
            WHERE queue_id = v_rec.queue_id;
            
            v_processed := v_processed + 1;
            
        EXCEPTION WHEN OTHERS THEN
            UPDATE cmis_knowledge.embedding_update_queue
            SET 
                status = 'failed',
                error_message = SQLERRM,
                retry_count = retry_count + 1
            WHERE queue_id = v_rec.queue_id;
            
            v_failed := v_failed + 1;
        END;
    END LOOP;
    
    RETURN jsonb_build_object(
        'processed', v_processed,
        'failed', v_failed,
        'timestamp', now()
    );
END;
$$;

-- دالة تنظيف البيانات القديمة
CREATE OR REPLACE FUNCTION cmis_knowledge.cleanup_old_embeddings()
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
    -- حذف cache القديم
    DELETE FROM cmis_knowledge.semantic_search_results_cache
    WHERE expires_at < now();
    
    -- حذف سجلات القائمة المكتملة القديمة
    DELETE FROM cmis_knowledge.embedding_update_queue
    WHERE status = 'completed' AND processed_at < now() - interval '7 days';
    
    -- حذف سجلات البحث القديمة
    DELETE FROM cmis_knowledge.semantic_search_logs
    WHERE created_at < now() - interval '30 days';
    
    -- إعادة تعيين إحصائيات الاستخدام القديمة
    UPDATE cmis_knowledge.embeddings_cache
    SET usage_count = 0
    WHERE last_accessed < now() - interval '30 days';
    
    -- تنظيف VACUUM
    VACUUM ANALYZE cmis_knowledge.embeddings_cache;
    VACUUM ANALYZE cmis_knowledge.semantic_search_results_cache;
    VACUUM ANALYZE cmis_knowledge.embedding_update_queue;
END;
$$;

-- دالة تقرير شامل عن حالة النظام
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

-- =====================================================
-- PHASE 6: إدراج البيانات الأولية
-- =====================================================

-- إدراج النوايا الأساسية
INSERT INTO cmis_knowledge.intent_mappings (
    intent_name, intent_name_ar, intent_description, 
    intent_embedding, related_keywords, related_keywords_ar
) VALUES 
    ('increase_sales', 'زيادة المبيعات', 'النية لزيادة المبيعات والإيرادات',
     cmis_knowledge.generate_embedding_mock('increase sales revenue growth profit'), 
     ARRAY['sales', 'revenue', 'growth', 'profit', 'conversion'], 
     ARRAY['مبيعات', 'إيرادات', 'نمو', 'ربح', 'تحويل']),
    
    ('brand_awareness', 'الوعي بالعلامة التجارية', 'بناء وتعزيز الوعي بالعلامة التجارية',
     cmis_knowledge.generate_embedding_mock('brand awareness recognition marketing visibility'),
     ARRAY['brand', 'awareness', 'recognition', 'visibility', 'reach'],
     ARRAY['علامة تجارية', 'وعي', 'تعريف', 'ظهور', 'وصول']),
    
    ('customer_engagement', 'تفاعل العملاء', 'زيادة التفاعل والمشاركة مع العملاء',
     cmis_knowledge.generate_embedding_mock('customer engagement interaction loyalty retention'),
     ARRAY['engagement', 'interaction', 'loyalty', 'retention', 'satisfaction'],
     ARRAY['تفاعل', 'مشاركة', 'ولاء', 'احتفاظ', 'رضا']),
    
    ('lead_generation', 'توليد العملاء المحتملين', 'جذب وتأهيل عملاء محتملين جدد',
     cmis_knowledge.generate_embedding_mock('lead generation prospects qualification pipeline'),
     ARRAY['leads', 'prospects', 'qualification', 'pipeline', 'acquisition'],
     ARRAY['عملاء محتملون', 'توقعات', 'تأهيل', 'مسار', 'اكتساب']),
    
    ('content_optimization', 'تحسين المحتوى', 'تحسين جودة وفعالية المحتوى',
     cmis_knowledge.generate_embedding_mock('content optimization quality performance SEO'),
     ARRAY['content', 'optimization', 'quality', 'SEO', 'performance'],
     ARRAY['محتوى', 'تحسين', 'جودة', 'تهيئة محركات البحث', 'أداء'])
ON CONFLICT (intent_name) DO UPDATE
SET 
    intent_embedding = EXCLUDED.intent_embedding,
    updated_at = now();

-- إدراج الاتجاهات الأساسية
INSERT INTO cmis_knowledge.direction_mappings (
    direction_name, direction_name_ar, direction_type,
    direction_embedding
) VALUES
    ('digital_transformation', 'التحول الرقمي', 'strategic',
     cmis_knowledge.generate_embedding_mock('digital transformation technology innovation automation')),
    
    ('market_expansion', 'التوسع في السوق', 'strategic',
     cmis_knowledge.generate_embedding_mock('market expansion growth new segments geographical')),
    
    ('operational_efficiency', 'الكفاءة التشغيلية', 'operational',
     cmis_knowledge.generate_embedding_mock('operational efficiency optimization productivity cost reduction')),
    
    ('customer_centric', 'التمحور حول العميل', 'strategic',
     cmis_knowledge.generate_embedding_mock('customer centric experience satisfaction loyalty')),
    
    ('data_driven', 'قائم على البيانات', 'tactical',
     cmis_knowledge.generate_embedding_mock('data driven analytics insights metrics KPIs'))
ON CONFLICT (direction_name) DO UPDATE
SET 
    direction_embedding = EXCLUDED.direction_embedding,
    updated_at = now();

-- إدراج المقاصد الأساسية
INSERT INTO cmis_knowledge.purpose_mappings (
    purpose_name, purpose_name_ar, purpose_category,
    purpose_embedding
) VALUES
    ('roi_maximization', 'تعظيم العائد على الاستثمار', 'financial',
     cmis_knowledge.generate_embedding_mock('ROI return investment maximization profit margin')),
    
    ('customer_satisfaction', 'رضا العملاء', 'customer',
     cmis_knowledge.generate_embedding_mock('customer satisfaction happiness loyalty NPS score')),
    
    ('innovation_leadership', 'الريادة في الابتكار', 'innovation',
     cmis_knowledge.generate_embedding_mock('innovation leadership pioneering creativity disruption')),
    
    ('market_dominance', 'السيطرة على السوق', 'competitive',
     cmis_knowledge.generate_embedding_mock('market dominance share leadership competitive advantage')),
    
    ('sustainable_growth', 'النمو المستدام', 'growth',
     cmis_knowledge.generate_embedding_mock('sustainable growth scalability long-term stability'))
ON CONFLICT (purpose_name) DO UPDATE
SET 
    purpose_embedding = EXCLUDED.purpose_embedding,
    updated_at = now();

-- =====================================================
-- PHASE 7: Triggers للتحديث التلقائي
-- =====================================================

-- دالة trigger لتحديث embeddings
CREATE OR REPLACE FUNCTION cmis_knowledge.trigger_update_embeddings()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    -- إضافة إلى قائمة الانتظار
    INSERT INTO cmis_knowledge.embedding_update_queue (
        knowledge_id,
        source_table,
        source_field,
        priority
    ) VALUES (
        COALESCE(NEW.knowledge_id, OLD.knowledge_id),
        TG_TABLE_NAME,
        CASE 
            WHEN TG_TABLE_NAME = 'index' THEN 'topic'
            ELSE 'content'
        END,
        CASE 
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 1 THEN 10
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 2 THEN 7
            ELSE 5
        END
    ) ON CONFLICT DO NOTHING;
    
    RETURN NEW;
END;
$$;

-- تطبيق triggers
DROP TRIGGER IF EXISTS update_embeddings_on_index_change ON cmis_knowledge.index;
CREATE TRIGGER update_embeddings_on_index_change
AFTER INSERT OR UPDATE OF topic, keywords, domain, category
ON cmis_knowledge.index
FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();

DROP TRIGGER IF EXISTS update_embeddings_on_dev_change ON cmis_knowledge.dev;
CREATE TRIGGER update_embeddings_on_dev_change
AFTER INSERT OR UPDATE OF content
ON cmis_knowledge.dev
FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();

DROP TRIGGER IF EXISTS update_embeddings_on_marketing_change ON cmis_knowledge.marketing;
CREATE TRIGGER update_embeddings_on_marketing_change
AFTER INSERT OR UPDATE OF content
ON cmis_knowledge.marketing
FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();

DROP TRIGGER IF EXISTS update_embeddings_on_research_change ON cmis_knowledge.research;
CREATE TRIGGER update_embeddings_on_research_change
AFTER INSERT OR UPDATE OF content
ON cmis_knowledge.research
FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();

-- =====================================================
-- PHASE 8: التحقق النهائي
-- =====================================================

-- دالة التحقق من صحة التثبيت
CREATE OR REPLACE FUNCTION cmis_knowledge.verify_installation()
RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_checks jsonb := '[]'::jsonb;
    v_check jsonb;
    v_all_passed boolean := true;
BEGIN
    -- التحقق من pgvector
    IF EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'vector') THEN
        v_check := jsonb_build_object('check', 'pgvector extension', 'status', 'installed', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'pgvector extension', 'status', 'not installed', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الجداول
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis_knowledge' 
               AND table_name = 'intent_mappings') THEN
        v_check := jsonb_build_object('check', 'support tables', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'support tables', 'status', 'not created', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الأعمدة
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_schema = 'cmis_knowledge' 
               AND table_name = 'index' 
               AND column_name = 'topic_embedding') THEN
        v_check := jsonb_build_object('check', 'vector columns', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'vector columns', 'status', 'not created', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الفهارس
    IF EXISTS (SELECT 1 FROM pg_indexes 
               WHERE schemaname = 'cmis_knowledge' 
               AND indexname LIKE '%embedding%') THEN
        v_check := jsonb_build_object('check', 'vector indexes', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'vector indexes', 'status', 'not created', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الدوال
    IF EXISTS (SELECT 1 FROM pg_proc 
               WHERE pronamespace = 'cmis_knowledge'::regnamespace 
               AND proname = 'batch_update_embeddings') THEN
        v_check := jsonb_build_object('check', 'processing functions', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'processing functions', 'status', 'not created', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من البيانات الأولية
    IF EXISTS (SELECT 1 FROM cmis_knowledge.intent_mappings LIMIT 1) THEN
        v_check := jsonb_build_object('check', 'initial data', 'status', 'loaded', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'initial data', 'status', 'not loaded', 'result', false);
        v_all_passed := false;
    END IF;
    v_checks := v_checks || v_check;
    
    RETURN jsonb_build_object(
        'installation_complete', v_all_passed,
        'checks', v_checks,
        'summary', jsonb_build_object(
            'tables_count', (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'cmis_knowledge'),
            'indexes_count', (SELECT COUNT(*) FROM pg_indexes WHERE schemaname = 'cmis_knowledge' AND indexname LIKE '%embedding%'),
            'intents_count', (SELECT COUNT(*) FROM cmis_knowledge.intent_mappings),
            'directions_count', (SELECT COUNT(*) FROM cmis_knowledge.direction_mappings),
            'purposes_count', (SELECT COUNT(*) FROM cmis_knowledge.purpose_mappings)
        ),
        'timestamp', now()
    );
END;
$$;

-- تنفيذ التحقق
SELECT cmis_knowledge.verify_installation();

-- عرض التقرير النهائي
SELECT cmis_knowledge.generate_system_report();

-- =====================================================
-- رسالة النهاية
-- =====================================================
DO $$
BEGIN
    RAISE NOTICE '=====================================================';
    RAISE NOTICE 'Vector Embeddings Completion Script - تم التنفيذ بنجاح';
    RAISE NOTICE '=====================================================';
    RAISE NOTICE 'تم إضافة المكونات التالية:';
    RAISE NOTICE '✅ الفهارس HNSW للبحث السريع';
    RAISE NOTICE '✅ جداول Intent/Direction/Purpose Mappings';
    RAISE NOTICE '✅ جداول Cache وQueue';
    RAISE NOTICE '✅ أعمدة embeddings لجميع جداول المحتوى';
    RAISE NOTICE '✅ Views للمراقبة والتحليل';
    RAISE NOTICE '✅ دوال المعالجة الدفعية';
    RAISE NOTICE '✅ Triggers للتحديث التلقائي';
    RAISE NOTICE '✅ البيانات الأولية للاختبار';
    RAISE NOTICE '=====================================================';
    RAISE NOTICE 'الخطوات التالية:';
    RAISE NOTICE '1. تكوين Gemini API الفعلي';
    RAISE NOTICE '2. تشغيل batch_update_embeddings() لمعالجة البيانات الموجودة';
    RAISE NOTICE '3. إعداد cron job لمعالجة القائمة بشكل دوري';
    RAISE NOTICE '4. مراقبة الأداء من خلال Views';
    RAISE NOTICE '=====================================================';
END $$;
