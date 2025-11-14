CREATE TABLE cmis.field_definitions (
    field_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    slug text NOT NULL,
    data_type text NOT NULL,
    is_list boolean DEFAULT false,
    description text,
    enum_options text[],
    required_default boolean DEFAULT false,
    guidance_anchor uuid,
    validations jsonb,
    module_scope text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT field_definitions_data_type_check CHECK ((data_type = ANY (ARRAY['text'::text, 'markdown'::text, 'number'::text, 'bool'::text, 'json'::text, 'enum'::text, 'vector'::text]))),
    CONSTRAINT field_definitions_module_scope_check CHECK ((module_scope = ANY (ARRAY['market_intel'::text, 'persuasion'::text, 'frameworks'::text, 'adaptation'::text, 'testing'::text, 'compliance'::text, 'video'::text, 'content'::text])))
);

CREATE TABLE cmis.field_values (
    value_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    field_id uuid NOT NULL,
    context_id uuid NOT NULL,
    value jsonb NOT NULL,
    source text NOT NULL,
    provider_ref text,
    justification text,
    confidence numeric,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT field_values_confidence_check CHECK (((confidence >= (0)::numeric) AND (confidence <= (1)::numeric))),
    CONSTRAINT field_values_source_check CHECK ((source = ANY (ARRAY['manual'::text, 'assumption'::text, 'derived'::text, 'imported'::text, 'model'::text])))
);

CREATE TABLE cmis.field_aliases (
    alias_slug text NOT NULL,
    field_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
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
    usage_context text,
    unified_alias text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.meta_function_descriptions (
    id integer NOT NULL,
    routine_schema text NOT NULL,
    routine_name text NOT NULL,
    description text,
    cognitive_category text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.modules (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.anchors (
    anchor_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    code public.ltree NOT NULL,
    title text,
    file_ref text,
    section text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.required_fields_cache (
    module_scope text NOT NULL,
    required_fields text[],
    last_updated timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);

