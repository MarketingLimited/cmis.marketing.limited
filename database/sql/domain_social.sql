CREATE TABLE cmis.social_posts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    caption text,
    media_url text,
    permalink text,
    media_type text,
    posted_at timestamp without time zone,
    metrics jsonb DEFAULT '{}'::jsonb,
    fetched_at timestamp without time zone DEFAULT now(),
    created_at timestamp without time zone DEFAULT now(),
    video_url text,
    thumbnail_url text,
    children_media jsonb,
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.scheduled_social_posts (
    id uuid NOT NULL,
    org_id uuid NOT NULL,
    user_id uuid,
    campaign_id uuid,
    platforms jsonb NOT NULL,
    content text NOT NULL,
    media jsonb,
    scheduled_at timestamp(0) without time zone,
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    published_at timestamp(0) without time zone,
    published_ids jsonb,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp with time zone
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

CREATE TABLE cmis.post_approvals (
    approval_id uuid NOT NULL,
    post_id uuid NOT NULL,
    requested_by uuid NOT NULL,
    assigned_to uuid,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    comments text,
    reviewed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_post_approvals_status CHECK (((status)::text = ANY (ARRAY[('pending'::character varying)::text, ('approved'::character varying)::text, ('rejected'::character varying)::text])))
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

CREATE TABLE cmis.social_post_metrics (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    social_post_id uuid NOT NULL,
    metric text NOT NULL,
    value numeric(20,4),
    fetched_at timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);

CREATE TABLE cmis.social_account_metrics (
    integration_id uuid NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    followers bigint,
    reach bigint,
    impressions bigint,
    profile_views bigint,
    deleted_at timestamp with time zone,
    provider text
);

