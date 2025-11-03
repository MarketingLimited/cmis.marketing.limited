ALTER TABLE cmis_knowledge.embeddings_cache DROP COLUMN IF EXISTS embedding_norm;
ALTER TABLE cmis_knowledge.embeddings_cache ADD COLUMN embedding_norm float;

-- تحديث القيم الحالية للعمود بعد إنشائه
UPDATE cmis_knowledge.embeddings_cache
SET embedding_norm = sqrt((embedding <=> embedding)::float)
WHERE embedding IS NOT NULL;