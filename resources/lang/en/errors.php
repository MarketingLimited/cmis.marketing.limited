<?php

return [
    // Error Page Titles
    '403_title' => 'Forbidden',
    '404_title' => 'Page Not Found',
    '500_title' => 'Server Error',
    '503_title' => 'Service Unavailable',

    // Error Messages
    '403_message' => 'You do not have permission to access this page.',
    '404_message' => 'The page you are looking for could not be found.',
    '500_message' => 'Something went wrong on our end. Please try again later.',
    '503_message' => 'The service is temporarily unavailable. Please try again later.',

    // Actions
    'go_home' => 'Go to Homepage',
    'go_back' => 'Go Back',
    'contact_support' => 'Contact Support',
    'try_again' => 'Try Again',

    // General Errors
    'error_occurred' => 'An error occurred',
    'something_went_wrong' => 'Something went wrong',
    'unexpected_error' => 'An unexpected error occurred',
    'please_try_again' => 'Please try again',

    // Validation Errors
    'validation_failed' => 'Validation failed',
    'required_field' => 'This field is required',
    'invalid_email' => 'Please enter a valid email address',
    'invalid_format' => 'Invalid format',
    'min_length' => 'Minimum length is :min characters',
    'max_length' => 'Maximum length is :max characters',

    // Authentication Errors
    'unauthorized' => 'Unauthorized access',
    'unauthenticated' => 'You must be logged in to access this page',
    'forbidden' => 'You do not have permission to perform this action',
    'invalid_credentials' => 'Invalid email or password',
    'account_suspended' => 'Your account has been suspended',
    'session_expired' => 'Your session has expired. Please log in again.',

    // Database Errors
    'database_error' => 'Database error occurred',
    'connection_failed' => 'Could not connect to database',
    'query_failed' => 'Query execution failed',

    // API Errors
    'api_error' => 'API error occurred',
    'rate_limit_exceeded' => 'Too many requests. Please try again later.',
    'invalid_request' => 'Invalid request',
    'timeout' => 'Request timeout',

    // File Upload Errors
    'upload_failed' => 'File upload failed',
    'file_too_large' => 'File size exceeds maximum limit',
    'invalid_file_type' => 'Invalid file type',
    'upload_error' => 'An error occurred during file upload',

    // Network Errors
    'network_error' => 'Network error occurred',
    'no_internet' => 'No internet connection',
    'connection_timeout' => 'Connection timeout',

    // Campaign Errors
    'campaign_not_found' => 'Campaign not found',
    'campaign_create_failed' => 'Failed to create campaign',
    'campaign_update_failed' => 'Failed to update campaign',
    'campaign_delete_failed' => 'Failed to delete campaign',

    // Platform Errors
    'platform_connection_failed' => 'Failed to connect to platform',
    'platform_authentication_failed' => 'Platform authentication failed',
    'platform_api_error' => 'Platform API error',
];
