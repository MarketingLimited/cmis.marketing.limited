CREATE TABLE cmis.ab_tests (
    ab_test_id uuid NOT NULL,
    ad_account_id uuid NOT NULL,
    entity_type character varying(20),
    entity_id uuid,
    test_name character varying(255) NOT NULL,
    test_type character varying(50) DEFAULT 'creative'::character varying NOT NULL,
    test_status character varying(20) DEFAULT 'draft'::character varying NOT NULL,
    hypothesis text,
    metric_to_optimize character varying(50) DEFAULT 'ctr'::character varying,
    budget_per_variation numeric(15,2),
    test_duration_days integer DEFAULT 7,
    min_sample_size integer DEFAULT 1000,
    confidence_level numeric(3,2) DEFAULT 0.95,
    winner_variation_id uuid,
    config jsonb,
    started_at timestamp with time zone,
    scheduled_end_at timestamp with time zone,
    completed_at timestamp with time zone,
    stop_reason text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cmis.ab_test_variations (
    variation_id uuid NOT NULL,
    ab_test_id uuid NOT NULL,
    variation_name character varying(255) NOT NULL,
    is_control boolean DEFAULT false,
    entity_id uuid,
    variation_config jsonb,
    traffic_allocation integer DEFAULT 50,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cmis.experiments (
    exp_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    channel_id integer,
    framework text,
    hypothesis text,
    status text DEFAULT 'draft'::text,
    created_at timestamp with time zone DEFAULT now(),
    campaign_id uuid,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.experiment_variants (
    exp_id uuid NOT NULL,
    asset_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

