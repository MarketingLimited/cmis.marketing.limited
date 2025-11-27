<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | English translations for all settings-related pages
    |
    */

    // Page Titles
    'user_settings' => 'User Settings',
    'organization_settings' => 'Organization Settings',
    'manage_personal_settings' => 'Manage your personal account settings and preferences',
    'manage_organization_settings' => 'Manage your organization, team, and integrations',

    // User Settings Tabs
    'profile' => 'Profile',
    'notifications' => 'Notifications',
    'security' => 'Security',

    // Organization Settings Tabs
    'general' => 'General',
    'team_members' => 'Team Members',
    'api_keys' => 'API Keys',
    'billing' => 'Billing',
    'platform_connections' => 'Platform Connections',

    // Profile Section
    'profile_information' => 'Profile Information',
    'update_personal_information' => 'Update your personal information and preferences',
    'profile_photo' => 'Profile Photo',
    'jpg_png_gif_max_2mb' => 'JPG, PNG or GIF. Max 2MB',
    'change_photo' => 'Change Photo',
    'full_name' => 'Full Name',
    'display_name' => 'Display Name',
    'how_name_appears' => 'How your name appears to others',
    'email_address' => 'Email Address',
    'language' => 'Language',
    'timezone' => 'Timezone',
    'save_changes' => 'Save Changes',

    // Notifications Section
    'notification_preferences' => 'Notification Preferences',
    'choose_notification_method' => 'Choose how and when you want to be notified',
    'email_notifications' => 'Email Notifications',
    'in_app_notifications' => 'In-App Notifications',

    // Email Notifications
    'campaign_alerts' => 'Campaign alerts',
    'campaign_alerts_desc' => 'Get notified when campaigns start, end, or need attention',
    'performance_reports' => 'Performance reports',
    'performance_reports_desc' => 'Weekly and monthly performance summaries',
    'budget_alerts' => 'Budget alerts',
    'budget_alerts_desc' => 'Get notified when budgets are running low',
    'team_activity' => 'Team activity',
    'team_activity_desc' => 'Updates when team members make changes',

    // In-App Notifications
    'realtime_alerts' => 'Real-time alerts',
    'realtime_alerts_desc' => 'Show notifications in the app as they happen',
    'sound_notifications' => 'Sound notifications',
    'sound_notifications_desc' => 'Play a sound for important alerts',
    'save_preferences' => 'Save Preferences',

    // Security Section
    'change_password' => 'Change Password',
    'update_password_secure' => 'Update your password to keep your account secure',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'minimum_8_characters' => 'Minimum 8 characters',
    'confirm_new_password' => 'Confirm New Password',
    'update_password' => 'Update Password',

    'active_sessions' => 'Active Sessions',
    'manage_active_sessions' => 'Manage your active sessions across devices',
    'current' => 'Current',
    'last_active' => 'Last active',
    'revoke' => 'Revoke',
    'no_active_sessions' => 'No active sessions found',

    'two_factor_authentication' => 'Two-Factor Authentication',
    'add_extra_security' => 'Add an extra layer of security to your account',
    'status' => 'Status',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'protect_account_2fa' => 'Protect your account with TOTP-based 2FA',
    'disable_2fa' => 'Disable 2FA',
    'enable_2fa' => 'Enable 2FA',

    // Organization Details
    'organization_details' => 'Organization Details',
    'manage_organization_info' => 'Manage your organization information and preferences',
    'organization_name' => 'Organization Name',
    'default_currency' => 'Default Currency',
    'default_language' => 'Default Language',
    'organization_information' => 'Organization Information',
    'organization_id' => 'Organization ID',
    'created' => 'Created',
    'active_campaigns' => 'Active Campaigns',

    // Team Members
    'manage_team_access' => 'Manage who has access to this organization',
    'invite_member' => 'Invite Member',
    'you' => 'You',
    'member' => 'Member',
    'remove' => 'Remove',
    'no_team_members' => 'No team members found',
    'are_you_sure_remove_member' => 'Are you sure you want to remove this member?',

    // API Keys
    'manage_api_keys' => 'Manage API keys for programmatic access',
    'create_api_key' => 'Create API Key',
    'last_used' => 'Last used',
    'expires' => 'Expires',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'are_you_sure_revoke_key' => 'Are you sure you want to revoke this API key?',
    'no_api_keys' => 'No API keys yet',
    'create_key_access_api' => 'Create an API key to access the CMIS API programmatically',
    'api_documentation' => 'API Documentation',
    'learn_how_use_api' => 'Learn how to use the CMIS API to automate your campaigns and analytics.',
    'view_documentation' => 'View Documentation',

    // Billing
    'current_plan' => 'Current Plan',
    'subscription_renews_on' => 'Your subscription renews on',
    'upgrade_plan' => 'Upgrade Plan',
    'campaigns' => 'Campaigns',
    'api_calls' => 'API Calls',

    'payment_method' => 'Payment Method',
    'visa_ending_in' => 'Visa ending in',
    'update' => 'Update',

    'billing_history' => 'Billing History',
    'date' => 'Date',
    'description' => 'Description',
    'amount' => 'Amount',
    'invoice' => 'Invoice',
    'download' => 'Download',
    'no_invoices' => 'No invoices yet',

    // Modals
    'create_new_api_key' => 'Create New API Key',
    'give_key_name_permissions' => 'Give your API key a name and select the permissions it needs.',
    'key_name' => 'Key Name',
    'key_name_placeholder' => 'e.g., Production Server, CI/CD Pipeline',
    'permissions' => 'Permissions',
    'expiration_optional' => 'Expiration (Optional)',
    'leave_empty_no_expiration' => 'Leave empty for no expiration',
    'cancel' => 'Cancel',
    'create_key' => 'Create Key',

    'invite_team_member' => 'Invite Team Member',
    'send_invitation_join' => 'Send an invitation to join your organization.',
    'role' => 'Role',
    'send_invitation' => 'Send Invitation',

    // Flash Messages
    'profile_updated_success' => 'Profile updated successfully',
    'failed_update_profile' => 'Failed to update profile',
    'notification_preferences_updated' => 'Notification preferences updated successfully',
    'failed_update_notifications' => 'Failed to update notification preferences',
    'password_updated_success' => 'Password updated successfully',
    'current_password_incorrect' => 'Current password is incorrect',
    'failed_update_password' => 'Failed to update password',
    'session_revoked_success' => 'Session revoked successfully',
    'failed_revoke_session' => 'Failed to revoke session',
    'organization_updated_success' => 'Organization settings updated successfully',
    'failed_update_organization' => 'Failed to update organization settings',
    'api_key_created_success' => 'API key created successfully. Your key:',
    'copy_now_wont_show_again' => '(Copy it now, it won\'t be shown again)',
    'failed_create_api_key' => 'Failed to create API key',
    'api_key_revoked_success' => 'API key revoked successfully',
    'failed_revoke_api_key' => 'Failed to revoke API key',
    'invitation_sent_success' => 'Invitation sent successfully',
    'user_already_member' => 'This user is already a member of the organization',
    'failed_send_invitation' => 'Failed to send invitation',
    'team_member_removed_success' => 'Team member removed successfully',
    'cannot_remove_yourself' => 'You cannot remove yourself from the organization',
    'failed_remove_team_member' => 'Failed to remove team member',

    // Languages
    'arabic' => 'العربية',
    'english' => 'English',
    'arabic_bahrain' => 'العربية (البحرين)',
    'arabic_saudi' => 'العربية (السعودية)',
    'arabic_uae' => 'العربية (الإمارات)',
    'english_us' => 'English (US)',
    'english_uk' => 'English (UK)',

    // Currencies
    'bhd' => 'BHD - Bahraini Dinar',
    'usd' => 'USD - US Dollar',
    'eur' => 'EUR - Euro',
    'sar' => 'SAR - Saudi Riyal',
    'aed' => 'AED - UAE Dirham',
    'kwd' => 'KWD - Kuwaiti Dinar',
    'qar' => 'QAR - Qatari Riyal',
    'omr' => 'OMR - Omani Rial',

];
