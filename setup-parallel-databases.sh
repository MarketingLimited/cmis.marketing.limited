#!/bin/bash

###############################################################################
# Parallel Test Database Setup Script
#
# Creates multiple test databases for parallel test execution.
# Each ParaTest process will use a separate database to avoid conflicts.
###############################################################################

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Database configuration
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USER="${DB_USER:-begin}"
DB_PASSWORD="${DB_PASSWORD:-123@Marketing@321}"
DB_BASE_NAME="cmis_test"
NUM_DATABASES="${NUM_DATABASES:-15}"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  CMIS Parallel Test Database Setup${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}Configuration:${NC}"
echo -e "  Host: ${DB_HOST}"
echo -e "  Port: ${DB_PORT}"
echo -e "  User: ${DB_USER}"
echo -e "  Base Database: ${DB_BASE_NAME}"
echo -e "  Number of Databases: ${NUM_DATABASES}"
echo ""

# Function to create a database
create_database() {
    local db_name=$1
    echo -e "${YELLOW}Creating database: ${db_name}${NC}"

    # Drop database if exists (for clean setup)
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d postgres -c "DROP DATABASE IF EXISTS ${db_name};" 2>&1 | grep -v "NOTICE" || true

    # Create database
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d postgres -c "CREATE DATABASE ${db_name} OWNER ${DB_USER};" 2>&1

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database ${db_name} created successfully${NC}"
        return 0
    else
        echo -e "${RED}✗ Failed to create database ${db_name}${NC}"
        return 1
    fi
}

# Create main test database
echo -e "${BLUE}Step 1: Creating main test database${NC}"
create_database "${DB_BASE_NAME}"
echo ""

# Run migrations on main test database
echo -e "${BLUE}Step 2: Running migrations on main test database${NC}"
echo -e "${YELLOW}Running: php artisan migrate --database=pgsql --env=testing${NC}"
DB_DATABASE="${DB_BASE_NAME}" php artisan migrate --database=pgsql --env=testing --force 2>&1 | tail -20
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Create parallel test databases
echo -e "${BLUE}Step 3: Creating parallel test databases (1-${NUM_DATABASES})${NC}"
SUCCESS_COUNT=0
FAIL_COUNT=0

for i in $(seq 1 $NUM_DATABASES); do
    db_name="${DB_BASE_NAME}_${i}"
    if create_database "$db_name"; then
        SUCCESS_COUNT=$((SUCCESS_COUNT + 1))

        # Copy schema from main test database
        echo -e "${YELLOW}  Copying schema to ${db_name}...${NC}"
        PGPASSWORD="${DB_PASSWORD}" pg_dump -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" \
            -d "${DB_BASE_NAME}" --schema-only --no-owner --no-privileges | \
        PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" \
            -d "${db_name}" -q 2>&1 | grep -v "NOTICE" || true

        echo -e "${GREEN}  ✓ Schema copied to ${db_name}${NC}"
    else
        FAIL_COUNT=$((FAIL_COUNT + 1))
    fi
    echo ""
done

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Setup Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ Successfully created: ${SUCCESS_COUNT} databases${NC}"
if [ $FAIL_COUNT -gt 0 ]; then
    echo -e "${RED}✗ Failed to create: ${FAIL_COUNT} databases${NC}"
fi
echo ""

# List all test databases
echo -e "${BLUE}Current test databases:${NC}"
PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d postgres \
    -c "SELECT datname, pg_size_pretty(pg_database_size(datname)) as size FROM pg_database WHERE datname LIKE '${DB_BASE_NAME}%' ORDER BY datname;" 2>&1

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Parallel test database setup complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}You can now run parallel tests with:${NC}"
echo -e "  ${BLUE}./run-tests-parallel.sh --unit${NC}"
echo -e "  ${BLUE}./run-tests-parallel.sh --feature${NC}"
echo -e "  ${BLUE}./run-tests-parallel.sh --integration${NC}"
echo ""
