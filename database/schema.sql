--
-- PostgreSQL database dump
--

-- Dumped from database version 12.22 (Ubuntu 12.22-0ubuntu0.20.04.4)
-- Dumped by pg_dump version 17.5 (Ubuntu 17.5-1.pgdg20.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: cmis; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis;


ALTER SCHEMA cmis OWNER TO begin;

--
-- Name: cmis_ai_analytics; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_ai_analytics;


ALTER SCHEMA cmis_ai_analytics OWNER TO begin;

--
-- Name: cmis_analytics; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_analytics;


ALTER SCHEMA cmis_analytics OWNER TO begin;

--
-- Name: cmis_ops; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_ops;


ALTER SCHEMA cmis_ops OWNER TO begin;

--
-- Name: cmis_refactored; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_refactored;


ALTER SCHEMA cmis_refactored OWNER TO begin;

--
-- Name: cmis_staging; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_staging;


ALTER SCHEMA cmis_staging OWNER TO begin;

--
-- Name: lab; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA lab;


ALTER SCHEMA lab OWNER TO begin;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: citext; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public;


--
-- Name: EXTENSION citext; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION citext IS 'data type for case-insensitive character strings';


--
-- Name: ltree; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS ltree WITH SCHEMA public;


--
-- Name: EXTENSION ltree; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION ltree IS 'data type for hierarchical tree-like structures';


--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: auto_delete_unapproved_assets(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.auto_delete_unapproved_assets() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  DELETE FROM cmis.creative_assets
  WHERE status = 'draft'
    AND created_at < NOW() - INTERVAL '7 days';
END;
$$;


ALTER FUNCTION cmis.auto_delete_unapproved_assets() OWNER TO begin;

--
-- Name: cleanup_scheduler(); Type: PROCEDURE; Schema: cmis; Owner: begin
--

CREATE PROCEDURE cmis.cleanup_scheduler()
    LANGUAGE sql
    AS $$
  SELECT cmis.auto_delete_unapproved_assets();
$$;


ALTER PROCEDURE cmis.cleanup_scheduler() OWNER TO begin;

--
-- Name: create_campaign_and_context_safe(uuid, uuid, uuid, text, text, text, text[]); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.create_campaign_and_context_safe(p_org_id uuid, p_offering_id uuid, p_segment_id uuid, p_campaign_name text, p_framework text, p_tone text, p_tags text[]) RETURNS TABLE(campaign_id uuid, context_id uuid, creative_context_id uuid)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_campaign_id uuid := gen_random_uuid();
  v_value_context_id uuid;
  v_creative_context_id uuid;
BEGIN
  IF p_org_id IS NULL OR p_offering_id IS NULL OR p_segment_id IS NULL OR p_campaign_name IS NULL THEN
    RAISE EXCEPTION 'Missing required parameters for campaign creation.';
  END IF;

  -- إنشاء السياق القيمي (Value Context)
  INSERT INTO cmis.value_contexts (org_id, offering_id, segment_id, locale, awareness_stage, framework, tone, tags)
  VALUES (p_org_id, p_offering_id, p_segment_id, 'ar-BH', 'awareness', p_framework, p_tone, p_tags)
  RETURNING value_contexts.context_id INTO v_value_context_id;

  -- إنشاء السياق الإبداعي (Creative Context)
  INSERT INTO cmis.creative_contexts (org_id, name, creative_brief)
  VALUES (p_org_id, p_campaign_name || ' - Creative Context', jsonb_build_object(
    'framework', p_framework,
    'tone', p_tone,
    'tags', p_tags
  ))
  RETURNING creative_contexts.context_id INTO v_creative_context_id;

  -- إدراج الحملة مع ربطها بالسياقين
  INSERT INTO cmis.campaigns (campaign_id, org_id, name, objective, start_date, end_date, status, context_id, creative_context_id)
  VALUES (v_campaign_id, p_org_id, p_campaign_name, 'conversion', CURRENT_DATE, CURRENT_DATE + INTERVAL '30 days', 'active', v_value_context_id, v_creative_context_id);

  -- تحديث الربط في value_contexts
  UPDATE cmis.value_contexts
  SET campaign_id = v_campaign_id
  WHERE cmis.value_contexts.context_id = v_value_context_id;

  RETURN QUERY SELECT v_campaign_id, v_value_context_id, v_creative_context_id;
END;$$;


ALTER FUNCTION cmis.create_campaign_and_context_safe(p_org_id uuid, p_offering_id uuid, p_segment_id uuid, p_campaign_name text, p_framework text, p_tone text, p_tags text[]) OWNER TO begin;

--
-- Name: enforce_creative_context(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.enforce_creative_context() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF NEW.creative_context_id IS NULL THEN
    RAISE EXCEPTION 'Creative Context is required for this entity';
  END IF;
  RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.enforce_creative_context() OWNER TO begin;

--
-- Name: prevent_incomplete_briefs(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.prevent_incomplete_briefs() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  required_keys TEXT[] := ARRAY[
    'marketing_objective', 'emotional_trigger', 'Hooks', 'channels', 'segments',
    'pains', 'Marketing frameworks', 'marketing_strategies', 'awareness_stage',
    'funnel_stage', 'Tones', 'features', 'benefits', 'transformational_benefits',
    'usps', 'message_map', 'proofs', 'brand', 'guardrails', 'seasonality',
    'style', 'offer', 'pricing', 'CTA', 'Content', 'Formats', 'Art Direction',
    'Mood', 'Visual Message', 'Look & Feel', 'Color Palette', 'Typography',
    'Imagery & Graphics', 'Icons & Symbols', 'Composition & Layout', 'Amplify',
    'Story/Solution', 'design_description', 'background', 'lighting', 'highlight',
    'de_emphasize', 'element_positions', 'ratio', 'motion'
  ];
  missing_keys TEXT[] := ARRAY[]::TEXT[];
  k TEXT;
BEGIN
  FOREACH k IN ARRAY required_keys LOOP
    IF NOT (NEW.brief_data ? k) THEN
      missing_keys := array_append(missing_keys, k);
    END IF;
  END LOOP;

  IF array_length(missing_keys, 1) > 0 THEN
    RAISE EXCEPTION 'Creative brief is missing required fields: %', array_to_string(missing_keys, ',');
  END IF;

  RETURN NEW;
END;$$;


ALTER FUNCTION cmis.prevent_incomplete_briefs() OWNER TO begin;

--
-- Name: sync_social_metrics(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.sync_social_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN SELECT id, org_id, platform, access_token, external_id
               FROM cmis.integrations
               WHERE account_type = 'social' AND is_active
    LOOP
        INSERT INTO cmis.audit_log (org_id, actor, action, target, meta)
        VALUES (rec.org_id, 'system', 'sync_social_metrics_start', rec.platform, jsonb_build_object('integration_id', rec.id));

        -- Placeholder: تُنفذ هذه الخطوة في الطبقة التطبيقية لاستدعاء Meta Graph API الفعلية
        -- البيانات تُدرج هنا بعد معالجتها خارجياً

        INSERT INTO cmis.audit_log (org_id, actor, action, target, meta)
        VALUES (rec.org_id, 'system', 'sync_social_metrics_end', rec.platform, jsonb_build_object('integration_id', rec.id));
    END LOOP;
END;$$;


ALTER FUNCTION cmis.sync_social_metrics() OWNER TO begin;

--
-- Name: fn_recommend_focus(); Type: FUNCTION; Schema: cmis_ai_analytics; Owner: begin
--

CREATE FUNCTION cmis_ai_analytics.fn_recommend_focus() RETURNS TABLE(recommendation jsonb)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT jsonb_build_object(
    'top_performing_context', (SELECT context_type FROM cmis_ai_analytics.v_context_impact ORDER BY impact_score DESC LIMIT 1),
    'weakest_asset_type', (SELECT output_type FROM cmis_ai_analytics.v_creative_efficiency ORDER BY efficiency_score ASC LIMIT 1),
    'best_campaign', (SELECT campaign_name FROM cmis_ai_analytics.v_kpi_summary ORDER BY performance_rate DESC LIMIT 1),
    'timestamp', NOW()
  );
END;
$$;


ALTER FUNCTION cmis_ai_analytics.fn_recommend_focus() OWNER TO begin;

--
-- Name: report_migrations(); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

CREATE FUNCTION cmis_analytics.report_migrations() RETURNS TABLE(executed_at timestamp with time zone, action text, sql_preview text)
    LANGUAGE sql
    AS $$
  SELECT executed_at, action, LEFT(sql_code, 200) || '...' AS sql_preview
  FROM cmis_analytics.migration_log
  ORDER BY executed_at DESC;
$$;


ALTER FUNCTION cmis_analytics.report_migrations() OWNER TO begin;

--
-- Name: run_ai_query(uuid, text); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

CREATE FUNCTION cmis_analytics.run_ai_query(p_org_id uuid, p_prompt text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    matched_template RECORD;
    generated_sql TEXT;
    result_summary TEXT;
BEGIN
    SELECT * INTO matched_template
    FROM cmis_analytics.prompt_templates
    ORDER BY similarity(prompt_text, p_prompt) DESC
    LIMIT 1;

    IF matched_template IS NULL THEN
        result_summary := 'لم يتم العثور على قالب مناسب للسؤال.';
    ELSE
        generated_sql := matched_template.sql_snippet;
        result_summary := 'تم تنفيذ القالب: ' || matched_template.name;
    END IF;

    INSERT INTO cmis_analytics.ai_queries (org_id, user_prompt, generated_sql, result_summary, confidence_score)
    VALUES (p_org_id, p_prompt, generated_sql, result_summary, 0.85);
END; $$;


ALTER FUNCTION cmis_analytics.run_ai_query(p_org_id uuid, p_prompt text) OWNER TO begin;

--
-- Name: snapshot_performance(); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

CREATE FUNCTION cmis_analytics.snapshot_performance() RETURNS TABLE(campaign_id uuid, campaign_name text, output_id uuid, kpi text, observed numeric, observed_at timestamp with time zone, trend_direction text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        pm.campaign_id,
        c.name AS campaign_name,
        pm.output_id,
        pm.kpi,
        pm.observed,
        pm.observed_at,
        CASE
            WHEN pm.observed > LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'up'
            WHEN pm.observed < LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'down'
            ELSE 'stable'
        END AS trend_direction
    FROM cmis.performance_metrics pm
    JOIN cmis.campaigns c ON pm.campaign_id = c.campaign_id
    WHERE pm.observed_at >= NOW() - INTERVAL '30 days'
    ORDER BY pm.observed_at DESC;
END;
$$;


ALTER FUNCTION cmis_analytics.snapshot_performance() OWNER TO begin;

--
-- Name: snapshot_performance(integer); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

CREATE FUNCTION cmis_analytics.snapshot_performance(snapshot_days integer DEFAULT 30) RETURNS TABLE(campaign_id uuid, campaign_name text, output_id uuid, kpi text, observed numeric, observed_at timestamp with time zone, trend_direction text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        pm.campaign_id,
        c.name AS campaign_name,
        pm.output_id,
        pm.kpi,
        pm.observed,
        pm.observed_at,
        CASE
            WHEN pm.observed > LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'up'
            WHEN pm.observed < LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'down'
            ELSE 'stable'
        END AS trend_direction
    FROM cmis.performance_metrics pm
    JOIN cmis.campaigns c ON pm.campaign_id = c.campaign_id
    WHERE pm.observed_at >= NOW() - (snapshot_days || ' days')::interval
    ORDER BY pm.observed_at DESC;
END;
$$;


ALTER FUNCTION cmis_analytics.snapshot_performance(snapshot_days integer) OWNER TO begin;

--
-- Name: cleanup_stale_assets(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.cleanup_stale_assets() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🧹 تنظيف الأصول القديمة غير النشطة...';
  DELETE FROM cmis_refactored.creative_outputs
  WHERE status = 'draft' AND created_at < NOW() - INTERVAL '90 days';
  RAISE NOTICE '✅ تم حذف الأصول القديمة بنجاح.';
END;
$$;


ALTER FUNCTION cmis_ops.cleanup_stale_assets() OWNER TO begin;

--
-- Name: generate_ai_summary(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.generate_ai_summary() RETURNS TABLE(campaign_id uuid, summary jsonb)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT c.campaign_id,
         jsonb_build_object(
           'name', c.name,
           'status', c.status,
           'avg_kpi', AVG(pm.observed),
           'top_contexts', jsonb_agg(DISTINCT ctx.type),
           'assets_count', COUNT(DISTINCT co.output_id)
         ) AS summary
  FROM cmis_refactored.campaigns c
  LEFT JOIN cmis_refactored.contexts ctx ON ctx.campaign_id = c.campaign_id
  LEFT JOIN cmis_refactored.creative_outputs co ON co.campaign_id = c.campaign_id
  LEFT JOIN cmis_refactored.performance_metrics pm ON pm.campaign_id = c.campaign_id
  GROUP BY c.campaign_id;
END;
$$;


ALTER FUNCTION cmis_ops.generate_ai_summary() OWNER TO begin;

--
-- Name: normalize_metrics(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.normalize_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$ BEGIN INSERT INTO cmis_refactored.performance_metrics (metric_id, org_id, campaign_id, output_id, kpi, observed, target, baseline, observed_at) SELECT gen_random_uuid(), i.org_id, NULL, NULL, (payload->>'metric_name')::text, (payload->>'value')::numeric, NULL, NULL, (payload->>'timestamp')::timestamp FROM cmis_staging.raw_channel_data d JOIN cmis_refactored.integrations i ON i.integration_id = d.integration_id WHERE d.platform = 'facebook' ON CONFLICT DO NOTHING; END; $$;


ALTER FUNCTION cmis_ops.normalize_metrics() OWNER TO begin;

--
-- Name: refresh_ai_insights(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.refresh_ai_insights() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🔄 بدء تحديث مؤشرات الذكاء الاصطناعي...';
  UPDATE cmis_refactored.performance_metrics pm
  SET observed = observed + (RANDOM() * 0.05 * COALESCE(target, 100)),
      observed_at = NOW()
  WHERE observed IS NOT NULL;
  RAISE NOTICE '✅ تم تحديث مؤشرات الأداء التحليلية.';
END;
$$;


ALTER FUNCTION cmis_ops.refresh_ai_insights() OWNER TO begin;

--
-- Name: sync_integrations(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.sync_integrations() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🔗 مزامنة تكاملات المنصات...';
  UPDATE cmis_refactored.integrations
  SET updated_at = NOW()
  WHERE is_active = TRUE;
  RAISE NOTICE '✅ تم تحديث بيانات الربط بنجاح.';
END;
$$;


ALTER FUNCTION cmis_ops.sync_integrations() OWNER TO begin;

--
-- Name: auto_predictive_campaign(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.auto_predictive_campaign() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.predictive_visual_engine (campaign_id, visual_factor_weight, predicted_ctr, predicted_engagement, predicted_trust_index, confidence_level, created_at)
    VALUES (NEW.campaign_id, NEW.visual_factor_weight, NEW.predicted_ctr, NEW.predicted_engagement, NEW.predicted_trust_index, NEW.confidence_level, NOW());
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.auto_predictive_campaign() OWNER TO begin;

--
-- Name: auto_update_cognitive_trends(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.auto_update_cognitive_trends() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.cognitive_trends (org_id, factor_name, trend_direction, growth_rate, trend_strength, summary_insight, created_at)
    VALUES (NEW.org_id, NEW.factor_name, NEW.trend_direction, NEW.growth_rate, NEW.trend_strength, NEW.summary_insight, NOW());
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.auto_update_cognitive_trends() OWNER TO begin;

--
-- Name: cognitive_console_report(text); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.cognitive_console_report(mode text DEFAULT 'summary'::text) RETURNS TABLE(campaign_name text, objective text, predicted_ctr double precision, predicted_engagement double precision, predicted_trust_index double precision, confidence_level double precision, dominant_visual_factor text, recommendation text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.ai_summary AS campaign_name,
        a.objective_code AS objective,
        p.predicted_ctr,
        p.predicted_engagement,
        p.predicted_trust_index,
        p.confidence_level,
        (SELECT key FROM jsonb_each_text(p.visual_factor_weight) ORDER BY value::numeric DESC LIMIT 1) AS dominant_visual_factor,
        CASE 
            WHEN p.predicted_ctr > 0.9 THEN 'استمر بنفس مستوى التباين، الأداء بصري ممتاز.'
            WHEN p.predicted_engagement < 0.8 THEN 'يُنصح بتبسيط العناصر وتقليل النصوص لتحسين التفاعل.'
            WHEN p.predicted_trust_index < 0.75 THEN 'أضف عناصر موثوقة مثل رموز الأمان أو شهادات.'
            ELSE 'التصميم متوازن إدراكيًا، حافظ على هذا النمط.'
        END AS recommendation
    FROM cmis.ai_generated_campaigns a
    JOIN cmis.predictive_visual_engine p ON a.campaign_id = p.campaign_id
    ORDER BY a.created_at DESC;
END;
$$;


ALTER FUNCTION public.cognitive_console_report(mode text) OWNER TO begin;

--
-- Name: run_auto_predictive_trigger(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.run_auto_predictive_trigger() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.ai_generated_campaigns (
        org_id,
        objective_code,
        recommended_principle,
        linked_kpi,
        ai_summary,
        ai_design_guideline
    )
    VALUES (
        (SELECT org_id FROM cmis.orgs LIMIT 1),
        'conversion',
        'clarity of message',
        'CTR',
        'حملة جديدة لإدارة السوشيال ميديا - أكتوبر 2025 (تشغيل تلقائي للتنبؤ الإدراكي)',
        'تصميم يعتمد على التباين العالي والتركيز على CTA واضح'
    );
END;
$$;


ALTER FUNCTION public.run_auto_predictive_trigger() OWNER TO begin;

--
-- Name: scheduled_cognitive_trend_update(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.scheduled_cognitive_trend_update() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  PERFORM public.update_cognitive_trends();
END;
$$;


ALTER FUNCTION public.scheduled_cognitive_trend_update() OWNER TO begin;

--
-- Name: update_cognitive_trends(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.update_cognitive_trends() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    factor TEXT;
    avg_current DOUBLE PRECISION;
    avg_previous DOUBLE PRECISION;
    growth DOUBLE PRECISION;
    direction TEXT;
    summary TEXT;
BEGIN
    FOR factor IN SELECT DISTINCT key FROM cmis.predictive_visual_engine, jsonb_each(visual_factor_weight) LOOP
        SELECT AVG((visual_factor_weight ->> factor)::DOUBLE PRECISION) INTO avg_current FROM cmis.predictive_visual_engine WHERE created_at >= NOW() - INTERVAL '14 days';
        SELECT AVG((visual_factor_weight ->> factor)::DOUBLE PRECISION) INTO avg_previous FROM cmis.predictive_visual_engine WHERE created_at < NOW() - INTERVAL '14 days' AND created_at >= NOW() - INTERVAL '28 days';

        IF avg_previous IS NULL THEN
            CONTINUE;
        END IF;

        growth := ((avg_current - avg_previous) / avg_previous) * 100;

        IF growth > 3 THEN
            direction := 'up';
            summary := 'العامل الإدراكي ' || factor || ' في ارتفاع بنسبة ' || ROUND(growth, 2) || '% خلال آخر أسبوعين.';
        ELSIF growth < -3 THEN
            direction := 'down';
            summary := 'العامل الإدراكي ' || factor || ' في تراجع بنسبة ' || ROUND(growth, 2) || '% خلال آخر أسبوعين.';
        ELSE
            direction := 'stable';
            summary := 'العامل الإدراكي ' || factor || ' مستقر تقريبًا خلال الفترة الأخيرة.';
        END IF;

        INSERT INTO cmis.cognitive_trends (org_id, factor_name, trend_direction, growth_rate, trend_strength, summary_insight)
        VALUES ((SELECT org_id FROM cmis.orgs LIMIT 1), factor, direction, growth, ABS(growth)/10, summary);
    END LOOP;
END;
$$;


ALTER FUNCTION public.update_cognitive_trends() OWNER TO begin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ad_accounts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_accounts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    account_external_id text NOT NULL,
    name text,
    currency text,
    timezone text,
    spend_cap numeric,
    status text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.ad_accounts OWNER TO begin;

--
-- Name: ad_audiences; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_audiences (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    entity_level text NOT NULL,
    entity_external_id text NOT NULL,
    audience_type text,
    platform text,
    demographics jsonb,
    interests jsonb,
    behaviors jsonb,
    location jsonb,
    keywords jsonb,
    custom_audience jsonb,
    lookalike_audience jsonb,
    advantage_plus_settings jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT ad_audiences_entity_level_check CHECK ((entity_level = ANY (ARRAY['campaign'::text, 'adset'::text, 'adgroup'::text])))
);


ALTER TABLE cmis.ad_audiences OWNER TO begin;

--
-- Name: ad_campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_campaigns (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    campaign_external_id text NOT NULL,
    name text,
    objective text,
    start_date date,
    end_date date,
    status text,
    budget numeric,
    metrics jsonb DEFAULT '{}'::jsonb,
    fetched_at timestamp without time zone DEFAULT now(),
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.ad_campaigns OWNER TO begin;

--
-- Name: ad_entities; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_entities (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    adset_external_id text NOT NULL,
    ad_external_id text NOT NULL,
    name text,
    status text,
    creative_id text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.ad_entities OWNER TO begin;

--
-- Name: ad_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_metrics (
    id bigint NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    entity_level text NOT NULL,
    entity_external_id text NOT NULL,
    date_start date NOT NULL,
    date_stop date NOT NULL,
    spend numeric,
    impressions bigint,
    clicks bigint,
    actions jsonb,
    conversions jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.ad_metrics OWNER TO begin;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.ad_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.ad_metrics_id_seq OWNER TO begin;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.ad_metrics_id_seq OWNED BY cmis.ad_metrics.id;


--
-- Name: ad_sets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_sets (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    campaign_external_id text NOT NULL,
    adset_external_id text NOT NULL,
    name text,
    status text,
    daily_budget numeric,
    start_date timestamp with time zone,
    end_date timestamp with time zone,
    billing_event text,
    optimization_goal text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.ad_sets OWNER TO begin;

--
-- Name: ai_actions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ai_actions (
    action_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    prompt_used text,
    sql_executed text,
    result_summary text,
    confidence_score numeric(5,2),
    created_at timestamp with time zone DEFAULT now(),
    audit_id uuid
);


ALTER TABLE cmis.ai_actions OWNER TO begin;

--
-- Name: ai_generated_campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ai_generated_campaigns (
    campaign_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    ai_summary text,
    ai_design_guideline text,
    created_at timestamp with time zone DEFAULT now(),
    engine text
);


ALTER TABLE cmis.ai_generated_campaigns OWNER TO begin;

--
-- Name: analytics_integrations; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.analytics_integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    platform text NOT NULL,
    source_endpoint text NOT NULL,
    mapping jsonb NOT NULL,
    refresh_frequency text DEFAULT 'weekly'::text,
    last_synced_at timestamp with time zone
);


ALTER TABLE cmis.analytics_integrations OWNER TO begin;

--
-- Name: anchors; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.anchors (
    anchor_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    code public.ltree NOT NULL,
    title text,
    file_ref text,
    section text
);


ALTER TABLE cmis.anchors OWNER TO begin;

--
-- Name: audio_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.audio_templates (
    atpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    voice_hints jsonb,
    sfx_pack jsonb,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.audio_templates OWNER TO begin;

--
-- Name: audit_log; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.audit_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    actor text,
    action text,
    target text,
    meta jsonb,
    ts timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.audit_log OWNER TO begin;

--
-- Name: awareness_stages; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.awareness_stages (
    stage text NOT NULL
);


ALTER TABLE public.awareness_stages OWNER TO gpts_data_user;

--
-- Name: awareness_stages; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.awareness_stages AS
 SELECT awareness_stages.stage
   FROM public.awareness_stages;


ALTER VIEW cmis.awareness_stages OWNER TO begin;

--
-- Name: bundle_offerings; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.bundle_offerings (
    bundle_id uuid NOT NULL,
    offering_id uuid NOT NULL
);


ALTER TABLE cmis.bundle_offerings OWNER TO begin;

--
-- Name: campaign_offerings; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaign_offerings (
    campaign_id uuid NOT NULL,
    offering_id uuid NOT NULL
);


ALTER TABLE cmis.campaign_offerings OWNER TO begin;

--
-- Name: campaign_performance_dashboard; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaign_performance_dashboard (
    dashboard_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    metric_name text NOT NULL,
    metric_value numeric(10,4),
    metric_target numeric(10,4),
    variance numeric(10,4),
    confidence_level numeric(4,2),
    collected_at timestamp with time zone DEFAULT now(),
    insights text
);


ALTER TABLE cmis.campaign_performance_dashboard OWNER TO begin;

--
-- Name: campaigns; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.campaigns (
    campaign_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    objective text,
    status text DEFAULT 'draft'::text,
    start_date date,
    end_date date,
    budget numeric(12,2),
    currency text DEFAULT 'USD'::text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_refactored.campaigns OWNER TO begin;

--
-- Name: TABLE campaigns; Type: COMMENT; Schema: cmis_refactored; Owner: begin
--

COMMENT ON TABLE cmis_refactored.campaigns IS 'المرجع الموحّد لجميع الحملات. استخدم هذا الجدول بدل أي نسخة قديمة.';


--
-- Name: campaigns; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.campaigns AS
 SELECT campaigns.campaign_id,
    campaigns.org_id,
    campaigns.name,
    campaigns.objective,
    campaigns.status,
    campaigns.start_date,
    campaigns.end_date,
    campaigns.budget,
    campaigns.currency,
    campaigns.created_at,
    campaigns.updated_at
   FROM cmis_refactored.campaigns;


ALTER VIEW cmis.campaigns OWNER TO begin;

--
-- Name: channel_formats; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.channel_formats (
    format_id integer NOT NULL,
    channel_id integer NOT NULL,
    code text NOT NULL,
    ratio text,
    length_hint text
);


ALTER TABLE public.channel_formats OWNER TO gpts_data_user;

--
-- Name: channel_formats; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.channel_formats AS
 SELECT channel_formats.format_id,
    channel_formats.channel_id,
    channel_formats.code,
    channel_formats.ratio,
    channel_formats.length_hint
   FROM public.channel_formats;


ALTER VIEW cmis.channel_formats OWNER TO begin;

--
-- Name: channels; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.channels (
    channel_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    constraints jsonb
);


ALTER TABLE public.channels OWNER TO gpts_data_user;

--
-- Name: channels; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.channels AS
 SELECT channels.channel_id,
    channels.code,
    channels.name,
    channels.constraints
   FROM public.channels;


ALTER VIEW cmis.channels OWNER TO begin;

--
-- Name: cognitive_tracker_template; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.cognitive_tracker_template (
    tracker_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    record_date date NOT NULL,
    platform text NOT NULL,
    content_type text NOT NULL,
    visual_factor text NOT NULL,
    ctr numeric(5,2),
    engagement_rate numeric(5,2),
    trust_index numeric(5,2),
    visual_insight text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.cognitive_tracker_template OWNER TO begin;

--
-- Name: cognitive_trends; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.cognitive_trends (
    trend_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    factor_name text NOT NULL,
    trend_direction text,
    growth_rate double precision,
    trend_strength double precision,
    summary_insight text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT cognitive_trends_trend_direction_check CHECK ((trend_direction = ANY (ARRAY['up'::text, 'down'::text, 'stable'::text])))
);


ALTER TABLE cmis.cognitive_trends OWNER TO begin;

--
-- Name: compliance_audits; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_audits (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid,
    rule_id uuid NOT NULL,
    status text NOT NULL,
    owner text,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT compliance_audits_status_check CHECK ((status = ANY (ARRAY['pass'::text, 'fail'::text, 'waived'::text])))
);


ALTER TABLE cmis.compliance_audits OWNER TO begin;

--
-- Name: compliance_rule_channels; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_rule_channels (
    rule_id uuid NOT NULL,
    channel_id integer NOT NULL
);


ALTER TABLE cmis.compliance_rule_channels OWNER TO begin;

--
-- Name: compliance_rules; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_rules (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    description text NOT NULL,
    severity text NOT NULL,
    params jsonb,
    CONSTRAINT compliance_rules_severity_check CHECK ((severity = ANY (ARRAY['warn'::text, 'block'::text])))
);


ALTER TABLE cmis.compliance_rules OWNER TO begin;

--
-- Name: component_types; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.component_types (
    type_code text NOT NULL
);


ALTER TABLE public.component_types OWNER TO gpts_data_user;

--
-- Name: component_types; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.component_types AS
 SELECT component_types.type_code
   FROM public.component_types;


ALTER VIEW cmis.component_types OWNER TO begin;

--
-- Name: content_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.content_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    plan_id uuid NOT NULL,
    channel_id integer,
    format_id integer,
    scheduled_at timestamp with time zone,
    title text,
    brief jsonb,
    asset_id uuid,
    status text DEFAULT 'draft'::text,
    context_id uuid,
    example_id uuid,
    creative_context_id uuid
);


ALTER TABLE cmis.content_items OWNER TO begin;

--
-- Name: content_plans; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.content_plans (
    plan_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    name text NOT NULL,
    timeframe_daterange daterange,
    strategy jsonb,
    created_at timestamp with time zone DEFAULT now(),
    brief_id uuid,
    creative_context_id uuid
);


ALTER TABLE cmis.content_plans OWNER TO begin;

--
-- Name: copy_components; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.copy_components (
    component_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    type_code text NOT NULL,
    content text NOT NULL,
    industry_id integer,
    market_id integer,
    awareness_stage text,
    channel_id integer,
    usage_notes text,
    quality_score smallint,
    created_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    example_id uuid,
    campaign_id uuid,
    plan_id uuid,
    visual_prompt jsonb,
    CONSTRAINT copy_components_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE cmis.copy_components OWNER TO begin;

--
-- Name: TABLE copy_components; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON TABLE cmis.copy_components IS 'مكونات المحتوى النصي المُولَّدة مثل hook و headline و CTA. كل مكون يمثل قطعة نصية قابلة لإعادة الاستخدام ضمن السياق الإبداعي.';


--
-- Name: COLUMN copy_components.type_code; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.type_code IS 'نوع المكون النصي: hook, headline, benefit, proof... تُستخدم لتحديد وظيفة النص داخل الرسالة الإعلانية.';


--
-- Name: COLUMN copy_components.context_id; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.context_id IS 'السياق المعرفي الذي يربط هذا المكون بحملة معينة، جمهور، نبرة، أو مرحلة وعي معينة.';


--
-- Name: COLUMN copy_components.example_id; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.example_id IS 'معرّف مجموعة الحقول (example_set) التي تم استخدامها لتوليد هذا النص. يساعد في تتبع وفهم المنطق التوليدي.';


--
-- Name: creative_assets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_assets (
    asset_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    strategy jsonb,
    channel_id integer NOT NULL,
    format_id integer,
    variation_tag text,
    copy_block text,
    art_direction jsonb,
    compliance_meta jsonb,
    final_copy jsonb,
    used_fields jsonb,
    compliance_report jsonb,
    status text,
    created_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    example_id uuid,
    brief_id uuid,
    creative_context_id uuid,
    CONSTRAINT creative_assets_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'pending_review'::text, 'approved'::text, 'rejected'::text, 'archived'::text])))
);


ALTER TABLE cmis.creative_assets OWNER TO begin;

--
-- Name: creative_briefs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_briefs (
    brief_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    brief_data jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.creative_briefs OWNER TO begin;

--
-- Name: creative_contexts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    creative_brief jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.creative_contexts OWNER TO begin;

--
-- Name: data_feeds; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.data_feeds (
    feed_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    kind text NOT NULL,
    source_meta jsonb,
    last_ingested timestamp with time zone,
    CONSTRAINT data_feeds_kind_check CHECK ((kind = ANY (ARRAY['price'::text, 'stock'::text, 'location'::text, 'catalog'::text])))
);


ALTER TABLE cmis.data_feeds OWNER TO begin;

--
-- Name: dataset_files; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.dataset_files (
    file_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    pkg_id uuid NOT NULL,
    filename text NOT NULL,
    checksum text,
    meta jsonb
);


ALTER TABLE cmis.dataset_files OWNER TO begin;

--
-- Name: dataset_packages; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.dataset_packages (
    pkg_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    version text NOT NULL,
    notes text
);


ALTER TABLE cmis.dataset_packages OWNER TO begin;

--
-- Name: experiment_variants; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.experiment_variants (
    exp_id uuid NOT NULL,
    asset_id uuid NOT NULL
);


ALTER TABLE cmis.experiment_variants OWNER TO begin;

--
-- Name: experiments; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.experiments (
    exp_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    channel_id integer,
    framework text,
    hypothesis text,
    status text DEFAULT 'draft'::text,
    created_at timestamp with time zone DEFAULT now(),
    campaign_id uuid
);


ALTER TABLE cmis.experiments OWNER TO begin;

--
-- Name: export_bundle_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.export_bundle_items (
    bundle_id uuid NOT NULL,
    asset_id uuid NOT NULL
);


ALTER TABLE cmis.export_bundle_items OWNER TO begin;

--
-- Name: export_bundles; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.export_bundles (
    bundle_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.export_bundles OWNER TO begin;

--
-- Name: feed_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.feed_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    feed_id uuid NOT NULL,
    sku text,
    payload jsonb NOT NULL,
    valid_from timestamp with time zone DEFAULT now(),
    valid_to timestamp with time zone
);


ALTER TABLE cmis.feed_items OWNER TO begin;

--
-- Name: field_aliases; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_aliases (
    alias_slug text NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE cmis.field_aliases OWNER TO begin;

--
-- Name: field_definitions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_definitions (
    field_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    slug text NOT NULL,
    data_type text NOT NULL,
    is_list boolean DEFAULT false,
    description text,
    enum_options text[],
    required_default boolean DEFAULT false,
    guidance_anchor uuid,
    validations jsonb,
    module_scope text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT field_definitions_data_type_check CHECK ((data_type = ANY (ARRAY['text'::text, 'markdown'::text, 'number'::text, 'bool'::text, 'json'::text, 'enum'::text, 'vector'::text]))),
    CONSTRAINT field_definitions_module_scope_check CHECK ((module_scope = ANY (ARRAY['market_intel'::text, 'persuasion'::text, 'frameworks'::text, 'adaptation'::text, 'testing'::text, 'compliance'::text, 'video'::text, 'content'::text])))
);


ALTER TABLE cmis.field_definitions OWNER TO begin;

--
-- Name: field_values; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_values (
    value_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    field_id uuid NOT NULL,
    context_id uuid NOT NULL,
    value jsonb NOT NULL,
    source text NOT NULL,
    provider_ref text,
    justification text,
    confidence numeric,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT field_values_confidence_check CHECK (((confidence >= (0)::numeric) AND (confidence <= (1)::numeric))),
    CONSTRAINT field_values_source_check CHECK ((source = ANY (ARRAY['manual'::text, 'assumption'::text, 'derived'::text, 'imported'::text, 'model'::text])))
);


ALTER TABLE cmis.field_values OWNER TO begin;

--
-- Name: flow_steps; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.flow_steps (
    step_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    flow_id uuid NOT NULL,
    ord smallint NOT NULL,
    type text NOT NULL,
    name text,
    input_map jsonb,
    config jsonb,
    output_map jsonb,
    condition jsonb,
    CONSTRAINT flow_steps_type_check CHECK ((type = ANY (ARRAY['llm'::text, 'sql'::text, 'tool'::text, 'branch'::text, 'transform'::text, 'evaluate'::text])))
);


ALTER TABLE cmis.flow_steps OWNER TO begin;

--
-- Name: flows; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.flows (
    flow_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    description text,
    version text DEFAULT '2025.10.0'::text,
    tags text[],
    enabled boolean DEFAULT true
);


ALTER TABLE cmis.flows OWNER TO begin;

--
-- Name: frameworks; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.frameworks (
    framework_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    framework_name text NOT NULL,
    framework_type text,
    description text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.frameworks OWNER TO begin;

--
-- Name: frameworks; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.frameworks AS
 SELECT frameworks.framework_id,
    frameworks.framework_name,
    frameworks.framework_type,
    frameworks.description,
    frameworks.created_at
   FROM public.frameworks;


ALTER VIEW cmis.frameworks OWNER TO begin;

--
-- Name: example_sets; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.example_sets (
    example_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    title text,
    kind text NOT NULL,
    channel_id integer,
    framework text,
    tone text,
    locale text DEFAULT 'ar-BH'::text,
    quality_score smallint,
    anchor uuid,
    tags text[],
    body jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    campaign_id uuid,
    CONSTRAINT example_sets_kind_check CHECK ((kind = ANY (ARRAY['example'::text, 'template'::text, 'set'::text, 'collection'::text, 'scenario'::text, 'template_set'::text]))),
    CONSTRAINT example_sets_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE lab.example_sets OWNER TO begin;

--
-- Name: full_campaign_snapshot; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.full_campaign_snapshot AS
 SELECT c.campaign_id,
    c.name AS campaign_name,
    c.objective,
    c.status AS campaign_status,
    cb.brief_data,
    cp.plan_id,
    cp.name AS plan_name,
    ci.item_id,
    ci.title AS content_title,
    ca.asset_id,
    ca.copy_block,
    ca.art_direction,
    cc.component_id,
    cc.type_code,
    cc.content AS component_content,
    cc.awareness_stage,
    cc.usage_notes,
    e.exp_id AS experiment_id,
    e.framework,
    e.hypothesis,
    es.example_id,
    es.title AS example_title,
    es.body AS example_body
   FROM (((((((cmis.campaigns c
     LEFT JOIN cmis.creative_briefs cb ON ((c.campaign_id = cb.brief_id)))
     LEFT JOIN cmis.content_plans cp ON ((cp.campaign_id = c.campaign_id)))
     LEFT JOIN cmis.content_items ci ON ((ci.plan_id = cp.plan_id)))
     LEFT JOIN cmis.creative_assets ca ON ((ca.campaign_id = c.campaign_id)))
     LEFT JOIN cmis.copy_components cc ON (((cc.campaign_id = c.campaign_id) OR (cc.plan_id = cp.plan_id))))
     LEFT JOIN cmis.experiments e ON ((e.campaign_id = c.campaign_id)))
     LEFT JOIN lab.example_sets es ON ((es.campaign_id = c.campaign_id)));


ALTER VIEW cmis.full_campaign_snapshot OWNER TO begin;

--
-- Name: funnel_stages; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.funnel_stages (
    stage text NOT NULL
);


ALTER TABLE public.funnel_stages OWNER TO gpts_data_user;

--
-- Name: funnel_stages; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.funnel_stages AS
 SELECT funnel_stages.stage
   FROM public.funnel_stages;


ALTER VIEW cmis.funnel_stages OWNER TO begin;

--
-- Name: industries; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.industries (
    industry_id integer NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.industries OWNER TO gpts_data_user;

--
-- Name: industries; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.industries AS
 SELECT industries.industry_id,
    industries.name
   FROM public.industries;


ALTER VIEW cmis.industries OWNER TO begin;

--
-- Name: integrations; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    platform text,
    account_id text,
    access_token text,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    business_id text
);


ALTER TABLE cmis_refactored.integrations OWNER TO begin;

--
-- Name: integrations; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.integrations AS
 SELECT integrations.integration_id,
    integrations.org_id,
    integrations.platform,
    integrations.account_id,
    integrations.access_token,
    integrations.is_active,
    integrations.created_at
   FROM cmis_refactored.integrations;


ALTER VIEW cmis.integrations OWNER TO begin;

--
-- Name: kpis; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.kpis (
    kpi text NOT NULL,
    description text
);


ALTER TABLE public.kpis OWNER TO gpts_data_user;

--
-- Name: kpis; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.kpis AS
 SELECT kpis.kpi,
    kpis.description
   FROM public.kpis;


ALTER VIEW cmis.kpis OWNER TO begin;

--
-- Name: logs_migration; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.logs_migration (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    phase text NOT NULL,
    status text NOT NULL,
    executed_at timestamp without time zone DEFAULT now(),
    details jsonb DEFAULT '{}'::jsonb
);


ALTER TABLE cmis.logs_migration OWNER TO begin;

--
-- Name: marketing_objectives; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.marketing_objectives (
    objective text NOT NULL,
    display_name text,
    category text,
    description text,
    CONSTRAINT marketing_objectives_category_check CHECK ((category = ANY (ARRAY['awareness'::text, 'understanding'::text, 'emotion'::text, 'trust'::text, 'conversion'::text]))),
    CONSTRAINT marketing_objectives_objective_check CHECK ((objective ~ '^[a-zA-Z0-9_]+$'::text))
);


ALTER TABLE public.marketing_objectives OWNER TO gpts_data_user;

--
-- Name: marketing_objectives; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.marketing_objectives AS
 SELECT marketing_objectives.objective,
    marketing_objectives.display_name,
    marketing_objectives.category,
    marketing_objectives.description
   FROM public.marketing_objectives;


ALTER VIEW cmis.marketing_objectives OWNER TO begin;

--
-- Name: markets; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.markets (
    market_id integer NOT NULL,
    market_name text NOT NULL,
    language_code text NOT NULL,
    currency_code text NOT NULL,
    text_direction text DEFAULT 'RTL'::text
);


ALTER TABLE public.markets OWNER TO gpts_data_user;

--
-- Name: markets; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.markets AS
 SELECT markets.market_id,
    markets.market_name,
    markets.language_code,
    markets.currency_code,
    markets.text_direction
   FROM public.markets;


ALTER VIEW cmis.markets OWNER TO begin;

--
-- Name: meta_documentation; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_documentation (
    doc_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    meta_key text NOT NULL,
    meta_value text NOT NULL,
    updated_by text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.meta_documentation OWNER TO begin;

--
-- Name: meta_field_dictionary; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_field_dictionary (
    id integer NOT NULL,
    field_name text NOT NULL,
    semantic_meaning text,
    usage_context text,
    unified_alias text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.meta_field_dictionary OWNER TO begin;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.meta_field_dictionary_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNER TO begin;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNED BY cmis.meta_field_dictionary.id;


--
-- Name: meta_function_descriptions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_function_descriptions (
    id integer NOT NULL,
    routine_schema text NOT NULL,
    routine_name text NOT NULL,
    description text,
    cognitive_category text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.meta_function_descriptions OWNER TO begin;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.meta_function_descriptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNER TO begin;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNED BY cmis.meta_function_descriptions.id;


--
-- Name: modules; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.modules (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.modules OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.modules_module_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.modules_module_id_seq OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.modules_module_id_seq OWNED BY cmis.modules.module_id;


--
-- Name: naming_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.naming_templates (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);


ALTER TABLE cmis.naming_templates OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.naming_templates_naming_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNED BY cmis.naming_templates.naming_id;


--
-- Name: offerings; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.offerings (
    offering_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    kind text NOT NULL,
    name text NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT offerings_kind_check CHECK ((kind = ANY (ARRAY['product'::text, 'service'::text, 'bundle'::text])))
);


ALTER TABLE cmis.offerings OWNER TO begin;

--
-- Name: offerings_full_details; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.offerings_full_details (
    detail_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    offering_id uuid,
    full_description text NOT NULL,
    pricing_notes text,
    target_segment text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.offerings_full_details OWNER TO begin;

--
-- Name: ops_audit; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ops_audit (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    operation_name text NOT NULL,
    status text NOT NULL,
    executed_at timestamp with time zone DEFAULT now(),
    details jsonb
);


ALTER TABLE cmis.ops_audit OWNER TO begin;

--
-- Name: ops_etl_log; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ops_etl_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    integration_id uuid,
    status text,
    started_at timestamp with time zone DEFAULT now(),
    ended_at timestamp with time zone,
    rows_processed integer,
    notes text
);


ALTER TABLE cmis.ops_etl_log OWNER TO begin;

--
-- Name: org_datasets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.org_datasets (
    org_id uuid NOT NULL,
    pkg_id uuid NOT NULL,
    enabled boolean DEFAULT true
);


ALTER TABLE cmis.org_datasets OWNER TO begin;

--
-- Name: org_markets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.org_markets (
    org_id uuid NOT NULL,
    market_id integer NOT NULL,
    is_default boolean DEFAULT false
);


ALTER TABLE cmis.org_markets OWNER TO begin;

--
-- Name: orgs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.orgs (
    org_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name public.citext NOT NULL,
    default_locale text DEFAULT 'ar-BH'::text,
    currency text DEFAULT 'BHD'::text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.orgs OWNER TO begin;

--
-- Name: output_contracts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.output_contracts (
    contract_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    json_schema jsonb NOT NULL,
    notes text
);


ALTER TABLE cmis.output_contracts OWNER TO begin;

--
-- Name: performance_metrics; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.performance_metrics (
    metric_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    output_id uuid,
    kpi text NOT NULL,
    observed numeric,
    target numeric,
    baseline numeric,
    observed_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_refactored.performance_metrics OWNER TO begin;

--
-- Name: COLUMN performance_metrics.observed_at; Type: COMMENT; Schema: cmis_refactored; Owner: begin
--

COMMENT ON COLUMN cmis_refactored.performance_metrics.observed_at IS 'زمن الرصد (كان ts في النسخة القديمة).';


--
-- Name: performance_metrics; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.performance_metrics AS
 SELECT performance_metrics.metric_id,
    performance_metrics.org_id,
    performance_metrics.campaign_id,
    performance_metrics.output_id,
    performance_metrics.kpi,
    performance_metrics.observed,
    performance_metrics.target,
    performance_metrics.baseline,
    performance_metrics.observed_at
   FROM cmis_refactored.performance_metrics;


ALTER VIEW cmis.performance_metrics OWNER TO begin;

--
-- Name: playbook_steps; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.playbook_steps AS
 SELECT s.step_id,
    s.flow_id AS playbook_id,
    s.ord AS step_order,
    COALESCE(s.name, s.type) AS step_name,
    NULL::text AS step_instructions,
    NULL::text AS module_reference
   FROM cmis.flow_steps s;


ALTER VIEW cmis.playbook_steps OWNER TO begin;

--
-- Name: playbooks; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.playbooks AS
 SELECT f.flow_id AS playbook_id,
    f.name AS playbook_name,
    f.description
   FROM cmis.flows f;


ALTER VIEW cmis.playbooks OWNER TO begin;

--
-- Name: predictive_visual_engine; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.predictive_visual_engine (
    prediction_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    predicted_ctr double precision,
    predicted_engagement double precision,
    predicted_trust_index double precision,
    confidence_level double precision,
    visual_factor_weight jsonb,
    prediction_summary text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.predictive_visual_engine OWNER TO begin;

--
-- Name: prompt_template_contracts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_contracts (
    prompt_id uuid NOT NULL,
    contract_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_contracts OWNER TO begin;

--
-- Name: prompt_template_presql; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_presql (
    prompt_id uuid NOT NULL,
    snippet_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_presql OWNER TO begin;

--
-- Name: prompt_template_required_fields; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_required_fields (
    prompt_id uuid NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_required_fields OWNER TO begin;

--
-- Name: prompt_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_templates (
    prompt_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    task text NOT NULL,
    instructions text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.prompt_templates OWNER TO begin;

--
-- Name: proof_layers; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.proof_layers (
    level text NOT NULL
);


ALTER TABLE public.proof_layers OWNER TO gpts_data_user;

--
-- Name: proof_layers; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.proof_layers AS
 SELECT proof_layers.level
   FROM public.proof_layers;


ALTER VIEW cmis.proof_layers OWNER TO begin;

--
-- Name: scene_library; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.scene_library (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    goal text,
    duration_sec integer,
    visual_spec jsonb,
    audio_spec jsonb,
    overlay_rules jsonb,
    anchor uuid,
    quality_score smallint,
    tags text[],
    CONSTRAINT scene_library_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE cmis.scene_library OWNER TO begin;

--
-- Name: segments; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.segments (
    segment_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    persona jsonb,
    notes text
);


ALTER TABLE cmis.segments OWNER TO begin;

--
-- Name: social_account_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_account_metrics (
    integration_id uuid NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    followers bigint,
    reach bigint,
    impressions bigint,
    profile_views bigint
);


ALTER TABLE cmis.social_account_metrics OWNER TO begin;

--
-- Name: social_accounts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_accounts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    account_external_id text NOT NULL,
    username text,
    display_name text,
    profile_picture_url text,
    biography text,
    followers_count bigint,
    follows_count bigint,
    media_count bigint,
    website text,
    category text,
    is_verified boolean DEFAULT false,
    fetched_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.social_accounts OWNER TO begin;

--
-- Name: social_post_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_post_metrics (
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    metric_date date NOT NULL,
    social_post_id uuid NOT NULL,
    impressions bigint,
    reach bigint,
    likes bigint,
    comments bigint,
    saves bigint,
    shares bigint
);


ALTER TABLE cmis.social_post_metrics OWNER TO begin;

--
-- Name: social_posts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_posts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    caption text,
    media_url text,
    permalink text,
    media_type text,
    posted_at timestamp without time zone,
    metrics jsonb DEFAULT '{}'::jsonb,
    fetched_at timestamp without time zone DEFAULT now(),
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.social_posts OWNER TO begin;

--
-- Name: sql_snippets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.sql_snippets (
    snippet_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    sql text NOT NULL,
    description text
);


ALTER TABLE cmis.sql_snippets OWNER TO begin;

--
-- Name: strategies; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.strategies (
    strategy text NOT NULL
);


ALTER TABLE public.strategies OWNER TO gpts_data_user;

--
-- Name: strategies; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.strategies AS
 SELECT strategies.strategy
   FROM public.strategies;


ALTER VIEW cmis.strategies OWNER TO begin;

--
-- Name: sync_logs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.sync_logs (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid,
    platform text,
    synced_at timestamp without time zone DEFAULT now(),
    status text,
    items integer DEFAULT 0,
    level_counts jsonb DEFAULT '{}'::jsonb
);


ALTER TABLE cmis.sync_logs OWNER TO begin;

--
-- Name: tones; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.tones (
    tone text NOT NULL
);


ALTER TABLE public.tones OWNER TO gpts_data_user;

--
-- Name: tones; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.tones AS
 SELECT tones.tone
   FROM public.tones;


ALTER VIEW cmis.tones OWNER TO begin;

--
-- Name: users; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.users (
    user_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    email public.citext NOT NULL,
    display_name text,
    role text DEFAULT 'editor'::text,
    CONSTRAINT users_role_check CHECK ((role = ANY (ARRAY['viewer'::text, 'editor'::text, 'admin'::text])))
);


ALTER TABLE cmis.users OWNER TO begin;

--
-- Name: v_ai_insights; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_ai_insights AS
 SELECT a.org_id,
    a.campaign_id,
    a.ai_summary AS campaign_summary,
    p.prediction_summary AS visual_prediction,
    t.factor_name AS cognitive_trend,
    t.trend_strength,
    t.trend_direction,
    t.summary_insight,
    a.engine AS ai_model,
    a.created_at,
    COALESCE((p.visual_factor_weight ->> 'dominant'::text), t.factor_name) AS dominant_theme
   FROM ((cmis.ai_generated_campaigns a
     LEFT JOIN cmis.predictive_visual_engine p ON (((a.org_id = p.org_id) AND (a.campaign_id = p.campaign_id))))
     LEFT JOIN cmis.cognitive_trends t ON ((a.org_id = t.org_id)));


ALTER VIEW cmis.v_ai_insights OWNER TO begin;

--
-- Name: v_campaigns_performance_summary; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_campaigns_performance_summary AS
 SELECT c.campaign_id,
    c.name AS campaign_name,
    c.status,
    c.start_date,
    c.end_date,
    c.budget,
    c.currency,
    d.metric_name,
    d.metric_value,
    d.metric_target,
    d.variance,
    d.confidence_level,
    d.insights,
    d.collected_at
   FROM (cmis.campaigns c
     JOIN cmis.campaign_performance_dashboard d ON ((c.campaign_id = d.campaign_id)));


ALTER VIEW cmis.v_campaigns_performance_summary OWNER TO begin;

--
-- Name: v_marketing_reference; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_marketing_reference AS
 SELECT f.framework_id,
    f.framework_name,
    f.description AS framework_description,
    s.strategy AS strategy_name,
    st.stage AS stage_name,
    concat(f.framework_name, ' → ', s.strategy, ' → ', st.stage) AS reference_path
   FROM ((cmis.frameworks f
     CROSS JOIN cmis.strategies s)
     CROSS JOIN cmis.funnel_stages st);


ALTER VIEW cmis.v_marketing_reference OWNER TO begin;

--
-- Name: v_unified_ad_targeting; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_unified_ad_targeting AS
 SELECT a.org_id,
    i.platform,
    a.entity_level,
    a.entity_external_id,
    COALESCE(a.demographics, '{}'::jsonb) AS demographics,
    COALESCE(a.interests, '{}'::jsonb) AS interests,
    COALESCE(a.behaviors, '{}'::jsonb) AS behaviors,
    COALESCE(a.location, '{}'::jsonb) AS location,
    COALESCE(a.keywords, '{}'::jsonb) AS keywords,
    COALESCE(a.custom_audience, '{}'::jsonb) AS custom_audience,
    COALESCE(a.lookalike_audience, '{}'::jsonb) AS lookalike_audience,
    COALESCE(a.advantage_plus_settings, '{}'::jsonb) AS advantage_plus
   FROM (cmis.ad_audiences a
     JOIN cmis_refactored.integrations i ON ((a.integration_id = i.integration_id)));


ALTER VIEW cmis.v_unified_ad_targeting OWNER TO begin;

--
-- Name: value_contexts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.value_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    offering_id uuid,
    segment_id uuid,
    campaign_id uuid,
    channel_id integer,
    format_id integer,
    locale text DEFAULT 'ar-BH'::text,
    awareness_stage text,
    funnel_stage text,
    framework text,
    tone text,
    dataset_ref text,
    variant_tag text,
    tags text[],
    market_id integer,
    industry_id integer,
    created_at timestamp with time zone DEFAULT now(),
    context_fingerprint text
);


ALTER TABLE cmis.value_contexts OWNER TO begin;

--
-- Name: variation_policies; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.variation_policies (
    policy_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    max_variations smallint DEFAULT 3,
    dco_enabled boolean DEFAULT true,
    naming_ref integer
);


ALTER TABLE cmis.variation_policies OWNER TO begin;

--
-- Name: video_scenes; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.video_scenes (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid NOT NULL,
    scene_number integer NOT NULL,
    duration_seconds integer,
    visual_prompt_en text,
    overlay_text_ar text,
    audio_instructions text,
    technical_specs jsonb
);


ALTER TABLE cmis.video_scenes OWNER TO begin;

--
-- Name: video_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.video_templates (
    vtpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    channel_id integer,
    format_id integer,
    name text NOT NULL,
    steps jsonb NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.video_templates OWNER TO begin;

--
-- Name: contexts; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    type text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT contexts_type_check CHECK ((type = ANY (ARRAY['value'::text, 'creative'::text, 'experiment'::text])))
);


ALTER TABLE cmis_refactored.contexts OWNER TO begin;

--
-- Name: creative_outputs; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.creative_outputs (
    output_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    context_id uuid,
    type text NOT NULL,
    status text DEFAULT 'draft'::text,
    data jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT creative_outputs_type_check CHECK ((type = ANY (ARRAY['asset'::text, 'copy'::text, 'content'::text])))
);


ALTER TABLE cmis_refactored.creative_outputs OWNER TO begin;

--
-- Name: v_context_impact; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_context_impact AS
 SELECT ctx.type AS context_type,
    count(DISTINCT co.output_id) AS total_outputs,
    avg(pm.observed) AS avg_observed,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS impact_score
   FROM ((cmis_refactored.contexts ctx
     LEFT JOIN cmis_refactored.creative_outputs co ON ((co.context_id = ctx.context_id)))
     LEFT JOIN cmis_refactored.performance_metrics pm ON ((pm.output_id = co.output_id)))
  GROUP BY ctx.type;


ALTER VIEW cmis_ai_analytics.v_context_impact OWNER TO begin;

--
-- Name: v_creative_efficiency; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_creative_efficiency AS
 SELECT co.type AS output_type,
    count(co.output_id) AS total_outputs,
    avg(pm.observed) AS avg_performance,
    avg(pm.target) AS avg_target,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS efficiency_score
   FROM (cmis_refactored.creative_outputs co
     LEFT JOIN cmis_refactored.performance_metrics pm ON ((pm.output_id = co.output_id)))
  GROUP BY co.type;


ALTER VIEW cmis_ai_analytics.v_creative_efficiency OWNER TO begin;

--
-- Name: v_kpi_summary; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_kpi_summary AS
 SELECT c.campaign_id,
    c.name AS campaign_name,
    date_trunc('day'::text, pm.observed_at) AS day,
    avg(pm.observed) AS avg_observed,
    avg(pm.target) AS avg_target,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS performance_rate
   FROM (cmis_refactored.campaigns c
     LEFT JOIN cmis_refactored.performance_metrics pm ON ((pm.campaign_id = c.campaign_id)))
  GROUP BY c.campaign_id, c.name, (date_trunc('day'::text, pm.observed_at))
  ORDER BY (date_trunc('day'::text, pm.observed_at)) DESC;


ALTER VIEW cmis_ai_analytics.v_kpi_summary OWNER TO begin;

--
-- Name: ai_queries; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.ai_queries (
    query_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    user_prompt text NOT NULL,
    generated_sql text,
    result_summary text,
    confidence_score numeric(5,2),
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.ai_queries OWNER TO begin;

--
-- Name: migration_log; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.migration_log (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    executed_at timestamp with time zone DEFAULT now() NOT NULL,
    action text NOT NULL,
    sql_code text NOT NULL
);


ALTER TABLE cmis_analytics.migration_log OWNER TO begin;

--
-- Name: performance_snapshot; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.performance_snapshot (
    snapshot_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    kpi text,
    value numeric,
    observed_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.performance_snapshot OWNER TO begin;

--
-- Name: prompt_templates; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.prompt_templates (
    template_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    prompt_text text NOT NULL,
    sql_snippet text NOT NULL,
    context_tags text[],
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.prompt_templates OWNER TO begin;

--
-- Name: scheduled_jobs; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.scheduled_jobs (
    job_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    job_name text NOT NULL,
    schedule text NOT NULL,
    function_name text NOT NULL,
    last_run timestamp with time zone,
    next_run timestamp with time zone
);


ALTER TABLE cmis_analytics.scheduled_jobs OWNER TO begin;

--
-- Name: ai_models; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.ai_models (
    model_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    engine text,
    version text,
    description text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_refactored.ai_models OWNER TO begin;

--
-- Name: organizations; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.organizations (
    org_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    default_locale text DEFAULT 'ar-BH'::text,
    currency text DEFAULT 'BHD'::text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_refactored.organizations OWNER TO begin;

--
-- Name: reference_entities; Type: TABLE; Schema: cmis_refactored; Owner: begin
--

CREATE TABLE cmis_refactored.reference_entities (
    ref_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    category text NOT NULL,
    code text NOT NULL,
    label text,
    description text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_refactored.reference_entities OWNER TO begin;

--
-- Name: v_campaign_snapshot_refactored; Type: VIEW; Schema: cmis_refactored; Owner: begin
--

CREATE VIEW cmis_refactored.v_campaign_snapshot_refactored AS
 SELECT c.campaign_id,
    c.name AS campaign_name,
    c.status,
    c.start_date,
    c.end_date,
    c.budget,
    c.currency,
    ctx.type AS context_type,
    co.type AS output_type,
    co.status AS output_status,
    pm.kpi,
    pm.observed,
    pm.target,
    pm.baseline,
    pm.observed_at
   FROM (((cmis_refactored.campaigns c
     LEFT JOIN cmis_refactored.contexts ctx ON ((ctx.campaign_id = c.campaign_id)))
     LEFT JOIN cmis_refactored.creative_outputs co ON ((co.campaign_id = c.campaign_id)))
     LEFT JOIN cmis_refactored.performance_metrics pm ON ((pm.campaign_id = c.campaign_id)));


ALTER VIEW cmis_refactored.v_campaign_snapshot_refactored OWNER TO begin;

--
-- Name: v_schema_map; Type: VIEW; Schema: cmis_refactored; Owner: begin
--

CREATE VIEW cmis_refactored.v_schema_map AS
 SELECT columns.table_schema,
    columns.table_name,
    columns.column_name,
    columns.data_type,
    columns.is_nullable,
    columns.column_default
   FROM information_schema.columns
  WHERE ((columns.table_schema)::name = 'cmis_refactored'::name)
  ORDER BY columns.table_schema, columns.table_name, columns.ordinal_position;


ALTER VIEW cmis_refactored.v_schema_map OWNER TO begin;

--
-- Name: raw_channel_data; Type: TABLE; Schema: cmis_staging; Owner: begin
--

CREATE TABLE cmis_staging.raw_channel_data (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    integration_id uuid,
    platform text NOT NULL,
    payload jsonb NOT NULL,
    fetched_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_staging.raw_channel_data OWNER TO begin;

--
-- Name: example_used_fields; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.example_used_fields (
    example_id uuid NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE lab.example_used_fields OWNER TO begin;

--
-- Name: test_matrix; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.test_matrix (
    test_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    design jsonb NOT NULL,
    notes text
);


ALTER TABLE lab.test_matrix OWNER TO begin;

--
-- Name: channel_formats_format_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.channel_formats_format_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.channel_formats_format_id_seq OWNER TO gpts_data_user;

--
-- Name: channel_formats_format_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.channel_formats_format_id_seq OWNED BY public.channel_formats.format_id;


--
-- Name: channels_channel_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.channels_channel_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.channels_channel_id_seq OWNER TO gpts_data_user;

--
-- Name: channels_channel_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.channels_channel_id_seq OWNED BY public.channels.channel_id;


--
-- Name: industries_industry_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.industries_industry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.industries_industry_id_seq OWNER TO gpts_data_user;

--
-- Name: industries_industry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.industries_industry_id_seq OWNED BY public.industries.industry_id;


--
-- Name: markets_market_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.markets_market_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.markets_market_id_seq OWNER TO gpts_data_user;

--
-- Name: markets_market_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.markets_market_id_seq OWNED BY public.markets.market_id;


--
-- Name: modules; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.modules AS
 SELECT modules.module_id,
    modules.code,
    modules.name,
    modules.version
   FROM cmis.modules;


ALTER VIEW public.modules OWNER TO begin;

--
-- Name: modules_old; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.modules_old (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE public.modules_old OWNER TO gpts_data_user;

--
-- Name: modules_module_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.modules_module_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.modules_module_id_seq OWNER TO gpts_data_user;

--
-- Name: modules_module_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.modules_module_id_seq OWNED BY public.modules_old.module_id;


--
-- Name: naming_templates; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.naming_templates AS
 SELECT naming_templates.naming_id,
    naming_templates.scope,
    naming_templates.template
   FROM cmis.naming_templates;


ALTER VIEW public.naming_templates OWNER TO begin;

--
-- Name: naming_templates_old; Type: TABLE; Schema: public; Owner: gpts_data_user
--

CREATE TABLE public.naming_templates_old (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);


ALTER TABLE public.naming_templates_old OWNER TO gpts_data_user;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE; Schema: public; Owner: gpts_data_user
--

CREATE SEQUENCE public.naming_templates_naming_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.naming_templates_naming_id_seq OWNER TO gpts_data_user;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: gpts_data_user
--

ALTER SEQUENCE public.naming_templates_naming_id_seq OWNED BY public.naming_templates_old.naming_id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO begin;

--
-- Name: visual_kpis; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_kpis (
    kpi_id integer NOT NULL,
    name text NOT NULL,
    metric_type text,
    unit text,
    ideal_value text,
    description text,
    CONSTRAINT visual_kpis_metric_type_check CHECK ((metric_type = ANY (ARRAY['attention'::text, 'comprehension'::text, 'emotion'::text, 'trust'::text])))
);


ALTER TABLE public.visual_kpis OWNER TO begin;

--
-- Name: visual_principles; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_principles (
    principle_id integer NOT NULL,
    name text NOT NULL,
    category text,
    description text,
    recommended_use text,
    CONSTRAINT visual_principles_category_check CHECK ((category = ANY (ARRAY['composition'::text, 'symbolism'::text, 'typography'::text, 'emotion'::text, 'speed'::text, 'clarity'::text])))
);


ALTER TABLE public.visual_principles OWNER TO begin;

--
-- Name: visual_dashboard_view; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.visual_dashboard_view AS
 SELECT mo.objective AS marketing_goal,
    mo.category AS goal_category,
    vp.name AS design_principle,
    vp.category AS principle_type,
    vk.name AS kpi_name,
    vk.metric_type AS metric_focus,
    vk.ideal_value AS benchmark,
    vk.description AS kpi_description
   FROM ((public.marketing_objectives mo
     CROSS JOIN public.visual_principles vp)
     JOIN public.visual_kpis vk ON (((vp.category = vk.metric_type) OR ((vk.metric_type = 'comprehension'::text) AND (vp.category = ANY (ARRAY['clarity'::text, 'composition'::text]))) OR ((vk.metric_type = 'emotion'::text) AND (vp.category = 'emotion'::text)))));


ALTER VIEW public.visual_dashboard_view OWNER TO begin;

--
-- Name: visual_kpis_kpi_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_kpis_kpi_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_kpis_kpi_id_seq OWNER TO begin;

--
-- Name: visual_kpis_kpi_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_kpis_kpi_id_seq OWNED BY public.visual_kpis.kpi_id;


--
-- Name: visual_principles_principle_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_principles_principle_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_principles_principle_id_seq OWNER TO begin;

--
-- Name: visual_principles_principle_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_principles_principle_id_seq OWNED BY public.visual_principles.principle_id;


--
-- Name: visual_recommendations; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_recommendations (
    recommendation_id integer NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    rationale text,
    suggested_action text
);


ALTER TABLE public.visual_recommendations OWNER TO begin;

--
-- Name: visual_recommendations_recommendation_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_recommendations_recommendation_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_recommendations_recommendation_id_seq OWNER TO begin;

--
-- Name: visual_recommendations_recommendation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_recommendations_recommendation_id_seq OWNED BY public.visual_recommendations.recommendation_id;


--
-- Name: ad_metrics id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics ALTER COLUMN id SET DEFAULT nextval('cmis.ad_metrics_id_seq'::regclass);


--
-- Name: meta_field_dictionary id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_field_dictionary ALTER COLUMN id SET DEFAULT nextval('cmis.meta_field_dictionary_id_seq'::regclass);


--
-- Name: meta_function_descriptions id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_function_descriptions ALTER COLUMN id SET DEFAULT nextval('cmis.meta_function_descriptions_id_seq'::regclass);


--
-- Name: modules module_id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules ALTER COLUMN module_id SET DEFAULT nextval('cmis.modules_module_id_seq'::regclass);


--
-- Name: naming_templates naming_id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates ALTER COLUMN naming_id SET DEFAULT nextval('cmis.naming_templates_naming_id_seq'::regclass);


--
-- Name: channel_formats format_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channel_formats ALTER COLUMN format_id SET DEFAULT nextval('public.channel_formats_format_id_seq'::regclass);


--
-- Name: channels channel_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channels ALTER COLUMN channel_id SET DEFAULT nextval('public.channels_channel_id_seq'::regclass);


--
-- Name: industries industry_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.industries ALTER COLUMN industry_id SET DEFAULT nextval('public.industries_industry_id_seq'::regclass);


--
-- Name: markets market_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.markets ALTER COLUMN market_id SET DEFAULT nextval('public.markets_market_id_seq'::regclass);


--
-- Name: modules_old module_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.modules_old ALTER COLUMN module_id SET DEFAULT nextval('public.modules_module_id_seq'::regclass);


--
-- Name: naming_templates_old naming_id; Type: DEFAULT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.naming_templates_old ALTER COLUMN naming_id SET DEFAULT nextval('public.naming_templates_naming_id_seq'::regclass);


--
-- Name: visual_kpis kpi_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis ALTER COLUMN kpi_id SET DEFAULT nextval('public.visual_kpis_kpi_id_seq'::regclass);


--
-- Name: visual_principles principle_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles ALTER COLUMN principle_id SET DEFAULT nextval('public.visual_principles_principle_id_seq'::regclass);


--
-- Name: visual_recommendations recommendation_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations ALTER COLUMN recommendation_id SET DEFAULT nextval('public.visual_recommendations_recommendation_id_seq'::regclass);


--
-- Name: ad_accounts ad_accounts_integration_id_account_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_integration_id_account_external_id_key UNIQUE (integration_id, account_external_id);


--
-- Name: ad_accounts ad_accounts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_pkey PRIMARY KEY (id);


--
-- Name: ad_audiences ad_audiences_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_pkey PRIMARY KEY (id);


--
-- Name: ad_campaigns ad_campaigns_integration_id_campaign_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_integration_id_campaign_external_id_key UNIQUE (integration_id, campaign_external_id);


--
-- Name: ad_campaigns ad_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_pkey PRIMARY KEY (id);


--
-- Name: ad_entities ad_entities_integration_id_ad_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_integration_id_ad_external_id_key UNIQUE (integration_id, ad_external_id);


--
-- Name: ad_entities ad_entities_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_pkey PRIMARY KEY (id);


--
-- Name: ad_metrics ad_metrics_integration_id_entity_level_entity_external_id_d_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_integration_id_entity_level_entity_external_id_d_key UNIQUE (integration_id, entity_level, entity_external_id, date_start, date_stop);


--
-- Name: ad_metrics ad_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_pkey PRIMARY KEY (id);


--
-- Name: ad_sets ad_sets_integration_id_adset_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_integration_id_adset_external_id_key UNIQUE (integration_id, adset_external_id);


--
-- Name: ad_sets ad_sets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_pkey PRIMARY KEY (id);


--
-- Name: ai_actions ai_actions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_pkey PRIMARY KEY (action_id);


--
-- Name: ai_generated_campaigns ai_generated_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: analytics_integrations analytics_integrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.analytics_integrations
    ADD CONSTRAINT analytics_integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: anchors anchors_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_code_key UNIQUE (code);


--
-- Name: anchors anchors_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_pkey PRIMARY KEY (anchor_id);


--
-- Name: audio_templates audio_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_pkey PRIMARY KEY (atpl_id);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (log_id);


--
-- Name: bundle_offerings bundle_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_pkey PRIMARY KEY (bundle_id, offering_id);


--
-- Name: campaign_offerings campaign_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_pkey PRIMARY KEY (campaign_id, offering_id);


--
-- Name: campaign_performance_dashboard campaign_performance_dashboard_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_performance_dashboard
    ADD CONSTRAINT campaign_performance_dashboard_pkey PRIMARY KEY (dashboard_id);


--
-- Name: cognitive_tracker_template cognitive_tracker_template_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_pkey PRIMARY KEY (tracker_id);


--
-- Name: cognitive_trends cognitive_trends_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_pkey PRIMARY KEY (trend_id);


--
-- Name: compliance_audits compliance_audits_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_pkey PRIMARY KEY (audit_id);


--
-- Name: compliance_rule_channels compliance_rule_channels_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_pkey PRIMARY KEY (rule_id, channel_id);


--
-- Name: compliance_rules compliance_rules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_code_key UNIQUE (code);


--
-- Name: compliance_rules compliance_rules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_pkey PRIMARY KEY (rule_id);


--
-- Name: content_items content_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_pkey PRIMARY KEY (item_id);


--
-- Name: content_plans content_plans_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_pkey PRIMARY KEY (plan_id);


--
-- Name: copy_components copy_components_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_pkey PRIMARY KEY (component_id);


--
-- Name: creative_assets creative_assets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_pkey PRIMARY KEY (asset_id);


--
-- Name: creative_briefs creative_briefs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_pkey PRIMARY KEY (brief_id);


--
-- Name: creative_contexts creative_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: data_feeds data_feeds_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_pkey PRIMARY KEY (feed_id);


--
-- Name: dataset_files dataset_files_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkey PRIMARY KEY (file_id);


--
-- Name: dataset_packages dataset_packages_code_version_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_code_version_key UNIQUE (code, version);


--
-- Name: dataset_packages dataset_packages_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_pkey PRIMARY KEY (pkg_id);


--
-- Name: experiment_variants experiment_variants_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_pkey PRIMARY KEY (exp_id, asset_id);


--
-- Name: experiments experiments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_pkey PRIMARY KEY (exp_id);


--
-- Name: export_bundle_items export_bundle_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_pkey PRIMARY KEY (bundle_id, asset_id);


--
-- Name: export_bundles export_bundles_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_pkey PRIMARY KEY (bundle_id);


--
-- Name: feed_items feed_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_pkey PRIMARY KEY (item_id);


--
-- Name: field_aliases field_aliases_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_pkey PRIMARY KEY (alias_slug);


--
-- Name: field_definitions field_definitions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_pkey PRIMARY KEY (field_id);


--
-- Name: field_definitions field_definitions_slug_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_slug_key UNIQUE (slug);


--
-- Name: field_values field_values_field_id_context_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_context_id_key UNIQUE (field_id, context_id);


--
-- Name: field_values field_values_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_pkey PRIMARY KEY (value_id);


--
-- Name: flow_steps flow_steps_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_pkey PRIMARY KEY (step_id);


--
-- Name: flows flows_name_version_org_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_name_version_org_id_key UNIQUE (name, version, org_id);


--
-- Name: flows flows_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_pkey PRIMARY KEY (flow_id);


--
-- Name: logs_migration logs_migration_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.logs_migration
    ADD CONSTRAINT logs_migration_pkey PRIMARY KEY (log_id);


--
-- Name: meta_documentation meta_documentation_meta_key_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_meta_key_key UNIQUE (meta_key);


--
-- Name: meta_documentation meta_documentation_pkey1; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_pkey1 PRIMARY KEY (doc_id);


--
-- Name: meta_field_dictionary meta_field_dictionary_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_field_dictionary
    ADD CONSTRAINT meta_field_dictionary_pkey PRIMARY KEY (id);


--
-- Name: meta_function_descriptions meta_function_descriptions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_function_descriptions
    ADD CONSTRAINT meta_function_descriptions_pkey PRIMARY KEY (id);


--
-- Name: modules modules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_code_key UNIQUE (code);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: naming_templates naming_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_pkey PRIMARY KEY (naming_id);


--
-- Name: naming_templates naming_templates_scope_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_scope_key UNIQUE (scope);


--
-- Name: offerings_full_details offerings_full_details_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_pkey PRIMARY KEY (detail_id);


--
-- Name: offerings offerings_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_org_id_name_key UNIQUE (org_id, name);


--
-- Name: offerings offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_pkey PRIMARY KEY (offering_id);


--
-- Name: ops_audit ops_audit_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ops_audit
    ADD CONSTRAINT ops_audit_pkey PRIMARY KEY (audit_id);


--
-- Name: ops_etl_log ops_etl_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ops_etl_log
    ADD CONSTRAINT ops_etl_log_pkey PRIMARY KEY (log_id);


--
-- Name: org_datasets org_datasets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkey PRIMARY KEY (org_id, pkg_id);


--
-- Name: org_markets org_markets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_pkey PRIMARY KEY (org_id, market_id);


--
-- Name: orgs orgs_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_name_key UNIQUE (name);


--
-- Name: orgs orgs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_pkey PRIMARY KEY (org_id);


--
-- Name: output_contracts output_contracts_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_code_key UNIQUE (code);


--
-- Name: output_contracts output_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_pkey PRIMARY KEY (contract_id);


--
-- Name: predictive_visual_engine predictive_visual_engine_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_pkey PRIMARY KEY (prediction_id);


--
-- Name: prompt_template_contracts prompt_template_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_pkey PRIMARY KEY (prompt_id, contract_id);


--
-- Name: prompt_template_presql prompt_template_presql_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_pkey PRIMARY KEY (prompt_id, snippet_id);


--
-- Name: prompt_template_required_fields prompt_template_required_fields_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_pkey PRIMARY KEY (prompt_id, field_id);


--
-- Name: prompt_templates prompt_templates_name_version_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_name_version_key UNIQUE (name, version);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (prompt_id);


--
-- Name: scene_library scene_library_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_pkey PRIMARY KEY (scene_id);


--
-- Name: segments segments_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_name_key UNIQUE (org_id, name);


--
-- Name: segments segments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_pkey PRIMARY KEY (segment_id);


--
-- Name: social_account_metrics social_account_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_account_metrics
    ADD CONSTRAINT social_account_metrics_pkey PRIMARY KEY (integration_id, period_start, period_end);


--
-- Name: social_accounts social_accounts_account_id_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_id_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_integration_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_integration_id_key UNIQUE (integration_id);


--
-- Name: social_post_metrics social_post_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_post_metrics
    ADD CONSTRAINT social_post_metrics_pkey PRIMARY KEY (integration_id, post_external_id, metric_date);


--
-- Name: social_posts social_posts_integration_id_post_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_integration_id_post_external_id_key UNIQUE (integration_id, post_external_id);


--
-- Name: social_posts social_posts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_pkey PRIMARY KEY (id);


--
-- Name: sql_snippets sql_snippets_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_name_key UNIQUE (name);


--
-- Name: sql_snippets sql_snippets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_pkey PRIMARY KEY (snippet_id);


--
-- Name: sync_logs sync_logs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_pkey PRIMARY KEY (id);


--
-- Name: flow_steps uq_step; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT uq_step UNIQUE (flow_id, ord);


--
-- Name: users users_org_id_email_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_org_id_email_key UNIQUE (org_id, email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: value_contexts value_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: variation_policies variation_policies_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_pkey PRIMARY KEY (policy_id);


--
-- Name: video_scenes video_scenes_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_pkey PRIMARY KEY (scene_id);


--
-- Name: video_templates video_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_pkey PRIMARY KEY (vtpl_id);


--
-- Name: ai_queries ai_queries_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.ai_queries
    ADD CONSTRAINT ai_queries_pkey PRIMARY KEY (query_id);


--
-- Name: migration_log migration_log_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.migration_log
    ADD CONSTRAINT migration_log_pkey PRIMARY KEY (id);


--
-- Name: performance_snapshot performance_snapshot_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.performance_snapshot
    ADD CONSTRAINT performance_snapshot_pkey PRIMARY KEY (snapshot_id);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (template_id);


--
-- Name: scheduled_jobs scheduled_jobs_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.scheduled_jobs
    ADD CONSTRAINT scheduled_jobs_pkey PRIMARY KEY (job_id);


--
-- Name: ai_models ai_models_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.ai_models
    ADD CONSTRAINT ai_models_pkey PRIMARY KEY (model_id);


--
-- Name: campaigns campaigns_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.campaigns
    ADD CONSTRAINT campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: contexts contexts_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.contexts
    ADD CONSTRAINT contexts_pkey PRIMARY KEY (context_id);


--
-- Name: creative_outputs creative_outputs_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.creative_outputs
    ADD CONSTRAINT creative_outputs_pkey PRIMARY KEY (output_id);


--
-- Name: integrations integrations_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.integrations
    ADD CONSTRAINT integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: organizations organizations_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.organizations
    ADD CONSTRAINT organizations_pkey PRIMARY KEY (org_id);


--
-- Name: performance_metrics performance_metrics_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.performance_metrics
    ADD CONSTRAINT performance_metrics_pkey PRIMARY KEY (metric_id);


--
-- Name: reference_entities reference_entities_pkey; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.reference_entities
    ADD CONSTRAINT reference_entities_pkey PRIMARY KEY (ref_id);


--
-- Name: campaigns uq_campaign_business; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.campaigns
    ADD CONSTRAINT uq_campaign_business UNIQUE (org_id, name, start_date);


--
-- Name: integrations uq_integration_business; Type: CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.integrations
    ADD CONSTRAINT uq_integration_business UNIQUE (org_id, platform, account_id);


--
-- Name: raw_channel_data raw_channel_data_pkey; Type: CONSTRAINT; Schema: cmis_staging; Owner: begin
--

ALTER TABLE ONLY cmis_staging.raw_channel_data
    ADD CONSTRAINT raw_channel_data_pkey PRIMARY KEY (id);


--
-- Name: example_sets example_sets_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_pkey PRIMARY KEY (example_id);


--
-- Name: example_used_fields example_used_fields_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_pkey PRIMARY KEY (example_id, field_id);


--
-- Name: test_matrix test_matrix_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.test_matrix
    ADD CONSTRAINT test_matrix_pkey PRIMARY KEY (test_id);


--
-- Name: awareness_stages awareness_stages_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.awareness_stages
    ADD CONSTRAINT awareness_stages_pkey PRIMARY KEY (stage);


--
-- Name: channel_formats channel_formats_channel_id_code_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_channel_id_code_key UNIQUE (channel_id, code);


--
-- Name: channel_formats channel_formats_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_pkey PRIMARY KEY (format_id);


--
-- Name: channels channels_code_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_code_key UNIQUE (code);


--
-- Name: channels channels_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_pkey PRIMARY KEY (channel_id);


--
-- Name: component_types component_types_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.component_types
    ADD CONSTRAINT component_types_pkey PRIMARY KEY (type_code);


--
-- Name: frameworks frameworks_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.frameworks
    ADD CONSTRAINT frameworks_pkey PRIMARY KEY (framework_id);


--
-- Name: funnel_stages funnel_stages_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.funnel_stages
    ADD CONSTRAINT funnel_stages_pkey PRIMARY KEY (stage);


--
-- Name: industries industries_name_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.industries
    ADD CONSTRAINT industries_name_key UNIQUE (name);


--
-- Name: industries industries_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.industries
    ADD CONSTRAINT industries_pkey PRIMARY KEY (industry_id);


--
-- Name: kpis kpis_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.kpis
    ADD CONSTRAINT kpis_pkey PRIMARY KEY (kpi);


--
-- Name: marketing_objectives marketing_objectives_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.marketing_objectives
    ADD CONSTRAINT marketing_objectives_pkey PRIMARY KEY (objective);


--
-- Name: markets markets_market_name_language_code_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.markets
    ADD CONSTRAINT markets_market_name_language_code_key UNIQUE (market_name, language_code);


--
-- Name: markets markets_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.markets
    ADD CONSTRAINT markets_pkey PRIMARY KEY (market_id);


--
-- Name: modules_old modules_code_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.modules_old
    ADD CONSTRAINT modules_code_key UNIQUE (code);


--
-- Name: modules_old modules_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.modules_old
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: naming_templates_old naming_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.naming_templates_old
    ADD CONSTRAINT naming_templates_pkey PRIMARY KEY (naming_id);


--
-- Name: naming_templates_old naming_templates_scope_key; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.naming_templates_old
    ADD CONSTRAINT naming_templates_scope_key UNIQUE (scope);


--
-- Name: proof_layers proof_layers_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.proof_layers
    ADD CONSTRAINT proof_layers_pkey PRIMARY KEY (level);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: strategies strategies_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.strategies
    ADD CONSTRAINT strategies_pkey PRIMARY KEY (strategy);


--
-- Name: tones tones_pkey; Type: CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.tones
    ADD CONSTRAINT tones_pkey PRIMARY KEY (tone);


--
-- Name: visual_kpis visual_kpis_name_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis
    ADD CONSTRAINT visual_kpis_name_key UNIQUE (name);


--
-- Name: visual_kpis visual_kpis_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis
    ADD CONSTRAINT visual_kpis_pkey PRIMARY KEY (kpi_id);


--
-- Name: visual_principles visual_principles_name_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles
    ADD CONSTRAINT visual_principles_name_key UNIQUE (name);


--
-- Name: visual_principles visual_principles_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles
    ADD CONSTRAINT visual_principles_pkey PRIMARY KEY (principle_id);


--
-- Name: visual_recommendations visual_recommendations_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_pkey PRIMARY KEY (recommendation_id);


--
-- Name: idx_ad_audiences_entity; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_audiences_entity ON cmis.ad_audiences USING btree (entity_level, entity_external_id);


--
-- Name: idx_ad_audiences_platform; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_audiences_platform ON cmis.ad_audiences USING btree (platform);


--
-- Name: idx_ad_metrics_date; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_metrics_date ON cmis.ad_metrics USING btree (date_start, date_stop);


--
-- Name: idx_ad_metrics_entity; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_metrics_entity ON cmis.ad_metrics USING btree (entity_level, entity_external_id);


--
-- Name: idx_ai_actions_org; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ai_actions_org ON cmis.ai_actions USING btree (org_id);


--
-- Name: idx_ai_actions_org_time; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ai_actions_org_time ON cmis.ai_actions USING btree (org_id, created_at);


--
-- Name: idx_anchors_code; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_anchors_code ON cmis.anchors USING gist (code);


--
-- Name: idx_assets_campaign; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_assets_campaign ON cmis.creative_assets USING btree (campaign_id);


--
-- Name: idx_audit_log_ts; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_audit_log_ts ON cmis.audit_log USING btree (ts DESC);


--
-- Name: idx_campaign_offerings_cid; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_offerings_cid ON cmis.campaign_offerings USING btree (campaign_id);


--
-- Name: idx_campaign_offerings_oid; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_offerings_oid ON cmis.campaign_offerings USING btree (offering_id);


--
-- Name: idx_cc_channel; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_channel ON cmis.copy_components USING btree (channel_id);


--
-- Name: idx_cc_content_trgm; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_content_trgm ON cmis.copy_components USING gin (content public.gin_trgm_ops);


--
-- Name: idx_cc_industry; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_industry ON cmis.copy_components USING btree (industry_id);


--
-- Name: idx_cc_market; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_market ON cmis.copy_components USING btree (market_id);


--
-- Name: idx_cc_type; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_type ON cmis.copy_components USING btree (type_code);


--
-- Name: idx_content_items_channel; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_channel ON cmis.content_items USING btree (channel_id);


--
-- Name: idx_content_items_context; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_context ON cmis.content_items USING btree (context_id);


--
-- Name: idx_content_items_example; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_example ON cmis.content_items USING btree (example_id);


--
-- Name: idx_content_items_format; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_format ON cmis.content_items USING btree (format_id);


--
-- Name: idx_content_items_plan; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_plan ON cmis.content_items USING btree (plan_id);


--
-- Name: idx_creative_assets_org; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_creative_assets_org ON cmis.creative_assets USING btree (org_id);


--
-- Name: idx_field_value_text; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_value_text ON cmis.field_values USING gin (((value)::text) public.gin_trgm_ops);


--
-- Name: idx_field_values_created; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_values_created ON cmis.field_values USING btree (created_at DESC);


--
-- Name: idx_field_values_json; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_values_json ON cmis.field_values USING gin (value jsonb_path_ops);


--
-- Name: idx_orgs_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_orgs_id ON cmis.orgs USING btree (org_id);


--
-- Name: idx_social_post_metrics_integration_date; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_social_post_metrics_integration_date ON cmis.social_post_metrics USING btree (integration_id, metric_date);


--
-- Name: idx_social_post_metrics_metric_date; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_social_post_metrics_metric_date ON cmis.social_post_metrics USING btree (metric_date);


--
-- Name: idx_social_post_metrics_post_date; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_social_post_metrics_post_date ON cmis.social_post_metrics USING btree (post_external_id, metric_date);


--
-- Name: idx_users_org; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_users_org ON cmis.users USING btree (org_id);


--
-- Name: idx_values_context; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_values_context ON cmis.field_values USING btree (context_id);


--
-- Name: idx_values_field; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_values_field ON cmis.field_values USING btree (field_id);


--
-- Name: idx_video_scenes_asset; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_video_scenes_asset ON cmis.video_scenes USING btree (asset_id);


--
-- Name: idx_prompt_templates_trgm; Type: INDEX; Schema: cmis_analytics; Owner: begin
--

CREATE INDEX idx_prompt_templates_trgm ON cmis_analytics.prompt_templates USING gin (prompt_text public.gin_trgm_ops);


--
-- Name: uq_perf_snapshot; Type: INDEX; Schema: cmis_analytics; Owner: begin
--

CREATE UNIQUE INDEX uq_perf_snapshot ON cmis_analytics.performance_snapshot USING btree (org_id, campaign_id, kpi, observed_at);


--
-- Name: idx_examples_body_fts; Type: INDEX; Schema: lab; Owner: begin
--

CREATE INDEX idx_examples_body_fts ON lab.example_sets USING gin (((body)::text) public.gin_trgm_ops);


--
-- Name: idx_examples_tags; Type: INDEX; Schema: lab; Owner: begin
--

CREATE INDEX idx_examples_tags ON lab.example_sets USING gin (tags);


--
-- Name: creative_briefs enforce_brief_completeness; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER enforce_brief_completeness BEFORE INSERT OR UPDATE ON cmis.creative_briefs FOR EACH ROW EXECUTE FUNCTION cmis.prevent_incomplete_briefs();


--
-- Name: ad_accounts ad_accounts_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_accounts ad_accounts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_audiences ad_audiences_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_audiences ad_audiences_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_campaigns ad_campaigns_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_campaigns ad_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_entities ad_entities_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_entities ad_entities_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_metrics ad_metrics_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_metrics ad_metrics_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_sets ad_sets_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_sets ad_sets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ai_actions ai_actions_audit_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_audit_id_fkey FOREIGN KEY (audit_id) REFERENCES cmis.audit_log(log_id);


--
-- Name: ai_generated_campaigns ai_generated_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: anchors anchors_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: audio_templates audio_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: audit_log audit_log_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: bundle_offerings bundle_offerings_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: bundle_offerings bundle_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: campaign_offerings campaign_offerings_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE CASCADE;


--
-- Name: campaign_offerings campaign_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: campaign_performance_dashboard campaign_performance_dashboard_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_performance_dashboard
    ADD CONSTRAINT campaign_performance_dashboard_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE CASCADE;


--
-- Name: cognitive_tracker_template cognitive_tracker_template_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: cognitive_trends cognitive_trends_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: compliance_audits compliance_audits_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: compliance_rule_channels compliance_rule_channels_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: content_items content_items_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: content_items content_items_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_items content_items_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: content_items content_items_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE CASCADE;


--
-- Name: content_plans content_plans_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: content_plans content_plans_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: copy_components copy_components_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: copy_components copy_components_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: copy_components copy_components_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: copy_components copy_components_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: creative_assets creative_assets_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: creative_assets creative_assets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_briefs creative_briefs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_contexts creative_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: data_feeds data_feeds_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: dataset_files dataset_files_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_exp_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_exp_id_fkey FOREIGN KEY (exp_id) REFERENCES cmis.experiments(exp_id) ON DELETE CASCADE;


--
-- Name: experiments experiments_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: experiments experiments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.export_bundles(bundle_id) ON DELETE CASCADE;


--
-- Name: export_bundles export_bundles_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: feed_items feed_items_feed_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_feed_id_fkey FOREIGN KEY (feed_id) REFERENCES cmis.data_feeds(feed_id) ON DELETE CASCADE;


--
-- Name: field_aliases field_aliases_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: field_definitions field_definitions_guidance_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_guidance_anchor_fkey FOREIGN KEY (guidance_anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: field_definitions field_definitions_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: field_values field_values_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id) ON DELETE CASCADE;


--
-- Name: field_values field_values_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: content_items fk_content_item_asset; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT fk_content_item_asset FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE SET NULL;


--
-- Name: flow_steps flow_steps_flow_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_flow_id_fkey FOREIGN KEY (flow_id) REFERENCES cmis.flows(flow_id) ON DELETE CASCADE;


--
-- Name: flows flows_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: offerings_full_details offerings_full_details_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: offerings offerings_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: org_markets org_markets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: predictive_visual_engine predictive_visual_engine_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_contract_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_contract_id_fkey FOREIGN KEY (contract_id) REFERENCES cmis.output_contracts(contract_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_snippet_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_snippet_id_fkey FOREIGN KEY (snippet_id) REFERENCES cmis.sql_snippets(snippet_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_templates prompt_templates_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: scene_library scene_library_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_anchor_fkey FOREIGN KEY (anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: scene_library scene_library_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: segments segments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: social_account_metrics social_account_metrics_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_account_metrics
    ADD CONSTRAINT social_account_metrics_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_accounts social_accounts_account_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_accounts social_accounts_account_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: social_post_metrics social_post_metrics_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_post_metrics
    ADD CONSTRAINT social_post_metrics_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_post_metrics social_post_metrics_social_post_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_post_metrics
    ADD CONSTRAINT social_post_metrics_social_post_id_fkey FOREIGN KEY (social_post_id) REFERENCES cmis.social_posts(id) ON DELETE CASCADE;


--
-- Name: social_posts social_posts_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_posts social_posts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: sync_logs sync_logs_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: sync_logs sync_logs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: users users_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL DEFERRABLE;


--
-- Name: value_contexts value_contexts_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE SET NULL;


--
-- Name: value_contexts value_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_segment_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_segment_id_fkey FOREIGN KEY (segment_id) REFERENCES cmis.segments(segment_id) ON DELETE SET NULL;


--
-- Name: variation_policies variation_policies_naming_ref_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_naming_ref_fkey FOREIGN KEY (naming_ref) REFERENCES cmis.naming_templates(naming_id);


--
-- Name: variation_policies variation_policies_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: video_scenes video_scenes_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: video_templates video_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: ai_models ai_models_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.ai_models
    ADD CONSTRAINT ai_models_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: campaigns campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.campaigns
    ADD CONSTRAINT campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: contexts contexts_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.contexts
    ADD CONSTRAINT contexts_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id);


--
-- Name: contexts contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.contexts
    ADD CONSTRAINT contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: creative_outputs creative_outputs_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.creative_outputs
    ADD CONSTRAINT creative_outputs_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id);


--
-- Name: creative_outputs creative_outputs_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.creative_outputs
    ADD CONSTRAINT creative_outputs_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis_refactored.contexts(context_id);


--
-- Name: creative_outputs creative_outputs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.creative_outputs
    ADD CONSTRAINT creative_outputs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: integrations integrations_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.integrations
    ADD CONSTRAINT integrations_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: performance_metrics performance_metrics_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.performance_metrics
    ADD CONSTRAINT performance_metrics_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id);


--
-- Name: performance_metrics performance_metrics_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.performance_metrics
    ADD CONSTRAINT performance_metrics_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis_refactored.organizations(org_id);


--
-- Name: performance_metrics performance_metrics_output_id_fkey; Type: FK CONSTRAINT; Schema: cmis_refactored; Owner: begin
--

ALTER TABLE ONLY cmis_refactored.performance_metrics
    ADD CONSTRAINT performance_metrics_output_id_fkey FOREIGN KEY (output_id) REFERENCES cmis_refactored.creative_outputs(output_id);


--
-- Name: raw_channel_data raw_channel_data_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis_staging; Owner: begin
--

ALTER TABLE ONLY cmis_staging.raw_channel_data
    ADD CONSTRAINT raw_channel_data_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis_refactored.integrations(integration_id);


--
-- Name: example_sets example_sets_anchor_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_anchor_fkey FOREIGN KEY (anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: example_sets example_sets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis_refactored.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: example_sets example_sets_org_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: example_used_fields example_used_fields_example_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id) ON DELETE CASCADE;


--
-- Name: example_used_fields example_used_fields_field_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: test_matrix test_matrix_org_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.test_matrix
    ADD CONSTRAINT test_matrix_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: channel_formats channel_formats_channel_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: gpts_data_user
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_channel_id_fkey FOREIGN KEY (channel_id) REFERENCES public.channels(channel_id) ON DELETE CASCADE;


--
-- Name: visual_recommendations visual_recommendations_linked_kpi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_linked_kpi_fkey FOREIGN KEY (linked_kpi) REFERENCES public.visual_kpis(name);


--
-- Name: visual_recommendations visual_recommendations_objective_code_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_objective_code_fkey FOREIGN KEY (objective_code) REFERENCES public.marketing_objectives(objective);


--
-- Name: visual_recommendations visual_recommendations_recommended_principle_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_recommended_principle_fkey FOREIGN KEY (recommended_principle) REFERENCES public.visual_principles(name);


--
-- Name: ad_accounts; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_audiences; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_audiences ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_campaigns; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_entities; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_entities ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_metrics; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_metrics ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_sets; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_actions; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ai_actions ENABLE ROW LEVEL SECURITY;

--
-- Name: audit_log; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.audit_log ENABLE ROW LEVEL SECURITY;

--
-- Name: content_plans; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.content_plans ENABLE ROW LEVEL SECURITY;

--
-- Name: creative_assets; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.creative_assets ENABLE ROW LEVEL SECURITY;

--
-- Name: data_feeds; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.data_feeds ENABLE ROW LEVEL SECURITY;

--
-- Name: experiments; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.experiments ENABLE ROW LEVEL SECURITY;

--
-- Name: export_bundles; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.export_bundles ENABLE ROW LEVEL SECURITY;

--
-- Name: feed_items; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.feed_items ENABLE ROW LEVEL SECURITY;

--
-- Name: flows; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.flows ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_accounts org_isolation_ad_accounts; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_accounts ON cmis.ad_accounts USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_audiences org_isolation_ad_audiences; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_audiences ON cmis.ad_audiences USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_campaigns org_isolation_ad_campaigns; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_campaigns ON cmis.ad_campaigns USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_entities org_isolation_ad_entities; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_entities ON cmis.ad_entities USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_metrics org_isolation_ad_metrics; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_metrics ON cmis.ad_metrics USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_sets org_isolation_ad_sets; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ad_sets ON cmis.ad_sets USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ai_actions org_isolation_ai_actions; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_ai_actions ON cmis.ai_actions USING ((org_id = (current_setting('app.current_org_id'::text))::uuid)) WITH CHECK ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: creative_assets org_isolation_assets; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_assets ON cmis.creative_assets FOR SELECT USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: audit_log org_isolation_audit_log; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_audit_log ON cmis.audit_log USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: content_plans org_isolation_content_plans; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_content_plans ON cmis.content_plans USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: creative_assets org_isolation_creative_assets; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_creative_assets ON cmis.creative_assets USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: data_feeds org_isolation_data_feeds; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_data_feeds ON cmis.data_feeds USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: experiments org_isolation_experiments; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_experiments ON cmis.experiments USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: export_bundles org_isolation_export_bundles; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_export_bundles ON cmis.export_bundles USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: feed_items org_isolation_feed_items; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_feed_items ON cmis.feed_items USING ((feed_id IN ( SELECT data_feeds.feed_id
   FROM cmis.data_feeds
  WHERE (data_feeds.org_id = (current_setting('app.current_org_id'::text))::uuid))));


--
-- Name: flows org_isolation_flows; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_flows ON cmis.flows USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: users org_isolation_users; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY org_isolation_users ON cmis.users USING ((org_id = (current_setting('app.current_org_id'::text))::uuid)) WITH CHECK ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: users; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.users ENABLE ROW LEVEL SECURITY;

--
-- Name: example_sets; Type: ROW SECURITY; Schema: lab; Owner: begin
--

ALTER TABLE lab.example_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: example_sets org_isolation_example_sets; Type: POLICY; Schema: lab; Owner: begin
--

CREATE POLICY org_isolation_example_sets ON lab.example_sets USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

GRANT CREATE ON SCHEMA public TO gpts_data_user;


--
-- PostgreSQL database dump complete
--

