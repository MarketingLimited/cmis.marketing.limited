-- ====================================================================
-- CMIS Database Fixes - MASTER SCRIPT
-- السكربت الرئيسي لتنفيذ جميع الإصلاحات
-- ====================================================================
-- Purpose: تنفيذ جميع أجزاء الإصلاحات بالترتيب الصحيح
-- Execution time: ~20-35 minutes total
-- ====================================================================
-- 
-- IMPORTANT / مهم:
-- This script executes all 6 parts of the database fixes.
-- يقوم هذا السكربت بتنفيذ جميع الأجزاء الستة من الإصلاحات.
--
-- Each part is independent and can be run separately if needed.
-- كل جزء مستقل ويمكن تنفيذه منفصلاً إذا لزم الأمر.
--
-- PREREQUISITES / المتطلبات:
-- 1. PostgreSQL 14.0 or higher
-- 2. SUPERUSER or DB OWNER privileges
-- 3. Full database backup completed
-- 4. Maintenance window scheduled
-- 5. Users notified
--
-- ====================================================================

\set ON_ERROR_STOP on
\timing on

-- ====================================================================
-- تسجيل بداية التنفيذ
-- ====================================================================

\echo '===================================================================='
\echo '           CMIS DATABASE FIXES - MASTER EXECUTION'
\echo '           تنفيذ الإصلاحات الشاملة لقاعدة بيانات CMIS'
\echo '===================================================================='
\echo ''
\echo 'Started at:' :DATE
\echo 'Database:' :DBNAME
\echo 'User:' :USER
\echo ''
\echo 'This will execute 6 parts:'
\echo '  1. Pre-flight checks (2-3 min)'
\echo '  2. Missing columns & triggers (3-5 min)'
\echo '  3. Foreign keys (5-10 min)'
\echo '  4. Constraints (3-5 min)'
\echo '  5. Indexes optimization (5-15 min)'
\echo '  6. Monitoring & maintenance (2-3 min)'
\echo ''
\echo 'Total estimated time: 20-35 minutes'
\echo ''
\echo 'Press Ctrl+C within 10 seconds to cancel...'

SELECT pg_sleep(10);

\echo ''
\echo 'Starting execution...'
\echo ''

-- ====================================================================
-- PART 1: Pre-flight Checks
-- ====================================================================

\echo '===================================================================='
\echo 'PART 1/6: Pre-flight Checks'
\echo 'الجزء 1/6: الفحوصات الأولية'
\echo '===================================================================='
\echo ''

\i 01_preflight_checks.sql

\echo ''
\echo 'Part 1 completed. Continuing to Part 2...'
\echo ''

SELECT pg_sleep(2);

-- ====================================================================
-- PART 2: Missing Columns & Triggers
-- ====================================================================

\echo '===================================================================='
\echo 'PART 2/6: Missing Columns & Triggers'
\echo 'الجزء 2/6: الأعمدة والمحفزات المفقودة'
\echo '===================================================================='
\echo ''

\i 02_missing_columns.sql

\echo ''
\echo 'Part 2 completed. Continuing to Part 3...'
\echo ''

SELECT pg_sleep(2);

-- ====================================================================
-- PART 3: Foreign Keys
-- ====================================================================

\echo '===================================================================='
\echo 'PART 3/6: Foreign Keys'
\echo 'الجزء 3/6: المفاتيح الخارجية'
\echo '===================================================================='
\echo ''

\i 03_foreign_keys.sql

\echo ''
\echo 'Part 3 completed. Continuing to Part 4...'
\echo ''

SELECT pg_sleep(2);

-- ====================================================================
-- PART 4: Constraints
-- ====================================================================

\echo '===================================================================='
\echo 'PART 4/6: Constraints (UNIQUE & CHECK)'
\echo 'الجزء 4/6: القيود'
\echo '===================================================================='
\echo ''

\i 04_constraints.sql

\echo ''
\echo 'Part 4 completed. Continuing to Part 5...'
\echo ''

SELECT pg_sleep(2);

-- ====================================================================
-- PART 5: Indexes Optimization
-- ====================================================================

\echo '===================================================================='
\echo 'PART 5/6: Indexes Optimization'
\echo 'الجزء 5/6: تحسين الفهارس'
\echo '===================================================================='
\echo ''

\i 05_indexes.sql

\echo ''
\echo 'Part 5 completed. Continuing to Part 6...'
\echo ''

SELECT pg_sleep(2);

-- ====================================================================
-- PART 6: Monitoring & Maintenance
-- ====================================================================

\echo '===================================================================='
\echo 'PART 6/6: Monitoring & Maintenance'
\echo 'الجزء 6/6: المراقبة والصيانة'
\echo '===================================================================='
\echo ''

\i 06_monitoring.sql

\echo ''
\echo 'Part 6 completed.'
\echo ''

-- ====================================================================
-- ملخص نهائي شامل
-- ====================================================================

\echo '===================================================================='
\echo '                    MASTER EXECUTION SUMMARY'
\echo '                    ملخص التنفيذ الشامل'
\echo '===================================================================='
\echo ''

-- تقرير شامل
SELECT 
    script_part,
    fix_category,
    COUNT(*) as fixes_count,
    STRING_AGG(status, ', ') as statuses
FROM operations.fix_tracking
GROUP BY script_part, fix_category
ORDER BY script_part;

\echo ''
\echo 'Detailed fixes report:'
\echo ''

SELECT * FROM operations.generate_fixes_report();

\echo ''
\echo '===================================================================='
\echo '                    EXECUTION COMPLETED'
\echo '                    اكتمل التنفيذ'
\echo '===================================================================='
\echo ''
\echo 'All 6 parts completed successfully!'
\echo 'جميع الأجزاء الستة اكتملت بنجاح!'
\echo ''
\echo 'Next steps:'
\echo '  1. Run verification_script.sql to verify all changes'
\echo '  2. Test critical application functions'
\echo '  3. Monitor for 24-48 hours'
\echo '  4. Review monitoring views:'
\echo '     - SELECT * FROM operations.v_tables_without_updated_at;'
\echo '     - SELECT * FROM operations.v_potential_missing_fks;'
\echo '     - SELECT * FROM operations.v_duplicate_indexes;'
\echo '     - SELECT * FROM operations.v_backup_schemas;'
\echo ''
\echo 'Completed at:' :DATE
\echo '===================================================================='

\timing off
