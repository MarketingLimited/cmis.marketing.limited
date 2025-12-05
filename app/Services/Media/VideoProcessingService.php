<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;
use RuntimeException;

/**
 * Video Processing Service
 *
 * Handles video conversion to H.264/MP4 format and thumbnail extraction
 * using FFmpeg for cross-platform social media compatibility.
 */
class VideoProcessingService
{
    private string $ffmpegPath;
    private string $ffprobePath;

    public function __construct()
    {
        $this->ffmpegPath = config('media.ffmpeg_path', '/usr/bin/ffmpeg');
        $this->ffprobePath = config('media.ffprobe_path', '/usr/bin/ffprobe');

        $this->validateFFmpegInstallation();
    }

    /**
     * Process a video file: convert to H.264/MP4 and extract thumbnail
     *
     * @param string $inputPath Full path to the input video file
     * @param string $orgId Organization ID for storage path
     * @return array Processing results with URLs and metadata
     */
    public function processVideo(string $inputPath, string $orgId): array
    {
        Log::info('Starting video processing', [
            'input_path' => $inputPath,
            'org_id' => $orgId,
        ]);

        // Get video metadata
        $metadata = $this->getVideoMetadata($inputPath);

        // Generate output paths
        $uuid = Str::uuid();
        $processedPath = "social-media/{$orgId}/processed/{$uuid}.mp4";
        $thumbnailPath = "social-media/{$orgId}/thumbnails/{$uuid}.jpg";

        $processedFullPath = Storage::disk('public')->path($processedPath);
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        // Ensure directories exist
        $this->ensureDirectoryExists(dirname($processedFullPath));
        $this->ensureDirectoryExists(dirname($thumbnailFullPath));

        // Check if conversion is needed or just remuxing
        $needsTranscode = $this->needsTranscode($metadata);

        // Convert or remux video
        if ($needsTranscode) {
            $this->convertToH264($inputPath, $processedFullPath, $metadata);
        } else {
            $this->remuxToMp4($inputPath, $processedFullPath);
        }

        // Extract thumbnail at configured time (default 1 second)
        $thumbnailTime = config('media.video.thumbnail_time', 1.0);
        // Ensure thumbnail time doesn't exceed video duration
        $actualThumbnailTime = min($thumbnailTime, max(0, $metadata['duration'] - 0.1));
        $this->extractThumbnail($inputPath, $thumbnailFullPath, $actualThumbnailTime);

        // Get processed file info
        $processedSize = filesize($processedFullPath);
        $processedMetadata = $this->getVideoMetadata($processedFullPath);

        // Generate public URLs
        $processedUrl = Storage::disk('public')->url($processedPath);
        $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);

        // Make URLs absolute if needed
        if (!str_starts_with($processedUrl, 'http')) {
            $processedUrl = url($processedUrl);
        }
        if (!str_starts_with($thumbnailUrl, 'http')) {
            $thumbnailUrl = url($thumbnailUrl);
        }

        Log::info('Video processing completed', [
            'org_id' => $orgId,
            'processed_path' => $processedPath,
            'thumbnail_path' => $thumbnailPath,
            'transcoded' => $needsTranscode,
        ]);

