-- =====================================================
-- CMIS Knowledge Vector Migration Script
-- Version: 1.0
-- Date: November 2024
-- Description: إضافة دعم Vector Embeddings باستخدام Gemini
-- =====================================================

-- =====================================================
-- PHASE 1: تفعيل Extensions والإعدادات الأساسية
-- =====================================================

-- تفعيل pgvector extension
CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;

-- تفعيل plpython3u للتكامل مع APIs
CREATE EXTENSION IF NOT EXISTS plpython3u;

-- =====================================================
-- PHASE 2: إنشاء جداول التكوين والإدارة
-- =====================================================

-- جدول تكوين Gemini API
CREATE TABLE IF NOT EXISTS cmis_knowledge.embedding_api_config (
    config_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    api_key_encrypted text NOT NULL,
    api_endpoint text DEFAULT 'https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent',
    model_name text DEFAULT 'models/text-embedding-004',
    embedding_dimension integer DEFAULT 768,
    max_batch_size integer DEFAULT 100,
    rate_limit_per_minute integer DEFAULT 60,
    retry_attempts integer DEFAULT 3,
    timeout_seconds integer DEFAULT 30,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);

-- جدول سجل استدعاءات API
CREATE TABLE IF NOT EXISTS cmis_knowledge.embedding_api_logs (
    log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    request_timestamp timestamp with time zone DEFAULT now(),
    response_timestamp timestamp with time zone,
    text_length integer,
    status_code integer,
    error_message text,
    tokens_used integer,
    execution_time_ms integer,
    model_used text
);

-- جدول قائمة انتظار تحديث Embeddings
CREATE TABLE IF NOT EXISTS cmis_knowledge.embedding_update_queue (
    queue_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    knowledge_id uuid NOT NULL,
    source_table text NOT NULL,
    source_field text NOT NULL,
    priority integer DEFAULT 5,
    status text DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    retry_count integer DEFAULT 0,
    error_message text,
    created_at timestamp with time zone DEFAULT now(),
    processed_at timestamp with time zone
);

-- إنشاء فهرس على قائمة الانتظار
CREATE INDEX idx_embedding_queue_status ON cmis_knowledge.embedding_update_queue(status, priority DESC);

-- =====================================================
-- PHASE 3: إضافة أعمدة Vector للجداول الأساسية
-- =====================================================

-- تحديث جدول الفهرس الرئيسي
ALTER TABLE cmis_knowledge.index 
ADD COLUMN IF NOT EXISTS topic_embedding vector(768),
ADD COLUMN IF NOT EXISTS keywords_embedding vector(768),
ADD COLUMN IF NOT EXISTS semantic_fingerprint vector(768),
ADD COLUMN IF NOT EXISTS intent_vector vector(768),
ADD COLUMN IF NOT EXISTS direction_vector vector(768),  -- متجه الاتجاه
ADD COLUMN IF NOT EXISTS purpose_vector vector(768),     -- متجه المقصد
ADD COLUMN IF NOT EXISTS embedding_model text DEFAULT 'gemini-text-embedding-004',
ADD COLUMN IF NOT EXISTS embedding_updated_at timestamp with time zone,
ADD COLUMN IF NOT EXISTS embedding_version integer DEFAULT 1;

-- تحديث جدول dev
ALTER TABLE cmis_knowledge.dev 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS chunk_embeddings jsonb,
ADD COLUMN IF NOT EXISTS semantic_summary_embedding vector(768),
ADD COLUMN IF NOT EXISTS intent_analysis jsonb,
ADD COLUMN IF NOT EXISTS embedding_metadata jsonb DEFAULT '{}';

-- تحديث جدول marketing
ALTER TABLE cmis_knowledge.marketing 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS audience_embedding vector(768),
ADD COLUMN IF NOT EXISTS tone_embedding vector(768),
ADD COLUMN IF NOT EXISTS campaign_intent_vector vector(768),
ADD COLUMN IF NOT EXISTS emotional_direction_vector vector(768);

-- تحديث جدول org
ALTER TABLE cmis_knowledge.org 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS org_context_embedding vector(768),
ADD COLUMN IF NOT EXISTS strategic_intent_vector vector(768);

-- تحديث جدول research
ALTER TABLE cmis_knowledge.research 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS source_context_embedding vector(768),
ADD COLUMN IF NOT EXISTS research_direction_vector vector(768),
ADD COLUMN IF NOT EXISTS insight_embedding vector(768);

-- تحديث جدول creative_templates
ALTER TABLE cmis_knowledge.creative_templates 
ADD COLUMN IF NOT EXISTS content_embedding vector(768),
ADD COLUMN IF NOT EXISTS emotion_embedding vector(768),
ADD COLUMN IF NOT EXISTS creative_style_embedding vector(768),
ADD COLUMN IF NOT EXISTS tone_direction_vector vector(768);

