CREATE TABLE cmis.content_plans (
    plan_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    name text NOT NULL,
    timeframe_daterange daterange,
    strategy jsonb,
    created_at timestamp with time zone DEFAULT now(),
    brief_id uuid,
    creative_context_id uuid,
    deleted_at timestamp with time zone,
    provider text
);

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
    creative_context_id uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    org_id uuid,
    deleted_by uuid
);

CREATE TABLE cmis.creative_briefs (
    brief_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    brief_data jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.creative_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    creative_brief jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

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
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid,
    CONSTRAINT creative_assets_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'pending_review'::text, 'approved'::text, 'rejected'::text, 'archived'::text])))
);

CREATE TABLE cmis.creative_outputs (
    output_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    context_id uuid,
    type text NOT NULL,
    status text DEFAULT 'draft'::text,
    data jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT creative_outputs_quality_valid_json CHECK (((data ->> 'quality'::text) = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text, 'excellent'::text]))),
    CONSTRAINT creative_outputs_type_check CHECK ((type = ANY (ARRAY['asset'::text, 'copy'::text, 'content'::text])))
);

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
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT copy_components_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);

