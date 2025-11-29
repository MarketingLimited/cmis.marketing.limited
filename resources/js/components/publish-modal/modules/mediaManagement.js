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

        async processImage(file) {
            this.isProcessingMedia = true;
            try {
                // Platform-specific limits (most restrictive for compatibility)
                const MAX_FILE_SIZE = 8 * 1024 * 1024; // 8MB (Instagram recommended)
                const MAX_DIMENSION = 1920; // Max width/height
                const JPEG_QUALITY = 0.9; // 90% quality

                // If file is already small enough and dimensions are okay, return as-is
                if (file.size <= MAX_FILE_SIZE) {
                    const dimensions = await this.getImageDimensions(file);
                    if (dimensions.width <= MAX_DIMENSION && dimensions.height <= MAX_DIMENSION) {
                        return file;
                    }
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
                // Platform-specific video limits
                const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB (Instagram recommended)
                const MAX_DURATION = 60; // 60 seconds (Instagram feed)

                // Get video metadata
                const metadata = await this.getVideoMetadata(file);

                // Validate file size
                if (file.size > MAX_VIDEO_SIZE) {
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    window.notify(
                        `Video is too large (${sizeMB}MB). Maximum size is ${MAX_VIDEO_SIZE / 1024 / 1024}MB. Please compress the video before uploading.`,
                        'error'
                    );
                    return null;
                }

                // Validate duration
                if (metadata.duration > MAX_DURATION) {
                    window.notify(
                        `Video is too long (${Math.round(metadata.duration)}s). Maximum duration is ${MAX_DURATION}s for Instagram feed posts. Consider trimming the video or posting to Reels.`,
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
