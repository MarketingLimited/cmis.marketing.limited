/**
 * Social Post Manager - Alpine.js Component
 * Manages social media post creation, scheduling, and publishing across multiple platforms
 */

export function socialPostManager(orgId, csrfToken, translations, platformConfigs) {
    return {
        // State
        loading: false,
        submitting: false,
        showCreateModal: false,
        filterStatus: 'all',
        posts: [],
        selectedPlatforms: [],
        selectedAccounts: [],
        orgId: orgId,
        csrfToken: csrfToken,
        translations: translations,

        // Platform configurations from config/social-platforms.php
        platformConfigs: platformConfigs,

        // Available platforms with connection status
        availablePlatforms: [],

        // Post data
        postData: {
            content: '',
            publish_type: 'now',
            scheduled_at: '',
            post_type: 'feed',
            files: []
        },

        // Initialize
        init() {
            this.minDateTime = new Date().toISOString().slice(0, 16);
            this.loadAvailablePlatforms();
            this.loadPosts();
        },

        // Load available platforms with connection status
        async loadAvailablePlatforms() {
            const platformsList = [
                { key: 'facebook', name: 'Facebook', icon: '<i class="fab fa-facebook text-blue-600"></i>' },
                { key: 'instagram', name: 'Instagram', icon: '<i class="fab fa-instagram text-pink-600"></i>' },
                { key: 'threads', name: 'Threads', icon: '<i class="fab fa-at text-purple-600"></i>' },
                { key: 'youtube', name: 'YouTube', icon: '<i class="fab fa-youtube text-red-600"></i>' },
                { key: 'linkedin', name: 'LinkedIn', icon: '<i class="fab fa-linkedin text-blue-700"></i>' },
                { key: 'twitter', name: 'X (Twitter)', icon: '<i class="fab fa-twitter text-sky-600"></i>' },
                { key: 'pinterest', name: 'Pinterest', icon: '<i class="fab fa-pinterest text-red-700"></i>' },
                { key: 'tiktok', name: 'TikTok', icon: '<i class="fab fa-tiktok text-gray-900"></i>' },
                { key: 'tumblr', name: 'Tumblr', icon: '<i class="fab fa-tumblr text-indigo-600"></i>' },
                { key: 'reddit', name: 'Reddit', icon: '<i class="fab fa-reddit text-orange-600"></i>' },
                { key: 'google_business', name: 'Google Business', icon: '<i class="fab fa-google text-blue-600"></i>' },
            ];

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/accounts`);
                const data = await response.json();

                if (data.success && data.data.accounts) {
                    const metaAccounts = data.data.accounts;
                    const fbAccounts = metaAccounts.filter(a => a.type === 'facebook');
                    const igAccounts = metaAccounts.filter(a => a.type === 'instagram');

                    this.availablePlatforms = platformsList.map(p => {
                        if (p.key === 'facebook') return { ...p, accounts: fbAccounts };
                        if (p.key === 'instagram') return { ...p, accounts: igAccounts };
                        return { ...p, accounts: [] };
                    });
                } else {
                    this.availablePlatforms = platformsList.map(p => ({ ...p, accounts: [] }));
                }
            } catch (error) {
                console.error('Failed to load platforms:', error);
                this.availablePlatforms = platformsList.map(p => ({ ...p, accounts: [] }));
            }
        },

        // Load posts
        async loadPosts() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`);
                const data = await response.json();
                if (data.success) {
                    this.posts = data.data.data || data.data || [];
                }
            } catch (error) {
                console.error('Failed to load posts:', error);
            } finally {
                this.loading = false;
            }
        },

        // Filtered posts based on status
        get filteredPosts() {
            if (this.filterStatus === 'all') return this.posts;
            return this.posts.filter(p => p.status === this.filterStatus);
        },

        // Toggle platform selection
        togglePlatform(platformKey) {
            const index = this.selectedPlatforms.indexOf(platformKey);
            if (index > -1) {
                this.selectedPlatforms.splice(index, 1);
                this.selectedAccounts = this.selectedAccounts.filter(a => !a.id.startsWith(platformKey + '_'));
            } else {
                this.selectedPlatforms.push(platformKey);
            }
        },

        // Get selected platforms data
        get selectedPlatformsData() {
            return this.availablePlatforms.filter(p => this.selectedPlatforms.includes(p.key));
        },

        // Toggle account selection
        toggleAccount(platformKey, account) {
            const fullAccount = { ...account, type: platformKey };
            const index = this.selectedAccounts.findIndex(a => a.id === account.id);
            if (index > -1) {
                this.selectedAccounts.splice(index, 1);
            } else {
                this.selectedAccounts.push(fullAccount);
            }
        },

        // Check if account is selected
        isAccountSelected(accountId) {
            return this.selectedAccounts.some(a => a.id === accountId);
        },

        // Get character limit based on selected platforms
        get characterLimit() {
            if (this.selectedPlatforms.length === 0) return 5000;

            const limits = {
                'twitter': 280,
                'threads': 500,
                'facebook': 63206,
                'instagram': 2200,
                'linkedin': 3000,
                'pinterest': 500,
                'tiktok': 2200,
                'youtube': 5000,
                'tumblr': 4096,
                'reddit': 40000,
                'google_business': 1500
            };

            const selectedLimits = this.selectedPlatforms.map(p => limits[p] || 5000);
            return Math.min(...selectedLimits);
        },

        // Can publish validation
        get canPublish() {
            return this.postData.content.trim().length > 0 &&
                   this.selectedAccounts.length > 0 &&
                   (this.postData.publish_type !== 'scheduled' || this.postData.scheduled_at);
        },

        // Handle file upload
        handleFileUpload(event) {
            const files = Array.from(event.target.files);
            this.postData.files = [...this.postData.files, ...files];
        },

        // Get file preview URL
        getFilePreview(file) {
            return URL.createObjectURL(file);
        },

        // Remove file
        removeFile(index) {
            this.postData.files.splice(index, 1);
        },

        // Create post
        async createPost() {
            if (!this.canPublish || this.submitting) return;

            this.submitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.postData.content);
                formData.append('publish_type', this.postData.publish_type);
                formData.append('post_type', this.postData.post_type);

                if (this.postData.scheduled_at) {
                    formData.append('scheduled_at', this.postData.scheduled_at);
                }

                const platformsData = this.selectedAccounts.map(account => ({
                    type: account.type,
                    platformId: account.platformId,
                    name: account.name,
                    integrationId: account.integrationId,
                    connectionId: account.connectionId,
                }));
                formData.append('platforms', JSON.stringify(platformsData));

                this.postData.files.forEach((file, index) => {
                    formData.append(`media[${index}]`, file);
                });

                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message || this.translations.post_created_success);
                    this.showCreateModal = false;
                    this.resetForm();
                    this.loadPosts();
                } else {
                    alert(data.message || this.translations.post_create_failed);
                }
            } catch (error) {
                console.error('Error creating post:', error);
                alert(this.translations.error_creating_post);
            } finally {
                this.submitting = false;
            }
        },

        // Reset form
        resetForm() {
            this.postData = {
                content: '',
                publish_type: 'now',
                scheduled_at: '',
                post_type: 'feed',
                files: []
            };
            this.selectedPlatforms = [];
            this.selectedAccounts = [];
        },

        // Publish a post immediately
        async publishPost(postId) {
            if (!confirm(this.translations.publish_confirm)) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(this.translations.publish_success);
                    this.loadPosts();
                } else {
                    alert(data.message || this.translations.publish_failed);
                }
            } catch (error) {
                console.error('Error publishing post:', error);
                alert(this.translations.error_publishing_post);
            }
        },

        // Delete a post
        async deletePost(postId) {
            if (!confirm(this.translations.delete_confirm)) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert(this.translations.delete_success);
                    this.loadPosts();
                } else {
                    alert(data.message || this.translations.delete_failed);
                }
            } catch (error) {
                console.error('Error deleting post:', error);
                alert(this.translations.error_deleting_post);
            }
        },

        // Get platform icon
        getPlatformIcon(platform) {
            const icons = {
                'facebook': '<i class="fab fa-facebook text-blue-600"></i>',
                'instagram': '<i class="fab fa-instagram text-pink-600"></i>',
                'threads': '<i class="fab fa-at text-purple-600"></i>',
                'youtube': '<i class="fab fa-youtube text-red-600"></i>',
                'linkedin': '<i class="fab fa-linkedin text-blue-700"></i>',
                'twitter': '<i class="fab fa-twitter text-sky-600"></i>',
                'pinterest': '<i class="fab fa-pinterest text-red-700"></i>',
                'tiktok': '<i class="fab fa-tiktok text-gray-900"></i>',
                'tumblr': '<i class="fab fa-tumblr text-indigo-600"></i>',
                'reddit': '<i class="fab fa-reddit text-orange-600"></i>',
                'google_business': '<i class="fab fa-google text-blue-600"></i>',
            };
            return icons[platform] || '<i class="fas fa-globe"></i>';
        },

        // Format date
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const locale = document.documentElement.lang === 'ar' ? 'ar-SA' : 'en-US';
            return date.toLocaleString(locale, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}

// Make globally available for Alpine.js
window.socialPostManager = socialPostManager;

export default socialPostManager;
