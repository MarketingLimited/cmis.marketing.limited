// Translations object for JavaScript
const translations = {
    // Days of week
    daySunday: @json(__('social.days.sunday')),
    dayMonday: @json(__('social.days.monday')),
    dayTuesday: @json(__('social.days.tuesday')),
    dayWednesday: @json(__('social.days.wednesday')),
    dayThursday: @json(__('social.days.thursday')),
    dayFriday: @json(__('social.days.friday')),
    daySaturday: @json(__('social.days.saturday')),

    // Post actions
    confirmDeletePosts: @json(__('social.confirm_delete_posts')),
    postsDeletedSuccess: @json(__('social.posts_deleted_success')),
    writeContentFirst: @json(__('social.write_content_first')),
    generatingHashtags: @json(__('social.generating_hashtags')),
    conversionFailed: @json(__('social.conversion_failed')),
    contentCopiedSuccess: @json(__('social.content_copied_success')),

    // Post status messages
    postPublishedSuccess: @json(__('social.post_published_success')),
    postScheduledSuccess: @json(__('social.post_scheduled_success')),
    postQueuedSuccess: @json(__('social.post_queued_success')),
    draftSavedSuccess: @json(__('social.draft_saved_success')),
    postSaveFailed: @json(__('social.post_save_failed')),
    postUpdatedSuccess: @json(__('social.post_updated_success')),
    postUpdateFailed: @json(__('social.post_update_failed')),

    // Publish actions
    confirmPublishNow: @json(__('social.confirm_publish_now')),
    postPublishFailed: @json(__('social.post_publish_failed')),
    confirmRetryPublish: @json(__('social.confirm_retry_publish')),
    retryFailed: @json(__('social.retry_failed')),

    // Delete actions
    confirmDeletePost: @json(__('social.confirm_delete_post')),
    postDeletedSuccess: @json(__('social.post_deleted_success')),
    postDeleteFailed: @json(__('social.post_delete_failed')),
    confirmDeleteFailedPosts: @json(__('social.confirm_delete_failed_posts')),
    failedPostsDeletedSuccess: @json(__('social.failed_posts_deleted_success')),
    failedPostsDeleteFailed: @json(__('social.failed_posts_delete_failed')),

    // Post types
    postTypeFeed: @json(__('social.post_types.feed')),
    postTypeReel: @json(__('social.post_types.reel')),
    postTypeStory: @json(__('social.post_types.story')),
    postTypeCarousel: @json(__('social.post_types.carousel')),
    postTypePost: @json(__('social.post_types.post')),

    // Status labels
    statusScheduled: @json(__('social.scheduled_status')),
    statusPublished: @json(__('social.published_status')),
    statusDraft: @json(__('social.draft_status')),
    statusFailed: @json(__('social.failed_status')),

    // Additional post types
    postTypeTweet: @json(__('social.post_types.tweet')),
    postTypeThread: @json(__('social.thread')),
    postTypeArticle: @json(__('social.post_types.article')),

    // Best times
    bestTimeMorning: @json(__('social.best_times.morning')),
    bestTimeNoon: @json(__('social.best_times.noon')),
    bestTimeEvening: @json(__('social.best_times.evening')),
    bestTimeNight: @json(__('social.best_times.night'))
};

// Helper function to replace placeholders in translations
function trans(key, replacements = {}) {
    let translation = translations[key] || key;
    Object.keys(replacements).forEach(placeholder => {
        translation = translation.replace(':' + placeholder, replacements[placeholder]);
    });
    return translation;
}

