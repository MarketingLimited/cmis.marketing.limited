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

CREATE TABLE cmis.social_accounts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    account_external_id text NOT NULL,
    username text,
    display_name text,
    profile_picture_url text,
    biography text,
    followers_count bigint,
    follows_count bigint,
    media_count bigint,
    website text,
    category text,
    fetched_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.team_account_access (
    access_id uuid NOT NULL,
    org_user_id uuid NOT NULL,
    social_account_id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);