        return [
            'processed_url' => $processedUrl,
            'processed_path' => $processedPath,
            'thumbnail_url' => $thumbnailUrl,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_time' => $actualThumbnailTime,
            'width' => $processedMetadata['width'],
            'height' => $processedMetadata['height'],
            'duration' => $processedMetadata['duration'],
            'file_size' => $processedSize,
            'codec' => 'h264',
            'format' => 'mp4',
            'transcoded' => $needsTranscode,
            'original_codec' => $metadata['video_codec'] ?? 'unknown',
        ];
    }

    /**
     * Extract a thumbnail at a specific timestamp
     *
     * @param string $inputPath Path to the video file
     * @param float $timestamp Timestamp in seconds
     * @return string Path to the generated thumbnail
     */
    public function extractThumbnailAtTimestamp(string $inputPath, float $timestamp, string $orgId): array
    {
        $uuid = Str::uuid();
        $thumbnailPath = "social-media/{$orgId}/thumbnails/{$uuid}.jpg";
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        $this->ensureDirectoryExists(dirname($thumbnailFullPath));
        $this->extractThumbnail($inputPath, $thumbnailFullPath, $timestamp);

        $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);
        if (!str_starts_with($thumbnailUrl, 'http')) {
            $thumbnailUrl = url($thumbnailUrl);
        }

        return [
            'thumbnail_url' => $thumbnailUrl,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_time' => $timestamp,
        ];
    }

    /**
     * Get video metadata using ffprobe
     *
     * @param string $inputPath Path to the video file
     * @return array Video metadata
     */
    public function getVideoMetadata(string $inputPath): array
    {
        $command = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>&1',
            escapeshellcmd($this->ffprobePath),
            escapeshellarg($inputPath)
        );

        $output = shell_exec($command);
        $data = json_decode($output, true);

        if (!$data) {
            throw new RuntimeException('Failed to get video metadata: ' . $output);
        }

        // Find video stream
        $videoStream = null;
        $audioStream = null;
        foreach ($data['streams'] ?? [] as $stream) {
            if ($stream['codec_type'] === 'video' && !$videoStream) {
                $videoStream = $stream;
            }
            if ($stream['codec_type'] === 'audio' && !$audioStream) {
                $audioStream = $stream;
            }
        }

        if (!$videoStream) {
            throw new RuntimeException('No video stream found in file');
        }

        $duration = (float) ($data['format']['duration'] ?? $videoStream['duration'] ?? 0);
        $width = (int) ($videoStream['width'] ?? 0);
        $height = (int) ($videoStream['height'] ?? 0);

        return [
            'duration' => $duration,
            'width' => $width,
            'height' => $height,
            'video_codec' => $videoStream['codec_name'] ?? 'unknown',
            'audio_codec' => $audioStream['codec_name'] ?? null,
            'bitrate' => (int) ($data['format']['bit_rate'] ?? 0),
            'format' => $data['format']['format_name'] ?? 'unknown',
            'file_size' => (int) ($data['format']['size'] ?? 0),
            'has_audio' => $audioStream !== null,
            'frame_rate' => $this->parseFrameRate($videoStream['r_frame_rate'] ?? '0/1'),
        ];
    }

    /**
     * Check if video needs full transcoding or just remuxing
     *
     * @param array $metadata Video metadata
     * @return bool True if transcoding is needed
     */
    private function needsTranscode(array $metadata): bool
    {
        // Always transcode if codec is not H.264
        if (!in_array($metadata['video_codec'], ['h264', 'avc', 'avc1'])) {
            return true;
        }

        // Check if audio needs re-encoding (should be AAC for best compatibility)
        if ($metadata['has_audio'] && !in_array($metadata['audio_codec'], ['aac', 'mp4a'])) {
            return true;
        }

        return false;
    }

    /**
     * Convert video to H.264/AAC/MP4 format
     *
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param array $metadata Input video metadata
     */
    private function convertToH264(string $inputPath, string $outputPath, array $metadata): void
    {
        $preset = config('media.video.h264_preset', 'medium');
        $crf = config('media.video.h264_crf', 23);
        $audioBitrate = config('media.video.audio_bitrate', '128k');

        // Build FFmpeg command
        $audioOptions = $metadata['has_audio']
            ? sprintf('-c:a aac -b:a %s', escapeshellarg($audioBitrate))
            : '-an'; // No audio

        $command = sprintf(
            '%s -y -i %s -c:v libx264 -preset %s -crf %d %s -movflags +faststart %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($preset),
            (int) $crf,
            $audioOptions,
            escapeshellarg($outputPath)
        );

        Log::debug('Running FFmpeg transcode command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('FFmpeg transcoding failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('FFmpeg output file is empty or missing');
        }
    }

    /**
     * Remux video to MP4 container without re-encoding
     *
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     */
    private function remuxToMp4(string $inputPath, string $outputPath): void
    {
        $command = sprintf(
            '%s -y -i %s -c copy -movflags +faststart %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        Log::debug('Running FFmpeg remux command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('FFmpeg remuxing failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('FFmpeg output file is empty or missing');
        }
    }

    /**
     * Extract a single frame as thumbnail
     *
     * @param string $inputPath Input video path
     * @param string $outputPath Output image path
     * @param float $timestamp Timestamp in seconds
     */
    private function extractThumbnail(string $inputPath, string $outputPath, float $timestamp): void
    {
        $quality = config('media.storage.thumbnail_quality', 85);

        // Use -ss before -i for faster seeking
        $command = sprintf(
            '%s -y -ss %s -i %s -vframes 1 -q:v %d %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg(number_format($timestamp, 2, '.', '')),
            escapeshellarg($inputPath),
            (int) ceil((100 - $quality) / 3), // Convert quality to FFmpeg scale (2 = best, 31 = worst)
            escapeshellarg($outputPath)
        );

        Log::debug('Running FFmpeg thumbnail command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('FFmpeg thumbnail extraction failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('FFmpeg thumbnail file is empty or missing');
        }
    }

    /**
     * Validate FFmpeg installation
     */
    private function validateFFmpegInstallation(): void
    {
        if (!file_exists($this->ffmpegPath)) {
            throw new RuntimeException(
                "FFmpeg not found at {$this->ffmpegPath}. " .
                "Please install FFmpeg: sudo apt-get install -y ffmpeg"
            );
        }

        if (!file_exists($this->ffprobePath)) {
            throw new RuntimeException(
                "FFprobe not found at {$this->ffprobePath}. " .
                "Please install FFmpeg: sudo apt-get install -y ffmpeg"
            );
        }
    }

    /**
     * Ensure directory exists
     *
     * @param string $directory Directory path
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException("Failed to create directory: {$directory}");
            }
        }
    }

    /**
     * Parse frame rate from FFprobe format (e.g., "30/1", "30000/1001")
     *
     * @param string $frameRate Frame rate string
     * @return float Calculated frame rate
     */
    private function parseFrameRate(string $frameRate): float
    {
        if (str_contains($frameRate, '/')) {
            [$numerator, $denominator] = explode('/', $frameRate);
            return $denominator > 0 ? (float) $numerator / (float) $denominator : 0;
        }

        return (float) $frameRate;
    }

    /**
     * Delete processed files (for cleanup)
     *
     * @param string $processedPath Path to processed video
     * @param string $thumbnailPath Path to thumbnail
     */
    public function deleteProcessedFiles(string $processedPath, string $thumbnailPath): void
    {
        if ($processedPath && Storage::disk('public')->exists($processedPath)) {
            Storage::disk('public')->delete($processedPath);
        }

        if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
            Storage::disk('public')->delete($thumbnailPath);
        }
    }

    /**
     * Process video with platform-specific optimizations.
     *
     * This method handles platform requirements like:
     * - TikTok: Minimum 540px, 9:16 aspect ratio preferred, max 10 min
     * - Instagram: Minimum 500px, 9:16 for Reels, max 60 sec (Reels) / 60 min (IGTV)
     * - Facebook: Flexible, but 1280px width recommended
     * - YouTube: Flexible, 1080p+ recommended
     *
     * @param string $inputPath Full path to the input video file
     * @param string $orgId Organization ID for storage path
     * @param string $platform Target platform (tiktok, instagram, facebook, youtube)
     * @param array $options Additional options
     * @return array Processing results with URLs and metadata
     */
    public function processVideoForPlatform(
        string $inputPath,
        string $orgId,
        string $platform = 'tiktok',
        array $options = []
    ): array {
        Log::info('Starting platform-specific video processing', [
            'input_path' => $inputPath,
            'org_id' => $orgId,
            'platform' => $platform,
        ]);

        // Get video metadata
        $metadata = $this->getVideoMetadata($inputPath);
        $width = $metadata['width'];
        $height = $metadata['height'];
        $duration = $metadata['duration'];

        // Get platform requirements
        $requirements = $this->getPlatformRequirements($platform);

        // Check if video meets minimum requirements
        $minDimension = min($width, $height);
        $needsUpscale = $minDimension < $requirements['min_dimension'];
        $needsTranscode = $this->needsTranscode($metadata);

        Log::info('Video analysis for platform', [
            'platform' => $platform,
            'original_size' => "{$width}x{$height}",
            'min_required' => $requirements['min_dimension'],
            'needs_upscale' => $needsUpscale,
            'needs_transcode' => $needsTranscode,
        ]);

        // Generate output paths
        $uuid = Str::uuid();
        $processedPath = "social-media/{$orgId}/processed/{$uuid}.mp4";
        $thumbnailPath = "social-media/{$orgId}/thumbnails/{$uuid}.jpg";

        $processedFullPath = Storage::disk('public')->path($processedPath);
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        // Ensure directories exist
        $this->ensureDirectoryExists(dirname($processedFullPath));
        $this->ensureDirectoryExists(dirname($thumbnailFullPath));

        // Process video with platform optimizations
        if ($needsUpscale || $needsTranscode) {
            $this->convertForPlatform($inputPath, $processedFullPath, $metadata, $requirements);
        } else {
            // Just remux to ensure proper container
            $this->remuxToMp4($inputPath, $processedFullPath);
        }

        // Extract thumbnail
        $thumbnailTime = config('media.video.thumbnail_time', 1.0);
        $actualThumbnailTime = min($thumbnailTime, max(0, $metadata['duration'] - 0.1));
        $this->extractThumbnail($processedFullPath, $thumbnailFullPath, $actualThumbnailTime);

        // Get processed file info
        $processedSize = filesize($processedFullPath);
        $processedMetadata = $this->getVideoMetadata($processedFullPath);

        // Generate public URLs
        $processedUrl = Storage::disk('public')->url($processedPath);
        $thumbnailUrl = Storage::disk('public')->url($thumbnailPath);

        if (!str_starts_with($processedUrl, 'http')) {
            $processedUrl = url($processedUrl);
        }
        if (!str_starts_with($thumbnailUrl, 'http')) {
            $thumbnailUrl = url($thumbnailUrl);
        }

        Log::info('Platform video processing completed', [
            'org_id' => $orgId,
            'platform' => $platform,
            'processed_path' => $processedPath,
            'original_size' => "{$width}x{$height}",
            'final_size' => "{$processedMetadata['width']}x{$processedMetadata['height']}",
            'upscaled' => $needsUpscale,
        ]);

        return [
            'processed_url' => $processedUrl,
            'processed_path' => $processedPath,
            'thumbnail_url' => $thumbnailUrl,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_time' => $actualThumbnailTime,
            'width' => $processedMetadata['width'],
            'height' => $processedMetadata['height'],
            'duration' => $processedMetadata['duration'],
            'file_size' => $processedSize,
            'codec' => 'h264',
            'format' => 'mp4',
            'transcoded' => $needsTranscode,
            'upscaled' => $needsUpscale,
            'platform' => $platform,
            'original_codec' => $metadata['video_codec'] ?? 'unknown',
            'original_size' => "{$width}x{$height}",
        ];
    }

    /**
     * Get platform-specific video requirements.
     *
     * @param string $platform Platform name
     * @return array Requirements array
     */
    private function getPlatformRequirements(string $platform): array
    {
        return match ($platform) {
            'tiktok' => [
                'min_dimension' => 540,       // TikTok minimum
                'recommended_width' => 1080,   // Full HD vertical
                'recommended_height' => 1920,  // 9:16 aspect
                'max_duration' => 600,         // 10 minutes
                'min_duration' => 3,           // 3 seconds
                'aspect_ratio' => '9:16',
            ],
            'instagram' => [
                'min_dimension' => 500,
                'recommended_width' => 1080,
                'recommended_height' => 1920,
                'max_duration' => 90,          // Reels max 90 seconds
                'min_duration' => 3,
                'aspect_ratio' => '9:16',
            ],
            'facebook' => [
                'min_dimension' => 500,
                'recommended_width' => 1280,
                'recommended_height' => 720,
                'max_duration' => 14400,       // 4 hours
                'min_duration' => 1,
                'aspect_ratio' => '16:9',
            ],
            'youtube' => [
                'min_dimension' => 480,
                'recommended_width' => 1920,
                'recommended_height' => 1080,
                'max_duration' => 43200,       // 12 hours
                'min_duration' => 1,
                'aspect_ratio' => '16:9',
            ],
            default => [
                'min_dimension' => 480,
                'recommended_width' => 1080,
                'recommended_height' => 1920,
                'max_duration' => 600,
                'min_duration' => 3,
                'aspect_ratio' => '9:16',
            ],
        };
    }

    /**
     * Convert video with platform-specific optimizations.
     *
     * @param string $inputPath Input file path
     * @param string $outputPath Output file path
     * @param array $metadata Input video metadata
     * @param array $requirements Platform requirements
     */
    private function convertForPlatform(
        string $inputPath,
        string $outputPath,
        array $metadata,
        array $requirements
    ): void {
        $preset = config('media.video.h264_preset', 'medium');
        $crf = config('media.video.h264_crf', 23);
        $audioBitrate = config('media.video.audio_bitrate', '128k');

        $width = $metadata['width'];
        $height = $metadata['height'];
        $minDimension = min($width, $height);
        $minRequired = $requirements['min_dimension'];

        // Calculate scale filter if upscaling is needed
        $scaleFilter = '';
        if ($minDimension < $minRequired) {
            // Calculate scale factor to meet minimum requirements
            $scaleFactor = ceil($minRequired / $minDimension * 10) / 10; // Round up to 1 decimal

            // Use scale2ref or direct scale, preserving aspect ratio
            // scale=iw*{factor}:ih*{factor}:flags=lanczos
            $newWidth = (int) ceil($width * $scaleFactor);
            $newHeight = (int) ceil($height * $scaleFactor);

            // Ensure dimensions are even (required by x264)
            $newWidth = $newWidth + ($newWidth % 2);
            $newHeight = $newHeight + ($newHeight % 2);

            $scaleFilter = sprintf(
                '-vf "scale=%d:%d:flags=lanczos"',
                $newWidth,
                $newHeight
            );

            Log::info('Upscaling video for platform', [
                'original' => "{$width}x{$height}",
                'scaled' => "{$newWidth}x{$newHeight}",
                'factor' => $scaleFactor,
                'min_required' => $minRequired,
            ]);
        }

        // Build audio options
        $audioOptions = $metadata['has_audio']
            ? sprintf('-c:a aac -b:a %s', escapeshellarg($audioBitrate))
            : '-an';

        // Build FFmpeg command
        $command = sprintf(
            '%s -y -i %s -c:v libx264 -preset %s -crf %d %s %s -movflags +faststart %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($preset),
            (int) $crf,
            $scaleFilter,
            $audioOptions,
            escapeshellarg($outputPath)
        );

        Log::debug('Running FFmpeg platform conversion command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('FFmpeg platform conversion failed', [
                'command' => $command,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
            ]);
            throw new RuntimeException('FFmpeg platform conversion failed: ' . implode("\n", $output));
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('FFmpeg output file is empty or missing');
        }
    }

    /**
     * Check if video meets platform requirements.
     *
     * @param string $inputPath Path to video
     * @param string $platform Target platform
     * @return array Validation result with details
     */
    public function validateForPlatform(string $inputPath, string $platform): array
    {
        $metadata = $this->getVideoMetadata($inputPath);
        $requirements = $this->getPlatformRequirements($platform);

        $issues = [];
        $warnings = [];

        // Check minimum dimension
        $minDimension = min($metadata['width'], $metadata['height']);
        if ($minDimension < $requirements['min_dimension']) {
            $issues[] = sprintf(
                'Video resolution (%dx%d) is below minimum (%dpx). Will be upscaled automatically.',
                $metadata['width'],
                $metadata['height'],
                $requirements['min_dimension']
            );
        }

        // Check duration
        if ($metadata['duration'] < $requirements['min_duration']) {
            $issues[] = sprintf(
                'Video duration (%.1fs) is too short. Minimum is %ds.',
                $metadata['duration'],
                $requirements['min_duration']
            );
        }

        if ($metadata['duration'] > $requirements['max_duration']) {
            $issues[] = sprintf(
                'Video duration (%.1f min) exceeds maximum (%.1f min).',
                $metadata['duration'] / 60,
                $requirements['max_duration'] / 60
            );
        }

        // Check codec
        if (!in_array($metadata['video_codec'], ['h264', 'avc', 'avc1'])) {
            $warnings[] = sprintf(
                'Video codec (%s) will be converted to H.264 for better compatibility.',
                $metadata['video_codec']
            );
        }

        return [
            'valid' => empty(array_filter($issues, fn($i) => !str_contains($i, 'upscaled'))),
            'needs_processing' => !empty($issues) || !empty($warnings),
            'issues' => $issues,
            'warnings' => $warnings,
            'metadata' => $metadata,
            'requirements' => $requirements,
        ];
    }
}
