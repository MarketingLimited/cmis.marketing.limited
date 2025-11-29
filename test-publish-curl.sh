#!/bin/bash

# CMIS Publishing API Test with curl
# Tests the publishing endpoint directly

BASE_URL="https://cmis-test.kazaaz.com"
ORG_ID="5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a"

echo "=== CMIS Publishing API Test ==="
echo ""

# Step 1: Login and get session cookies
echo "Step 1: Logging in..."
LOGIN_RESPONSE=$(curl -s -k -c cookies.txt -X POST "${BASE_URL}/login" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Accept: application/json" \
  -d "email=admin@cmis.test" \
  -d "password=password" \
  -d "_token=$(curl -s -k -c cookies-csrf.txt ${BASE_URL}/login | grep -oP 'name="_token" value="\K[^"]+' | head -1)")

if [ $? -eq 0 ]; then
    echo "✅ Login successful"
else
    echo "❌ Login failed"
    exit 1
fi

echo ""

# Step 2: Get connected accounts
echo "Step 2: Fetching connected accounts..."
ACCOUNTS_RESPONSE=$(curl -s -k -b cookies.txt -b cookies-csrf.txt \
  "${BASE_URL}/orgs/${ORG_ID}/social/accounts" \
  -H "Accept: application/json")

echo "$ACCOUNTS_RESPONSE" | python3 -m json.tool > test-results/accounts.json 2>/dev/null || echo "$ACCOUNTS_RESPONSE" > test-results/accounts.txt

# Parse account IDs
INSTAGRAM_ID=$(echo "$ACCOUNTS_RESPONSE" | grep -oP '"integration_id":"[^"]+","platform":"instagram"' | grep -oP 'integration_id":"\K[^"]+' | head -1)
FACEBOOK_ID=$(echo "$ACCOUNTS_RESPONSE" | grep -oP '"integration_id":"[^"]+","platform":"facebook"' | grep -oP 'integration_id":"\K[^"]+' | head -1)

echo "Found accounts:"
echo "  Instagram ID: ${INSTAGRAM_ID:-Not found}"
echo "  Facebook ID: ${FACEBOOK_ID:-Not found}"
echo ""

# Step 3: Upload test image
echo "Step 3: Uploading test image..."

# Create a simple test image if needed
if [ ! -f "test-image.jpg" ] && [ -f "test-error-screenshot.png" ]; then
    cp test-error-screenshot.png test-image.jpg
fi

if [ -f "test-image.jpg" ]; then
    UPLOAD_RESPONSE=$(curl -s -k -b cookies.txt -b cookies-csrf.txt \
      -X POST "${BASE_URL}/orgs/${ORG_ID}/social/media/upload" \
      -F "file=@test-image.jpg" \
      -H "Accept: application/json")

    echo "$UPLOAD_RESPONSE" | python3 -m json.tool > test-results/upload-response.json 2>/dev/null || echo "$UPLOAD_RESPONSE" > test-results/upload-response.txt

    MEDIA_URL=$(echo "$UPLOAD_RESPONSE" | grep -oP '"url":"\K[^"]+' | head -1)
    echo "Media uploaded: ${MEDIA_URL}"
else
    echo "⚠️  No test image found, proceeding without media"
    MEDIA_URL=""
fi

echo ""

# Step 4: Publish post
echo "Step 4: Publishing post..."

# Get CSRF token
CSRF_TOKEN=$(grep XSRF-TOKEN cookies.txt | awk '{print $NF}')

# Prepare post data
POST_CONTENT="Test post from curl automation - $(date +%Y-%m-%d_%H:%M:%S)"

# Build selected_profiles array
SELECTED_PROFILES="[]"
if [ -n "$INSTAGRAM_ID" ] && [ -n "$FACEBOOK_ID" ]; then
    SELECTED_PROFILES="[\"${INSTAGRAM_ID}\",\"${FACEBOOK_ID}\"]"
elif [ -n "$INSTAGRAM_ID" ]; then
    SELECTED_PROFILES="[\"${INSTAGRAM_ID}\"]"
elif [ -n "$FACEBOOK_ID" ]; then
    SELECTED_PROFILES="[\"${FACEBOOK_ID}\"]"
fi

# Build media array
if [ -n "$MEDIA_URL" ]; then
    MEDIA_JSON="[{\"type\":\"image\",\"url\":\"${MEDIA_URL}\"}]"
else
    MEDIA_JSON="[]"
fi

# Publish post
PUBLISH_RESPONSE=$(curl -s -k -b cookies.txt -b cookies-csrf.txt \
  -X POST "${BASE_URL}/orgs/${ORG_ID}/social/publish-modal/create" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: ${CSRF_TOKEN}" \
  -d "{
    \"profile_ids\": ${SELECTED_PROFILES},
    \"content\": {
      \"global\": {
        \"text\": \"${POST_CONTENT}\",
        \"media\": ${MEDIA_JSON},
        \"link\": \"\",
        \"labels\": []
      },
      \"platforms\": {}
    },
    \"is_draft\": false
  }")

echo ""
echo "=== PUBLISH API RESPONSE ==="
echo "$PUBLISH_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$PUBLISH_RESPONSE"
echo "==========================="

# Save response
echo "$PUBLISH_RESPONSE" > test-results/publish-response.json

# Check result
if echo "$PUBLISH_RESPONSE" | grep -q '"success":true'; then
    echo ""
    echo "✅ PUBLISH SUCCESSFUL!"

    # Extract details
    MESSAGE=$(echo "$PUBLISH_RESPONSE" | grep -oP '"message":"\K[^"]+' | head -1)
    echo "Message: ${MESSAGE}"
else
    echo ""
    echo "❌ PUBLISH FAILED!"

    ERROR=$(echo "$PUBLISH_RESPONSE" | grep -oP '"message":"\K[^"]+' | head -1)
    echo "Error: ${ERROR}"

    # Check for validation errors
    if echo "$PUBLISH_RESPONSE" | grep -q '"errors"'; then
        echo ""
        echo "Validation Errors:"
        echo "$PUBLISH_RESPONSE" | python3 -m json.tool 2>/dev/null | grep -A 10 '"errors"'
    fi
fi

echo ""
echo "Full logs saved to test-results/"

# Cleanup
rm -f cookies.txt cookies-csrf.txt
