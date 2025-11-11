-- ============================================================================
-- CMIS Database Refactoring Script v2.2 COMPLETE
-- Full Production-Ready Version with View-to-Table Conversion
-- Total Lines: 1800+ 
-- Date: November 2024
-- ============================================================================

\timing on
\echo 'CMIS Database Refactoring v2.2 - COMPLETE PRODUCTION VERSION'
\echo 'Starting at: ' `date`

BEGIN;

-- ============================================================================
-- PHASE 0: Pre-Flight Checks & Preparations
-- ============================================================================
\echo '========================================='
\echo 'PHASE 0: Pre-Flight Checks & Preparations'
\echo '========================================='

-- Create operations schema if not exists
CREATE SCHEMA IF NOT EXISTS operations;

-- Create migration tracking table
DROP TABLE IF EXISTS operations.migrations CASCADE;
CREATE TABLE operations.migrations (
    migration_id SERIAL PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    phase VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    duration_seconds INTEGER GENERATED ALWAYS AS 
        (EXTRACT(EPOCH FROM (completed_at - started_at))::INTEGER) STORED,
    affected_objects TEXT[],
    error_message TEXT,
    rollback_sql TEXT
);

-- Create indexes for better tracking
CREATE INDEX idx_migrations_status ON operations.migrations(status);
CREATE INDEX idx_migrations_phase ON operations.migrations(phase);
CREATE INDEX idx_migrations_started ON operations.migrations(started_at);

-- Log migration start
INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_0_PREFLIGHT', 'RUNNING');

-- Security check for unsafe extensions
DO $$
DECLARE
    unsafe_ext RECORD;
    warning_msg TEXT := '';
BEGIN
    FOR unsafe_ext IN 
        SELECT extname 
        FROM pg_extension 
        WHERE extname IN ('plpython3u', 'plperlu', 'pltclu')
    LOOP
        warning_msg := warning_msg || 'WARNING: ' || unsafe_ext.extname || ' extension is installed! ';
    END LOOP;
    
    IF warning_msg != '' THEN
        RAISE WARNING 'SECURITY ALERT: %', warning_msg;
        RAISE WARNING 'Consider disabling or restricting access before production deployment';
    END IF;
END $$;

-- Create temporary tables for migration tracking
CREATE TEMP TABLE view_definitions_backup (
    schema_name TEXT,
    view_name TEXT,
    view_definition TEXT,
    view_owner TEXT,
    depends_on_refactored BOOLEAN DEFAULT false
);

-- Save all view definitions before we start
INSERT INTO view_definitions_backup (schema_name, view_name, view_definition, view_owner)
SELECT 
    schemaname,
    viewname,
    definition,
    viewowner
FROM pg_views
WHERE schemaname = 'cmis';

-- Mark views that depend on cmis_refactored
UPDATE view_definitions_backup
SET depends_on_refactored = true
WHERE view_definition ILIKE '%cmis_refactored%';

-- Check current state of campaigns and integrations
DO $$
DECLARE
    obj_type TEXT;
    obj_count INTEGER;
BEGIN
    -- Check campaigns
    SELECT 
        CASE 
            WHEN EXISTS (SELECT 1 FROM pg_views WHERE schemaname = 'cmis' AND viewname = 'campaigns') THEN 'VIEW'
            WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'campaigns') THEN 'TABLE'
            ELSE 'NOT FOUND'
        END INTO obj_type;
    
    RAISE NOTICE 'cmis.campaigns is currently: %', obj_type;
    
    IF obj_type = 'VIEW' THEN
        UPDATE operations.migrations 
        SET affected_objects = array_append(affected_objects, 'campaigns: VIEW -> needs conversion')
        WHERE phase = 'PHASE_0_PREFLIGHT' AND status = 'RUNNING';
    END IF;
    
    -- Check integrations
    SELECT 
        CASE 
            WHEN EXISTS (SELECT 1 FROM pg_views WHERE schemaname = 'cmis' AND viewname = 'integrations') THEN 'VIEW'
            WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'integrations') THEN 'TABLE'
            ELSE 'NOT FOUND'
        END INTO obj_type;
    
    RAISE NOTICE 'cmis.integrations is currently: %', obj_type;
    
    IF obj_type = 'VIEW' THEN
        UPDATE operations.migrations 
        SET affected_objects = array_append(affected_objects, 'integrations: VIEW -> needs conversion')
        WHERE phase = 'PHASE_0_PREFLIGHT' AND status = 'RUNNING';
    END IF;
    
    -- Count total objects in cmis_refactored
    SELECT COUNT(*) INTO obj_count
    FROM pg_class c
    JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'cmis_refactored';
    
    RAISE NOTICE 'Total objects in cmis_refactored: %', obj_count;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'cmis_refactored objects: ' || obj_count::text)
    WHERE phase = 'PHASE_0_PREFLIGHT' AND status = 'RUNNING';
END $$;

-- Update phase 0 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_0_PREFLIGHT' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 1: Convert Views to Tables (Critical for v2.2)
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 1: Converting Views to Tables'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_1_VIEW_CONVERSION', 'RUNNING');

-- Drop dependent views first
DO $$
DECLARE
    v_rec RECORD;
    drop_count INTEGER := 0;
BEGIN
    RAISE NOTICE 'Dropping views that depend on cmis_refactored...';
    
    -- Drop views in reverse dependency order
    FOR v_rec IN 
        SELECT viewname 
        FROM view_definitions_backup
        WHERE depends_on_refactored = true
        ORDER BY 
            CASE viewname 
                WHEN 'campaigns' THEN 1
                WHEN 'integrations' THEN 2
                ELSE 3
            END
    LOOP
        EXECUTE format('DROP VIEW IF EXISTS cmis.%I CASCADE', v_rec.viewname);
        drop_count := drop_count + 1;
        RAISE NOTICE 'Dropped view: cmis.%', v_rec.viewname;
    END LOOP;
    
    RAISE NOTICE 'Total views dropped: %', drop_count;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'views dropped: ' || drop_count::text)
    WHERE phase = 'PHASE_1_VIEW_CONVERSION' AND status = 'RUNNING';
END $$;

-- Move tables from cmis_refactored to cmis
DO $$
DECLARE
    tbl_rec RECORD;
    move_count INTEGER := 0;
BEGIN
    RAISE NOTICE 'Moving tables from cmis_refactored to cmis...';
    
    FOR tbl_rec IN 
        SELECT tablename 
        FROM pg_tables 
        WHERE schemaname = 'cmis_refactored'
    LOOP
        -- Check if table already exists in cmis
        IF NOT EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = tbl_rec.tablename) THEN
            EXECUTE format('ALTER TABLE cmis_refactored.%I SET SCHEMA cmis', tbl_rec.tablename);
            move_count := move_count + 1;
            RAISE NOTICE 'Moved table: % to cmis', tbl_rec.tablename;
        ELSE
            RAISE NOTICE 'Table % already exists in cmis, skipping', tbl_rec.tablename;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Total tables moved: %', move_count;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'tables moved: ' || move_count::text)
    WHERE phase = 'PHASE_1_VIEW_CONVERSION' AND status = 'RUNNING';
END $$;

-- Move sequences
DO $$
DECLARE
    seq_rec RECORD;
    move_count INTEGER := 0;
