<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | English translations for email templates
    |
    */

    // Alert Notification Email
    'alert_notification' => [
        'subject' => 'CMIS Alert: :name',
        'alert' => 'ALERT',
        'severity' => [
            'critical' => 'CRITICAL',
            'high' => 'HIGH',
            'medium' => 'MEDIUM',
            'low' => 'LOW',
        ],
        'triggered_at' => 'Triggered at',
        'actual_value' => 'Actual Value',
        'threshold' => 'Threshold',
        'alert_rule' => 'Alert Rule',
        'metric' => 'Metric',
        'entity_type' => 'Entity Type',
        'entity_id' => 'Entity ID',
        'condition' => 'Condition',
        'severity_label' => 'Severity',
        'status' => 'Status',
        'view_in_dashboard' => 'View in Dashboard',
        'recommended_actions' => 'Recommended Actions',
        'immediate_action' => 'Immediate action required - Review entity performance',
        'check_issues' => 'Check for any system issues or anomalies',
        'consider_pausing' => 'Consider pausing affected campaigns if necessary',
        'review_24h' => 'Review entity metrics within next 24 hours',
        'analyze_changes' => 'Analyze recent changes that may have caused this alert',
        'prepare_plan' => 'Prepare corrective action plan',
        'review_scheduled' => 'Review during next scheduled check-in',
        'monitor_trend' => 'Monitor for continued trend',
        'document_findings' => 'Document findings for future reference',
        'automated_alert' => 'This is an automated alert from CMIS Analytics.',
        'manage_alert_settings' => 'Manage Alert Settings',
        'view_all_alerts' => 'View All Alerts',
        'copyright' => 'CMIS - Cognitive Marketing Intelligence Suite',
    ],

    // One-Time Report Email
    'one_time_report' => [
        'subject' => 'CMIS Analytics Report',
        'title' => 'Analytics Report',
        'report_type' => ':type Report',
        'hello' => 'Hello,',
        'ready_message' => 'Your requested analytics report has been generated and is ready for download.',
        'report_type_label' => 'Report Type',
        'generated' => 'Generated',
        'expires' => 'Expires',
        'click_to_download' => 'Click the button below to download your report:',
        'download_button' => 'Download Report',
        'expires_note' => 'This download link will expire in 7 days for security purposes.',
        'note' => 'Note:',
        'additional_reports' => 'If you need to generate additional reports or schedule automated delivery, please visit the Analytics Dashboard.',
        'automated_message' => 'This report was generated on request from CMIS Analytics.',
        'analytics_dashboard' => 'Analytics Dashboard',
        'help_center' => 'Help Center',
        'copyright' => 'CMIS - Cognitive Marketing Intelligence Suite',
    ],

    // Scheduled Report Email
    'scheduled_report' => [
        'subject' => ':name - CMIS Analytics Report',
        'frequency' => [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ],
        'report' => 'Report',
        'hello' => 'Hello,',
        'ready_message' => 'Your scheduled analytics report has been generated and is ready for review.',
        'report_name' => 'Report Name',
        'report_type' => 'Report Type',
        'frequency_label' => 'Frequency',
        'generated' => 'Generated',
        'expires' => 'Expires',
        'can_download' => 'You can download your report using the button below:',
        'download_button' => 'Download Report',
        'auto_generated' => 'This report has been automatically generated based on your schedule configuration. If you need to modify the schedule or report settings, please visit the Analytics Dashboard.',
        'automated_message' => 'This is an automated message from CMIS Analytics.',
        'manage_schedules' => 'Manage Report Schedules',
        'analytics_dashboard' => 'Analytics Dashboard',
        'copyright' => 'CMIS - Cognitive Marketing Intelligence Suite',
    ],

];
