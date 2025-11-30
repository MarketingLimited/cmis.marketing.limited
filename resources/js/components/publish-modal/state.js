/**
 * Publish Modal - State Management Module
 * Initial state and data structures for the publish modal
 */

export function getInitialState() {
    return {
        // Modal state
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
        showBrandSafety: false,
        brandSafetyScore: 100,

        // Approval
        requiresApproval: false,

        // NEW: Emoji Picker
        showEmojiPicker: false,
        emojiCategories: [
            { name: 'Smileys', icon: '😀', emojis: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '☺️', '😚'] },
            { name: 'Gestures', icon: '👍', emojis: ['👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👏', '🙌', '👐', '🤲', '🤝', '🙏'] },
            { name: 'Objects', icon: '⚽', emojis: ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🥏', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳'] }
        ],
        selectedEmojiCategory: 'Smileys',

        // NEW: Hashtag Manager
        showHashtagManager: false,
        hashtagSets: [],
        selectedHashtagSet: null,
        hashtagSearch: '',

        // NEW: Mention Picker
        showMentionPicker: false,
        mentionSearch: '',

        // NEW: Media Processing
        isProcessingMedia: false,
        mediaProcessingProgress: 0,

        // NEW: Link Shortener
        showLinkShortener: false,
        linkToShorten: '',
        shortenedLink: '',
        isShortening: false,

        // NEW: Auto-save
        autoSaveEnabled: true,
        lastSaved: null,
        isSaving: false,
        autoSaveInterval: null,

        // PHASE 2: Location Tagging
        showLocationPicker: false,
        locationSearch: '',
        locationResults: {},
        isLoadingLocation: false,

        // PHASE 2: Enhanced First Comment
        showFirstCommentHelper: false,

        // PHASE 4: Multiple Media Sources
        mediaSource: 'local', // local, url, library, canva, unsplash, giphy
        mediaUrl: '',
        isLoadingMedia: false,

        // PHASE 4: Platform Warnings
        platformWarnings: {},

        // Character limits
        characterLimits: {
            instagram: { text: 2200, first_comment: 2200 },
            facebook: { text: 63206 },
            twitter: { text: 280 },
            linkedin: { text: 3000 },
            tiktok: { description: 2200, video_title: 100 },
            youtube: { description: 5000, video_title: 100 },
            google_business: { text: 1500 }
        },

        // Publishing status (async publishing)
        isPublishing: false,
        publishingStatus: null, // 'uploading', 'submitting', 'publishing', null
        publishingProgress: {
            total: 0,
            completed: 0,
            success: 0,
            failed: 0
        },
    };
}

export default getInitialState;
