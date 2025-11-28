#!/bin/bash

# Fix remaining dynamic messages in controllers

BASE_DIR="/home/cmis-test/public_html/app/Http/Controllers"

echo "Fixing dynamic messages in OAuth/OAuthController.php..."

# OAuth Controller - initiate failed
sed -i "s/->with('error', 'Failed to initiate OAuth: ' \. \$e->getMessage())/->with('error', __('oauth.initiate_failed', ['error' => \$e->getMessage()]))/g" \
    "$BASE_DIR/OAuth/OAuthController.php"

# OAuth Controller - auth failed
sed -i "s/->with('error', 'OAuth failed: ' \. \$e->getMessage())/->with('error', __('oauth.auth_failed', ['error' => \$e->getMessage()]))/g" \
    "$BASE_DIR/OAuth/OAuthController.php"

# OAuth Controller - disconnect failed
sed -i "s/->with('error', 'Failed to disconnect: ' \. \$e->getMessage())/->with('error', __('oauth.disconnect_failed', ['error' => \$e->getMessage()]))/g" \
    "$BASE_DIR/OAuth/OAuthController.php"

# OAuth Controller - OAuth error
sed -i 's/throw new \\Exception("OAuth error: \$errorDescription")/throw new \\Exception(__('\''oauth.auth_error'\'', ['\''error'\'' => $errorDescription]))/g' \
    "$BASE_DIR/OAuth/OAuthController.php"

echo "✓ OAuth Controller fixed"

echo "Fixing PlatformConnectionsController.php..."

# Platform connection deleted
sed -i 's/->with('\''success'\'', "{\$platformName} connection deleted successfully")/->with('\''success'\'', __('\''settings.platform_connection_deleted'\'', ['\''platform'\'' => $platformName]))/g' \
    "$BASE_DIR/Settings/PlatformConnectionsController.php"

# Ad accounts found
sed -i "s/return back()->with('success', 'Found ' \. count(\$adAccounts) \. ' ad account(s)')/return back()->with('success', __('settings.ad_accounts_found', ['count' => count(\$adAccounts)]))/g" \
    "$BASE_DIR/Settings/PlatformConnectionsController.php"

# Meta auth denied
sed -i "s/->with('error', 'Meta authorization was denied: ' \. \$request->get('error_description', 'Unknown error'))/->with('error', __('settings.meta_auth_denied', ['error' => \$request->get('error_description', 'Unknown error')]))/g" \
    "$BASE_DIR/Settings/PlatformConnectionsController.php"

# Token validation failed
sed -i "s/->with('error', 'Token validation failed: ' \. (\$tokenInfo\['error'\] ?? 'Unknown error'))/->with('error', __('settings.token_validation_failed', ['error' => (\$tokenInfo['error'] ?? 'Unknown error')]))/g" \
    "$BASE_DIR/Settings/PlatformConnectionsController.php"

# Google auth denied
sed -i "s/->with('error', 'Google authorization was denied: ' \. \$request->get('error_description', \$request->get('error')))/->with('error', __('settings.google_auth_denied', ['error' => \$request->get('error_description', \$request->get('error'))]))/g" \
    "$BASE_DIR/Settings/PlatformConnectionsController.php"

echo "✓ PlatformConnectionsController fixed"

echo "Fixing AdAccountSettingsController.php..."

# Platform connection failed
sed -i "s/throw new \\\\Exception('Platform connection failed: ' \. (\$testResult\['error'\] ?? 'Unknown error'))/throw new \\\\Exception(__('settings.platform_connection_failed', ['error' => (\$testResult['error'] ?? 'Unknown error')]))/g" \
    "$BASE_DIR/Settings/AdAccountSettingsController.php"

# Ad accounts fetch failed
sed -i "s/throw new \\\\Exception('Failed to fetch ad accounts: ' \. (\$syncResult\['error'\] ?? 'Unknown error'))/throw new \\\\Exception(__('settings.ad_accounts_fetch_failed', ['error' => (\$syncResult['error'] ?? 'Unknown error')]))/g" \
    "$BASE_DIR/Settings/AdAccountSettingsController.php"

echo "✓ AdAccountSettingsController fixed"

echo "Fixing SubscriptionController.php..."

sed -i "s/return redirect()->back()->with('warning', 'You are already on the ' \. ucfirst(\$currentPlan) \. ' plan.')/return redirect()->back()->with('warning', __('common.already_on_plan', ['plan' => ucfirst(\$currentPlan)]))/g" \
    "$BASE_DIR/SubscriptionController.php"

echo "✓ SubscriptionController fixed"

echo "Fixing Web controllers..."

# VectorEmbeddingsController
sed -i "s/->with('error', 'فشلت المعالجة: ' \. \$e->getMessage())/->with('error', __('common.processing_failed', ['error' => \$e->getMessage()]))/g" \
    "$BASE_DIR/Web/VectorEmbeddingsController.php"

# TeamWebController
sed -i 's/return back()->with('\''success'\'', "Invitation sent to {\$validated\['\''email'\''\]}!")/return back()->with('\''success'\'', __('\''common.invitation_sent'\'', ['\''email'\'' => $validated['\''email'\'']]))/g' \
    "$BASE_DIR/Web/TeamWebController.php"

echo "✓ Web controllers fixed"

echo "Fixing AI/GPT controllers..."

# AIAssistantController - Gemini API request failed
sed -i "s/throw new \\\\Exception('Gemini API request failed: ' \. \$response->body())/throw new \\\\Exception(__('api.gemini_request_failed', ['error' => \$response->body()]))/g" \
    "$BASE_DIR/API/AIAssistantController.php"

# GPTController - Operation not supported
sed -i 's/throw new \\Exception("Operation {\$operation} not supported for campaigns")/throw new \\Exception(__('\''api.operation_not_supported'\'', ['\''operation'\'' => $operation, '\''resource'\'' => '\''campaigns'\'']))/g' \
    "$BASE_DIR/GPT/GPTController.php"

# GPTController - Unsupported resource type
sed -i 's/throw new \\Exception("Unsupported resource type: {\$resourceType}")/throw new \\Exception(__('\''api.unsupported_resource_type'\'', ['\''type'\'' => $resourceType]))/g' \
    "$BASE_DIR/GPT/GPTController.php"

echo "✓ AI/GPT controllers fixed"

echo ""
echo "✓ All dynamic messages fixed!"
