<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | English translations for reusable Blade components
    |
    */

    // Alert Component
    'alert' => [
        'close' => 'Close',
    ],

    // Breadcrumb Component
    'breadcrumb' => [
        'home' => 'Home',
    ],

    // Delete Confirmation Modal
    'delete_modal' => [
        'title' => 'Confirm Deletion',
        'confirm_message' => 'Are you sure you want to delete',
        'warning' => 'Warning',
        'cascade_info' => 'The following items will also be deleted:',
        'can_restore' => 'Deleted :item can be restored within 30 days.',
        'delete_button' => 'Delete',
        'deleting' => 'Deleting...',
        'cancel' => 'Cancel',
        'success' => 'Deleted successfully',
        'failed' => 'Failed to delete',
        'error' => 'An error occurred while deleting. Please try again.',
    ],

    // File Upload Component
    'file_upload' => [
        'upload_file' => 'Upload File',
        'click_or_drag' => 'Click to upload or drag files here',
        'max_size' => 'Max size',
        'allowed_types' => 'Allowed types',
        'add_more' => 'Add more files',
        'size_exceeded' => 'File :filename exceeds maximum allowed size (:maxsize)',
    ],

    // Organization Switcher
    'org_switcher' => [
        'current_org' => 'Current Organization',
        'loading' => 'Loading...',
        'choose_org' => 'Choose Organization',
        'switch_between' => 'Switch between your organizations',
        'no_organizations' => 'No Organizations',
        'switching' => 'Switching...',
        'switch_failed' => 'Failed to switch organization. Please try again.',
        'no_slug' => 'No slug',
    ],

    // Language Switcher
    'language' => [
        'arabic' => 'العربية',
        'english' => 'English',
        'arabic_sub' => 'Arabic',
        'english_sub' => 'الإنجليزية',
    ],

    // Pagination
    'pagination' => [
        'previous' => 'Previous',
        'next' => 'Next',
        'showing' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'results' => 'results',
        'go_to_page' => 'Go to page :page',
    ],

    // Publish Modal
    'publish' => [
        'create_post' => 'Create New Post',
        'edit_post' => 'Edit Post',
        'save_draft' => 'Save as Draft',

        // Profile Groups
        'profile_groups' => 'Profile Groups',
        'select_all' => 'Select All',
        'clear' => 'Clear',

        // Profiles
        'profiles' => 'Profiles',
        'selected' => 'selected',
        'search_profiles' => 'Search profiles...',
        'all_platforms' => 'All',
        'choose_groups_first' => 'Choose profile groups first',
        'select_group_above' => 'Select one or more groups above',
        'select_all_profiles' => 'Select All Profiles',
        'connection_error' => 'Connection Error',

        // Content
        'global_content' => 'Global Content',
        'post_content' => 'Post Content',
        'what_to_share' => 'What do you want to share?',
        'emoji' => 'Emoji',
        'hashtags' => 'Hashtags',
        'mention' => 'Mention',
        'ai_assistant' => 'AI Assistant',

        // Media
        'media' => 'Media',
        'drag_or_click' => 'Drag files here or click to upload',
        'media_formats' => 'Images: JPG, PNG, GIF | Video: MP4, MOV (max 100MB)',

        // Link
        'link_optional' => 'Link (Optional)',
        'shorten' => 'Shorten',

        // Labels
        'labels' => 'Labels',
        'add_label' => 'Add label...',

        // Platform-specific
        'customize_for' => 'Customize content for :platform',
        'leave_empty_global' => 'Leave empty to use global content.',
        'custom_content_for' => 'Custom content for :platform...',

        // Instagram
        'post_type' => 'Post Type',
        'feed_post' => 'Post',
        'reel' => 'Reel',
        'story' => 'Story',
        'first_comment' => 'First Comment',
        'hashtags_as_comment' => 'Add hashtags here as first comment...',

        // Twitter
        'reply_settings' => 'Reply Settings',
        'everyone_reply' => 'Everyone can reply',
        'following_reply' => 'People you follow only',
        'mentioned_reply' => 'Mentioned only',

        // Scheduling
        'schedule' => 'Schedule',
        'best_times' => 'Best Times',
        'timezone_riyadh' => 'Riyadh Time',
        'timezone_dubai' => 'Dubai Time',
        'timezone_london' => 'London',
        'timezone_newyork' => 'New York',

        // Preview
        'preview' => 'Preview',
        'account_name' => 'Account Name',
        'just_now' => 'Just now',
        'scheduled_at' => 'Scheduled: :date :time',
        'post_preview' => 'Your post content will appear here...',

        // Brand Safety
        'brand_safe' => 'Content complies with brand guidelines',
        'brand_issues' => 'Content issues detected',

        // Approval
        'requires_approval' => 'Requires approval before publishing',
        'submit_for_approval' => 'Submit for Approval',

        // Actions
        'cancel' => 'Cancel',
        'publish_now' => 'Publish Now',
        'schedule_post' => 'Schedule Post',

        // AI Assistant
        'ai_assistant_title' => 'AI Assistant',
        'brand_voice' => 'Brand Voice',
        'default' => 'Default',
        'tone' => 'Tone',
        'tone_professional' => 'Professional',
        'tone_friendly' => 'Friendly',
        'tone_casual' => 'Casual',
        'tone_formal' => 'Formal',
        'tone_humorous' => 'Humorous',
        'tone_inspirational' => 'Inspirational',
        'length' => 'Length',
        'shorter' => 'Shorter',
        'same_length' => 'Same Length',
        'longer' => 'Longer',
        'custom_instructions' => 'Custom Instructions',
        'custom_prompt_placeholder' => 'Add any specific instructions...',
        'generate_content' => 'Generate Content',
        'generating' => 'Generating...',
        'suggestions' => 'Suggestions',
    ],

    // Platform Selector
    'platform_selector' => [
        'all' => 'All',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'snapchat' => 'Snapchat',
    ],

    // Stats Card
    'stats' => [
        'total' => 'Total',
        'change' => 'Change',
        'vs_previous' => 'vs previous period',
    ],

    // Progress Bar
    'progress' => [
        'complete' => 'Complete',
        'in_progress' => 'In Progress',
    ],

];
