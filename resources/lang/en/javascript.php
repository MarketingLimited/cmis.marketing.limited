<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JavaScript Language Lines (English)
    |--------------------------------------------------------------------------
    | Translation keys specifically for JavaScript components and Alpine.js
    */

    // Alert & Confirmation Messages
    'confirm_delete' => 'Are you sure you want to delete this?',
    'confirm_delete_scheduled_report' => 'Are you sure you want to delete this scheduled report?',
    'confirm_delete_export_config' => 'Delete this export configuration?',
    'confirm_delete_experiment' => 'Delete this experiment? This action cannot be undone.',
    'confirm_start_experiment' => 'Start this experiment?',
    'confirm_complete_experiment' => 'Complete this experiment? This will calculate results and determine a winner.',
    'confirm_accept_recommendation' => 'Accept this recommendation?',
    'confirm_mark_false_positive' => 'Mark this anomaly as a false positive?',
    'confirm_revoke_token' => 'Revoke this API token? This action cannot be undone.',
    'cannot_undo' => 'This action cannot be undone',

    // Success Messages
    'scheduled_report_created' => 'Scheduled report created successfully',
    'scheduled_report_deleted' => 'Scheduled report deleted',
    'schedule_updated' => 'Schedule updated successfully',
    'schedule_activated' => 'Schedule activated',
    'schedule_deactivated' => 'Schedule deactivated',
    'experiment_created' => 'Experiment created',
    'experiment_started' => 'Experiment started',
    'experiment_paused' => 'Experiment paused',
    'experiment_resumed' => 'Experiment resumed',
    'experiment_completed' => 'Experiment completed',
    'experiment_deleted' => 'Experiment deleted',
    'variant_added' => 'Variant added',
    'export_config_created' => 'Export configuration created',
    'export_config_activated' => 'Configuration activated',
    'export_config_deactivated' => 'Configuration deactivated',
    'export_config_deleted' => 'Configuration deleted',
    'export_queued' => 'Export queued for processing',
    'export_completed' => 'Export completed',
    'api_token_created' => 'API token created',
    'token_revoked' => 'Token revoked',
    'copied_to_clipboard' => 'Copied to clipboard',
    'trend_analysis_completed' => 'Trend analysis completed',
    'forecasts_generated' => ':count forecasts generated successfully',
    'recommendations_generated' => 'Recommendations generated successfully',
    'anomalies_detected' => 'Anomalies detected successfully',

    // Error Messages
    'failed_to_load_schedules' => 'Failed to load scheduled reports',
    'failed_to_load_templates' => 'Failed to load templates',
    'failed_to_create_schedule' => 'Failed to create scheduled report',
    'failed_to_update_schedule' => 'Failed to update schedule',
    'failed_to_delete_schedule' => 'Failed to delete schedule',
    'failed_to_load_history' => 'Failed to load execution history',
    'failed_to_load_experiments' => 'Failed to load experiments',
    'failed_to_load_stats' => 'Failed to load stats',
    'failed_to_create_experiment' => 'Failed to create experiment',
    'failed_to_load_experiment' => 'Failed to load experiment',
    'failed_to_add_variant' => 'Failed to add variant',
    'failed_to_start_experiment' => 'Failed to start experiment',
    'failed_to_pause_experiment' => 'Failed to pause experiment',
    'failed_to_resume_experiment' => 'Failed to resume experiment',
    'failed_to_complete_experiment' => 'Failed to complete experiment',
    'failed_to_delete_experiment' => 'Failed to delete experiment',
    'failed_to_load_results' => 'Failed to load results',
    'failed_to_load_configs' => 'Failed to load configs',
    'failed_to_load_logs' => 'Failed to load logs',
    'failed_to_load_tokens' => 'Failed to load tokens',
    'failed_to_create_config' => 'Failed to create configuration',
    'failed_to_update_config' => 'Failed to update configuration',
    'failed_to_delete_config' => 'Failed to delete configuration',
    'failed_to_execute_export' => 'Failed to execute export',
    'failed_to_create_token' => 'Failed to create token',
    'failed_to_revoke_token' => 'Failed to revoke token',
    'failed_to_copy' => 'Failed to copy',
    'failed_to_load_forecasts' => 'Failed to load forecasts',
    'failed_to_load_anomalies' => 'Failed to load anomalies',
    'failed_to_load_trends' => 'Failed to load trends',
    'failed_to_load_recommendations' => 'Failed to load recommendations',
    'no_significant_winner' => 'No statistically significant winner found.',
    'experiment_winner_found' => 'Winner: :name\nImprovement: :improvement%',
    'failed_to_create' => 'Failed to create',
    'failed_to_update' => 'Failed to update',
    'failed_to_delete' => 'Failed to delete',
    'failed_to_acknowledge' => 'Failed to acknowledge',
    'failed_to_resolve' => 'Failed to resolve',

    // Validation Messages
    'please_enter_valid_email' => 'Please enter a valid email address',
    'please_enter_report_name' => 'Please enter a report name',
    'please_add_recipient' => 'Please add at least one recipient',

    // Prompt Messages
    'enter_acknowledgement_notes' => 'Enter acknowledgement notes (optional):',
    'enter_resolution_notes' => 'Enter resolution notes:',
    'enter_rejection_reason' => 'Enter rejection reason (optional):',
    'enter_implementation_notes' => 'Enter implementation notes (optional):',

    // Console/Debug Messages (keep for developers, not user-facing)
    'comparison_error' => 'Comparison error:',
    'alerts_load_error' => 'Alerts load error:',
    'acknowledge_error' => 'Acknowledge error:',
    'resolve_error' => 'Resolve error:',
    'kpi_load_error' => 'KPI load error:',
    'context_load_error' => 'Context load error:',
    'organizations_load_error' => 'Organizations load error:',
    'switch_organization_error' => 'Switch organization error:',
    'metrics_load_error' => 'Metrics load error:',
    'trends_load_error' => 'Trends load error:',
    'top_performing_load_error' => 'Top performing load error:',
    'roi_load_error' => 'ROI load error:',
    'attribution_load_error' => 'Attribution load error:',
    'ltv_load_error' => 'LTV load error:',
    'projection_load_error' => 'Projection load error:',

    // General JavaScript UI
    'loading' => 'Loading...',
    'processing' => 'Processing...',
    'saving' => 'Saving...',
    'deleting' => 'Deleting...',
    'error' => 'Error',
    'success' => 'Success',
    'warning' => 'Warning',
    'info' => 'Information',

];
