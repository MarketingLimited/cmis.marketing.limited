/**
 * Feature Flag Service - Frontend Client
 *
 * Provides a convenient API for checking feature flags from JavaScript
 * Includes caching and batch loading capabilities
 */
class FeatureFlagService {
    constructor() {
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
        this.apiBase = '/api/features';
    }

    /**
     * Check if a specific feature is enabled
     *
     * @param {string} featureKey - Feature key (e.g., 'scheduling.meta.enabled')
     * @returns {Promise<boolean>}
     */
    async isEnabled(featureKey) {
        // Check cache first
        const cached = this.getFromCache(featureKey);
        if (cached !== null) {
            return cached;
        }

        try {
            const response = await fetch(`${this.apiBase}/check/${featureKey}`);

            if (!response.ok) {
                console.error('Failed to check feature:', featureKey);
                return false;
            }

            const data = await response.json();
            const enabled = data.enabled || false;

            // Cache the result
            this.setCache(featureKey, enabled);

            return enabled;

        } catch (error) {
            console.error('Feature check error:', error);
            return false; // Fail-safe: disabled by default
        }
    }

    /**
     * Get all available platforms with their features
     *
     * @returns {Promise<Array>}
     */
    async getAvailablePlatforms() {
        const cacheKey = 'available_platforms';
        const cached = this.getFromCache(cacheKey);

        if (cached !== null) {
            return cached;
        }

        try {
            const response = await fetch(`${this.apiBase}/available-platforms`);

            if (!response.ok) {
                throw new Error('Failed to fetch available platforms');
            }

            const data = await response.json();
            const platforms = data.platforms || [];

            this.setCache(cacheKey, platforms);

            return platforms;

        } catch (error) {
            console.error('Get available platforms error:', error);
            return [];
        }
    }

    /**
     * Get enabled platforms for a specific feature category
     *
     * @param {string} category - Feature category (e.g., 'scheduling', 'paid_campaigns')
     * @returns {Promise<Array<string>>}
     */
    async getEnabledPlatforms(category) {
        const cacheKey = `enabled_platforms_${category}`;
        const cached = this.getFromCache(cacheKey);

        if (cached !== null) {
            return cached;
        }

        try {
            const response = await fetch(`${this.apiBase}/enabled-platforms/${category}`);

            if (!response.ok) {
                throw new Error(`Failed to fetch enabled platforms for ${category}`);
            }

            const data = await response.json();
            const platforms = data.enabled_platforms || [];

            this.setCache(cacheKey, platforms);

            return platforms;

        } catch (error) {
            console.error('Get enabled platforms error:', error);
            return [];
        }
    }

    /**
     * Get the complete feature matrix
     *
     * @returns {Promise<Object>}
     */
    async getFeatureMatrix() {
        const cacheKey = 'feature_matrix';
        const cached = this.getFromCache(cacheKey);

        if (cached !== null) {
            return cached;
        }

        try {
            const response = await fetch(`${this.apiBase}/matrix`);

            if (!response.ok) {
                throw new Error('Failed to fetch feature matrix');
            }

            const data = await response.json();
            const matrix = data.matrix || {};

            this.setCache(cacheKey, matrix);

            return matrix;

        } catch (error) {
            console.error('Get feature matrix error:', error);
            return {};
        }
    }

    /**
     * Check multiple features at once
     *
     * @param {Array<string>} featureKeys - Array of feature keys
     * @returns {Promise<Object>} - Object with feature keys as keys and boolean values
     */
    async checkMultiple(featureKeys) {
        const results = {};

        // Check cache first for all keys
        const uncachedKeys = [];
        for (const key of featureKeys) {
            const cached = this.getFromCache(key);
            if (cached !== null) {
                results[key] = cached;
            } else {
                uncachedKeys.push(key);
            }
        }

        // Fetch uncached keys in parallel
        if (uncachedKeys.length > 0) {
            const promises = uncachedKeys.map(key => this.isEnabled(key));
            const values = await Promise.all(promises);

            uncachedKeys.forEach((key, index) => {
                results[key] = values[index];
            });
        }

        return results;
    }

    /**
     * Check if platform is enabled for a feature
     *
     * @param {string} category - Feature category
     * @param {string} platform - Platform key
     * @returns {Promise<boolean>}
     */
    async isPlatformEnabled(category, platform) {
        const featureKey = `${category}.${platform}.enabled`;
        return this.isEnabled(featureKey);
    }

    /**
     * Clear the cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Get value from cache
     *
     * @private
     */
    getFromCache(key) {
        const item = this.cache.get(key);

        if (!item) {
            return null;
        }

        // Check if expired
        if (Date.now() - item.timestamp > this.cacheTimeout) {
            this.cache.delete(key);
            return null;
        }

        return item.value;
    }

    /**
     * Set value in cache
     *
     * @private
     */
    setCache(key, value) {
        this.cache.set(key, {
            value,
            timestamp: Date.now()
        });
    }
}

// Create singleton instance
const featureFlagService = new FeatureFlagService();

// Export for use in modules
export default featureFlagService;

// Also make available globally (for non-module scripts)
if (typeof window !== 'undefined') {
    window.FeatureFlagService = featureFlagService;
}
