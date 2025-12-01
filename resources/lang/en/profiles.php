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
];
