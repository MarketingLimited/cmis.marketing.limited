<?php

namespace App\Apps\Backup\Services\Extraction;

use App\Apps\Backup\Services\Discovery\FileDiscoveryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * File Collector Service
 *
 * Collects files referenced in database records for inclusion in backups.
 * Handles both local files and remote URLs, downloading as needed.
 */
class FileCollectorService
{
    protected FileDiscoveryService $fileDiscovery;

    /**
     * Temporary storage path for downloaded files
     */
    protected string $tempPath;

    /**
     * Maximum file size to download (bytes)
     */
    protected int $maxFileSize;

    /**
     * HTTP timeout for downloads (seconds)
     */
    protected int $downloadTimeout;

    public function __construct(FileDiscoveryService $fileDiscovery)
    {
        $this->fileDiscovery = $fileDiscovery;
        $this->tempPath = config('backup.storage.temp_path', storage_path('app/temp/backups'));
        $this->maxFileSize = 100 * 1024 * 1024; // 100MB default
        $this->downloadTimeout = 60;
    }

    /**
     * Collect all files for an organization
     *
     * @param string $orgId Organization ID
     * @param array $extractedData Extracted database data
     * @param callable|null $progressCallback Progress callback (path, size)
     * @return Collection Collected files with metadata
     */
    public function collectFiles(
        string $orgId,
        array $extractedData,
        ?callable $progressCallback = null
    ): Collection {
        $files = collect();
        $processedPaths = [];

        foreach ($extractedData as $categoryKey => $categoryInfo) {
            $categoryData = $categoryInfo['data'] ?? [];

            foreach ($categoryData as $tableName => $records) {
                foreach ($records as $record) {
                    $sourcePaths = $this->extractPathsFromRecord($record);

                    foreach ($sourcePaths as $path) {
                        // Skip already processed paths
                        if (in_array($path, $processedPaths)) {
                            continue;
                        }
                        $processedPaths[] = $path;

                        $fileInfo = $this->collectFile($path);

                        if ($fileInfo) {
                            $fileInfo['category'] = $categoryKey;
                            $fileInfo['source_table'] = $tableName;
                            $files->push($fileInfo);

                            if ($progressCallback) {
                                $progressCallback($path, $fileInfo['size'] ?? 0);
                            }
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Extract file paths from a record
     *
     * @param array $record Database record
     * @return array File paths found
     */
    protected function extractPathsFromRecord(array $record): array
    {
        $paths = [];

        foreach ($record as $key => $value) {
            if (is_string($value) && $this->looksLikeFilePath($value)) {
                $paths[] = $value;
            } elseif (is_array($value)) {
                $paths = array_merge($paths, $this->extractPathsRecursive($value));
            }
        }

        return array_unique($paths);
    }

    /**
     * Recursively extract paths from nested array
     *
     * @param array $data Data to search
     * @return array File paths found
     */
    protected function extractPathsRecursive(array $data): array
    {
        $paths = [];

        foreach ($data as $key => $value) {
            if (is_string($value) && $this->looksLikeFilePath($value)) {
                $paths[] = $value;
            } elseif (is_array($value)) {
                $paths = array_merge($paths, $this->extractPathsRecursive($value));
            }
        }

        return $paths;
    }

    /**
     * Check if a value looks like a file path
     *
     * @param string $value Value to check
     * @return bool
     */
    protected function looksLikeFilePath(string $value): bool
    {
        // URL check
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->hasFileExtension($value);
        }

        // Local path check
        if (str_starts_with($value, '/') || str_starts_with($value, 'storage/')) {
            return $this->hasFileExtension($value);
        }

        return false;
    }

    /**
     * Check if a path has a recognizable file extension
     *
     * @param string $path Path to check
     * @return bool
     */
    protected function hasFileExtension(string $path): bool
    {
        $extensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp',
            'mp4', 'webm', 'mov', 'avi', 'mkv',
            'mp3', 'wav', 'ogg', 'aac',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', '7z', 'tar', 'gz',
            'json', 'xml', 'csv', 'txt',
        ];

        $pathWithoutQuery = parse_url($path, PHP_URL_PATH) ?? $path;
        $extension = strtolower(pathinfo($pathWithoutQuery, PATHINFO_EXTENSION));

        return in_array($extension, $extensions);
    }

    /**
     * Collect a single file
     *
     * @param string $path File path or URL
     * @return array|null File info or null if collection failed
     */
    public function collectFile(string $path): ?array
    {
        $isUrl = filter_var($path, FILTER_VALIDATE_URL);

        if ($isUrl) {
            return $this->collectRemoteFile($path);
        }

        return $this->collectLocalFile($path);
    }

    /**
     * Collect a local file
     *
     * @param string $path Local file path
     * @return array|null
     */
    protected function collectLocalFile(string $path): ?array
    {
        // Normalize path
        $normalizedPath = ltrim($path, '/');

        // Try multiple storage disks
        $disks = ['public', 'local'];

        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($normalizedPath)) {
                return [
                    'original_path' => $path,
                    'type' => 'local',
                    'disk' => $disk,
                    'relative_path' => $normalizedPath,
                    'size' => Storage::disk($disk)->size($normalizedPath),
                    'mime_type' => Storage::disk($disk)->mimeType($normalizedPath),
                    'last_modified' => Storage::disk($disk)->lastModified($normalizedPath),
                    'full_path' => Storage::disk($disk)->path($normalizedPath),
                ];
            }
        }

        // File not found
        return [
            'original_path' => $path,
            'type' => 'local',
            'disk' => null,
            'relative_path' => $normalizedPath,
            'size' => 0,
            'error' => 'File not found',
            'exists' => false,
        ];
    }

    /**
     * Collect a remote file (download to temp)
     *
     * @param string $url Remote URL
     * @return array|null
     */
    protected function collectRemoteFile(string $url): ?array
    {
        try {
            // First, get file info with HEAD request
            $response = Http::timeout(10)->head($url);

            if (!$response->successful()) {
                return [
                    'original_path' => $url,
                    'type' => 'remote',
                    'error' => "HTTP {$response->status()}",
                    'exists' => false,
                ];
            }

            $contentLength = (int) $response->header('Content-Length', 0);
            $contentType = $response->header('Content-Type', 'application/octet-stream');

            // Check file size limit
            if ($contentLength > $this->maxFileSize) {
                return [
                    'original_path' => $url,
                    'type' => 'remote',
                    'size' => $contentLength,
                    'error' => 'File too large',
                    'exists' => true,
                    'skipped' => true,
                ];
            }

            // Generate temp path
            $extension = $this->getExtensionFromUrl($url) ?? $this->getExtensionFromMime($contentType);
            $tempFileName = Str::uuid() . '.' . $extension;
            $tempPath = $this->getTempPath($tempFileName);

            // Download the file
            $downloadResponse = Http::timeout($this->downloadTimeout)->get($url);

            if ($downloadResponse->successful()) {
                // Ensure temp directory exists
                if (!is_dir(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0755, true);
                }

                file_put_contents($tempPath, $downloadResponse->body());

                return [
                    'original_path' => $url,
                    'type' => 'remote',
                    'relative_path' => 'remote/' . $tempFileName,
                    'temp_path' => $tempPath,
                    'size' => filesize($tempPath),
                    'mime_type' => $contentType,
                    'downloaded' => true,
                ];
            }

            return [
                'original_path' => $url,
                'type' => 'remote',
                'error' => 'Download failed',
                'exists' => true,
            ];

        } catch (\Exception $e) {
            return [
                'original_path' => $url,
                'type' => 'remote',
                'error' => $e->getMessage(),
                'exists' => false,
            ];
        }
    }

    /**
     * Get file extension from URL
     *
     * @param string $url URL
     * @return string|null
     */
    protected function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $extension ?: null;
    }

    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType MIME type
     * @return string
     */
    protected function getExtensionFromMime(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'application/pdf' => 'pdf',
            'application/json' => 'json',
            'text/plain' => 'txt',
        ];

        $baseMime = explode(';', $mimeType)[0];
        return $mimeMap[$baseMime] ?? 'bin';
    }

