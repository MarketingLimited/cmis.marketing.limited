#!/bin/bash
echo "Testing Org-Scoped Routes..."
echo "============================"
echo ""

ORG_ID="5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a"
BASE_URL="https://cmis-test.kazaaz.com"

# Test routes (without auth)
routes=(
    "/orgs"
    "/orgs/$ORG_ID"
    "/orgs/$ORG_ID/dashboard"
    "/orgs/$ORG_ID/campaigns"
    "/orgs/$ORG_ID/campaigns/create"
    "/orgs/$ORG_ID/analytics"
    "/orgs/$ORG_ID/analytics/realtime"
    "/orgs/$ORG_ID/analytics/kpis"
    "/orgs/$ORG_ID/influencer"
    "/orgs/$ORG_ID/influencer/create"
    "/orgs/$ORG_ID/orchestration"
    "/orgs/$ORG_ID/listening"
    "/orgs/$ORG_ID/creative/assets"
    "/orgs/$ORG_ID/creative/briefs"
    "/orgs/$ORG_ID/creative/briefs/create"
    "/orgs/$ORG_ID/social"
    "/orgs/$ORG_ID/social/posts"
    "/orgs/$ORG_ID/social/scheduler"
    "/orgs/$ORG_ID/social/history"
    "/orgs/$ORG_ID/settings/user"
    "/orgs/$ORG_ID/settings/organization"
    "/orgs/$ORG_ID/settings/platform-connections"
    "/orgs/$ORG_ID/settings/profile-groups"
    "/orgs/$ORG_ID/settings/profile-groups/create"
    "/orgs/$ORG_ID/settings/brand-voices"
    "/orgs/$ORG_ID/settings/brand-voices/create"
    "/orgs/$ORG_ID/settings/brand-safety"
    "/orgs/$ORG_ID/settings/brand-safety/create"
    "/orgs/$ORG_ID/settings/approval-workflows"
    "/orgs/$ORG_ID/settings/approval-workflows/create"
    "/orgs/$ORG_ID/settings/boost-rules"
    "/orgs/$ORG_ID/settings/boost-rules/create"
    "/orgs/$ORG_ID/settings/ad-accounts"
    "/orgs/$ORG_ID/team"
    "/orgs/$ORG_ID/products"
    "/orgs/$ORG_ID/workflows"
    "/orgs/$ORG_ID/ai"
    "/orgs/$ORG_ID/knowledge"
    "/orgs/$ORG_ID/knowledge/create"
    "/orgs/$ORG_ID/predictive"
    "/orgs/$ORG_ID/experiments"
    "/orgs/$ORG_ID/optimization"
    "/orgs/$ORG_ID/automation"
    "/orgs/$ORG_ID/alerts"
    "/orgs/$ORG_ID/exports"
    "/orgs/$ORG_ID/dashboard-builder"
    "/orgs/$ORG_ID/feature-flags"
    "/orgs/$ORG_ID/inbox"
    "/profile"
)

total=0
ok_200=0
redirect_302=0
not_found_404=0
forbidden_403=0
server_error_500=0
other=0

for route in "${routes[@]}"; do
    url="${BASE_URL}${route}"
    status=$(curl -s -o /dev/null -w "%{http_code}" -L "$url" 2>/dev/null)
    total=$((total + 1))
    
    if [ "$status" = "200" ]; then
        echo "✅ $route - $status OK"
        ok_200=$((ok_200 + 1))
    elif [ "$status" = "302" ] || [ "$status" = "301" ]; then
        echo "🔄 $route - $status (Redirect to login)"
        redirect_302=$((redirect_302 + 1))
    elif [ "$status" = "404" ]; then
        echo "❌ $route - $status (Not Found)"
        not_found_404=$((not_found_404 + 1))
    elif [ "$status" = "403" ]; then
        echo "⚠️  $route - $status (Forbidden)"
        forbidden_403=$((forbidden_403 + 1))
    elif [ "$status" = "500" ]; then
        echo "💥 $route - $status (Server Error)"
        server_error_500=$((server_error_500 + 1))
    else
        echo "❓ $route - $status"
        other=$((other + 1))
    fi
done

echo ""
echo "============================"
echo "SUMMARY"
echo "============================"
echo "Total Routes Tested: $total"
echo "✅ 200 OK: $ok_200"
echo "🔄 Redirects (302/301): $redirect_302"
echo "❌ 404 Not Found: $not_found_404"
echo "⚠️  403 Forbidden: $forbidden_403"
echo "💥 500 Server Error: $server_error_500"
echo "❓ Other: $other"
echo ""
echo "Success Rate: $(awk "BEGIN {printf \"%.1f\", ($ok_200 + $redirect_302) / $total * 100}")%"