BEGIN
    FOR seq_rec IN 
        SELECT sequence_name 
        FROM information_schema.sequences 
        WHERE sequence_schema = 'cmis_refactored'
    LOOP
        IF NOT EXISTS (
            SELECT 1 FROM information_schema.sequences 
            WHERE sequence_schema = 'cmis' AND sequence_name = seq_rec.sequence_name
        ) THEN
            EXECUTE format('ALTER SEQUENCE cmis_refactored.%I SET SCHEMA cmis', seq_rec.sequence_name);
            move_count := move_count + 1;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Moved % sequences', move_count;
END $$;

-- Now add missing columns to the moved tables
DO $$
BEGIN
    -- Add columns to campaigns table (now it's a table, not a view)
    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'campaigns') THEN
        ALTER TABLE cmis.campaigns 
            ADD COLUMN IF NOT EXISTS context_id UUID,
            ADD COLUMN IF NOT EXISTS creative_id UUID,
            ADD COLUMN IF NOT EXISTS value_id UUID,
            ADD COLUMN IF NOT EXISTS org_id UUID,
            ADD COLUMN IF NOT EXISTS created_by UUID,
            ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
        
        RAISE NOTICE 'Added missing columns to campaigns table';
    END IF;
    
    -- Add columns to integrations table
    IF EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'integrations') THEN
        ALTER TABLE cmis.integrations 
            ADD COLUMN IF NOT EXISTS created_by UUID,
            ADD COLUMN IF NOT EXISTS updated_by UUID,
            ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
        
        RAISE NOTICE 'Added missing columns to integrations table';
    END IF;
END $$;

-- Update phase 1 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_1_VIEW_CONVERSION' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 2: Create Unified Contexts Table (from v2.1.1)
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 2: Creating Unified Contexts Table'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_2_UNIFIED_CONTEXTS', 'RUNNING');

-- Drop existing table if exists (for idempotency)
DROP TABLE IF EXISTS cmis.contexts_unified CASCADE;

-- Create the unified contexts table with all fields from all context types
CREATE TABLE cmis.contexts_unified (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    context_type VARCHAR(50) NOT NULL CHECK (context_type IN ('creative', 'value', 'offering')),
    
    -- Common fields from all context types
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    
    -- Creative context specific fields
    creative_brief TEXT,
    brand_guidelines JSONB,
    visual_style JSONB,
    tone_of_voice TEXT,
    target_platforms TEXT[],
    creative_assets JSONB,
    
    -- Value context specific fields  
    value_proposition TEXT,
    target_audience JSONB,
    key_messages TEXT[],
    pain_points TEXT[],
    benefits TEXT[],
    differentiators TEXT[],
    
    -- Offering context specific fields
    offering_details JSONB,
    pricing_info JSONB,
    availability JSONB,
    features TEXT[],
    specifications JSONB,
    terms_conditions TEXT,
    
    -- Relationship fields
    parent_context_id UUID REFERENCES cmis.contexts_unified(id),
    related_contexts UUID[],
    
    -- Metadata and tracking
    org_id UUID,
    created_by UUID,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP,
    version INTEGER DEFAULT 1,
    metadata JSONB DEFAULT '{}',
    
    -- Search and categorization
    tags TEXT[],
    categories TEXT[],
    keywords TEXT[],
    search_vector tsvector GENERATED ALWAYS AS (
        setweight(to_tsvector('english', coalesce(name, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(description, '')), 'B') ||
        setweight(to_tsvector('english', coalesce(creative_brief, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(value_proposition, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(array_to_string(tags, ' '), '')), 'D')
    ) STORED,
    
    -- Constraints
    CONSTRAINT valid_status CHECK (status IN ('active', 'inactive', 'draft', 'archived')),
    CONSTRAINT valid_dates CHECK (created_at <= updated_at),
    CONSTRAINT valid_deletion CHECK (deleted_at IS NULL OR deleted_at >= created_at)
);

-- Create indexes for performance
CREATE INDEX idx_contexts_unified_type ON cmis.contexts_unified(context_type);
CREATE INDEX idx_contexts_unified_status ON cmis.contexts_unified(status);
CREATE INDEX idx_contexts_unified_org ON cmis.contexts_unified(org_id);
CREATE INDEX idx_contexts_unified_created_by ON cmis.contexts_unified(created_by);
CREATE INDEX idx_contexts_unified_created_at ON cmis.contexts_unified(created_at);
CREATE INDEX idx_contexts_unified_updated_at ON cmis.contexts_unified(updated_at);
CREATE INDEX idx_contexts_unified_deleted_at ON cmis.contexts_unified(deleted_at) WHERE deleted_at IS NOT NULL;
CREATE INDEX idx_contexts_unified_parent ON cmis.contexts_unified(parent_context_id) WHERE parent_context_id IS NOT NULL;
CREATE INDEX idx_contexts_unified_search ON cmis.contexts_unified USING gin(search_vector);
CREATE INDEX idx_contexts_unified_tags ON cmis.contexts_unified USING gin(tags);
CREATE INDEX idx_contexts_unified_categories ON cmis.contexts_unified USING gin(categories);
CREATE INDEX idx_contexts_unified_keywords ON cmis.contexts_unified USING gin(keywords);
CREATE INDEX idx_contexts_unified_metadata ON cmis.contexts_unified USING gin(metadata);
CREATE INDEX idx_contexts_unified_related ON cmis.contexts_unified USING gin(related_contexts);

-- Add table comment
COMMENT ON TABLE cmis.contexts_unified IS 'Unified table for all context types (creative, value, offering) with full field coverage';
COMMENT ON COLUMN cmis.contexts_unified.context_type IS 'Type of context: creative, value, or offering';
COMMENT ON COLUMN cmis.contexts_unified.search_vector IS 'Full-text search vector for fast searching';
COMMENT ON COLUMN cmis.contexts_unified.version IS 'Version number for optimistic locking';
COMMENT ON COLUMN cmis.contexts_unified.deleted_at IS 'Soft delete timestamp';

-- Create a function to update the updated_at timestamp
CREATE OR REPLACE FUNCTION cmis.update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    NEW.version = OLD.version + 1;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for automatic updated_at
CREATE TRIGGER update_contexts_unified_updated_at
    BEFORE UPDATE ON cmis.contexts_unified
    FOR EACH ROW
    EXECUTE FUNCTION cmis.update_updated_at_column();

-- Migrate data from existing context tables
DO $$
DECLARE
    migrated_count INTEGER := 0;
    total_migrated INTEGER := 0;
BEGIN
    -- Migrate creative contexts if table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'creative_contexts') THEN
        INSERT INTO cmis.contexts_unified (
            id, context_type, name, description, creative_brief, 
            brand_guidelines, visual_style, tone_of_voice,
            target_platforms, creative_assets, org_id, created_by, 
            created_at, updated_at, metadata, tags, categories,
            keywords, status
        )
        SELECT 
            COALESCE(id, gen_random_uuid()),
            'creative',
            COALESCE(name, 'Creative Context ' || COALESCE(id::text, 'Unknown')),
            description,
            creative_brief,
            CASE 
                WHEN brand_guidelines IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(brand_guidelines::jsonb) = 'object' THEN brand_guidelines::jsonb
                        ELSE jsonb_build_object('data', brand_guidelines)
                    END
                ELSE '{}'::jsonb
            END,
            CASE 
                WHEN visual_style IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(visual_style::jsonb) = 'object' THEN visual_style::jsonb
                        ELSE jsonb_build_object('data', visual_style)
                    END
                ELSE '{}'::jsonb
            END,
            tone_of_voice,
            target_platforms,
            CASE 
                WHEN creative_assets IS NOT NULL THEN creative_assets::jsonb
                ELSE '{}'::jsonb
            END,
            org_id,
            created_by,
            COALESCE(created_at, CURRENT_TIMESTAMP),
            COALESCE(updated_at, CURRENT_TIMESTAMP),
            COALESCE(metadata, '{}'::jsonb),
            COALESCE(tags, '{}'),
            COALESCE(categories, '{}'),
            COALESCE(keywords, '{}'),
            COALESCE(status, 'active')
        FROM cmis.creative_contexts
        ON CONFLICT (id) DO NOTHING;
        
        GET DIAGNOSTICS migrated_count = ROW_COUNT;
        total_migrated := total_migrated + migrated_count;
        RAISE NOTICE 'Migrated % creative contexts', migrated_count;
    END IF;
    
    -- Migrate value contexts if table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'value_contexts') THEN
        
        -- First add missing columns if they don't exist
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                      WHERE table_schema = 'cmis' AND table_name = 'value_contexts' 
                      AND column_name = 'target_audience') THEN
            ALTER TABLE cmis.value_contexts ADD COLUMN target_audience TEXT;
        END IF;
        
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                      WHERE table_schema = 'cmis' AND table_name = 'value_contexts' 
                      AND column_name = 'created_by') THEN
            ALTER TABLE cmis.value_contexts ADD COLUMN created_by UUID;
        END IF;
        
        INSERT INTO cmis.contexts_unified (
            id, context_type, name, description, value_proposition,
            target_audience, key_messages, pain_points, benefits,
            differentiators, org_id, created_by, created_at, 
            updated_at, metadata, tags, categories, keywords, status
        )
        SELECT 
            COALESCE(id, gen_random_uuid()),
            'value',
            COALESCE(name, 'Value Context ' || COALESCE(id::text, 'Unknown')),
            description,
            value_proposition,
            CASE 
                WHEN target_audience IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(target_audience::jsonb) = 'object' THEN target_audience::jsonb
                        ELSE jsonb_build_object('data', target_audience)
                    END
                ELSE '{}'::jsonb
            END,
            COALESCE(key_messages, '{}'),
            COALESCE(pain_points, '{}'),
            COALESCE(benefits, '{}'),
            COALESCE(differentiators, '{}'),
            org_id,
            created_by,
            COALESCE(created_at, CURRENT_TIMESTAMP),
            COALESCE(updated_at, CURRENT_TIMESTAMP),
            COALESCE(metadata, '{}'::jsonb),
            COALESCE(tags, '{}'),
            COALESCE(categories, '{}'),
            COALESCE(keywords, '{}'),
            COALESCE(status, 'active')
        FROM cmis.value_contexts
        ON CONFLICT (id) DO NOTHING;
        
        GET DIAGNOSTICS migrated_count = ROW_COUNT;
        total_migrated := total_migrated + migrated_count;
        RAISE NOTICE 'Migrated % value contexts', migrated_count;
    END IF;
    
    -- Migrate offerings if table exists
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'offerings') THEN
        INSERT INTO cmis.contexts_unified (
            id, context_type, name, description, offering_details,
            pricing_info, availability, features, specifications,
            terms_conditions, org_id, created_by, created_at,
            updated_at, metadata, tags, categories, keywords, status
        )
        SELECT 
            COALESCE(id, gen_random_uuid()),
            'offering',
            COALESCE(name, 'Offering ' || COALESCE(id::text, 'Unknown')),
            description,
            CASE 
                WHEN offering_details IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(offering_details::jsonb) = 'object' THEN offering_details::jsonb
                        ELSE jsonb_build_object('data', offering_details)
                    END
                ELSE '{}'::jsonb
            END,
            CASE 
                WHEN pricing_info IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(pricing_info::jsonb) = 'object' THEN pricing_info::jsonb
                        ELSE jsonb_build_object('data', pricing_info)
                    END
                ELSE '{}'::jsonb
            END,
            CASE 
                WHEN availability IS NOT NULL THEN 
                    CASE 
                        WHEN jsonb_typeof(availability::jsonb) = 'object' THEN availability::jsonb
                        ELSE jsonb_build_object('data', availability)
                    END
                ELSE '{}'::jsonb
            END,
            COALESCE(features, '{}'),
            CASE 
                WHEN specifications IS NOT NULL THEN specifications::jsonb
                ELSE '{}'::jsonb
            END,
            terms_conditions,
            org_id,
            created_by,
            COALESCE(created_at, CURRENT_TIMESTAMP),
            COALESCE(updated_at, CURRENT_TIMESTAMP),
            COALESCE(metadata, '{}'::jsonb),
            COALESCE(tags, '{}'),
            COALESCE(categories, '{}'),
            COALESCE(keywords, '{}'),
            COALESCE(status, 'active')
        FROM cmis.offerings
        ON CONFLICT (id) DO NOTHING;
        
        GET DIAGNOSTICS migrated_count = ROW_COUNT;
        total_migrated := total_migrated + migrated_count;
        RAISE NOTICE 'Migrated % offerings', migrated_count;
    END IF;
    
    RAISE NOTICE 'Total contexts migrated: %', total_migrated;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'migrated_contexts: ' || total_migrated::text)
    WHERE phase = 'PHASE_2_UNIFIED_CONTEXTS' AND status = 'RUNNING';
