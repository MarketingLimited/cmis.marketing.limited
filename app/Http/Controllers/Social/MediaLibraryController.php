<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Jobs\ProcessVideoJob;
use App\Models\Social\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        try {
            // Set RLS context for multi-tenancy
            $userId = auth()->id();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$userId, $org]);

            // Build query with filters
            $query = MediaAsset::where('org_id', $org)
                ->whereNull('deleted_at');

            // Filter by media type if specified
            if ($request->has('type') && in_array($request->type, ['image', 'video'])) {
                $query->where('media_type', $request->type);
            }

            // Filter by analysis status if specified
            if ($request->has('analyzed')) {
                if ($request->analyzed === 'true' || $request->analyzed === '1') {
                    $query->where('is_analyzed', true);
                } elseif ($request->analyzed === 'false' || $request->analyzed === '0') {
                    $query->where('is_analyzed', false);
                }
            }

            // Search by filename
            if ($request->has('search') && $request->search) {
                $query->where('file_name', 'ilike', '%' . $request->search . '%');
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = min((int) ($request->per_page ?? 24), 100);
            $assets = $query->paginate($perPage);

            // Transform for frontend consumption
            $files = $assets->map(function ($asset) {
                // Generate thumbnail URL for images
                $thumbnailUrl = $asset->original_url;
                if ($asset->media_type === 'video' && !empty($asset->metadata['thumbnail_url'])) {
                    $thumbnailUrl = $asset->metadata['thumbnail_url'];
                }

                return [
                    'id' => $asset->asset_id,
                    'url' => $asset->original_url,
                    'thumbnail_url' => $thumbnailUrl,
                    'type' => $asset->media_type,
                    'file_name' => $asset->file_name,
                    'file_size' => $asset->file_size,
                    'file_size_human' => $asset->getFileSizeHuman(),
                    'width' => $asset->width,
                    'height' => $asset->height,
                    'aspect_ratio' => $asset->aspect_ratio,
                    'aspect_ratio_label' => $asset->getAspectRatioLabel(),
                    'duration_seconds' => $asset->duration_seconds,
                    'is_analyzed' => $asset->is_analyzed,
                    'analysis_status' => $asset->analysis_status,
                    'created_at' => $asset->created_at?->toIso8601String(),
                    'post_id' => $asset->post_id,
                ];
            });

            return $this->success([
                'files' => $files,
                'total' => $assets->total(),
                'per_page' => $assets->perPage(),
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
            ], 'Media library retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch media library', [
                'org_id' => $org,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError('Failed to fetch media library: ' . $e->getMessage());
        }
    }

    /**
     * Delete a media asset
     *
     * @param Request $request
     * @param string $org
     * @param string $assetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $org, string $assetId)
    {
        try {
            // Set RLS context for multi-tenancy
            $userId = auth()->id();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$userId, $org]);

            $asset = MediaAsset::where('org_id', $org)
                ->where('asset_id', $assetId)
                ->first();

            if (!$asset) {
                return $this->notFound('Media asset not found');
            }

            // Delete from storage if exists
            if ($asset->storage_path && Storage::disk('public')->exists($asset->storage_path)) {
                Storage::disk('public')->delete($asset->storage_path);
            }

            // Soft delete the record
            $asset->delete();

            Log::info('Media asset deleted', [
                'asset_id' => $assetId,
                'org_id' => $org,
            ]);

            return $this->deleted('Media asset deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete media asset', [
                'asset_id' => $assetId,
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to delete media asset: ' . $e->getMessage());
        }
    }

    /**
     * Get a single media asset details
     *
     * @param Request $request
     * @param string $org
     * @param string $assetId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $org, string $assetId)
    {
        try {
            // Set RLS context for multi-tenancy
            $userId = auth()->id();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$userId, $org]);

            $asset = MediaAsset::where('org_id', $org)
                ->where('asset_id', $assetId)
                ->first();

            if (!$asset) {
                return $this->notFound('Media asset not found');
            }

            return $this->success([
                'id' => $asset->asset_id,
                'url' => $asset->original_url,
                'storage_path' => $asset->storage_path,
                'type' => $asset->media_type,
                'file_name' => $asset->file_name,
                'file_size' => $asset->file_size,
                'file_size_human' => $asset->getFileSizeHuman(),
                'mime_type' => $asset->mime_type,
                'width' => $asset->width,
                'height' => $asset->height,
                'aspect_ratio' => $asset->aspect_ratio,
                'aspect_ratio_label' => $asset->getAspectRatioLabel(),
                'duration_seconds' => $asset->duration_seconds,
                'is_analyzed' => $asset->is_analyzed,
                'analysis_status' => $asset->analysis_status,
                'analyzed_at' => $asset->analyzed_at?->toIso8601String(),
                'visual_caption' => $asset->visual_caption,
                'color_palette' => $asset->color_palette,
                'typography' => $asset->typography,
                'detected_objects' => $asset->detected_objects,
                'extracted_text' => $asset->extracted_text,
                'created_at' => $asset->created_at?->toIso8601String(),
                'updated_at' => $asset->updated_at?->toIso8601String(),
                'post_id' => $asset->post_id,
            ], 'Media asset retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch media asset', [
                'asset_id' => $assetId,
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Failed to fetch media asset: ' . $e->getMessage());
        }
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
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,wmv,webm,mkv|max:512000', // 500MB max for videos
            'type' => 'nullable|in:image,video',
        ]);

        try {
            // Set RLS context for multi-tenancy (requires user_id and org_id)
            $userId = auth()->id();
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [$userId, $org]);

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

                // Generate public URL for original
                $url = Storage::disk('public')->url($path);
                if (!str_starts_with($url, 'http')) {
                    $url = url($url);
                }

                // Create MediaAsset record for tracking processing
                $asset = MediaAsset::create([
                    'org_id' => $org,
                    'post_id' => null, // Will be linked when post is created
                    'media_type' => 'video',
                    'original_url' => $url,
                    'storage_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'file_size' => $file->getSize(),
                    'is_analyzed' => false,
                    'analysis_status' => 'pending',
                    'metadata' => [
                        'original_extension' => $extension,
                        'uploaded_at' => now()->toIso8601String(),
                    ],
                ]);

                // Dispatch background job to process video (include user_id for RLS context)
                ProcessVideoJob::dispatch(
                    $asset->asset_id,
                    $org,
                    $path,
                    $userId
                )->onQueue(config('media.processing.queue', 'default'));

                Log::info('Video upload queued for processing', [
                    'asset_id' => $asset->asset_id,
                    'org_id' => $org,
                    'path' => $path,
                ]);

                return $this->success([
                    'url' => $url,
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => 'video',
                    'asset_id' => $asset->asset_id,
                    'needs_processing' => true,
                    'processing_status' => 'pending',
                ], 'Video uploaded and queued for processing');

            } else {
                // For images, process and convert to JPEG
                $filename = $org . '/' . $uuid . '.jpg';
                $path = $this->processAndStoreImage($file, $filename);

                // Generate public URL
                $url = Storage::disk('public')->url($path);
                if (!str_starts_with($url, 'http')) {
                    $url = url($url);
                }

                return $this->success([
                    'url' => $url,
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                    'size' => Storage::disk('public')->size($path),
                    'type' => 'image',
                    'needs_processing' => false,
                ], 'Image uploaded successfully');
            }
        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'org_id' => $org,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
