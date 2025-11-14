CREATE TABLE cmis.campaigns (
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
    updated_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    creative_id uuid,
    value_id uuid,
    created_by uuid,
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid,
    description text,
    CONSTRAINT campaigns_status_valid CHECK ((status = ANY (ARRAY['draft'::text, 'active'::text, 'paused'::text, 'completed'::text, 'archived'::text])))
);

CREATE TABLE cmis.campaign_offerings (
    campaign_id uuid NOT NULL,
    offering_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    type text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT contexts_type_check CHECK ((type = ANY (ARRAY['value'::text, 'creative'::text, 'experiment'::text])))
);

CREATE TABLE cmis.contexts_base (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    context_type character varying(50) NOT NULL,
    name character varying(255),
    org_id uuid,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT contexts_base_context_type_check CHECK (((context_type)::text = ANY (ARRAY[('creative'::character varying)::text, ('value'::character varying)::text, ('offering'::character varying)::text])))
);

CREATE TABLE cmis.contexts_creative (
    context_id uuid NOT NULL,
    creative_brief text,
    brand_guidelines jsonb,
    visual_style jsonb,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.contexts_value (
    context_id uuid NOT NULL,
    value_proposition text,
    target_audience jsonb,
    key_messages text[],
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.contexts_offering (
    context_id uuid NOT NULL,
    offering_details text,
    pricing_info jsonb,
    features jsonb,
    deleted_at timestamp with time zone,
    provider text
);

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
    context_fingerprint text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.segments (
    segment_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    persona jsonb,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);

