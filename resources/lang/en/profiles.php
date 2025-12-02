<?php

return [
    // Page titles
    'title' => 'Profile Management',
    'subtitle' => 'This is a list of connected social profiles across your profile groups.',
    'configure_subtitle' => 'Configure name, profile groups, publishing queue, custom fields and boost settings.',

    // Filters
    'search_placeholder' => 'Search by name',
    'select_network' => 'Select network',
    'select_status' => 'Select status',
    'select_group' => 'Select group',
    'all_networks' => 'All networks',
    'all_statuses' => 'All statuses',
    'all_groups' => 'All groups',

    // Table headers
    'name' => 'Name',
    'profile_group' => 'Profile group',
    'connected' => 'Connected',
    'status' => 'Status',
    'actions' => 'Actions',

    // Status badges
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'status_error' => 'Error',

    // Actions
    'refresh_connection' => 'Refresh connection',
    'view_profile' => 'View profile',
    'manage_groups' => 'Manage groups',
    'remove_profile' => 'Remove profile',
    'update_image' => 'Update image',
    'remove' => 'Remove',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'close' => 'Close',

    // Profile detail
    'type' => 'Type',
    'team_member' => 'Team member',
    'industry' => 'Industry',
    'timezone' => 'Timezone',
    'timezone_updated' => 'Timezone updated successfully',
    'inherited_timezone' => 'Inherited from parent',
    'not_set' => 'Not set',
    'facebook_user' => 'Facebook user',
    'connected_by' => 'Connected by',

    // Profile types
    'type_business' => 'Business',
    'type_personal' => 'Personal',
    'type_creator' => 'Creator',

    // Sections
    'profile_groups' => 'PROFILE GROUPS',
    'manage_profile_groups' => 'Manage profile groups',
    'publishing_queues' => 'PUBLISHING QUEUES',
    'queue_settings' => 'Queue Settings',
    'boost_settings' => 'BOOST SETTINGS',
    'add_boost' => 'Add boost',
    'custom_fields' => 'Custom Fields',
    'add_custom_field' => 'Add custom field',

    // Queue settings
    'queue_settings_description' => 'Configure when your posts will be published automatically',
    'queue_enabled' => 'Queue enabled',
    'enable_publishing_queue' => 'Enable publishing queue',
    'queue_enabled_description' => 'When enabled, posts will be automatically scheduled at the configured times.',
    'posting_times' => 'Posting times',
    'days_enabled' => 'Days enabled',
    'posts_per_day' => 'Posts per day',
    'add_time' => 'Add time',
    'add_time_slot' => 'Add time slot',
    'add_first_time' => 'Add your first time slot',
    'no_times_set' => 'No times set for this day',
    'quick_add_times' => 'Quick add times to enabled days',
    'days_active' => 'days active',
    'time_slot' => 'slot',
    'time_slots' => 'slots',
    'time_slots_total' => 'time slots total',
    'setup_queue' => 'Setup Queue',
    'add_time_all_days' => 'Add time to all enabled days',
    'apply_to_weekdays' => 'Copy Monday to weekdays',
    'clear_all' => 'Clear all times',
    'confirm_clear_all_times' => 'Are you sure you want to clear all time slots?',
    'queue_disabled' => 'Queue is disabled',
    'queue_disabled_description' => 'Enable the queue to schedule posts automatically at specific times',
    'queue_settings_saved' => 'Queue settings saved successfully',

    // Queue Slot Labels
    'all_labels' => 'All labels',
    'any_labels' => 'Any labels',
    'labels' => 'Labels',
    'manage_labels' => 'Manage labels',
    'add_label' => 'Add label',
    'edit_label' => 'Edit label',
    'label_name' => 'Label name',
    'label_name_placeholder' => 'Enter label name',
    'search_labels' => 'Search labels',
    'select_label' => 'Select label',
    'no_label' => 'No label',
    'create_new_label' => 'Create ":name"',
    'no_labels' => 'No labels yet',
    'no_labels_message' => 'Create labels to organize your time slots',

    // Label Colors
    'solid_color' => 'Solid',
    'gradient_color' => 'Gradient',
    'text_color' => 'Text color',
    'background_color' => 'Background color',
    'select_background_color' => 'Select background color',
    'select_text_color' => 'Select text color',
    'label_preview' => 'Preview',

    // Enhanced Time Slot
    'create_time_slot' => 'Create time slot',
    'select_days' => 'Select days',
    'mark_as_evergreen' => 'Mark as evergreen',
    'evergreen_description' => 'Evergreen content recycles when queue is empty',
    'evergreen' => 'Evergreen',

    // Label Filtering
    'filter_by_label' => 'Filter by label',
    'showing_label' => 'Showing: :label',
    'clear_filter' => 'Clear filter',

    // Label Messages
    'labels_retrieved' => 'Labels retrieved successfully',
    'labels_load_failed' => 'Failed to load labels',
    'label_created' => 'Label created successfully',
    'label_create_failed' => 'Failed to create label',
    'label_updated' => 'Label updated successfully',
    'label_update_failed' => 'Failed to update label',
    'label_deleted' => 'Label deleted successfully',
    'label_delete_failed' => 'Failed to delete label',
    'label_not_found' => 'Label not found',
    'labels_reordered' => 'Labels reordered successfully',
    'labels_reorder_failed' => 'Failed to reorder labels',
    'presets_retrieved' => 'Color presets retrieved',
    'presets_load_failed' => 'Failed to load color presets',

    // Label Confirmations
    'confirm_delete_label' => 'Delete this label? It will be removed from all time slots.',

    // Days of week
    'sunday' => 'Sunday',
    'monday' => 'Monday',
    'tuesday' => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday' => 'Thursday',
    'friday' => 'Friday',
    'saturday' => 'Saturday',

    // Boost modal
    'create_boost' => 'Create boost',
    'edit_boost' => 'Edit boost',
    'boost_name' => 'Boost name',
    'boost_name_placeholder' => 'Describe your boost',
    'boost_delay' => 'Boost delay after publishing',
    'profile' => 'Profile',
    'ad_account' => 'Ad account',
    'budget' => 'Budget',
    'campaign_days' => 'Campaign days',
    'number_of_days' => 'Number of days',
    'budget_note' => 'All amounts (i.e. budget, bid amount) are expressed in (selected ad account\'s currency).',
    'hours' => 'Hours',
    'days' => 'Days',

    // Audience targeting
    'included_audiences' => 'Included audiences',
    'excluded_audiences' => 'Excluded audiences',
    'select_audiences' => 'Select audiences',
    'interests' => 'Interests',
    'search_interests' => 'Search for interests',
    'work_positions' => 'Work positions',
    'search_work_positions' => 'Search for work positions',
    'countries' => 'Countries',
    'search_countries' => 'Search for countries',
    'cities' => 'Cities',
    'search_cities' => 'Search for cities',
    'genders' => 'Genders',
    'select_genders' => 'Select genders',
    'min_age' => 'Min age',
    'max_age' => 'Max age',
    'male' => 'Male',
    'female' => 'Female',
    'all_genders' => 'All genders',

    // Ad Account selection
    'select_ad_account' => 'Select ad account',
    'no_ad_accounts_available' => 'No ad accounts available',
    'connect_ad_account_first' => 'Connect an ad account from Platform Connections first',

    // Campaign objectives
    'campaign_objective' => 'Campaign objective',
    'select_objective' => 'Select objective',
    'objective_awareness' => 'Brand Awareness',
    'objective_awareness_desc' => 'Show your ads to people most likely to remember them.',
    'objective_engagement' => 'Engagement',
    'objective_engagement_desc' => 'Get more post engagement, likes, comments and shares.',
    'objective_traffic' => 'Traffic',
    'objective_traffic_desc' => 'Send people to a destination, like your website or app.',
    'objective_leads' => 'Lead Generation',
    'objective_leads_desc' => 'Collect leads for your business.',
    'objective_sales' => 'Sales',
    'objective_sales_desc' => 'Find people likely to purchase your product or service.',
    'objective_app' => 'App Promotion',
    'objective_app_desc' => 'Get more app installs and activity.',

    // Boost delay
    'boost_delay_hint' => 'Time to wait after a post is published before boosting.',
    'enter_budget' => 'Enter amount',

    // Advantage+ settings
    'advantage_plus_audience' => 'Advantage+ Audience',
    'advantage_plus_description' => 'Let Meta\'s AI optimize your audience for better results.',
    'audience_expansion' => 'Audience expansion',
    'auto_placements' => 'Advantage+ placements',
    'dynamic_creative' => 'Dynamic creative',

    // Audience targeting
    'audience_targeting' => 'Audience targeting',
    'custom_audiences' => 'Custom audiences',
    'loading_audiences' => 'Loading audiences',
    'custom_audiences_hint' => 'Target people who have already interacted with your business.',
    'lookalike_audiences' => 'Lookalike audiences',
    'lookalike_audiences_hint' => 'Target people similar to your existing customers.',
    'excluded_audiences_hint' => 'Exclude specific audiences from seeing your ads.',
    'audiences_available' => 'audiences available',
    'no_custom_audiences' => 'No custom audiences found',
    'no_lookalike_audiences' => 'No lookalike audiences found',
    'no_audiences_to_exclude' => 'No audiences available to exclude',
    'detailed_targeting' => 'Detailed targeting',

    // Boost actions
    'save_boost' => 'Save boost',
    'delete_boost' => 'Delete boost',
    'enable_boost' => 'Enable boost',
    'disable_boost' => 'Disable boost',

    // Trigger types
    'trigger_manual' => 'Manual',
    'trigger_auto_after_publish' => 'Auto after publish',
    'trigger_auto_performance' => 'Auto based on performance',

    // Stats
    'total_profiles' => 'Total profiles',
    'active_profiles' => 'Active profiles',
    'inactive_profiles' => 'Inactive profiles',
    'with_groups' => 'With groups',
    'without_groups' => 'Without groups',

    // Messages
    'profile_not_found' => 'Profile not found',
    'profiles_retrieved' => 'Profiles retrieved successfully',
    'profile_retrieved' => 'Profile retrieved successfully',
    'profile_updated' => 'Profile updated successfully',
    'avatar_updated' => 'Avatar updated successfully',
    'avatar_upload_failed' => 'Failed to upload avatar',
    'assign_group_failed' => 'Failed to assign profile to group',
    'group_assigned' => 'Profile assigned to group successfully',
    'group_removed' => 'Profile removed from group successfully',
    'status_toggled' => 'Profile status toggled successfully',
    'connection_refreshed' => 'Connection refreshed successfully',
    'profile_removed' => 'Profile removed successfully',
    'queue_settings_retrieved' => 'Queue settings retrieved successfully',
    'queue_settings_updated' => 'Queue settings updated successfully',
    'boost_rules_retrieved' => 'Boost rules retrieved successfully',
    'profile_must_be_in_group' => 'Profile must be assigned to a group before creating boost rules',
    'boost_created' => 'Boost rule created successfully',
    'boost_create_failed' => 'Failed to create boost rule',
    'boost_not_found' => 'Boost rule not found',
    'boost_updated' => 'Boost rule updated successfully',
    'boost_update_failed' => 'Failed to update boost rule',
    'boost_deleted' => 'Boost rule deleted successfully',
    'boost_toggled' => 'Boost rule status toggled successfully',
    'stats_retrieved' => 'Profile statistics retrieved successfully',

    // Confirmations
    'confirm_remove' => 'Are you sure you want to remove this profile?',
    'confirm_remove_from_group' => 'Are you sure you want to remove this profile from the group?',
    'confirm_delete_boost' => 'Are you sure you want to delete this boost rule?',

    // Empty states
    'no_profiles' => 'No profiles found',
    'no_profiles_message' => 'Connect your social accounts from Platform Connections to get started.',
    'no_groups' => 'No profile groups',
    'no_groups_message' => 'This profile is not assigned to any group.',
    'no_boosts' => 'No boost rules',
    'no_boosts_message' => 'Create a boost rule to automatically promote your posts.',
    'no_queue' => 'No queue settings',
    'no_queue_message' => 'Configure queue settings to schedule posts automatically.',

    // Platform-specific boost features
    'loading_platform_config' => 'Loading platform configuration',
    'boost_config_retrieved' => 'Boost configuration retrieved successfully',
    'boost_config_failed' => 'Failed to load boost configuration',
    'ad_account_required' => 'Ad account is required',
    'ad_account_not_found' => 'Ad account not found',
    'ad_account_not_connected' => 'Ad account is not connected. Please reconnect your ad account in Platform Connections.',
    'platform' => 'Platform',
    'ad_accounts_retrieved' => 'Ad accounts retrieved successfully',
    'audiences_retrieved' => 'Audiences retrieved successfully',
    'budget_validated' => 'Budget validated',
    'exceeds_daily_limit' => 'Daily budget (:limit :currency) may be exceeded',
    'exceeds_balance' => 'Budget exceeds account balance (:balance :currency)',
    'may_exceed_monthly' => 'May exceed monthly remaining budget (:remaining)',
    'exceeds_spend_cap' => 'Budget exceeds daily spend cap',

    // TikTok Spark Ads
    'spark_ads' => 'Spark Ads',
    'spark_ads_description' => 'Boost organic TikTok posts as ads for authentic engagement.',

    // Placements
    'placements' => 'Placements',
    'auto_placements_recommended' => 'Automatic placements (recommended)',

    // Bidding
    'bidding_strategy' => 'Bidding strategy',
    'auto_bidding' => 'Automatic bidding',

    // B2B Targeting (LinkedIn)
    'b2b_targeting' => 'B2B Targeting',
    'job_titles' => 'Job titles',
    'enter_job_titles' => 'Enter job titles (comma separated)',
    'company_size' => 'Company size',
    'any_size' => 'Any size',
    'seniority' => 'Seniority level',
    'any_seniority' => 'Any seniority',
    'industries' => 'Industries',
    'enter_industries' => 'Enter industries (comma separated)',

    // Pinterest
    'pinterest_ads' => 'Pinterest Ads',
    'actalike_audiences' => 'Actalike audiences',
    'actalike_description' => 'Pinterest lookalike audiences based on your best customers.',

    // Validation messages
    'invalid_objective_for_platform' => 'Invalid objective for :platform',
    'minimum_budget_for_platform' => 'Minimum budget for :platform is :amount',
    'invalid_placement' => 'Invalid placement: :placement',
    'invalid_bidding_strategy' => 'Invalid bidding strategy',
    'linkedin_audience_size_warning' => 'LinkedIn requires minimum audience size of :size',
    'spark_ads_requires_post' => 'Spark Ads requires an organic post ID',

    // Destination Types
    'destination_type' => 'Conversion Location',
    'destination_type_description' => 'Where do you want to drive results?',
    'loading_messaging_accounts' => 'Loading messaging accounts',
    'messaging_accounts_retrieved' => 'Messaging accounts retrieved successfully',
    'messaging_accounts_failed' => 'Failed to load messaging accounts',

    // Destination type options
    'destination_website' => 'Website',
    'destination_app' => 'App',
    'destination_messenger' => 'Messenger',
    'destination_whatsapp' => 'WhatsApp',
    'destination_instagram_direct' => 'Instagram Direct',
    'destination_calls' => 'Phone Calls',
    'destination_instant_forms' => 'Instant Forms',
    'destination_on_ad' => 'On Ad',
    'destination_on_post' => 'On Post',
    'destination_on_page' => 'On Page',
    'destination_app_and_website' => 'App and Website',
    'destination_shopping' => 'Shopping',

    // Destination type fields
    'destination_url' => 'Website URL',
    'whatsapp_number' => 'WhatsApp Number',
    'select_whatsapp_number' => 'Select a WhatsApp number',
    'connect_new_whatsapp' => 'Connect new WhatsApp number',
    'facebook_page' => 'Facebook Page',
    'select_facebook_page' => 'Select a Facebook Page',
    'phone_number' => 'Phone Number',
    'app_id' => 'App ID',
    'enter_app_id' => 'Enter your app ID',
    'lead_form' => 'Lead Form',
    'enter_form_id' => 'Enter form ID (optional)',
    'form_id_hint' => 'Leave empty to create a new form or enter existing form ID',

    // Meta Targeting API
    'interests_retrieved' => 'Interests retrieved successfully',
    'behaviors_retrieved' => 'Behaviors retrieved successfully',
    'locations_retrieved' => 'Locations retrieved successfully',
    'work_positions_retrieved' => 'Work positions retrieved successfully',
    'interests_fetch_failed' => 'Failed to fetch interests',
    'behaviors_fetch_failed' => 'Failed to fetch behaviors',
    'locations_fetch_failed' => 'Failed to fetch locations',
    'work_positions_fetch_failed' => 'Failed to fetch work positions',
    'audiences_fetch_failed' => 'Failed to fetch audiences',

    // Targeting fields
    'locations' => 'Locations',
    'search_locations' => 'Search cities, regions, or countries...',
    'behaviors' => 'Behaviors',
    'no_behaviors_available' => 'No behaviors available for this ad account',
    'loading' => 'Loading',

    // Multi-select messaging destinations
    'messaging_apps_multiselect' => 'Select one or more messaging apps (you can select multiple)',
    'selected_messaging_apps' => 'Selected messaging apps',
    'instagram_account' => 'Instagram Account',
    'select_instagram_account' => 'Select an Instagram account',
    'no_whatsapp_numbers_found' => 'No WhatsApp Business numbers found. Connect one from Platform Connections.',
    'no_messenger_pages_found' => 'No Facebook Pages found. Connect a page from Platform Connections.',
    'no_instagram_accounts_found' => 'No Instagram accounts found. Connect an account from Platform Connections.',
];
