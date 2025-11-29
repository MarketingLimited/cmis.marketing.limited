// Publish Modal Alpine.js Component
// Define publishModal function immediately so it's available for Alpine
function publishModal() {
    return {
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

        // Character limits
        characterLimits: {
            twitter: 280,
            instagram: 2200,
            facebook: 63206,
            linkedin: 3000,
            tiktok: 2200
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

        init() {
            this.loadProfileGroups();
            this.loadBrandVoices();
            this.loadPlatformConnections();

            // Listen for open modal event
            window.addEventListener('open-publish-modal', (event) => {
                this.open = true;
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

            // Basic validation
            if (!Array.isArray(this.selectedProfiles) || this.selectedProfiles.length === 0) {
                errors.push('{{ __("publish.select_at_least_one_profile") }}');
            }

            const globalText = this.content.global.text || '';
            const globalMedia = this.content.global.media || [];
            if (!globalText.trim() && globalMedia.length === 0) {
                errors.push('{{ __("publish.content_or_media_required") }}');
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
                    errors.push('{{ __("publish.instagram_character_limit") }}'.replace(':limit', instagramSpecs.characterLimit));
                }

                // Reel requires video
                if (instagramContent?.post_type === 'reel') {
                    const hasVideo = globalMedia.some(m => m.type === 'video');
                    if (!hasVideo) {
                        errors.push('{{ __("publish.instagram_reel_requires_video") }}');
                    }
                }

                // Story requires media first
                if (instagramContent?.post_type === 'story' && globalMedia.length === 0) {
                    errors.push('{{ __("publish.instagram_story_requires_media") }}');
                }

                // Media count validation
                if (globalMedia.length > instagramSpecs.maxMediaCount) {
                    errors.push('{{ __("publish.instagram_max_media") }}'.replace(':max', instagramSpecs.maxMediaCount));
                }
            }

            // === TWITTER/X VALIDATION ===
            if (selectedPlatforms.includes('twitter')) {
                const twitterSpecs = this.platformSpecs.twitter;
                const twitterContent = this.content.platforms.twitter;

                // Character limit validation
                const twitterText = twitterContent?.text || globalText;
                if (twitterText.length > twitterSpecs.characterLimit) {
                    errors.push('{{ __("publish.twitter_character_limit") }}'.replace(':limit', twitterSpecs.characterLimit));
                }

                // Media count validation
                const imageCount = globalMedia.filter(m => m.type === 'image').length;
                const videoCount = globalMedia.filter(m => m.type === 'video').length;

                if (imageCount > twitterSpecs.maxImagesPerPost) {
                    errors.push('{{ __("publish.twitter_max_images") }}'.replace(':max', twitterSpecs.maxImagesPerPost));
                }

                if (videoCount > twitterSpecs.maxVideosPerPost) {
                    errors.push('{{ __("publish.twitter_max_videos") }}');
                }

                // Cannot mix images and videos
                if (imageCount > 0 && videoCount > 0) {
                    errors.push('{{ __("publish.twitter_no_mixed_media") }}');
                }
            }

            // === LINKEDIN VALIDATION ===
            if (selectedPlatforms.includes('linkedin')) {
                const linkedinSpecs = this.platformSpecs.linkedin;
                const linkedinContent = this.content.platforms.linkedin;

                // Character limit validation
                const linkedinText = linkedinContent?.text || globalText;
                if (linkedinText.length > linkedinSpecs.characterLimit) {
                    errors.push('{{ __("publish.linkedin_character_limit") }}'.replace(':limit', linkedinSpecs.characterLimit));
                }

                // Video validation
                const videos = globalMedia.filter(m => m.type === 'video');
                if (videos.length > 0) {
                    // Check for Partner Program requirement
                    errors.push('{{ __("publish.linkedin_partner_required") }}');
                }
            }

            // === TIKTOK VALIDATION ===
            if (selectedPlatforms.includes('tiktok')) {
                const tiktokSpecs = this.platformSpecs.tiktok;
                const tiktokContent = this.content.platforms.tiktok;

                // Character limit validation
                const tiktokText = tiktokContent?.text || globalText;
                if (tiktokText.length > tiktokSpecs.characterLimit) {
                    errors.push('{{ __("publish.tiktok_character_limit") }}'.replace(':limit', tiktokSpecs.characterLimit));
                }

                // TikTok requires video
                const hasVideo = globalMedia.some(m => m.type === 'video');
                if (!hasVideo) {
                    errors.push('{{ __("publish.tiktok_video_required") }}');
                }

                // MP4 + H.264 format validation
                const nonMp4Videos = globalMedia.filter(m =>
                    m.type === 'video' && !m.mime_type?.includes('mp4')
                );
                if (nonMp4Videos.length > 0) {
                    errors.push('{{ __("publish.tiktok_mp4_h264_required") }}');
                }
            }

            // === YOUTUBE VALIDATION ===
            if (selectedPlatforms.includes('youtube')) {
                const youtubeSpecs = this.platformSpecs.youtube;
                const youtubeContent = this.content.platforms.youtube;

                // Title required
                if (!youtubeContent?.video_title?.trim()) {
                    errors.push('{{ __("publish.youtube_title_required") }}');
                }

                // Video required
                const hasVideo = globalMedia.some(m => m.type === 'video');
                if (!hasVideo) {
                    errors.push('{{ __("publish.youtube_video_required") }}');
                }
            }

            // === SNAPCHAT VALIDATION ===
            if (selectedPlatforms.includes('snapchat')) {
                const snapchatSpecs = this.platformSpecs.snapchat;

                // Snapchat requires media
                if (globalMedia.length === 0) {
                    errors.push('{{ __("publish.snapchat_media_required") }}');
                }
            }

            // Schedule validation
            if (this.publishMode === 'schedule' && this.scheduleEnabled) {
                if (!this.schedule.date || !this.schedule.time) {
                    errors.push('{{ __("publish.schedule_datetime_required") }}');
                }

                // Check if scheduled time is in the future
                const scheduledDateTime = new Date(`${this.schedule.date}T${this.schedule.time}`);
                if (scheduledDateTime <= new Date()) {
                    errors.push('{{ __("publish.schedule_must_be_future") }}');
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
                    this.content.global.media.push({
                        file: file,
                        type: file.type.startsWith('video') ? 'video' : 'image',
                        preview_url: e.target.result
                    });
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
                        this.content.global.media.push({
                            file: file,
                            type: file.type.startsWith('video') ? 'video' : 'image',
                            preview_url: e.target.result
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeMedia(index) {
            this.content.global.media.splice(index, 1);
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
                    this.closeModal();
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
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post created successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to create post', 'error');
                }
            } catch (e) {
                console.error('Failed to publish', e);
                window.notify('Failed to publish post', 'error');
            }
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
                    this.closeModal();
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
                    this.closeModal();
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
                    this.closeModal();
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
            if (!confirm('{{ __("publish.reset_all_confirm") }}')) {
                return;
            }

            // Reset all platform-specific text
            Object.keys(this.content.platforms).forEach(platform => {
                if (this.content.platforms[platform].text) {
                    this.content.platforms[platform].text = '';
                }
            });

            this.platformWarnings = this.platformWarnings.filter(w => w.type !== 'customization');
            window.notify && window.notify('{{ __("publish.reset_all_success") }}', 'success');
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
            const confirmMessage = '{{ __("publish.apply_to_all_confirm") }}'
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
            const successMessage = '{{ __("publish.applied_to_all_success") }}'
                .replace(':platform', platformDisplayName)
                .replace(':count', count);

            window.notify && window.notify(successMessage, 'success');
        },

        closeModal() {
            if (this.autoSaveInterval) clearInterval(this.autoSaveInterval);
            this.open = false;
            this.resetForm();
        },

        resetForm() {
            this.editMode = false;
            this.editPostId = null;
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

// Also make it globally available
