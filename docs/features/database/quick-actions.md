# Quick Action Checklist - CMIS Database Fixes

## Week 1: Critical Safety (P0)

### Day 1: Backup & Safety
- [ ] Create full database backup
  ```bash
  pg_dump -Fc cmis > cmis_backup_$(date +%Y%m%d).dump
  ```
- [ ] Test restore on separate server
  ```bash
  pg_restore -d cmis_test cmis_backup_*.dump
  ```
- [ ] Document backup procedure in runbook
- [ ] Set up automated daily backups

### Day 2: Foreign Keys Audit
- [ ] Run FK discovery script:
  ```sql
  -- Check for missing FKs
  SELECT tc.table_name, kcu.column_name
  FROM information_schema.columns c
  WHERE c.table_schema = 'cmis'
  AND c.column_name LIKE '%_id'
  AND NOT EXISTS (
      SELECT 1 FROM information_schema.table_constraints tc
      JOIN information_schema.key_column_usage kcu
      ON tc.constraint_name = kcu.constraint_name
      WHERE tc.constraint_type = 'FOREIGN KEY'
      AND kcu.table_name = c.table_name
      AND kcu.column_name = c.column_name
  );
  ```
- [ ] Create migration for missing FKs
- [ ] Test on staging

### Day 3: Model Relations Fix
- [ ] Fix ScheduledSocialPost->user() relation:
  ```php
  // FROM:
  return $this->belongsTo(User::class, 'user_id', 'id');
  // TO:
  return $this->belongsTo(User::class, 'user_id', 'user_id');
  ```
- [ ] Run tests: `php artisan test`
- [ ] Fix any other broken relations

### Day 4-5: Query Monitoring
- [ ] Install Laravel Telescope:
  ```bash
  composer require laravel/telescope
  php artisan telescope:install
  php artisan migrate
  ```
- [ ] Enable pg_stat_statements:
  ```sql
  CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
  ```
- [ ] Set up slow query alerts (> 1s)

---

## Week 2: High Priority Fixes (P1)

### Monday: Seeders
- [ ] Fix ExtendedDemoDataSeeder modules issue
- [ ] Fix SessionsSeeder user_id type mismatch
- [ ] Add error handling to all seeders
- [ ] Test: `php artisan db:seed --class=DatabaseSeeder`

### Tuesday: Transaction Safety
- [ ] Audit migrations without transactions
- [ ] Add DB::transaction() wrapping
- [ ] Add proper error handling
- [ ] Test rollback scenarios

### Wednesday: ON DELETE Strategy
- [ ] Document ON DELETE strategy:
  - Audit columns (created_by, updated_by): SET NULL
  - User sessions: CASCADE
  - Org references: RESTRICT
- [ ] Create migration to standardize
- [ ] Apply changes

### Thursday: N+1 Queries
- [ ] Enable Telescope Query Watcher
- [ ] Identify N+1 queries in main routes
- [ ] Add eager loading:
  ```php
  Campaign::with(['org', 'creator', 'offerings'])->get();
  ```
- [ ] Verify with Telescope

### Friday: Review & Testing
- [ ] Run full test suite
- [ ] Performance baseline measurements
- [ ] Update documentation
- [ ] Team review meeting

---

## Month 1 Checklist Summary

### Critical (Must Do)
- [x] Database backup created ✓
- [x] Restore tested ✓
- [x] Foreign keys audited ✓
- [x] Missing FKs added ✓
- [x] Model relations fixed ✓
- [x] Query monitoring enabled ✓

### High Priority (Should Do)
- [ ] Seeders fixed
- [ ] Transaction safety added
- [ ] ON DELETE standardized
- [ ] N+1 queries fixed
- [ ] Performance baseline documented

### Medium Priority (Nice to Have)
- [ ] Slow query optimization
- [ ] JSONB indexes verified
- [ ] Documentation started

---

## Quick Reference: Common Issues & Fixes

### Issue: Migration fails with FK error
```bash
# Solution: Check for orphaned records
SELECT * FROM table_name t
WHERE NOT EXISTS (
    SELECT 1 FROM referenced_table r
    WHERE r.id = t.foreign_key_id
);

# Fix orphaned records or delete them
DELETE FROM table_name WHERE foreign_key_id NOT IN (SELECT id FROM referenced_table);
```

### Issue: N+1 Query detected
```php
// Bad
foreach ($campaigns as $campaign) {
    echo $campaign->org->name;  // N+1!
}

// Good
$campaigns = Campaign::with('org')->get();
foreach ($campaigns as $campaign) {
    echo $campaign->org->name;  // Single query
}
```

### Issue: Slow JSONB query
```php
// Add GIN index
Schema::table('table_name', function (Blueprint $table) {
    DB::statement('CREATE INDEX idx_table_jsonb_gin ON table_name USING gin (json_column jsonb_path_ops)');
});
```

### Issue: RLS blocking query
```php
// Set current user context
DB::statement("SET LOCAL app.current_user_id = ?", [$userId]);
```

---

## Daily Health Checks

### Morning Check (5 minutes)
```bash
# 1. Check database size
psql -c "SELECT pg_size_pretty(pg_database_size('cmis'));"

# 2. Check slow queries
psql -c "SELECT query, mean_exec_time FROM pg_stat_statements ORDER BY mean_exec_time DESC LIMIT 5;"

# 3. Check long-running queries
psql -c "SELECT pid, now() - query_start as duration, query FROM pg_stat_activity WHERE state = 'active' AND query_start < now() - interval '1 minute';"

# 4. Check backup status
ls -lh /backups/cmis_*.dump | tail -1
```

### Weekly Review (30 minutes)
- [ ] Review Telescope slow queries
- [ ] Check pg_stat_statements report
- [ ] Review database growth trends
- [ ] Update team on progress

---

## Emergency Procedures

### Database Down
1. Check PostgreSQL status: `systemctl status postgresql`
2. Check disk space: `df -h`
3. Check logs: `tail -100 /var/log/postgresql/postgresql.log`
4. Restart if needed: `systemctl restart postgresql`

### Slow Performance
1. Check active queries: `SELECT * FROM pg_stat_activity;`
2. Kill long-running query: `SELECT pg_terminate_backend(pid);`
3. Check locks: `SELECT * FROM pg_locks WHERE NOT granted;`
4. Check indexes: `SELECT * FROM pg_stat_user_indexes WHERE idx_scan = 0;`

### Data Corruption
1. Stop application immediately
2. Create emergency backup
3. Restore from last known good backup
4. Investigate root cause
5. Restore application

---

## Contact & Resources

### Team
- **Database Admin:** [Name]
- **Backend Lead:** [Name]
- **DevOps:** [Name]

### Documentation
- Full Report: `CMIS_DATABASE_ANALYSIS_REPORT.md`
- Executive Summary: `EXECUTIVE_SUMMARY_AR.md`
- This Checklist: `QUICK_ACTION_CHECKLIST.md`

### Tools
- Telescope: `http://localhost/telescope`
- pgAdmin: `http://localhost:5050`
- Monitoring: `http://localhost:3000`

---

**Last Updated:** 2025-11-18
**Next Review:** Weekly

**Remember: Test everything on staging first! 🧪**
