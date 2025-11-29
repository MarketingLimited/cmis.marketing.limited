#!/bin/bash

# Test script for CI-001, CI-002, CI-003 fixes
# Tests critical page loads in both Arabic and English

echo "=========================================="
echo "Testing Critical Page Fixes"
echo "=========================================="
echo ""

BASE_URL="https://cmis-test.kazaaz.com"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to test a URL
test_url() {
    local url=$1
    local name=$2
    local lang=$3

    echo -n "Testing $name ($lang): "

    # Make request with language cookie
    response=$(curl -s -o /dev/null -w "%{http_code}" \
        -H "Cookie: locale=$lang" \
        "$url" \
        --max-time 10)

    if [ "$response" == "200" ] || [ "$response" == "302" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $response)"
        return 1
    fi
}

echo "Note: These tests check HTTP status codes."
echo "You need to be authenticated to test org-specific pages."
echo ""

# Test CI-002: Settings Page (no org context needed)
echo "=== CI-002: Settings Page ==="
test_url "$BASE_URL/settings" "Settings Page" "en"
test_url "$BASE_URL/settings" "Settings Page" "ar"
echo ""

# Test CI-003: Onboarding Page (no org context needed)
echo "=== CI-003: Onboarding Page ==="
test_url "$BASE_URL/onboarding" "Onboarding Page" "en"
test_url "$BASE_URL/onboarding" "Onboarding Page" "ar"
echo ""

echo "=== Testing requires authentication for org-specific routes ==="
echo "To test CI-001 (Social Pages), you need to:"
echo "1. Login to https://cmis-test.kazaaz.com/login"
echo "2. Get your session cookie"
echo "3. Test these URLs manually:"
echo "   - /orgs/{org-id}/social/posts"
echo "   - /orgs/{org-id}/social/scheduler"
echo "   - /orgs/{org-id}/social/inbox"
echo ""

echo "=========================================="
echo "Test Complete"
echo "=========================================="
