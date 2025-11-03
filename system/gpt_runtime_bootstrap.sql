-- 🧩 CMIS GPT Runtime Bootstrap Script
-- هذا الملف يقوم بتهيئة كل المكونات الأساسية للنظام الإدراكي CMIS Orchestrator دفعة واحدة.
-- يجب تنفيذه في بيئة PostgreSQL بعد رفع المشروع.

BEGIN;

-- 🔹 إنشاء الـ Schemas الأساسية
CREATE SCHEMA IF NOT EXISTS cmis_dev;
CREATE SCHEMA IF NOT EXISTS cmis_knowledge;
CREATE SCHEMA IF NOT EXISTS cmis_audit;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 🔹 جداول المعرفة الأساسية
CREATE TABLE IF NOT EXISTS cmis_knowledge.index (
  knowledge_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  domain text NOT NULL,
  category text NOT NULL CHECK (category IN ('dev', 'marketing', 'org', 'research')),
  topic text NOT NULL,
  keywords text[],
  tier smallint DEFAULT 2 CHECK (tier IN (1,2,3)),
  token_budget int DEFAULT 1200,
  supersedes_knowledge_id uuid,
  is_deprecated boolean DEFAULT false,
  last_verified_at timestamptz DEFAULT now()
);

-- 🔹 جداول المهام التشغيلية
CREATE TABLE IF NOT EXISTS cmis_dev.dev_tasks (
  task_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  name text,
  description text,
  scope_code text,
  status text DEFAULT 'pending',
  priority smallint DEFAULT 3,
  execution_plan jsonb,
  confidence numeric(3,2),
  effectiveness_score smallint,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz,
  result_summary text
);

CREATE TABLE IF NOT EXISTS cmis_dev.dev_logs (
  log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  task_id uuid REFERENCES cmis_dev.dev_tasks(task_id),
  event text,
  details jsonb,
  created_at timestamptz DEFAULT now()
);

-- 🔹 جداول الأمان والتدقيق
CREATE TABLE IF NOT EXISTS cmis_access_control (
  rule_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  resource_type text,
  resource_id uuid,
  actor text,
  permission text CHECK (permission IN ('read', 'write', 'execute', 'admin')),
  granted_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS cmis_audit.security_logs (
  log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  event_type text,
  actor text,
  details jsonb,
  severity text CHECK (severity IN ('info','warning','critical')),
  created_at timestamptz DEFAULT now()
);

CREATE TABLE IF NOT EXISTS cmis_audit.activity_log (
  log_id uuid DEFAULT gen_random_uuid() PRIMARY KEY,
  actor text,
  action text,
  context jsonb,
  category text CHECK (category IN ('task','knowledge','security','system')),
  created_at timestamptz DEFAULT now()
);

-- 🔹 دالة تحميل السياق الذكية
CREATE OR REPLACE FUNCTION load_context_by_priority(
    p_domain text,
    p_category text DEFAULT NULL,
    p_max_tokens int DEFAULT 5000
) RETURNS TABLE (knowledge_id uuid, content text, tier smallint, token_count int, cumulative_tokens bigint)
AS $$
BEGIN
    RETURN QUERY
    WITH ranked_knowledge AS (
        SELECT 
            ki.knowledge_id,
            CASE p_category
                WHEN 'dev' THEN kd.content
                WHEN 'marketing' THEN km.content
                WHEN 'org' THEN ko.content
                WHEN 'research' THEN kr.content
            END as content,
            ki.tier,
            COALESCE(
                kd.token_count,
                km.token_count,
                ko.token_count,
                kr.token_count
            ) as token_count,
            SUM(COALESCE(
                kd.token_count,
                km.token_count,
                ko.token_count,
                kr.token_count
            )) OVER (ORDER BY ki.tier ASC, ki.last_verified_at DESC) as cumulative_tokens
        FROM cmis_knowledge.index ki
        LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
        LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
        LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
        LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
        WHERE ki.domain = p_domain
          AND (p_category IS NULL OR ki.category = p_category)
          AND ki.is_deprecated = false
    )
    SELECT * FROM ranked_knowledge WHERE cumulative_tokens <= p_max_tokens;
END;
$$ LANGUAGE plpgsql;

-- 🔹 لوحة التحكم اللحظية (Realtime Dashboard)
CREATE OR REPLACE VIEW cmis_audit.realtime_status AS
SELECT 
  COUNT(*) FILTER (WHERE category='task' AND action='task_failed') AS recent_failures,
  COUNT(*) FILTER (WHERE category='security') AS security_events,
  COUNT(*) FILTER (WHERE category='knowledge') AS knowledge_updates,
  MAX(created_at) AS last_update
FROM cmis_audit.activity_log
WHERE created_at > now() - interval '1 hour';

COMMIT;

-- ✅ جاهز للتشغيل.
-- بعد تنفيذ هذا السكربت يمكنك استخدام جميع وظائف CMIS Orchestrator فورًا.