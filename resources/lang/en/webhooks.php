<?php

return [
    // Page titles
    'title' => 'Webhook Configuration',
    'subtitle' => 'Configure webhooks to receive real-time notifications',
    'create_title' => 'Create Webhook',
    'edit_title' => 'Edit Webhook',
    'details_title' => 'Webhook Details',

    // Actions
    'create' => 'Create Webhook',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'verify' => 'Verify Endpoint',
    'test' => 'Send Test',
    'activate' => 'Activate',
    'deactivate' => 'Deactivate',
    'regenerate_token' => 'Regenerate Token',
    'regenerate_secret' => 'Regenerate Secret',
    'view_logs' => 'View Logs',
    'retry' => 'Retry',
    'copy' => 'Copy',
    'copied' => 'Copied!',

    // Form fields
    'name' => 'Webhook Name',
    'name_placeholder' => 'e.g., My CRM Webhook',
    'name_help' => 'A friendly name to identify this webhook',
    'callback_url' => 'Callback URL',
    'callback_url_placeholder' => 'https://your-server.com/webhook',
    'callback_url_help' => 'The URL where webhook events will be sent',
    'verify_token' => 'Verify Token',
    'verify_token_help' => 'Use this token to verify webhook requests from CMIS',
    'secret_key' => 'Secret Key',
    'secret_key_help' => 'Use this secret to verify webhook signatures (HMAC-SHA256)',
    'platform' => 'Platform Filter',
    'platform_help' => 'Only receive events from a specific platform',
    'all_platforms' => 'All Platforms',
    'subscribed_events' => 'Subscribed Events',
    'subscribed_events_help' => 'Select which events you want to receive',
    'all_events' => 'All Events',
    'timeout' => 'Request Timeout',
    'timeout_help' => 'Maximum time to wait for your endpoint to respond',
    'max_retries' => 'Max Retries',
    'max_retries_help' => 'Number of retry attempts for failed deliveries',
    'custom_headers' => 'Custom Headers',
    'custom_headers_help' => 'Additional headers to include with webhook requests',

    // Status
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'verified' => 'Verified',
    'unverified' => 'Not Verified',
    'pending' => 'Pending',
    'success' => 'Success',
    'failed' => 'Failed',
    'retrying' => 'Retrying',

    // Statistics
    'statistics' => 'Statistics',
    'total_deliveries' => 'Total Deliveries',
    'success_rate' => 'Success Rate',
    'last_triggered' => 'Last Triggered',
    'last_success' => 'Last Success',
    'last_failure' => 'Last Failure',
    'last_error' => 'Last Error',
    'last_24h' => 'Last 24 Hours',
    'last_7d' => 'Last 7 Days',

    // Logs
    'delivery_logs' => 'Delivery Logs',
    'event_type' => 'Event Type',
    'response_status' => 'Response Status',
    'response_time' => 'Response Time',
    'attempt' => 'Attempt',
    'timestamp' => 'Timestamp',
    'payload' => 'Payload',
    'response' => 'Response',
    'error_message' => 'Error Message',
    'no_logs' => 'No delivery logs yet',

    // Event types
    'events' => [
        'message.received' => 'Message Received',
        'message.sent' => 'Message Sent',
        'message.delivered' => 'Message Delivered',
        'message.read' => 'Message Read',
        'message.failed' => 'Message Failed',
        'status.changed' => 'Status Changed',
        'webhook.meta' => 'Meta Events',
        'webhook.whatsapp' => 'WhatsApp Events',
        'webhook.tiktok' => 'TikTok Events',
        'webhook.twitter' => 'Twitter/X Events',
        'webhook.linkedin' => 'LinkedIn Events',
        'webhook.snapchat' => 'Snapchat Events',
        'webhook.google' => 'Google Ads Events',
        'lead.created' => 'Lead Created',
        'lead.updated' => 'Lead Updated',
        'campaign.status_changed' => 'Campaign Status Changed',
        'campaign.budget_alert' => 'Campaign Budget Alert',
        'post.published' => 'Post Published',
        'post.failed' => 'Post Failed',
        'post.engagement' => 'Post Engagement',
    ],

    // Messages
    'created' => 'Webhook created successfully. Please verify your endpoint.',
    'updated' => 'Webhook updated successfully.',
    'deleted' => 'Webhook deleted successfully.',
    'activated' => 'Webhook activated successfully.',
    'deactivated' => 'Webhook deactivated successfully.',
    'token_regenerated' => 'Verify token regenerated. Please verify your endpoint again.',
    'secret_regenerated' => 'Secret key regenerated. Update your webhook handler.',
    'verified' => 'Webhook endpoint verified successfully.',
    'verification_failed' => 'Endpoint verification failed.',
    'test_sent' => 'Test webhook sent successfully.',
    'test_failed' => 'Test webhook failed.',
    'retry_queued' => 'Delivery queued for retry.',
    'logs_retrieved' => 'Delivery logs retrieved.',

    // Errors
    'must_verify_first' => 'You must verify your webhook endpoint before activating it.',
    'cannot_retry' => 'This delivery cannot be retried.',
    'max_limit_reached' => 'You have reached the maximum number of webhooks (10).',
    'invalid_url' => 'Please enter a valid URL.',
    'url_not_reachable' => 'The callback URL is not reachable.',

    // Verification instructions
    'verification_instructions' => 'Verification Instructions',
    'verification_step1' => 'When we verify your endpoint, we send a GET request to your callback URL with these parameters:',
    'verification_step2' => 'Your server must:',
    'verification_step2a' => 'Check that hub.verify_token matches your verify token',
    'verification_step2b' => 'Respond with the hub.challenge value as plain text',
    'verification_example' => 'Example Response',

    // Signature verification
    'signature_verification' => 'Signature Verification',
    'signature_instructions' => 'To verify webhook authenticity, check the X-CMIS-Signature header:',
    'signature_step1' => 'Parse the signature header to get timestamp (t) and signature (v1)',
    'signature_step2' => 'Compute expected signature: HMAC-SHA256(timestamp.payload, secret_key)',
    'signature_step3' => 'Compare signatures using constant-time comparison',
    'signature_step4' => 'Reject requests older than 5 minutes (300 seconds)',

    // Empty state
    'no_webhooks' => 'No webhooks configured',
    'no_webhooks_description' => 'Create a webhook to receive real-time event notifications.',

    // Confirmation
    'confirm_delete' => 'Are you sure you want to delete this webhook?',
    'confirm_regenerate_token' => 'Regenerating the verify token will require re-verification. Continue?',
    'confirm_regenerate_secret' => 'Regenerating the secret key will require updating your webhook handler. Continue?',
];
