CREATE TABLE public.awareness_stages (
    stage text NOT NULL
);
CREATE TABLE cmis.bundle_offerings (
    bundle_id uuid NOT NULL,
    offering_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE cmis.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);
CREATE TABLE cmis.cache_locks (
    key character varying(255) NOT NULL,
--
CREATE TABLE public.channel_formats (
    format_id integer NOT NULL,
    channel_id integer NOT NULL,
    code text NOT NULL,
    ratio text,
    length_hint text
);
CREATE TABLE public.channels (
    channel_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    constraints jsonb
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
CREATE TABLE public.component_types (
    type_code text NOT NULL
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
CREATE TABLE cmis.content_plans (
--
CREATE TABLE public.frameworks (
    framework_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    framework_name text NOT NULL,
    framework_type text,
    description text,
    created_at timestamp with time zone DEFAULT now()
);
CREATE TABLE public.funnel_stages (
    stage text NOT NULL
);
CREATE TABLE cmis.inbox_items (
    item_id uuid NOT NULL,
    org_id uuid NOT NULL,
    social_account_id uuid NOT NULL,
    item_type character varying(50) NOT NULL,
    platform character varying(50) NOT NULL,
    external_id character varying(255),
    content text NOT NULL,
    sender_name character varying(255) NOT NULL,
    sender_id character varying(255),
    sender_avatar_url character varying(500),
    needs_reply boolean DEFAULT true NOT NULL,
    assigned_to uuid,
    status character varying(20) DEFAULT 'unread'::character varying NOT NULL,
    reply_content text,
    replied_at timestamp(0) without time zone,
    sentiment character varying(20),
    sentiment_score numeric(3,2),
    platform_created_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);
CREATE TABLE public.industries (
    industry_id integer NOT NULL,
    name text NOT NULL
);
CREATE TABLE cmis.integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    platform text,
    account_id text,
    access_token text,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    business_id text,
    username text,
    created_by uuid,
    updated_by uuid,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE cmis.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
--
CREATE TABLE public.kpis (
    kpi text NOT NULL,
    description text
);
CREATE TABLE cmis.logs_migration (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    phase text NOT NULL,
    status text NOT NULL,
    executed_at timestamp without time zone DEFAULT now(),
    details jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE public.marketing_objectives (
    objective text NOT NULL,
    display_name text,
    category text,
    description text,
    CONSTRAINT marketing_objectives_category_check CHECK ((category = ANY (ARRAY['awareness'::text, 'understanding'::text, 'emotion'::text, 'trust'::text, 'conversion'::text]))),
    CONSTRAINT marketing_objectives_objective_check CHECK ((objective ~ '^[a-zA-Z0-9_]+$'::text))
);
CREATE TABLE public.markets (
    market_id integer NOT NULL,
    market_name text NOT NULL,
    language_code text NOT NULL,
    currency_code text NOT NULL,
    text_direction text DEFAULT 'RTL'::text
);
CREATE TABLE cmis.meta_documentation (
    doc_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    meta_key text NOT NULL,
    meta_value text NOT NULL,
    updated_by text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE cmis.meta_field_dictionary (
    id integer NOT NULL,
    field_name text NOT NULL,
    semantic_meaning text,
--
CREATE TABLE public.proof_layers (
    level text NOT NULL
);
CREATE TABLE cmis.publishing_queues (
    queue_id uuid NOT NULL,
    org_id uuid NOT NULL,
    social_account_id uuid NOT NULL,
    weekdays_enabled character varying(7) DEFAULT '1111111'::character varying NOT NULL,
    time_slots jsonb DEFAULT '[]'::jsonb NOT NULL,
    timezone character varying(50) DEFAULT 'UTC'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);
CREATE TABLE cmis.reference_entities (
    ref_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    category text NOT NULL,
--
CREATE TABLE public.strategies (
    strategy text NOT NULL
);
CREATE TABLE cmis.sync_logs (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid,
    platform text,
    synced_at timestamp without time zone DEFAULT now(),
    status text,
    items integer DEFAULT 0,
    level_counts jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE cmis.user_sessions (
    session_id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    session_token text NOT NULL,
    ip_address inet,
    user_agent text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    last_activity timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp with time zone DEFAULT (CURRENT_TIMESTAMP + '24:00:00'::interval) NOT NULL,
--
CREATE TABLE public.tones (
    tone text NOT NULL
);
CREATE TABLE cmis.user_activities (
    activity_id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    org_id uuid NOT NULL,
    session_id uuid,
    action text NOT NULL,
    entity_type text,
    entity_id uuid,
    details jsonb,
    ip_address inet,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);
CREATE TABLE cmis.user_orgs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id bigint NOT NULL,
    org_id uuid NOT NULL,
    role_id uuid NOT NULL,
    is_active boolean DEFAULT true,
    joined_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
--
CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value jsonb NOT NULL,
    expiration integer
);
CREATE TABLE public.cmis_access_control (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    resource_type text,
    resource_id uuid,
    actor text,
    permission text,
    granted_at timestamp with time zone DEFAULT now(),
    CONSTRAINT cmis_access_control_permission_check CHECK ((permission = ANY (ARRAY['read'::text, 'write'::text, 'execute'::text, 'admin'::text])))
);
CREATE TABLE public.cmis_system_health (
    check_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    metric_name text,
    metric_value numeric,
    threshold numeric,
    status text,
    checked_at timestamp with time zone DEFAULT now(),
    CONSTRAINT cmis_system_health_status_check CHECK ((status = ANY (ARRAY['healthy'::text, 'warning'::text, 'critical'::text])))
);
CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload jsonb NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);
CREATE TABLE public.migration_log (
    id integer NOT NULL,
    phase text NOT NULL,
    started_at timestamp with time zone DEFAULT now(),
    completed_at timestamp with time zone,
    status text DEFAULT 'pending'::text,
    notes text
);
CREATE TABLE public.modules_old (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);
CREATE TABLE public.naming_templates_old (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);
CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);
CREATE TABLE public.view_definitions_backup (
    viewname text NOT NULL,
    depends_on_refactored boolean DEFAULT false,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);
CREATE TABLE public.visual_kpis (
    kpi_id integer NOT NULL,
    name text NOT NULL,
    metric_type text,
    unit text,
    ideal_value text,
    description text,
    CONSTRAINT visual_kpis_metric_type_check CHECK ((metric_type = ANY (ARRAY['attention'::text, 'comprehension'::text, 'emotion'::text, 'trust'::text])))
);
CREATE TABLE public.visual_principles (
    principle_id integer NOT NULL,
    name text NOT NULL,
    category text,
    description text,
    recommended_use text,
    CONSTRAINT visual_principles_category_check CHECK ((category = ANY (ARRAY['composition'::text, 'symbolism'::text, 'typography'::text, 'emotion'::text, 'speed'::text, 'clarity'::text])))
);
CREATE TABLE public.visual_recommendations (
    recommendation_id integer NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    rationale text,
    suggested_action text
);
