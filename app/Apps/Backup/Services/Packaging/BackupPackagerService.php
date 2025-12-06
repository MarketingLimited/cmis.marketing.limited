<?php

namespace App\Apps\Backup\Services\Packaging;

use App\Apps\Backup\Services\Export\ExportMapperService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Backup Packager Service
 *
 * Creates backup packages (ZIP files) containing:
 * - Extracted data as JSON files organized by category
 * - Organization files (images, documents, media)
 * - Manifest with metadata and checksums
 * - Schema snapshot for restore compatibility
 */
class BackupPackagerService
{
    protected ExportMapperService $exportMapper;
    protected ChecksumService $checksumService;

    /**
     * Temporary storage path
     */
    protected string $tempPath;

    /**
     * Compression level (0-9)
     */
    protected int $compressionLevel;

    public function __construct(
        ExportMapperService $exportMapper,
        ChecksumService $checksumService
    ) {
        $this->exportMapper = $exportMapper;
        $this->checksumService = $checksumService;
        $this->tempPath = config('backup.storage.temp_path', storage_path('app/temp/backups'));
        $this->compressionLevel = config('backup.packaging.compression_level', 6);
    }

    /**
     * Create a complete backup package
     *
     * @param string $orgId Organization ID
     * @param array $extractedData Extracted database data by category
     * @param Collection $files Collected files
     * @param array|null $schemaSnapshot Database schema snapshot (optional, defaults to null for security)
     * @param array $metadata Additional metadata
     * @param bool $includeSchema Whether to include schema snapshot (default: false for security)
     * @return array Package info with path, size, checksums
     */
    public function createPackage(
        string $orgId,
        array $extractedData,
        Collection $files,
        ?array $schemaSnapshot = null,
        array $metadata = [],
        bool $includeSchema = false
    ): array {
        // Ensure temp directory exists
        $this->ensureTempDirectory();

        // Generate unique package name
        $packageId = Str::uuid()->toString();
        $packageName = "backup_{$orgId}_{$packageId}.zip";
        $packagePath = $this->getTempPath($packageName);

        // Create ZIP archive
        $zip = new ZipArchive();
        $result = $zip->open($packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException("Failed to create ZIP archive: error code {$result}");
        }

        // Set compression level
        $zip->setCompressionIndex(0, ZipArchive::CM_DEFLATE);

        // Track file checksums for manifest
        $fileChecksums = [];

        // Add data files by category
        foreach ($extractedData as $categoryKey => $categoryInfo) {
            $categoryData = $categoryInfo['data'] ?? [];

            // Create category JSON file
            $jsonContent = json_encode($categoryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $jsonPath = "data/{$categoryKey}.json";

            $zip->addFromString($jsonPath, $jsonContent);
            $fileChecksums[$jsonPath] = $this->checksumService->hashString($jsonContent);
        }

        // Add organization files
        $addedFiles = $this->addFilesToZip($zip, $files, $fileChecksums);

        // Add schema snapshot only if explicitly requested (disabled by default for security)
        if ($includeSchema && $schemaSnapshot !== null) {
            // Remove sensitive info from schema (database name, etc.)
            $safeSchema = $this->sanitizeSchemaSnapshot($schemaSnapshot);
            $schemaContent = json_encode($safeSchema, JSON_PRETTY_PRINT);
            $zip->addFromString('schema/snapshot.json', $schemaContent);
            $fileChecksums['schema/snapshot.json'] = $this->checksumService->hashString($schemaContent);
        }

        // Build and add manifest
        $manifest = $this->buildManifest(
            $orgId,
            $extractedData,
            $addedFiles,
            $includeSchema ? $schemaSnapshot : null,
            $metadata,
            $fileChecksums
        );

        $manifestContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('manifest.json', $manifestContent);

        // Close the archive
        $zip->close();

        // Calculate package checksum
        $packageChecksum = $this->checksumService->hashFile($packagePath);
        $packageSize = filesize($packagePath);

        return [
            'path' => $packagePath,
            'filename' => $packageName,
            'size' => $packageSize,
            'checksum' => $packageChecksum,
            'manifest' => $manifest,
            'file_count' => count($fileChecksums),
        ];
    }

    /**
     * Add collected files to ZIP archive
     *
     * @param ZipArchive $zip ZIP archive
     * @param Collection $files Collected files
     * @param array $checksums Checksums array (by reference)
     * @return array Added file info
     */
    protected function addFilesToZip(ZipArchive $zip, Collection $files, array &$checksums): array
    {
        $addedFiles = [];

        foreach ($files as $file) {
            // Skip files with errors
            if (isset($file['error'])) {
                $addedFiles[] = [
                    'original_path' => $file['original_path'],
                    'status' => 'skipped',
                    'error' => $file['error'],
                ];
                continue;
            }

            // Determine source path
            $sourcePath = $file['temp_path'] ?? $file['full_path'] ?? null;

            if (!$sourcePath || !file_exists($sourcePath)) {
                $addedFiles[] = [
                    'original_path' => $file['original_path'],
                    'status' => 'skipped',
                    'error' => 'Source file not found',
                ];
                continue;
            }

            // Determine archive path
            $archivePath = 'files/' . ($file['relative_path'] ?? basename($sourcePath));

            // Add to ZIP
            $zip->addFile($sourcePath, $archivePath);

            // Calculate checksum
            $checksums[$archivePath] = $this->checksumService->hashFile($sourcePath);

            $addedFiles[] = [
                'original_path' => $file['original_path'],
                'archive_path' => $archivePath,
                'size' => $file['size'] ?? filesize($sourcePath),
                'mime_type' => $file['mime_type'] ?? null,
                'status' => 'added',
            ];
        }

        return $addedFiles;
    }

    /**
     * Build backup manifest
     *
     * @param string $orgId Organization ID
     * @param array $extractedData Extracted data
     * @param array $files Added files info
     * @param array|null $schemaSnapshot Schema snapshot (null if not included)
     * @param array $metadata Additional metadata
     * @param array $checksums File checksums
     * @return array Manifest data
     */
    protected function buildManifest(
        string $orgId,
        array $extractedData,
        array $files,
        ?array $schemaSnapshot,
        array $metadata,
        array $checksums
    ): array {
        // Calculate statistics
        $totalRecords = 0;
        $categories = [];

        foreach ($extractedData as $categoryKey => $categoryInfo) {
            $recordCount = $categoryInfo['record_count'] ?? 0;
            $totalRecords += $recordCount;

            $categories[$categoryKey] = [
                'label' => $categoryInfo['label'] ?? $this->exportMapper->getCategoryLabel($categoryKey),
                'table_count' => $categoryInfo['table_count'] ?? 0,
                'record_count' => $recordCount,
            ];
        }

        // File statistics
        $addedFiles = collect($files)->where('status', 'added');
        $skippedFiles = collect($files)->where('status', 'skipped');

        $manifest = [
            'version' => config('backup.version', '1.0.0'),
            'format' => 'cmis-backup',
            'created_at' => now()->toISOString(),
            'organization' => [
                'id' => $orgId,
                // Note: Organization name/details intentionally not included for privacy
            ],
            'summary' => [
                'categories' => $categories,
                'total_records' => $totalRecords,
                'total_tables' => array_sum(array_column($categories, 'table_count')),
                'files' => [
                    'total' => count($files),
                    'added' => $addedFiles->count(),
                    'skipped' => $skippedFiles->count(),
                    'total_size' => $addedFiles->sum('size'),
                ],
            ],
            'files' => $files,
            'checksums' => $checksums,
            'metadata' => array_merge($metadata, [
                'generator' => 'CMIS Backup System',
                'generator_version' => config('backup.version', '1.0.0'),
            ]),
        ];

        // Only include schema info if schema was included in backup
        if ($schemaSnapshot !== null) {
            $manifest['schema'] = [
                'cmis_version' => config('cmis.version', '3.0'),
                'table_count' => count($schemaSnapshot['tables'] ?? []),
            ];
        }

        return $manifest;
    }

    /**
     * Extract and validate a backup package
     *
     * @param string $packagePath Path to ZIP file
     * @param string|null $extractTo Optional extraction path
     * @return array Extraction result with manifest
     */
    public function extractPackage(string $packagePath, ?string $extractTo = null): array
    {
        if (!file_exists($packagePath)) {
            throw new \RuntimeException("Package file not found: {$packagePath}");
        }

        // Verify package checksum if provided
        $zip = new ZipArchive();
        $result = $zip->open($packagePath, ZipArchive::RDONLY);

        if ($result !== true) {
            throw new \RuntimeException("Failed to open ZIP archive: error code {$result}");
        }

        // Extract manifest first
        $manifestContent = $zip->getFromName('manifest.json');
        if ($manifestContent === false) {
            $zip->close();
            throw new \RuntimeException("Invalid backup package: manifest.json not found");
        }

        $manifest = json_decode($manifestContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $zip->close();
            throw new \RuntimeException("Invalid manifest.json: " . json_last_error_msg());
        }

        // Validate format
        if (($manifest['format'] ?? null) !== 'cmis-backup') {
            $zip->close();
            throw new \RuntimeException("Invalid backup format: expected 'cmis-backup'");
        }

        // Extract if path provided
        $extractedFiles = [];
        if ($extractTo) {
            $this->ensureDirectory($extractTo);
            $zip->extractTo($extractTo);

            // Verify checksums
            $checksums = $manifest['checksums'] ?? [];
            $verificationErrors = [];

            foreach ($checksums as $file => $expectedChecksum) {
                $filePath = $extractTo . '/' . $file;
                if (file_exists($filePath)) {
                    $actualChecksum = $this->checksumService->hashFile($filePath);
                    if ($actualChecksum !== $expectedChecksum) {
                        $verificationErrors[] = [
                            'file' => $file,
                            'expected' => $expectedChecksum,
                            'actual' => $actualChecksum,
                        ];
                    }
                    $extractedFiles[] = $filePath;
                }
            }

            if (!empty($verificationErrors)) {
                return [
                    'success' => false,
                    'manifest' => $manifest,
                    'extracted_to' => $extractTo,
                    'verification_errors' => $verificationErrors,
                ];
            }
        }

        $zip->close();

        return [
            'success' => true,
            'manifest' => $manifest,
            'extracted_to' => $extractTo,
            'extracted_files' => $extractedFiles,
            'package_checksum' => $this->checksumService->hashFile($packagePath),
        ];
    }

    /**
     * Get package info without extraction
     *
     * @param string $packagePath Path to ZIP file
     * @return array Package info
     */
    public function getPackageInfo(string $packagePath): array
    {
        $zip = new ZipArchive();
        $result = $zip->open($packagePath, ZipArchive::RDONLY);

        if ($result !== true) {
            throw new \RuntimeException("Failed to open ZIP archive: error code {$result}");
        }

        $manifestContent = $zip->getFromName('manifest.json');
        $manifest = $manifestContent ? json_decode($manifestContent, true) : null;

        $info = [
            'path' => $packagePath,
            'size' => filesize($packagePath),
            'file_count' => $zip->numFiles,
            'manifest' => $manifest,
            'checksum' => $this->checksumService->hashFile($packagePath),
        ];

        $zip->close();

        return $info;
    }

    /**
     * Get temp path for a file
     *
     * @param string $filename Filename
     * @return string Full path
     */
    protected function getTempPath(string $filename): string
    {
        return rtrim($this->tempPath, '/') . '/' . $filename;
    }

    /**
     * Ensure temp directory exists
     */
    protected function ensureTempDirectory(): void
    {
        $this->ensureDirectory($this->tempPath);
    }

    /**
     * Ensure a directory exists
     *
     * @param string $path Directory path
     */
    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Sanitize schema snapshot to remove sensitive information
     *
     * @param array $schemaSnapshot Original schema snapshot
     * @return array Sanitized schema snapshot
     */
    protected function sanitizeSchemaSnapshot(array $schemaSnapshot): array
    {
        // Remove database name and other sensitive system info
        unset($schemaSnapshot['database']);
        unset($schemaSnapshot['host']);
        unset($schemaSnapshot['port']);

        // Keep only structural information needed for restore compatibility
        return [
            'version' => $schemaSnapshot['version'] ?? '1.0',
            'created_at' => $schemaSnapshot['created_at'] ?? now()->toISOString(),
            'tables' => $schemaSnapshot['tables'] ?? [],
        ];
    }

    /**
     * Clean up temporary files older than specified hours
     *
     * @param int $olderThanHours Hours threshold
     * @return int Number of files cleaned
     */
    public function cleanupTempFiles(int $olderThanHours = 24): int
    {
        $cleaned = 0;
        $threshold = now()->subHours($olderThanHours)->timestamp;

        $files = glob($this->tempPath . '/*.zip');
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Move package to permanent storage
     *
     * @param string $tempPath Temporary package path
     * @param string $disk Storage disk
     * @param string $destination Destination path
     * @return string Final storage path
     */
    public function moveToStorage(string $tempPath, string $disk, string $destination): string
    {
        $content = file_get_contents($tempPath);
        Storage::disk($disk)->put($destination, $content);

        // Clean up temp file
        unlink($tempPath);

        return $destination;
    }
}
