# Database Migrations

This directory contains the Laravel migrations for the CMIS database schema.

## Migration Structure

The database schema has been refactored from `database/schema.sql` into organized Laravel migrations:

### 1. Extensions and Schemas (2025_11_14_000001)
Creates PostgreSQL extensions and application schemas:
- **Extensions**: uuid-ossp, pgcrypto, pg_trgm, btree_gin, citext, ltree, vector
- **Schemas**: cmis, cmis_ai_analytics, cmis_analytics, cmis_audit, cmis_dev, cmis_knowledge, cmis_marketing, cmis_ops, cmis_staging, cmis_system_health, archive, lab, operations

### 2. Database Tables (2025_11_14_000002)
Creates all 189 database tables across multiple schemas:
- Archive tables (backup and historical data)
- Core CMIS tables (campaigns, users, orgs, etc.)
- Analytics tables (metrics, tracking, performance)
- Marketing tables (ads, content, creative assets)
- System tables (jobs, cache, migrations)

Table definitions are stored in `database/sql/tables.sql` for maintainability.

### 3. Indexes and Constraints (2025_11_14_000003)
Adds all indexes, primary keys, foreign keys, and constraints:
- Primary keys for all tables
- Foreign key relationships
- Unique constraints
- Check constraints
- Performance indexes

Definitions are stored in `database/sql/constraints_and_indexes.sql`.

### 4. Functions and Triggers (2025_11_14_000004)
Creates PostgreSQL functions, stored procedures, and triggers:
- Permission checking functions
- Cache management functions
- Audit logging triggers
- Validation triggers
- Business logic functions

Definitions are stored in `database/sql/functions_and_triggers.sql`.

## Running Migrations

To run all migrations:
```bash
php artisan migrate
```

To rollback:
```bash
php artisan migrate:rollback
```

To reset and re-run all migrations:
```bash
php artisan migrate:fresh
```

## Database Schema

The complete database includes:
- **189 tables** across multiple schemas
- **14 schemas** for logical separation
- **8 PostgreSQL extensions** for advanced features
- **Custom functions** for business logic
- **Triggers** for automation and audit logging

## Schema Organization

- `cmis.*` - Main application tables
- `cmis_audit.*` - Audit logging and compliance
- `cmis_analytics.*` - Analytics and reporting
- `cmis_ai_analytics.*` - AI/ML analytics
- `cmis_marketing.*` - Marketing-specific data
- `cmis_ops.*` - Operational data
- `archive.*` - Historical and backup data
- `public.*` - Shared reference tables

## Notes

- The schema uses UUIDs for primary keys throughout
- Soft deletes are implemented on most tables
- Multi-tenancy is supported via org_id
- Full-text search is enabled with tsvector columns
- Vector embeddings are supported for AI features

## Phases Directory

The `phases/` directory contains planned incremental migrations that were designed before the schema refactor. These migrations are now superseded by the base migrations created from `schema.sql`. They are kept for reference but should not be executed.

## Maintenance

When updating the schema:
1. Export changes from database: `pg_dump -s > database/schema.sql`
2. Re-run the extraction scripts to update SQL files
3. Test migrations on a clean database
4. Document any breaking changes

## Extraction Scripts

The following scripts were used to extract components from schema.sql:
- Extract tables: Separates CREATE TABLE statements
- Extract constraints: Extracts ALTER TABLE and constraints
- Extract indexes: Separates CREATE INDEX statements
- Extract functions: Extracts CREATE FUNCTION and CREATE TRIGGER statements

These scripts can be re-run if the schema.sql file is updated.