-- تحديث جدول temporal_analytics
ALTER TABLE cmis_knowledge.temporal_analytics 
ADD COLUMN IF NOT EXISTS temporal_embedding vector(768),
ADD COLUMN IF NOT EXISTS change_vector vector(768),
ADD COLUMN IF NOT EXISTS trend_direction_embedding vector(768);

-- =====================================================
-- PHASE 4: إنشاء جداول دعم Vector
-- =====================================================

-- جدول تخزين Embeddings المركزي مع تحسينات
CREATE TABLE IF NOT EXISTS cmis_knowledge.embeddings_cache (
    cache_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    source_table text NOT NULL,
    source_id uuid NOT NULL,
    source_field text NOT NULL,
    embedding vector(768) NOT NULL,
    embedding_norm float GENERATED ALWAYS AS (sqrt((embedding <-> embedding)::float)) STORED,
    metadata jsonb DEFAULT '{}',
    model_version text DEFAULT 'gemini-text-embedding-004',
    quality_score numeric(3,2),
    usage_count integer DEFAULT 0,
    last_accessed timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    UNIQUE(source_table, source_id, source_field)
);

-- جدول Intent والنوايا
CREATE TABLE IF NOT EXISTS cmis_knowledge.intent_mappings (
    intent_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    intent_name text NOT NULL UNIQUE,
    intent_name_ar text NOT NULL,
    intent_description text,
    intent_embedding vector(768) NOT NULL,
    parent_intent_id uuid REFERENCES cmis_knowledge.intent_mappings(intent_id),
    intent_level integer DEFAULT 1,
    related_keywords text[],
    related_keywords_ar text[],
    confidence_threshold numeric(3,2) DEFAULT 0.75,
    usage_statistics jsonb DEFAULT '{"count": 0, "last_used": null}',
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);

-- جدول Directions والاتجاهات
CREATE TABLE IF NOT EXISTS cmis_knowledge.direction_mappings (
    direction_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    direction_name text NOT NULL UNIQUE,
    direction_name_ar text NOT NULL,
    direction_type text CHECK (direction_type IN ('strategic', 'tactical', 'operational')),
    direction_embedding vector(768) NOT NULL,
    parent_direction_id uuid REFERENCES cmis_knowledge.direction_mappings(direction_id),
    associated_intents uuid[],
    confidence_score numeric(3,2) DEFAULT 0.80,
    metadata jsonb DEFAULT '{}',
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now()
);

-- جدول Purposes والمقاصد
CREATE TABLE IF NOT EXISTS cmis_knowledge.purpose_mappings (
    purpose_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    purpose_name text NOT NULL UNIQUE,
    purpose_name_ar text NOT NULL,
    purpose_category text,
    purpose_embedding vector(768) NOT NULL,
    related_intents uuid[],
    related_directions uuid[],
    achievement_criteria jsonb,
    confidence_threshold numeric(3,2) DEFAULT 0.70,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now()
);

-- جدول تخزين نتائج البحث الدلالي
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

-- =====================================================
-- PHASE 5: إنشاء الفهارس المتقدمة
-- =====================================================

