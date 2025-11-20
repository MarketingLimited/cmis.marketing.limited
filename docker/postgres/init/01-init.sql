-- CMIS PostgreSQL Initialization Script
-- Sets up database with pgvector extension and multi-tenancy support

\echo 'Initializing CMIS database...'

-- Enable pgvector extension
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS btree_gin;

\echo 'Extensions created successfully'

-- Create schemas (Laravel migrations will create tables)
CREATE SCHEMA IF NOT EXISTS cmis;
CREATE SCHEMA IF NOT EXISTS cmis_meta;
CREATE SCHEMA IF NOT EXISTS cmis_google;
CREATE SCHEMA IF NOT EXISTS cmis_tiktok;
CREATE SCHEMA IF NOT EXISTS cmis_linkedin;
CREATE SCHEMA IF NOT EXISTS cmis_twitter;
CREATE SCHEMA IF NOT EXISTS cmis_snapchat;
CREATE SCHEMA IF NOT EXISTS cmis_pinterest;
CREATE SCHEMA IF NOT EXISTS cmis_youtube;
CREATE SCHEMA IF NOT EXISTS cmis_platform;
CREATE SCHEMA IF NOT EXISTS cmis_ai;
CREATE SCHEMA IF NOT EXISTS cmis_analytics;

\echo 'Schemas created successfully'

-- Grant privileges to application user (will be replaced by envsubst)
GRANT ALL ON SCHEMA cmis TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_meta TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_google TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_tiktok TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_linkedin TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_twitter TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_snapchat TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_pinterest TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_youtube TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_platform TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_ai TO ${DB_USERNAME};
GRANT ALL ON SCHEMA cmis_analytics TO ${DB_USERNAME};

-- Set default privileges for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_meta GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_google GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_tiktok GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_linkedin GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_twitter GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_snapchat GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_pinterest GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_youtube GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_platform GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_ai GRANT ALL ON TABLES TO ${DB_USERNAME};
ALTER DEFAULT PRIVILEGES IN SCHEMA cmis_analytics GRANT ALL ON TABLES TO ${DB_USERNAME};

\echo 'Privileges granted successfully'

-- Create function for init_transaction_context (used by Laravel for RLS)
CREATE OR REPLACE FUNCTION public.init_transaction_context(org_uuid UUID)
RETURNS void AS $$
BEGIN
    PERFORM set_config('app.current_org_id', org_uuid::text, false);
END;
$$ LANGUAGE plpgsql;

\echo 'Helper functions created successfully'

-- Performance tuning
ALTER DATABASE ${DB_DATABASE} SET shared_preload_libraries = 'pg_stat_statements';
ALTER DATABASE ${DB_DATABASE} SET track_activity_query_size = 2048;
ALTER DATABASE ${DB_DATABASE} SET pg_stat_statements.track = all;

\echo 'CMIS database initialization complete!'
