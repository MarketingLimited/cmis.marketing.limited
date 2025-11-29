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
            $type = $request->input('type', $file->getMimeType());

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $org . '/' . Str::uuid() . '.' . $extension;

            // Store file in public disk
            $path = $file->storeAs('social-media', $filename, 'public');

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
                'size' => $file->getSize(),
                'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image',
            ], 'Media uploaded successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to upload media: ' . $e->getMessage());
        }
    }
}
