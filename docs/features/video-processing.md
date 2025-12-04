# Video Processing & Thumbnail Extraction

**Date:** 2025-12-04
**Author:** Claude Code Agent
**Related Files:**
- `config/media.php`
- `app/Services/Media/VideoProcessingService.php`
- `app/Jobs/ProcessVideoJob.php`
- `app/Http/Controllers/Social/VideoProcessingController.php`
- `app/Http/Controllers/Social/MediaLibraryController.php`
- `public/js/components/publish-modal.js`
- `resources/views/components/publish-modal/preview-panel.blade.php`
- `resources/views/components/publish-modal/composer/global-content.blade.php`
- `resources/lang/en/publish.php`
- `resources/lang/ar/publish.php`

---

## Summary

Automatic video processing feature for the social media publishing module. When users upload videos in the publish modal, the system automatically:

1. **Converts videos to H.264/MP4 format** - Compatible with all major social media platforms (TikTok, Instagram, Facebook, YouTube, etc.)
2. **Extracts thumbnail at 1 second** - Auto-generates a video cover image
3. **Displays thumbnail in preview** - Shows video thumbnail instead of broken image placeholder
4. **Allows cover frame selection** - Users can choose a different frame time for the video thumbnail

---

## Problem Solved

Previously, uploading videos to TikTok and other platforms failed with errors like:
- "Only MP4 videos with H.264 codec are supported"
- Preview panel showed broken image placeholders for videos

---

## Architecture

### Processing Flow

```
User Upload → MediaLibraryController → Store Original → Create MediaAsset
                                                              ↓
                                                    Dispatch ProcessVideoJob
                                                              ↓
                                             Queue Worker → VideoProcessingService
                                                              ↓
                                                    FFprobe: Get metadata
                                                              ↓
                                              FFmpeg: Convert to H.264/MP4
                                                              ↓
                                              FFmpeg: Extract thumbnail
                                                              ↓
                                                    Update MediaAsset
                                                              ↓
Frontend Polling ← Processing Status API ← MediaAsset Status
       ↓
Update UI with thumbnail
```

### Components

#### 1. VideoProcessingService (`app/Services/Media/VideoProcessingService.php`)

Core FFmpeg wrapper with methods:
- `processVideo(string $inputPath, string $orgId)` - Main orchestration
- `getVideoMetadata(string $inputPath)` - FFprobe metadata extraction
- `convertToH264(string $inputPath, string $outputPath)` - H.264 transcoding
- `extractThumbnail(string $inputPath, string $outputPath, float $timestamp)` - Thumbnail extraction
- `extractThumbnailAtTimestamp(string $inputPath, float $timestamp, string $orgId)` - User-selected frame

#### 2. ProcessVideoJob (`app/Jobs/ProcessVideoJob.php`)

Queue job with:
- 3 retry attempts
- 10-minute timeout
- RLS context handling
- Error status tracking

#### 3. VideoProcessingController (`app/Http/Controllers/Social/VideoProcessingController.php`)

API endpoints:
- `POST /orgs/{org}/social/media/processing-status` - Poll multiple asset statuses
- `POST /orgs/{org}/social/media/update-thumbnail` - Update thumbnail at specific timestamp
- `POST /orgs/{org}/social/media/frames` - Get multiple frames for cover selection UI

---

## Configuration

### Environment Variables

```env
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe
VIDEO_H264_PRESET=medium
VIDEO_H264_CRF=23
VIDEO_PROCESSING_QUEUE=default
VIDEO_PROCESSING_TIMEOUT=600
```

### Config File (`config/media.php`)

```php
return [
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    'processing' => [
        'queue' => env('VIDEO_PROCESSING_QUEUE', 'default'),
        'timeout' => env('VIDEO_PROCESSING_TIMEOUT', 600),
        'tries' => 3,
    ],
    'video' => [
        'thumbnail_time' => 1.0,
        'h264_preset' => env('VIDEO_H264_PRESET', 'medium'),
        'h264_crf' => env('VIDEO_H264_CRF', 23),
        'audio_bitrate' => '128k',
    ],
];
```

---

## API Endpoints

### Poll Processing Status