END $$;

-- Update phase 2 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_2_UNIFIED_CONTEXTS' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 3: Create Campaign-Context Links Table
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 3: Creating Campaign-Context Links'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_3_CAMPAIGN_LINKS', 'RUNNING');

-- Drop existing table if exists
DROP TABLE IF EXISTS cmis.campaign_context_links CASCADE;

-- Create the campaign-context links table with enhanced features
CREATE TABLE cmis.campaign_context_links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    campaign_id UUID NOT NULL,
    context_id UUID NOT NULL REFERENCES cmis.contexts_unified(id) ON DELETE CASCADE,
    context_type VARCHAR(50) NOT NULL,
    link_type VARCHAR(50) DEFAULT 'primary',
    link_strength DECIMAL(3,2) DEFAULT 1.0 CHECK (link_strength BETWEEN 0 AND 1),
    
    -- Additional link metadata
    link_purpose TEXT,
    link_notes TEXT,
    effective_from DATE,
    effective_to DATE,
    is_active BOOLEAN DEFAULT true,
    
    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by UUID,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by UUID,
    metadata JSONB DEFAULT '{}',
    
    -- Constraints
    UNIQUE(campaign_id, context_id, link_type),
    CONSTRAINT valid_link_type CHECK (link_type IN ('primary', 'secondary', 'reference', 'historical')),
    CONSTRAINT valid_dates CHECK (effective_from IS NULL OR effective_to IS NULL OR effective_from <= effective_to)
);

-- Create indexes for performance
CREATE INDEX idx_campaign_links_campaign ON cmis.campaign_context_links(campaign_id);
CREATE INDEX idx_campaign_links_context ON cmis.campaign_context_links(context_id);
CREATE INDEX idx_campaign_links_type ON cmis.campaign_context_links(context_type);
CREATE INDEX idx_campaign_links_link_type ON cmis.campaign_context_links(link_type);
CREATE INDEX idx_campaign_links_active ON cmis.campaign_context_links(is_active) WHERE is_active = true;
CREATE INDEX idx_campaign_links_created_at ON cmis.campaign_context_links(created_at);
CREATE INDEX idx_campaign_links_effective ON cmis.campaign_context_links(effective_from, effective_to) 
    WHERE effective_from IS NOT NULL OR effective_to IS NOT NULL;
CREATE INDEX idx_campaign_links_metadata ON cmis.campaign_context_links USING gin(metadata);

