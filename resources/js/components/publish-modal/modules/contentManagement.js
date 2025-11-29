/**
 * Publish Modal - Content Management Module
 * Handles content composition, character counting, and text management
 */

export function getContentManagementMethods() {
    return {
        // ============================================
        // CONTENT METHODS
        // ============================================

        updateCharacterCounts() {
            this.checkBrandSafety();
        },

        getCharacterCount(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform]?.text || 2200;
            return `${text.length}/${limit}`;
        },

        getCharacterCountClass(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform]?.text || 2200;
            if (text.length > limit) return 'text-red-600';
            if (text.length > limit * 0.9) return 'text-yellow-600';
            return 'text-gray-500';
        },

        // Get content for a specific platform (platform-specific or global fallback)
        getContentForPlatform(platform) {
            const platformContent = this.content.platforms[platform] || {};
            return {
                text: platformContent.text || this.content.global.text,
                media: this.content.global.media, // Media is always shared from global
                ...platformContent
            };
        },

        // Copy global content to a specific platform
        copyToAllPlatforms() {
            const globalText = this.content.global.text;
            Object.keys(this.content.platforms).forEach(platform => {
                this.content.platforms[platform].text = globalText;
            });
        },

        // Clear content
        clearContent() {
            this.content.global.text = '';
            this.content.global.media = [];
            this.content.global.link = '';
            this.content.global.labels = [];

            // Clear platform-specific content
            Object.keys(this.content.platforms).forEach(platform => {
                this.content.platforms[platform].text = '';
                this.content.platforms[platform].first_comment = '';
            });
        },

        // Add label
        addLabel() {
            if (this.newLabel.trim() && !this.content.global.labels.includes(this.newLabel.trim())) {
                this.content.global.labels.push(this.newLabel.trim());
                this.newLabel = '';
            }
        },

        // Remove label
        removeLabel(label) {
            const index = this.content.global.labels.indexOf(label);
            if (index >= 0) {
                this.content.global.labels.splice(index, 1);
            }
        }
    };
}

export default getContentManagementMethods;
