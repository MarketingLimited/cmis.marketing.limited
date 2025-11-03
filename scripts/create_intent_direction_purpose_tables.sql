-- إنشاء جدول intent_mappings
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

-- إنشاء جدول direction_mappings
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

-- إنشاء جدول purpose_mappings
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

-- إنشاء الفهارس HNSW لهذه الجداول
CREATE INDEX IF NOT EXISTS idx_intent_embedding ON cmis_knowledge.intent_mappings USING hnsw (intent_embedding vector_cosine_ops);
CREATE INDEX IF NOT EXISTS idx_direction_embedding ON cmis_knowledge.direction_mappings USING hnsw (direction_embedding vector_cosine_ops);
CREATE INDEX IF NOT EXISTS idx_purpose_embedding ON cmis_knowledge.purpose_mappings USING hnsw (purpose_embedding vector_cosine_ops);