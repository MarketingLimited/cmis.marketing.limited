<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the paths to FFmpeg and FFprobe binaries. These are used
    | for video processing, format conversion, and thumbnail extraction.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | Video Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for video transcoding and thumbnail generation.
    |
    */

    'video' => [
        // Default timestamp (in seconds) for thumbnail extraction
        'thumbnail_time' => 1.0,

        // H.264 encoding preset (ultrafast, superfast, veryfast, faster, fast, medium, slow, slower, veryslow)
        // 'medium' is a good balance between speed and quality
        'h264_preset' => env('VIDEO_H264_PRESET', 'medium'),

        // Constant Rate Factor (0-51, lower = better quality, larger file)
        // 23 is considered "visually lossless" for most content
        'h264_crf' => env('VIDEO_H264_CRF', 23),

        // Audio bitrate for transcoding
        'audio_bitrate' => '128k',

        // Maximum file size in MB (for validation)
        'max_size_mb' => 500,

        // Supported input formats
        'supported_formats' => ['mp4', 'mov', 'avi', 'wmv', 'webm', 'mkv', 'flv'],

        // Output format (H.264/AAC in MP4 container for maximum compatibility)
        'output_format' => 'mp4',
        'video_codec' => 'libx264',
        'audio_codec' => 'aac',
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the video processing queue job.
    |
    */

    'processing' => [
        // Queue name for video processing jobs
        'queue' => env('VIDEO_PROCESSING_QUEUE', 'default'),

        // Maximum job timeout in seconds (10 minutes default)
        'timeout' => env('VIDEO_PROCESSING_TIMEOUT', 600),

        // Number of retry attempts
        'tries' => 3,

        // Delay between retries in seconds
        'retry_delay' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Paths for storing processed videos and thumbnails.
    |
    */

    'storage' => [
        // Base path for processed videos (relative to storage/app/public)
        'processed_path' => 'social-media/{org}/processed',

        // Base path for thumbnails (relative to storage/app/public)
        'thumbnail_path' => 'social-media/{org}/thumbnails',

        // Thumbnail image quality (1-100)
        'thumbnail_quality' => 85,

        // Thumbnail format
        'thumbnail_format' => 'jpg',
    ],
];
