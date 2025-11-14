CREATE TABLE cmis.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);

CREATE TABLE cmis.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);

CREATE TABLE cmis.cache_metadata (
    cache_name text NOT NULL,
    last_refreshed timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    refresh_count bigint DEFAULT 1,
    avg_refresh_time_ms numeric,
    last_refresh_duration_ms numeric,
    auto_refresh boolean DEFAULT true,
    metadata jsonb,
    hit_count bigint DEFAULT 0
);

CREATE TABLE cmis.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);

CREATE TABLE cmis.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

CREATE TABLE cmis.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);

CREATE TABLE cmis.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);

CREATE TABLE cmis.api_keys (
    key_id uuid DEFAULT gen_random_uuid() NOT NULL,
    service_name text NOT NULL,
    service_code text NOT NULL,
    api_key_encrypted bytea NOT NULL,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.session_context (
    session_id uuid NOT NULL,
    active_org_id uuid,
    switched_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
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

