--
-- PostgreSQL database dump
--

\restrict ds4Y3MzJy0mPM6YugX9AV4y4jZbEVOUQfccEawGvGAPklKj3vatoKlitHbb8Wo7

-- Dumped from database version 18.0 (Ubuntu 18.0-1.pgdg24.04+3)
-- Dumped by pg_dump version 18.0 (Ubuntu 18.0-1.pgdg24.04+3)

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
-- Name: cmis; Type: SCHEMA; Schema: -; Owner: cmis
--

CREATE SCHEMA cmis;


ALTER SCHEMA cmis OWNER TO cmis;

--
-- Name: auto_delete_unapproved_assets(); Type: FUNCTION; Schema: cmis; Owner: cmis
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


ALTER FUNCTION cmis.auto_delete_unapproved_assets() OWNER TO cmis;

--
-- Name: cleanup_scheduler(); Type: PROCEDURE; Schema: cmis; Owner: cmis
--

CREATE PROCEDURE cmis.cleanup_scheduler()
    LANGUAGE sql
    AS $$
  SELECT cmis.auto_delete_unapproved_assets();
$$;


ALTER PROCEDURE cmis.cleanup_scheduler() OWNER TO cmis;

--
-- Name: create_campaign_and_context_safe(uuid, uuid, uuid, text, text, text, text[]); Type: FUNCTION; Schema: cmis; Owner: cmis
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


ALTER FUNCTION cmis.create_campaign_and_context_safe(p_org_id uuid, p_offering_id uuid, p_segment_id uuid, p_campaign_name text, p_framework text, p_tone text, p_tags text[]) OWNER TO cmis;

--
-- Name: enforce_creative_context(); Type: FUNCTION; Schema: cmis; Owner: cmis
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


ALTER FUNCTION cmis.enforce_creative_context() OWNER TO cmis;

