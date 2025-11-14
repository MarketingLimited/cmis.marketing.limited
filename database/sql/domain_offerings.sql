CREATE TABLE cmis.offerings_full_details (
    detail_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    offering_id uuid,
    full_description text NOT NULL,
    pricing_notes text,
    target_segment text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.offerings_old (
    offering_id uuid DEFAULT public.gen_random_uuid() CONSTRAINT offerings_offering_id_not_null NOT NULL,
    org_id uuid CONSTRAINT offerings_org_id_not_null NOT NULL,
    kind text CONSTRAINT offerings_kind_not_null NOT NULL,
    name text CONSTRAINT offerings_name_not_null NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT offerings_kind_check CHECK ((kind = ANY (ARRAY['product'::text, 'service'::text, 'bundle'::text])))
);

CREATE TABLE cmis.bundle_offerings (
    bundle_id uuid NOT NULL,
    offering_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.export_bundles (
    bundle_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.export_bundle_items (
    bundle_id uuid NOT NULL,
    asset_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);

