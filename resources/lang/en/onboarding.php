<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Onboarding Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | English translations for user onboarding experience
    |
    */

    // General
    'welcome' => 'Welcome',
    'welcome_title' => 'Welcome to CMIS, :name!',
    'welcome_message' => 'Let\'s get you set up in just a few steps. This will only take about 15 minutes.',
    'your_progress' => 'Your Progress',
    'steps_completed' => ':completed of :total steps completed',
    'steps_remaining' => ':remaining steps remaining',
    'estimated_time' => 'Estimated time',

    // Status
    'completed' => 'Completed',
    'in_progress' => 'In Progress',
    'not_started' => 'Not Started',

    // Actions
    'continue' => 'Continue',
    'start' => 'Start',
    'skip_step' => 'Skip This Step',
    'skip_for_now' => 'I\'ll do this later',
    'complete_and_continue' => 'Complete & Continue',
    'finish_onboarding' => 'Finish Onboarding',
    'back_to_overview' => 'Back to Overview',
    'go_to_profile_settings' => 'Go to Profile Settings',
    'go_to_integrations' => 'Go to Integrations',
    'start_campaign_wizard' => 'Start Campaign Wizard',
    'go_to_team_management' => 'Go to Team Management',
    'go_to_analytics' => 'Go to Analytics Dashboard',

    // Steps
    'step_x' => 'Step :number',
    'step_x_of_y' => 'Step :current of :total',
    'tasks_to_complete' => 'Tasks to Complete',
    'helpful_tips' => 'Helpful Tips',
    'complete_all_tasks' => 'Please complete all tasks before continuing',

    // Confirmation
    'confirm_skip' => 'Are you sure you want to skip this step?',
    'confirm_dismiss' => 'Are you sure you want to dismiss the onboarding? You can always restart it later from your settings.',

    // Step Details
    'profile_details' => 'Complete Your Profile',
    'profile_setup_description' => 'Add your personal information and preferences to personalize your experience.',
    'connect_platform' => 'Connect Your Ad Platform',
    'platform_connection_description' => 'Connect your Meta (Facebook) account to start managing campaigns.',
    'create_first_campaign' => 'Create Your First Campaign',
    'first_campaign_description' => 'Use our guided wizard to create your first marketing campaign in minutes.',
    'invite_team' => 'Invite Your Team',
    'team_setup_description' => 'Invite team members and assign roles to collaborate effectively.',
    'explore_analytics' => 'Explore Analytics',
    'analytics_tour_description' => 'Learn how to track performance and measure your campaign success.',

    // Messages
    'step_completed' => 'Step completed successfully!',
    'step_skipped' => 'Step skipped',
    'reset_complete' => 'Onboarding progress reset',
    'dismissed' => 'Onboarding dismissed. You can restart from Settings.',
    'create_org_first' => 'Please create an organization first to continue with onboarding.',

    // Completion Page (HI-005)
    'title' => 'Onboarding',
    'complete_title' => 'Onboarding Complete',
    'congratulations' => 'Congratulations!',
    'complete_message' => 'You have successfully completed all onboarding steps. You are now ready to use CMIS to its full potential!',
    'what_you_accomplished' => 'What You Accomplished',
    'completed_label' => 'Completed',
    'next_steps' => 'What\'s Next?',
    'go_to_dashboard' => 'Go to Dashboard',
    'dashboard_description' => 'View your performance overview and key metrics',
    'view_campaigns' => 'View Campaigns',
    'campaigns_description' => 'Manage and track all your marketing campaigns',
    'explore_analytics' => 'Explore Analytics',
    'analytics_description' => 'Dive deep into your performance data and insights',

    // Step Definitions (used in controller)
    'steps' => [
        'profile_setup' => [
            'title' => 'Complete Your Profile',
            'description' => 'Set up your account with your personal information and preferences',
            'tasks' => [
                'complete_profile' => 'Add your full name and profile picture',
                'upload_logo' => 'Upload your company logo',
                'set_preferences' => 'Configure language and notification preferences',
            ],
        ],
        'platform_connection' => [
            'title' => 'Connect Ad Platform',
            'description' => 'Link your Meta (Facebook) account to start managing campaigns',
            'tasks' => [
                'connect_meta' => 'Connect your Meta Business Account',
                'authorize_access' => 'Authorize CMIS to access your ad accounts',
                'sync_accounts' => 'Sync your existing campaigns and data',
            ],
        ],
        'first_campaign' => [
            'title' => 'Create First Campaign',
            'description' => 'Launch your first marketing campaign using our guided wizard',
            'tasks' => [
                'use_wizard' => 'Complete the campaign creation wizard',
                'set_budget' => 'Configure your campaign budget and schedule',
                'review_launch' => 'Review and publish your campaign',
            ],
        ],
        'team_setup' => [
            'title' => 'Set Up Your Team',
            'description' => 'Invite team members and configure roles and permissions',
            'tasks' => [
                'invite_members' => 'Send invitations to your team members',
                'assign_roles' => 'Assign appropriate roles (Admin, Manager, Viewer)',
                'configure_permissions' => 'Set up custom permissions if needed',
            ],
        ],
        'analytics_tour' => [
            'title' => 'Explore Analytics',
            'description' => 'Learn how to track and measure your marketing performance',
            'tasks' => [
                'explore_dashboard' => 'Tour the analytics dashboard',
                'understand_metrics' => 'Learn about key performance metrics',
                'setup_alerts' => 'Configure performance alerts and notifications',
            ],
        ],
    ],

];
