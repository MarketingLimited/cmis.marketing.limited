#!/bin/bash
###############################################################################
# Laravel Testing Pre-Flight Checks
#
# This script validates the testing infrastructure before running tests.
# It checks PostgreSQL, Composer, database connections, and test databases.
#
# Usage: ./scripts/test-preflight.sh
###############################################################################

set -e

echo "========================================="
echo "  Laravel Testing Pre-Flight Checks"
echo "========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Status counters
CHECKS_PASSED=0
CHECKS_FAILED=0
CHECKS_WARNING=0

# 1. PostgreSQL Server Check
echo "1. Checking PostgreSQL server..."
if service postgresql status 2>&1 | grep -qi "active\|running\|online"; then
    echo -e "${GREEN}✅ PostgreSQL is running${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${RED}❌ PostgreSQL NOT running - attempting to start...${NC}"
    if service postgresql start 2>&1; then
        sleep 2  # Give PostgreSQL time to start
        if service postgresql status 2>&1 | grep -qi "active\|running\|online"; then
            echo -e "${GREEN}✅ PostgreSQL started successfully${NC}"
            ((CHECKS_PASSED++))
        else
            echo -e "${RED}❌ Failed to start PostgreSQL${NC}"
            ((CHECKS_FAILED++))
        fi
    else
        echo -e "${RED}❌ Failed to start PostgreSQL${NC}"
        ((CHECKS_FAILED++))
    fi
fi
echo ""

# 2. PostgreSQL Connection Check
echo "2. Checking PostgreSQL connection..."
if psql -h 127.0.0.1 -U postgres -d postgres -c "SELECT 1;" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Can connect to PostgreSQL${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${RED}❌ Cannot connect to PostgreSQL${NC}"
    echo "   Try: sed -i 's/scram-sha-256/trust/g' /etc/postgresql/*/main/pg_hba.conf && service postgresql reload"
    ((CHECKS_FAILED++))
fi
echo ""

# 3. Composer Dependencies Check
echo "3. Checking Composer dependencies..."
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo -e "${YELLOW}⚠️  Dependencies missing - running composer install...${NC}"
    if composer install --no-interaction --prefer-dist 2>&1 | tail -3; then
        echo -e "${GREEN}✅ Composer dependencies installed${NC}"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}❌ Composer install failed${NC}"
        ((CHECKS_FAILED++))
    fi
else
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
    ((CHECKS_PASSED++))
fi
echo ""

# 4. PHPUnit/ParaTest Check
echo "4. Checking test frameworks..."
if [ -f vendor/bin/phpunit ]; then
    echo -e "${GREEN}✅ PHPUnit installed${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${RED}❌ PHPUnit missing${NC}"
    ((CHECKS_FAILED++))
fi

if [ -f vendor/bin/paratest ]; then
    echo -e "${GREEN}✅ ParaTest installed${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}⚠️  ParaTest missing (optional for parallel testing)${NC}"
    ((CHECKS_WARNING++))
fi
echo ""

# 5. Database Role Check
echo "5. Checking database roles..."
if psql -h 127.0.0.1 -U postgres -d postgres -c "\du" 2>&1 | grep -q "begin"; then
    echo -e "${GREEN}✅ 'begin' role exists${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}⚠️  'begin' role missing - creating...${NC}"
    if psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE ROLE begin WITH LOGIN SUPERUSER PASSWORD '123@Marketing@321';" 2>&1; then
        echo -e "${GREEN}✅ 'begin' role created${NC}"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}❌ Failed to create 'begin' role${NC}"
        ((CHECKS_FAILED++))
    fi
fi
echo ""

# 6. pgvector Extension Check
echo "6. Checking pgvector extension..."
if psql -h 127.0.0.1 -U postgres -d postgres -c "SELECT * FROM pg_available_extensions WHERE name = 'vector';" 2>&1 | grep -q "vector"; then
    echo -e "${GREEN}✅ pgvector extension available${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}⚠️  pgvector extension missing (may be needed for tests)${NC}"
    echo "   Install: apt-get install -y postgresql-*-pgvector && service postgresql restart"
    ((CHECKS_WARNING++))
fi
echo ""

# 7. Test Databases Check
echo "7. Checking test databases..."
test_db_count=$(psql -h 127.0.0.1 -U postgres -d postgres -c "SELECT COUNT(*) FROM pg_database WHERE datname LIKE 'cmis_test%';" 2>&1 | grep -o "[0-9]*" | head -1)

if [ -z "$test_db_count" ] || [ "$test_db_count" -eq 0 ]; then
    echo -e "${YELLOW}⚠️  No test databases found - creating...${NC}"
    # Create test databases
    for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do
        psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE cmis_test_$i;" 2>&1 | grep -v "already exists" || true
    done
    psql -h 127.0.0.1 -U postgres -d postgres -c "CREATE DATABASE cmis_test;" 2>&1 | grep -v "already exists" || true
    echo -e "${GREEN}✅ Test databases created${NC}"
    ((CHECKS_PASSED++))
elif [ "$test_db_count" -ge 15 ]; then
    echo -e "${GREEN}✅ Parallel test databases exist ($test_db_count)${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}⚠️  Only $test_db_count test databases found (recommended: 15+)${NC}"
    ((CHECKS_WARNING++))
fi
echo ""

# 8. Environment Variables Check
echo "8. Checking environment variables..."
if printenv | grep -q "^DB_HOST=2\.59\.156\.237"; then
    echo -e "${YELLOW}⚠️  Remote DB_HOST detected - tests should use local PostgreSQL${NC}"
    echo "   Run: unset DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD"
    ((CHECKS_WARNING++))
else
    echo -e "${GREEN}✅ No remote database environment variables set${NC}"
    ((CHECKS_PASSED++))
fi
echo ""

# 9. phpunit.xml Configuration Check
echo "9. Checking phpunit.xml configuration..."
if grep -q "cmis_test" phpunit.xml 2>/dev/null; then
    echo -e "${GREEN}✅ Test database configured in phpunit.xml${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${RED}❌ Test database not configured in phpunit.xml${NC}"
    ((CHECKS_FAILED++))
fi

if grep -q "TEST_TOKEN\|PARALLEL_TESTING" config/database.php 2>/dev/null; then
    echo -e "${GREEN}✅ Parallel testing support detected${NC}"
    ((CHECKS_PASSED++))
else
    echo -e "${YELLOW}⚠️  Parallel testing not configured${NC}"
    ((CHECKS_WARNING++))
fi
echo ""

# Summary
echo "========================================="
echo "  Pre-Flight Check Summary"
echo "========================================="
echo -e "${GREEN}Passed:${NC}   $CHECKS_PASSED"
echo -e "${YELLOW}Warnings:${NC} $CHECKS_WARNING"
echo -e "${RED}Failed:${NC}   $CHECKS_FAILED"
echo ""

if [ $CHECKS_FAILED -gt 0 ]; then
    echo -e "${RED}❌ Pre-flight checks FAILED. Fix issues before running tests.${NC}"
    exit 1
elif [ $CHECKS_WARNING -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Pre-flight checks PASSED with warnings. Tests may run slower or have issues.${NC}"
    exit 0
else
    echo -e "${GREEN}✅ Pre-flight checks PASSED. Ready to run tests!${NC}"
    exit 0
fi