function socialManager() {
    return {
        // Posts list state
        posts: [],
        filterPlatform: 'all',
        statusFilter: 'all',
        filterPostType: 'all',
        searchQuery: '',
        sortBy: 'newest',
        viewMode: 'grid',
        scheduledCount: 0,
        publishedCount: 0,
        draftCount: 0,
        failedCount: 0,
        selectedPosts: [],

        // Calendar state
        currentDate: new Date(),
        dayNames: [
            translations.daySunday,
            translations.dayMonday,
            translations.dayTuesday,
            translations.dayWednesday,
            translations.dayThursday,
            translations.dayFriday,
            translations.daySaturday
        ],

        // New post state (used by global publish modal)
        loadingPlatforms: false,
        connectedPlatforms: [],
        selectedPlatformIds: [],

        // Timezone state
        profileGroupTimezone: null,
        profileGroupName: null,
        timezoneOffset: null,
        timezoneLoading: false,

        isSubmitting: false,
        dragOver: false,
        uploadedMedia: [],
        showAiAssistant: false,
        previewPlatform: 'facebook',
        newPost: {
            content: '',
            publishType: 'now',
            scheduledDate: '',
            scheduledTime: '',
            postType: 'feed', // Default post type
            firstComment: '', // First comment for Instagram/Facebook
            location: '', // Location tag
            locationId: null, // Location ID for API
        },

        // Input helper variables
        collaboratorInput: '',
        productTagInput: '',
        userTagInput: '',

        // Location autocomplete state
        locationQuery: '',
        locationResults: [],
        showLocationDropdown: false,
        isSearchingLocations: false,
        selectedLocation: null,
        locationSearchTimeout: null,

        // Collaborator suggestions state
        collaboratorSuggestions: [],
        showCollaboratorSuggestions: false,
        isValidatingUsername: false,
        usernameValidationResult: null,
        usernameValidationTimeout: null,
        validatedUserInfo: null,

        // Product details state
        showProductDetails: false,
        currencies: [
            { code: 'SAR', symbol: 'ر.س', name: 'ريال سعودي' },
            { code: 'AED', symbol: 'د.إ', name: 'درهم إماراتي' },
            { code: 'USD', symbol: '$', name: 'دولار أمريكي' },
            { code: 'EUR', symbol: '€', name: 'يورو' },
            { code: 'GBP', symbol: '£', name: 'جنيه إسترليني' },
            { code: 'EGP', symbol: 'ج.م', name: 'جنيه مصري' },
            { code: 'KWD', symbol: 'د.ك', name: 'دينار كويتي' },
            { code: 'QAR', symbol: 'ر.ق', name: 'ريال قطري' },
            { code: 'BHD', symbol: 'د.ب', name: 'دينار بحريني' },
            { code: 'OMR', symbol: 'ر.ع', name: 'ريال عماني' },
        ],

        // Post type specific options (API-supported only)
        postOptions: {
            // Instagram/Facebook API-Supported Options
            instagram: {
                location: '', // Location name (API: location_id)
                locationId: '', // Facebook Places ID
                userTags: [], // Tagged users [{username, x, y}] (API: user_tags)
                collaborators: [], // Collaborators up to 3 (API: collaborators)
                productTags: [], // Product tags for shopping posts
                altText: '', // Alt text for accessibility (API: alt_text)
                firstComment: '', // Auto-post first comment (API: /comments endpoint)
            },

            // Product Details (for DM-based orders - no Instagram Shopping required)
            product: {
                enabled: false,
                title: '',
                price: '',
                currency: 'SAR',
                description: '',
                orderMessage: 'للطلب، أرسل رسالة مباشرة 📩', // Default CTA
            },

            // Reel API-Supported Options
            reel: {
                coverType: 'frame', // 'frame' or 'custom'
                coverFrameOffset: 0, // milliseconds (API: thumb_offset)
                coverImageUrl: '', // custom cover (API: cover_url)
                shareToFeed: true, // Show reel in feed (API: share_to_feed)
            },

            // Story Options (limited API support)
            story: {
                duration: 5, // seconds per slide (for reference only)
            },

            // Carousel API-Supported Options
            carousel: {
                altTexts: [], // Alt text for each image (API: alt_text per child)
            },

            // TikTok Content Posting API Options
            tiktok: {
                viewerSetting: 'public', // API: privacy_level (PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, SELF_ONLY)
                disableComments: false, // API: disable_comment
                disableDuet: false, // API: disable_duet
                disableStitch: false, // API: disable_stitch
                brandContentToggle: false, // API: brand_content_toggle
                aiGenerated: false, // API: ai_disclosure
            },

            // LinkedIn Posts API Options
            linkedin: {
                visibility: 'PUBLIC', // API: visibility (PUBLIC, CONNECTIONS)
                articleTitle: '', // API: article title
                articleDescription: '', // API: article description
                allowComments: true, // API: allowComments
            },

            // Twitter/X API Options
            twitter: {
                threadTweets: [''], // Multiple tweets for thread
                replyRestriction: 'everyone', // API: reply_settings (everyone, mentionedUsers, following)
                altText: '', // API: alt_text for media
            },
        },

        // Post types configuration
        allPostTypes: {
            'facebook': [
                {value: 'feed', label: translations.postTypeFeed, icon: 'fa-newspaper'},
                {value: 'reel', label: translations.postTypeReel, icon: 'fa-video'},
                {value: 'story', label: translations.postTypeStory, icon: 'fa-circle'}
            ],
            'instagram': [
                {value: 'feed', label: translations.postTypeFeed, icon: 'fa-image'},
                {value: 'reel', label: translations.postTypeReel, icon: 'fa-video'},
                {value: 'story', label: translations.postTypeStory, icon: 'fa-circle'},
                {value: 'carousel', label: translations.postTypeCarousel, icon: 'fa-images'}
            ],
            'twitter': [
                {value: 'tweet', label: translations.postTypeTweet, icon: 'fa-comment'},
                {value: 'thread', label: translations.postTypeThread, icon: 'fa-list'}
            ],
            'linkedin': [
                {value: 'post', label: translations.postTypePost, icon: 'fa-file-alt'},
                {value: 'article', label: translations.postTypeArticle, icon: 'fa-newspaper'}
            ]
        },

        // Best times suggestions
        bestTimes: [
            { label: translations.bestTimeMorning, value: '09:00', engagement: '+23%' },
            { label: translations.bestTimeNoon, value: '12:00', engagement: '+18%' },
            { label: translations.bestTimeEvening, value: '18:00', engagement: '+31%' },
            { label: translations.bestTimeNight, value: '21:00', engagement: '+15%' }
        ],

        // Edit post modal state
        showEditPostModal: false,
        editingPost: {
            id: null,
            content: '',
            platform: '',
            status: '',
            scheduled_at: null,
            media: [],
            account_username: '',
            integration_id: null
        },
        editTimezone: 'UTC',
        editTimezoneLoading: false,
        isUpdating: false,
        isDeletingFailed: false,

        // Platform character limits (from config/social-platforms.php)
        editPlatformLimits: {
            twitter: 280,
            facebook: 63206,
            instagram: 2200,
            linkedin: 3000,
            tiktok: 2200,
            threads: 500,
            youtube: 5000,
            pinterest: 500,
            tumblr: 4096,
            reddit: 40000,
            snapchat: 250,
            google_business: 1500
        },

        // Edit Modal Toolbar State
        showEditEmojiPicker: false,

        // Common emojis for quick access
        editCommonEmojis: [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
            '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐',
            '👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '🤌', '🤏', '👈', '👉', '👆',
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
            '🔥', '✨', '🎉', '🎊', '💯', '⭐', '🌟', '💫', '🚀', '💪', '🙏', '👏', '🎯', '💡', '📢', '🔔'
        ],

        // AI Content Assistance State
        showEditAIPanel: false,
        editAILoading: false,
        editAILoadingType: null, // 'hashtags', 'emojis', 'improve', 'transform'
        editAISuggestions: {
            hashtags: [],
            improved: null,
            variations: []
        },
        editAIError: null,

        // Media Management State
        editMediaDraggedIndex: null,
        editMediaDragOverIndex: null,
        editMediaDragging: false,
        editMediaUploading: false,
        editMediaUploadProgress: 0,

        // Hashtag Manager
        showHashtagManager: false,
        hashtagSets: [],
        recentHashtags: [],
        loadingTrendingHashtags: false,
        trendingHashtags: [],

        // Mention Picker
        showMentionPicker: false,
        mentionSearch: '',
        availableMentions: [],

        // Calendar
        showCalendar: false,
        calendarYear: new Date().getFullYear(),
        calendarMonth: new Date().getMonth(),
        scheduledPosts: [],

        // Best Times Modal
        showBestTimes: false,
        optimalTimes: [],

        // Media Source Picker
        showMediaSourcePicker: false,
        mediaUrlInput: '',

        // Media Library
        showMediaLibrary: false,
        mediaLibraryFiles: [],

        // Platform Warnings
        platformWarnings: [],

        // Days of week for scheduling
        daysOfWeek: [
            {v: 0, l: @json(__('social.days.sunday')), s: 'ح'},
            {v: 1, l: @json(__('social.days.monday')), s: 'ن'},
            {v: 2, l: @json(__('social.days.tuesday')), s: 'ث'},
            {v: 3, l: @json(__('social.days.wednesday')), s: 'ر'},
            {v: 4, l: @json(__('social.days.thursday')), s: 'خ'},
            {v: 5, l: @json(__('social.days.friday')), s: 'ج'},
            {v: 6, l: @json(__('social.days.saturday')), s: 'س'}
        ],

        // Get the org ID from the URL
        get orgId() {
            const match = window.location.pathname.match(/\/orgs\/([^\/]+)/);
            return match ? match[1] : null;
        },

        // Minimum date for scheduling (today)
        get minDate() {
            return new Date().toISOString().split('T')[0];
        },

        // Check platform selections
        get hasInstagramSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'instagram'
            );
        },

        get hasTwitterSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'twitter'
            );
        },

        get hasFacebookSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'facebook'
            );
        },

        get hasTikTokSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'tiktok'
            );
        },

        get hasLinkedInSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'linkedin'
            );
        },

        // Get available post types based on selected platforms
        get availablePostTypes() {
            if (this.selectedPlatformIds.length === 0) {
                return [];
            }

            // Get unique platforms from selected platform IDs
            const selectedPlatforms = this.connectedPlatforms
                .filter(p => this.selectedPlatformIds.includes(p.id))
                .map(p => p.type);

            const uniquePlatforms = [...new Set(selectedPlatforms)];

            // If multiple platforms selected, show common post types
            if (uniquePlatforms.length > 1) {
                // Find common post types across all selected platforms
                const platformPostTypes = uniquePlatforms.map(platform =>
                    this.allPostTypes[platform] || []
                );

                // Get the intersection of all post types (common across all platforms)
                if (platformPostTypes.length === 0) {
                    return [{value: 'feed', label: translations.postTypeFeed, icon: 'fa-newspaper'}];
                }

                // Find post types that exist in all platforms
                const commonPostTypes = platformPostTypes[0].filter(postType =>
                    platformPostTypes.every(types =>
                        types.some(t => t.value === postType.value)
                    )
                );

                // If no common types, default to feed
                return commonPostTypes.length > 0
                    ? commonPostTypes
                    : [{value: 'feed', label: translations.postTypeFeed, icon: 'fa-newspaper'}];
            }

            // Single platform selected, return its specific post types
            const platform = uniquePlatforms[0];
            return this.allPostTypes[platform] || [{value: 'feed', label: translations.postTypeFeed, icon: 'fa-newspaper'}];
        },

        get selectedPlatformsForPreview() {
            return this.connectedPlatforms.filter(p => this.selectedPlatformIds.includes(p.id));
        },

        // Can submit the form
        get canSubmit() {
            const hasContent = this.newPost.content.trim().length > 0;
            const hasPlatforms = this.selectedPlatformIds.length > 0;
            const hasScheduleIfNeeded = this.newPost.publishType !== 'scheduled' ||
                                        (this.newPost.scheduledDate && this.newPost.scheduledTime);
            const hasMediaForInstagram = !this.hasInstagramSelected || this.uploadedMedia.length > 0;
            return hasContent && hasPlatforms && hasScheduleIfNeeded && hasMediaForInstagram;
        },

        // Unique platforms from posts (for dynamic platform filter)
        get uniquePlatforms() {
            const platforms = [...new Set(this.posts.map(p => p.platform).filter(Boolean))];
            return platforms.sort();
        },

        // Helper methods for platform display
        getPlatformIcon(platform) {
            const icons = {
                'facebook': 'fab fa-facebook',
                'instagram': 'fab fa-instagram',
                'twitter': 'fab fa-twitter',
                'x': 'fab fa-x-twitter',
                'linkedin': 'fab fa-linkedin',
                'youtube': 'fab fa-youtube',
                'tiktok': 'fab fa-tiktok',
                'pinterest': 'fab fa-pinterest',
                'reddit': 'fab fa-reddit',
                'tumblr': 'fab fa-tumblr',
                'threads': 'fab fa-at',
                'google_business': 'fab fa-google'
            };
            return icons[platform] || 'fas fa-share-alt';
        },

        getPlatformName(platform) {
            const names = {
                'facebook': 'Facebook',
                'instagram': 'Instagram',
                'twitter': 'Twitter',
                'x': 'X',
                'linkedin': 'LinkedIn',
                'youtube': 'YouTube',
                'tiktok': 'TikTok',
                'pinterest': 'Pinterest',
                'reddit': 'Reddit',
                'tumblr': 'Tumblr',
                'threads': 'Threads',
                'google_business': 'Google Business'
            };
            return names[platform] || platform;
        },

        getPlatformFilterClass(platform, active) {
            if (!active) return 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            const classes = {
                'facebook': 'bg-blue-600 text-white',
                'instagram': 'bg-gradient-to-r from-purple-600 to-pink-600 text-white',
                'twitter': 'bg-sky-500 text-white',
                'x': 'bg-black text-white',
                'linkedin': 'bg-blue-700 text-white',
                'youtube': 'bg-red-600 text-white',
                'tiktok': 'bg-black text-white',
                'pinterest': 'bg-red-700 text-white',
                'reddit': 'bg-orange-600 text-white',
                'tumblr': 'bg-indigo-800 text-white',
                'threads': 'bg-black text-white',
                'google_business': 'bg-blue-500 text-white'
            };
            return classes[platform] || 'bg-gray-600 text-white';
        },

        // Calendar helper method
        getCalendarDays() {
            const year = this.calendarYear;
            const month = this.calendarMonth;
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            const days = [];
            // Add empty cells for days before month starts
            for (let i = 0; i < startingDayOfWeek; i++) {
                days.push(null);
            }
            // Add actual days
            for (let day = 1; day <= daysInMonth; day++) {
                days.push(day);
            }
            return days;
        },

        // Sorted and filtered posts
        get sortedFilteredPosts() {
            let filtered = this.posts.filter(post => {
                const platformMatch = this.filterPlatform === 'all' || post.platform === this.filterPlatform;
                const statusMatch = this.statusFilter === 'all' || post.status === this.statusFilter;
                const postTypeMatch = this.filterPostType === 'all' || post.post_type === this.filterPostType;
                const searchMatch = !this.searchQuery ||
                    (post.post_text && post.post_text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                return platformMatch && statusMatch && postTypeMatch && searchMatch;
            });

            // Sort
            switch(this.sortBy) {
                case 'oldest':
                    filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    break;
                case 'scheduled':
                    filtered.sort((a, b) => {
                        if (!a.scheduled_at) return 1;
                        if (!b.scheduled_at) return -1;
                        return new Date(a.scheduled_at) - new Date(b.scheduled_at);
                    });
                    break;
                case 'platform':
                    filtered.sort((a, b) => (a.platform || '').localeCompare(b.platform || ''));
                    break;
                default: // newest
                    filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }

            return filtered;
        },

        // Calendar helpers
        get currentMonthYear() {
            return this.currentDate.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const days = [];

            // Previous month days
            const firstDayOfWeek = firstDay.getDay();
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const date = new Date(year, month, -i);
                days.push(this.createDayObject(date, false));
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                days.push(this.createDayObject(date, true));
            }

            // Next month days to fill grid
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) {
                const date = new Date(year, month + 1, i);
                days.push(this.createDayObject(date, false));
            }

            return days;
        },

        createDayObject(date, isCurrentMonth) {
            const dateStr = date.toISOString().split('T')[0];
            const today = new Date().toISOString().split('T')[0];
            return {
                date: dateStr,
                dayNumber: date.getDate(),
                isCurrentMonth,
                isToday: dateStr === today,
                posts: this.posts.filter(p => {
                    const postDate = p.scheduled_at || p.published_at || p.created_at;
                    return postDate && postDate.startsWith(dateStr);
                })
            };
        },

        changeMonth(delta) {
            this.currentDate = new Date(
                this.currentDate.getFullYear(),
                this.currentDate.getMonth() + delta,
                1
            );
        },

        async init() {
            // Debug: v2025.11.26.1040 - Added dynamic filters
            console.log('[CMIS Social] v1.0.2 - Initializing, orgId:', this.orgId);

            // Load posts, connected platforms, and collaborator suggestions in parallel
            await Promise.all([
                this.fetchPosts(),
                this.loadConnectedPlatforms(),
                this.loadCollaboratorSuggestions()
            ]);
            console.log('[CMIS Social] Posts loaded:', this.posts.length, 'posts');
            console.log('[CMIS Social] Platforms loaded:', this.connectedPlatforms.length, 'platforms');
            console.log('[CMIS Social] Collaborator suggestions loaded:', this.collaboratorSuggestions.length);

            // Set default schedule time to tomorrow 10 AM
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';

            // Update preview platform and fetch timezone when selection changes
            this.$watch('selectedPlatformIds', async (ids) => {
                if (ids.length > 0) {
                    const platform = this.connectedPlatforms.find(p => ids.includes(p.id));
                    if (platform) this.previewPlatform = platform.type;

                    // Fetch timezone for selected platforms
                    await this.fetchProfileGroupTimezone();
                } else {
                    // Reset timezone when no platforms selected
                    this.profileGroupTimezone = null;
                    this.profileGroupName = null;
                    this.timezoneOffset = null;
                }
            });
        },

        async fetchPosts() {
            console.log('[CMIS Social] fetchPosts() called, orgId:', this.orgId);
            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts-json`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                console.log('[CMIS Social] API response status:', response.status);
                const result = await response.json();
                console.log('[CMIS Social] API result:', result);

                if (result.success && result.data) {
                    this.posts = result.data.data || result.data || [];
                    console.log('[CMIS Social] Posts set from result.data.data:', this.posts.length);
                } else if (Array.isArray(result.data)) {
                    this.posts = result.data;
                    console.log('[CMIS Social] Posts set from result.data array:', this.posts.length);
                } else {
                    this.posts = [];
                    console.log('[CMIS Social] Posts set to empty array');
                }
                this.updateCounts();
            } catch (error) {
                console.error('[CMIS Social] Failed to fetch posts:', error);
                this.posts = [];
            }
        },

        async fetchProfileGroupTimezone() {
            if (this.selectedPlatformIds.length === 0) {
                this.profileGroupTimezone = null;
                return;
            }

            this.timezoneLoading = true;
            try {
                // Get integration IDs from selected platforms
                const integrationIds = this.selectedPlatformIds
                    .map(id => {
                        const platform = this.connectedPlatforms.find(p => p.id === id);
                        return platform?.integrationId;
                    })
                    .filter(Boolean);

                if (integrationIds.length === 0) {
                    console.log('[CMIS Social] No integration IDs found for selected platforms');
                    return;
                }

                console.log('[CMIS Social] Fetching timezone for integrations:', integrationIds);

                const response = await fetch(`/api/orgs/${this.orgId}/social/timezone`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ integration_ids: integrationIds })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success && result.data) {
                    this.profileGroupTimezone = result.data.timezone;
                    this.profileGroupName = result.data.profile_group_name;

                    // Calculate timezone offset for display
                    const tz = result.data.timezone;
                    const now = new Date();
                    const formatter = new Intl.DateTimeFormat('en-US', {
                        timeZone: tz,
                        year: 'numeric',
                        month: 'numeric',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric'
                    });
                    const parts = formatter.formatToParts(now);
                    const tzDate = new Date(
                        parts.find(p => p.type === 'year').value,
                        parts.find(p => p.type === 'month').value - 1,
                        parts.find(p => p.type === 'day').value,
                        parts.find(p => p.type === 'hour').value,
                        parts.find(p => p.type === 'minute').value,
                        parts.find(p => p.type === 'second').value
                    );

                    const offset = (tzDate - now) / 1000 / 60; // minutes
                    const hours = Math.floor(Math.abs(offset) / 60);
                    const mins = Math.abs(offset) % 60;
                    this.timezoneOffset = `${offset >= 0 ? '+' : '-'}${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;

                    console.log('[CMIS Social] Timezone loaded:', tz, this.timezoneOffset, 'from:', result.data.timezone_source);

                    if (result.data.warning) {
                        console.warn('[CMIS Social] Timezone warning:', result.data.warning);
                    }
                } else {
                    throw new Error('Invalid API response');
                }
            } catch (error) {
                console.error('[CMIS Social] Failed to fetch timezone:', error);
                this.profileGroupTimezone = 'UTC';
                this.timezoneOffset = '+00:00';
            } finally {
                this.timezoneLoading = false;
            }
        },

        async loadConnectedPlatforms() {
            this.loadingPlatforms = true;
            try {
                const response = await fetch(`/orgs/${this.orgId}/social/accounts`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();

                if (data.success && data.data?.accounts) {
                    this.connectedPlatforms = data.data.accounts.map(account => ({
                        id: account.id,
                        type: account.type,
                        name: account.name,
                        platformId: account.platformId,
                        picture: account.picture,
                        username: account.username,
                        connectionId: account.connectionId,
                        pageId: account.type === 'facebook' ? account.platformId : null,
                        accountId: account.type === 'instagram' ? account.platformId : null,
                    }));
                } else {
                    this.connectedPlatforms = [];
                }
            } catch (error) {
                console.error('Failed to load platforms:', error);
                this.connectedPlatforms = [];
            } finally {
                this.loadingPlatforms = false;
            }
        },

        togglePlatformSelection(platform) {
            const index = this.selectedPlatformIds.indexOf(platform.id);
            if (index === -1) {
                this.selectedPlatformIds.push(platform.id);
            } else {
                this.selectedPlatformIds.splice(index, 1);
            }
        },

        togglePostSelection(postId) {
            const index = this.selectedPosts.indexOf(postId);
            if (index === -1) {
                this.selectedPosts.push(postId);
            } else {
                this.selectedPosts.splice(index, 1);
            }
        },

        toggleAllPosts(event) {
            if (event.target.checked) {
                this.selectedPosts = this.sortedFilteredPosts.map(p => p.post_id);
            } else {
                this.selectedPosts = [];
            }
        },

        async bulkDelete() {
            if (!confirm(trans('confirmDeletePosts', {count: this.selectedPosts.length}))) return;

            for (const postId of this.selectedPosts) {
                await this.deletePost(postId, false);
            }
            this.selectedPosts = [];
            await this.fetchPosts();
            if (window.notify) {
                window.notify(translations.postsDeletedSuccess, 'success');
            }
        },

        setBestTime(time) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = time.value;
        },

        async aiSuggest(type) {
            const content = this.newPost.content;
            if (!content) {
                if (window.notify) {
                    window.notify(translations.writeContentFirst, 'warning');
                }
                return;
            }

            // Show loading state
            const loadingMessage = {
                'shorter': 'جاري الاختصار...',
                'longer': 'جاري التوسع...',
                'formal': 'جاري تحويل الأسلوب...',
                'casual': 'جاري تحويل الأسلوب...',
                'hashtags': translations.generatingHashtags,
                'emojis': 'جاري إضافة الإيموجي...',
            }[type] || 'جاري المعالجة...';

            if (window.notify) {
                window.notify(loadingMessage, 'info');
            }

            // Disable the button temporarily
            const originalContent = this.newPost.content;

            try {
                // Call the AI API
                const response = await fetch(`/orgs/${this.orgId}/social/ai/transform-content`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: content,
                        type: type,
                        platform: 'general'
                    })
                });

                const data = await response.json();

                if (response.ok && data.success && data.data.transformed) {
                    this.newPost.content = data.data.transformed;
                    if (window.notify) {
                        window.notify('تم التحويل بنجاح!', 'success');
                    }
                } else {
                    // Show detailed validation errors for debugging
                    let errorMessage = data.message || translations.conversionFailed;
                    if (data.errors) {
                        const errorDetails = Object.values(data.errors).flat().join(', ');
                        errorMessage += ': ' + errorDetails;
                    }
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('AI transformation error:', error);
                if (window.notify) {
                    window.notify('حدث خطأ في مساعد AI: ' + error.message, 'error');
                }
                // Restore original content on error
                this.newPost.content = originalContent;
            }
        },

        duplicatePost(post) {
            // Dispatch event to open global publish modal with pre-filled content
            window.dispatchEvent(new CustomEvent('open-publish-modal', {
                detail: { content: post.post_text || post.content || '' }
            }));
            if (window.notify) {
                window.notify(translations.contentCopiedSuccess, 'success');
            }
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },

        processFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.uploadedMedia.push({
                            file: file,
                            preview: e.target.result,
                            type: file.type.startsWith('image/') ? 'image' : 'video'
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeMedia(index) {
            this.uploadedMedia.splice(index, 1);
        },

        // Location Search Methods
        searchLocations() {
            // Clear previous timeout
            if (this.locationSearchTimeout) {
                clearTimeout(this.locationSearchTimeout);
            }

            // Don't search if query is too short
            if (this.locationQuery.length < 2) {
                this.locationResults = [];
                this.showLocationDropdown = false;
                return;
            }

            // Debounce search by 300ms
            this.locationSearchTimeout = setTimeout(async () => {
                this.isSearchingLocations = true;
                try {
                    const response = await fetch(`/orgs/${this.orgId}/social/locations/search?query=${encodeURIComponent(this.locationQuery)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.data) {
                        this.locationResults = result.data;
                        this.showLocationDropdown = true;
                    } else {
                        this.locationResults = [];
                        console.warn('Location search failed:', result.message);
                    }
                } catch (error) {
                    console.error('Location search error:', error);
                    this.locationResults = [];
                } finally {
                    this.isSearchingLocations = false;
                }
            }, 300);
        },

        selectLocation(location) {
            this.selectedLocation = location;
            this.postOptions.instagram.location = location.name;
            this.postOptions.instagram.locationId = location.id;
            this.locationQuery = '';
            this.locationResults = [];
            this.showLocationDropdown = false;
        },

        clearLocation() {
            this.selectedLocation = null;
            this.postOptions.instagram.location = '';
            this.postOptions.instagram.locationId = '';
            this.locationQuery = '';
        },

        // Collaborator Methods
        async loadCollaboratorSuggestions() {
            try {
                const response = await fetch(`/orgs/${this.orgId}/social/collaborators/suggestions`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    this.collaboratorSuggestions = result.data?.collaborators || [];
                }
            } catch (error) {
                console.error('Failed to load collaborator suggestions:', error);
            }
        },

        get filteredCollaboratorSuggestions() {
            const input = this.collaboratorInput?.replace('@', '').toLowerCase() || '';
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());

            return this.collaboratorSuggestions.filter(s => {
                const lowerS = s.toLowerCase();
                return !existing.includes(lowerS) &&
                       (input === '' || lowerS.includes(input));
            }).slice(0, 5);
        },

        searchCollaborators() {
            // Show suggestions dropdown when typing
            this.showCollaboratorSuggestions = true;
            this.usernameValidationResult = null;

            // Debounce validation
            if (this.usernameValidationTimeout) {
                clearTimeout(this.usernameValidationTimeout);
            }

            const username = this.collaboratorInput?.replace('@', '').trim();
            if (username && username.length >= 2) {
                this.usernameValidationTimeout = setTimeout(() => {
                    this.validateUsername(username);
                }, 800);
            }
        },

        async validateUsername(username) {
            if (!username || username.length < 2) return;

            this.isValidatingUsername = true;
            this.usernameValidationResult = null;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/instagram/validate-username`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({ username: username })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.usernameValidationResult = result.data?.valid || false;
                    this.validatedUserInfo = result.data?.user || null;
                } else {
                    this.usernameValidationResult = null;
                }
            } catch (error) {
                console.error('Username validation failed:', error);
                this.usernameValidationResult = null;
            } finally {
                this.isValidatingUsername = false;
            }
        },

        async addCollaborator(username) {
            if (!username) return;

            // Clean the username
            username = username.replace('@', '').trim();
            if (!username) return;

            // Check if already added
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());
            if (existing.includes(username.toLowerCase())) {
                this.collaboratorInput = '';
                this.showCollaboratorSuggestions = false;
                return;
            }

            // Check limit (max 3)
            if (this.postOptions.instagram.collaborators.length >= 3) {
                if (window.notify) {
                    window.notify('يمكنك إضافة 3 متعاونين كحد أقصى', 'warning');
                }
                return;
            }

            // Add to list
            this.postOptions.instagram.collaborators.push(username);
            this.collaboratorInput = '';
            this.showCollaboratorSuggestions = false;
            this.usernameValidationResult = null;

            // Store for future suggestions (async, don't wait)
            fetch(`/orgs/${this.orgId}/social/collaborators`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ username: username })
            }).catch(() => {}); // Silently ignore errors
        },

        async savePost() {
            if (!this.canSubmit || this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.newPost.content);
                formData.append('publish_type', this.newPost.publishType);
                formData.append('post_type', this.newPost.postType); // Add post type

                const selectedPlatforms = this.connectedPlatforms.filter(p =>
                    this.selectedPlatformIds.includes(p.id)
                );
                formData.append('platforms', JSON.stringify(selectedPlatforms));

                // Add all post options as JSON
                formData.append('post_options', JSON.stringify(this.postOptions));

                // Add location if set
                if (this.newPost.location) {
                    formData.append('location', this.newPost.location);
                }

                // Add first comment if set
                if (this.newPost.firstComment) {
                    formData.append('first_comment', this.newPost.firstComment);
                }

                if (this.newPost.publishType === 'scheduled') {
                    formData.append('scheduled_at', `${this.newPost.scheduledDate}T${this.newPost.scheduledTime}:00`);
                }

                this.uploadedMedia.forEach((media, index) => {
                    formData.append(`media[${index}]`, media.file);
                });

                const response = await fetch(`/orgs/${this.orgId}/social/publish-modal/create`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    const messages = {
                        'now': translations.postPublishedSuccess,
                        'scheduled': translations.postScheduledSuccess,
                        'queue': translations.postQueuedSuccess,
                        'draft': translations.draftSavedSuccess
                    };
                    if (window.notify) {
                        window.notify(messages[this.newPost.publishType], 'success');
                    }
                    this.resetNewPost();
                    await this.fetchPosts();
                } else {
                    throw new Error(result.message || translations.postSaveFailed);
                }
            } catch (error) {
                console.error('Failed to save post:', error);
                if (window.notify) {
                    window.notify(error.message || translations.postSaveFailed, 'error');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        resetNewPost() {
            this.newPost = {
                content: '',
                publishType: 'now',
                scheduledDate: '',
                scheduledTime: ''
            };
            this.selectedPlatformIds = [];
            this.uploadedMedia = [];
            this.showAiAssistant = false;

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';
        },

        updateCounts() {
            this.scheduledCount = this.posts.filter(p => p.status === 'scheduled').length;
            this.publishedCount = this.posts.filter(p => p.status === 'published').length;
            this.draftCount = this.posts.filter(p => p.status === 'draft').length;
            this.failedCount = this.posts.filter(p => p.status === 'failed').length;
        },

        getStatusLabel(status) {
            const labels = {
                'scheduled': translations.statusScheduled,
                'published': translations.statusPublished,
                'draft': translations.statusDraft,
                'failed': translations.statusFailed
            };
            return labels[status] || status;
        },

        /**
         * Format date with timezone support
         * @param {string} date - UTC date string from database
         * @param {string} timezone - IANA timezone (e.g., 'Asia/Riyadh') from post.display_timezone
         * @returns {string} Formatted date in the specified timezone
         */
        formatDate(date, timezone = null) {
            if (!date) return '';

            const dateObj = new Date(date);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true,
                timeZoneName: 'short'
            };

            // Use post's timezone if provided, otherwise fall back to browser timezone
            if (timezone && timezone !== 'UTC') {
                try {
                    options.timeZone = timezone;
                    return dateObj.toLocaleString('en-GB', options);
                } catch (e) {
                    console.warn('[formatDate] Invalid timezone:', timezone, e);
                    // Fall back to browser timezone if invalid
                }
            }

            return dateObj.toLocaleString('en-GB', options);
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        // ============================================
        // Edit Post Character Validation Methods
        // ============================================

        /**
         * Get the character limit for the current platform being edited
         */
        getEditCharacterLimit() {
            const platform = this.editingPost?.platform?.toLowerCase() || '';
            return this.editPlatformLimits[platform] || 5000;
        },

        /**
         * Get the current character count percentage
         */
        getEditCharacterPercentage() {
            const count = this.editingPost?.content?.length || 0;
            const limit = this.getEditCharacterLimit();
            return Math.min((count / limit) * 100, 100);
        },

        /**
         * Get the character status: 'ok', 'caution', 'warning', or 'exceeded'
         */
        getEditCharacterStatus() {
            const count = this.editingPost?.content?.length || 0;
            const limit = this.getEditCharacterLimit();
            const percentage = (count / limit) * 100;

            if (percentage >= 100) return 'exceeded';
            if (percentage >= 90) return 'warning';
            if (percentage >= 75) return 'caution';
            return 'ok';
        },

        /**
         * Check if save should be disabled due to character limit
         */
        isEditContentValid() {
            const count = this.editingPost?.content?.length || 0;
            const limit = this.getEditCharacterLimit();
            return count > 0 && count <= limit;
        },

        // ============================================
        // Edit Post Toolbar Methods
        // ============================================

        /**
         * Insert emoji at cursor position in edit textarea
         */
        insertEditEmoji(emoji) {
            const textarea = this.$refs.editContentTextarea;
            if (!textarea) {
                this.editingPost.content += emoji;
                this.showEditEmojiPicker = false;
                return;
            }

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.editingPost.content || '';

            this.editingPost.content = text.slice(0, start) + emoji + text.slice(end);
            this.showEditEmojiPicker = false;

            // Reset cursor position after emoji
            this.$nextTick(() => {
                textarea.focus();
                const newPos = start + emoji.length;
                textarea.setSelectionRange(newPos, newPos);
            });
        },

        /**
         * Insert text at cursor position in edit textarea
         */
        insertEditText(text) {
            const textarea = this.$refs.editContentTextarea;
            if (!textarea) {
                this.editingPost.content += text;
                return;
            }

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const content = this.editingPost.content || '';

            this.editingPost.content = content.slice(0, start) + text + content.slice(end);

            // Reset cursor position after inserted text
            this.$nextTick(() => {
                textarea.focus();
                const newPos = start + text.length;
                textarea.setSelectionRange(newPos, newPos);
            });
        },

        /**
         * Apply markdown-style formatting to selected text
         */
        formatEditText(type) {
            const textarea = this.$refs.editContentTextarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.editingPost.content || '';
            const selectedText = text.slice(start, end);

            let wrapper = '';
            switch (type) {
                case 'bold':
                    wrapper = '**';
                    break;
                case 'italic':
                    wrapper = '_';
                    break;
                case 'underline':
                    wrapper = '__';
                    break;
                case 'strikethrough':
                    wrapper = '~~';
                    break;
                default:
                    return;
            }

            // If text is selected, wrap it; otherwise insert placeholder
            const newText = selectedText
                ? wrapper + selectedText + wrapper
                : wrapper + 'text' + wrapper;

            this.editingPost.content = text.slice(0, start) + newText + text.slice(end);

            // Position cursor appropriately
            this.$nextTick(() => {
                textarea.focus();
                if (selectedText) {
                    // Keep selection on the formatted text
                    const newStart = start + wrapper.length;
                    const newEnd = newStart + selectedText.length;
                    textarea.setSelectionRange(newStart, newEnd);
                } else {
                    // Select the placeholder "text"
                    const newStart = start + wrapper.length;
                    const newEnd = newStart + 4; // "text" length
                    textarea.setSelectionRange(newStart, newEnd);
                }
            });
        },

        // ============================================
        // AI Content Assistance Methods
        // ============================================

        /**
         * Generate hashtag suggestions for the current content
         */
        async generateEditHashtags() {
            if (this.editAILoading || !this.editingPost.content?.trim()) return;

            this.editAILoading = true;
            this.editAILoadingType = 'hashtags';
            this.editAIError = null;

            try {
                const response = await fetch('/api/ai/generate-hashtags', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        caption: this.editingPost.content,
                        platform: this.editingPost.platform
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.editAISuggestions.hashtags = result.data?.hashtags || result.hashtags || [];
                } else {
                    this.editAIError = result.message || 'Failed to generate hashtags';
                }
            } catch (error) {
                console.error('[AI] Hashtag generation failed:', error);
                this.editAIError = 'Failed to connect to AI service';
            } finally {
                this.editAILoading = false;
                this.editAILoadingType = null;
            }
        },

        /**
         * Transform content using AI (shorter, longer, formal, casual, emojis)
         */
        async transformEditContent(type) {
            if (this.editAILoading || !this.editingPost.content?.trim()) return;

            this.editAILoading = true;
            this.editAILoadingType = type;
            this.editAIError = null;

            try {
                const response = await fetch('/api/ai/transform-social-content', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: this.editingPost.content,
                        type: type,
                        platform: this.editingPost.platform
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const transformed = result.data?.content || result.content;
                    if (transformed) {
                        this.editingPost.content = transformed;
                    }
                } else {
                    this.editAIError = result.message || 'Failed to transform content';
                }
            } catch (error) {
                console.error('[AI] Content transformation failed:', error);
                this.editAIError = 'Failed to connect to AI service';
            } finally {
                this.editAILoading = false;
                this.editAILoadingType = null;
            }
        },

        /**
         * Get improvement suggestions for the current content
         */
        async improveEditContent() {
            if (this.editAILoading || !this.editingPost.content?.trim()) return;

            this.editAILoading = true;
            this.editAILoadingType = 'improve';
            this.editAIError = null;

            try {
                const response = await fetch('/api/ai/suggest-improvements', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: this.editingPost.content,
                        context: {
                            platform: this.editingPost.platform,
                            type: 'social_post'
                        }
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const improved = result.data?.improved || result.data?.content || result.improved;
                    if (improved) {
                        this.editAISuggestions.improved = improved;
                    }
                } else {
                    this.editAIError = result.message || 'Failed to get improvement suggestions';
                }
            } catch (error) {
                console.error('[AI] Content improvement failed:', error);
                this.editAIError = 'Failed to connect to AI service';
            } finally {
                this.editAILoading = false;
                this.editAILoadingType = null;
            }
        },

        /**
         * Insert a single hashtag into the content
         */
        insertEditHashtag(hashtag) {
            const tag = hashtag.startsWith('#') ? hashtag : '#' + hashtag;
            const space = this.editingPost.content && !this.editingPost.content.endsWith(' ') ? ' ' : '';
            this.editingPost.content += space + tag;
            this.editAISuggestions.hashtags = this.editAISuggestions.hashtags.filter(t => t !== hashtag);
        },

        /**
         * Insert all suggested hashtags
         */
        insertAllEditHashtags() {
            if (!this.editAISuggestions.hashtags?.length) return;

            const hashtags = this.editAISuggestions.hashtags
                .map(tag => tag.startsWith('#') ? tag : '#' + tag)
                .join(' ');

            const space = this.editingPost.content && !this.editingPost.content.endsWith(' ') ? ' ' : '';
            this.editingPost.content += space + hashtags;
            this.editAISuggestions.hashtags = [];
        },

        /**
         * Apply an AI suggestion to replace the content
         */
        applyEditSuggestion(content) {
            if (content) {
                this.editingPost.content = content;
                this.editAISuggestions.improved = null;
            }
        },

        // =============================================
        // MEDIA MANAGEMENT METHODS (Edit Post Modal)
        // =============================================

        /**
         * Reorder media items via drag and drop
         */
        reorderEditMedia(fromIndex, toIndex) {
            if (fromIndex === toIndex || !this.editingPost.media) return;

            const media = [...this.editingPost.media];
            const [movedItem] = media.splice(fromIndex, 1);
            media.splice(toIndex, 0, movedItem);

            this.editingPost.media = media;
            this.editMediaDraggedIndex = null;
            this.editMediaDragOverIndex = null;
            this.editMediaDragging = false;

            console.log('[Edit Media] Reordered media:', { from: fromIndex, to: toIndex });
        },

        /**
         * Remove media item from the post
         */
        removeEditMedia(index) {
            if (!this.editingPost.media || index < 0 || index >= this.editingPost.media.length) return;

            this.editingPost.media = this.editingPost.media.filter((_, i) => i !== index);
            console.log('[Edit Media] Removed media at index:', index);
        },

        /**
         * Handle file upload via input
         */
        async handleEditMediaUpload(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            await this.processEditMediaFiles(files);
            event.target.value = ''; // Reset input
        },

        /**
         * Handle file drop
         */
        async handleEditMediaDrop(event) {
            event.preventDefault();
            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) return;

            await this.processEditMediaFiles(files);
        },

        /**
         * Process uploaded media files
         */
        async processEditMediaFiles(files) {
            this.editMediaUploading = true;
            this.editMediaUploadProgress = 0;

            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime', 'video/webm'];
            const maxFileSize = 50 * 1024 * 1024; // 50MB

            try {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];

                    // Validate file type
                    if (!allowedTypes.includes(file.type)) {
                        console.warn('[Edit Media] Invalid file type:', file.type);
                        continue;
                    }

                    // Validate file size
                    if (file.size > maxFileSize) {
                        console.warn('[Edit Media] File too large:', file.name);
                        continue;
                    }

                    // Update progress
                    this.editMediaUploadProgress = Math.round(((i + 1) / files.length) * 100);

                    // Create form data for upload
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('type', file.type.startsWith('video/') ? 'video' : 'image');

                    try {
                        const response = await fetch(`/orgs/${this.orgId}/social/media/upload`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                            },
                            body: formData
                        });

                        if (response.ok) {
                            const result = await response.json();
                            if (result.success && result.data) {
                                // Initialize media array if needed
                                if (!this.editingPost.media) {
                                    this.editingPost.media = [];
                                }

                                // Add new media item
                                this.editingPost.media.push({
                                    url: result.data.url,
                                    type: result.data.type || (file.type.startsWith('video/') ? 'video' : 'image'),
                                    filename: file.name,
                                    size: file.size
                                });

                                console.log('[Edit Media] Uploaded successfully:', file.name);
                            }
                        } else {
                            console.error('[Edit Media] Upload failed:', file.name);
                        }
                    } catch (uploadError) {
                        console.error('[Edit Media] Upload error:', uploadError);
                    }
                }
            } finally {
                this.editMediaUploading = false;
                this.editMediaUploadProgress = 0;
            }
        },

        /**
         * Get media type icon
         */
        getEditMediaTypeIcon(media) {
            if (media.type === 'video' || media.url?.match(/\.(mp4|mov|webm)$/i)) {
                return 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }
            return 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z';
        },

        /**
         * Check if media is a video
         */
        isEditMediaVideo(media) {
            return media.type === 'video' || media.url?.match(/\.(mp4|mov|webm)$/i);
        },

        async editPost(post) {
            this.editingPost = {
                id: post.post_id || post.id,
                content: post.content || post.post_text || '',
                platform: post.platform,
                status: post.status,
                scheduled_at: post.scheduled_at,
                media: post.media || [],
                account_username: post.account_username,
                social_account_username: post.social_account_username,
                social_account_display_name: post.social_account_display_name,
                integration_id: post.integration_id,
                scheduledDate: '',
                scheduledTime: ''
            };

            // Default to UTC until we fetch the correct timezone
            this.editTimezone = 'UTC';

            // Fetch timezone for this post's integration
            if (post.integration_id) {
                await this.fetchEditTimezone(post.integration_id);
            }

            // Convert UTC time to local timezone for display
            if (post.scheduled_at) {
                try {
                    // Parse UTC time and convert to local timezone
                    const utcDate = new Date(post.scheduled_at);

                    if (this.editTimezone && this.editTimezone !== 'UTC') {
                        // Format the UTC date in the profile's timezone
                        const localDateStr = utcDate.toLocaleString('en-CA', {
                            timeZone: this.editTimezone,
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                        const localTimeStr = utcDate.toLocaleString('en-GB', {
                            timeZone: this.editTimezone,
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        });

                        this.editingPost.scheduledDate = localDateStr;
                        this.editingPost.scheduledTime = localTimeStr;

                        console.log('[Edit Post] Converted UTC to local:', {
                            utc: post.scheduled_at,
                            timezone: this.editTimezone,
                            localDate: localDateStr,
                            localTime: localTimeStr
                        });
                    } else {
                        // No timezone, use UTC directly
                        this.editingPost.scheduledDate = utcDate.toISOString().split('T')[0];
                        this.editingPost.scheduledTime = utcDate.toISOString().slice(11, 16);
                    }
                } catch (error) {
                    console.error('[Edit Post] Failed to convert timezone:', error);
                    const scheduled = new Date(post.scheduled_at);
                    this.editingPost.scheduledDate = scheduled.toISOString().split('T')[0];
                    this.editingPost.scheduledTime = scheduled.toTimeString().slice(0, 5);
                }
            }

            this.showEditPostModal = true;
        },

        async fetchEditTimezone(integrationId) {
            if (!integrationId) return;

            this.editTimezoneLoading = true;
            try {
                console.log('[Edit Post] Fetching timezone for integration:', integrationId);

                const response = await fetch(`/orgs/${this.orgId}/social/publish-modal/timezone`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ integration_ids: [integrationId] })
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success && result.data?.timezone) {
                    this.editTimezone = result.data.timezone;
                    console.log('[Edit Post] Timezone fetched:', this.editTimezone, 'from:', result.data.timezone_source);
                } else {
                    console.warn('[Edit Post] No timezone in response, using UTC');
                    this.editTimezone = 'UTC';
                }
            } catch (error) {
                console.error('[Edit Post] Failed to fetch timezone:', error);
                this.editTimezone = 'UTC';
            } finally {
                this.editTimezoneLoading = false;
            }
        },

        async updatePost() {
            if (this.isUpdating || !this.editingPost.content.trim()) return;
            this.isUpdating = true;

            try {
                const updateData = {
                    content: this.editingPost.content,
                };

                if ((this.editingPost.status === 'draft' || this.editingPost.status === 'scheduled')
                    && this.editingPost.scheduledDate && this.editingPost.scheduledTime) {
                    updateData.scheduled_at = `${this.editingPost.scheduledDate}T${this.editingPost.scheduledTime}:00`;
                    updateData.status = 'scheduled';

                    // Include timezone so backend can convert to UTC
                    updateData.timezone = this.editTimezone;

                    console.log('[Edit Post] Sending update with timezone:', {
                        scheduled_at: updateData.scheduled_at,
                        timezone: updateData.timezone
                    });
                }

                const response = await fetch(`/orgs/${this.orgId}/social/posts/${this.editingPost.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(updateData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.showEditPostModal = false;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(translations.postUpdatedSuccess, 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || translations.postUpdateFailed, 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to update post:', error);
                if (window.notify) {
                    window.notify(translations.postUpdateFailed, 'error');
                }
            } finally {
                this.isUpdating = false;
            }
        },

        async publishNow(postId) {
            if (!confirm(translations.confirmPublishNow)) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(translations.postPublishedSuccess, "success");
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || translations.postPublishFailed, 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to publish post:', error);
                if (window.notify) {
                    window.notify(translations.postPublishFailed, 'error');
                }
            }
        },

        async retryPost(postId) {
            if (!confirm(translations.confirmRetryPublish)) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(translations.postPublishedSuccess, 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(translations.retryFailed + ': ' + (result.message || ''), 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to retry post:', error);
                if (window.notify) {
                    window.notify(translations.retryFailed, 'error');
                }
            }
        },

        async deletePost(postId, showConfirm = true) {
            if (showConfirm && !confirm(translations.confirmDeletePost)) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (showConfirm) {
                        await this.fetchPosts();
                        if (window.notify) {
                            window.notify(translations.postDeletedSuccess, "success");
                        }
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || translations.postDeleteFailed, 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete post:', error);
                if (window.notify) {
                    window.notify(translations.postDeleteFailed, 'error');
                }
            }
        },

        async deleteAllFailed() {
            if (!confirm(trans('confirmDeleteFailedPosts', {count: this.failedCount}))) return;

            this.isDeletingFailed = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts-failed`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const deletedCount = result.data?.deleted_count || 0;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(trans('failedPostsDeletedSuccess', {count: deletedCount}), "success");
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || translations.failedPostsDeleteFailed, 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete all failed posts:', error);
                if (window.notify) {
                    window.notify(translations.failedPostsDeleteFailed, 'error');
                }
            } finally {
                this.isDeletingFailed = false;
            }
        }
    };
}
