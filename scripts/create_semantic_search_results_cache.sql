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

CREATE INDEX IF NOT EXISTS idx_search_cache_hash 
ON cmis_knowledge.semantic_search_results_cache (query_hash);