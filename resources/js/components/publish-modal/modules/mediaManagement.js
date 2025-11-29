/**
 * Publish Modal - Media Management Module
 * Handles media upload, processing, and preview
 */

export function getMediaManagementMethods() {
    return {
        // ============================================
        // MEDIA UPLOAD METHODS
        // ============================================

        async handleMediaUpload(event) {
            const files = Array.from(event.target.files);

            for (const file of files) {
                const isVideo = file.type.startsWith('video');
                const isImage = file.type.startsWith('image');

                // Process based on type
                if (isImage && this.imageProcessingEnabled) {
                    const processedFile = await this.processImage(file);
                    this.addMediaToContent(processedFile);
                } else if (isVideo && this.videoProcessingEnabled) {
                    const processedFile = await this.processVideo(file);
                    if (processedFile) {
                        this.addMediaToContent(processedFile);
                    }
                } else {
                    // No processing, add directly
                    this.addMediaToContent(file);
                }
            }
        },

        addMediaToContent(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.content.global.media.push({
                    file: file,
                    type: file.type.startsWith('video') ? 'video' : 'image',
                    url: e.target.result,
                    name: file.name,
                    size: file.size
                });
            };
            reader.readAsDataURL(file);
        },

        removeMedia(index) {
            this.content.global.media.splice(index, 1);
        },

        reorderMedia(oldIndex, newIndex) {
            const media = this.content.global.media.splice(oldIndex, 1)[0];
            this.content.global.media.splice(newIndex, 0, media);
        },

        // ============================================
        // MEDIA PROCESSING
        // ============================================
        // Platform File Size Limitations:
        //
        // META PLATFORMS:
        // Messenger Platform (Attachment Upload API):
        //   - Images: 8MB (png, jpeg, gif)
        //   - Video: 25MB (mp4, ogg, avi, mov, webm)
        //   - Audio: 25MB (aac, m4a, wav, mp4)
        //
        // WhatsApp Cloud API (Most Restrictive for Images/Videos):
        //   - Images: 5MB (png, jpeg)
        //   - Video: 16MB (mpeg4)
        //   - Audio: 16MB (ACC, M4A, MP3, AMR, OGG-OPUS)
        //   - Documents: 100MB (text, Excel, Word, PowerPoint, PDF)
        //
        // Marketing API (Images in Link Shares):
        //   - Images: 8MB (jpeg, bmp, png, gif, tiff)
        //   - PNG: Recommended <1MB to avoid pixelation
        //
        // Instagram:
        //   - Images: 8MB recommended
        //   - Videos: 100MB recommended, max 60s for feed
        //
        // GOOGLE BUSINESS PROFILE:
        // Photos:
        //   - Size: 10 KB to 5 MB (JPG or PNG)
        //   - Recommended: 720x720 px
        //   - Minimum: 250x250 px
        //   - Quality: In focus, well lit, no excessive filters
        //
        // Videos:
        //   - Duration: Up to 30 seconds (Most Restrictive Duration)
        //   - Size: Up to 75 MB
        //   - Resolution: 720p or higher
        //
        // Profile Photos:
        //   - Square (250px to 500px)
        //   - Formats: JPG, PNG, GIF
        //
        // TIKTOK CONTENT POSTING API:
        // Videos (SIGNIFICANTLY HIGHER LIMITS):
        //   - Size: Up to 4GB (!)
        //   - Duration: Up to 10 minutes (some creators limited to 3 min)
        //   - Formats: MP4 (recommended), WebM, MOV
        //   - Codecs: H.264, H.265, VP8, VP9
        //   - Frame Rate: 23-60 FPS
        //   - Dimensions: 360-4096 px (both height and width)
        //   - Supports chunked upload for large files
        //
        // TikTok Ads Profile Photos:
        //   - Size: Max 50KB
        //   - Dimensions: 98x98px (1:1 aspect ratio)
        //   - Formats: .jpg, .jpeg, .png
        //
        // SNAPCHAT MARKETING API:
        // Images (General Ad Types):
        //   - Top Snap Images: Max 5MB
        //   - Audience Filters: Max 300KB (Most Restrictive!)
        //   - Preview Images (Story Ads): Max 2MB
        //   - Logo Images (Story Ads): Max 2MB
        //   - Spotlight Images: Max 20MB
        //   - Lead Gen Ads: Max 5MB
        //
        // Videos (General Ad Types):
        //   - Full Screen Canvas: Up to 1GB
        //   - Spotlight: 5-60 seconds
        //   - Sponsored Snaps: 10 seconds
        //   - Duration varies by ad type
        //
        // Other:
        //   - Shared Media (Creative Kit): Max 300MB
        //   - Lenses: Max 8MB (zipped), ML models 10MB
        //   - Catalog Feeds: Max 500MB
        //   - Event Files: Max 100MB
        //   - Multipart upload for files >32MB (up to 1GB)
        //
        // X (TWITTER) API:
        // Images:
        //   - Max file size: 5MB
        //
        // GIFs:
        //   - Max file size: 15MB
        //
        // Videos:
        //   - Max file size: 512MB (with media_category=amplify_video)
        //   - Max duration: 140 seconds (regular API uploads)
        //   - Premium subscribers: Longer videos via Media Studio
        //
        // Chunked Upload (for large files):
        //   - Chunk size: Max 5MB (recommended ≤4MB to avoid 413 errors)
        //   - Process: INIT → APPEND (chunks) → FINALIZE → attach media_id
        //
        // LINKEDIN API:
        // Documents (PPT, PPTX, DOC, DOCX, PDF):
        //   - Max file size: 100MB
        //   - Max pages: 300 pages
        //
        // Videos:
        //   - Max file size: 500MB (via Videos API)
        //   - Duration: 3 seconds to 30 minutes
        //   - File format: MP4
        //   - Resolution: 256x144 to 4096x2304
        //   - Frame rate: 24-60 FPS
        //
        // Images (In-Feed Posts):
        //   - Max file size: 8MB
        //   - File formats: PNG or JPG
        //
        // Images (Open Graph Shares from External Websites):
        //   - Max file size: 5MB
        //   - Min dimensions: 1200x627 pixels
        //   - Recommended ratio: 1.91:1
        //
        // Vector Assets (Profile/Background Photos):
        //   - Max pixels: 36,152,320 pixels
        //   - File formats: JPG, GIF, PNG
        //
        // YOUTUBE DATA API:
        // Videos (videos.insert):
        //   - Max file size: 256GB (!!!)
        //   - Supports resumable uploads
        //   - Chunk size: Multiple of 256KB (except last chunk)
        //
        // Captions (captions.insert):
        //   - Max file size: 100MB
        //
        // Channel Banners (channelBanners.insert):
        //   - Max file size: 6MB
        //
        // Playlist Images (playlistImages.insert):
        //   - Max file size: 2MB
        //
        // Thumbnails (thumbnails.set):
        //   - Max file size: 2MB (Most Restrictive for Images!)
        //
        // CROSS-PLATFORM COMPATIBILITY STRATEGY:
        // When posting to MULTIPLE platforms simultaneously, we use the
        // MOST RESTRICTIVE limits to ensure universal compatibility:
        //   - Images: 5MB max, 10KB min (WhatsApp & Google Business)
        //   - Videos: 16MB max, 30s duration (WhatsApp size, Google duration)
        //   - Min resolution: 250x250 px (Google Business)
        //
        // NOTE: For TikTok-ONLY posts, users can upload much larger files
        // (up to 4GB, 10 min). The restrictive limits below are designed
        // for cross-platform publishing compatibility.
        //
        // Platform-Specific Limits Summary:
        // ┌──────────────┬──────────┬──────────┬──────────┐
        // │ Platform     │ Images   │ Videos   │ Duration │
        // ├──────────────┼──────────┼──────────┼──────────┤
        // │ YouTube†     │ 2MB      │ 256GB    │ -        │
        // │ WhatsApp     │ 5MB      │ 16MB     │ -        │
        // │ Google Biz   │ 5MB      │ 75MB     │ 30s      │
        // │ Snapchat*    │ 300KB-5MB│ 1GB      │ 10s-60s  │
        // │ X (Twitter)  │ 5MB      │ 512MB    │ 140s     │
        // │ LinkedIn     │ 5-8MB    │ 500MB    │ 3s-30min │
        // │ Messenger    │ 8MB      │ 25MB     │ -        │
        // │ Instagram    │ 8MB      │ 100MB    │ 60s      │
        // │ TikTok       │ -        │ 4GB      │ 10min    │
        // └──────────────┴──────────┴──────────┴──────────┘
        // * Snapchat varies by ad type (Audience Filters: 300KB!)
        // † YouTube thumbnails: 2MB (most restrictive for images!)
        //   YouTube videos: 256GB (most permissive!)
        //
        // MOST RESTRICTIVE LIMITS (Cross-Platform):
        //   - Images: 2MB (YouTube thumbnails - MOST restrictive!)
        //            5MB (WhatsApp, Google Biz, X, LinkedIn OG)
        //   - Videos: 16MB (WhatsApp - most restrictive)
        //   - Duration: 30s (Google Business - most restrictive)
        //   - Min Image Size: 10KB (Google Biz)
        //   - Min Resolution: 250x250px (Google Biz)
        //
        // IMPLEMENTATION DECISION:
        // We use 5MB for images (not 2MB) because:
        // - YouTube thumbnails are a specialized use case
        // - Most platforms support 5MB+
        // - 2MB would be too restrictive for general use
        // - Users uploading YouTube thumbnails can be warned separately
        //
        // NOTE: These conservative limits ensure media works across MOST
        // supported platforms. For single-platform posts (especially
        // YouTube, TikTok, LinkedIn, or X), users can upload much larger files.
        //
        // ============================================
        // GOOGLE GEMINI API (For AI Features - Not Social Publishing)
        // ============================================
        // Note: These limits apply to CMIS's AI features (semantic search,
        // content analysis, embeddings), NOT to social media publishing.
        //
        // Files API (Upload & Manage Files):
        //   - Max file size per file: 2GB
        //   - Max total storage per project: 20GB
        //   - Retention: 48 hours
        //
        // Inline Data (In Prompt):
        //   - Max request size (text + files): 20MB
        //   - Use Files API for larger files
        //
        // Vertex AI Gemini Pro:
        //   Documents (PDFs):
        //     - Max file size: 50MB
        //     - Max pages per PDF: 1,000 pages
        //     - Max files per prompt: 3,000
        //   Images:
        //     - Max image size: 7MB
        //     - Max images per prompt: 3,000
        //
        // Gemini Apps (Free/Go Plan):
        //   - Max file size (non-video): 100MB
        //   - Max video size: 2GB (max 5 minutes length)
        //   - Max files per prompt: 10
        //
        // Gemini Apps (Pro/Ultra Plan):
        //   - Max file size (non-video): 100MB
        //   - Max video size: 2GB (max 1 hour total length)
        //   - Max files per prompt: 10
        //   - GitHub repos/ZIP: Up to 5,000 files, 100MB each
        //
        // VEO 3 API (AI Video Generation):
        // Image-to-Video Input:
        //   - veo-3.1-generate: Max 8MB image input
        //   - veo-3.0-generate-preview: Max 20MB image input
        //   - Uses Files API for storage (2GB per file, 48h retention)
        // ============================================

        async processImage(file) {
            this.isProcessingMedia = true;
            try {
                // Cross-platform limits (most restrictive for universal compatibility)
                const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB (WhatsApp & Google Business)
                const MIN_FILE_SIZE = 10 * 1024; // 10KB (Google Business)
                const MAX_DIMENSION = 1920; // Max width/height
                const MIN_DIMENSION = 250; // Min width/height (Google Business)
                const RECOMMENDED_DIMENSION = 720; // Recommended for Google Business
                const JPEG_QUALITY = 0.85; // 85% quality (better compression)

                // Check minimum file size
                if (file.size < MIN_FILE_SIZE) {
                    window.notify(
                        `Image is too small (${(file.size / 1024).toFixed(2)}KB). Minimum size is 10KB for Google Business Profile.`,
                        'error'
                    );
                    return null;
                }

                // Get image dimensions
                const dimensions = await this.getImageDimensions(file);

                // Check minimum resolution
                if (dimensions.width < MIN_DIMENSION || dimensions.height < MIN_DIMENSION) {
                    window.notify(
                        `Image resolution is too small (${dimensions.width}x${dimensions.height}). Minimum resolution is 250x250 pixels for Google Business Profile.`,
                        'error'
                    );
                    return null;
                }

                // If file is already within acceptable range, return as-is
                if (file.size <= MAX_FILE_SIZE &&
                    dimensions.width <= MAX_DIMENSION &&
                    dimensions.height <= MAX_DIMENSION) {
                    return file;
                }

                // Need to resize/compress
                const processedBlob = await this.resizeAndCompressImage(file, MAX_DIMENSION, JPEG_QUALITY);

                // If processed file is still too large, reduce quality further
                let finalBlob = processedBlob;
                let quality = JPEG_QUALITY;

                while (finalBlob.size > MAX_FILE_SIZE && quality > 0.5) {
                    quality -= 0.1;
                    finalBlob = await this.resizeAndCompressImage(file, MAX_DIMENSION, quality);
                }

                // Create new File object from blob
                const processedFile = new File(
                    [finalBlob],
                    file.name.replace(/\.[^/.]+$/, '.jpg'), // Change extension to .jpg
                    { type: 'image/jpeg' }
                );

                console.log(`Image processed: ${(file.size / 1024 / 1024).toFixed(2)}MB → ${(processedFile.size / 1024 / 1024).toFixed(2)}MB`);

                return processedFile;
            } catch (error) {
                console.error('Error processing image:', error);
                window.notify('Failed to process image. Using original.', 'warning');
                return file;
            } finally {
                this.isProcessingMedia = false;
            }
        },

        async getImageDimensions(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    resolve({ width: img.width, height: img.height });
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(file);
            });
        },

        async resizeAndCompressImage(file, maxDimension, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();

                reader.onload = (e) => {
                    const img = new Image();

                    img.onload = () => {
                        // Calculate new dimensions
                        let width = img.width;
                        let height = img.height;

                        if (width > maxDimension || height > maxDimension) {
                            if (width > height) {
                                height = Math.round((height * maxDimension) / width);
                                width = maxDimension;
                            } else {
                                width = Math.round((width * maxDimension) / height);
                                height = maxDimension;
                            }
                        }

                        // Create canvas and draw resized image
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // Convert to blob
                        canvas.toBlob(
                            (blob) => {
                                if (blob) {
                                    resolve(blob);
                                } else {
                                    reject(new Error('Failed to create blob'));
                                }
                            },
                            'image/jpeg',
                            quality
                        );
                    };

                    img.onerror = reject;
                    img.src = e.target.result;
                };

                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        async processVideo(file) {
            this.isProcessingMedia = true;
            try {
                // Cross-platform video limits (most restrictive for universal compatibility)
                // WhatsApp: 16MB, Messenger: 25MB, Instagram: 100MB, Google Business: 75MB
                // Duration: Google Business 30s (most restrictive), Instagram 60s
                const MAX_VIDEO_SIZE = 16 * 1024 * 1024; // 16MB (WhatsApp Cloud API limit)
                const MAX_DURATION = 30; // 30 seconds (Google Business Profile - most restrictive)
                const MIN_RESOLUTION_HEIGHT = 720; // 720p minimum (Google Business)

                // Get video metadata
                const metadata = await this.getVideoMetadata(file);

                // Validate file size
                if (file.size > MAX_VIDEO_SIZE) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    window.notify(
                        `Video is too large (${sizeMB}MB). Maximum size is ${MAX_VIDEO_SIZE / 1024 / 1024}MB for compatibility across all platforms (WhatsApp, Messenger, Instagram). Please compress the video before uploading.`,
                        'error'
                    );
                    return null;
                }

                // Validate duration
                if (metadata.duration > MAX_DURATION) {
                    window.notify(
                        `Video is too long (${Math.round(metadata.duration)}s). Maximum duration is ${MAX_DURATION}s for Google Business Profile (Instagram allows up to 60s). Please trim the video.`,
                        'error'
                    );
                    return null;
                }

                // Validate resolution (Google Business requires 720p+)
                if (metadata.height < MIN_RESOLUTION_HEIGHT) {
                    window.notify(
                        `Video resolution is too low (${metadata.width}x${metadata.height}). Minimum resolution is 720p (1280x720) for Google Business Profile.`,
                        'warning'
                    );
                }

                // Validate format
                if (!file.type.includes('mp4') && !file.type.includes('mov')) {
                    window.notify(
                        'Video format may not be compatible. MP4 or MOV formats are recommended.',
                        'warning'
                    );
                }

                console.log(`Video validated: ${(file.size / 1024 / 1024).toFixed(2)}MB, ${Math.round(metadata.duration)}s`);

                return file;
            } catch (error) {
                console.error('Error processing video:', error);
                window.notify('Failed to validate video. Proceeding with caution.', 'warning');
                return file;
            } finally {
                this.isProcessingMedia = false;
            }
        },

        async getVideoMetadata(file) {
            return new Promise((resolve, reject) => {
                const video = document.createElement('video');
                video.preload = 'metadata';

                video.onloadedmetadata = () => {
                    resolve({
                        duration: video.duration,
                        width: video.videoWidth,
                        height: video.videoHeight
                    });
                    URL.revokeObjectURL(video.src);
                };

                video.onerror = () => {
                    reject(new Error('Failed to load video metadata'));
                    URL.revokeObjectURL(video.src);
                };

                video.src = URL.createObjectURL(file);
            });
        },

        // ============================================
        // MEDIA SOURCES
        // ============================================

        async loadMediaFromUrl() {
            if (!this.mediaUrl.trim()) return;

            this.isLoadingMedia = true;
            try {
                const response = await fetch(this.mediaUrl);
                const blob = await response.blob();
                const file = new File([blob], 'media-from-url', { type: blob.type });

                const isVideo = file.type.startsWith('video');
                const isImage = file.type.startsWith('image');

                // Process media before adding
                let processedFile = file;
                if (isImage && this.imageProcessingEnabled) {
                    processedFile = await this.processImage(file);
                } else if (isVideo && this.videoProcessingEnabled) {
                    processedFile = await this.processVideo(file);
                    if (!processedFile) {
                        // Video validation failed
                        this.mediaUrl = '';
                        return;
                    }
                }

                this.addMediaToContent(processedFile);
                this.mediaUrl = '';
            } catch (error) {
                console.error('Failed to load media from URL:', error);
                window.notify('Failed to load media from URL', 'error');
            } finally {
                this.isLoadingMedia = false;
            }
        },

        async loadMediaFromLibrary(mediaItem) {
            this.content.global.media.push({
                file: null,
                type: mediaItem.type,
                url: mediaItem.url,
                name: mediaItem.name,
                size: mediaItem.size,
                library_id: mediaItem.id
            });
        },

        async loadMediaFromCanva() {
            // Canva integration logic
            console.log('Loading from Canva...');
        },

        async loadMediaFromUnsplash() {
            // Unsplash integration logic
            console.log('Loading from Unsplash...');
        },

        async loadMediaFromGiphy() {
            // Giphy integration logic
            console.log('Loading from Giphy...');
        },

        setMediaSource(source) {
            this.mediaSource = source;
        },

        // ============================================
        // MEDIA VALIDATION
        // ============================================

        get mediaCount() {
            return this.content.global.media.length;
        },

        get totalMediaSize() {
            return this.content.global.media.reduce((total, media) => total + (media.size || 0), 0);
        },

        validateMediaForPlatform(platform) {
            const specs = this.platformSpecs[platform];
            if (!specs) return { valid: true };

            const errors = [];
            const imageCount = this.content.global.media.filter(m => m.type === 'image').length;
            const videoCount = this.content.global.media.filter(m => m.type === 'video').length;

            if (imageCount + videoCount > specs.maxMediaCount) {
                errors.push(`Maximum ${specs.maxMediaCount} media items allowed for ${platform}`);
            }

            return {
                valid: errors.length === 0,
                errors
            };
        }
    };
}

export default getMediaManagementMethods;
