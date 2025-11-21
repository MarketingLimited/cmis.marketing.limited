<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Campaign Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | English translations for campaign-related text
    |
    */

    // General
    'campaigns' => 'Campaigns',
    'campaign' => 'Campaign',
    'my_campaigns' => 'My Campaigns',
    'all_campaigns' => 'All Campaigns',
    'active_campaigns' => 'Active Campaigns',
    'draft_campaigns' => 'Drafts',
    'completed_campaigns' => 'Completed Campaigns',

    // Actions
    'create_campaign' => 'Create New Campaign',
    'edit_campaign' => 'Edit Campaign',
    'delete_campaign' => 'Delete Campaign',
    'pause_campaign' => 'Pause',
    'resume_campaign' => 'Resume',
    'duplicate_campaign' => 'Duplicate Campaign',
    'archive_campaign' => 'Archive',
    'publish_campaign' => 'Publish Campaign',
    'save_draft' => 'Save as Draft',

    // Campaign Details
    'campaign_name' => 'Campaign Name',
    'campaign_objective' => 'Campaign Objective',
    'campaign_budget' => 'Campaign Budget',
    'campaign_duration' => 'Campaign Duration',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'target_audience' => 'Target Audience',
    'campaign_status' => 'Campaign Status',

    // Campaign Objectives
    'objectives' => [
        'awareness' => 'Brand Awareness',
        'traffic' => 'Website Traffic',
        'engagement' => 'Engagement',
        'leads' => 'Lead Generation',
        'sales' => 'Sales',
        'app_installs' => 'App Installs',
        'video_views' => 'Video Views',
        'messages' => 'Messages',
    ],

    // Campaign Status
    'status' => [
        'draft' => 'Draft',
        'pending_review' => 'Pending Review',
        'approved' => 'Approved',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'archived' => 'Archived',
    ],

    // Budget
    'daily_budget' => 'Daily Budget',
    'total_budget' => 'Total Budget',
    'spent' => 'Spent',
    'remaining' => 'Remaining',
    'currency' => 'Currency',

    // Performance Metrics
    'metrics' => [
        'impressions' => 'Impressions',
        'reach' => 'Reach',
        'clicks' => 'Clicks',
        'ctr' => 'Click-Through Rate',
        'conversions' => 'Conversions',
        'cost_per_click' => 'Cost Per Click',
        'cost_per_conversion' => 'Cost Per Conversion',
        'roas' => 'Return on Ad Spend',
    ],

    // Platforms
    'platforms' => [
        'meta' => 'Meta (Facebook & Instagram)',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'google' => 'Google Ads',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'twitter' => 'Twitter (X)',
        'snapchat' => 'Snapchat',
    ],

    // AI Generation
    'ai_generate' => 'Generate with AI',
    'ai_suggestions' => 'AI Suggestions',
    'generate_ideas' => 'Generate Ideas',
    'improve_content' => 'Improve Content',
    'marketing_principles' => 'Marketing Principles',

    // Messages
    'created_successfully' => 'Campaign created successfully',
    'updated_successfully' => 'Campaign updated successfully',
    'deleted_successfully' => 'Campaign deleted successfully',
    'published_successfully' => 'Campaign published successfully',
    'paused_successfully' => 'Campaign paused successfully',
    'resumed_successfully' => 'Campaign resumed successfully',

    // Errors
    'not_found' => 'Campaign not found',
    'cannot_edit' => 'Cannot edit this campaign',
    'cannot_delete' => 'Cannot delete this campaign',
    'budget_exceeded' => 'Budget exceeded',

    // Wizard Steps
    'wizard' => [
        'title' => 'Campaign Wizard',
        'create_campaign' => 'Create Campaign with Wizard',
        'started' => 'Campaign wizard started',
        'session_expired' => 'Your wizard session has expired. Please start again.',
        'step_saved' => 'Progress saved successfully',
        'progress_saved' => 'Your progress has been saved',
        'cancelled' => 'Campaign wizard cancelled',
        'confirm_complete' => 'Are you sure you want to complete and launch this campaign?',
        'completion_error' => 'Please complete all required steps before launching',
        'step_x_of_y' => 'Step :current of :total',

        'step_1_title' => 'Campaign Basics',
        'step_2_title' => 'Targeting & Audience',
        'step_3_title' => 'Creative Content',
        'step_4_title' => 'Review & Launch',

        // Step 1
        'name_help' => 'Choose a clear, descriptive name for your campaign',
        'objective_help' => 'Select what you want to achieve with this campaign',
        'budget_help' => 'Set your total campaign budget (minimum $10)',
        'end_date_help' => 'Leave blank for ongoing campaigns',
        'description_help' => 'Optional: Add notes about your campaign strategy',

        // Step 2
        'targeting' => [
            'custom_help' => 'Define audience manually with demographics and interests',
            'lookalike_help' => 'Find people similar to your existing customers',
            'saved_help' => 'Use a previously saved audience',
            'saved_audience_help' => 'Select from your saved audiences',
            'age_help' => 'Target specific age groups (18-65)',
            'locations_help' => 'Hold Ctrl/Cmd to select multiple countries',
            'interests_help' => 'Enter interests separated by commas (e.g., fitness, technology, travel)',
            'interests_placeholder' => 'e.g., fitness, technology, travel',
            'reach_estimate' => 'Estimated reach: :min - :max people',
        ],

        // Step 3
        'creative' => [
            'primary_text_placeholder' => 'Write your main ad text here...',
            'primary_text_help' => 'This is the main text that will appear in your ad',
            'headline_placeholder' => 'Catchy headline',
            'headline_help' => 'A short, attention-grabbing headline',
            'description_placeholder' => 'Additional description',
            'description_help' => 'Brief description to support your message',
            'cta_help' => 'Select the action button that appears on your ad',
            'media_help' => 'PNG, JPG, or MP4 files up to 10MB',
            'media_urls_placeholder' => 'Enter image/video URLs (one per line)',
            'ai_helper_title' => 'Need help writing?',
            'ai_helper_description' => 'Use AI to generate compelling ad copy based on your campaign details',
            'ai_helper_coming_soon' => 'AI content generation will open here (coming soon)',
            'preview_placeholder' => 'Your ad text will appear here',
            'preview_note' => 'This is a preview. Actual ad appearance may vary by platform.',
        ],

        // Step 4
        'review' => [
            'almost_ready' => '🎉 Almost Ready!',
            'review_description' => 'Review your campaign details before launching. You can edit any section by clicking Edit.',
            'health_check' => 'Campaign Health Check',
            'all_required_fields' => 'All required fields completed',
            'budget_configured' => 'Budget properly configured',
            'audience_defined' => 'Target audience defined',
            'creative_complete' => 'Creative content ready',
            'important_notice' => 'Important Notice',
            'launch_notice' => 'Once launched, this campaign will go live and start spending your budget. Make sure all details are correct.',
            'agree_terms' => 'I understand and agree to launch this campaign',
        ],
    ],

    // Additional Fields
    'name' => 'Campaign Name',
    'name_placeholder' => 'Enter campaign name',
    'objective' => 'Campaign Objective',
    'objectives' => [
        'awareness' => 'Brand Awareness',
        'traffic' => 'Website Traffic',
        'engagement' => 'Engagement',
        'conversions' => 'Conversions',
        'app_installs' => 'App Installs',
    ],
    'budget_total' => 'Total Budget',
    'budget_daily' => 'Daily Budget (Optional)',
    'description' => 'Description',
    'description_placeholder' => 'Add campaign notes...',
    'duration' => 'Campaign Duration',
    'ongoing' => 'Ongoing',
    'people' => 'people',

    // Targeting
    'audience_type' => 'Audience Type',
    'saved_audience' => 'Saved Audience',
    'age_range' => 'Age Range',
    'min_age' => 'Minimum Age',
    'max_age' => 'Maximum Age',
    'genders' => 'Genders',
    'all_genders' => 'All Genders',
    'male' => 'Male',
    'female' => 'Female',
    'locations' => 'Target Locations',
    'interests' => 'Interests',
    'estimated_reach' => 'Estimated Reach',
    'targeting' => [
        'custom' => 'Custom Audience',
        'lookalike' => 'Lookalike Audience',
        'saved' => 'Saved Audience',
    ],
    'countries' => [
        'saudi_arabia' => 'Saudi Arabia',
        'uae' => 'United Arab Emirates',
        'egypt' => 'Egypt',
        'kuwait' => 'Kuwait',
        'qatar' => 'Qatar',
        'bahrain' => 'Bahrain',
        'oman' => 'Oman',
        'jordan' => 'Jordan',
        'lebanon' => 'Lebanon',
    ],

    // Creative
    'ad_format' => 'Ad Format',
    'formats' => [
        'single_image' => 'Single Image',
        'carousel' => 'Carousel',
        'video' => 'Video',
        'collection' => 'Collection',
    ],
    'primary_text' => 'Primary Text',
    'headline' => 'Headline',
    'call_to_action' => 'Call to Action',
    'cta' => [
        'learn_more' => 'Learn More',
        'shop_now' => 'Shop Now',
        'sign_up' => 'Sign Up',
        'download' => 'Download',
        'get_offer' => 'Get Offer',
        'contact_us' => 'Contact Us',
        'book_now' => 'Book Now',
        'apply_now' => 'Apply Now',
    ],
    'upload_media' => 'Upload Media',
    'or_provide_urls' => 'Or provide URLs',
    'generate_with_ai' => 'Generate with AI',
    'preview' => 'Preview',

    // Review
    'budget_summary' => 'Budget Summary',
    'total_budget' => 'Total Budget',
    'saved_as_draft' => 'Campaign saved as draft',
    'created_successfully' => 'Campaign created successfully!',

    // Help Text
    'help' => [
        'campaign_name' => 'Choose a descriptive name to help you identify this campaign',
        'objective' => 'Select the primary goal you want to achieve with this campaign',
        'budget' => 'Set the amount you want to spend on this campaign',
        'audience' => 'Define the characteristics of the audience you want to reach',
    ],

];
