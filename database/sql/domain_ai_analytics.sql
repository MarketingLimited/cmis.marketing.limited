CREATE TABLE cmis.ai_models (
    model_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    engine text,
    version text,
    description text,
    created_at timestamp with time zone DEFAULT now(),
    model_name character varying(255),
    model_family character varying(255),
    status character varying(50),
    trained_at timestamp without time zone,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.ai_actions (
    action_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    prompt_used text,
    sql_executed text,
    result_summary text,
    confidence_score numeric(5,2),
    created_at timestamp with time zone DEFAULT now(),
    audit_id uuid,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.ai_generated_campaigns (
    campaign_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    ai_summary text,
    ai_design_guideline text,
    created_at timestamp with time zone DEFAULT now(),
    engine text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.analytics_integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    platform text NOT NULL,
    source_endpoint text NOT NULL,
    mapping jsonb NOT NULL,
    refresh_frequency text DEFAULT 'weekly'::text,
    last_synced_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

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
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.cognitive_trends (
    trend_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    factor_name text NOT NULL,
    trend_direction text,
    growth_rate double precision,
    trend_strength double precision,
    summary_insight text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT cognitive_trends_trend_direction_check CHECK ((trend_direction = ANY (ARRAY['up'::text, 'down'::text, 'stable'::text])))
);

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
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.performance_metrics (
    metric_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    output_id uuid,
    kpi text NOT NULL,
    observed numeric,
    target numeric,
    baseline numeric,
    observed_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT performance_score_range CHECK (((observed >= (0)::numeric) AND (observed <= (1)::numeric)))
);

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
    insights text,
    deleted_at timestamp with time zone,
    provider text
);

