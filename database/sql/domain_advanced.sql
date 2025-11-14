CREATE TABLE cmis.campaign_context_links (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    campaign_id uuid NOT NULL,
    context_id uuid NOT NULL,
    context_type character varying(50) NOT NULL,
    link_type character varying(50) DEFAULT 'primary'::character varying,
    link_strength numeric(3,2) DEFAULT 1.0,
    link_purpose text,
    link_notes text,
    effective_from date,
    effective_to date,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_by uuid,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_by uuid,
    metadata jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT campaign_context_links_link_strength_check CHECK (((link_strength >= (0)::numeric) AND (link_strength <= (1)::numeric))),
    CONSTRAINT campaign_links_strength_range CHECK (((link_strength >= (0)::numeric) AND (link_strength <= (1)::numeric))),
    CONSTRAINT valid_dates CHECK (((effective_from IS NULL) OR (effective_to IS NULL) OR (effective_from <= effective_to))),
    CONSTRAINT valid_link_type CHECK (((link_type)::text = ANY (ARRAY[('primary'::character varying)::text, ('secondary'::character varying)::text, ('reference'::character varying)::text, ('historical'::character varying)::text])))
);

CREATE TABLE cmis.variation_policies (
    policy_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    max_variations smallint DEFAULT 3,
    dco_enabled boolean DEFAULT true,
    naming_ref integer,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.audience_templates (
    template_id uuid NOT NULL,
    org_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    targeting_criteria jsonb DEFAULT '{}'::jsonb NOT NULL,
    platforms jsonb DEFAULT '["meta", "google"]'::jsonb NOT NULL,
    usage_count integer DEFAULT 0 NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_by uuid NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