```http
POST /orgs/{org}/social/media/processing-status
Content-Type: application/json

{
    "asset_ids": ["uuid-1", "uuid-2"]
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "statuses": {
            "uuid-1": {
                "status": "completed",
                "thumbnail_url": "https://example.com/thumb.jpg",
                "thumbnail_time": 1.0,
                "processed_url": "https://example.com/processed.mp4",
                "width": 1920,
                "height": 1080,
                "duration": 30.5
            }
        },
        "all_complete": true
    }
}
```

### Update Thumbnail

```http
POST /orgs/{org}/social/media/update-thumbnail
Content-Type: application/json

{
    "asset_id": "uuid-1",
    "timestamp": 5.5
}
```

### Get Frames for Selection

```http
POST /orgs/{org}/social/media/frames
Content-Type: application/json

{
    "asset_id": "uuid-1",
    "count": 6
}
```

---

## Frontend Integration

### JavaScript (publish-modal.js)

Key additions:
- `pollVideoProcessingStatus(assetIds)` - Polls every 2 seconds, max 60 attempts
- `updateThumbnailAtTime(assetId, timestamp)` - Updates thumbnail via API

### Blade Templates

Preview panel and media grid now show:
- Processing spinner when `processing_status === 'processing' || 'pending'`
- Thumbnail with play icon overlay when `processing_status === 'completed'`
- Fallback video icon when no thumbnail available

---

## Translation Keys

Added to both `en/publish.php` and `ar/publish.php`:

| Key | English | Arabic |
|-----|---------|--------|
| `processing_video` | Processing video... | جاري معالجة الفيديو... |
| `video_ready` | Video ready | الفيديو جاهز |
| `select_cover_frame` | Select cover frame | اختر صورة الغلاف |
| `change_cover` | Change cover | تغيير الغلاف |
| `video_processing_failed` | Video processing failed | فشلت معالجة الفيديو |
| `video_thumbnail_updated` | Video thumbnail updated | تم تحديث صورة الفيديو |

---

## FFmpeg Commands Used

```bash
# Get metadata
ffprobe -v quiet -print_format json -show_format -show_streams input.mp4

# Convert to H.264 (full transcode)
ffmpeg -y -i input.mov -c:v libx264 -preset medium -crf 23 \
  -c:a aac -b:a 128k -movflags +faststart output.mp4

# Remux only (if already H.264)
ffmpeg -y -i input.mp4 -c copy -movflags +faststart output.mp4

# Extract thumbnail at timestamp
ffmpeg -y -ss 1 -i input.mp4 -vframes 1 -q:v 2 thumbnail.jpg
```

---

## Database

Uses existing `MediaAsset` model (`cmis_social.media_assets` table) with fields:
- `analysis_status` - 'pending', 'processing', 'completed', 'failed'
- `analysis_error` - Error message if failed
- `metadata` (JSONB) - Stores `processed_url`, `thumbnail_url`, `thumbnail_path`, `thumbnail_time`
- `duration_seconds`, `width`, `height` - Video dimensions from ffprobe

---

## Testing

### Manual Testing

1. Navigate to https://cmis-test.kazaaz.com/orgs/{org}/social
2. Click "Create New Post"
3. Upload a video file (MP4, MOV, AVI, etc.)
4. Observe:
   - Processing spinner appears in media grid
   - After ~5-30 seconds, thumbnail appears
   - Preview panel shows thumbnail with play icon overlay

### Verify Queue Processing

```bash
# Check queue worker is running
ps aux | grep "queue:work"

# Watch logs during upload
tail -f storage/logs/laravel.log | grep -i "video"
```

### Test Different Formats

Upload videos in various formats:
- MP4 (H.264) - Should remux only
- MOV - Should transcode
- AVI - Should transcode
- WebM - Should transcode

---

## Troubleshooting

### Common Issues

1. **FFmpeg not found**
   - Install: `sudo apt-get install ffmpeg`
   - Verify: `which ffmpeg && ffmpeg -version`

2. **Processing stuck at 'pending'**
   - Check queue worker: `ps aux | grep queue:work`
   - Restart: `php artisan queue:restart`

3. **Thumbnail not showing**
   - Check storage permissions: `chmod -R 775 storage/app/public`
   - Verify symlink: `php artisan storage:link`

4. **Memory issues with large videos**
   - Increase PHP memory limit in `php.ini`
   - Consider chunked upload for very large files

---

## Related Documentation

- [MediaAsset Model](../api/media-asset.md)
- [Queue Configuration](../infrastructure/queues.md)
- [Social Publishing Overview](../features/social-publishing.md)
