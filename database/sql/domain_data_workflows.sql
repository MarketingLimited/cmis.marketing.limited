CREATE TABLE cmis.data_feeds (
    feed_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    kind text NOT NULL,
    source_meta jsonb,
    last_ingested timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT data_feeds_kind_check CHECK ((kind = ANY (ARRAY['price'::text, 'stock'::text, 'location'::text, 'catalog'::text])))
);

CREATE TABLE cmis.feed_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    feed_id uuid NOT NULL,
    sku text,
    payload jsonb NOT NULL,
    valid_from timestamp with time zone DEFAULT now(),
    valid_to timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.dataset_packages (
    pkg_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    version text NOT NULL,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.dataset_files (
    file_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    pkg_id uuid NOT NULL,
    filename text NOT NULL,
    checksum text,
    meta jsonb,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.org_datasets (
    org_id uuid NOT NULL,
    pkg_id uuid NOT NULL,
    enabled boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.org_markets (
    org_id uuid NOT NULL,
    market_id integer NOT NULL,
    is_default boolean DEFAULT false,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.flows (
    flow_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    description text,
    version text DEFAULT '2025.10.0'::text,
    tags text[],
    enabled boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);

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
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT flow_steps_type_check CHECK ((type = ANY (ARRAY['llm'::text, 'sql'::text, 'tool'::text, 'branch'::text, 'transform'::text, 'evaluate'::text])))
);

CREATE TABLE cmis.sql_snippets (
    snippet_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    sql text NOT NULL,
    description text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.reference_entities (
    ref_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    category text NOT NULL,
    code text NOT NULL,
    label text,
    description text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

