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

CREATE INDEX IF NOT EXISTS idx_embedding_queue_status 
ON cmis_knowledge.embedding_update_queue (status, priority DESC)
WHERE status IN ('pending', 'processing');