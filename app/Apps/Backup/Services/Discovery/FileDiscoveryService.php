<?php

namespace App\Apps\Backup\Services\Discovery;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * File Discovery Service
 *
 * Discovers database columns that contain file paths/URLs.
 * Used to identify which files need to be included in backups.
 */
class FileDiscoveryService
{
    /**
     * Cache TTL for file column discovery (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Patterns to detect file columns
     */
    protected array $fileColumnPatterns;

    /**
     * Schemas to scan
     */
    protected array $schemas;

    public function __construct()
    {
        $this->fileColumnPatterns = config('backup.extraction.file_column_patterns', [
            'file_path',
            'file_url',
            'image_url',
            'media_url',
            'attachment',
            'thumbnail',
            'avatar',
            'logo',
            'document',
            'asset_url',
            'video_url',
            'audio_url',
            'cover_image',
            'profile_image',
            'banner_image',
        ]);

        $this->schemas = config('backup.discovery.schemas', [
            'cmis',
            'cmis_creative',
            'cmis_platform',
        ]);
    }

    /**
     * Discover all columns that likely contain file paths
     *
     * @return Collection Collection of file columns with table info
     */
    public function discoverFileColumns(): Collection
    {
        $cacheKey = 'backup:file_columns:' . md5(implode(',', $this->schemas));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $conditions = collect($this->fileColumnPatterns)
                ->map(fn($pattern) => "column_name ILIKE '%{$pattern}%'")
                ->implode(' OR ');

            return DB::table('information_schema.columns')
                ->whereIn('table_schema', $this->schemas)
                ->whereRaw("({$conditions})")
                ->where(function ($query) {
                    // Only text-based columns can contain file paths
                    $query->where('data_type', 'character varying')
                        ->orWhere('data_type', 'text')
                        ->orWhere('data_type', 'jsonb')
                        ->orWhere('data_type', 'json');
                })
                ->select([
                    'table_schema',
                    'table_name',
                    'column_name',
                    'data_type',
                ])
                ->get()
                ->map(function ($row) {
                    return [
                        'table' => "{$row->table_schema}.{$row->table_name}",
                        'column' => $row->column_name,
                        'data_type' => $row->data_type,
                        'is_json' => in_array($row->data_type, ['jsonb', 'json']),
                    ];
                });
        });
    }

    /**
     * Get file columns grouped by table
     *
     * @return Collection Tables with their file columns
     */
    public function getFileColumnsByTable(): Collection
    {
        return $this->discoverFileColumns()
            ->groupBy('table')
            ->map(function ($columns, $table) {
                return [
                    'table' => $table,
                    'columns' => $columns->pluck('column')->toArray(),
                    'has_json_columns' => $columns->contains('is_json', true),
                ];
            });
    }

    /**
     * Extract file paths from a record based on discovered file columns
     *
     * @param string $tableName Fully qualified table name
     * @param object|array $record Database record
     * @return array List of file paths found
     */
    public function extractFilePaths(string $tableName, $record): array
    {
        $record = (array) $record;
        $fileColumns = $this->getFileColumnsForTable($tableName);
        $paths = [];

        foreach ($fileColumns as $columnInfo) {
            $column = $columnInfo['column'];
            $value = $record[$column] ?? null;

            if (empty($value)) {
                continue;
            }

            if ($columnInfo['is_json']) {
                // Extract paths from JSON/JSONB column
                $paths = array_merge($paths, $this->extractPathsFromJson($value));
            } else {
                // Direct file path column
                $paths[] = $value;
            }
        }

        return array_filter(array_unique($paths));
    }

    /**
     * Get file columns for a specific table
     *
     * @param string $tableName Fully qualified table name
     * @return array File columns for the table
     */
    public function getFileColumnsForTable(string $tableName): array
    {
        return $this->discoverFileColumns()
            ->where('table', $tableName)
            ->values()
            ->toArray();
    }

    /**
     * Check if a table has file columns
     *
     * @param string $tableName Fully qualified table name
     * @return bool
     */
    public function hasFileColumns(string $tableName): bool
    {
        return $this->discoverFileColumns()
            ->where('table', $tableName)
            ->isNotEmpty();
    }

    /**
     * Extract file paths from JSON/JSONB value
     *
     * @param mixed $value JSON value (string or array)
     * @return array File paths found
     */
    protected function extractPathsFromJson($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            return [];
        }

        $paths = [];
        $this->extractPathsRecursive($value, $paths);

        return $paths;
    }

    /**
     * Recursively extract file paths from nested array
     *
     * @param array $data Data to search
     * @param array $paths Collected paths (by reference)
     */
    protected function extractPathsRecursive(array $data, array &$paths): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->extractPathsRecursive($value, $paths);
            } elseif (is_string($value) && $this->looksLikeFilePath($value, $key)) {
                $paths[] = $value;
            }
        }
    }

    /**
     * Check if a value looks like a file path
     *
     * @param string $value The value to check
     * @param string $key The array key (for context)
     * @return bool
     */
    protected function looksLikeFilePath(string $value, string $key = ''): bool
    {
        // Check if key suggests it's a file path
        $keyPatterns = ['url', 'path', 'file', 'image', 'media', 'thumbnail', 'avatar', 'logo'];
        $keyIsFileRelated = collect($keyPatterns)
            ->some(fn($pattern) => str_contains(strtolower($key), $pattern));

        if ($keyIsFileRelated) {
            // Validate it looks like a path or URL
            return $this->isValidPath($value);
        }

        // Check the value itself for common file patterns
        $fileExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
            'mp4', 'webm', 'mov', 'avi',
            'mp3', 'wav', 'ogg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'zip', 'rar', '7z',
        ];

        foreach ($fileExtensions as $ext) {
            if (preg_match("/\.{$ext}(\?|$)/i", $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a string is a valid file path or URL
     *
     * @param string $value Value to validate
     * @return bool
     */
    protected function isValidPath(string $value): bool
    {
        // Check for URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return true;
        }

        // Check for relative path (starts with / or storage/)
        if (str_starts_with($value, '/') || str_starts_with($value, 'storage/')) {
            return true;
        }

        // Check for S3-style paths
        if (preg_match('#^[a-z0-9-]+/.*\.[a-z0-9]+$#i', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Get all unique file paths for an organization
     *
     * @param string $orgId Organization ID
     * @param Collection|null $tables Optional: limit to specific tables
     * @return Collection File paths with metadata
     */
    public function discoverOrgFiles(string $orgId, ?Collection $tables = null): Collection
    {
        $fileColumnsByTable = $this->getFileColumnsByTable();

        if ($tables) {
            $fileColumnsByTable = $fileColumnsByTable->only($tables->toArray());
        }

        $allFiles = collect();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $orgId
        ]);

        foreach ($fileColumnsByTable as $tableInfo) {
            $tableName = $tableInfo['table'];
            $columns = $tableInfo['columns'];

            try {
                $records = DB::table($tableName)
                    ->select(array_merge(['id'], $columns))
                    ->cursor();

                foreach ($records as $record) {
                    $paths = $this->extractFilePaths($tableName, $record);

                    foreach ($paths as $path) {
                        $allFiles->push([
                            'path' => $path,
                            'source_table' => $tableName,
                            'source_record_id' => $record->id ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Table might not have 'id' column or access issues
                continue;
            }
        }

        return $allFiles->unique('path')->values();
    }

    /**
     * Validate that a file exists
     *
     * @param string $path File path
     * @param string $disk Storage disk
     * @return bool
     */
    public function fileExists(string $path, string $disk = 'public'): bool
    {
        // Handle URLs - they always "exist" for backup purposes
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }

        // Normalize path
        $path = ltrim($path, '/');

        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get file info (size, type) for a path
     *
     * @param string $path File path
     * @param string $disk Storage disk
     * @return array|null File info or null if not found
     */
    public function getFileInfo(string $path, string $disk = 'public'): ?array
    {
        // Handle URLs differently
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return [
                'path' => $path,
                'type' => 'url',
                'size' => null,
                'mime_type' => $this->getMimeFromExtension($path),
            ];
        }

        $normalizedPath = ltrim($path, '/');

        if (!Storage::disk($disk)->exists($normalizedPath)) {
            return null;
        }

        return [
            'path' => $path,
            'type' => 'local',
            'size' => Storage::disk($disk)->size($normalizedPath),
            'mime_type' => Storage::disk($disk)->mimeType($normalizedPath),
            'last_modified' => Storage::disk($disk)->lastModified($normalizedPath),
        ];
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $path File path
     * @return string|null
     */
    protected function getMimeFromExtension(string $path): ?string
    {
        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
        ];

        return $mimeTypes[$extension] ?? null;
    }

    /**
     * Clear discovery cache
     */
    public function clearCache(): void
    {
        $cacheKey = 'backup:file_columns:' . md5(implode(',', $this->schemas));
        Cache::forget($cacheKey);
    }
}
