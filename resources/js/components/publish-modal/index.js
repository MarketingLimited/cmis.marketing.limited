/**
 * Publish Modal - Main Index
 * Modular architecture for the publish modal Alpine.js component
 *
 * FULLY MODULAR IMPLEMENTATION
 * All functionality extracted into focused, maintainable modules
 */

import getInitialState from '../state.js';
import getProfileManagementMethods from './modules/profileManagement.js';
import getContentManagementMethods from './modules/contentManagement.js';
import getUtilityMethods from './modules/utilities.js';
import getSchedulingManagementMethods from './modules/schedulingManagement.js';
import getMediaManagementMethods from './modules/mediaManagement.js';
import getValidationManagementMethods from './modules/validationManagement.js';
import getPlatformFeaturesMethods from './modules/platformFeatures.js';
import getPublishingManagementMethods from './modules/publishingManagement.js';
import getAIFeaturesMethods from './modules/aiFeatures.js';

/**
 * Publish Modal Alpine.js Component
 *
 * Architecture:
 * - State management: Centralized initial state (state.js)
 * - Profile management: Profile selection and group management (profileManagement.js)
 * - Content management: Content composition and character counting (contentManagement.js)
 * - Scheduling management: Calendar, best times, bulk scheduling (schedulingManagement.js)
 * - Media management: Upload, processing, validation (mediaManagement.js)
 * - Validation management: Form validation and platform rules (validationManagement.js)
 * - Platform features: Emoji, hashtags, mentions, links, location (platformFeatures.js)
 * - Publishing management: Publish, schedule, queue, draft (publishingManagement.js)
 * - AI features: Content generation, brand voice, sentiment analysis (aiFeatures.js)
 * - Utilities: Modal management, formatters, helpers (utilities.js)
 */
export function publishModal() {
    // Compose all modules into a single Alpine.js component
    return {
        // 1. Initial state and data structures
        ...getInitialState(),

        // 2. Core functionality modules
        ...getProfileManagementMethods(),
        ...getContentManagementMethods(),
        ...getSchedulingManagementMethods(),
        ...getMediaManagementMethods(),

        // 3. Validation and features
        ...getValidationManagementMethods(),
        ...getPlatformFeaturesMethods(),

        // 4. Publishing workflows
        ...getPublishingManagementMethods(),

        // 5. AI and advanced features
        ...getAIFeaturesMethods(),

        // 6. Utilities (last to allow overrides)
        ...getUtilityMethods(),
    };
}

// Make globally available for Alpine.js
window.publishModal = publishModal;

export default publishModal;
