/**
 * Publish Modal - Utilities Module
 * Helper functions, formatters, and utility methods
 */

export function getUtilityMethods() {
    return {
        // ============================================
        // MODAL MANAGEMENT
        // ============================================

        openModal() {
            this.open = true;
            this.editMode = false;
            this.editPostId = null;
            this.loadProfileGroups();
        },

        closeModal() {
            this.open = false;
            this.resetForm();
        },

        // ============================================
        // FORM RESET
        // ============================================

        resetForm() {
            // Reset profile selection
            this.selectedGroupIds = [];
            this.selectedProfiles = [];
            this.profileSearch = '';
            this.platformFilter = null;

            // Reset content
            this.content = {
                global: {
                    text: '',
                    media: [],
                    link: '',
                    labels: [],
                },
                platforms: {
                    instagram: {
                        text: '',
                        post_type: 'feed',
                        first_comment: '',
                        location_query: '',
                        location: null,
                        targeting_enabled: false,
                        target_country: '',
                        target_gender: 'all',
                        target_min_age: '',
                        target_max_age: '',
                        target_relationship: ''
                    },
                    facebook: {
                        text: '',
                        post_type: 'single',
                        location_query: '',
                        location: null,
                        targeting_enabled: false,
                        target_country: '',
                        target_gender: 'all',
                        target_min_age: '',
                        target_max_age: '',
                        target_relationship: ''
                    },
                    twitter: { text: '', reply_settings: 'everyone', location_query: '', location: null },
                    linkedin: { text: '', location_query: '', location: null },
                    tiktok: {
                        text: '',
                        video_title: '',
                        privacy: 'public',
                        allow_comments: true,
                        allow_duet: true,
                        allow_stitch: true,
                        location_query: '',
                        location: null
                    },
                    youtube: {
                        text: '',
                        video_title: '',
                        description: '',
                        category: 'entertainment',
                        visibility: 'public',
                        tags: '',
                        notify_subscribers: false,
                        embeddable: true,
                        create_first_like: false,
                        location_query: '',
                        location: null
                    }
                }
            };
            this.scheduleEnabled = false;
            this.schedule = { date: '', time: '', timezone: 'UTC' };
            this.composerTab = 'global';
            this.aiSuggestions = [];
            this.locationResults = {};
        },

        // ============================================
        // FORMATTERS
        // ============================================

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        formatTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            return `${this.formatDate(dateString)} ${this.formatTime(dateString)}`;
        }
    };
}

export default getUtilityMethods;
