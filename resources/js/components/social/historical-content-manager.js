/**
 * Historical Content Manager - Alpine.js Component
 * Manages historical social media posts import, analysis, and knowledge base integration
 */

export function historicalContentManager(orgId, csrfToken) {
    return {
        // Data properties
        orgId: orgId,
        csrfToken: csrfToken,
        posts: [],
        profileGroups: [],
        integrations: [],
        selectedPosts: [],
        campaigns: [],
        loading: false,
        importing: false,
        viewMode: 'grid',
        showImportModal: false,
        showKBModal: false,
        showDetailModal: false,
        showCampaignModal: false,
        selectedPostForCampaign: null,
        selectedPost: null,
        currentMediaIndex: 0,
        searchQuery: '',
        filters: {
            profile_group_id: '',
            platform: '',
            is_analyzed: '',
            is_in_kb: '',
            min_success_score: 0
        },
        stats: {
            totalImported: 0,
            totalAnalyzed: 0,
            inKB: 0,
            highPerformers: 0
        },
        importData: {
            integration_id: '',
            limit: 100,
            start_date: '',
            end_date: '',
            auto_analyze: true
        },
        campaignData: {
            campaign_id: '',
            creative_type: 'image'
        },

        // Initialization
        init() {
            if (!this.orgId) return;

            this.loadIntegrations();
            this.loadPosts();
            this.loadCampaigns();

            // Set default date range (last 6 months)
            const today = new Date();
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(today.getMonth() - 6);
            this.importData.start_date = sixMonthsAgo.toISOString().split('T')[0];
            this.importData.end_date = today.toISOString().split('T')[0];
        },

        // Data loading methods
        async loadPosts() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && !(key === 'min_success_score' && parseFloat(value) === 0)) {
                        params.append(key, value);
                    }
                });

                const response = await fetch(`/orgs/${this.orgId}/social/history/api/posts?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    this.posts = data.data?.data || data.data || [];
                    this.updateStats();
                }
            } catch (error) {
                console.error('Failed to load posts:', error);
            } finally {
                this.loading = false;
            }
        },

        updateStats() {
            this.stats.totalImported = this.posts.length;
            this.stats.totalAnalyzed = this.posts.filter(p => p.is_analyzed).length;
            this.stats.inKB = this.posts.filter(p => p.is_in_knowledge_base).length;
            this.stats.highPerformers = this.posts.filter(p => p.success_label === 'high_performer').length;
        },

        async loadIntegrations() {
            try {
                const response = await fetch(`/orgs/${this.orgId}/settings/platform-connections/api/list`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.success) {
                    this.integrations = (data.data || []).filter(i =>
                        ['instagram', 'facebook', 'threads', 'twitter', 'linkedin', 'tiktok'].includes(i.platform_type)
                    );
                }
            } catch (error) {
                console.error('Failed to load integrations:', error);
            }
        },

        async loadCampaigns() {
            try {
                const response = await fetch(`/orgs/${this.orgId}/campaigns/api`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.success) {
                    this.campaigns = data.data?.data || data.data || [];
                }
            } catch (error) {
                console.error('Failed to load campaigns:', error);
            }
        },

        // Import methods
        async startImport() {
            if (!this.importData.integration_id) return;
            this.importing = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/history/api/import`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        integration_id: this.importData.integration_id,
                        limit: this.importData.limit || 100,
                        start_date: this.importData.start_date,
                        end_date: this.importData.end_date,
                        auto_analyze: this.importData.auto_analyze,
                        async: true
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showImportModal = false;
                    this.importData.integration_id = '';
                    setTimeout(() => this.loadPosts(), 3000);
                }
            } catch (error) {
                console.error('Failed to import:', error);
            } finally {
                this.importing = false;
            }
        },

        // Analysis methods
        async analyzePost(postId) {
            try {
                const response = await fetch(`/orgs/${this.orgId}/social/history/api/posts/${postId}/analyze`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    console.log('✓ Post sent for analysis');
                    setTimeout(() => this.loadPosts(), 2000);
                } else {
                    console.error('Failed to analyze:', data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Failed to analyze:', error);
            }
        },

        // Knowledge Base methods
        async addToKB(postIds) {
            try {
                const response = await fetch(`/orgs/${this.orgId}/social/history/api/kb/add`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    console.log('✓ Added to knowledge base successfully');
                    this.loadPosts();
                } else {
                    console.error('Failed to add to KB:', data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Failed to add to KB:', error);
            }
        },

        async removeFromKB(postIds) {
            try {
                await fetch(`/orgs/${this.orgId}/social/history/api/kb/remove`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });
                this.loadPosts();
            } catch (error) {
                console.error('Failed to remove from KB:', error);
            }
        },

        // Bulk operations
        bulkAddToKB() {
            this.addToKB(this.selectedPosts);
            this.selectedPosts = [];
        },

        bulkAnalyze() {
            this.selectedPosts.forEach(id => this.analyzePost(id));
            this.selectedPosts = [];
        },

        clearSelection() {
            this.selectedPosts = [];
        },

        // Filter methods
        resetFilters() {
            this.filters = { profile_group_id: '', platform: '', is_analyzed: '', is_in_kb: '', min_success_score: 0 };
            this.loadPosts();
        },

        // Campaign methods
        openCampaignModal(post) {
            this.selectedPostForCampaign = post;
            this.campaignData.campaign_id = '';
            this.campaignData.creative_type = 'image';
            this.showCampaignModal = true;
        },

        async addToCampaign() {
            if (!this.campaignData.campaign_id || !this.selectedPostForCampaign) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/history/api/posts/${this.selectedPostForCampaign.post_id}/add-to-campaign`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        campaign_id: this.campaignData.campaign_id,
                        creative_type: this.campaignData.creative_type
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showCampaignModal = false;
                    this.loadPosts();
                } else {
                    console.error('Failed to add to campaign:', data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Failed to add to campaign:', error);
            }
        },

        // Modal methods
        viewPost(post) {
            this.selectedPost = post;
            this.currentMediaIndex = 0;
            this.showDetailModal = true;
        },

        // Utility methods
        getPostMedia(post) {
            let media = [];

            if (post.metadata?.platform_data?.full_picture) {
                media.push(post.metadata.platform_data.full_picture);
            }

            if (post.media_url) {
                media.push(post.media_url);
            }

            if (post.media && Array.isArray(post.media)) {
                post.media.forEach(m => {
                    if (typeof m === 'string') media.push(m);
                    else if (m.url) media.push(m.url);
                });
            }

            if (post.media_assets && Array.isArray(post.media_assets)) {
                post.media_assets.forEach(m => {
                    if (m.original_url) media.push(m.original_url);
                });
            }

            if (post.metadata?.platform_data?.children?.data) {
                post.metadata.platform_data.children.data.forEach(child => {
                    if (child.media_url) media.push(child.media_url);
                });
            }

            return [...new Set(media)];
        },

        getMediaType(url) {
            if (!url) return 'image';
            if (url.match(/\.(mp4|mov|avi|webm)$/i)) return 'video';
            return 'image';
        },

        getMetric(post, type) {
            if (post.engagement_cache && type === 'likes') return post.engagement_cache;

            const platformData = post.metadata?.platform_data;
            if (platformData) {
                if (type === 'likes') return platformData.likes?.summary?.total_count || platformData.like_count || 0;
                if (type === 'comments') return platformData.comments?.summary?.total_count || platformData.comments_count || 0;
                if (type === 'shares') return platformData.shares?.count || 0;
            }

            return 0;
        },

        formatNumber(num) {
            if (!num) return '0';
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ar-SA', { year: 'numeric', month: 'short', day: 'numeric' });
        },

        getPlatformName(platform) {
            const names = { instagram: 'إنستغرام', facebook: 'فيسبوك', threads: 'ثريدز', twitter: 'تويتر', linkedin: 'لينكد إن', tiktok: 'تيك توك' };
            return names[platform] || platform;
        },

        getPlatformBgClass(platform) {
            const classes = {
                facebook: 'bg-[#1877F2]',
                instagram: 'bg-gradient-to-br from-[#833AB4] via-[#FD1D1D] to-[#F77737]',
                twitter: 'bg-[#1DA1F2]',
                linkedin: 'bg-[#0A66C2]',
                tiktok: 'bg-black',
                threads: 'bg-black'
            };
            return classes[platform] || 'bg-gray-500';
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                instagram: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
                twitter: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
                linkedin: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                tiktok: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
                threads: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.33-3.022.88-.73 2.082-1.123 3.479-1.14.967-.01 1.904.132 2.794.425.02-.455.038-.882.022-1.325-.057-1.254-.407-2.21-1.04-2.843-.658-.657-1.627-.98-2.88-.96-1.134.017-2.072.343-2.793.97-.654.566-1.075 1.322-1.253 2.25l-2.019-.457c.268-1.344.893-2.47 1.86-3.35 1.062-.968 2.442-1.478 4.1-1.518h.095c1.867.037 3.368.572 4.462 1.588 1.058.98 1.633 2.355 1.708 4.085.012.384-.006.781-.054 1.186 1.156.553 2.095 1.334 2.763 2.295.872 1.257 1.058 2.79.523 4.32-.55 1.574-1.77 2.923-3.433 3.797-1.527.8-3.346 1.21-5.41 1.218l-.036-.001z"/></svg>'
            };
            return icons[platform] || '';
        },

        getScoreColorClass(score) {
            if (!score) return 'bg-gray-300';
            if (score >= 0.7) return 'bg-gradient-to-r from-green-400 to-green-600';
            if (score >= 0.4) return 'bg-gradient-to-r from-yellow-400 to-yellow-600';
            return 'bg-gradient-to-r from-red-400 to-red-600';
        },

        getScoreTextClass(score) {
            if (!score) return 'text-gray-500';
            if (score >= 0.7) return 'text-green-600';
            if (score >= 0.4) return 'text-yellow-600';
            return 'text-red-600';
        }
    };
}

// Make globally available for Alpine.js
window.historicalContentManager = historicalContentManager;

export default historicalContentManager;
