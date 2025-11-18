#!/bin/bash

###############################################################################
# Parallel Test Execution Script
#
# This script runs PHPUnit tests in parallel to significantly speed up
# test execution time. It splits test suites and runs them concurrently.
###############################################################################

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  CMIS Parallel Test Runner${NC}"
echo -e "${GREEN}========================================${NC}"

# Check if paratest is installed
if ! command -v vendor/bin/paratest &> /dev/null; then
    echo -e "${YELLOW}ParaTest not found. Installing...${NC}"
    composer require --dev brianium/paratest --no-interaction
fi

# Determine number of parallel processes (use CPU cores - 1, minimum 2)
PROCESSES=$(nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null || echo 4)
PROCESSES=$((PROCESSES > 2 ? PROCESSES - 1 : 2))

echo -e "${GREEN}Running tests with ${PROCESSES} parallel processes${NC}"

# Parse command line arguments
SUITE=""
FILTER=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --suite)
            SUITE="$2"
            shift 2
            ;;
        --filter)
            FILTER="$2"
            shift 2
            ;;
        --unit)
            SUITE="Unit"
            shift
            ;;
        --feature)
            SUITE="Feature"
            shift
            ;;
        --integration)
            SUITE="Integration"
            shift
            ;;
        --help)
            echo "Usage: $0 [options]"
            echo "Options:"
            echo "  --suite <name>       Run specific test suite (Unit|Feature|Integration)"
            echo "  --unit               Run unit tests only"
            echo "  --feature            Run feature tests only"
            echo "  --integration        Run integration tests only"
            echo "  --filter <pattern>   Run tests matching pattern"
            echo "  --help               Show this help message"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Build paratest command
CMD="vendor/bin/paratest --processes=${PROCESSES} --runner=WrapperRunner"

if [ -n "$SUITE" ]; then
    CMD="$CMD --testsuite=$SUITE"
fi

if [ -n "$FILTER" ]; then
    CMD="$CMD --filter=$FILTER"
fi

# Add common options
CMD="$CMD --colors --stop-on-failure"

echo -e "${GREEN}Command: ${CMD}${NC}"
echo ""

# Run tests
START_TIME=$(date +%s)

if eval $CMD; then
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ All tests passed!${NC}"
    echo -e "${GREEN}  Time: ${DURATION}s${NC}"
    echo -e "${GREEN}========================================${NC}"
    exit 0
else
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    echo ""
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}✗ Tests failed${NC}"
    echo -e "${RED}  Time: ${DURATION}s${NC}"
    echo -e "${RED}========================================${NC}"
    exit 1
fi
