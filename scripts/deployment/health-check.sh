#!/bin/bash

###############################################################################
# CMIS Health Check Script
#
# Usage: ./health-check.sh [--verbose]
#
# Performs comprehensive health checks on CMIS application
###############################################################################

# Configuration
VERBOSE=false
if [[ "$1" == "--verbose" ]]; then
    VERBOSE=true
fi

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Counters
PASSED=0
FAILED=0

# Functions
check_pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

verbose() {
    if [[ "$VERBOSE" == true ]]; then
        echo -e "${BLUE}  ℹ${NC} $1"
    fi
}

echo "========================================"
echo "CMIS Health Check - $(date)"
echo "========================================"
echo ""

# Check Docker
echo "Docker Services:"
if docker info > /dev/null 2>&1; then
    check_pass "Docker daemon is running"
    verbose "Docker version: $(docker --version)"
else
    check_fail "Docker daemon is not running"
fi

# Check containers
if docker-compose ps | grep -q "Up"; then
    check_pass "Docker containers are running"
    if [[ "$VERBOSE" == true ]]; then
        docker-compose ps
    fi
else
    check_fail "Docker containers are not running properly"
fi

# Check individual services
echo ""
echo "Service Health:"

# App container
if docker inspect cmis-app --format='{{.State.Health.Status}}' 2>/dev/null | grep -q "healthy"; then
    check_pass "Application container is healthy"
else
    check_fail "Application container is unhealthy or not running"
fi

# Nginx container
if docker inspect cmis-nginx --format='{{.State.Health.Status}}' 2>/dev/null | grep -q "healthy"; then
    check_pass "Nginx container is healthy"
else
    check_fail "Nginx container is unhealthy or not running"
fi

# PostgreSQL container
if docker inspect cmis-postgres --format='{{.State.Health.Status}}' 2>/dev/null | grep -q "healthy"; then
    check_pass "PostgreSQL container is healthy"
else
    check_fail "PostgreSQL container is unhealthy or not running"
fi

# Redis container
if docker inspect cmis-redis --format='{{.State.Health.Status}}' 2>/dev/null | grep -q "healthy"; then
    check_pass "Redis container is healthy"
else
    check_fail "Redis container is unhealthy or not running"
fi

# Check HTTP endpoints
echo ""
echo "HTTP Endpoints:"

# Health endpoint
if curl -f -s http://localhost/health > /dev/null 2>&1; then
    check_pass "Health endpoint responding"
else
    check_fail "Health endpoint not responding"
fi

# Application endpoint
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "200" ]] || [[ "$HTTP_CODE" == "302" ]]; then
    check_pass "Application endpoint responding (HTTP $HTTP_CODE)"
else
    check_fail "Application endpoint not responding (HTTP $HTTP_CODE)"
fi

# Check database connectivity
echo ""
echo "Database:"

DB_CONN=$(docker-compose exec -T postgres psql -U cmis -d cmis -c "SELECT 1" 2>/dev/null || echo "failed")
if [[ "$DB_CONN" != "failed" ]]; then
    check_pass "Database connection successful"

    # Count tables
    if [[ "$VERBOSE" == true ]]; then
        TABLE_COUNT=$(docker-compose exec -T postgres psql -U cmis -d cmis -t -c "SELECT count(*) FROM information_schema.tables WHERE table_schema NOT IN ('pg_catalog', 'information_schema')" 2>/dev/null | tr -d ' ')
        verbose "Tables in database: $TABLE_COUNT"
    fi
else
    check_fail "Database connection failed"
fi

# Check Redis connectivity
echo ""
echo "Cache & Queue:"

REDIS_PING=$(docker-compose exec -T redis redis-cli ping 2>/dev/null || echo "failed")
if [[ "$REDIS_PING" == "PONG" ]]; then
    check_pass "Redis connection successful"

    if [[ "$VERBOSE" == true ]]; then
        REDIS_MEMORY=$(docker-compose exec -T redis redis-cli INFO memory | grep "used_memory_human" | cut -d: -f2 | tr -d '\r')
        verbose "Redis memory usage: $REDIS_MEMORY"
    fi
else
    check_fail "Redis connection failed"
fi

# Check queue worker
if docker-compose ps queue | grep -q "Up"; then
    check_pass "Queue worker is running"
else
    check_fail "Queue worker is not running"
fi

# Check scheduler
if docker-compose ps scheduler | grep -q "Up"; then
    check_pass "Scheduler is running"
else
    check_fail "Scheduler is not running"
fi

# Check disk space
echo ""
echo "System Resources:"

DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [[ "$DISK_USAGE" -lt 80 ]]; then
    check_pass "Disk usage is acceptable ($DISK_USAGE%)"
elif [[ "$DISK_USAGE" -lt 90 ]]; then
    check_warn "Disk usage is high ($DISK_USAGE%)"
else
    check_fail "Disk usage is critical ($DISK_USAGE%)"
fi

# Check memory
MEMORY_USAGE=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [[ "$MEMORY_USAGE" -lt 80 ]]; then
    check_pass "Memory usage is acceptable ($MEMORY_USAGE%)"
elif [[ "$MEMORY_USAGE" -lt 90 ]]; then
    check_warn "Memory usage is high ($MEMORY_USAGE%)"
else
    check_fail "Memory usage is critical ($MEMORY_USAGE%)"
fi

# Check Docker volumes
echo ""
echo "Docker Volumes:"

if docker volume ls | grep -q "cmis_postgres-data"; then
    check_pass "PostgreSQL volume exists"
    if [[ "$VERBOSE" == true ]]; then
        VOLUME_SIZE=$(docker system df -v | grep postgres-data | awk '{print $4}')
        verbose "PostgreSQL volume size: $VOLUME_SIZE"
    fi
else
    check_warn "PostgreSQL volume not found"
fi

if docker volume ls | grep -q "cmis_redis-data"; then
    check_pass "Redis volume exists"
else
    check_warn "Redis volume not found"
fi

# Check recent logs for errors
echo ""
echo "Recent Errors:"

ERROR_COUNT=$(docker-compose logs --tail=100 2>/dev/null | grep -i "error" | wc -l)
if [[ "$ERROR_COUNT" -eq 0 ]]; then
    check_pass "No recent errors in logs"
elif [[ "$ERROR_COUNT" -lt 5 ]]; then
    check_warn "Found $ERROR_COUNT recent errors in logs"
else
    check_fail "Found $ERROR_COUNT recent errors in logs"
fi

# Summary
echo ""
echo "========================================"
echo "Health Check Summary:"
echo "  Passed: $PASSED"
echo "  Failed: $FAILED"
echo "========================================"

if [[ "$FAILED" -eq 0 ]]; then
    echo -e "${GREEN}All checks passed!${NC}"
    exit 0
elif [[ "$FAILED" -lt 3 ]]; then
    echo -e "${YELLOW}Some checks failed - manual review recommended${NC}"
    exit 1
else
    echo -e "${RED}Multiple checks failed - immediate attention required${NC}"
    exit 2
fi