-- Add table comments
COMMENT ON TABLE cmis.campaign_context_links IS 'Links campaigns to their various contexts with flexible relationship types';
COMMENT ON COLUMN cmis.campaign_context_links.link_strength IS 'Strength of the relationship (0.0 to 1.0)';
COMMENT ON COLUMN cmis.campaign_context_links.link_type IS 'Type of link: primary, secondary, reference, or historical';

-- Create trigger for updated_at
CREATE TRIGGER update_campaign_links_updated_at
    BEFORE UPDATE ON cmis.campaign_context_links
    FOR EACH ROW
    EXECUTE FUNCTION cmis.update_updated_at_column();

-- Migrate existing campaign relationships
DO $$
DECLARE
    link_count INTEGER := 0;
    total_links INTEGER := 0;
    campaign_rec RECORD;
    context_rec RECORD;
BEGIN
    -- Link campaigns to contexts based on context_id column
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_schema = 'cmis' AND table_name = 'campaigns' 
               AND column_name = 'context_id') THEN
        
        FOR campaign_rec IN 
            SELECT DISTINCT c.id AS campaign_id, c.context_id
            FROM cmis.campaigns c
            WHERE c.context_id IS NOT NULL
        LOOP
            -- Check if this context exists in unified table
            SELECT id, context_type INTO context_rec
            FROM cmis.contexts_unified
            WHERE id = campaign_rec.context_id;
            
            IF FOUND THEN
                INSERT INTO cmis.campaign_context_links (
                    campaign_id, context_id, context_type, link_type, 
                    link_purpose, created_at
                )
                VALUES (
                    campaign_rec.campaign_id, 
                    context_rec.id, 
                    context_rec.context_type,
                    'primary',
                    'Migrated from original context_id column',
                    CURRENT_TIMESTAMP
                )
                ON CONFLICT (campaign_id, context_id, link_type) DO NOTHING;
                
                GET DIAGNOSTICS link_count = ROW_COUNT;
                total_links := total_links + link_count;
            END IF;
        END LOOP;
    END IF;
    
    -- Link campaigns to creative contexts
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_schema = 'cmis' AND table_name = 'campaigns' 
               AND column_name = 'creative_id') THEN
        
        link_count := 0;
        FOR campaign_rec IN 
            SELECT DISTINCT c.id AS campaign_id, c.creative_id
            FROM cmis.campaigns c
            WHERE c.creative_id IS NOT NULL
        LOOP
            SELECT id INTO context_rec
            FROM cmis.contexts_unified
            WHERE id = campaign_rec.creative_id AND context_type = 'creative';
            
            IF FOUND THEN
                INSERT INTO cmis.campaign_context_links (
                    campaign_id, context_id, context_type, link_type,
                    link_purpose, created_at
                )
                VALUES (
                    campaign_rec.campaign_id, 
                    campaign_rec.creative_id,
                    'creative',
                    'primary',
                    'Migrated from creative_id column',
                    CURRENT_TIMESTAMP
                )
                ON CONFLICT (campaign_id, context_id, link_type) DO NOTHING;
                
                link_count := link_count + 1;
            END IF;
        END LOOP;
        
        total_links := total_links + link_count;
    END IF;
    
    -- Link campaigns to value contexts
    IF EXISTS (SELECT 1 FROM information_schema.columns 
               WHERE table_schema = 'cmis' AND table_name = 'campaigns' 
               AND column_name = 'value_id') THEN
        
        link_count := 0;
        FOR campaign_rec IN 
            SELECT DISTINCT c.id AS campaign_id, c.value_id
            FROM cmis.campaigns c
            WHERE c.value_id IS NOT NULL
        LOOP
            SELECT id INTO context_rec
            FROM cmis.contexts_unified
            WHERE id = campaign_rec.value_id AND context_type = 'value';
            
            IF FOUND THEN
                INSERT INTO cmis.campaign_context_links (
                    campaign_id, context_id, context_type, link_type,
                    link_purpose, created_at
                )
                VALUES (
                    campaign_rec.campaign_id, 
                    campaign_rec.value_id,
                    'value',
                    'primary',
                    'Migrated from value_id column',
                    CURRENT_TIMESTAMP
                )
                ON CONFLICT (campaign_id, context_id, link_type) DO NOTHING;
                
                link_count := link_count + 1;
            END IF;
        END LOOP;
        
        total_links := total_links + link_count;
    END IF;
    
    RAISE NOTICE 'Total campaign-context links created: %', total_links;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'created_links: ' || total_links::text)
    WHERE phase = 'PHASE_3_CAMPAIGN_LINKS' AND status = 'RUNNING';
END $$;

-- Update phase 3 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_3_CAMPAIGN_LINKS' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 4: Create Audit Infrastructure
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 4: Setting up Audit Infrastructure'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_4_AUDIT', 'RUNNING');

-- Create comprehensive audit log table
DROP TABLE IF EXISTS operations.audit_log CASCADE;

CREATE TABLE operations.audit_log (
    id BIGSERIAL PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- User and session information
    user_id UUID,
    session_id TEXT,
    username TEXT,
    
    -- Action details
    action VARCHAR(50) NOT NULL,
    table_schema VARCHAR(63) NOT NULL,
    table_name VARCHAR(63) NOT NULL,
    record_id UUID,
    record_key TEXT, -- For composite keys
    
    -- Data changes
    old_values JSONB,
    new_values JSONB,
    changed_fields TEXT[],
    
    -- Query information
    query TEXT,
    query_params TEXT[],
    
    -- Context information
    ip_address INET,
    user_agent TEXT,
    application_name TEXT,
    host_name TEXT,
    
    -- Additional metadata
    metadata JSONB DEFAULT '{}',
    tags TEXT[],
    
    -- Performance metrics
    execution_time_ms INTEGER,
    rows_affected INTEGER
);

-- Create comprehensive indexes for audit log
CREATE INDEX idx_audit_timestamp ON operations.audit_log(timestamp);
CREATE INDEX idx_audit_timestamp_hour ON operations.audit_log(date_trunc('hour', timestamp));
CREATE INDEX idx_audit_user ON operations.audit_log(user_id);
CREATE INDEX idx_audit_username ON operations.audit_log(username);
CREATE INDEX idx_audit_session ON operations.audit_log(session_id);
CREATE INDEX idx_audit_action ON operations.audit_log(action);
CREATE INDEX idx_audit_table ON operations.audit_log(table_schema, table_name);
CREATE INDEX idx_audit_record ON operations.audit_log(record_id);
CREATE INDEX idx_audit_record_key ON operations.audit_log(record_key);
CREATE INDEX idx_audit_ip ON operations.audit_log(ip_address);
CREATE INDEX idx_audit_metadata ON operations.audit_log USING gin(metadata);
CREATE INDEX idx_audit_tags ON operations.audit_log USING gin(tags);
CREATE INDEX idx_audit_changed_fields ON operations.audit_log USING gin(changed_fields);

-- Create enhanced audit trigger function
CREATE OR REPLACE FUNCTION operations.audit_trigger_function()
RETURNS TRIGGER AS $$
DECLARE
    changed_fields TEXT[] := '{}';
    old_json JSONB;
    new_json JSONB;
    user_id_val UUID;
    username_val TEXT;
    session_id_val TEXT;
    app_name TEXT;
