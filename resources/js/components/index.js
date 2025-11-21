/**
 * CMIS Alpine.js Components Index (Phase 8-16)
 *
 * Central export file for all dashboard and analytics components
 */

import realtimeDashboard from './realtimeDashboard.js';
import campaignAnalytics from './campaignAnalytics.js';
import kpiDashboard from './kpiDashboard.js';
import notificationCenter from './notificationCenter.js';
import campaignComparison from './campaignComparison.js';
import scheduledReports from './scheduledReports.js';
import alertsManagement from './alertsManagement.js';
import dataExports from './dataExports.js';
import experiments from './experiments.js';
import predictiveAnalytics from './predictiveAnalytics.js';

// Export all components
export {
    realtimeDashboard,
    campaignAnalytics,
    kpiDashboard,
    notificationCenter,
    campaignComparison,
    scheduledReports,
    alertsManagement,
    dataExports,
    experiments,
    predictiveAnalytics
};

// Register components globally with Alpine.js
if (window.Alpine) {
    window.Alpine.data('realtimeDashboard', realtimeDashboard);
    window.Alpine.data('campaignAnalytics', campaignAnalytics);
    window.Alpine.data('kpiDashboard', kpiDashboard);
    window.Alpine.data('notificationCenter', notificationCenter);
    window.Alpine.data('campaignComparison', campaignComparison);
    window.Alpine.data('scheduledReports', scheduledReports);
    window.Alpine.data('alertsManagement', alertsManagement);
    window.Alpine.data('dataExports', dataExports);
    window.Alpine.data('experiments', experiments);
    window.Alpine.data('predictiveAnalytics', predictiveAnalytics);
}

export default {
    realtimeDashboard,
    campaignAnalytics,
    kpiDashboard,
    notificationCenter,
    campaignComparison,
    scheduledReports,
    alertsManagement,
    dataExports,
    experiments,
    predictiveAnalytics
};
