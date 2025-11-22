<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * UploadController
 *
 * Handles large file uploads with progress tracking.
 * Supports chunked uploads for better UX with large files.
 *
 * Issue #74 - Add upload progress indicators
 */
class UploadController extends Controller
{
    use ApiResponse;

    /**
     * Initialize chunked upload.
     *
     * POST /api/uploads/init
     *
     * Request:
     * {
     *   "filename": "video.mp4",
     *   "filesize": 104857600,
     *   "mime_type": "video/mp4",
     *   "chunk_size": 1048576
     * }
     */
    public function initChunkedUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => 'required|string|max:255',
            'filesize' => 'required|integer|min:1|max:5368709120', // Max 5GB
            'mime_type' => 'required|string',
            'chunk_size' => 'nullable|integer|min:1024|max:10485760', // 1KB - 10MB chunks
        ]);

        $user = $request->user();
        $orgId = $user->organization->id;

        // Generate upload session ID
        $uploadId = Str::uuid()->toString();

        // Calculate number of chunks
        $chunkSize = $validated['chunk_size'] ?? 1048576; // Default 1MB
        $totalChunks = ceil($validated['filesize'] / $chunkSize);

        // Store upload metadata
        $uploadMeta = [
            'upload_id' => $uploadId,
            'user_id' => $user->id,
            'org_id' => $orgId,
            'filename' => $validated['filename'],
            'filesize' => $validated['filesize'],
            'mime_type' => $validated['mime_type'],
            'chunk_size' => $chunkSize,
            'total_chunks' => $totalChunks,
            'uploaded_chunks' => [],
            'status' => 'initialized',
            'created_at' => now()->toIso8601String(),
        ];

        Cache::put("upload:{$uploadId}", $uploadMeta, now()->addHours(24));

        return $this->success([
            'upload_id' => $uploadId,
            'total_chunks' => $totalChunks,
            'chunk_size' => $chunkSize,
        ], 'Upload session initialized');
    }

    /**
     * Upload a chunk.
     *
     * POST /api/uploads/{uploadId}/chunk
     *
     * Form data:
     * - chunk_number: 1
     * - chunk_data: (binary data)
     */
    public function uploadChunk(Request $request, string $uploadId): JsonResponse
    {
        $validated = $request->validate([
            'chunk_number' => 'required|integer|min:1',
            'chunk_data' => 'required|file',
        ]);

        // Get upload metadata
        $uploadMeta = Cache::get("upload:{$uploadId}");

        if (!$uploadMeta) {
            return $this->error('Upload session not found or expired', 404, null, 'UPLOAD_SESSION_NOT_FOUND');
        }

        // Verify chunk number is valid
        $chunkNumber = $validated['chunk_number'];
        if ($chunkNumber > $uploadMeta['total_chunks']) {
            return $this->error('Invalid chunk number', 400);
        }

        // Store chunk
        $chunkPath = "uploads/temp/{$uploadId}/chunk_{$chunkNumber}";
        $request->file('chunk_data')->storeAs(dirname($chunkPath), basename($chunkPath), 'local');

        // Update metadata
        $uploadMeta['uploaded_chunks'][] = $chunkNumber;
        $uploadMeta['uploaded_chunks'] = array_unique($uploadMeta['uploaded_chunks']);
        sort($uploadMeta['uploaded_chunks']);

        $uploadMeta['last_chunk_at'] = now()->toIso8601String();

        Cache::put("upload:{$uploadId}", $uploadMeta, now()->addHours(24));

        // Calculate progress
        $progress = (count($uploadMeta['uploaded_chunks']) / $uploadMeta['total_chunks']) * 100;
        $isComplete = count($uploadMeta['uploaded_chunks']) === $uploadMeta['total_chunks'];

        // If complete, assemble file
        $finalPath = null;
        if ($isComplete) {
            $finalPath = $this->assembleChunks($uploadId, $uploadMeta);
            $uploadMeta['status'] = 'completed';
            $uploadMeta['final_path'] = $finalPath;
            $uploadMeta['completed_at'] = now()->toIso8601String();
            Cache::put("upload:{$uploadId}", $uploadMeta, now()->addDays(7));
        }

        return $this->success([
            'upload_id' => $uploadId,
            'chunk_number' => $chunkNumber,
            'uploaded_chunks' => count($uploadMeta['uploaded_chunks']),
            'total_chunks' => $uploadMeta['total_chunks'],
            'progress_percentage' => round($progress, 2),
            'is_complete' => $isComplete,
            'final_path' => $finalPath,
        ], $isComplete ? 'Upload completed' : 'Chunk uploaded successfully');
    }

    /**
     * Get upload progress.
     *
     * GET /api/uploads/{uploadId}/progress
     */
    public function getProgress(Request $request, string $uploadId): JsonResponse
    {
        $uploadMeta = Cache::get("upload:{$uploadId}");

        if (!$uploadMeta) {
            return $this->error('Upload session not found', 404);
        }

        $progress = (count($uploadMeta['uploaded_chunks']) / $uploadMeta['total_chunks']) * 100;

        return $this->success([
            'upload_id' => $uploadId,
            'filename' => $uploadMeta['filename'],
            'filesize' => $uploadMeta['filesize'],
            'uploaded_chunks' => count($uploadMeta['uploaded_chunks']),
            'total_chunks' => $uploadMeta['total_chunks'],
            'progress_percentage' => round($progress, 2),
            'status' => $uploadMeta['status'],
            'bytes_uploaded' => count($uploadMeta['uploaded_chunks']) * $uploadMeta['chunk_size'],
            'estimated_remaining_time' => $this->estimateRemainingTime($uploadMeta),
        ]);
    }

    /**
     * Cancel an upload.
     *
     * DELETE /api/uploads/{uploadId}
     */
    public function cancelUpload(string $uploadId): JsonResponse
    {
        $uploadMeta = Cache::get("upload:{$uploadId}");

        if (!$uploadMeta) {
            return $this->error('Upload session not found', 404);
        }

        // Delete chunks
        Storage::disk('local')->deleteDirectory("uploads/temp/{$uploadId}");

        // Remove metadata
        Cache::forget("upload:{$uploadId}");

        return $this->success(null, 'Upload cancelled successfully');
    }

    /**
     * Assemble chunks into final file.
     */
    protected function assembleChunks(string $uploadId, array $uploadMeta): string
    {
        $finalPath = "uploads/{$uploadMeta['org_id']}/{$uploadId}_{$uploadMeta['filename']}";

        // Create final file
        $finalFile = Storage::disk('local')->path($finalPath);
        $handle = fopen($finalFile, 'wb');

        // Append each chunk
        for ($i = 1; $i <= $uploadMeta['total_chunks']; $i++) {
            $chunkPath = Storage::disk('local')->path("uploads/temp/{$uploadId}/chunk_{$i}");
            if (file_exists($chunkPath)) {
                $chunkHandle = fopen($chunkPath, 'rb');
                stream_copy_to_stream($chunkHandle, $handle);
                fclose($chunkHandle);
            }
        }

        fclose($handle);

        // Clean up chunks
        Storage::disk('local')->deleteDirectory("uploads/temp/{$uploadId}");

        return $finalPath;
    }

    /**
     * Estimate remaining upload time.
     */
    protected function estimateRemainingTime(array $uploadMeta): ?int
    {
        if (empty($uploadMeta['uploaded_chunks']) || !isset($uploadMeta['created_at'])) {
            return null;
        }

        $elapsedSeconds = now()->diffInSeconds($uploadMeta['created_at']);
        $uploadedChunks = count($uploadMeta['uploaded_chunks']);
        $remainingChunks = $uploadMeta['total_chunks'] - $uploadedChunks;

        if ($uploadedChunks === 0) {
            return null;
        }

        $secondsPerChunk = $elapsedSeconds / $uploadedChunks;
        $estimatedRemaining = (int) ($secondsPerChunk * $remainingChunks);

        return $estimatedRemaining;
    }
}
