<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LinkedIn Integration Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for LinkedIn Ads platform
    | integration. Use these translations for campaign objectives,
    | ad formats, placements, and other LinkedIn-specific terminology.
    |
    */

    'objectives' => [
        'BRAND_AWARENESS' => 'Brand Awareness',
        'WEBSITE_VISITS' => 'Website Visits',
        'ENGAGEMENT' => 'Engagement',
        'VIDEO_VIEWS' => 'Video Views',
        'LEAD_GENERATION' => 'Lead Generation',
        'WEBSITE_CONVERSIONS' => 'Website Conversions',
        'JOB_APPLICANTS' => 'Job Applicants',
    ],

    'placements' => [
        'linkedin_feed' => 'LinkedIn Feed',
        'linkedin_right_rail' => 'LinkedIn Right Rail',
        'linkedin_messaging' => 'LinkedIn Messaging (InMail)',
    ],

    'ad_formats' => [
        'SPONSORED_STATUS_UPDATE' => 'Sponsored Content (Single Image)',
        'SPONSORED_VIDEO' => 'Sponsored Content (Video)',
        'SPONSORED_CAROUSEL' => 'Sponsored Content (Carousel)',
        'SPONSORED_INMAILS' => 'Message Ads (InMail)',
        'TEXT_AD' => 'Text Ads',
        'DYNAMIC_AD_FOLLOWER' => 'Dynamic Ads (Follower)',
        'DYNAMIC_AD_SPOTLIGHT' => 'Dynamic Ads (Spotlight)',
    ],

    'cost_types' => [
        'CPC' => 'Cost Per Click',
        'CPM' => 'Cost Per Impression',
        'CPS' => 'Cost Per Send',
    ],

    'statuses' => [
        'ACTIVE' => 'Active',
        'PAUSED' => 'Paused',
        'ARCHIVED' => 'Archived',
        'DRAFT' => 'Draft',
    ],

    'targeting' => [
        'locations' => 'Locations',
        'company_sizes' => 'Company Sizes',
        'industries' => 'Industries',
        'job_titles' => 'Job Titles',
        'job_functions' => 'Job Functions',
        'seniorities' => 'Seniority Levels',
        'skills' => 'Skills',
        'companies' => 'Companies',
        'age_ranges' => 'Age Ranges',
        'genders' => 'Genders',
        'matched_audiences' => 'Matched Audiences',
    ],

    'seniority_levels' => [
        'entry' => 'Entry Level',
        'mid' => 'Mid Level',
        'senior' => 'Senior Level',
        'manager' => 'Manager',
        'director' => 'Director',
        'executive' => 'Executive (C-Level)',
        'owner' => 'Owner/Partner',
    ],

    'form_fields' => [
        'FIRST_NAME' => 'First Name',
        'LAST_NAME' => 'Last Name',
        'EMAIL' => 'Email Address',
        'PHONE' => 'Phone Number',
        'COMPANY' => 'Company Name',
        'JOB_TITLE' => 'Job Title',
        'SENIORITY' => 'Seniority Level',
        'INDUSTRY' => 'Industry',
        'COUNTRY' => 'Country',
        'STATE' => 'State/Province',
        'CITY' => 'City',
    ],

    'webhooks' => [
        'lead_gen_form_response' => 'Lead Gen Form Submission',
        'campaign_notification' => 'Campaign Notification',
        'signature_invalid' => 'Invalid webhook signature',
        'signature_missing' => 'Missing webhook signature',
        'processing_failed' => 'Webhook processing failed',
        'success' => 'Webhook processed successfully',
    ],

    'errors' => [
        'auth_failed' => 'LinkedIn authentication failed',
        'token_refresh_failed' => 'Failed to refresh access token',
        'no_refresh_token' => 'No refresh token available',
        'api_error' => 'LinkedIn API error',
        'campaign_creation_failed' => 'Failed to create campaign',
        'form_creation_failed' => 'Failed to create Lead Gen Form',
        'invalid_objective' => 'Invalid campaign objective',
        'invalid_targeting' => 'Invalid targeting criteria',
        'rate_limit_exceeded' => 'LinkedIn API rate limit exceeded',
    ],

    'success' => [
        'campaign_created' => 'Campaign created successfully',
        'campaign_updated' => 'Campaign updated successfully',
        'campaign_deleted' => 'Campaign archived successfully',
        'form_created' => 'Lead Gen Form created successfully',
        'token_refreshed' => 'Access token refreshed successfully',
        'lead_processed' => 'Lead processed successfully',
    ],
];