BEGIN
    -- Try to get user information from various sources
    BEGIN
        user_id_val := current_setting('app.current_user_id', true)::uuid;
    EXCEPTION WHEN OTHERS THEN
        user_id_val := NULL;
    END;
    
    BEGIN
        username_val := current_setting('app.current_username', true);
    EXCEPTION WHEN OTHERS THEN
        username_val := current_user;
    END;
    
    BEGIN
        session_id_val := current_setting('app.session_id', true);
    EXCEPTION WHEN OTHERS THEN
        session_id_val := pg_backend_pid()::text;
    END;
    
    BEGIN
        app_name := current_setting('application_name', true);
    EXCEPTION WHEN OTHERS THEN
        app_name := 'unknown';
    END;
    
    -- Handle different operations
    IF TG_OP = 'DELETE' THEN
        old_json := to_jsonb(OLD);
        
        INSERT INTO operations.audit_log(
            user_id, username, session_id, action, 
            table_schema, table_name, record_id, 
            old_values, query, application_name,
            ip_address, rows_affected
        )
        VALUES (
            user_id_val,
            username_val,
            session_id_val,
            'DELETE',
            TG_TABLE_SCHEMA,
            TG_TABLE_NAME,
            OLD.id,
            old_json,
            current_query(),
            app_name,
            inet_client_addr(),
            1
        );
        RETURN OLD;
        
    ELSIF TG_OP = 'UPDATE' THEN
        old_json := to_jsonb(OLD);
        new_json := to_jsonb(NEW);
        
        -- Identify changed fields
        SELECT array_agg(key) INTO changed_fields
        FROM (
            SELECT key 
            FROM jsonb_each(old_json) o
            FULL OUTER JOIN jsonb_each(new_json) n USING (key)
            WHERE o.value IS DISTINCT FROM n.value
        ) changes;
        
        -- Only log if there are actual changes
        IF array_length(changed_fields, 1) > 0 THEN
            INSERT INTO operations.audit_log(
                user_id, username, session_id, action,
                table_schema, table_name, record_id,
                old_values, new_values, changed_fields,
                query, application_name, ip_address,
                rows_affected
            )
            VALUES (
                user_id_val,
                username_val,
                session_id_val,
                'UPDATE',
                TG_TABLE_SCHEMA,
                TG_TABLE_NAME,
                NEW.id,
                old_json,
                new_json,
                changed_fields,
                current_query(),
                app_name,
                inet_client_addr(),
                1
            );
        END IF;
        RETURN NEW;
        
    ELSIF TG_OP = 'INSERT' THEN
        new_json := to_jsonb(NEW);
        
        INSERT INTO operations.audit_log(
            user_id, username, session_id, action,
            table_schema, table_name, record_id,
            new_values, query, application_name,
            ip_address, rows_affected
        )
        VALUES (
            user_id_val,
            username_val,
            session_id_val,
            'INSERT',
            TG_TABLE_SCHEMA,
            TG_TABLE_NAME,
            NEW.id,
            new_json,
            current_query(),
            app_name,
            inet_client_addr(),
            1
        );
        RETURN NEW;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Add audit triggers to critical tables
DO $$
DECLARE
    tbl RECORD;
    trigger_count INTEGER := 0;
BEGIN
    -- Add triggers to main tables
    FOR tbl IN 
        SELECT schemaname, tablename 
        FROM pg_tables 
        WHERE schemaname = 'cmis' 
        AND tablename IN ('contexts_unified', 'campaign_context_links', 'campaigns', 
                         'integrations', 'creative_assets', 'orgs', 'users')
    LOOP
        -- Drop existing trigger if exists
        EXECUTE format('DROP TRIGGER IF EXISTS audit_trigger_%s ON %I.%I',
            tbl.tablename, tbl.schemaname, tbl.tablename);
        
        -- Create new trigger  
        EXECUTE format('
            CREATE TRIGGER audit_trigger_%s
            AFTER INSERT OR UPDATE OR DELETE ON %I.%I
            FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function()',
            tbl.tablename, tbl.schemaname, tbl.tablename
        );
        trigger_count := trigger_count + 1;
    END LOOP;
    
    RAISE NOTICE 'Created % audit triggers', trigger_count;
    
    UPDATE operations.migrations 
    SET affected_objects = array_append(affected_objects, 'audit_triggers: ' || trigger_count::text)
    WHERE phase = 'PHASE_4_AUDIT' AND status = 'RUNNING';
END $$;

-- Create audit summary view
CREATE OR REPLACE VIEW operations.audit_summary AS
SELECT 
    date_trunc('hour', timestamp) AS hour,
    table_schema,
    table_name,
    action,
    COUNT(*) AS operation_count,
    COUNT(DISTINCT user_id) AS unique_users,
    COUNT(DISTINCT record_id) AS unique_records,
    AVG(execution_time_ms) AS avg_execution_time_ms
FROM operations.audit_log
GROUP BY date_trunc('hour', timestamp), table_schema, table_name, action;

-- Create function to purge old audit logs
CREATE OR REPLACE FUNCTION operations.purge_old_audit_logs(retention_days INTEGER DEFAULT 90)
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM operations.audit_log
    WHERE timestamp < CURRENT_TIMESTAMP - (retention_days || ' days')::interval;
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    RAISE NOTICE 'Purged % old audit log entries', deleted_count;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;

-- Update phase 4 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_4_AUDIT' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 5: Create Helper Functions and Views
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 5: Creating Helper Functions & Views'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_5_HELPERS', 'RUNNING');

