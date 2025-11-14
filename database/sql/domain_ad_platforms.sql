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
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

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
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);

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
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);

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
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);

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
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

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
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT ad_audiences_entity_level_check CHECK ((entity_level = ANY (ARRAY['campaign'::text, 'adset'::text, 'adgroup'::text])))
);

CREATE TABLE cmis.ad_variants (
    variant_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    variant_type character varying(50) NOT NULL,
    variant_name character varying(100) NOT NULL,
    variant_data jsonb NOT NULL,
    budget_allocation numeric(5,2) DEFAULT 33.33 NOT NULL,
    actual_spend numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    impressions integer DEFAULT 0 NOT NULL,
    clicks integer DEFAULT 0 NOT NULL,
    conversions integer DEFAULT 0 NOT NULL,
    ctr numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    conversion_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_winner boolean DEFAULT false NOT NULL,
    declared_winner_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

