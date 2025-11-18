# Database Documentation

This directory contains comprehensive documentation for the CMIS database architecture, schema, optimization, and maintenance.

---

## Quick Navigation

- **[Overview](overview.md)** - Database architecture overview
- **[Analysis Report](analysis-report.md)** - Detailed database analysis
- **[Quick Actions](quick-actions.md)** - Quick fixes and optimizations
- **[Executive Summary (Arabic)](executive-summary-ar.md)** - ملخص تنفيذي بالعربية

---

## Overview

CMIS uses PostgreSQL as its primary database with advanced features:

- **PostgreSQL 14+** - Primary database
- **pgvector Extension** - Vector similarity search
- **Row-Level Security (RLS)** - Multi-tenancy isolation
- **JSON/JSONB** - Flexible data storage
- **Full-Text Search** - Content search capabilities
- **Partitioning** - Performance optimization

---

## Database Architecture

### Core Schema Components

#### 1. Core Tables (cmis schema)
- **users** - User accounts and authentication
- **orgs** - Organizations (tenants)
- **org_users** - User-organization relationships
- **permissions** - Permission definitions
- **user_permissions** - User permission assignments

#### 2. Campaign Management
- **campaigns** - Campaign definitions
- **content_plans** - Content planning
- **content_items** - Individual content pieces
- **creative_assets** - Media and creative files
- **copy_components** - Copy variations

#### 3. Platform Integration
- **ad_accounts** - Advertising account configurations
- **ad_campaigns** - Platform-specific campaigns
- **ad_sets** - Ad set configurations
- **ad_entities** - Individual ads
- **ad_metrics** - Performance metrics

#### 4. Social Publishing
- **social_posts** - Social media posts
- **social_platforms** - Platform configurations
- **social_schedules** - Publishing schedules
- **social_analytics** - Social metrics

#### 5. AI & Semantic
- **embeddings** - Vector embeddings
- **knowledge_base** - Organization knowledge
- **semantic_cache** - Semantic search cache

#### 6. Compliance & Testing
- **compliance_rules** - Compliance rule definitions
- **compliance_audits** - Audit logs
- **ab_tests** - A/B test configurations
- **ab_test_variations** - Test variations

---

## Key Features

### 1. Multi-Tenancy with RLS

Row-Level Security ensures data isolation:

```sql
-- Example RLS policy
CREATE POLICY tenant_isolation ON campaigns
    USING (org_id = current_setting('app.current_org_id')::uuid);
```

**Benefits:**
- Automatic tenant isolation
- No application-level filtering needed
- Prevents data leaks
- Simplified queries

### 2. Vector Search with pgvector

Semantic search capabilities:

```sql
-- Find similar content
SELECT * FROM embeddings
ORDER BY embedding <-> query_vector
LIMIT 10;
```

**Use Cases:**
- Content similarity
- Semantic search
- Recommendation engine
- Duplicate detection

### 3. JSON/JSONB Storage

Flexible schema for platform-specific data:

```sql
-- Store platform-specific metadata
{
    "facebook": {"post_id": "123", "status": "published"},
    "instagram": {"media_id": "456", "permalink": "..."}
}
```

### 4. Performance Optimization

- **Indexes** - Strategic indexing for common queries
- **Partitioning** - Table partitioning for large tables
- **Materialized Views** - Pre-computed aggregations
- **Connection Pooling** - Efficient connection management

---

## Documentation Structure

### For Database Administrators
- [Overview](overview.md) - Architecture and design
- [Analysis Report](analysis-report.md) - Performance analysis
- [Quick Actions](quick-actions.md) - Common maintenance tasks

### For Developers
- [Overview](overview.md) - Schema and relationships
- [Analysis Report](analysis-report.md) - Query patterns
- Database Setup Guide (../../deployment/database-setup.md)

### For Architects
- [Analysis Report](analysis-report.md) - Comprehensive analysis
- [Executive Summary (Arabic)](executive-summary-ar.md) - Strategic overview

---

## Related Documentation

- **[Database Setup Guide](../../deployment/database-setup.md)** - Installation and configuration
- **[Multi-Tenancy Architecture](../../architecture/)** - RLS implementation
- **[Vector Embeddings API](../../VECTOR_EMBEDDINGS_V2_API_DOCUMENTATION.md)** - Vector search API
- **[Database Sync Report](../../DATABASE_SYNC_REPORT.md)** - Synchronization status