-- Function to get all contexts for a campaign
CREATE OR REPLACE FUNCTION cmis.get_campaign_contexts(
    p_campaign_id UUID,
    p_include_inactive BOOLEAN DEFAULT false
)
RETURNS TABLE (
    context_id UUID,
    context_type VARCHAR(50),
    name VARCHAR(255),
    description TEXT,
    link_type VARCHAR(50),
    link_strength DECIMAL(3,2),
    link_purpose TEXT,
    is_active BOOLEAN,
    created_at TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        cu.id,
        cu.context_type,
        cu.name,
        cu.description,
        ccl.link_type,
        ccl.link_strength,
        ccl.link_purpose,
        ccl.is_active,
        ccl.created_at
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.campaign_id = p_campaign_id
      AND (p_include_inactive OR ccl.is_active = true)
    ORDER BY ccl.link_strength DESC, ccl.created_at;
END;
$$ LANGUAGE plpgsql STABLE;

-- Function to find related campaigns (campaigns sharing contexts)
CREATE OR REPLACE FUNCTION cmis.find_related_campaigns(
    p_campaign_id UUID,
    p_limit INTEGER DEFAULT 10
)
RETURNS TABLE (
    campaign_id UUID,
    campaign_name VARCHAR(255),
    shared_contexts_count INTEGER,
    similarity_score DECIMAL(5,2)
) AS $$
BEGIN
    RETURN QUERY
    WITH campaign_contexts AS (
        SELECT context_id, link_strength
        FROM cmis.campaign_context_links
        WHERE campaign_id = p_campaign_id AND is_active = true
    ),
    related AS (
        SELECT 
            ccl.campaign_id,
            COUNT(DISTINCT ccl.context_id) AS shared_count,
            SUM(ccl.link_strength * cc.link_strength) AS similarity
        FROM cmis.campaign_context_links ccl
        INNER JOIN campaign_contexts cc ON ccl.context_id = cc.context_id
        WHERE ccl.campaign_id != p_campaign_id
          AND ccl.is_active = true
        GROUP BY ccl.campaign_id
    )
    SELECT 
        r.campaign_id,
        c.name,
        r.shared_count::INTEGER,
        r.similarity::DECIMAL(5,2)
    FROM related r
    LEFT JOIN cmis.campaigns c ON r.campaign_id = c.id
    ORDER BY r.similarity DESC, r.shared_count DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- Function to search contexts
CREATE OR REPLACE FUNCTION cmis.search_contexts(
    p_search_query TEXT,
    p_context_type VARCHAR(50) DEFAULT NULL,
    p_limit INTEGER DEFAULT 20
)
RETURNS TABLE (
    id UUID,
    context_type VARCHAR(50),
    name VARCHAR(255),
    description TEXT,
    relevance REAL,
    highlights TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        cu.id,
        cu.context_type,
        cu.name,
        cu.description,
        ts_rank(cu.search_vector, query) AS relevance,
        ts_headline('english', 
                   coalesce(cu.name, '') || ' ' || coalesce(cu.description, ''),
                   query,
                   'MaxWords=50, MinWords=20, ShortWord=3, HighlightAll=FALSE'
        ) AS highlights
    FROM cmis.contexts_unified cu,
         plainto_tsquery('english', p_search_query) query
    WHERE cu.search_vector @@ query
      AND (p_context_type IS NULL OR cu.context_type = p_context_type)
      AND cu.status = 'active'
      AND cu.deleted_at IS NULL
    ORDER BY relevance DESC
    LIMIT p_limit;
END;
$$ LANGUAGE plpgsql STABLE;

-- View for campaign summary with contexts
CREATE OR REPLACE VIEW cmis.campaign_summary AS
WITH context_counts AS (
    SELECT 
        campaign_id,
        context_type,
        COUNT(*) AS type_count
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.is_active = true
    GROUP BY campaign_id, context_type
)
SELECT 
    c.id AS campaign_id,
    c.name AS campaign_name,
    c.status AS campaign_status,
    c.created_at AS campaign_created,
    c.updated_at AS campaign_updated,
    
    -- Creative context
    creative.id AS creative_context_id,
    creative.name AS creative_context_name,
    creative.description AS creative_context_description,
    
    -- Value context
    value.id AS value_context_id,
    value.name AS value_context_name,
    value.description AS value_context_description,
    
    -- Offering context
    offering.id AS offering_context_id,
    offering.name AS offering_context_name,
    offering.description AS offering_context_description,
    
    -- Context counts
    COALESCE(cc_creative.type_count, 0) AS creative_contexts_count,
    COALESCE(cc_value.type_count, 0) AS value_contexts_count,
    COALESCE(cc_offering.type_count, 0) AS offering_contexts_count,
    COALESCE(cc_creative.type_count, 0) + 
    COALESCE(cc_value.type_count, 0) + 
    COALESCE(cc_offering.type_count, 0) AS total_contexts
FROM cmis.campaigns c
LEFT JOIN LATERAL (
    SELECT cu.* 
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.campaign_id = c.id 
    AND cu.context_type = 'creative'
    AND ccl.link_type = 'primary'
    AND ccl.is_active = true
    ORDER BY ccl.link_strength DESC
    LIMIT 1
) creative ON true
LEFT JOIN LATERAL (
    SELECT cu.*
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.campaign_id = c.id 
    AND cu.context_type = 'value'
    AND ccl.link_type = 'primary'
    AND ccl.is_active = true
    ORDER BY ccl.link_strength DESC
    LIMIT 1
) value ON true
LEFT JOIN LATERAL (
    SELECT cu.*
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.campaign_id = c.id 
    AND cu.context_type = 'offering'
    AND ccl.link_type = 'primary'
    AND ccl.is_active = true
    ORDER BY ccl.link_strength DESC
    LIMIT 1
) offering ON true
LEFT JOIN context_counts cc_creative ON cc_creative.campaign_id = c.id AND cc_creative.context_type = 'creative'
LEFT JOIN context_counts cc_value ON cc_value.campaign_id = c.id AND cc_value.context_type = 'value'
LEFT JOIN context_counts cc_offering ON cc_offering.campaign_id = c.id AND cc_offering.context_type = 'offering';

-- View for context usage statistics
CREATE OR REPLACE VIEW cmis.context_usage_stats AS
SELECT 
    cu.id AS context_id,
    cu.context_type,
    cu.name AS context_name,
    cu.status,
    COUNT(DISTINCT ccl.campaign_id) AS campaigns_count,
    COUNT(DISTINCT ccl.campaign_id) FILTER (WHERE ccl.link_type = 'primary') AS primary_links_count,
    COUNT(DISTINCT ccl.campaign_id) FILTER (WHERE ccl.link_type = 'secondary') AS secondary_links_count,
    AVG(ccl.link_strength) AS avg_link_strength,
    MAX(ccl.created_at) AS last_used_at,
    cu.created_at,
    cu.updated_at
FROM cmis.contexts_unified cu
LEFT JOIN cmis.campaign_context_links ccl ON cu.id = ccl.context_id AND ccl.is_active = true
WHERE cu.deleted_at IS NULL
GROUP BY cu.id, cu.context_type, cu.name, cu.status, cu.created_at, cu.updated_at;

-- Create materialized view for performance dashboards
CREATE MATERIALIZED VIEW cmis.dashboard_metrics AS
SELECT 
    -- Context metrics
    (SELECT COUNT(*) FROM cmis.contexts_unified WHERE deleted_at IS NULL) AS total_contexts,
    (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'creative' AND deleted_at IS NULL) AS creative_contexts,
    (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'value' AND deleted_at IS NULL) AS value_contexts,
    (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'offering' AND deleted_at IS NULL) AS offering_contexts,
    
    -- Campaign metrics
    (SELECT COUNT(*) FROM cmis.campaigns) AS total_campaigns,
    (SELECT COUNT(*) FROM cmis.campaigns WHERE status = 'active') AS active_campaigns,
    (SELECT COUNT(*) FROM cmis.campaigns WHERE created_at > CURRENT_DATE - INTERVAL '30 days') AS recent_campaigns,
    
    -- Link metrics
    (SELECT COUNT(*) FROM cmis.campaign_context_links WHERE is_active = true) AS active_links,
    (SELECT AVG(link_strength) FROM cmis.campaign_context_links WHERE is_active = true) AS avg_link_strength,
    
    -- Audit metrics
    (SELECT COUNT(*) FROM operations.audit_log WHERE timestamp > CURRENT_DATE - INTERVAL '24 hours') AS daily_audit_entries,
    
    -- Last update
    CURRENT_TIMESTAMP AS last_refreshed;

-- Create index on materialized view
CREATE UNIQUE INDEX idx_dashboard_metrics_refresh ON cmis.dashboard_metrics(last_refreshed);

-- Function to refresh dashboard metrics
CREATE OR REPLACE FUNCTION cmis.refresh_dashboard_metrics()
RETURNS void AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY cmis.dashboard_metrics;
END;
$$ LANGUAGE plpgsql;

-- Update phase 5 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_5_HELPERS' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 6: Backward Compatibility Layer
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 6: Creating Backward Compatibility Layer'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_6_COMPATIBILITY', 'RUNNING');

-- Rename original tables if they exist (backup)
DO $$
BEGIN
    -- Backup creative_contexts
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'creative_contexts') THEN
        ALTER TABLE cmis.creative_contexts RENAME TO creative_contexts_old;
        RAISE NOTICE 'Renamed creative_contexts to creative_contexts_old';
    END IF;
    
    -- Backup value_contexts
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'value_contexts') THEN
        ALTER TABLE cmis.value_contexts RENAME TO value_contexts_old;
        RAISE NOTICE 'Renamed value_contexts to value_contexts_old';
    END IF;
    
    -- Backup offerings
    IF EXISTS (SELECT 1 FROM information_schema.tables 
               WHERE table_schema = 'cmis' AND table_name = 'offerings') THEN
        ALTER TABLE cmis.offerings RENAME TO offerings_old;
        RAISE NOTICE 'Renamed offerings to offerings_old';
    END IF;
