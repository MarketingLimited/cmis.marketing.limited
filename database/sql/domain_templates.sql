CREATE TABLE cmis.video_templates (
    vtpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    channel_id integer,
    format_id integer,
    name text NOT NULL,
    steps jsonb NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.video_scenes (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid NOT NULL,
    scene_number integer NOT NULL,
    duration_seconds integer,
    visual_prompt_en text,
    overlay_text_ar text,
    audio_instructions text,
    technical_specs jsonb,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.audio_templates (
    atpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    voice_hints jsonb,
    sfx_pack jsonb,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.scene_library (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    goal text,
    duration_sec integer,
    visual_spec jsonb,
    audio_spec jsonb,
    overlay_rules jsonb,
    anchor uuid,
    quality_score smallint,
    tags text[],
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT scene_library_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);

CREATE TABLE cmis.prompt_templates (
    prompt_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    task text NOT NULL,
    instructions text NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.prompt_template_contracts (
    prompt_id uuid NOT NULL,
    contract_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.prompt_template_presql (
    prompt_id uuid NOT NULL,
    snippet_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.prompt_template_required_fields (
    prompt_id uuid NOT NULL,
    field_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.output_contracts (
    contract_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    json_schema jsonb NOT NULL,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.naming_templates (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);

