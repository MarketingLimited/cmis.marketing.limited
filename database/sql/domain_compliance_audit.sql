CREATE TABLE cmis.compliance_rules (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    description text NOT NULL,
    severity text NOT NULL,
    params jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT compliance_rules_severity_check CHECK ((severity = ANY (ARRAY['warn'::text, 'block'::text])))
);

CREATE TABLE cmis.compliance_rule_channels (
    rule_id uuid NOT NULL,
    channel_id integer NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.compliance_audits (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid,
    rule_id uuid NOT NULL,
    status text NOT NULL,
    owner text,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT compliance_audits_status_check CHECK ((status = ANY (ARRAY['pass'::text, 'fail'::text, 'waived'::text])))
);

CREATE TABLE cmis.audit_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    actor text,
    action text,
    target text,
    meta jsonb,
    ts timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);
CREATE TABLE operations.audit_log (
    id bigint NOT NULL,
    "timestamp" timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    user_id uuid,
    session_id text,
    username text,
    action character varying(50) NOT NULL,
    table_schema character varying(63) NOT NULL,
    table_name character varying(63) NOT NULL,
    record_id uuid,
    record_key text,
    old_values jsonb,
    new_values jsonb,
    changed_fields text[],
    query text,
    query_params text[],
    ip_address inet,
    user_agent text,
    application_name text,
    host_name text,
    metadata jsonb DEFAULT '{}'::jsonb,
    tags text[],
    execution_time_ms integer,
    rows_affected integer
);

CREATE TABLE cmis.ops_audit (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    operation_name text NOT NULL,
    status text NOT NULL,
    executed_at timestamp with time zone DEFAULT now(),
    details jsonb,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.ops_etl_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    integration_id uuid,
    status text,
    started_at timestamp with time zone DEFAULT now(),
    ended_at timestamp with time zone,
    rows_processed integer,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.security_context_audit (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    transaction_id bigint DEFAULT txid_current(),
    user_id uuid,
    org_id uuid,
    action text,
    success boolean,
    error_message text,
    context_version text,
    session_id text,
    ip_address inet,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
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