END $$;

-- Create backward-compatible views with full column support
CREATE OR REPLACE VIEW cmis.creative_contexts AS
SELECT 
    id,
    name,
    description,
    creative_brief,
    brand_guidelines,
    visual_style,
    tone_of_voice,
    target_platforms,
    creative_assets,
    org_id,
    created_by,
    created_at,
    updated_at,
    metadata,
    tags,
    categories,
    keywords,
    status,
    parent_context_id,
    related_contexts,
    version
FROM cmis.contexts_unified
WHERE context_type = 'creative'
  AND deleted_at IS NULL;

CREATE OR REPLACE VIEW cmis.value_contexts AS
SELECT 
    id,
    name,
    description,
    value_proposition,
    target_audience,
    key_messages,
    pain_points,
    benefits,
    differentiators,
    org_id,
    created_by,
    created_at,
    updated_at,
    metadata,
    tags,
    categories,
    keywords,
    status,
    parent_context_id,
    related_contexts,
    version
FROM cmis.contexts_unified
WHERE context_type = 'value'
  AND deleted_at IS NULL;

CREATE OR REPLACE VIEW cmis.offerings AS
SELECT 
    id,
    name,
    description,
    offering_details,
    pricing_info,
    availability,
    features,
    specifications,
    terms_conditions,
    org_id,
    created_by,
    created_at,
    updated_at,
    metadata,
    tags,
    categories,
    keywords,
    status,
    parent_context_id,
    related_contexts,
    version
FROM cmis.contexts_unified
WHERE context_type = 'offering'
  AND deleted_at IS NULL;

-- Create INSTEAD OF triggers for INSERT operations
CREATE OR REPLACE FUNCTION cmis.creative_contexts_insert()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO cmis.contexts_unified (
        id, context_type, name, description, creative_brief,
        brand_guidelines, visual_style, tone_of_voice,
        target_platforms, creative_assets, org_id, created_by,
        metadata, tags, categories, keywords, status,
        parent_context_id, related_contexts
    ) VALUES (
        COALESCE(NEW.id, gen_random_uuid()),
        'creative',
        NEW.name,
        NEW.description,
        NEW.creative_brief,
        NEW.brand_guidelines,
        NEW.visual_style,
        NEW.tone_of_voice,
        NEW.target_platforms,
        NEW.creative_assets,
        NEW.org_id,
        NEW.created_by,
        COALESCE(NEW.metadata, '{}'::jsonb),
        COALESCE(NEW.tags, '{}'),
        COALESCE(NEW.categories, '{}'),
        COALESCE(NEW.keywords, '{}'),
        COALESCE(NEW.status, 'active'),
        NEW.parent_context_id,
        NEW.related_contexts
    ) RETURNING * INTO NEW;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER creative_contexts_insert_trigger
    INSTEAD OF INSERT ON cmis.creative_contexts
    FOR EACH ROW EXECUTE FUNCTION cmis.creative_contexts_insert();

-- Similar for UPDATE
CREATE OR REPLACE FUNCTION cmis.creative_contexts_update()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE cmis.contexts_unified
    SET 
        name = NEW.name,
        description = NEW.description,
        creative_brief = NEW.creative_brief,
        brand_guidelines = NEW.brand_guidelines,
        visual_style = NEW.visual_style,
        tone_of_voice = NEW.tone_of_voice,
        target_platforms = NEW.target_platforms,
        creative_assets = NEW.creative_assets,
        metadata = NEW.metadata,
        tags = NEW.tags,
        categories = NEW.categories,
        keywords = NEW.keywords,
        status = NEW.status,
        parent_context_id = NEW.parent_context_id,
        related_contexts = NEW.related_contexts
    WHERE id = NEW.id AND context_type = 'creative';
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER creative_contexts_update_trigger
    INSTEAD OF UPDATE ON cmis.creative_contexts
    FOR EACH ROW EXECUTE FUNCTION cmis.creative_contexts_update();

-- Similar for DELETE
CREATE OR REPLACE FUNCTION cmis.creative_contexts_delete()
RETURNS TRIGGER AS $$
BEGIN
    -- Soft delete
    UPDATE cmis.contexts_unified
    SET deleted_at = CURRENT_TIMESTAMP
    WHERE id = OLD.id AND context_type = 'creative';
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER creative_contexts_delete_trigger
    INSTEAD OF DELETE ON cmis.creative_contexts
    FOR EACH ROW EXECUTE FUNCTION cmis.creative_contexts_delete();

-- Update phase 6 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_6_COMPATIBILITY' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 7: Update Foreign Keys and Clean cmis_refactored
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 7: Foreign Keys & Cleanup'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_7_FK_CLEANUP', 'RUNNING');

-- Update all foreign key constraints
DO $$
DECLARE
    fk_rec RECORD;
    update_count INTEGER := 0;
BEGIN
    -- Find and update foreign keys
    FOR fk_rec IN 
        SELECT 
            tc.table_schema,
            tc.table_name,
            tc.constraint_name,
            kcu.column_name,
            ccu.table_schema AS foreign_schema,
            ccu.table_name AS foreign_table,
            ccu.column_name AS foreign_column
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY' 
            AND ccu.table_schema = 'cmis_refactored'
    LOOP
        -- Drop old constraint
        EXECUTE format('ALTER TABLE %I.%I DROP CONSTRAINT IF EXISTS %I',
                      fk_rec.table_schema, 
                      fk_rec.table_name, 
                      fk_rec.constraint_name);
        
        -- Create new constraint pointing to cmis schema
        EXECUTE format('ALTER TABLE %I.%I ADD CONSTRAINT %I 
                       FOREIGN KEY (%I) REFERENCES cmis.%I(%I)',
                      fk_rec.table_schema,
                      fk_rec.table_name,
                      fk_rec.constraint_name,
                      fk_rec.column_name,
                      fk_rec.foreign_table,
                      fk_rec.foreign_column);
        
        update_count := update_count + 1;
        RAISE NOTICE 'Updated FK: %.%.% -> cmis.%', 
                    fk_rec.table_schema,
                    fk_rec.table_name, 
                    fk_rec.column_name,
                    fk_rec.foreign_table;
    END LOOP;
    
    RAISE NOTICE 'Updated % foreign key constraints', update_count;
END $$;

-- Check if cmis_refactored is empty and remove it
DO $$
DECLARE
    object_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO object_count
    FROM pg_class c
    JOIN pg_namespace n ON n.oid = c.relnamespace
    WHERE n.nspname = 'cmis_refactored';
    
    IF object_count = 0 THEN
        DROP SCHEMA IF EXISTS cmis_refactored CASCADE;
        RAISE NOTICE '✅ Dropped empty schema cmis_refactored';
    ELSE
        RAISE NOTICE '⚠️ Schema cmis_refactored still contains % objects', object_count;
    END IF;
END $$;

-- Recreate views that were not dependent on cmis_refactored
DO $$
DECLARE
    v_rec RECORD;
    new_definition TEXT;
