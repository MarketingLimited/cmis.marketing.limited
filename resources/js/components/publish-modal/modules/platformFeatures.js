/**
 * Publish Modal - Platform Features Module
 * Handles emoji picker, hashtag manager, mention picker, link shortener, location tagging, rich text formatting
 */

export function getPlatformFeaturesMethods() {
    return {
        // ============================================
        // EMOJI PICKER
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

        // ============================================
        // RICH TEXT FORMATTING
        // ============================================

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
        // HASHTAG MANAGER
        // ============================================

        async loadHashtagSets() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/hashtag-sets`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
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
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
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
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
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
        // MENTION PICKER
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
        // LINK SHORTENER
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
        // LOCATION TAGGING
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
        // FIRST COMMENT
        // ============================================

        updateFirstCommentCount() {
            // Reactive - no action needed, Alpine handles it
        },

        // ============================================
        // MEDIA SOURCES (EXTENDED)
        // ============================================

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
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
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

        // ============================================
        // AUTO-SAVE
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
        }
    };
}

export default getPlatformFeaturesMethods;
