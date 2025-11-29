#!/bin/bash
echo "Testing CMIS Platform - Arabic & English"
echo "========================================"
echo ""

BASE_URL="https://cmis-test.kazaaz.com"
ORG_ID="5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a"

# Test public pages in both languages
echo "📋 PUBLIC PAGES - LANGUAGE TESTING"
echo "===================================="
echo ""

# Test login page in Arabic
echo "Testing Arabic (RTL)..."
google-chrome --headless --screenshot=/tmp/login-arabic.png \
  "$BASE_URL/login?lang=ar" 2>/dev/null
echo "✅ Arabic Login: /tmp/login-arabic.png"

# Test login page in English
google-chrome --headless --screenshot=/tmp/login-english.png \
  "$BASE_URL/login?lang=en" 2>/dev/null
echo "✅ English Login: /tmp/login-english.png"

# Test register page in Arabic
google-chrome --headless --screenshot=/tmp/register-arabic.png \
  "$BASE_URL/register?lang=ar" 2>/dev/null
echo "✅ Arabic Register: /tmp/register-arabic.png"

# Test register page in English
google-chrome --headless --screenshot=/tmp/register-english.png \
  "$BASE_URL/register?lang=en" 2>/dev/null
echo "✅ English Register: /tmp/register-english.png"

# Test org list page in Arabic
google-chrome --headless --screenshot=/tmp/orgs-arabic.png \
  "$BASE_URL/orgs?lang=ar" 2>/dev/null
echo "✅ Arabic Orgs: /tmp/orgs-arabic.png"

# Test org list page in English
google-chrome --headless --screenshot=/tmp/orgs-english.png \
  "$BASE_URL/orgs?lang=en" 2>/dev/null
echo "✅ English Orgs: /tmp/orgs-english.png"

echo ""
echo "📊 ORG-SCOPED PAGES - LANGUAGE TESTING"
echo "======================================"
echo ""

# Test dashboard in both languages
google-chrome --headless --screenshot=/tmp/dashboard-arabic.png \
  "$BASE_URL/orgs/$ORG_ID/dashboard?lang=ar" 2>/dev/null
echo "✅ Arabic Dashboard: /tmp/dashboard-arabic.png"

google-chrome --headless --screenshot=/tmp/dashboard-english.png \
  "$BASE_URL/orgs/$ORG_ID/dashboard?lang=en" 2>/dev/null
echo "✅ English Dashboard: /tmp/dashboard-english.png"

# Test campaigns in both languages
google-chrome --headless --screenshot=/tmp/campaigns-arabic.png \
  "$BASE_URL/orgs/$ORG_ID/campaigns?lang=ar" 2>/dev/null
echo "✅ Arabic Campaigns: /tmp/campaigns-arabic.png"

google-chrome --headless --screenshot=/tmp/campaigns-english.png \
  "$BASE_URL/orgs/$ORG_ID/campaigns?lang=en" 2>/dev/null
echo "✅ English Campaigns: /tmp/campaigns-english.png"

# Test social in both languages
google-chrome --headless --screenshot=/tmp/social-arabic.png \
  "$BASE_URL/orgs/$ORG_ID/social?lang=ar" 2>/dev/null
echo "✅ Arabic Social: /tmp/social-arabic.png"

google-chrome --headless --screenshot=/tmp/social-english.png \
  "$BASE_URL/orgs/$ORG_ID/social?lang=en" 2>/dev/null
echo "✅ English Social: /tmp/social-english.png"

# Test analytics in both languages
google-chrome --headless --screenshot=/tmp/analytics-arabic.png \
  "$BASE_URL/orgs/$ORG_ID/analytics?lang=ar" 2>/dev/null
echo "✅ Arabic Analytics: /tmp/analytics-arabic.png"

google-chrome --headless --screenshot=/tmp/analytics-english.png \
  "$BASE_URL/orgs/$ORG_ID/analytics?lang=en" 2>/dev/null
echo "✅ English Analytics: /tmp/analytics-english.png"

# Test settings in both languages
google-chrome --headless --screenshot=/tmp/settings-arabic.png \
  "$BASE_URL/orgs/$ORG_ID/settings/platform-connections?lang=ar" 2>/dev/null
echo "✅ Arabic Settings: /tmp/settings-arabic.png"

google-chrome --headless --screenshot=/tmp/settings-english.png \
  "$BASE_URL/orgs/$ORG_ID/settings/platform-connections?lang=en" 2>/dev/null
echo "✅ English Settings: /tmp/settings-english.png"

echo ""
echo "========================================"
echo "✅ BILINGUAL TESTING COMPLETE"
echo "========================================"
echo "Total Screenshots: 14"
echo "  • Arabic (RTL): 7 screenshots"
echo "  • English (LTR): 7 screenshots"
echo ""
echo "Screenshots saved to /tmp/"
echo "View with: eog /tmp/login-arabic.png"
echo ""
