# Database Migrations

## Overview

This directory contains Laravel migrations for the CMIS database, organized into **24 semantic, domain-focused migrations** optimized for maintainability, scaling, and AI agent interaction.

## Migration Architecture

### Design Principles

1. **Semantic Grouping**: Tables are grouped by business domain, not technical dependencies
2. **Clear Dependencies**: Each migration's header documents its dependencies
3. **AI-Friendly**: Descriptive names, inline documentation, and clear contexts
4. **Granular**: ~10-15 tables per migration for focused understanding
5. **Rollback-Safe**: Down migrations properly clean up all changes

### Migration Order (Must Follow This Sequence)

```
┌─────────────────────────────────────────────────────────────┐
│ PHASE 1: FOUNDATION                                         │
├─────────────────────────────────────────────────────────────┤
│ 000001  extensions_and_schemas          8 extensions       │
│                                          14 schemas          │
├─────────────────────────────────────────────────────────────┤
│ PHASE 2: INDEPENDENT TABLES                                 │
├─────────────────────────────────────────────────────────────┤
│ 000002  public_reference_tables         25 lookup tables   │
│ 000003  core_identity_and_access        10 core tables     │
│ 000004  system_infrastructure           9 system tables    │
├─────────────────────────────────────────────────────────────┤
│ PHASE 3: BUSINESS DOMAINS                                   │
├─────────────────────────────────────────────────────────────┤
│ 000005  integrations_and_accounts       Connections        │
│ 000006  campaigns_and_contexts          Campaign mgmt      │
│ 000007  content_and_creative            Content production │
│ 000008  ad_platforms                    Meta/Google Ads    │
│ 000009  testing_and_experiments         A/B testing        │
│ 000010  social_media_publishing         Social posts       │
│ 000011  fields_and_metadata             Dynamic fields     │
│ 000012  ai_and_analytics                AI/ML features     │
│ 000013  templates_and_generation        Content templates  │
│ 000014  data_and_workflows              ETL & automation   │
│ 000015  offerings_and_bundles           Product catalog    │
│ 000016  teams_and_communication         Collaboration      │
│ 000017  compliance_and_audit            Compliance & logs  │
│ 000018  advanced_features               Advanced campaign  │
│ 000019  archive_and_backups             Historical data    │
├─────────────────────────────────────────────────────────────┤
│ PHASE 4: DATABASE CONSTRAINTS                               │
├─────────────────────────────────────────────────────────────┤
│ 000020  sequences                       30 sequences       │
│ 000021  constraints                     409 constraints    │
│ 000022  indexes                         171 indexes        │
├─────────────────────────────────────────────────────────────┤
│ PHASE 5: BUSINESS LOGIC                                     │
├─────────────────────────────────────────────────────────────┤
│ 000023  functions                       126 functions      │
│ 000024  triggers                        20 triggers        │
└─────────────────────────────────────────────────────────────┘
```

## For AI Agents: How to Work with This Schema

### Adding a New Table

1. **Identify the Domain**: Determine which business domain your table belongs to
2. **Check Dependencies**: Review the migration file's "AI Agent Context" comment
3. **Add to Domain File**: Append CREATE TABLE statement to `database/sql/domain_*.sql`
4. **Add Constraints**: Add FK/PK constraints to `database/sql/all_constraints.sql`
5. **Add Indexes**: Add performance indexes to `database/sql/all_indexes.sql`
6. **Document**: Update this README if adding a new domain

### Modifying Existing Tables

1. **Create New Migration**: Don't modify base migrations
2. **Use Alter Table**: Create a new dated migration file
3. **Follow Naming**: `YYYY_MM_DD_HHMMSS_modify_{table}_add_{feature}.php`

### Understanding Table Relationships

```
users (cmis.users)
  ├─→ user_orgs ─→ orgs
  ├─→ user_permissions ─→ permissions
  └─→ user_sessions

orgs (cmis.orgs)
  ├─→ campaigns
  ├─→ integrations
  ├─→ social_accounts
  ├─→ ad_accounts
  └─→ content_plans

campaigns (cmis.campaigns)
  ├─→ campaign_context_links ─→ contexts
  ├─→ content_plans
  ├─→ creative_assets
  ├─→ ad_campaigns
  └─→ experiments
```

## Domain Descriptions

### Core Domains

| Domain | Tables | Purpose | Key Dependencies |
|--------|--------|---------|------------------|
| **Identity & Access** | users, orgs, roles, permissions | Multi-tenant RBAC | None (foundational) |
| **Campaigns** | campaigns, contexts, segments | Campaign management | core_identity |
| **Content & Creative** | creative_assets, copy_components | Content production | campaigns |
| **Ad Platforms** | ad_accounts, ad_campaigns, ad_metrics | Platform integrations | integrations, orgs |
| **Social Media** | social_posts, publishing_queues | Social publishing | integrations, campaigns |

### Support Domains

| Domain | Tables | Purpose |
|--------|--------|---------|
| **System Infrastructure** | cache, jobs, sessions | Application infrastructure |
| **Fields & Metadata** | field_definitions, meta_* | Dynamic schema |
| **AI & Analytics** | ai_models, cognitive_trends | ML features |
| **Compliance & Audit** | audit_log, compliance_rules | Governance |

