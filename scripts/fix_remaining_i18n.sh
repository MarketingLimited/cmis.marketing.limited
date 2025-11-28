#!/bin/bash

# Fix remaining hardcoded messages in controllers
# This handles dynamic messages with variables

BASE_DIR="/home/cmis-test/public_html"

echo "Fixing remaining hardcoded messages with variables..."

# Add missing translation keys to language files
echo "Adding translation keys to language files..."

# organizations.php
cat >> "$BASE_DIR/resources/lang/ar/organizations.php" <<'EOF'
    'create_failed' => 'فشل إنشاء المؤسسة: :error',
    'minimum_selection_required' => 'يجب اختيار حملتين على الأقل للمقارنة.',
    'campaigns_not_found' => 'لم يتم العثور على الحملات المحددة.',
EOF

cat >> "$BASE_DIR/resources/lang/en/organizations.php" <<'EOF'
    'create_failed' => 'Failed to create organization: :error',
    'minimum_selection_required' => 'You must select at least two campaigns to compare.',
    'campaigns_not_found' => 'The specified campaigns were not found.',
EOF

# auth.php
cat >> "$BASE_DIR/resources/lang/ar/auth.php" <<'EOF'
    'invitation_welcome' => 'مرحباً بك في :org_name! لقد انضممت بنجاح إلى المؤسسة.',
EOF

cat >> "$BASE_DIR/resources/lang/en/auth.php" <<'EOF'
    'invitation_welcome' => 'Welcome to :org_name! You have successfully joined the organization.',
EOF

# oauth.php
cat >> "$BASE_DIR/resources/lang/ar/oauth.php" <<'EOF'
    'initiate_failed' => 'فشل بدء OAuth: :error',
    'auth_failed' => 'فشل OAuth: :error',
    'disconnect_failed' => 'فشل قطع الاتصال: :error',
    'auth_error' => 'خطأ OAuth: :error',
EOF

cat >> "$BASE_DIR/resources/lang/en/oauth.php" <<'EOF'
    'initiate_failed' => 'Failed to initiate OAuth: :error',
    'auth_failed' => 'OAuth failed: :error',
    'disconnect_failed' => 'Failed to disconnect: :error',
    'auth_error' => 'OAuth error: :error',
EOF

# settings.php
cat >> "$BASE_DIR/resources/lang/ar/settings.php" <<'EOF'
    'platform_connection_deleted' => 'تم حذف اتصال :platform بنجاح',
    'ad_accounts_found' => 'تم العثور على :count حساب إعلاني',
    'meta_auth_denied' => 'تم رفض تفويض Meta: :error',
    'token_validation_failed' => 'فشل التحقق من الرمز المميز: :error',
    'google_auth_denied' => 'تم رفض تفويض Google: :error',
    'platform_connection_failed' => 'فشل اتصال المنصة: :error',
    'ad_accounts_fetch_failed' => 'فشل جلب حسابات الإعلانات: :error',
EOF

cat >> "$BASE_DIR/resources/lang/en/settings.php" <<'EOF'
    'platform_connection_deleted' => ':platform connection deleted successfully',
    'ad_accounts_found' => 'Found :count ad account(s)',
    'meta_auth_denied' => 'Meta authorization was denied: :error',
    'token_validation_failed' => 'Token validation failed: :error',
    'google_auth_denied' => 'Google authorization was denied: :error',
    'platform_connection_failed' => 'Platform connection failed: :error',
    'ad_accounts_fetch_failed' => 'Failed to fetch ad accounts: :error',
EOF

# common.php (for subscription messages)
cat >> "$BASE_DIR/resources/lang/ar/common.php" <<'EOF'
    'already_on_plan' => 'أنت بالفعل على خطة :plan.',
    'invitation_sent' => 'تم إرسال الدعوة إلى :email!',
    'processing_failed' => 'فشلت المعالجة: :error',
EOF

cat >> "$BASE_DIR/resources/lang/en/common.php" <<'EOF'
    'already_on_plan' => 'You are already on the :plan plan.',
    'invitation_sent' => 'Invitation sent to :email!',
    'processing_failed' => 'Processing failed: :error',
EOF

# api.php (for AI/GPT errors)
cat >> "$BASE_DIR/resources/lang/ar/api.php" <<'EOF'
    'gemini_request_failed' => 'فشل طلب Gemini API: :error',
    'operation_not_supported' => 'العملية :operation غير مدعومة لـ :resource',
    'unsupported_resource_type' => 'نوع المورد غير مدعوم: :type',
EOF

cat >> "$BASE_DIR/resources/lang/en/api.php" <<'EOF'
    'gemini_request_failed' => 'Gemini API request failed: :error',
    'operation_not_supported' => 'Operation :operation not supported for :resource',
    'unsupported_resource_type' => 'Unsupported resource type: :type',
EOF

echo "✓ Translation keys added to language files"