---

## Common Tasks

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Run seeders
php artisan db:seed
```

### Database Backup

```bash
# Full database backup
pg_dump -h localhost -U cmis_user cmis > backup.sql

# Backup specific schema
pg_dump -h localhost -U cmis_user -n cmis cmis > cmis_schema.sql

# Restore backup
psql -h localhost -U cmis_user cmis < backup.sql
```

### Performance Analysis

```sql
-- Find slow queries
SELECT query, mean_exec_time, calls
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 10;

-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0
ORDER BY tablename;

-- Table sizes
SELECT tablename,
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename))
FROM pg_tables
WHERE schemaname = 'cmis'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

### RLS Management

```sql
-- Enable RLS on a table
ALTER TABLE campaigns ENABLE ROW LEVEL SECURITY;

-- Create RLS policy
CREATE POLICY tenant_isolation ON campaigns
    USING (org_id = current_setting('app.current_org_id')::uuid);

-- View RLS policies
SELECT * FROM pg_policies WHERE tablename = 'campaigns';
```

---

## Database Maintenance

### Regular Tasks

**Daily:**
- Monitor query performance
- Check replication lag (if applicable)
- Review error logs

**Weekly:**
- Vacuum analyze tables
- Check index health
- Review slow query log

**Monthly:**
- Full database backup
- Review and optimize indexes
- Update statistics
- Check table bloat

### Monitoring

```sql
-- Connection count
SELECT count(*) FROM pg_stat_activity;

-- Active queries
SELECT pid, usename, query, state
FROM pg_stat_activity
WHERE state = 'active';

-- Database size
SELECT pg_size_pretty(pg_database_size('cmis'));

-- Table vacuum stats
SELECT schemaname, relname, last_vacuum, last_autovacuum
FROM pg_stat_user_tables
WHERE schemaname = 'cmis';
```

---

## Schema Diagrams

### Entity Relationship Diagram

```
orgs (1) ──── (M) campaigns
  │
  └─── (M) org_users ──── (1) users
              │
              └─── (M) user_permissions ──── (1) permissions

campaigns (1) ──── (M) content_plans
                      │
                      └─── (M) content_items
                              │
                              ├─── (M) creative_assets
                              └─── (M) copy_components
```

### Multi-Tenancy Flow

```
User Login → Set current_org_id → RLS Policies Applied →
Queries Automatically Filtered → Results Returned
```

---

## Best Practices

### Query Performance
- Use appropriate indexes
- Avoid SELECT *
- Use EXPLAIN ANALYZE
- Batch updates when possible
- Use connection pooling

### Data Integrity
- Use foreign key constraints
- Implement check constraints
- Use NOT NULL where appropriate
- Validate data at application level
- Use transactions for multi-step operations

### Security
- Enable RLS on all tenant tables
- Use prepared statements
- Limit database user permissions
- Encrypt sensitive data
- Regular security audits

### Scalability
- Partition large tables
- Use materialized views for complex reports
- Archive old data
- Monitor table growth
- Plan for horizontal scaling

---

## Troubleshooting

### Common Issues

**Slow Queries**
- Run EXPLAIN ANALYZE
- Check for missing indexes
- Review RLS policy overhead
- Consider query optimization

**Lock Contention**
- Monitor pg_stat_activity
- Identify long-running queries
- Review transaction patterns
- Consider deadlock detection

**Disk Space**
- Check table and index sizes
- Run VACUUM FULL if needed
- Archive old data
- Review logging settings

**RLS Not Working**
- Verify policies are enabled
- Check current_org_id is set
- Review policy conditions
- Test with different users

---

## Migration Guide

See database/migrations/ directory for:
- Migration files
- Migration naming conventions
- Rollback procedures
- Migration best practices

See database/seeders/ directory for:
- Seeder files
- Test data generation
- Production seeders

---

## Support

- **Performance Issues** → See [Analysis Report](analysis-report.md)
- **Setup Questions** → See [Database Setup Guide](../../deployment/database-setup.md)
- **RLS Problems** → See [Multi-Tenancy Architecture](../../architecture/)
- **Quick Fixes** → See [Quick Actions](quick-actions.md)

---

**Last Updated:** 2025-11-18
**Maintained by:** CMIS Database Team
