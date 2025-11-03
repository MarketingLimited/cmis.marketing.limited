CREATE TABLE IF NOT EXISTS cmis_knowledge.embeddings_cache (
    cache_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
    source_table text NOT NULL,
    source_id uuid NOT NULL,
    source_field text NOT NULL,
    embedding vector(768) NOT NULL,
    embedding_norm float,
    metadata jsonb DEFAULT '{}',
    model_version text DEFAULT 'gemini-text-embedding-004',
    quality_score numeric(3,2),
    usage_count integer DEFAULT 0,
    last_accessed timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    UNIQUE(source_table, source_id, source_field)
);

CREATE INDEX IF NOT EXISTS idx_embeddings_cache_vector 
ON cmis_knowledge.embeddings_cache USING hnsw (embedding vector_cosine_ops);

CREATE INDEX IF NOT EXISTS idx_embeddings_cache_source 
ON cmis_knowledge.embeddings_cache (source_table, source_id);