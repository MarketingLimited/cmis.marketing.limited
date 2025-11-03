UPDATE cmis_knowledge.embeddings_cache
SET embedding_norm = sqrt((embedding <=> embedding)::float)
WHERE embedding IS NOT NULL;