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

CREATE INDEX IF NOT EXISTS idx_search_logs_time 
ON cmis_knowledge.semantic_search_logs (created_at DESC);