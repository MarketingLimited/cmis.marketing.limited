<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    use ApiResponse;

    /**
     * Get media files for the media library
     *
     * @param Request $request
     * @param string $org
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $org)
    {
        // TODO: Implement media library functionality
        // For now, return empty array to prevent errors
        return $this->success([
            'files' => [],
            'total' => 0,
            'message' => 'Media library feature coming soon'
        ], 'Media library retrieved successfully');
    }

    /**
     * Upload a media file to the server
     *
     * @param Request $request
     * @param string $org
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request, string $org)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,wmv|max:102400', // 100MB max
            'type' => 'nullable|in:image,video',
        ]);

        try {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();
            $isVideo = str_starts_with($mimeType, 'video');

            // Generate unique filename
            $uuid = Str::uuid();

            if ($isVideo) {
                // For videos, keep original extension
                $extension = strtolower($file->getClientOriginalExtension());
                $filename = $org . '/' . $uuid . '.' . $extension;

                // Store video file directly
                $path = $file->storeAs('social-media', $filename, 'public');
            } else {
                // For images, process and convert to JPEG
                $filename = $org . '/' . $uuid . '.jpg';
                $path = $this->processAndStoreImage($file, $filename);
            }

            // Generate public URL
            $url = Storage::disk('public')->url($path);

            // If URL is relative, make it absolute
            if (!str_starts_with($url, 'http')) {
                $url = url($url);
            }

            return $this->success([
                'url' => $url,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'size' => Storage::disk('public')->size($path),
                'type' => $isVideo ? 'video' : 'image',
            ], 'Media uploaded successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to upload media: ' . $e->getMessage());
        }
    }

    /**
     * Process image and convert to JPEG format
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $filename
     * @return string The stored file path
     */
    protected function processAndStoreImage($file, string $filename): string
    {
        // Load image based on mime type
        $mimeType = $file->getMimeType();

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($file->getPathname());
                break;
            case 'image/png':
                $image = imagecreatefrompng($file->getPathname());
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file->getPathname());
                break;
            default:
                // Fallback: try to detect from file
                $image = imagecreatefromstring(file_get_contents($file->getPathname()));
                break;
        }

        if (!$image) {
            throw new \Exception('Failed to process image');
        }

        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Max dimensions for social media (Instagram: 1080px, Facebook: 2048px)
        // We'll use 2048px as max to support all platforms
        $maxDimension = 2048;

        // Calculate new dimensions if image is too large
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int) ($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) ($width * ($maxDimension / $height));
            }

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG/GIF (will be converted to white background in JPEG)
            $white = imagecolorallocate($resizedImage, 255, 255, 255);
            imagefill($resizedImage, 0, 0, $white);

            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resizedImage;
        } else {
            // If not resizing, still need to handle transparency for PNG/GIF
            if (in_array($mimeType, ['image/png', 'image/gif'])) {
                $newImage = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($newImage, 255, 255, 255);
                imagefill($newImage, 0, 0, $white);
                imagecopy($newImage, $image, 0, 0, 0, 0, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }
        }

        // Create temporary file for JPEG
        $tempPath = tempnam(sys_get_temp_dir(), 'img_');

        // Save as JPEG with 85% quality (good balance for social media)
        imagejpeg($image, $tempPath, 85);
        imagedestroy($image);

        // Store the processed image
        $storedPath = Storage::disk('public')->putFileAs(
            'social-media',
            new \Illuminate\Http\File($tempPath),
            $filename
        );

        // Clean up temp file
        @unlink($tempPath);

        return $storedPath;
    }
}