BEGIN
    FOR v_rec IN 
        SELECT * FROM view_definitions_backup 
        WHERE depends_on_refactored = false
            AND view_name NOT IN ('creative_contexts', 'value_contexts', 'offerings', 
                                  'campaigns', 'integrations')
        ORDER BY view_name
    LOOP
        BEGIN
            -- Update any references just in case
            new_definition := replace(v_rec.view_definition, 'cmis_refactored.', 'cmis.');
            
            EXECUTE format('CREATE OR REPLACE VIEW %I.%I AS %s',
                          v_rec.schema_name,
                          v_rec.view_name,
                          new_definition);
            RAISE NOTICE 'Recreated view: %.%', v_rec.schema_name, v_rec.view_name;
        EXCEPTION
            WHEN OTHERS THEN
                RAISE WARNING 'Could not recreate view %.%: %', 
                             v_rec.schema_name, v_rec.view_name, SQLERRM;
        END;
    END LOOP;
END $$;

-- Update phase 7 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_7_FK_CLEANUP' AND status = 'RUNNING';

-- ============================================================================
-- PHASE 8: Final Optimization and Verification
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'PHASE 8: Final Optimization'
\echo '========================================='

INSERT INTO operations.migrations (version, phase, status)
VALUES ('2.2', 'PHASE_8_OPTIMIZATION', 'RUNNING');

-- Analyze all tables for query optimization
ANALYZE cmis.contexts_unified;
ANALYZE cmis.campaign_context_links;
ANALYZE cmis.campaigns;
ANALYZE cmis.integrations;
ANALYZE operations.audit_log;
ANALYZE operations.migrations;

-- Refresh materialized view
REFRESH MATERIALIZED VIEW cmis.dashboard_metrics;

-- Set permissions
DO $$
BEGIN
    -- Grant permissions on new tables
    GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA cmis TO begin;
    GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA cmis TO begin;
    GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA cmis TO begin;
    
    -- Grant permissions on operations schema
    GRANT USAGE ON SCHEMA operations TO begin;
    GRANT SELECT ON ALL TABLES IN SCHEMA operations TO begin;
    
    -- Grant to application user if exists
    IF EXISTS (SELECT 1 FROM pg_user WHERE usename = 'gpts_data') THEN
        GRANT USAGE ON SCHEMA cmis TO gpts_data;
        GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA cmis TO gpts_data;
        GRANT USAGE ON ALL SEQUENCES IN SCHEMA cmis TO gpts_data;
    END IF;
    
    RAISE NOTICE 'Permissions granted successfully';
EXCEPTION
    WHEN undefined_object THEN
        RAISE NOTICE 'Some roles do not exist, skipping permission grants';
END $$;

-- Create summary statistics
DO $$
DECLARE
    stats JSONB;
BEGIN
    SELECT jsonb_build_object(
        'contexts_unified_count', (SELECT COUNT(*) FROM cmis.contexts_unified),
        'creative_contexts', (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'creative'),
        'value_contexts', (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'value'),
        'offerings', (SELECT COUNT(*) FROM cmis.contexts_unified WHERE context_type = 'offering'),
        'campaign_links_count', (SELECT COUNT(*) FROM cmis.campaign_context_links),
        'active_links', (SELECT COUNT(*) FROM cmis.campaign_context_links WHERE is_active = true),
        'audit_triggers_count', (SELECT COUNT(*) FROM pg_trigger WHERE tgname LIKE 'audit_trigger_%'),
        'tables_in_cmis', (SELECT COUNT(*) FROM pg_tables WHERE schemaname = 'cmis'),
        'views_in_cmis', (SELECT COUNT(*) FROM pg_views WHERE schemaname = 'cmis'),
        'cmis_refactored_removed', NOT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'cmis_refactored'),
        'total_migration_time', (
            SELECT SUM(duration_seconds) 
            FROM operations.migrations 
            WHERE version = '2.2'
        ),
        'database_size', (SELECT pg_size_pretty(pg_database_size(current_database())))
    ) INTO stats;
    
    UPDATE operations.migrations
    SET metadata = stats
    WHERE phase = 'PHASE_8_OPTIMIZATION' AND status = 'RUNNING';
    
    RAISE NOTICE 'Migration Statistics: %', jsonb_pretty(stats);
END $$;

-- Update phase 8 completion
UPDATE operations.migrations 
SET status = 'COMPLETED', completed_at = CURRENT_TIMESTAMP
WHERE phase = 'PHASE_8_OPTIMIZATION' AND status = 'RUNNING';

-- ============================================================================
-- FINAL: Migration Summary
-- ============================================================================
\echo ''
\echo '========================================='
\echo 'MIGRATION COMPLETE - Summary'
\echo '========================================='

-- Display migration summary
SELECT 
    phase,
    status,
    duration_seconds,
    TO_CHAR(started_at, 'HH24:MI:SS') AS started,
    TO_CHAR(completed_at, 'HH24:MI:SS') AS completed,
    COALESCE(
        CASE 
            WHEN array_length(affected_objects, 1) > 0 
            THEN array_to_string(affected_objects, ', ')
            ELSE 'N/A'
        END,
        'N/A'
    ) AS affected_objects
FROM operations.migrations
WHERE version = '2.2'
ORDER BY migration_id;

-- Display final statistics
\echo ''
\echo 'Final Statistics:'
\echo '-----------------'

SELECT 
    'Contexts Unified' AS entity,
    COUNT(*) AS total_count,
    COUNT(*) FILTER (WHERE context_type = 'creative') AS creative,
    COUNT(*) FILTER (WHERE context_type = 'value') AS value,
    COUNT(*) FILTER (WHERE context_type = 'offering') AS offering
FROM cmis.contexts_unified

UNION ALL

SELECT 
    'Campaign Links',
    COUNT(*),
    COUNT(*) FILTER (WHERE link_type = 'primary'),
    COUNT(*) FILTER (WHERE link_type = 'secondary'),
    COUNT(*) FILTER (WHERE link_type = 'reference')
FROM cmis.campaign_context_links

UNION ALL

SELECT 
    'Tables in CMIS',
    COUNT(*),
    COUNT(*) FILTER (WHERE tablename IN ('campaigns', 'integrations')),
    COUNT(*) FILTER (WHERE tablename IN ('creative_assets', 'orgs', 'users')),
    COUNT(*) FILTER (WHERE tablename NOT IN ('campaigns', 'integrations', 'creative_assets', 'orgs', 'users'))
FROM pg_tables
WHERE schemaname = 'cmis';

-- Verify critical conversions
\echo ''
\echo 'Critical Conversions Verification:'
\echo '-----------------------------------'

SELECT 
    'campaigns' as object_name,
    CASE 
        WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'campaigns')
        THEN '✅ Is now a TABLE'
        ELSE '❌ Not found as table'
    END as status
UNION ALL
SELECT 
    'integrations',
    CASE 
        WHEN EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = 'cmis' AND tablename = 'integrations')
        THEN '✅ Is now a TABLE'
        ELSE '❌ Not found as table'
    END
UNION ALL
SELECT 
    'cmis_refactored schema',
    CASE 
        WHEN NOT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = 'cmis_refactored')
        THEN '✅ Successfully removed'
        ELSE '⚠️ Still exists'
    END;

COMMIT;

\echo ''
\echo '============================================================================'
\echo 'Migration v2.2 completed successfully!'
\echo 'Completed at: ' `date`
\echo ''
\echo 'IMPORTANT POST-MIGRATION STEPS:'
\echo '1. Test all application functionality'
\echo '2. Verify campaigns and integrations are now tables'
\echo '3. Monitor performance for 24-48 hours'
\echo '4. Review audit logs for any issues'
\echo '5. Keep backup for at least 7 days'
\echo '============================================================================'

\timing off