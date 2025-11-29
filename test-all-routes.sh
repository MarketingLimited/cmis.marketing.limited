#!/bin/bash
echo "Testing CMIS Platform Routes..."
echo "================================"

BASE_URL="https://cmis-test.kazaaz.com"

# Test public routes
routes=(
    "/"
    "/login"
    "/register"
    "/forgot-password"
    "/dashboard"
    "/home"
    "/campaigns"
    "/campaigns/create"
    "/users"
    "/users/profile"
    "/social"
    "/social/posts"
    "/social/schedule"
    "/social/library"
    "/social/analytics"
    "/analytics"
    "/reports"
    "/settings"
    "/integrations"
    "/integrations/meta"
    "/integrations/google"
    "/integrations/tiktok"
    "/integrations/linkedin"
    "/integrations/twitter"
    "/integrations/snapchat"
    "/api/health"
    "/api/status"
)

for route in "${routes[@]}"; do
    url="${BASE_URL}${route}"
    status=$(curl -s -o /dev/null -w "%{http_code}" -L "$url" 2>/dev/null)
    if [ "$status" = "200" ]; then
        echo "✅ $route - $status"
    elif [ "$status" = "302" ] || [ "$status" = "301" ]; then
        echo "🔄 $route - $status (Redirect)"
    elif [ "$status" = "404" ]; then
        echo "❌ $route - $status (Not Found)"
    elif [ "$status" = "403" ]; then
        echo "⚠️  $route - $status (Forbidden)"
    elif [ "$status" = "500" ]; then
        echo "💥 $route - $status (Server Error)"
    else
        echo "❓ $route - $status"
    fi
done
