// Publish Modal Alpine.js Component
// Define publishModal function immediately so it's available for Alpine
function publishModal() {
    return {
        // Initialization flag to prevent overlay rendering before component is ready
        _initialized: false,

        open: false,
        editMode: false,
        editPostId: null,

        // Profile Groups Selection (Step 1)
        profileGroups: [],
        selectedGroupIds: [], // Multi-select groups

        // Profile Selection (Step 2)
        selectedProfiles: [],
        profileSearch: '',
        platformFilter: null,
        availablePlatforms: ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'google_business'],

        // Content
        composerTab: 'global',
        content: {
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
                },
                google_business: {
                    text: '',
                    post_type: 'update',
                    cta_type: '',
                    cta_url: '',
                    event_title: '',
                    event_start_date: '',
                    event_start_time: '',
                    event_end_date: '',
                    event_end_time: '',
                    offer_title: '',
                    offer_coupon_code: '',
                    offer_redeem_url: '',
                    offer_terms_conditions: ''
                }
            }
        },
        newLabel: '',
        isDragging: false,
        imageProcessingEnabled: true,
        videoProcessingEnabled: true,

        // Scheduling
        scheduleEnabled: false,
        schedule: {
            date: '',
            time: '',
            timezone: 'UTC'
        },
        publishMode: 'publish_now', // publish_now, schedule, add_to_queue
        queuePosition: 'available', // 'next', 'available', 'last' - PHASE 5B: Queue positioning

        // PHASE 3: Advanced Scheduling
        showCalendar: false,
        showBestTimes: false,
        showBulkScheduling: false,
        scheduledPosts: [],
        calendarMonth: new Date().getMonth(),
        calendarYear: new Date().getFullYear(),
        draggedPost: null, // For drag-and-drop rescheduling
        dragOverDate: null, // For visual feedback during drag
        optimalTimes: [
            { day: 'Monday', time: '09:00', engagement: 'High' },
            { day: 'Monday', time: '14:00', engagement: 'Medium' },
            { day: 'Tuesday', time: '10:00', engagement: 'High' },
            { day: 'Wednesday', time: '15:00', engagement: 'High' },
            { day: 'Thursday', time: '11:00', engagement: 'Medium' },
            { day: 'Friday', time: '13:00', engagement: 'High' },
        ],
        bulkSchedule: {
            times: [],
            recurring: false,
            repeatType: 'never', // daily, weekly, monthly
            isEvergreen: false
        },

        // Preview
        previewPlatform: 'instagram',
        previewMode: 'mobile', // mobile or desktop - ENHANCED PREVIEW FEATURE

        // Mobile Responsive Overlays (Phase 1E)
        showMobileProfileSelector: false,
        showMobilePreview: false,

        // AI Assistant
        showAIAssistant: false,
        brandVoices: [],
        aiSettings: {
            brandVoice: '',
            tone: 'professional',
            length: 'same',
            prompt: ''
        },
        isGenerating: false,
        aiSuggestions: [],

        // Brand Safety
        brandSafetyStatus: 'pass',
        brandSafetyIssues: [],

        // Approval
        requiresApproval: false,

        // NEW: Emoji Picker
        showEmojiPicker: false,
        commonEmojis: [
            '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
            '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🤐',
            '🤨', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷', '🤒',
            '🤕', '🤢', '🤮', '🤧', '🥵', '🥶', '🥴', '😵', '🤯', '🤠', '🥳', '🥸', '😎', '🤓', '🧐', '😕',
            '👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '🤌', '🤏', '👈', '👉', '👆',
            '👇', '☝️', '👋', '🤚', '🖐', '✋', '🖖', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳',
            '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
            '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈',
            '🔥', '💥', '💫', '💦', '💨', '✨', '🌟', '⭐', '🌠', '☀️', '⛅', '⛈', '🌤', '🌥', '🌦', '🌧',
            '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉'
        ],

        // NEW: Hashtag Manager
        showHashtagManager: false,
        hashtagSets: [],
        recentHashtags: [],
        trendingHashtags: [],
        selectedHashtagSet: null,
        loadingTrendingHashtags: false,
        selectedHashtagPlatform: 'instagram',
        platformConnections: [], // External platform connections from API

        // NEW: Mention Picker
        showMentionPicker: false,
        mentionSearch: '',

        // NEW: Media Processing
        uploadingMedia: [],
        mediaProcessingStatus: {}, // {index: {progress: 0-100, status: 'uploading'|'processing'|'done'|'error'}}

        // NEW: Link Shortener
        shorteningLink: false,

        // NEW: Auto-save
        lastSaved: null,
        saveIndicator: false,
        autoSaveInterval: null,

        // PHASE 2: Location Tagging
        locationResults: {},
        locationSearchTimeout: null,

        // PHASE 2: Enhanced First Comment
        showEmojiPickerFirstComment: false,

        // PHASE 4: Multiple Media Sources
        showMediaSourcePicker: false,
        mediaUrlInput: '',
        showMediaLibrary: false,
        mediaLibraryFiles: [],

        // PHASE 4: Platform Warnings
        platformWarnings: [],

        // PHASE 2: Template Library
        showTemplateLibrary: false,
        newTemplateName: '',
        savedTemplates: [],

        // PHASE 2: Advanced Collaboration
        activeCollaborators: [],
        showCollaborators: false,
        collaborationCheckInterval: null,

        // PHASE 2: Enhanced AI - Content Variations
        showAiVariations: false,
        aiGeneratingVariations: false,
        contentVariations: [],
        enableABTesting: false,
        abTestDuration: '48',
        abTestMetric: 'engagement',

        // PHASE 2: Error Handling & Recovery
        networkError: false,
        lastError: null,
        retryCount: 0,
        maxRetries: 3,

        // Character limits
        characterLimits: {
            twitter: 280,
            instagram: 2200,
            facebook: 63206,
            linkedin: 3000,
            tiktok: 2200
        },

        // Publishing status (async publishing)
        isPublishing: false,
        publishSucceeded: false, // Flag to bypass unsaved changes check after successful publish
        publishingStatus: null, // 'uploading', 'submitting', 'publishing', null
        publishingProgress: {
            total: 0,
            completed: 0,
            success: 0,
            failed: 0
        },

        // PLATFORM API SPECIFICATIONS (Based on Official API Documentation)
        // Note: We accept any image format and convert to required format during publishing
        platformSpecs: {
            instagram: {
                characterLimit: 2200,
                imageFormats: ['JPEG', 'PNG', 'GIF', 'WEBP'], // Accept all, convert to JPEG on backend
                apiRequiredFormat: 'JPEG', // What the API actually requires
                videoFormats: ['MP4', 'MOV'],
                maxMediaCount: 10,
                maxImagesPerPost: 10,
                maxVideosPerPost: 1,
                dailyPostLimit: 25,
                requiresBusinessAccount: true,
                reelRequiresVideo: true,
                storyRequiresMediaFirst: true
            },
            twitter: {
                characterLimit: 280,
                imageFormats: ['JPG', 'PNG', 'GIF', 'WEBP', 'JPEG', 'BMP', 'TIFF'], // Accept all common formats
                videoFormats: ['MP4', 'MOV'],
                maxImagesPerPost: 4,
                maxVideosPerPost: 1,
                maxGifSize: 15 * 1024 * 1024, // 15MB
                maxVideoSize: 512 * 1024 * 1024, // 512MB
                maxVideoDuration: 140, // seconds
                minVideoDuration: 0.5, // seconds
                requiresScope: 'media.write'
            },
            linkedin: {
                characterLimit: 3000,
                videoFormats: ['MP4', 'MOV', 'AVI'],
                maxVideoSize: 5 * 1024 * 1024 * 1024, // 5GB
                minVideoDuration: 3, // seconds
                maxVideoDuration: 600, // 10 minutes
                minVideoResolution: '256x144',
                maxVideoResolution: '4096x2304',
                minFrameRate: 24,
                maxFrameRate: 60,
                requiresPartnerProgram: true
            },
            tiktok: {
                characterLimit: 2200,
                videoFormats: ['MP4'], // MP4 + H.264 codec only
                requiredCodec: 'H.264',
                dailyPostLimit: 15,
                rateLimitPerMinute: 6,
                maxChunkSize: 64 * 1024 * 1024, // 64MB
                finalChunkMaxSize: 128 * 1024 * 1024, // 128MB
                privateUntilVerified: true,
                minVideoDuration: 3,
                maxVideoDuration: 180 // 3 minutes (can be extended)
            },
            youtube: {
                videoFormats: ['MP4', 'MOV', 'AVI', 'WMV', 'FLV', 'WEBM'],
                quotaUnitsPerUpload: 1600,
                defaultDailyQuota: 10000, // ~6 uploads per day
                maxUploadsPerDay: 6,
                privateUntilVerified: true, // After July 28, 2020
                requiresOAuth: true,
                titleRequired: true,
                videoRequired: true,
                privacyOptions: ['public', 'private', 'unlisted']
            },
            snapchat: {
                aspectRatio: '9:16',
                minResolution: '1080x1920',
                imageFormats: ['PNG', 'JPEG'],
                videoFormats: ['MP4', 'MOV'],
                maxImageSize: 5 * 1024 * 1024, // 5MB
                minVideoDuration: 3,
                maxVideoDuration: 180, // 3 minutes
                brandingSafeZone: 150, // pixels from top/bottom
                requiredAspectRatio: true
            },
            facebook: {
                characterLimit: 63206,
                imageFormats: ['JPG', 'JPEG', 'PNG', 'GIF', 'BMP', 'TIFF', 'WEBP', 'HEIC'], // Accept all formats
                videoFormats: ['MP4', 'MOV', 'AVI'],
                maxMediaCount: 10,
                maxImagesPerPost: 10,
                maxVideosPerPost: 1
            },
            google_business: {
                characterLimit: 1500,
                imageFormats: ['JPG', 'PNG'],
                videoFormats: ['MP4'],
                maxImageSize: 5 * 1024 * 1024, // 5MB
                postTypes: ['update', 'event', 'offer'],
                ctaTypes: ['BOOK', 'ORDER', 'SHOP', 'LEARN_MORE', 'SIGN_UP', 'CALL'],
                eventRequiresDateTime: true,
                offerRequiresTitle: true
            }
        },

        // i18n translations - loaded from data attributes
        i18n: {
            selectProfile: 'Please select at least one account',
            contentRequired: 'Please add text or media',
            scheduleDatetimeRequired: 'Please select a date and time for scheduling',
            scheduleMustBeFuture: 'Scheduled time must be in the future',
            instagramCharacterLimit: 'Instagram: Content exceeds :limit characters',
            instagramReelRequiresVideo: 'Instagram Reels require a video',
            instagramStoryRequiresMedia: 'Instagram Stories require media',
            instagramMaxMedia: 'Instagram: Maximum :max media items allowed',
            twitterCharacterLimit: 'Twitter: Content exceeds :limit characters',
            twitterMaxImages: 'Twitter: Maximum :max images allowed',
            twitterMaxVideos: 'Twitter allows only one video per post',
            twitterNoMixedMedia: 'Twitter does not allow mixing images and videos',
            linkedinCharacterLimit: 'LinkedIn: Content exceeds :limit characters',
            linkedinPartnerRequired: 'LinkedIn video requires Partner Program',
            tiktokCharacterLimit: 'TikTok: Content exceeds :limit characters',
            tiktokVideoRequired: 'TikTok requires a video',
            tiktokMp4H264Required: 'TikTok requires MP4/H.264 format',
            youtubeTitleRequired: 'YouTube requires a video title',
            youtubeVideoRequired: 'YouTube requires a video',
            snapchatMediaRequired: 'Snapchat requires media',
            resetAllConfirm: 'Reset all customizations?',
            resetAllSuccess: 'All customizations reset',
            applyToAllConfirm: 'Apply to all :platform accounts?',
            appliedToAllSuccess: 'Applied to all accounts'
        },

        loadI18n() {
            const el = this.$el;
            if (!el) return;
            const map = {
                'i18nSelectProfile': 'selectProfile',
                'i18nContentRequired': 'contentRequired',
                'i18nScheduleDatetimeRequired': 'scheduleDatetimeRequired',
                'i18nScheduleMustBeFuture': 'scheduleMustBeFuture',
                'i18nInstagramCharacterLimit': 'instagramCharacterLimit',
                'i18nInstagramReelRequiresVideo': 'instagramReelRequiresVideo',
                'i18nInstagramStoryRequiresMedia': 'instagramStoryRequiresMedia',
                'i18nInstagramMaxMedia': 'instagramMaxMedia',
                'i18nTwitterCharacterLimit': 'twitterCharacterLimit',
                'i18nTwitterMaxImages': 'twitterMaxImages',
                'i18nTwitterMaxVideos': 'twitterMaxVideos',
                'i18nTwitterNoMixedMedia': 'twitterNoMixedMedia',
                'i18nLinkedinCharacterLimit': 'linkedinCharacterLimit',
                'i18nLinkedinPartnerRequired': 'linkedinPartnerRequired',
                'i18nTiktokCharacterLimit': 'tiktokCharacterLimit',
                'i18nTiktokVideoRequired': 'tiktokVideoRequired',
                'i18nTiktokMp4H264Required': 'tiktokMp4H264Required',
                'i18nYoutubeTitleRequired': 'youtubeTitleRequired',
                'i18nYoutubeVideoRequired': 'youtubeVideoRequired',
                'i18nSnapchatMediaRequired': 'snapchatMediaRequired',
                'i18nResetAllConfirm': 'resetAllConfirm',
                'i18nResetAllSuccess': 'resetAllSuccess',
                'i18nApplyToAllConfirm': 'applyToAllConfirm',
                'i18nAppliedToAllSuccess': 'appliedToAllSuccess'
            };
            for (const [dataKey, i18nKey] of Object.entries(map)) {
                const val = el.dataset[dataKey];
                if (val) this.i18n[i18nKey] = val;
            }
        },

        init() {
            this.loadI18n();
            this.loadProfileGroups();
            this.loadBrandVoices();
            this.loadPlatformConnections();
            this.loadTemplatesFromStorage(); // PHASE 2: Load saved templates

            // Listen for open modal event
            window.addEventListener('open-publish-modal', (event) => {
                this.open = true;
                this.startCollaborationSimulation(); // PHASE 2: Start collaboration simulation
                if (event.detail?.postId) {
                    this.editMode = true;
                    this.editPostId = event.detail.postId;
                    this.loadPost(event.detail.postId);
                } else if (event.detail?.content) {
                    // Pre-fill content for duplicate post
                    this.content.global.text = event.detail.content;
                }
            });

            // PHASE 4: Watch for content changes and check platform warnings
            this.$watch('content.global.text', () => {
                this.checkPlatformWarnings();
            });

            this.$watch('content.global.media', () => {
                this.checkPlatformWarnings();
            });

            this.$watch('selectedProfiles', () => {
                this.checkPlatformWarnings();
            });

            // Mark component as initialized (prevents overlay rendering errors)
            this._initialized = true;
        },

        async loadProfileGroups() {
            try {
                // Use web route for session auth (not API route)
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/profile-groups`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.profileGroups = result.data || result;
                }
            } catch (e) {
                console.error('Failed to load profile groups', e);
            }
        },

        async loadBrandVoices() {
            try {
                // Use web route for session auth
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/brand-voices`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.brandVoices = result.data || result;
                }
            } catch (e) {
                console.error('Failed to load brand voices', e);
            }
        },

        // Filter profile groups based on selected group IDs
        get filteredProfileGroups() {
            // Safety check for initialization
            if (!this.selectedGroupIds || !Array.isArray(this.selectedGroupIds) || !this.profileGroups || !Array.isArray(this.profileGroups)) {
                return [];
            }

            // If no groups selected, show nothing (must select groups first)
            if (this.selectedGroupIds.length === 0) {
                return [];
            }

            return this.profileGroups
                .filter(group => this.selectedGroupIds.includes(group.group_id))
                .map(group => ({
                    ...group,
                    profiles: group.profiles?.filter(p =>
                        (!this.platformFilter || p.platform === this.platformFilter) &&
                        (!this.profileSearch || p?.account_name?.toLowerCase().includes(this.profileSearch.toLowerCase()))
                    ) || []
                })).filter(g => g.profiles.length > 0);
        },

        // PHASE 5B: Enhanced Validation with Platform API Specifications
        get validationErrors() {
            const errors = [];

            // Safety check for initialization
            if (!this.selectedProfiles || !this.content || !this.content.global) {
                return errors;
            }

            // Get translated error messages from data attributes
            const modalEl = document.querySelector('[x-data="publishModal()"]');
            const i18nSelectProfile = modalEl?.dataset?.i18nSelectProfile || 'Please select at least one account';
            const i18nContentRequired = modalEl?.dataset?.i18nContentRequired || 'Please add text or media before publishing';

            // Basic validation
            if (!Array.isArray(this.selectedProfiles) || this.selectedProfiles.length === 0) {
                errors.push(i18nSelectProfile);
            }

            const globalText = this.content.global.text || '';
            const globalMedia = this.content.global.media || [];
            if (!globalText.trim() && globalMedia.length === 0) {
                errors.push(i18nContentRequired);
            }

            // Platform-specific validation
            const selectedPlatforms = this.getSelectedPlatforms();

            // === INSTAGRAM VALIDATION ===
            if (selectedPlatforms.includes('instagram')) {
                const instagramSpecs = this.platformSpecs.instagram;
                const instagramContent = this.content.platforms.instagram;

                // Character limit validation
                const instagramText = instagramContent?.text || globalText;
                if (instagramText.length > instagramSpecs.characterLimit) {
                    errors.push(this.i18n.instagramCharacterLimit.replace(':limit', instagramSpecs.characterLimit));
                }

                // Reel requires video
                if (instagramContent?.post_type === 'reel') {
                    const hasVideo = globalMedia.some(m => m.type === 'video');
                    if (!hasVideo) {
                        errors.push(this.i18n.instagramReelRequiresVideo);
                    }
                }

                // Story requires media first
                if (instagramContent?.post_type === 'story' && globalMedia.length === 0) {
                    errors.push(this.i18n.instagramStoryRequiresMedia);
                }

                // Media count validation
                if (globalMedia.length > instagramSpecs.maxMediaCount) {
                    errors.push(this.i18n.instagramMaxMedia.replace(':max', instagramSpecs.maxMediaCount));
                }
            }

            // === TWITTER/X VALIDATION ===
            if (selectedPlatforms.includes('twitter')) {
                const twitterSpecs = this.platformSpecs.twitter;
                const twitterContent = this.content.platforms.twitter;

                // Character limit validation
                const twitterText = twitterContent?.text || globalText;
                if (twitterText.length > twitterSpecs.characterLimit) {
                    errors.push(this.i18n.twitterCharacterLimit.replace(':limit', twitterSpecs.characterLimit));
                }

                // Media count validation
                const imageCount = globalMedia.filter(m => m.type === 'image').length;
                const videoCount = globalMedia.filter(m => m.type === 'video').length;

                if (imageCount > twitterSpecs.maxImagesPerPost) {
                    errors.push(this.i18n.twitterMaxImages.replace(':max', twitterSpecs.maxImagesPerPost));
                }

                if (videoCount > twitterSpecs.maxVideosPerPost) {
                    errors.push(this.i18n.twitterMaxVideos);
                }

                // Cannot mix images and videos
                if (imageCount > 0 && videoCount > 0) {
                    errors.push(this.i18n.twitterNoMixedMedia);
                }
            }

            // === LINKEDIN VALIDATION ===
            if (selectedPlatforms.includes('linkedin')) {
                const linkedinSpecs = this.platformSpecs.linkedin;
                const linkedinContent = this.content.platforms.linkedin;

                // Character limit validation
                const linkedinText = linkedinContent?.text || globalText;
                if (linkedinText.length > linkedinSpecs.characterLimit) {
                    errors.push(this.i18n.linkedinCharacterLimit.replace(':limit', linkedinSpecs.characterLimit));
                }

                // Video validation
                const videos = globalMedia.filter(m => m.type === 'video');
                if (videos.length > 0) {
                    // Check for Partner Program requirement
                    errors.push(this.i18n.linkedinPartnerRequired);
                }
            }

            // === TIKTOK VALIDATION ===
            if (selectedPlatforms.includes('tiktok')) {
                const tiktokSpecs = this.platformSpecs.tiktok;
                const tiktokContent = this.content.platforms.tiktok;

                // Character limit validation
                const tiktokText = tiktokContent?.text || globalText;
                if (tiktokText.length > tiktokSpecs.characterLimit) {
                    errors.push(this.i18n.tiktokCharacterLimit.replace(':limit', tiktokSpecs.characterLimit));
                }

                // TikTok requires video
                const hasVideo = globalMedia.some(m => m.type === 'video');
                if (!hasVideo) {
                    errors.push(this.i18n.tiktokVideoRequired);
                }

                // MP4 + H.264 format validation
                const nonMp4Videos = globalMedia.filter(m =>
                    m.type === 'video' && !m.mime_type?.includes('mp4')
                );
                if (nonMp4Videos.length > 0) {
                    errors.push(this.i18n.tiktokMp4H264Required);
                }
            }

            // === YOUTUBE VALIDATION ===
            if (selectedPlatforms.includes('youtube')) {
                const youtubeSpecs = this.platformSpecs.youtube;
                const youtubeContent = this.content.platforms.youtube;

                // Title required
                if (!youtubeContent?.video_title?.trim()) {
                    errors.push(this.i18n.youtubeTitleRequired);
                }

                // Video required
                const hasVideo = globalMedia.some(m => m.type === 'video');
                if (!hasVideo) {
                    errors.push(this.i18n.youtubeVideoRequired);
                }
            }

            // === SNAPCHAT VALIDATION ===
            if (selectedPlatforms.includes('snapchat')) {
                const snapchatSpecs = this.platformSpecs.snapchat;

                // Snapchat requires media
                if (globalMedia.length === 0) {
                    errors.push(this.i18n.snapchatMediaRequired);
                }
            }

            // Schedule validation
            if (this.publishMode === 'schedule' && this.scheduleEnabled) {
                if (!this.schedule.date || !this.schedule.time) {
                    errors.push(this.i18n.scheduleDatetimeRequired);
                }

                // Check if scheduled time is in the future
                const scheduledDateTime = new Date(`${this.schedule.date}T${this.schedule.time}`);
                if (scheduledDateTime <= new Date()) {
                    errors.push(this.i18n.scheduleMustBeFuture);
                }
            }

            return errors;
        },

        get canSubmit() {
            return this.validationErrors.length === 0;
        },

        isProfileSelected(id) {
            return this.selectedProfiles.some(p => p.integration_id === id);
        },

        toggleProfile(profile) {
            const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
            if (index >= 0) {
                this.selectedProfiles.splice(index, 1);
            } else {
                this.selectedProfiles.push(profile);
            }
        },

        // ============================================
        // STEP 1: Profile Group Selection Functions
        // ============================================

        // Toggle a group ID in the selection
        toggleGroupId(groupId) {
            const index = this.selectedGroupIds.indexOf(groupId);
            if (index >= 0) {
                this.selectedGroupIds.splice(index, 1);
                // Also remove profiles from this group
                const group = this.profileGroups.find(g => g.group_id === groupId);
                if (group?.profiles) {
                    group.profiles.forEach(profile => {
                        const profileIndex = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                        if (profileIndex >= 0) {
                            this.selectedProfiles.splice(profileIndex, 1);
                        }
                    });
                }
            } else {
                this.selectedGroupIds.push(groupId);
            }
        },

        // Select all groups
        selectAllGroups() {
            this.selectedGroupIds = this.profileGroups.map(g => g.group_id);
        },

        // Clear all selected groups and profiles
        clearSelectedGroups() {
            this.selectedGroupIds = [];
            this.selectedProfiles = [];
        },

        // ============================================
        // STEP 2: Profile Selection Functions
        // ============================================

        selectAllProfiles() {
            this.filteredProfileGroups.forEach(group => {
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            });
        },

        clearSelectedProfiles() {
            this.selectedProfiles = [];
        },

        // Check if all profiles in a group are selected
        isGroupFullySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            return group.profiles.every(p => this.isProfileSelected(p.integration_id));
        },

        // Check if any profile in a group is selected
        isGroupPartiallySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            const selectedCount = group.profiles.filter(p => this.isProfileSelected(p.integration_id)).length;
            return selectedCount > 0 && selectedCount < group.profiles.length;
        },

        // Toggle all profiles in a group
        toggleGroupSelection(group) {
            if (!group.profiles) return;

            if (this.isGroupFullySelected(group)) {
                // Deselect all profiles in this group
                group.profiles.forEach(profile => {
                    const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                    if (index >= 0) {
                        this.selectedProfiles.splice(index, 1);
                    }
                });
            } else {
                // Select all profiles in this group
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            }
        },

        getSelectedPlatforms() {
            // Safety check for initialization
            if (!this.selectedProfiles || !Array.isArray(this.selectedProfiles)) {
                return [];
            }
            return [...new Set(this.selectedProfiles.map(p => p.platform))];
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: 'fab fa-facebook',
                instagram: 'fab fa-instagram',
                twitter: 'fab fa-twitter',
                linkedin: 'fab fa-linkedin',
                tiktok: 'fab fa-tiktok'
            };
            return icons[platform] || 'fas fa-globe';
        },

        getPlatformBgClass(platform) {
            const classes = {
                facebook: 'bg-blue-600',
                instagram: 'bg-pink-500',
                twitter: 'bg-sky-500',
                linkedin: 'bg-blue-700',
                tiktok: 'bg-gray-900'
            };
            return classes[platform] || 'bg-gray-500';
        },

        getDefaultAvatar(profile) {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.account_name || 'U')}&background=6366f1&color=fff`;
        },

        updateCharacterCounts() {
            this.checkBrandSafety();
        },

        getCharacterCount(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform] || 2200;
            return `${text.length}/${limit}`;
        },

        getCharacterCountClass(platform) {
            const text = this.content.platforms[platform]?.text || this.content.global.text;
            const limit = this.characterLimits[platform] || 2200;
            if (text.length > limit) return 'text-red-600';
            if (text.length > limit * 0.9) return 'text-yellow-600';
            return 'text-gray-500';
        },

        handleMediaUpload(event) {
            const files = Array.from(event.target.files);
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    // Add media with uploading status
                    const mediaItem = {
                        file: file,
                        type: file.type.startsWith('video') ? 'video' : 'image',
                        preview_url: e.target.result,
                        uploadStatus: 'uploading', // 'uploading', 'uploaded', 'failed'
                        url: null,
                        uploadProgress: 0
                    };
                    this.content.global.media.push(mediaItem);

                    // Get the index after pushing
                    const mediaIndex = this.content.global.media.length - 1;

                    // Start auto-upload immediately
                    this.autoUploadMedia(mediaIndex);
                };
                reader.readAsDataURL(file);
            });
        },

        handleMediaDrop(event) {
            this.isDragging = false;
            const files = Array.from(event.dataTransfer.files);
            files.forEach(file => {
                if (file.type.startsWith('image') || file.type.startsWith('video')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        // Add media with uploading status
                        const mediaItem = {
                            file: file,
                            type: file.type.startsWith('video') ? 'video' : 'image',
                            preview_url: e.target.result,
                            uploadStatus: 'uploading',
                            url: null,
                            uploadProgress: 0
                        };
                        this.content.global.media.push(mediaItem);

                        // Get the index after pushing
                        const mediaIndex = this.content.global.media.length - 1;

                        // Start auto-upload immediately
                        this.autoUploadMedia(mediaIndex);
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        /**
         * Auto-upload media file immediately after selection
         */
        async autoUploadMedia(mediaIndex) {
            const mediaItem = this.content.global.media[mediaIndex];
            if (!mediaItem || !mediaItem.file) {
                console.error('[AutoUpload] Invalid media item at index', mediaIndex);
                return;
            }

            console.log('[AutoUpload] Starting upload for media', {
                index: mediaIndex,
                fileName: mediaItem.file.name,
                fileSize: mediaItem.file.size
            });

            try {
                const uploadedUrl = await this.uploadMediaFile(mediaItem.file);

                if (uploadedUrl) {
                    // Update the media item with uploaded URL
                    this.content.global.media[mediaIndex].url = uploadedUrl;
                    this.content.global.media[mediaIndex].uploadStatus = 'uploaded';
                    this.content.global.media[mediaIndex].uploadProgress = 100;
                    console.log('[AutoUpload] Upload successful', { index: mediaIndex, url: uploadedUrl });
                } else {
                    this.content.global.media[mediaIndex].uploadStatus = 'failed';
                    console.error('[AutoUpload] Upload failed', { index: mediaIndex });
                    window.notify && window.notify('Failed to upload media file', 'error');
                }
            } catch (error) {
                this.content.global.media[mediaIndex].uploadStatus = 'failed';
                console.error('[AutoUpload] Upload error', { index: mediaIndex, error: error.message });
                window.notify && window.notify('Error uploading media: ' + error.message, 'error');
            }
        },

        /**
         * Check if all media files are uploaded
         */
        isAllMediaUploaded() {
            if (!this.content.global.media || this.content.global.media.length === 0) {
                return true;
            }
            return this.content.global.media.every(item =>
                item.uploadStatus === 'uploaded' || item.url || !item.file
            );
        },

        /**
         * Get count of media still uploading
         */
        getUploadingMediaCount() {
            if (!this.content.global.media) return 0;
            return this.content.global.media.filter(item => item.uploadStatus === 'uploading').length;
        },

        removeMedia(index) {
            this.content.global.media.splice(index, 1);
        },

        /**
         * Prepare content by using pre-uploaded media URLs
         * Media is now auto-uploaded when selected, so we just use the stored URLs
         */
        async prepareContentForPublishing(content) {
            console.log('[Publishing] Preparing content for publishing', {
                mediaCount: content.global.media?.length || 0,
                hasMedia: !!(content.global.media && content.global.media.length > 0)
            });

            let uploadedMedia = [];

            // Process media - use pre-uploaded URLs from auto-upload
            if (content.global.media && content.global.media.length > 0) {
                for (const mediaItem of content.global.media) {
                    console.log('[Publishing] Processing media item', {
                        hasFile: !!mediaItem.file,
                        hasUrl: !!mediaItem.url,
                        uploadStatus: mediaItem.uploadStatus,
                        type: mediaItem.type
                    });

                    // Check if already uploaded via auto-upload
                    if (mediaItem.url && !mediaItem.url.startsWith('data:')) {
                        console.log('[Publishing] Using pre-uploaded URL', { url: mediaItem.url });
                        uploadedMedia.push({
                            type: mediaItem.type,
                            url: mediaItem.url,
                            name: mediaItem.file?.name || mediaItem.name,
                            size: mediaItem.file?.size || mediaItem.size
                        });
                    }
                    // If still has file but no URL (upload failed or still in progress), try uploading now
                    else if (mediaItem.file && mediaItem.uploadStatus !== 'uploaded') {
                        console.log('[Publishing] Media not pre-uploaded, uploading now...');
                        const uploadedUrl = await this.uploadMediaFile(mediaItem.file);
                        if (uploadedUrl) {
                            uploadedMedia.push({
                                type: mediaItem.type,
                                url: uploadedUrl,
                                name: mediaItem.file.name,
                                size: mediaItem.file.size
                            });
                        } else {
                            console.error('[Publishing] Failed to upload media');
                        }
                    }
                }
            }

            console.log('[Publishing] Uploaded media ready', { count: uploadedMedia.length, urls: uploadedMedia.map(m => m.url) });

            // Build clean content object without File objects
            return {
                global: {
                    text: content.global.text || '',
                    media: uploadedMedia,
                    link: content.global.link || '',
                    labels: content.global.labels || [],
                },
                platforms: content.platforms || {}
            };
        },

        /**
         * Upload a media file and return its URL
         */
        async uploadMediaFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', file.type.startsWith('video') ? 'video' : 'image');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            console.log('[Upload] Uploading media file', {
                name: file.name,
                size: file.size,
                type: file.type,
                csrfToken: csrfToken ? 'Present' : 'Missing',
                orgId: window.currentOrgId
            });

            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/media/upload`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    const url = data.data?.url || data.url;
                    console.log('[Upload] Upload successful', { url, fullResponse: data });
                    return url;
                } else {
                    const errorData = await response.json().catch(() => ({ message: 'Unknown error' }));
                    console.error(`[Upload] Failed to upload media file - Status: ${response.status}`, errorData);
                    console.error(`[Upload] Error message: ${errorData.message || JSON.stringify(errorData)}`);
                    return null;
                }
            } catch (e) {
                console.error('[Upload] Error uploading media:', e);
                return null;
            }
        },

        addLabel() {
            if (this.newLabel.trim() && !this.content.global.labels.includes(this.newLabel.trim())) {
                this.content.global.labels.push(this.newLabel.trim());
                this.newLabel = '';
            }
        },

        removeLabel(label) {
            this.content.global.labels = this.content.global.labels.filter(l => l !== label);
        },

        getPreviewProfile() {
            return this.selectedProfiles.find(p => p.platform === this.previewPlatform) || this.selectedProfiles[0];
        },

        getPreviewContent() {
            return this.content.platforms[this.previewPlatform]?.text || this.content.global.text || 'Your post content will appear here...';
        },

        getPreviewTime() {
            if (this.scheduleEnabled && this.schedule.date && this.schedule.time) {
                return `Scheduled: ${this.schedule.date} ${this.schedule.time}`;
            }
            return 'Just now';
        },

        checkBrandSafety() {
            // Simulated brand safety check
            this.brandSafetyIssues = [];
            const text = this.content.global.text.toLowerCase();

            // Add real brand safety checks based on selected profile group's policy
            this.brandSafetyStatus = this.brandSafetyIssues.length === 0 ? 'pass' : 'fail';
        },

        async generateWithAI() {
            this.isGenerating = true;
            try {
                // Call AI generation API
                const response = await fetch('/api/ai/generate-content', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        original_text: this.content.global.text,
                        brand_voice_id: this.aiSettings.brandVoice,
                        tone: this.aiSettings.tone,
                        length: this.aiSettings.length,
                        instructions: this.aiSettings.prompt
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    this.aiSuggestions = data.suggestions || [];
                }
            } catch (e) {
                console.error('AI generation failed', e);
            } finally {
                this.isGenerating = false;
            }
        },

        useSuggestion(suggestion) {
            this.content.global.text = suggestion;
            this.showAIAssistant = false;
        },

        async saveDraft() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true
                    })
                });
                if (response.ok) {
                    window.notify('Draft saved successfully', 'success');
                    this.publishSucceeded = true;
                    this.closeModal(true); // Force close - skip unsaved changes check
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to save draft', 'error');
                }
            } catch (e) {
                console.error('Failed to save draft', e);
                window.notify('Failed to save draft', 'error');
            }
        },

        async publishNow() {
            if (!this.canSubmit) return;

            // [PERF] Start timing the entire publish flow
            const perfStart = performance.now();
            console.log('[PERF] Publish flow started');

            // Check if media is still uploading
            const uploadingCount = this.getUploadingMediaCount();
            if (uploadingCount > 0) {
                const lang = document.documentElement.lang || 'en';
                const message = lang === 'ar'
                    ? 'يرجى الانتظار حتى اكتمال رفع الوسائط'
                    : 'Please wait for media to finish uploading';
                window.notify && window.notify(message, 'warning');
                return;
            }

            // Set publishing state for UI feedback
            this.isPublishing = true;
            this.publishingStatus = 'submitting'; // Changed from 'uploading' since media is already uploaded

            try {
                // [PERF] Measure content preparation
                const prepStart = performance.now();
                console.log('[PERF] Starting content preparation');
                const contentToSend = await this.prepareContentForPublishing(this.content);
                const prepDuration = performance.now() - prepStart;
                console.log(`[PERF] Content preparation completed in ${prepDuration.toFixed(2)}ms`);

                this.publishingStatus = 'submitting';

                // [PERF] Measure API call
                const apiStart = performance.now();
                console.log('[PERF] Starting API call to /social/publish-modal/create');
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: contentToSend,
                        is_draft: false
                    })
                });
                const apiDuration = performance.now() - apiStart;
                console.log(`[PERF] API call completed in ${apiDuration.toFixed(2)}ms`);

                // [PERF] Measure response processing
                const processStart = performance.now();
                if (response.ok) {
                    const data = await response.json();
                    const processDuration = performance.now() - processStart;
                    console.log(`[PERF] Response processing completed in ${processDuration.toFixed(2)}ms`);

                    // Check if async publishing (queued)
                    if (data.data && data.data.is_async && data.data.post_ids) {
                        // Close modal immediately after job is queued - don't wait for completion
                        const postCount = data.data.post_ids.length;
                        window.notify(data.message || `${postCount} post(s) queued for publishing`, 'success');
                        this.publishSucceeded = true;
                        this.closeModal(true);
                    } else {
                        // Legacy sync response handling
                        if (data.data && data.data.failed_count > 0) {
                            const failedPost = data.data.posts.find(p => p.status === 'failed');
                            if (failedPost) {
                                window.notify(`Failure Reason:\n\n${failedPost.error_message}`, 'error');
                            } else {
                                window.notify(data.message || 'Some posts failed to publish', 'warning');
                            }
                        } else {
                            window.notify(data.message || 'Post created successfully', 'success');
                        }

                        if (data.data && (data.data.success_count > 0 || data.data.queued_count > 0)) {
                            this.publishSucceeded = true;
                            this.closeModal(true);
                        }
                    }
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to create post', 'error');
                }

                // [PERF] Total time
                const totalDuration = performance.now() - perfStart;
                console.log(`[PERF] TOTAL publish flow completed in ${totalDuration.toFixed(2)}ms`, {
                    preparation: `${prepDuration.toFixed(2)}ms`,
                    api_call: `${apiDuration.toFixed(2)}ms`,
                    response_processing: `${(totalDuration - prepDuration - apiDuration).toFixed(2)}ms`
                });
            } catch (e) {
                console.error('Failed to publish', e);
                window.notify('Failed to publish post: ' + e.message, 'error');
            } finally {
                this.isPublishing = false;
                this.publishingStatus = null;
            }
        },

        /**
         * Poll for publishing status until all posts are complete
         */
        async pollPublishingStatus(postIds, maxAttempts = 60, intervalMs = 2000) {
            let attempts = 0;

            while (attempts < maxAttempts) {
                try {
                    const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({ post_ids: postIds })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const result = data.data;

                        console.log('[Publishing] Status poll', {
                            attempt: attempts + 1,
                            all_complete: result.all_complete,
                            success: result.success_count,
                            failed: result.failed_count,
                            pending: result.pending_count
                        });

                        // Update UI with progress
                        this.publishingProgress = {
                            total: postIds.length,
                            completed: result.success_count + result.failed_count,
                            success: result.success_count,
                            failed: result.failed_count
                        };

                        if (result.all_complete) {
                            // Find first error message if any failed
                            let firstError = null;
                            for (const [id, status] of Object.entries(result.statuses)) {
                                if (status.status === 'failed' && status.error_message) {
                                    firstError = status.error_message;
                                    break;
                                }
                            }

                            return {
                                success_count: result.success_count,
                                failed_count: result.failed_count,
                                first_error: firstError
                            };
                        }
                    }
                } catch (e) {
                    console.warn('[Publishing] Status poll error', e);
                }

                // Wait before next poll
                await new Promise(resolve => setTimeout(resolve, intervalMs));
                attempts++;
            }

            // Timeout - return partial results
            return {
                success_count: 0,
                failed_count: 0,
                first_error: 'Publishing timed out. Check the posts page for status.'
            };
        },

        async schedulePost() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.schedule,
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post scheduled successfully', 'success');
                    this.publishSucceeded = true;
                    this.closeModal(true); // Force close - skip unsaved changes check
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to schedule post', 'error');
                }
            } catch (e) {
                console.error('Failed to schedule', e);
                window.notify('Failed to schedule post', 'error');
            }
        },

        async addToQueue() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        queue_position: this.queuePosition // PHASE 5B: Queue positioning
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post added to queue successfully', 'success');
                    this.publishSucceeded = true;
                    this.closeModal(true); // Force close - skip unsaved changes check
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to add post to queue', 'error');
                }
            } catch (e) {
                console.error('Failed to add to queue', e);
                window.notify('Failed to add post to queue', 'error');
            }
        },

        async submitForApproval() {
            if (!this.canSubmit) return;
            try {
                // For now, treat as draft with pending approval status
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true,
                        requires_approval: true
                    })
                });
                if (response.ok) {
                    window.notify('Post submitted for approval', 'success');
                    this.publishSucceeded = true;
                    this.closeModal(true); // Force close - skip unsaved changes check
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to submit for approval', 'error');
                }
            } catch (e) {
                console.error('Failed to submit for approval', e);
                window.notify('Failed to submit for approval', 'error');
            }
        },

        // ============================================
        // PHASE 1: EMOJI PICKER
        // ============================================
        insertEmoji(emoji) {
            const textarea = document.querySelector('textarea[x-model="content.global.text"]');
            if (textarea) {
                const cursorPos = textarea.selectionStart;
                const textBefore = this.content.global.text.substring(0, cursorPos);
                const textAfter = this.content.global.text.substring(cursorPos);
                this.content.global.text = textBefore + emoji + textAfter;
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
                });
            }
            this.showEmojiPicker = false;
        },

        // RICH TEXT FORMATTING
        formatText(type) {
            const textarea = document.querySelector('textarea[x-model="content.global.text"]');
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = this.content.global.text.substring(start, end);

            if (!selectedText) {
                // No selection - insert placeholder
                const placeholders = {
                    bold: '**bold text**',
                    italic: '_italic text_',
                    underline: '__underline text__',
                    strikethrough: '~~strikethrough text~~'
                };
                const placeholder = placeholders[type] || '';
                const textBefore = this.content.global.text.substring(0, start);
                const textAfter = this.content.global.text.substring(start);
                this.content.global.text = textBefore + placeholder + textAfter;
                this.$nextTick(() => {
                    textarea.focus();
                    const newPos = start + placeholder.length;
                    textarea.setSelectionRange(newPos, newPos);
                });
                return;
            }

            // Wrap selected text with formatting
            let formattedText = selectedText;
            let wrapLength = 2; // Default for ** or __

            switch(type) {
                case 'bold':
                    formattedText = '**' + selectedText + '**';
                    break;
                case 'italic':
                    formattedText = '_' + selectedText + '_';
                    wrapLength = 1;
                    break;
                case 'underline':
                    formattedText = '__' + selectedText + '__';
                    break;
                case 'strikethrough':
                    formattedText = '~~' + selectedText + '~~';
                    break;
            }

            const textBefore = this.content.global.text.substring(0, start);
            const textAfter = this.content.global.text.substring(end);
            this.content.global.text = textBefore + formattedText + textAfter;

            this.$nextTick(() => {
                textarea.focus();
                // Select the formatted text (without the wrapper characters)
                textarea.setSelectionRange(start + wrapLength, end + wrapLength);
            });
        },

        // ============================================
        // PHASE 1: HASHTAG MANAGER
        // ============================================
        async loadHashtagSets() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/hashtag-sets`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.hashtagSets = result.data || [];
                }
            } catch (e) {
                console.error('Failed to load hashtag sets', e);
            }
        },

        async loadPlatformConnections() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/settings/platform-connections`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.platformConnections = result.data || [];
                }
            } catch (e) {
                console.error('Failed to load platform connections', e);
            }
        },

        async loadTrendingHashtags(platform) {
            this.loadingTrendingHashtags = true;
            this.trendingHashtags = [];
            this.selectedHashtagPlatform = platform;

            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/trending-hashtags/${platform}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });

                if (response.ok) {
                    const result = await response.json();
                    this.trendingHashtags = result.data || [];
                } else {
                    const error = await response.json();
                    window.notify && window.notify(error.message || 'Failed to load trending hashtags', 'error');
                }
            } catch (e) {
                console.error('Failed to load trending hashtags', e);
                window.notify && window.notify('Failed to load trending hashtags. Please try again.', 'error');
            } finally {
                this.loadingTrendingHashtags = false;
            }
        },

        insertHashtags(hashtags) {
            const hashtagString = hashtags.map(h => h.startsWith('#') ? h : '#' + h).join(' ');
            this.content.global.text += (this.content.global.text ? ' ' : '') + hashtagString;
            this.showHashtagManager = false;
            // Add to recent
            hashtags.forEach(h => {
                if (!this.recentHashtags.includes(h)) {
                    this.recentHashtags.unshift(h);
                    if (this.recentHashtags.length > 20) this.recentHashtags.pop();
                }
            });
        },

        // ============================================
        // PHASE 1: MENTION PICKER
        // ============================================
        get availableMentions() {
            // Safety check for initialization
            if (!this.selectedProfiles || !Array.isArray(this.selectedProfiles)) {
                return [];
            }
            if (!this.mentionSearch) {
                return this.selectedProfiles;
            }
            return this.selectedProfiles.filter(p =>
                p?.account_name?.toLowerCase().includes(this.mentionSearch.toLowerCase()) ||
                (p?.platform_handle && p.platform_handle.toLowerCase().includes(this.mentionSearch.toLowerCase()))
            );
        },

        insertMention(profile) {
            const mention = '@' + (profile.platform_handle || profile.account_name).replace(/\s+/g, '');
            this.content.global.text += (this.content.global.text ? ' ' : '') + mention;
            this.showMentionPicker = false;
            this.mentionSearch = '';
        },

        // ============================================
        // PHASE 1: LINK SHORTENER
        // ============================================
        async shortenLink() {
            if (!this.content.global.link || this.shorteningLink) return;

            this.shorteningLink = true;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/shorten-link`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ url: this.content.global.link })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.content.global.link = data.short_url || data.data?.short_url;
                    window.notify && window.notify('Link shortened successfully', 'success');
                } else {
                    window.notify && window.notify('Failed to shorten link', 'error');
                }
            } catch (e) {
                console.error('Failed to shorten link', e);
                window.notify && window.notify('Failed to shorten link', 'error');
            } finally {
                this.shorteningLink = false;
            }
        },

        // ============================================
        // PHASE 1: AUTO-SAVE
        // ============================================
        startAutoSave() {
            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            this.autoSaveInterval = setInterval(async () => {
                if (this.selectedProfiles.length > 0 && (this.content.global.text || this.content.global.media.length > 0)) {
                    await this.autoSaveDraft();
                }
            }, 30000); // Every 30 seconds
        },

        async autoSaveDraft() {
            try {
                await this.saveDraft();
                this.lastSaved = new Date();
                this.saveIndicator = true;
                setTimeout(() => { this.saveIndicator = false; }, 2000);
            } catch (e) {
                console.error('Auto-save failed', e);
            }
        },

        // ============================================
        // PHASE 2: LOCATION TAGGING
        // ============================================
        async searchLocation(query, platform) {
            if (!query || query.length < 3) {
                this.locationResults[platform] = [];
                return;
            }

            // Debounce search
            if (this.locationSearchTimeout) clearTimeout(this.locationSearchTimeout);

            this.locationSearchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`/orgs/${window.currentOrgId}/social/locations/search?query=${encodeURIComponent(query)}`, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.locationResults[platform] = data.results || data.data || [];
                    }
                } catch (e) {
                    console.error('Location search failed', e);
                }
            }, 300);
        },

        selectLocation(location, platform) {
            this.content.platforms[platform].location = location;
            this.content.platforms[platform].location_query = location.name;
        },

        // ============================================
        // PHASE 2: ENHANCED FIRST COMMENT
        // ============================================
        updateFirstCommentCount() {
            // Reactive - no action needed, Alpine handles it
        },

        // PHASE 3: Calendar View Methods
        getCalendarDays() {
            // Safety check for initialization
            const year = this.calendarYear || new Date().getFullYear();
            const month = this.calendarMonth !== undefined ? this.calendarMonth : new Date().getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            const days = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Add previous month days
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                const dayNumber = prevMonthLastDay - i;
                const date = new Date(year, month - 1, dayNumber);
                days.push({
                    dayNumber,
                    date: date.toISOString().split('T')[0],
                    isCurrentMonth: false,
                    isToday: false,
                    posts: this.getPostsForDate(date)
                });
            }

            // Add current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                const todayStr = today.toISOString().split('T')[0];
                days.push({
                    dayNumber: day,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    posts: this.getPostsForDate(date)
                });
            }

            // Add next month days to complete the grid
            const remainingDays = 42 - days.length; // 6 rows * 7 days
            for (let day = 1; day <= remainingDays; day++) {
                const date = new Date(year, month + 1, day);
                days.push({
                    dayNumber: day,
                    date: date.toISOString().split('T')[0],
                    isCurrentMonth: false,
                    isToday: false,
                    posts: this.getPostsForDate(date)
                });
            }

            return days;
        },

        getPostsForDate(date) {
            // Safety check for initialization
            if (!this.scheduledPosts || !Array.isArray(this.scheduledPosts)) {
                return [];
            }
            const dateStr = date.toISOString().split('T')[0];
            return this.scheduledPosts.filter(post => post.scheduled_date === dateStr);
        },

        async loadScheduledPosts() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/posts-scheduled?month=${this.calendarMonth + 1}&year=${this.calendarYear}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                const data = await response.json();
                this.scheduledPosts = data.data || data.posts || [];
            } catch (error) {
                console.error('Failed to load scheduled posts:', error);
                this.scheduledPosts = [];
            }
        },

        editScheduledPost(post) {
            // Load post data into form
            this.editMode = true;
            this.editPostId = post.id;
            this.content.global.text = post.content || '';
            this.scheduleEnabled = true;
            this.schedule.date = post.scheduled_date;
            this.schedule.time = post.scheduled_time;
            this.showCalendar = false;
        },

        // PHASE 3: Optimal Times Methods
        applyOptimalTime(time) {
            // Calculate next occurrence of the suggested day
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const targetDay = days.indexOf(time.day);
            const today = new Date();
            const currentDay = today.getDay();

            let daysUntilTarget = targetDay - currentDay;
            if (daysUntilTarget <= 0) {
                daysUntilTarget += 7; // Next week
            }

            const targetDate = new Date(today);
            targetDate.setDate(today.getDate() + daysUntilTarget);

            this.schedule.date = targetDate.toISOString().split('T')[0];
            this.schedule.time = time.time;
            this.scheduleEnabled = true;
            this.showBestTimes = false;
        },

        // DRAG-AND-DROP CALENDAR RESCHEDULING
        handlePostDragStart(post) {
            this.draggedPost = post;
        },

        async handlePostDrop(newDate) {
            if (!this.draggedPost || !newDate) return;

            const oldDate = this.draggedPost.scheduled_date;
            if (oldDate === newDate) {
                this.draggedPost = null;
                this.dragOverDate = null;
                return;
            }

            try {
                // Update post schedule via API
                const response = await fetch(`/orgs/${window.currentOrgId}/social/posts/${this.draggedPost.id}/reschedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        scheduled_date: newDate,
                        scheduled_time: this.draggedPost.time
                    })
                });

                if (response.ok) {
                    // Update local state
                    this.draggedPost.scheduled_date = newDate;

                    // Reload calendar to reflect changes
                    await this.loadScheduledPosts();

                    // Show success feedback
                    this.showToast('Post rescheduled successfully', 'success');
                } else {
                    throw new Error('Failed to reschedule post');
                }
            } catch (error) {
                console.error('Failed to reschedule post:', error);
                this.showToast('Failed to reschedule post', 'error');
            } finally {
                this.draggedPost = null;
                this.dragOverDate = null;
            }
        },

        showToast(message, type = 'info') {
            // Simple toast notification (can be enhanced with a toast library)
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 end-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 3000);
        },

        // PHASE 4: Multiple Media Sources Methods
        async uploadFromUrl() {
            if (!this.mediaUrlInput) return;

            try {
                // Validate URL format
                const url = new URL(this.mediaUrlInput);

                // Detect media type from URL
                const extension = url.pathname.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'mov', 'avi', 'webm'].includes(extension);

                this.content.global.media.push({
                    file: null,
                    type: isVideo ? 'video' : 'image',
                    preview_url: this.mediaUrlInput,
                    url: this.mediaUrlInput,
                    source: 'url'
                });

                this.mediaUrlInput = '';
                this.showMediaSourcePicker = false;
            } catch (error) {
                alert('Invalid URL. Please enter a valid media URL.');
            }
        },

        async loadMediaLibrary() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/media-library`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                const data = await response.json();
                this.mediaLibraryFiles = data.data || data.files || [];
            } catch (error) {
                console.error('Failed to load media library:', error);
                this.mediaLibraryFiles = [];
            }
        },

        selectLibraryMedia(media) {
            this.content.global.media.push({
                file: null,
                type: media.type || 'image',
                preview_url: media.thumbnail_url || media.url,
                url: media.url,
                source: 'library',
                id: media.id
            });
            this.showMediaLibrary = false;
        },

        connectGoogleDrive() {
            // Open Google Drive picker (requires Google API integration)
            alert('Google Drive integration coming soon! This will allow you to select files from your Google Drive.');
            this.showMediaSourcePicker = false;
        },

        connectDropbox() {
            // Open Dropbox chooser (requires Dropbox API integration)
            alert('Dropbox integration coming soon! This will allow you to select files from your Dropbox.');
            this.showMediaSourcePicker = false;
        },

        connectOneDrive() {
            // Open OneDrive file picker (requires Microsoft Graph API integration)
            alert('OneDrive integration coming soon! This will allow you to select files from your OneDrive.');
            this.showMediaSourcePicker = false;
        },

        // PHASE 4: Platform Warnings System
        checkPlatformWarnings() {
            this.platformWarnings = [];

            const globalText = this.content.global.text?.trim() || '';

            // PHASE 5B: Check for platform-specific customizations
            this.getSelectedPlatforms().forEach(platform => {
                const platformText = this.content.platforms[platform]?.text?.trim() || '';

                // Check if platform has different content than global
                if (platformText && platformText !== globalText) {
                    this.addPlatformWarning(
                        this.getPlatformName(platform) + ' Customized',
                        `${this.getPlatformName(platform)} content has been customized and differs from the global content`,
                        'customization',
                        platform
                    );
                }
            });

            // Check character limits for each platform
            this.getSelectedPlatforms().forEach(platform => {
                const content = this.content.platforms[platform]?.text || this.content.global.text;
                const limit = this.characterLimits[platform];

                if (limit && content.length > limit) {
                    this.addPlatformWarning(
                        platform.charAt(0).toUpperCase() + platform.slice(1),
                        `Character limit exceeded (${content.length}/${limit})`
                    );
                }
            });

            // Check for video processing warnings (Instagram, YouTube)
            if (this.content.global.media.some(m => m.type === 'video')) {
                if (this.selectedProfiles.some(p => p.platform === 'instagram')) {
                    this.addPlatformWarning(
                        'Instagram Video',
                        'Video may take 5-10 minutes to process on Instagram'
                    );
                }
                if (this.selectedProfiles.some(p => p.platform === 'youtube')) {
                    this.addPlatformWarning(
                        'YouTube Video',
                        'Please fill all required fields (Title, Description, Category)'
                    );
                }
            }

            // Check for missing required fields
            if (this.selectedProfiles.some(p => p.platform === 'youtube')) {
                if (!this.content.platforms.youtube?.video_title) {
                    this.addPlatformWarning(
                        'YouTube Required Field',
                        'Video Title is required for YouTube uploads'
                    );
                }
            }
        },

        addPlatformWarning(title, message, type = 'warning', platform = null) {
            this.platformWarnings.push({
                title,
                message,
                type, // 'warning', 'error', 'customization'
                platform,
                dismissed: false
            });
        },

        // PHASE 5B: Get platform display name
        getPlatformName(platform) {
            const names = {
                instagram: 'Instagram',
                facebook: 'Facebook',
                twitter: 'Twitter',
                linkedin: 'LinkedIn',
                tiktok: 'TikTok',
                youtube: 'YouTube'
            };
            return names[platform] || platform.charAt(0).toUpperCase() + platform.slice(1);
        },

        // PHASE 5B: Reset all platform customizations
        resetAllCustomizations() {
            if (!confirm(this.i18n.resetAllConfirm)) {
                return;
            }

            // Reset all platform-specific text
            Object.keys(this.content.platforms).forEach(platform => {
                if (this.content.platforms[platform].text) {
                    this.content.platforms[platform].text = '';
                }
            });

            this.platformWarnings = this.platformWarnings.filter(w => w.type !== 'customization');
            window.notify && window.notify(this.i18n.resetAllSuccess, 'success');
        },

        // PHASE 2: Analytics - Performance Predictions
        getPredictedReach() {
            // Calculate based on follower count of selected profiles
            if (!this.selectedProfiles || this.selectedProfiles.length === 0) {
                return '0';
            }

            const totalFollowers = this.selectedProfiles.reduce((sum, profile) => {
                return sum + (profile.followers_count || 1000); // Default 1k if not available
            }, 0);

            const hasMedia = this.content.global.media.length > 0;
            const hasHashtags = this.content.global.text.includes('#');

            // Base multiplier based on content type
            let multiplier = hasMedia ? 0.15 : 0.08; // Media gets better reach

            // Boost for hashtags
            if (hasHashtags) multiplier *= 1.2;

            // Boost for scheduled posts (better timing)
            if (this.scheduleEnabled) multiplier *= 1.1;

            const reach = Math.floor(totalFollowers * multiplier);

            // Format with K, M for large numbers
            if (reach >= 1000000) {
                return (reach / 1000000).toFixed(1) + 'M';
            } else if (reach >= 1000) {
                return (reach / 1000).toFixed(1) + 'K';
            }
            return reach.toString();
        },

        getPredictedEngagement() {
            // Calculate engagement rate based on content quality
            const score = this.getContentQualityScore();

            // Higher quality = higher engagement
            let baseRate = 2.0;
            if (score >= 80) baseRate = 6.5;
            else if (score >= 70) baseRate = 5.5;
            else if (score >= 60) baseRate = 4.5;
            else if (score >= 50) baseRate = 3.5;
            else if (score >= 40) baseRate = 2.8;

            return baseRate.toFixed(1) + '%';
        },

        getContentQualityScore() {
            // Score content based on multiple factors (0-100)
            let score = 0;
            const text = this.content.global.text || '';
            const hasMedia = this.content.global.media.length > 0;

            // Text length (optimal 100-200 chars) - up to 30 points
            const textLength = text.length;
            if (textLength >= 100 && textLength <= 200) {
                score += 30;
            } else if (textLength >= 50 && textLength < 300) {
                score += 20;
            } else if (textLength >= 20) {
                score += 10;
            }

            // Has media - 25 points (visual content performs better)
            if (hasMedia) score += 25;

            // Has hashtags - 15 points (discoverability)
            if (text.includes('#')) score += 15;

            // Has emojis - 10 points (engagement)
            const emojiRegex = /[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/u;
            if (emojiRegex.test(text)) score += 10;

            // Multiple platforms selected - 10 points (wider reach)
            if (this.selectedProfiles && this.selectedProfiles.length > 1) score += 10;

            // Scheduled (not immediate) - 10 points (optimal timing)
            if (this.scheduleEnabled) score += 10;

            return Math.min(score, 100);
        },

        getOptimizationTip() {
            const text = this.content.global.text || '';
            const hasMedia = this.content.global.media.length > 0;
            const score = this.getContentQualityScore();
            const textLength = text.length;

            // Prioritized tips based on impact
            if (score >= 80) {
                return 'Excellent! Your content is optimized for high engagement.';
            }

            if (!hasMedia) {
                return 'Add an image or video to increase engagement by 2-3x.';
            }

            if (textLength < 50) {
                return 'Add more context to your post for better engagement.';
            }

            if (textLength > 300) {
                return 'Consider shortening your text for better readability.';
            }

            if (!text.includes('#')) {
                return 'Add relevant hashtags to increase discoverability.';
            }

            const emojiRegex = /[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}]/u;
            if (!emojiRegex.test(text)) {
                return 'Add an emoji to make your post more engaging.';
            }

            if (!this.scheduleEnabled) {
                return 'Schedule for optimal time to reach more audience.';
            }

            if (!this.selectedProfiles || this.selectedProfiles.length < 2) {
                return 'Select multiple platforms to maximize your reach.';
            }

            return 'Good content! Consider testing different variations.';
        },

        // PHASE 2: Template Library Methods
        saveAsTemplate() {
            if (!this.newTemplateName.trim()) {
                return;
            }

            // Create template object with current content snapshot
            const template = {
                id: Date.now().toString(),
                name: this.newTemplateName.trim(),
                content: JSON.parse(JSON.stringify(this.content)),
                selectedPlatforms: this.selectedProfiles.map(p => p.platform),
                created_at: new Date().toISOString()
            };

            // Add to saved templates
            this.savedTemplates.unshift(template);

            // Save to localStorage for persistence
            this.saveTemplatesToStorage();

            // Clear input
            this.newTemplateName = '';

            // Notify user
            window.notify && window.notify(`Template "${template.name}" saved successfully!`, 'success');
        },

        loadTemplate(template) {
            if (!template || !template.content) {
                return;
            }

            // Confirm before loading (to avoid losing unsaved work)
            if (this.hasUnsavedChanges()) {
                if (!confirm('Loading a template will replace your current content. Continue?')) {
                    return;
                }
            }

            // Load template content
            this.content = JSON.parse(JSON.stringify(template.content));

            // Notify user
            window.notify && window.notify(`Template "${template.name}" loaded!`, 'success');

            // Collapse template library
            this.showTemplateLibrary = false;
        },

        deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }

            // Find and remove template
            const index = this.savedTemplates.findIndex(t => t.id === templateId);
            if (index !== -1) {
                const templateName = this.savedTemplates[index].name;
                this.savedTemplates.splice(index, 1);

                // Update localStorage
                this.saveTemplatesToStorage();

                // Notify user
                window.notify && window.notify(`Template "${templateName}" deleted.`, 'info');
            }
        },

        saveTemplatesToStorage() {
            try {
                localStorage.setItem('cmis_publish_templates', JSON.stringify(this.savedTemplates));
            } catch (error) {
                console.error('Failed to save templates to localStorage:', error);
            }
        },

        loadTemplatesFromStorage() {
            try {
                const stored = localStorage.getItem('cmis_publish_templates');
                if (stored) {
                    this.savedTemplates = JSON.parse(stored);
                }
            } catch (error) {
                console.error('Failed to load templates from localStorage:', error);
                this.savedTemplates = [];
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;

            // Format as date
            const options = { month: 'short', day: 'numeric' };
            if (date.getFullYear() !== now.getFullYear()) {
                options.year = 'numeric';
            }
            return date.toLocaleDateString('en-US', options);
        },

        // PHASE 2: Advanced Collaboration Methods
        getCollaboratorSummary() {
            const count = this.activeCollaborators.length;
            const editingCount = this.activeCollaborators.filter(c => c.status === 'editing').length;

            if (count === 0) return '';
            if (count === 1) {
                const name = this.activeCollaborators[0].name.split(' ')[0];
                return this.activeCollaborators[0].status === 'editing'
                    ? `${name} is editing`
                    : `${name} is viewing`;
            }
            if (editingCount > 0) {
                return `${editingCount} editing, ${count - editingCount} viewing`;
            }
            return `${count} team members viewing`;
        },

        getLastActivity() {
            if (this.activeCollaborators.length === 0) return '';

            // Find most recent activity
            const latest = this.activeCollaborators.reduce((prev, current) => {
                return new Date(current.last_activity) > new Date(prev.last_activity) ? current : prev;
            });

            return this.formatTime(latest.last_activity);
        },

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffSecs = Math.floor(diffMs / 1000);
            const diffMins = Math.floor(diffMs / 60000);

            if (diffSecs < 10) return 'just now';
            if (diffSecs < 60) return `${diffSecs}s ago`;
            if (diffMins < 60) return `${diffMins}m ago`;

            const hours = date.getHours();
            const minutes = date.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
        },

        simulateCollaborators() {
            // Simulate real-time collaboration for demo
            // In production, this would be replaced with WebSocket/Server-Sent Events

            const teamMembers = [
                { id: 1, name: 'Sarah Johnson', role: 'Marketing Manager', initials: 'SJ' },
                { id: 2, name: 'Mike Chen', role: 'Content Creator', initials: 'MC' },
                { id: 3, name: 'Emma Davis', role: 'Social Media Specialist', initials: 'ED' },
                { id: 4, name: 'Alex Turner', role: 'Designer', initials: 'AT' }
            ];

            // Randomly add/remove collaborators to simulate activity
            if (Math.random() > 0.7) {
                // Add a random collaborator
                const available = teamMembers.filter(m => !this.activeCollaborators.find(c => c.id === m.id));
                if (available.length > 0 && this.activeCollaborators.length < 3) {
                    const member = available[Math.floor(Math.random() * available.length)];
                    this.activeCollaborators.push({
                        ...member,
                        status: Math.random() > 0.5 ? 'editing' : 'viewing',
                        last_activity: new Date().toISOString()
                    });
                }
            } else if (Math.random() > 0.8 && this.activeCollaborators.length > 0) {
                // Remove a random collaborator
                const index = Math.floor(Math.random() * this.activeCollaborators.length);
                this.activeCollaborators.splice(index, 1);
            } else if (this.activeCollaborators.length > 0 && Math.random() > 0.6) {
                // Update status of a random collaborator
                const index = Math.floor(Math.random() * this.activeCollaborators.length);
                this.activeCollaborators[index].status = Math.random() > 0.5 ? 'editing' : 'viewing';
                this.activeCollaborators[index].last_activity = new Date().toISOString();
            }
        },

        startCollaborationSimulation() {
            // Start simulating collaboration activity
            if (!this.collaborationCheckInterval) {
                // Initial collaborators
                if (Math.random() > 0.5) {
                    this.simulateCollaborators();
                }

                // Check every 5 seconds
                this.collaborationCheckInterval = setInterval(() => {
                    this.simulateCollaborators();
                }, 5000);
            }
        },

        stopCollaborationSimulation() {
            if (this.collaborationCheckInterval) {
                clearInterval(this.collaborationCheckInterval);
                this.collaborationCheckInterval = null;
            }
            this.activeCollaborators = [];
        },

        // PHASE 2: Enhanced AI - Content Variation Methods
        async generateContentVariations() {
            this.aiGeneratingVariations = true;
            this.contentVariations = [];

            // Simulate AI processing delay
            await new Promise(resolve => setTimeout(resolve, 1500));

            const originalText = this.content.global.text;

            // Generate variations with different tones and styles
            const variations = [];

            // Variation 1: Professional tone
            variations.push({
                id: 1,
                text: this.generateProfessionalVariation(originalText),
                style: 'Professional',
                quality: Math.floor(Math.random() * 15) + 85,
                estimatedReach: this.estimateReach('professional')
            });

            // Variation 2: Casual/Friendly tone
            variations.push({
                id: 2,
                text: this.generateCasualVariation(originalText),
                style: 'Casual',
                quality: Math.floor(Math.random() * 15) + 80,
                estimatedReach: this.estimateReach('casual')
            });

            // Variation 3: Engaging/Question-based
            variations.push({
                id: 3,
                text: this.generateEngagingVariation(originalText),
                style: 'Engaging',
                quality: Math.floor(Math.random() * 15) + 82,
                estimatedReach: this.estimateReach('engaging')
            });

            // Variation 4: Short & Punchy
            if (originalText.length > 100) {
                variations.push({
                    id: 4,
                    text: this.generateShortVariation(originalText),
                    style: 'Concise',
                    quality: Math.floor(Math.random() * 15) + 78,
                    estimatedReach: this.estimateReach('concise')
                });
            }

            this.contentVariations = variations;
            this.aiGeneratingVariations = false;

            window.notify && window.notify(`Generated ${variations.length} content variations!`, 'success');
        },

        generateProfessionalVariation(text) {
            // Simulate professional tone transformation
            // In production, this would call an AI API
            const words = text.split(' ');
            const enhanced = words.map(word => {
                if (word.toLowerCase() === 'great') return 'excellent';
                if (word.toLowerCase() === 'good') return 'impressive';
                if (word.toLowerCase() === 'nice') return 'outstanding';
                return word;
            }).join(' ');

            return `We're pleased to share: ${enhanced}\n\n#Professional #Quality`;
        },

        generateCasualVariation(text) {
            // Simulate casual tone transformation
            const casual = text.replace(/\./g, '!').replace(/We are/gi, 'We\'re');
            return `Hey there! 👋 ${casual}\n\nWhat do you think? 💭`;
        },

        generateEngagingVariation(text) {
            // Simulate engaging question-based variation
            const questions = [
                'Have you experienced this?',
                'What\'s your take on this?',
                'Curious to hear your thoughts!',
                'Who else can relate?'
            ];
            const question = questions[Math.floor(Math.random() * questions.length)];
            return `${text}\n\n${question} 🤔\n\n#Community #Engagement`;
        },

        generateShortVariation(text) {
            // Simulate condensed version
            const sentences = text.split('.').filter(s => s.trim());
            const firstSentence = sentences[0]?.trim() || text.substring(0, 100);
            return `${firstSentence}... ⚡\n\n#Quick #Update`;
        },

        estimateReach(style) {
            const base = 1000;
            const multipliers = {
                professional: 1.2,
                casual: 1.5,
                engaging: 1.8,
                concise: 1.3
            };
            const reach = base * (multipliers[style] || 1);
            return Math.floor(reach + Math.random() * 500);
        },

        async improveContent() {
            this.aiGeneratingVariations = true;

            // Simulate AI processing
            await new Promise(resolve => setTimeout(resolve, 1000));

            const originalText = this.content.global.text;

            // Add emojis if missing
            let improved = originalText;
            if (!/[\u{1F600}-\u{1F64F}]/u.test(improved)) {
                const emojis = ['✨', '🚀', '💡', '⭐', '🎯'];
                improved += ` ${emojis[Math.floor(Math.random() * emojis.length)]}`;
            }

            // Add call-to-action if missing
            if (!improved.match(/\?|!{2,}/)) {
                const ctas = [
                    '\n\nWhat do you think?',
                    '\n\nShare your thoughts below!',
                    '\n\nTag someone who needs to see this!',
                    '\n\nDouble-tap if you agree!'
                ];
                improved += ctas[Math.floor(Math.random() * ctas.length)];
            }

            // Add hashtags if missing or few
            const hashtagCount = (improved.match(/#/g) || []).length;
            if (hashtagCount < 2) {
                improved += '\n\n#Trending #MustSee';
            }

            this.content.global.text = improved;
            this.aiGeneratingVariations = false;

            window.notify && window.notify('Content improved with AI suggestions!', 'success');
        },

        useVariation(variation) {
            if (!variation || !variation.text) return;

            if (confirm('Replace current content with this variation?')) {
                this.content.global.text = variation.text;
                window.notify && window.notify(`Applied "${variation.style}" variation!`, 'success');
                this.contentVariations = []; // Clear variations after use
            }
        },

        // PHASE 2: Comprehensive Error Handling & Recovery
        handleError(error, context = 'Operation') {
            console.error(`Error in ${context}:`, error);

            // Store error for potential recovery
            this.lastError = {
                message: error.message || 'Unknown error',
                context,
                timestamp: new Date(),
                stack: error.stack
            };

            // Determine error type and handle accordingly
            if (error.name === 'TypeError' || error.name === 'ReferenceError') {
                this.handleClientError(error, context);
            } else if (error.message?.includes('fetch') || error.message?.includes('network')) {
                this.handleNetworkError(error, context);
            } else if (error.response) {
                this.handleServerError(error, context);
            } else {
                this.showUserFriendlyError(`${context} failed. Please try again.`);
            }
        },

        handleClientError(error, context) {
            console.error('Client-side error:', error);

            // User-friendly messages for common client errors
            const friendlyMessages = {
                'Cannot read property': 'A required field is missing. Please refresh and try again.',
                'is not defined': 'A required component failed to load. Please refresh the page.',
                'JSON': 'Data format error. Please try again.',
            };

            let message = `An error occurred in ${context}.`;
            for (const [key, value] of Object.entries(friendlyMessages)) {
                if (error.message?.includes(key)) {
                    message = value;
                    break;
                }
            }

            this.showUserFriendlyError(message);
        },

        handleNetworkError(error, context) {
            this.networkError = true;

            const message = navigator.onLine
                ? 'Unable to connect to server. Please check your connection and try again.'
                : 'You appear to be offline. Please check your internet connection.';

            this.showUserFriendlyError(message, 'warning');

            // Offer retry option
            if (this.retryCount < this.maxRetries) {
                setTimeout(() => {
                    this.showRetryOption(context);
                }, 2000);
            }
        },

        handleServerError(error, context) {
            const status = error.response?.status;
            const data = error.response?.data;

            let message = `Server error in ${context}.`;

            // Handle specific HTTP status codes
            switch (status) {
                case 400:
                    message = data?.message || 'Invalid request. Please check your input.';
                    break;
                case 401:
                    message = 'Your session has expired. Please refresh and log in again.';
                    setTimeout(() => window.location.reload(), 3000);
                    break;
                case 403:
                    message = 'You don\'t have permission to perform this action.';
                    break;
                case 404:
                    message = 'The requested resource was not found.';
                    break;
                case 422:
                    message = data?.message || 'Validation error. Please check your input.';
                    this.displayValidationErrors(data?.errors);
                    return; // Validation errors handled separately
                case 429:
                    message = 'Too many requests. Please wait a moment and try again.';
                    break;
                case 500:
                case 502:
                case 503:
                    message = 'Server error. Our team has been notified. Please try again later.';
                    break;
                default:
                    message = data?.message || `Unexpected server error (${status}).`;
            }

            this.showUserFriendlyError(message, 'error');
        },

        displayValidationErrors(errors) {
            if (!errors || typeof errors !== 'object') return;

            const messages = [];
            for (const [field, fieldErrors] of Object.entries(errors)) {
                if (Array.isArray(fieldErrors)) {
                    messages.push(...fieldErrors);
                } else {
                    messages.push(fieldErrors);
                }
            }

            this.validationErrors = messages;
        },

        showUserFriendlyError(message, type = 'error') {
            // Use notification system if available
            if (window.notify) {
                window.notify(message, type);
            } else {
                // Fallback to alert
                alert(message);
            }
        },

        showRetryOption(context) {
            if (confirm(`${context} failed. Would you like to retry?`)) {
                this.retryLastOperation(context);
            }
        },

        async retryLastOperation(context) {
            this.retryCount++;
            this.networkError = false;

            // Implement context-specific retry logic
            try {
                switch (context) {
                    case 'Load Profile Groups':
                        await this.loadProfileGroups();
                        break;
                    case 'Load Brand Voices':
                        await this.loadBrandVoices();
                        break;
                    case 'Publish Post':
                        await this.publishNow();
                        break;
                    default:
                        console.log('No retry handler for:', context);
                }

                // Reset retry count on success
                this.retryCount = 0;
                window.notify && window.notify('Operation completed successfully!', 'success');
            } catch (error) {
                this.handleError(error, `Retry ${context}`);
            }
        },

        recoverFromError() {
            // Attempt to recover from last error
            if (!this.lastError) return;

            console.log('Attempting recovery from:', this.lastError);

            // Clear error state
            this.networkError = false;
            this.lastError = null;
            this.retryCount = 0;

            // Clear validation errors
            this.validationErrors = [];

            window.notify && window.notify('Error cleared. Please try again.', 'info');
        },

        // Wrap async operations with error handling
        async safeAsync(operation, context = 'Operation') {
            try {
                return await operation();
            } catch (error) {
                this.handleError(error, context);
                return null;
            }
        },

        // PHASE 5B: Get profile count for a specific platform
        getProfileCountForPlatform(platform) {
            if (!this.selectedProfiles || this.selectedProfiles.length === 0) {
                return 0;
            }
            return this.selectedProfiles.filter(profile => profile.platform === platform).length;
        },

        // PHASE 5B: Apply content to all profiles of the same platform
        applyToAllPlatformProfiles(platform) {
            const count = this.getProfileCountForPlatform(platform);

            if (count <= 1) {
                return;
            }

            const platformDisplayName = this.getPlatformName(platform);
            const confirmMessage = this.i18n.applyToAllConfirm
                .replace(':platform', platformDisplayName)
                .replace(':count', count);

            if (!confirm(confirmMessage)) {
                return;
            }

            // Get the current platform content
            const sourcePlatformContent = JSON.parse(JSON.stringify(this.content.platforms[platform] || {}));

            // Apply to all selected profiles of the same platform
            // Note: In a real implementation, you might want to do this via an API call
            // For now, we're just copying the content structure

            // Success notification
            const successMessage = this.i18n.appliedToAllSuccess
                .replace(':platform', platformDisplayName)
                .replace(':count', count);

            window.notify && window.notify(successMessage, 'success');
        },

        hasUnsavedChanges() {
            // Check if there's any content in global text
            if (this.content.global.text.trim() !== '') return true;

            // Check if there's any media
            if (this.content.global.media.length > 0) return true;

            // Check if there's any link
            if (this.content.global.link.trim() !== '') return true;

            // Check if there are any selected profiles
            if (this.selectedProfiles.length > 0) return true;

            // Check platform-specific content
            for (const platform in this.content.platforms) {
                if (this.content.platforms[platform].text && this.content.platforms[platform].text.trim() !== '') {
                    return true;
                }
            }

            return false;
        },

        closeModal(force = false) {
            // Check for unsaved changes before closing (skip if force=true or publishSucceeded)
            if (!force && !this.publishSucceeded && this.hasUnsavedChanges() && !this.editMode) {
                const confirmMessage = document.documentElement.lang === 'ar'
                    ? 'لديك تغييرات غير محفوظة. هل تريد حقاً الإغلاق؟'
                    : 'You have unsaved changes. Do you really want to close?';

                if (!confirm(confirmMessage)) {
                    return; // Don't close if user cancels
                }
            }

            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            this.stopCollaborationSimulation();
            this.open = false;
            this.resetForm();
        },

        resetForm() {
            this.editMode = false;
            this.editPostId = null;
            this.publishSucceeded = false; // Reset publish success flag
            this.selectedProfiles = [];
            this.content = {
                global: { text: '', media: [], link: '', labels: [] },
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
        }
    };
}

// Register component with Alpine.js during initialization
// This prevents "X is not defined" warnings during DOM scanning
document.addEventListener('alpine:init', () => {
    if (typeof Alpine !== 'undefined') {
        // Register as Alpine component
        Alpine.data('publishModal', publishModal);
    }
});

// Also make it globally available for direct function calls
if (typeof window !== 'undefined') {
    window.publishModal = publishModal;
}
