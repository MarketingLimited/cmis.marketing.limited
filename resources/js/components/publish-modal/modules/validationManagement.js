/**
 * Publish Modal - Validation Management Module
 * Handles form validation and platform-specific validation rules
 */

export function getValidationManagementMethods() {
    return {
        // ============================================
        // VALIDATION METHODS
        // ============================================

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

        // ============================================
        // PLATFORM WARNINGS
        // ============================================

        checkPlatformWarnings() {
            this.platformWarnings = [];

            const globalText = this.content.global.text?.trim() || '';

            // Check for platform-specific customizations
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

        getProfileCountForPlatform(platform) {
            if (!this.selectedProfiles || this.selectedProfiles.length === 0) {
                return 0;
            }
            return this.selectedProfiles.filter(profile => profile.platform === platform).length;
        },

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

            // Success notification
            const successMessage = '{{ __("publish.applied_to_all_success") }}'
                .replace(':platform', platformDisplayName)
                .replace(':count', count);

            window.notify && window.notify(successMessage, 'success');
        }
    };
}

export default getValidationManagementMethods;