## SQL File Structure

```
database/sql/
├── domain_public_reference.sql      # Lookup tables (11KB)
├── domain_core_identity.sql         # Users, orgs, roles (3.4KB)
├── domain_system_infra.sql          # Cache, jobs (2.6KB)
├── domain_integrations.sql          # Platform connections (1.3KB)
├── domain_campaigns.sql             # Campaigns, contexts (3.3KB)
├── domain_content.sql               # Creative assets (3.7KB)
├── domain_ad_platforms.sql          # Ad accounts, metrics (4.1KB)
├── domain_testing.sql               # A/B tests, experiments (1.9KB)
├── domain_social.sql                # Social posts, metrics (4.0KB)
├── domain_fields_metadata.sql       # Dynamic fields (3.3KB)
├── domain_ai_analytics.sql          # AI/ML analytics (4.2KB)
├── domain_templates.sql             # Content templates (2.9KB)
├── domain_data_workflows.sql        # ETL, workflows (2.9KB)
├── domain_offerings.sql             # Product catalog (1.5KB)
├── domain_teams.sql                 # Collaboration (1.4KB)
├── domain_compliance_audit.sql      # Compliance, logs (3.8KB)
├── domain_advanced.sql              # Advanced features (2.2KB)
├── domain_archive.sql               # Historical data (1.7KB)
├── all_sequences.sql                # 30 sequences (1.5KB)
├── all_constraints.sql              # 409 constraints (56KB)
├── all_indexes.sql                  # 171 indexes (19KB)
├── all_functions.sql                # 126 functions (143KB)
└── all_triggers.sql                 # 20 triggers (5.8KB)
```

## Running Migrations

### Fresh Installation
```bash
php artisan migrate
```

### Reset and Rebuild
```bash
php artisan migrate:fresh
```

### Rollback One Batch
```bash
php artisan migrate:rollback
```

### Check Status
```bash
php artisan migrate:status
```

## Database Statistics

- **Total Tables**: 189
- **Schemas**: 14
- **Extensions**: 8
- **Constraints**: 409 (PKs, FKs, UNIQUEs, CHECKs)
- **Indexes**: 171 (performance optimization)
- **Sequences**: 30 (auto-increment)
- **Functions**: 126 (business logic)
- **Triggers**: 20 (automation)

## Key Features

### Multi-Tenancy
- All tables scoped by `org_id` (UUID)
- Row-level security via org context
- Tenant isolation enforced at DB level

### Soft Deletes
- Most tables include `deleted_at` column
- Data never permanently deleted (compliance)
- Use `WHERE deleted_at IS NULL` filters

### UUID Primary Keys
- All tables use UUID instead of auto-increment
- Distributed system friendly
- Prevents ID enumeration attacks

### Full-Text Search
- Tables include `tsvector` columns
- GIN indexes for fast text search
- Supports multi-language search

### Vector Embeddings
- `vector` extension for AI/ML
- Semantic search capabilities
- Similarity matching

## Maintenance

### Updating Schema

When updating `database/schema.sql` (from production dump):

```bash
# 1. Dump production schema
pg_dump -s cmis > database/schema.sql

# 2. Re-extract domain files
/tmp/extract_by_domain.sh

# 3. Re-extract constraints/indexes/functions
/tmp/extract_all_constraints_indexes.sh
/tmp/extract_functions_triggers.sh

# 4. Test on clean database
php artisan migrate:fresh

# 5. Commit changes
git add database/
git commit -m "refactor: update schema from production"
```

### Adding New Domains

If you need to create a new domain:

1. Create `database/sql/domain_new_feature.sql`
2. Add migration file with next number (000025, etc.)
3. Document dependencies in migration header
4. Update this README
5. Test migration order

## Troubleshooting

### Migration Fails on Constraints

**Problem**: Foreign key constraint fails
**Solution**: Check dependency order - parent table must exist first

### Migration Fails on Functions

**Problem**: Function creation fails
**Solution**: May require PostgreSQL extensions or permissions. Check logs.

### Table Already Exists

**Problem**: `relation "table" already exists`
**Solution**: Run `php artisan migrate:fresh` or check migrations table

### Performance Issues

**Problem**: Slow queries after migration
**Solution**: Ensure indexes migration (000022) completed successfully

## Phases Directory

The `phases/` directory contains planned incremental migrations that were designed before the schema refactor. These migrations are now superseded by the base migrations created from `schema.sql`. They are kept for reference but should not be executed.

## For Developers

### Migration Best Practices

✅ **DO**:
- Add migrations for schema changes
- Test rollback functionality
- Document dependencies
- Use transactions where possible
- Add indexes for frequently queried columns

❌ **DON'T**:
- Modify existing base migrations
- Remove old migrations
- Skip dependency chains
- Create circular dependencies
- Forget to add constraints

### Testing Migrations

```bash
# Test up migration
php artisan migrate

# Test down migration
php artisan migrate:rollback

# Test full cycle
php artisan migrate:fresh

# Test specific migration
php artisan migrate --path=database/migrations/2025_11_14_000003_create_core_identity_and_access.php
```

---

**Last Updated**: 2025-11-14
**Schema Version**: Based on `database/schema.sql` (PostgreSQL 18.0)
**Total Migration Size**: ~290KB SQL definitions