-- فهارس HNSW للبحث السريع على جدول الفهرس
CREATE INDEX IF NOT EXISTS idx_index_topic_embedding 
ON cmis_knowledge.index USING hnsw (topic_embedding vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_index_intent_vector 
ON cmis_knowledge.index USING hnsw (intent_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_index_direction_vector 
ON cmis_knowledge.index USING hnsw (direction_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

CREATE INDEX IF NOT EXISTS idx_index_purpose_vector 
ON cmis_knowledge.index USING hnsw (purpose_vector vector_cosine_ops)
WITH (m = 16, ef_construction = 64);

-- فهارس على جداول المحتوى
CREATE INDEX IF NOT EXISTS idx_dev_content_embedding 
ON cmis_knowledge.dev USING hnsw (content_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_marketing_content_embedding 
ON cmis_knowledge.marketing USING hnsw (content_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_research_content_embedding 
ON cmis_knowledge.research USING hnsw (content_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_creative_content_embedding 
ON cmis_knowledge.creative_templates USING hnsw (content_embedding vector_cosine_ops);

-- فهارس على جدول الـ cache
CREATE INDEX IF NOT EXISTS idx_embeddings_cache_vector 
ON cmis_knowledge.embeddings_cache USING hnsw (embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_embeddings_cache_source 
ON cmis_knowledge.embeddings_cache (source_table, source_id);

-- فهارس على جداول Intent/Direction/Purpose
CREATE INDEX IF NOT EXISTS idx_intent_embedding 
ON cmis_knowledge.intent_mappings USING hnsw (intent_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_direction_embedding 
ON cmis_knowledge.direction_mappings USING hnsw (direction_embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_purpose_embedding 
ON cmis_knowledge.purpose_mappings USING hnsw (purpose_embedding vector_cosine_ops);

-- =====================================================
-- PHASE 6: الدوال الأساسية للنظام
-- =====================================================

-- دالة محاكاة توليد Embedding (للتطوير والاختبار)
CREATE OR REPLACE FUNCTION cmis_knowledge.generate_embedding_mock(
    p_text text,
    p_model text DEFAULT 'gemini-text-embedding-004'
) RETURNS vector
LANGUAGE plpgsql
AS $$
DECLARE
    v_embedding float[];
    i integer;
BEGIN
    -- توليد embedding عشوائي للاختبار
    -- في الإنتاج سيتم استبدال هذا باستدعاء Gemini API الفعلي
    v_embedding := ARRAY[]::float[];
    
    -- توليد 768 قيمة عشوائية
    FOR i IN 1..768 LOOP
        v_embedding := array_append(v_embedding, (random() * 2 - 1)::float);
    END LOOP;
    
    -- تطبيع المتجه
    DECLARE
        v_norm float;
    BEGIN
        v_norm := sqrt((SELECT SUM(val * val) FROM unnest(v_embedding) val));
        IF v_norm > 0 THEN
            FOR i IN 1..768 LOOP
                v_embedding[i] := v_embedding[i] / v_norm;
            END LOOP;
        END IF;
    END;
    
    RETURN v_embedding::vector;
END;
$$;

-- دالة البحث الدلالي المتقدم
CREATE OR REPLACE FUNCTION cmis_knowledge.semantic_search_advanced(
    p_query text,
    p_intent text DEFAULT NULL,
    p_direction text DEFAULT NULL,
    p_purpose text DEFAULT NULL,
    p_category text DEFAULT NULL,
    p_limit integer DEFAULT 10,
    p_threshold numeric DEFAULT 0.7
) RETURNS TABLE(
    knowledge_id uuid,
    domain text,
    topic text,
    content text,
    similarity_score numeric,
    intent_match numeric,
    direction_match numeric,
    purpose_match numeric,
    combined_score numeric,
    category text,
    tier smallint,
    metadata jsonb
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_query_embedding vector(768);
    v_intent_embedding vector(768);
    v_direction_embedding vector(768);
    v_purpose_embedding vector(768);
BEGIN
    -- توليد embeddings للمدخلات
    v_query_embedding := cmis_knowledge.generate_embedding_mock(p_query);
    
    IF p_intent IS NOT NULL THEN
        v_intent_embedding := cmis_knowledge.generate_embedding_mock(p_intent);
    END IF;
    
    IF p_direction IS NOT NULL THEN
        v_direction_embedding := cmis_knowledge.generate_embedding_mock(p_direction);
    END IF;
    
    IF p_purpose IS NOT NULL THEN
        v_purpose_embedding := cmis_knowledge.generate_embedding_mock(p_purpose);
    END IF;
    
    RETURN QUERY
    WITH scored_results AS (
        SELECT 
            knowledge_id,
            ki.domain,
            ki.topic,
            COALESCE(
                kd.content, 
                km.content, 
                ko.content, 
                kr.content,
                'No content available'
            ) AS content,
            -- حساب التشابه مع الاستعلام
            COALESCE(1 - (ki.topic_embedding <=> v_query_embedding), 0) AS topic_similarity,
            -- حساب التطابق مع النية
            CASE 
                WHEN v_intent_embedding IS NOT NULL AND ki.intent_vector IS NOT NULL
                THEN 1 - (ki.intent_vector <=> v_intent_embedding)
                ELSE 1.0
            END AS intent_similarity,
            -- حساب التطابق مع الاتجاه
            CASE 
                WHEN v_direction_embedding IS NOT NULL AND ki.direction_vector IS NOT NULL
                THEN 1 - (ki.direction_vector <=> v_direction_embedding)
                ELSE 1.0
            END AS direction_similarity,
            -- حساب التطابق مع المقصد
            CASE 
                WHEN v_purpose_embedding IS NOT NULL AND ki.purpose_vector IS NOT NULL
                THEN 1 - (ki.purpose_vector <=> v_purpose_embedding)
                ELSE 1.0
            END AS purpose_similarity,
            ki.category,
            ki.tier,
            jsonb_build_object(
                'keywords', ki.keywords,
                'last_verified', ki.last_verified_at,
                'is_deprecated', ki.is_deprecated
            ) AS metadata
        FROM cmis_knowledge.index ki
        LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
        LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
        LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
        LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
        WHERE 
            (p_category IS NULL OR ki.category = p_category)
            AND ki.is_deprecated = false
            AND ki.topic_embedding IS NOT NULL
    )
    SELECT 
        knowledge_id,
        domain,
        topic,
        content,
        topic_similarity AS similarity_score,
        intent_similarity AS intent_match,
        direction_similarity AS direction_match,
        purpose_similarity AS purpose_match,
        -- حساب النقاط المركبة مع أوزان مختلفة
        (
            topic_similarity * 0.4 + 
            intent_similarity * 0.25 + 
            direction_similarity * 0.2 + 
            purpose_similarity * 0.15
        ) AS combined_score,
        category,
        tier,
        metadata
    FROM scored_results
    WHERE (
        topic_similarity * 0.4 + 
        intent_similarity * 0.25 + 
        direction_similarity * 0.2 + 
        purpose_similarity * 0.15
    ) >= p_threshold
    ORDER BY combined_score DESC, tier ASC
    LIMIT p_limit;
END;
$$;

-- دالة تحديث embeddings لسجل واحد
CREATE OR REPLACE FUNCTION cmis_knowledge.update_single_embedding(
    p_knowledge_id uuid
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_rec record;
    v_result jsonb;
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
    
    -- تحديث embeddings في جدول الفهرس
    UPDATE cmis_knowledge.index 
    SET 
        topic_embedding = cmis_knowledge.generate_embedding_mock(v_rec.topic),
        keywords_embedding = CASE 
            WHEN array_length(v_rec.keywords, 1) > 0 
            THEN cmis_knowledge.generate_embedding_mock(array_to_string(v_rec.keywords, ' '))
            ELSE NULL
        END,
        semantic_fingerprint = cmis_knowledge.generate_embedding_mock(
            COALESCE(v_rec.topic, '') || ' ' || 
            COALESCE(array_to_string(v_rec.keywords, ' '), '') || ' ' ||
            COALESCE(v_rec.domain, '')
        ),
        embedding_updated_at = now(),
        embedding_version = COALESCE(embedding_version, 0) + 1
    WHERE ki.knowledge_id = p_knowledge_id;
    
    -- تحديث content embedding في الجدول المناسب
    IF v_rec.content IS NOT NULL THEN
        CASE v_rec.category
            WHEN 'dev' THEN
                UPDATE cmis_knowledge.dev 
                SET 
                    content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content),
                    semantic_summary_embedding = cmis_knowledge.generate_embedding_mock(
                        left(v_rec.content, 500)
                    )
                WHERE ki.knowledge_id = p_knowledge_id;
            
            WHEN 'marketing' THEN
                UPDATE cmis_knowledge.marketing 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE ki.knowledge_id = p_knowledge_id;
            
            WHEN 'org' THEN
                UPDATE cmis_knowledge.org 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE ki.knowledge_id = p_knowledge_id;
            
            WHEN 'research' THEN
                UPDATE cmis_knowledge.research 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE ki.knowledge_id = p_knowledge_id;
        END CASE;
    END IF;
    
    -- تحديث cache
    INSERT INTO cmis_knowledge.embeddings_cache (
        source_table, source_id, source_field, 
        embedding, metadata
    ) VALUES (
        'index', p_knowledge_id, 'topic',
        cmis_knowledge.generate_embedding_mock(v_rec.topic),
        jsonb_build_object('category', v_rec.category, 'domain', v_rec.domain)
    )
    ON CONFLICT (source_table, source_id, source_field) 
    DO UPDATE SET 
        embedding = EXCLUDED.embedding,
        updated_at = now(),
        usage_count = cmis_knowledge.embeddings_cache.usage_count + 1;
    
    RETURN jsonb_build_object(
        'status', 'success',
        'ki.knowledge_id', p_knowledge_id,
        'topic', v_rec.topic,
        'category', v_rec.category,
        'embedding_updated', true,
        'timestamp', now()
    );
END;
$$;

-- دالة معالجة دفعية للـ embeddings
CREATE OR REPLACE FUNCTION cmis_knowledge.batch_update_embeddings(
    p_batch_size integer DEFAULT 100,
    p_category text DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_count integer := 0;
    v_errors integer := 0;
    v_rec record;
    v_start_time timestamp := clock_timestamp();
BEGIN
    FOR v_rec IN 
        SELECT knowledge_id
        FROM cmis_knowledge.index
        WHERE 
            (p_category IS NULL OR category = p_category)
            AND (embedding_updated_at IS NULL OR embedding_updated_at < last_verified_at)
            AND is_deprecated = false
        ORDER BY tier ASC, last_verified_at DESC
        LIMIT p_batch_size
    LOOP
        BEGIN
            PERFORM cmis_knowledge.update_single_embedding(v_rec.knowledge_id);
            v_count := v_count + 1;
        EXCEPTION WHEN OTHERS THEN
            v_errors := v_errors + 1;
            RAISE NOTICE 'Error updating embedding for %: %', v_rec.knowledge_id, SQLERRM;
        END;
    END LOOP;
    
    RETURN jsonb_build_object(
        'status', 'completed',
        'processed', v_count,
        'errors', v_errors,
        'execution_time_seconds', EXTRACT(EPOCH FROM (clock_timestamp() - v_start_time)),
        'timestamp', now()
    );
END;
$$;

-- =====================================================
-- PHASE 7: تحديث الدوال الموجودة
-- =====================================================

-- تحديث دالة smart_context_loader للعمل مع embeddings
CREATE OR REPLACE FUNCTION cmis_knowledge.smart_context_loader_v2(
    p_query text,
    p_intent text DEFAULT NULL,
    p_direction text DEFAULT NULL,
    p_purpose text DEFAULT NULL,
    p_domain text DEFAULT NULL,
    p_category text DEFAULT 'dev',
    p_token_limit integer DEFAULT 5000
) RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_context jsonb := '[]'::jsonb;
    v_total_tokens integer := 0;
    v_rec record;
    v_search_method text := 'hybrid'; -- hybrid, semantic, or text
BEGIN
    -- تحديد طريقة البحث
    IF EXISTS (
        SELECT 1 FROM cmis_knowledge.index 
        WHERE topic_embedding IS NOT NULL 
        LIMIT 1
    ) THEN
        v_search_method := 'semantic';
    ELSE
        v_search_method := 'text';
    END IF;
    
    IF v_search_method = 'semantic' THEN
        -- استخدام البحث الدلالي
        FOR v_rec IN
            SELECT 
                ki.knowledge_id,
                topic,
                content,
                similarity_score,
                intent_match,
                direction_match,
                purpose_match,
                combined_score
            FROM cmis_knowledge.semantic_search_advanced(
                p_query, 
                p_intent,
                p_direction,
                p_purpose,
                p_category,
                20,
                0.65
            )
        LOOP
            EXIT WHEN v_total_tokens + (length(v_rec.content) / 4) > p_token_limit;
            
            v_context := v_context || jsonb_build_object(
                'knowledge_id', v_rec.knowledge_id,
                'topic', v_rec.topic,
                'excerpt', left(v_rec.content, 2000),
                'semantic_score', round(v_rec.similarity_score::numeric, 4),
                'intent_match', round(v_rec.intent_match::numeric, 4),
                'direction_match', round(v_rec.direction_match::numeric, 4),
                'purpose_match', round(v_rec.purpose_match::numeric, 4),
                'combined_score', round(v_rec.combined_score::numeric, 4)
            );
            
            v_total_tokens := v_total_tokens + (length(v_rec.content) / 4);
        END LOOP;
    ELSE
        -- fallback للبحث النصي التقليدي
        FOR v_rec IN
            SELECT 
                knowledge_id,
                ki.topic,
                COALESCE(kd.content, km.content, ko.content, kr.content) AS content
            FROM cmis_knowledge.index ki
            LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
            LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
            LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
            LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
            WHERE 
                ki.topic ILIKE '%' || p_query || '%'
                AND (p_domain IS NULL OR ki.domain = p_domain)
                AND ki.category = p_category
                AND ki.is_deprecated = false
            ORDER BY ki.tier ASC, ki.last_verified_at DESC
            LIMIT 10
        LOOP
            EXIT WHEN v_total_tokens + (length(v_rec.content) / 4) > p_token_limit;
            
            v_context := v_context || jsonb_build_object(
                'knowledge_id', v_rec.knowledge_id,
                'topic', v_rec.topic,
                'excerpt', left(v_rec.content, 2000)
            );
            
            v_total_tokens := v_total_tokens + (length(v_rec.content) / 4);
        END LOOP;
    END IF;
    
    RETURN jsonb_build_object(
        'query', p_query,
        'intent', p_intent,
        'direction', p_direction,
        'purpose', p_purpose,
        'domain', p_domain,
        'category', p_category,
        'context_loaded', v_context,
        'estimated_tokens', v_total_tokens,
        'search_method', v_search_method,
        'results_count', jsonb_array_length(v_context)
    );
END;
$$;

-- =====================================================
-- PHASE 8: Triggers للتحديث التلقائي
-- =====================================================

-- دالة trigger لتحديث embeddings
CREATE OR REPLACE FUNCTION cmis_knowledge.trigger_update_embeddings()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    -- إضافة إلى قائمة الانتظار للمعالجة غير المتزامنة
    INSERT INTO cmis_knowledge.embedding_update_queue (
        ki.knowledge_id,
        source_table,
        source_field,
        priority,
        created_at
    ) VALUES (
        COALESCE(NEW.ki.knowledge_id, OLD.ki.knowledge_id),
        TG_TABLE_NAME,
        CASE 
            WHEN TG_TABLE_NAME = 'index' THEN 'topic'
            ELSE 'content'
        END,
        CASE 
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 1 THEN 10
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 2 THEN 7
            ELSE 5
        END,
        now()
    ) ON CONFLICT DO NOTHING;
    
    RETURN NEW;
END;
$$;

-- تطبيق triggers على الجداول
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
-- PHASE 9: إنشاء Views للمراقبة والتحليل
-- =====================================================

-- View لحالة Embeddings
CREATE OR REPLACE VIEW cmis_knowledge.v_embedding_status AS
WITH category_stats AS (
    SELECT 
        category,
        COUNT(*) AS total_records,
        COUNT(topic_embedding) AS embedded_records,
        COUNT(*) FILTER (WHERE embedding_updated_at > now() - interval '7 days') AS recently_updated,
        COUNT(*) FILTER (WHERE embedding_updated_at IS NULL) AS never_embedded,
        AVG(EXTRACT(epoch FROM (now() - embedding_updated_at))/3600)::numeric(10,2) AS avg_hours_since_update
    FROM cmis_knowledge.index
    GROUP BY category
)
SELECT 
    category AS "الفئة",
    total_records AS "إجمالي السجلات",
    embedded_records AS "سجلات مع Embeddings",
    never_embedded AS "بدون Embeddings",
    ROUND((embedded_records::numeric / NULLIF(total_records, 0)) * 100, 1) AS "نسبة التغطية %",
    recently_updated AS "محدث حديثاً (7 أيام)",
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
    ki.embedding_updated_at,
    ki.embedding_version,
    CASE 
        WHEN ki.embedding_updated_at IS NULL THEN 'pending'
        WHEN ki.embedding_updated_at < ki.last_verified_at THEN 'outdated'
        ELSE 'current'
    END AS embedding_status,
    COALESCE(kd.content, km.content, ko.content, kr.content) AS content,
    COALESCE(
        kd.content_embedding, 
        km.content_embedding, 
        ko.content_embedding, 
        kr.content_embedding
    ) AS content_embedding,
    jsonb_build_object(
        'has_intent', ki.intent_vector IS NOT NULL,
        'has_direction', ki.direction_vector IS NOT NULL,
        'has_purpose', ki.purpose_vector IS NOT NULL,
        'last_verified', ki.last_verified_at,
        'is_deprecated', ki.is_deprecated
    ) AS metadata
FROM cmis_knowledge.index ki
LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
LEFT JOIN cmis_knowledge.research kr USING (knowledge_id);

-- View لتحليل النوايا
CREATE OR REPLACE VIEW cmis_knowledge.v_intent_analysis AS
WITH intent_stats AS (
    SELECT 
        im.intent_name,
        im.intent_name_ar,
        im.intent_description,
        COUNT(DISTINCT ki.knowledge_id) AS matched_knowledge_count,
        AVG(1 - (ki.intent_vector <=> im.intent_embedding)) AS avg_similarity
    FROM cmis_knowledge.intent_mappings im
    LEFT JOIN cmis_knowledge.index ki 
        ON ki.intent_vector IS NOT NULL 
        AND 1 - (ki.intent_vector <=> im.intent_embedding) > 0.6
    WHERE im.is_active = true
    GROUP BY im.intent_id, im.intent_name, im.intent_name_ar, im.intent_description
)
SELECT 
    intent_name AS "Intent",
    intent_name_ar AS "النية",
    intent_description AS "الوصف",
    matched_knowledge_count AS "عدد المعارف المرتبطة",
    ROUND(avg_similarity::numeric * 100, 2) AS "متوسط التشابه %",
    CASE 
        WHEN matched_knowledge_count > 10 THEN '🟢 نشط جداً'
        WHEN matched_knowledge_count > 5 THEN '🟡 نشط'
        WHEN matched_knowledge_count > 0 THEN '🟠 محدود'
        ELSE '🔴 غير مستخدم'
    END AS "الحالة"
FROM intent_stats
ORDER BY matched_knowledge_count DESC;

-- View لأداء البحث الدلالي
CREATE OR REPLACE VIEW cmis_knowledge.v_search_performance AS
SELECT 
    DATE_TRUNC('hour', created_at) AS "الساعة",
    COUNT(*) AS "عدد عمليات البحث",
    AVG(result_count) AS "متوسط النتائج",
    ROUND(AVG(avg_similarity)::numeric, 4) AS "متوسط التشابه",
    PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY avg_similarity) AS "الوسيط",
    MAX(avg_similarity) AS "أعلى تشابه",
    MIN(avg_similarity) AS "أقل تشابه"
FROM cmis_knowledge.semantic_search_results_cache
WHERE created_at > now() - interval '24 hours'
GROUP BY DATE_TRUNC('hour', created_at)
ORDER BY "الساعة" DESC;

-- View لقائمة انتظار التحديث
CREATE OR REPLACE VIEW cmis_knowledge.v_embedding_queue_status AS
SELECT 
    status AS "الحالة",
    COUNT(*) AS "العدد",
    AVG(retry_count) AS "متوسط المحاولات",
    MIN(created_at) AS "أقدم طلب",
    MAX(created_at) AS "أحدث طلب",
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
-- PHASE 10: دوال المعالجة والصيانة
-- =====================================================

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
    -- معالجة الطلبات المعلقة
    FOR v_rec IN
        SELECT queue_id, ki.knowledge_id
        FROM cmis_knowledge.embedding_update_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT p_batch_size
        FOR UPDATE SKIP LOCKED
    LOOP
        BEGIN
            -- تحديث الحالة إلى processing
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing', processed_at = now()
            WHERE queue_id = v_rec.queue_id;
            
            -- معالجة embedding
            PERFORM cmis_knowledge.update_single_embedding(v_rec.knowledge_id);
            
            -- تحديث الحالة إلى completed
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'completed'
            WHERE queue_id = v_rec.queue_id;
            
            v_processed := v_processed + 1;
            
        EXCEPTION WHEN OTHERS THEN
            -- تسجيل الخطأ
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
    
    -- تحديث إحصائيات الاستخدام
    UPDATE cmis_knowledge.embeddings_cache
    SET usage_count = 0
    WHERE last_accessed < now() - interval '30 days';
END;
$$;

-- =====================================================
-- PHASE 11: إدراج بيانات أولية للاختبار
-- =====================================================

-- إدراج نوايا أساسية
INSERT INTO cmis_knowledge.intent_mappings (
    intent_name, intent_name_ar, intent_description, 
    intent_embedding, related_keywords, related_keywords_ar
) VALUES 
    ('increase_sales', 'زيادة المبيعات', 'النية لزيادة المبيعات والإيرادات',
     cmis_knowledge.generate_embedding_mock('increase sales revenue growth'), 
     ARRAY['sales', 'revenue', 'growth'], 
     ARRAY['مبيعات', 'إيرادات', 'نمو']),
    
    ('brand_awareness', 'الوعي بالعلامة التجارية', 'بناء وتعزيز الوعي بالعلامة التجارية',
     cmis_knowledge.generate_embedding_mock('brand awareness recognition marketing'),
     ARRAY['brand', 'awareness', 'recognition'],
     ARRAY['علامة تجارية', 'وعي', 'تعريف']),
    
    ('customer_engagement', 'تفاعل العملاء', 'زيادة التفاعل مع العملاء',
     cmis_knowledge.generate_embedding_mock('customer engagement interaction loyalty'),
     ARRAY['engagement', 'interaction', 'loyalty'],
     ARRAY['تفاعل', 'مشاركة', 'ولاء'])
ON CONFLICT (intent_name) DO NOTHING;

-- إدراج اتجاهات أساسية
INSERT INTO cmis_knowledge.direction_mappings (
    direction_name, direction_name_ar, direction_type,
    direction_embedding
) VALUES
    ('digital_transformation', 'التحول الرقمي', 'strategic',
     cmis_knowledge.generate_embedding_mock('digital transformation technology innovation')),
    
    ('market_expansion', 'التوسع في السوق', 'strategic',
     cmis_knowledge.generate_embedding_mock('market expansion growth new segments')),
    
    ('operational_efficiency', 'الكفاءة التشغيلية', 'operational',
     cmis_knowledge.generate_embedding_mock('operational efficiency optimization productivity'))
ON CONFLICT (direction_name) DO NOTHING;

-- إدراج مقاصد أساسية
INSERT INTO cmis_knowledge.purpose_mappings (
    purpose_name, purpose_name_ar, purpose_category,
    purpose_embedding
) VALUES
    ('roi_maximization', 'تعظيم العائد على الاستثمار', 'financial',
     cmis_knowledge.generate_embedding_mock('ROI return investment maximization profit')),
    
    ('customer_satisfaction', 'رضا العملاء', 'customer',
     cmis_knowledge.generate_embedding_mock('customer satisfaction happiness loyalty retention')),
    
    ('innovation_leadership', 'الريادة في الابتكار', 'innovation',
     cmis_knowledge.generate_embedding_mock('innovation leadership pioneering creativity'))
ON CONFLICT (purpose_name) DO NOTHING;

-- =====================================================
-- PHASE 12: دوال التقارير والإحصائيات
-- =====================================================

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
            (SELECT AVG(usage_count) FROM cmis_knowledge.embeddings_cache) AS avg_cache_usage
    )
    SELECT jsonb_build_object(
        'timestamp', now(),
        'knowledge_stats', jsonb_build_object(
            'total', total_knowledge,
            'embedded', embedded_knowledge,
            'coverage_percentage', ROUND((embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) * 100, 2)
        ),
        'intent_system', jsonb_build_object(
            'active_intents', active_intents,
            'active_directions', active_directions,
            'active_purposes', active_purposes
        ),
        'processing', jsonb_build_object(
            'pending_updates', pending_updates,
            'avg_cache_usage', ROUND(avg_cache_usage::numeric, 2)
        ),
        'health_status', CASE 
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.8 THEN 'healthy'
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.5 THEN 'warning'
            ELSE 'critical'
        END
    ) INTO v_report
    FROM stats;
    
    RETURN v_report;
END;
$$;

-- =====================================================
-- PHASE 13: Grants والصلاحيات
-- =====================================================

-- منح الصلاحيات للمستخدمين
GRANT USAGE ON SCHEMA cmis_knowledge TO begin;
GRANT SELECT, INSERT, UPDATE ON ALL TABLES IN SCHEMA cmis_knowledge TO begin;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA cmis_knowledge TO begin;

-- =====================================================
-- التحقق النهائي
-- =====================================================

-- إنشاء دالة للتحقق من صحة التثبيت
CREATE OR REPLACE FUNCTION cmis_knowledge.verify_installation()
RETURNS jsonb
LANGUAGE plpgsql
AS $$
DECLARE
    v_checks jsonb := '[]'::jsonb;
    v_check jsonb;
BEGIN
    -- التحقق من pgvector
    IF EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'vector') THEN
        v_check := jsonb_build_object('check', 'pgvector extension', 'status', 'installed', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'pgvector extension', 'status', 'not installed', 'result', false);
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الأعمدة الجديدة
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_schema = 'cmis_knowledge' 
               AND table_name = 'index' 
               AND column_name = 'topic_embedding') THEN
        v_check := jsonb_build_object('check', 'vector columns', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'vector columns', 'status', 'not created', 'result', false);
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الفهارس
    IF EXISTS (SELECT 1 FROM pg_indexes 
               WHERE schemaname = 'cmis_knowledge' 
               AND indexname = 'idx_index_topic_embedding') THEN
        v_check := jsonb_build_object('check', 'vector indexes', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'vector indexes', 'status', 'not created', 'result', false);
    END IF;
    v_checks := v_checks || v_check;
    
    -- التحقق من الدوال
    IF EXISTS (SELECT 1 FROM pg_proc 
               WHERE pronamespace = 'cmis_knowledge'::regnamespace 
               AND proname = 'semantic_search_advanced') THEN
        v_check := jsonb_build_object('check', 'search functions', 'status', 'created', 'result', true);
    ELSE
        v_check := jsonb_build_object('check', 'search functions', 'status', 'not created', 'result', false);
    END IF;
    v_checks := v_checks || v_check;
    
    RETURN jsonb_build_object(
        'installation_complete', (SELECT bool_and((c->>'result')::boolean) FROM jsonb_array_elements(v_checks) c),
        'checks', v_checks,
        'timestamp', now()
    );
END;
$$;

-- تنفيذ التحقق
SELECT cmis_knowledge.verify_installation();

-- =====================================================
-- رسالة النهاية
-- =====================================================
DO $$
BEGIN
    RAISE NOTICE '=====================================================';
    RAISE NOTICE 'Vector Embeddings Migration Script - تم التنفيذ بنجاح';
    RAISE NOTICE '=====================================================';
    RAISE NOTICE 'الخطوات التالية:';
    RAISE NOTICE '1. تكوين Gemini API key في جدول embedding_api_config';
    RAISE NOTICE '2. استبدال دالة generate_embedding_mock بالتكامل الفعلي مع Gemini';
    RAISE NOTICE '3. تشغيل batch_update_embeddings() لتوليد embeddings للبيانات الموجودة';
    RAISE NOTICE '4. إعداد cron job لمعالجة embedding_update_queue بشكل دوري';
    RAISE NOTICE '5. مراقبة الأداء من خلال v_embedding_status و v_search_performance';
    RAISE NOTICE '=====================================================';
END $$;

-- =====================================================
-- نموذج استعلامات للاختبار
-- =====================================================

-- 1. التحقق من حالة التثبيت
SELECT * FROM cmis_knowledge.verify_installation();

-- 2. عرض حالة Embeddings
SELECT * FROM cmis_knowledge.v_embedding_status;

-- 3. توليد embeddings لدفعة تجريبية
SELECT cmis_knowledge.batch_update_embeddings(10, 'dev');

-- 4. اختبار البحث الدلالي
SELECT * FROM cmis_knowledge.semantic_search_advanced(
    'كيفية تحسين أداء الحملات التسويقية',
    'improve_performance',
    'optimization',
    'roi_maximization',
    'marketing',
    5
);

-- 5. عرض تقرير النظام
SELECT cmis_knowledge.generate_system_report();

-- 6. معالجة قائمة الانتظار
SELECT cmis_knowledge.process_embedding_queue(5);

-- 7. عرض حالة قائمة الانتظار
SELECT * FROM cmis_knowledge.v_embedding_queue_status;