    /**
     * Get temp path for a file
     *
     * @param string $fileName File name
     * @return string Full temp path
     */
    protected function getTempPath(string $fileName): string
    {
        return rtrim($this->tempPath, '/') . '/' . $fileName;
    }

    /**
     * Clean up temporary files
     *
     * @param Collection $files Collected files
     */
    public function cleanupTempFiles(Collection $files): void
    {
        foreach ($files as $file) {
            if (isset($file['temp_path']) && file_exists($file['temp_path'])) {
                unlink($file['temp_path']);
            }
        }
    }

    /**
     * Get total size of collected files
     *
     * @param Collection $files Collected files
     * @return int Total size in bytes
     */
    public function getTotalSize(Collection $files): int
    {
        return $files->sum('size');
    }

    /**
     * Filter files by type
     *
     * @param Collection $files Collected files
     * @param string $type File type (local, remote)
     * @return Collection
     */
    public function filterByType(Collection $files, string $type): Collection
    {
        return $files->where('type', $type);
    }

    /**
     * Get files that failed to collect
     *
     * @param Collection $files Collected files
     * @return Collection
     */
    public function getFailedFiles(Collection $files): Collection
    {
        return $files->filter(fn($file) => isset($file['error']));
    }

    /**
     * Create a manifest of collected files
     *
     * @param Collection $files Collected files
     * @return array File manifest
     */
    public function createManifest(Collection $files): array
    {
        return [
            'file_count' => $files->count(),
            'total_size' => $this->getTotalSize($files),
            'local_files' => $this->filterByType($files, 'local')->count(),
            'remote_files' => $this->filterByType($files, 'remote')->count(),
            'failed_files' => $this->getFailedFiles($files)->count(),
            'files' => $files->map(function ($file) {
                return [
                    'path' => $file['original_path'],
                    'type' => $file['type'],
                    'size' => $file['size'] ?? 0,
                    'relative_path' => $file['relative_path'] ?? null,
                    'error' => $file['error'] ?? null,
                ];
            })->toArray(),
        ];
    }
}
