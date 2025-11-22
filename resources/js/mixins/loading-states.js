/**
 * Loading States Mixin
 * Issue #9: Shows loading indicators for long operations
 *
 * Usage:
 * x-data="{ ...loadingStates(), ...yourData }"
 */

export function loadingStates() {
    return {
        loadingStates: {},
        loadingMessages: {},
        loadingProgress: {},

        startLoading(key, message = 'Loading...') {
            this.loadingStates[key] = true;
            this.loadingMessages[key] = message;
            this.loadingProgress[key] = 0;
        },

        stopLoading(key) {
            this.loadingStates[key] = false;
            delete this.loadingMessages[key];
            delete this.loadingProgress[key];
        },

        updateProgress(key, percentage, message = null) {
            this.loadingProgress[key] = percentage;
            if (message) {
                this.loadingMessages[key] = message;
            }
        },

        isLoading(key) {
            return this.loadingStates[key] || false;
        },

        getLoadingMessage(key) {
            return this.loadingMessages[key] || 'Loading...';
        },

        getLoadingProgress(key) {
            return this.loadingProgress[key] || 0;
        },

        hasProgress(key) {
            return this.loadingProgress[key] !== undefined && this.loadingProgress[key] > 0;
        },

        // Wrapper for async operations
        async withLoading(key, message, asyncFn) {
            this.startLoading(key, message);
            try {
                const result = await asyncFn();
                return result;
            } finally {
                this.stopLoading(key);
            }
        },

        // Wrapper for fetch operations with progress
        async fetchWithProgress(key, url, options = {}) {
            this.startLoading(key, 'Fetching data...');

            try {
                const response = await fetch(url, options);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                this.updateProgress(key, 50, 'Processing data...');
                const data = await response.json();
                this.updateProgress(key, 100, 'Complete');

                return data;
            } catch (error) {
                console.error('Fetch error:', error);
                throw error;
            } finally {
                // Keep complete message for 500ms before clearing
                setTimeout(() => this.stopLoading(key), 500);
            }
        }
    };
}