--
-- Name: get_session_memory(text); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.get_session_memory(p_session_id text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE result JSONB; BEGIN SELECT memory INTO result FROM cmis.sessions_memory WHERE session_id = p_session_id; RETURN result; END; $$;


ALTER FUNCTION cmis.get_session_memory(p_session_id text) OWNER TO begin;

--
-- Name: prevent_incomplete_briefs(); Type: FUNCTION; Schema: cmis; Owner: cmis
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


ALTER FUNCTION cmis.prevent_incomplete_briefs() OWNER TO cmis;

--
-- Name: sync_social_metrics(); Type: FUNCTION; Schema: cmis; Owner: cmis
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


ALTER FUNCTION cmis.sync_social_metrics() OWNER TO cmis;

--
-- Name: upsert_session_memory(text, text, jsonb); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.upsert_session_memory(p_session_id text, p_user_id text, p_memory jsonb) RETURNS void
    LANGUAGE plpgsql
    AS $$ BEGIN INSERT INTO cmis.sessions_memory (session_id, user_id, memory) VALUES (p_session_id, p_user_id, p_memory) ON CONFLICT (session_id) DO UPDATE SET memory = EXCLUDED.memory, updated_at = now(); END; $$;


ALTER FUNCTION cmis.upsert_session_memory(p_session_id text, p_user_id text, p_memory jsonb) OWNER TO begin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ad_accounts; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_accounts OWNER TO cmis;

--
-- Name: ad_audiences; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_audiences OWNER TO cmis;

--
-- Name: ad_campaigns; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_campaigns OWNER TO cmis;

--
-- Name: ad_entities; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_entities OWNER TO cmis;

--
-- Name: ad_metrics; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_metrics OWNER TO cmis;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.ad_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.ad_metrics_id_seq OWNER TO cmis;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.ad_metrics_id_seq OWNED BY cmis.ad_metrics.id;


--
-- Name: ad_sets; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ad_sets OWNER TO cmis;

--
-- Name: ai_actions; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ai_actions OWNER TO cmis;

--
-- Name: ai_generated_campaigns; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ai_generated_campaigns OWNER TO cmis;

--
-- Name: analytics_integrations; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.analytics_integrations OWNER TO cmis;

--
-- Name: anchors; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.anchors (
    anchor_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    code public.ltree NOT NULL,
    title text,
    file_ref text,
    section text
);


ALTER TABLE cmis.anchors OWNER TO cmis;

--
-- Name: audio_templates; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.audio_templates (
    atpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    voice_hints jsonb,
    sfx_pack jsonb,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.audio_templates OWNER TO cmis;

--
-- Name: audit_log; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.audit_log OWNER TO cmis;

--
-- Name: awareness_stages; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.awareness_stages AS
 SELECT stage
   FROM public.awareness_stages;


ALTER VIEW cmis.awareness_stages OWNER TO cmis;

--
-- Name: bundle_offerings; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.bundle_offerings (
    bundle_id uuid NOT NULL,
    offering_id uuid NOT NULL
);


ALTER TABLE cmis.bundle_offerings OWNER TO cmis;

--
-- Name: campaign_offerings; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.campaign_offerings (
    campaign_id uuid NOT NULL,
    offering_id uuid NOT NULL
);


ALTER TABLE cmis.campaign_offerings OWNER TO cmis;

--
-- Name: campaign_performance_dashboard; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.campaign_performance_dashboard OWNER TO cmis;

--
-- Name: campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaigns (
    campaign_id uuid NOT NULL,
    org_id uuid,
    name text,
    objective text,
    status text,
    start_date date,
    end_date date,
    budget numeric,
    currency text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE cmis.campaigns OWNER TO begin;

--
-- Name: channel_formats; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.channel_formats AS
 SELECT format_id,
    channel_id,
    code,
    ratio,
    length_hint
   FROM public.channel_formats;


ALTER VIEW cmis.channel_formats OWNER TO cmis;

--
-- Name: channels; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.channels AS
 SELECT channel_id,
    code,
    name,
    constraints
   FROM public.channels;


ALTER VIEW cmis.channels OWNER TO cmis;

--
-- Name: cognitive_tracker_template; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.cognitive_tracker_template OWNER TO cmis;

--
-- Name: cognitive_trends; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.cognitive_trends OWNER TO cmis;

--
-- Name: compliance_audits; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.compliance_audits OWNER TO cmis;

--
-- Name: compliance_rule_channels; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.compliance_rule_channels (
    rule_id uuid NOT NULL,
    channel_id integer NOT NULL
);


ALTER TABLE cmis.compliance_rule_channels OWNER TO cmis;

--
-- Name: compliance_rules; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.compliance_rules (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    description text NOT NULL,
    severity text NOT NULL,
    params jsonb,
    CONSTRAINT compliance_rules_severity_check CHECK ((severity = ANY (ARRAY['warn'::text, 'block'::text])))
);


ALTER TABLE cmis.compliance_rules OWNER TO cmis;

--
-- Name: component_types; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.component_types AS
 SELECT type_code
   FROM public.component_types;


ALTER VIEW cmis.component_types OWNER TO cmis;

--
-- Name: content_items; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.content_items OWNER TO cmis;

--
-- Name: content_plans; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.content_plans OWNER TO cmis;

--
-- Name: copy_components; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.copy_components OWNER TO cmis;

--
-- Name: TABLE copy_components; Type: COMMENT; Schema: cmis; Owner: cmis
--

COMMENT ON TABLE cmis.copy_components IS 'مكونات المحتوى النصي المُولَّدة مثل hook و headline و CTA. كل مكون يمثل قطعة نصية قابلة لإعادة الاستخدام ضمن السياق الإبداعي.';


--
-- Name: COLUMN copy_components.type_code; Type: COMMENT; Schema: cmis; Owner: cmis
--

COMMENT ON COLUMN cmis.copy_components.type_code IS 'نوع المكون النصي: hook, headline, benefit, proof... تُستخدم لتحديد وظيفة النص داخل الرسالة الإعلانية.';


--
-- Name: COLUMN copy_components.context_id; Type: COMMENT; Schema: cmis; Owner: cmis
--

COMMENT ON COLUMN cmis.copy_components.context_id IS 'السياق المعرفي الذي يربط هذا المكون بحملة معينة، جمهور، نبرة، أو مرحلة وعي معينة.';


--
-- Name: COLUMN copy_components.example_id; Type: COMMENT; Schema: cmis; Owner: cmis
--

COMMENT ON COLUMN cmis.copy_components.example_id IS 'معرّف مجموعة الحقول (example_set) التي تم استخدامها لتوليد هذا النص. يساعد في تتبع وفهم المنطق التوليدي.';


--
-- Name: creative_assets; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.creative_assets OWNER TO cmis;

--
-- Name: creative_briefs; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.creative_briefs (
    brief_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    brief_data jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.creative_briefs OWNER TO cmis;

--
-- Name: creative_contexts; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.creative_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    creative_brief jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.creative_contexts OWNER TO cmis;

--
-- Name: data_feeds; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.data_feeds (
    feed_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    kind text NOT NULL,
    source_meta jsonb,
    last_ingested timestamp with time zone,
    CONSTRAINT data_feeds_kind_check CHECK ((kind = ANY (ARRAY['price'::text, 'stock'::text, 'location'::text, 'catalog'::text])))
);


ALTER TABLE cmis.data_feeds OWNER TO cmis;

--
-- Name: dataset_files; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.dataset_files (
    file_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    pkg_id uuid NOT NULL,
    filename text NOT NULL,
    checksum text,
    meta jsonb
);


ALTER TABLE cmis.dataset_files OWNER TO cmis;

--
-- Name: dataset_packages; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.dataset_packages (
    pkg_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    version text NOT NULL,
    notes text
);


ALTER TABLE cmis.dataset_packages OWNER TO cmis;

--
-- Name: experiment_variants; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.experiment_variants (
    exp_id uuid NOT NULL,
    asset_id uuid NOT NULL
);


ALTER TABLE cmis.experiment_variants OWNER TO cmis;

--
-- Name: experiments; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.experiments OWNER TO cmis;

--
-- Name: export_bundle_items; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.export_bundle_items (
    bundle_id uuid NOT NULL,
    asset_id uuid NOT NULL
);


ALTER TABLE cmis.export_bundle_items OWNER TO cmis;

--
-- Name: export_bundles; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.export_bundles (
    bundle_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.export_bundles OWNER TO cmis;

--
-- Name: feed_items; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.feed_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    feed_id uuid NOT NULL,
    sku text,
    payload jsonb NOT NULL,
    valid_from timestamp with time zone DEFAULT now(),
    valid_to timestamp with time zone
);


ALTER TABLE cmis.feed_items OWNER TO cmis;

--
-- Name: field_aliases; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.field_aliases (
    alias_slug text NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE cmis.field_aliases OWNER TO cmis;

--
-- Name: field_definitions; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.field_definitions OWNER TO cmis;

--
-- Name: field_values; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.field_values OWNER TO cmis;

--
-- Name: flow_steps; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.flow_steps OWNER TO cmis;

--
-- Name: flows; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.flows OWNER TO cmis;

--
-- Name: frameworks; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.frameworks AS
 SELECT framework_id,
    framework_name,
    framework_type,
    description,
    created_at
   FROM public.frameworks;


ALTER VIEW cmis.frameworks OWNER TO cmis;

--
-- Name: funnel_stages; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.funnel_stages AS
 SELECT stage
   FROM public.funnel_stages;


ALTER VIEW cmis.funnel_stages OWNER TO cmis;

--
-- Name: industries; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.industries AS
 SELECT industry_id,
    name
   FROM public.industries;


ALTER VIEW cmis.industries OWNER TO cmis;

--
-- Name: integrations; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.integrations (
    integration_id uuid NOT NULL,
    org_id uuid,
    platform text,
    account_id text,
    access_token text,
    is_active boolean,
    created_at timestamp with time zone,
    business_id text,
    username text
);


ALTER TABLE cmis.integrations OWNER TO begin;

--
-- Name: kpis; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.kpis AS
 SELECT kpi,
    description
   FROM public.kpis;


ALTER VIEW cmis.kpis OWNER TO cmis;

--
-- Name: logs_migration; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.logs_migration (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    phase text NOT NULL,
    status text NOT NULL,
    executed_at timestamp without time zone DEFAULT now(),
    details jsonb DEFAULT '{}'::jsonb
);


ALTER TABLE cmis.logs_migration OWNER TO cmis;

--
-- Name: marketing_objectives; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.marketing_objectives AS
 SELECT objective,
    display_name,
    category,
    description
   FROM public.marketing_objectives;


ALTER VIEW cmis.marketing_objectives OWNER TO cmis;

--
-- Name: markets; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.markets AS
 SELECT market_id,
    market_name,
    language_code,
    currency_code,
    text_direction
   FROM public.markets;


ALTER VIEW cmis.markets OWNER TO cmis;

--
-- Name: meta_documentation; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.meta_documentation (
    doc_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    meta_key text NOT NULL,
    meta_value text NOT NULL,
    updated_by text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.meta_documentation OWNER TO cmis;

--
-- Name: meta_field_dictionary; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.meta_field_dictionary (
    id integer NOT NULL,
    field_name text NOT NULL,
    semantic_meaning text,
    usage_context text,
    unified_alias text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.meta_field_dictionary OWNER TO cmis;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.meta_field_dictionary_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNER TO cmis;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNED BY cmis.meta_field_dictionary.id;


--
-- Name: meta_function_descriptions; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.meta_function_descriptions (
    id integer NOT NULL,
    routine_schema text NOT NULL,
    routine_name text NOT NULL,
    description text,
    cognitive_category text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.meta_function_descriptions OWNER TO cmis;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.meta_function_descriptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNER TO cmis;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNED BY cmis.meta_function_descriptions.id;


--
-- Name: migrations; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE cmis.migrations OWNER TO cmis;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.migrations_id_seq OWNER TO cmis;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.migrations_id_seq OWNED BY cmis.migrations.id;


--
-- Name: modules; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.modules (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.modules OWNER TO cmis;

--
-- Name: modules_module_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.modules_module_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.modules_module_id_seq OWNER TO cmis;

--
-- Name: modules_module_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.modules_module_id_seq OWNED BY cmis.modules.module_id;


--
-- Name: naming_templates; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.naming_templates (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);


ALTER TABLE cmis.naming_templates OWNER TO cmis;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE; Schema: cmis; Owner: cmis
--

CREATE SEQUENCE cmis.naming_templates_naming_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNER TO cmis;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: cmis
--

ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNED BY cmis.naming_templates.naming_id;


--
-- Name: offerings; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.offerings OWNER TO cmis;

--
-- Name: offerings_full_details; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.offerings_full_details (
    detail_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    offering_id uuid,
    full_description text NOT NULL,
    pricing_notes text,
    target_segment text,
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis.offerings_full_details OWNER TO cmis;

--
-- Name: ops_audit; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.ops_audit (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    operation_name text NOT NULL,
    status text NOT NULL,
    executed_at timestamp with time zone DEFAULT now(),
    details jsonb
);


ALTER TABLE cmis.ops_audit OWNER TO cmis;

--
-- Name: ops_etl_log; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.ops_etl_log OWNER TO cmis;

--
-- Name: org_datasets; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.org_datasets (
    org_id uuid NOT NULL,
    pkg_id uuid NOT NULL,
    enabled boolean DEFAULT true
);


ALTER TABLE cmis.org_datasets OWNER TO cmis;

--
-- Name: org_markets; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.org_markets (
    org_id uuid NOT NULL,
    market_id integer NOT NULL,
    is_default boolean DEFAULT false
);


ALTER TABLE cmis.org_markets OWNER TO cmis;

--
-- Name: orgs; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.orgs (
    org_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name public.citext NOT NULL,
    default_locale text DEFAULT 'ar-BH'::text,
    currency text DEFAULT 'BHD'::text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.orgs OWNER TO cmis;

--
-- Name: output_contracts; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.output_contracts (
    contract_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    json_schema jsonb NOT NULL,
    notes text
);


ALTER TABLE cmis.output_contracts OWNER TO cmis;

--
-- Name: playbook_steps; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.playbook_steps AS
 SELECT step_id,
    flow_id AS playbook_id,
    ord AS step_order,
    COALESCE(name, type) AS step_name,
    NULL::text AS step_instructions,
    NULL::text AS module_reference
   FROM cmis.flow_steps s;


ALTER VIEW cmis.playbook_steps OWNER TO cmis;

--
-- Name: playbooks; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.playbooks AS
 SELECT flow_id AS playbook_id,
    name AS playbook_name,
    description
   FROM cmis.flows f;


ALTER VIEW cmis.playbooks OWNER TO cmis;

--
-- Name: predictive_visual_engine; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.predictive_visual_engine OWNER TO cmis;

--
-- Name: prompt_template_contracts; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.prompt_template_contracts (
    prompt_id uuid NOT NULL,
    contract_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_contracts OWNER TO cmis;

--
-- Name: prompt_template_presql; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.prompt_template_presql (
    prompt_id uuid NOT NULL,
    snippet_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_presql OWNER TO cmis;

--
-- Name: prompt_template_required_fields; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.prompt_template_required_fields (
    prompt_id uuid NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE cmis.prompt_template_required_fields OWNER TO cmis;

--
-- Name: prompt_templates; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.prompt_templates (
    prompt_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    task text NOT NULL,
    instructions text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE cmis.prompt_templates OWNER TO cmis;

--
-- Name: proof_layers; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.proof_layers AS
 SELECT level
   FROM public.proof_layers;


ALTER VIEW cmis.proof_layers OWNER TO cmis;

--
-- Name: scene_library; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.scene_library OWNER TO cmis;

--
-- Name: segments; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.segments (
    segment_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    persona jsonb,
    notes text
);


ALTER TABLE cmis.segments OWNER TO cmis;

--
-- Name: sessions_memory; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.sessions_memory (
    session_id text NOT NULL,
    user_id text NOT NULL,
    memory jsonb DEFAULT '{}'::jsonb NOT NULL,
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.sessions_memory OWNER TO begin;

--
-- Name: social_account_metrics; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.social_account_metrics OWNER TO cmis;

--
-- Name: social_accounts; Type: TABLE; Schema: cmis; Owner: cmis
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
    fetched_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE cmis.social_accounts OWNER TO cmis;

--
-- Name: social_post_metrics; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.social_post_metrics (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    social_post_id uuid NOT NULL,
    metric text NOT NULL,
    value numeric(20,4),
    fetched_at timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis.social_post_metrics OWNER TO cmis;

--
-- Name: social_posts; Type: TABLE; Schema: cmis; Owner: cmis
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
    created_at timestamp without time zone DEFAULT now(),
    video_url text,
    thumbnail_url text,
    children_media jsonb
);


ALTER TABLE cmis.social_posts OWNER TO cmis;

--
-- Name: sql_snippets; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.sql_snippets (
    snippet_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    sql text NOT NULL,
    description text
);


ALTER TABLE cmis.sql_snippets OWNER TO cmis;

--
-- Name: strategies; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.strategies AS
 SELECT strategy
   FROM public.strategies;


ALTER VIEW cmis.strategies OWNER TO cmis;

--
-- Name: sync_logs; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.sync_logs OWNER TO cmis;

--
-- Name: tones; Type: VIEW; Schema: cmis; Owner: cmis
--

CREATE VIEW cmis.tones AS
 SELECT tone
   FROM public.tones;


ALTER VIEW cmis.tones OWNER TO cmis;

--
-- Name: users; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.users (
    user_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    email public.citext NOT NULL,
    display_name text,
    role text DEFAULT 'editor'::text,
    CONSTRAINT users_role_check CHECK ((role = ANY (ARRAY['viewer'::text, 'editor'::text, 'admin'::text])))
);


ALTER TABLE cmis.users OWNER TO cmis;

--
-- Name: v_ai_insights; Type: VIEW; Schema: cmis; Owner: cmis
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


ALTER VIEW cmis.v_ai_insights OWNER TO cmis;

--
-- Name: v_marketing_reference; Type: VIEW; Schema: cmis; Owner: cmis
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


ALTER VIEW cmis.v_marketing_reference OWNER TO cmis;

--
-- Name: value_contexts; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.value_contexts OWNER TO cmis;

--
-- Name: variation_policies; Type: TABLE; Schema: cmis; Owner: cmis
--

CREATE TABLE cmis.variation_policies (
    policy_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    max_variations smallint DEFAULT 3,
    dco_enabled boolean DEFAULT true,
    naming_ref integer
);


ALTER TABLE cmis.variation_policies OWNER TO cmis;

--
-- Name: video_scenes; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.video_scenes OWNER TO cmis;

--
-- Name: video_templates; Type: TABLE; Schema: cmis; Owner: cmis
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


ALTER TABLE cmis.video_templates OWNER TO cmis;

--
-- Name: ad_metrics id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_metrics ALTER COLUMN id SET DEFAULT nextval('cmis.ad_metrics_id_seq'::regclass);


--
-- Name: meta_field_dictionary id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_field_dictionary ALTER COLUMN id SET DEFAULT nextval('cmis.meta_field_dictionary_id_seq'::regclass);


--
-- Name: meta_function_descriptions id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_function_descriptions ALTER COLUMN id SET DEFAULT nextval('cmis.meta_function_descriptions_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.migrations ALTER COLUMN id SET DEFAULT nextval('cmis.migrations_id_seq'::regclass);


--
-- Name: modules module_id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.modules ALTER COLUMN module_id SET DEFAULT nextval('cmis.modules_module_id_seq'::regclass);


--
-- Name: naming_templates naming_id; Type: DEFAULT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.naming_templates ALTER COLUMN naming_id SET DEFAULT nextval('cmis.naming_templates_naming_id_seq'::regclass);


--
-- Name: ad_accounts ad_accounts_integration_id_account_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_integration_id_account_external_id_key UNIQUE (integration_id, account_external_id);


--
-- Name: ad_accounts ad_accounts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_pkey PRIMARY KEY (id);


--
-- Name: ad_audiences ad_audiences_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_pkey PRIMARY KEY (id);


--
-- Name: ad_campaigns ad_campaigns_integration_id_campaign_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_integration_id_campaign_external_id_key UNIQUE (integration_id, campaign_external_id);


--
-- Name: ad_campaigns ad_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_pkey PRIMARY KEY (id);


--
-- Name: ad_entities ad_entities_integration_id_ad_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_integration_id_ad_external_id_key UNIQUE (integration_id, ad_external_id);


--
-- Name: ad_entities ad_entities_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_pkey PRIMARY KEY (id);


--
-- Name: ad_metrics ad_metrics_integration_id_entity_level_entity_external_id_d_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_integration_id_entity_level_entity_external_id_d_key UNIQUE (integration_id, entity_level, entity_external_id, date_start, date_stop);


--
-- Name: ad_metrics ad_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_pkey PRIMARY KEY (id);


--
-- Name: ad_sets ad_sets_integration_id_adset_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_integration_id_adset_external_id_key UNIQUE (integration_id, adset_external_id);


--
-- Name: ad_sets ad_sets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_pkey PRIMARY KEY (id);


--
-- Name: ai_actions ai_actions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_pkey PRIMARY KEY (action_id);


--
-- Name: ai_generated_campaigns ai_generated_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: analytics_integrations analytics_integrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.analytics_integrations
    ADD CONSTRAINT analytics_integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: anchors anchors_code_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_code_key UNIQUE (code);


--
-- Name: anchors anchors_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_pkey PRIMARY KEY (anchor_id);


--
-- Name: audio_templates audio_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_pkey PRIMARY KEY (atpl_id);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (log_id);


--
-- Name: bundle_offerings bundle_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_pkey PRIMARY KEY (bundle_id, offering_id);


--
-- Name: campaign_offerings campaign_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_pkey PRIMARY KEY (campaign_id, offering_id);


--
-- Name: campaign_performance_dashboard campaign_performance_dashboard_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.campaign_performance_dashboard
    ADD CONSTRAINT campaign_performance_dashboard_pkey PRIMARY KEY (dashboard_id);


--
-- Name: campaigns campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaigns
    ADD CONSTRAINT campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: cognitive_tracker_template cognitive_tracker_template_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_pkey PRIMARY KEY (tracker_id);


--
-- Name: cognitive_trends cognitive_trends_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_pkey PRIMARY KEY (trend_id);


--
-- Name: compliance_audits compliance_audits_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_pkey PRIMARY KEY (audit_id);


--
-- Name: compliance_rule_channels compliance_rule_channels_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_pkey PRIMARY KEY (rule_id, channel_id);


--
-- Name: compliance_rules compliance_rules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_code_key UNIQUE (code);


--
-- Name: compliance_rules compliance_rules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_pkey PRIMARY KEY (rule_id);


--
-- Name: content_items content_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_pkey PRIMARY KEY (item_id);


--
-- Name: content_plans content_plans_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_pkey PRIMARY KEY (plan_id);


--
-- Name: copy_components copy_components_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_pkey PRIMARY KEY (component_id);


--
-- Name: creative_assets creative_assets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_pkey PRIMARY KEY (asset_id);


--
-- Name: creative_briefs creative_briefs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_pkey PRIMARY KEY (brief_id);


--
-- Name: creative_contexts creative_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: data_feeds data_feeds_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_pkey PRIMARY KEY (feed_id);


--
-- Name: dataset_files dataset_files_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkey PRIMARY KEY (file_id);


--
-- Name: dataset_packages dataset_packages_code_version_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_code_version_key UNIQUE (code, version);


--
-- Name: dataset_packages dataset_packages_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_pkey PRIMARY KEY (pkg_id);


--
-- Name: experiment_variants experiment_variants_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_pkey PRIMARY KEY (exp_id, asset_id);


--
-- Name: experiments experiments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_pkey PRIMARY KEY (exp_id);


--
-- Name: export_bundle_items export_bundle_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_pkey PRIMARY KEY (bundle_id, asset_id);


--
-- Name: export_bundles export_bundles_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_pkey PRIMARY KEY (bundle_id);


--
-- Name: feed_items feed_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_pkey PRIMARY KEY (item_id);


--
-- Name: field_aliases field_aliases_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_pkey PRIMARY KEY (alias_slug);


--
-- Name: field_definitions field_definitions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_pkey PRIMARY KEY (field_id);


--
-- Name: field_definitions field_definitions_slug_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_slug_key UNIQUE (slug);


--
-- Name: field_values field_values_field_id_context_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_context_id_key UNIQUE (field_id, context_id);


--
-- Name: field_values field_values_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_pkey PRIMARY KEY (value_id);


--
-- Name: flow_steps flow_steps_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_pkey PRIMARY KEY (step_id);


--
-- Name: flows flows_name_version_org_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_name_version_org_id_key UNIQUE (name, version, org_id);


--
-- Name: flows flows_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_pkey PRIMARY KEY (flow_id);


--
-- Name: integrations integrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.integrations
    ADD CONSTRAINT integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: logs_migration logs_migration_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.logs_migration
    ADD CONSTRAINT logs_migration_pkey PRIMARY KEY (log_id);


--
-- Name: meta_documentation meta_documentation_meta_key_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_meta_key_key UNIQUE (meta_key);


--
-- Name: meta_documentation meta_documentation_pkey1; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_pkey1 PRIMARY KEY (doc_id);


--
-- Name: meta_field_dictionary meta_field_dictionary_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_field_dictionary
    ADD CONSTRAINT meta_field_dictionary_pkey PRIMARY KEY (id);


--
-- Name: meta_function_descriptions meta_function_descriptions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.meta_function_descriptions
    ADD CONSTRAINT meta_function_descriptions_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: modules modules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_code_key UNIQUE (code);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: naming_templates naming_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_pkey PRIMARY KEY (naming_id);


--
-- Name: naming_templates naming_templates_scope_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_scope_key UNIQUE (scope);


--
-- Name: offerings_full_details offerings_full_details_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_pkey PRIMARY KEY (detail_id);


--
-- Name: offerings offerings_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_org_id_name_key UNIQUE (org_id, name);


--
-- Name: offerings offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_pkey PRIMARY KEY (offering_id);


--
-- Name: ops_audit ops_audit_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ops_audit
    ADD CONSTRAINT ops_audit_pkey PRIMARY KEY (audit_id);


--
-- Name: ops_etl_log ops_etl_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ops_etl_log
    ADD CONSTRAINT ops_etl_log_pkey PRIMARY KEY (log_id);


--
-- Name: org_datasets org_datasets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkey PRIMARY KEY (org_id, pkg_id);


--
-- Name: org_markets org_markets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_pkey PRIMARY KEY (org_id, market_id);


--
-- Name: orgs orgs_name_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_name_key UNIQUE (name);


--
-- Name: orgs orgs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_pkey PRIMARY KEY (org_id);


--
-- Name: output_contracts output_contracts_code_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_code_key UNIQUE (code);


--
-- Name: output_contracts output_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_pkey PRIMARY KEY (contract_id);


--
-- Name: predictive_visual_engine predictive_visual_engine_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_pkey PRIMARY KEY (prediction_id);


--
-- Name: prompt_template_contracts prompt_template_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_pkey PRIMARY KEY (prompt_id, contract_id);


--
-- Name: prompt_template_presql prompt_template_presql_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_pkey PRIMARY KEY (prompt_id, snippet_id);


--
-- Name: prompt_template_required_fields prompt_template_required_fields_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_pkey PRIMARY KEY (prompt_id, field_id);


--
-- Name: prompt_templates prompt_templates_name_version_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_name_version_key UNIQUE (name, version);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (prompt_id);


--
-- Name: scene_library scene_library_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_pkey PRIMARY KEY (scene_id);


--
-- Name: segments segments_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_name_key UNIQUE (org_id, name);


--
-- Name: segments segments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_pkey PRIMARY KEY (segment_id);


--
-- Name: sessions_memory sessions_memory_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sessions_memory
    ADD CONSTRAINT sessions_memory_pkey PRIMARY KEY (session_id);


--
-- Name: social_account_metrics social_account_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_account_metrics
    ADD CONSTRAINT social_account_metrics_pkey PRIMARY KEY (integration_id, period_start, period_end);


--
-- Name: social_accounts social_accounts_account_id_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_id_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_integration_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_integration_id_key UNIQUE (integration_id);


--
-- Name: social_post_metrics social_post_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_post_metrics
    ADD CONSTRAINT social_post_metrics_pkey PRIMARY KEY (id);


--
-- Name: social_posts social_posts_integration_id_post_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_integration_id_post_external_id_key UNIQUE (integration_id, post_external_id);


--
-- Name: social_posts social_posts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_pkey PRIMARY KEY (id);


--
-- Name: sql_snippets sql_snippets_name_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_name_key UNIQUE (name);


--
-- Name: sql_snippets sql_snippets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_pkey PRIMARY KEY (snippet_id);


--
-- Name: sync_logs sync_logs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_pkey PRIMARY KEY (id);


--
-- Name: flow_steps uq_step; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT uq_step UNIQUE (flow_id, ord);


--
-- Name: users users_org_id_email_key; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_org_id_email_key UNIQUE (org_id, email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: value_contexts value_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: variation_policies variation_policies_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_pkey PRIMARY KEY (policy_id);


--
-- Name: video_scenes video_scenes_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_pkey PRIMARY KEY (scene_id);


--
-- Name: video_templates video_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_pkey PRIMARY KEY (vtpl_id);


--
-- Name: idx_ad_audiences_entity; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ad_audiences_entity ON cmis.ad_audiences USING btree (entity_level, entity_external_id);


--
-- Name: idx_ad_audiences_platform; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ad_audiences_platform ON cmis.ad_audiences USING btree (platform);


--
-- Name: idx_ad_metrics_date; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ad_metrics_date ON cmis.ad_metrics USING btree (date_start, date_stop);


--
-- Name: idx_ad_metrics_entity; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ad_metrics_entity ON cmis.ad_metrics USING btree (entity_level, entity_external_id);


--
-- Name: idx_ai_actions_org; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ai_actions_org ON cmis.ai_actions USING btree (org_id);


--
-- Name: idx_ai_actions_org_time; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_ai_actions_org_time ON cmis.ai_actions USING btree (org_id, created_at);


--
-- Name: idx_anchors_code; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_anchors_code ON cmis.anchors USING gist (code);


--
-- Name: idx_assets_campaign; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_assets_campaign ON cmis.creative_assets USING btree (campaign_id);


--
-- Name: idx_audit_log_ts; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_audit_log_ts ON cmis.audit_log USING btree (ts DESC);


--
-- Name: idx_campaign_offerings_cid; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_campaign_offerings_cid ON cmis.campaign_offerings USING btree (campaign_id);


--
-- Name: idx_campaign_offerings_oid; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_campaign_offerings_oid ON cmis.campaign_offerings USING btree (offering_id);


--
-- Name: idx_cc_channel; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_cc_channel ON cmis.copy_components USING btree (channel_id);


--
-- Name: idx_cc_content_trgm; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_cc_content_trgm ON cmis.copy_components USING gin (content public.gin_trgm_ops);


--
-- Name: idx_cc_industry; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_cc_industry ON cmis.copy_components USING btree (industry_id);


--
-- Name: idx_cc_market; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_cc_market ON cmis.copy_components USING btree (market_id);


--
-- Name: idx_cc_type; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_cc_type ON cmis.copy_components USING btree (type_code);


--
-- Name: idx_content_items_channel; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_content_items_channel ON cmis.content_items USING btree (channel_id);


--
-- Name: idx_content_items_context; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_content_items_context ON cmis.content_items USING btree (context_id);


--
-- Name: idx_content_items_example; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_content_items_example ON cmis.content_items USING btree (example_id);


--
-- Name: idx_content_items_format; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_content_items_format ON cmis.content_items USING btree (format_id);


--
-- Name: idx_content_items_plan; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_content_items_plan ON cmis.content_items USING btree (plan_id);


--
-- Name: idx_creative_assets_org; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_creative_assets_org ON cmis.creative_assets USING btree (org_id);


--
-- Name: idx_field_value_text; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_field_value_text ON cmis.field_values USING gin (((value)::text) public.gin_trgm_ops);


--
-- Name: idx_field_values_created; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_field_values_created ON cmis.field_values USING btree (created_at DESC);


--
-- Name: idx_field_values_json; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_field_values_json ON cmis.field_values USING gin (value jsonb_path_ops);


--
-- Name: idx_orgs_id; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_orgs_id ON cmis.orgs USING btree (org_id);


--
-- Name: idx_sessions_memory_user; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_sessions_memory_user ON cmis.sessions_memory USING btree (user_id);


--
-- Name: idx_users_org; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_users_org ON cmis.users USING btree (org_id);


--
-- Name: idx_values_context; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_values_context ON cmis.field_values USING btree (context_id);


--
-- Name: idx_values_field; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_values_field ON cmis.field_values USING btree (field_id);


--
-- Name: idx_video_scenes_asset; Type: INDEX; Schema: cmis; Owner: cmis
--

CREATE INDEX idx_video_scenes_asset ON cmis.video_scenes USING btree (asset_id);


--
-- Name: creative_briefs enforce_brief_completeness; Type: TRIGGER; Schema: cmis; Owner: cmis
--

CREATE TRIGGER enforce_brief_completeness BEFORE INSERT OR UPDATE ON cmis.creative_briefs FOR EACH ROW EXECUTE FUNCTION cmis.prevent_incomplete_briefs();


--
-- Name: ad_accounts ad_accounts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_audiences ad_audiences_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_campaigns ad_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_entities ad_entities_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_metrics ad_metrics_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_sets ad_sets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ai_actions ai_actions_audit_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_audit_id_fkey FOREIGN KEY (audit_id) REFERENCES cmis.audit_log(log_id);


--
-- Name: ai_generated_campaigns ai_generated_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: anchors anchors_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: audio_templates audio_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: audit_log audit_log_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: bundle_offerings bundle_offerings_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: bundle_offerings bundle_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: campaign_offerings campaign_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: cognitive_tracker_template cognitive_tracker_template_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: cognitive_trends cognitive_trends_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: compliance_audits compliance_audits_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: compliance_rule_channels compliance_rule_channels_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: content_items content_items_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: content_items content_items_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_items content_items_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: content_items content_items_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE CASCADE;


--
-- Name: content_plans content_plans_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: copy_components copy_components_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: copy_components copy_components_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: copy_components copy_components_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: creative_assets creative_assets_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: creative_assets creative_assets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_briefs creative_briefs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_contexts creative_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: data_feeds data_feeds_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: dataset_files dataset_files_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_exp_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_exp_id_fkey FOREIGN KEY (exp_id) REFERENCES cmis.experiments(exp_id) ON DELETE CASCADE;


--
-- Name: experiments experiments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.export_bundles(bundle_id) ON DELETE CASCADE;


--
-- Name: export_bundles export_bundles_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: feed_items feed_items_feed_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_feed_id_fkey FOREIGN KEY (feed_id) REFERENCES cmis.data_feeds(feed_id) ON DELETE CASCADE;


--
-- Name: field_aliases field_aliases_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: field_definitions field_definitions_guidance_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_guidance_anchor_fkey FOREIGN KEY (guidance_anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: field_definitions field_definitions_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: field_values field_values_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id) ON DELETE CASCADE;


--
-- Name: field_values field_values_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: content_items fk_content_item_asset; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT fk_content_item_asset FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE SET NULL;


--
-- Name: flow_steps flow_steps_flow_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_flow_id_fkey FOREIGN KEY (flow_id) REFERENCES cmis.flows(flow_id) ON DELETE CASCADE;


--
-- Name: flows flows_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: offerings_full_details offerings_full_details_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE CASCADE;


--
-- Name: offerings offerings_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.offerings
    ADD CONSTRAINT offerings_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: org_markets org_markets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: predictive_visual_engine predictive_visual_engine_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_contract_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_contract_id_fkey FOREIGN KEY (contract_id) REFERENCES cmis.output_contracts(contract_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_snippet_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_snippet_id_fkey FOREIGN KEY (snippet_id) REFERENCES cmis.sql_snippets(snippet_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_templates prompt_templates_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: scene_library scene_library_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_anchor_fkey FOREIGN KEY (anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: scene_library scene_library_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: segments segments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: social_accounts social_accounts_account_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: social_accounts social_accounts_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_posts social_posts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: sync_logs sync_logs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: users users_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings(offering_id) ON DELETE SET NULL;


--
-- Name: value_contexts value_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_segment_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_segment_id_fkey FOREIGN KEY (segment_id) REFERENCES cmis.segments(segment_id) ON DELETE SET NULL;


--
-- Name: variation_policies variation_policies_naming_ref_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_naming_ref_fkey FOREIGN KEY (naming_ref) REFERENCES cmis.naming_templates(naming_id);


--
-- Name: variation_policies variation_policies_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: video_scenes video_scenes_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: video_templates video_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: cmis
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: ad_accounts; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_audiences; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_audiences ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_campaigns; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_entities; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_entities ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_metrics; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_metrics ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_sets; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ad_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_actions; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.ai_actions ENABLE ROW LEVEL SECURITY;

--
-- Name: audit_log; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.audit_log ENABLE ROW LEVEL SECURITY;

--
-- Name: content_plans; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.content_plans ENABLE ROW LEVEL SECURITY;

--
-- Name: creative_assets; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.creative_assets ENABLE ROW LEVEL SECURITY;

--
-- Name: data_feeds; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.data_feeds ENABLE ROW LEVEL SECURITY;

--
-- Name: experiments; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.experiments ENABLE ROW LEVEL SECURITY;

--
-- Name: export_bundles; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.export_bundles ENABLE ROW LEVEL SECURITY;

--
-- Name: feed_items; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.feed_items ENABLE ROW LEVEL SECURITY;

--
-- Name: flows; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.flows ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_accounts org_isolation_ad_accounts; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_accounts ON cmis.ad_accounts USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_audiences org_isolation_ad_audiences; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_audiences ON cmis.ad_audiences USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_campaigns org_isolation_ad_campaigns; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_campaigns ON cmis.ad_campaigns USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_entities org_isolation_ad_entities; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_entities ON cmis.ad_entities USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_metrics org_isolation_ad_metrics; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_metrics ON cmis.ad_metrics USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ad_sets org_isolation_ad_sets; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ad_sets ON cmis.ad_sets USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: ai_actions org_isolation_ai_actions; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_ai_actions ON cmis.ai_actions USING ((org_id = (current_setting('app.current_org_id'::text))::uuid)) WITH CHECK ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: creative_assets org_isolation_assets; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_assets ON cmis.creative_assets FOR SELECT USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: audit_log org_isolation_audit_log; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_audit_log ON cmis.audit_log USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: content_plans org_isolation_content_plans; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_content_plans ON cmis.content_plans USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: creative_assets org_isolation_creative_assets; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_creative_assets ON cmis.creative_assets USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: data_feeds org_isolation_data_feeds; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_data_feeds ON cmis.data_feeds USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: experiments org_isolation_experiments; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_experiments ON cmis.experiments USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: export_bundles org_isolation_export_bundles; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_export_bundles ON cmis.export_bundles USING ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: feed_items org_isolation_feed_items; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_feed_items ON cmis.feed_items USING ((feed_id IN ( SELECT data_feeds.feed_id
   FROM cmis.data_feeds
  WHERE (data_feeds.org_id = (current_setting('app.current_org_id'::text))::uuid))));


--
-- Name: flows org_isolation_flows; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_flows ON cmis.flows USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: users org_isolation_users; Type: POLICY; Schema: cmis; Owner: cmis
--

CREATE POLICY org_isolation_users ON cmis.users USING ((org_id = (current_setting('app.current_org_id'::text))::uuid)) WITH CHECK ((org_id = (current_setting('app.current_org_id'::text))::uuid));


--
-- Name: users; Type: ROW SECURITY; Schema: cmis; Owner: cmis
--

ALTER TABLE cmis.users ENABLE ROW LEVEL SECURITY;

--
-- PostgreSQL database dump complete
--

\unrestrict ds4Y3MzJy0mPM6YugX9AV4y4jZbEVOUQfccEawGvGAPklKj3vatoKlitHbb8Wo7

