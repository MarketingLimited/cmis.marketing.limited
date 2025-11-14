CREATE TABLE cmis.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp with time zone
);

CREATE TABLE cmis.orgs (
    org_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name public.citext NOT NULL,
    default_locale text DEFAULT 'ar-BH'::text,
    currency text DEFAULT 'BHD'::text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.roles (
    role_id uuid DEFAULT gen_random_uuid() NOT NULL,
    org_id uuid,
    role_name text NOT NULL,
    role_code text NOT NULL,
    description text,
    is_system boolean DEFAULT false,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    created_by uuid,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.permissions (
    permission_id uuid DEFAULT gen_random_uuid() NOT NULL,
    permission_code text NOT NULL,
    permission_name text NOT NULL,
    category text NOT NULL,
    description text,
    is_dangerous boolean DEFAULT false,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);

CREATE TABLE cmis.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);

CREATE TABLE cmis.user_orgs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id bigint NOT NULL,
    org_id uuid NOT NULL,
    role_id uuid NOT NULL,
    is_active boolean DEFAULT true,
    joined_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    invited_by uuid,
    last_accessed timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.user_permissions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    org_id uuid NOT NULL,
    permission_id uuid NOT NULL,
    is_granted boolean DEFAULT true,
    granted_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid,
    expires_at timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.role_permissions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    role_id uuid NOT NULL,
    permission_id uuid NOT NULL,
    granted_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid,
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
    is_active boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);

