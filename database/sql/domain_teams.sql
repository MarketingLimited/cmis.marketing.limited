CREATE TABLE cmis.team_invitations (
    invitation_id uuid NOT NULL,
    org_id uuid NOT NULL,
    invited_email character varying(255) NOT NULL,
    role_id uuid,
    invited_by uuid,
    status character varying(20) DEFAULT 'pending'::character varying,
    sent_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    accepted_at timestamp with time zone,
    expires_at timestamp with time zone
);

CREATE TABLE cmis.scheduled_reports (
    schedule_id uuid NOT NULL,
    report_type character varying(50) NOT NULL,
    entity_id uuid NOT NULL,
    frequency character varying(20) DEFAULT 'weekly'::character varying NOT NULL,
    format character varying(10) DEFAULT 'pdf'::character varying NOT NULL,
    delivery_method character varying(20) DEFAULT 'email'::character varying NOT NULL,
    recipients jsonb,
    config jsonb,
    is_active boolean DEFAULT true,
    last_run_at timestamp with time zone,
    next_run_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_scheduled_reports_frequency CHECK (((frequency)::text = ANY (ARRAY[('daily'::character varying)::text, ('weekly'::character varying)::text, ('monthly'::character varying)::text, ('quarterly'::character varying)::text, ('yearly'::character varying)::text])))
);

