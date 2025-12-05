<?php

namespace App\Apps\Backup\Services\Packaging;

/**
 * Checksum Service
 *
 * Provides checksum generation and verification for backup integrity.
 * Uses SHA-256 for cryptographic hashing.
 */
class ChecksumService
{
    /**
     * Default hash algorithm
     */
    protected const DEFAULT_ALGORITHM = 'sha256';

    /**
     * Chunk size for streaming hash (1MB)
     */
    protected const CHUNK_SIZE = 1048576;

    /**
     * Calculate hash of a file
     *
     * @param string $filePath File path
     * @param string $algorithm Hash algorithm
     * @return string Hex-encoded hash
     */
    public function hashFile(string $filePath, string $algorithm = self::DEFAULT_ALGORITHM): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        return hash_file($algorithm, $filePath);
    }

    /**
     * Calculate hash of a string
     *
     * @param string $content Content to hash
     * @param string $algorithm Hash algorithm
     * @return string Hex-encoded hash
     */
    public function hashString(string $content, string $algorithm = self::DEFAULT_ALGORITHM): string
    {
        return hash($algorithm, $content);
    }

    /**
     * Calculate hash of a file using streaming (memory efficient)
     *
     * @param string $filePath File path
     * @param string $algorithm Hash algorithm
     * @return string Hex-encoded hash
     */
    public function hashFileStreaming(string $filePath, string $algorithm = self::DEFAULT_ALGORITHM): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $context = hash_init($algorithm);
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        while (!feof($handle)) {
            $chunk = fread($handle, self::CHUNK_SIZE);
            hash_update($context, $chunk);
        }

        fclose($handle);

        return hash_final($context);
    }

    /**
     * Verify a file's checksum
     *
     * @param string $filePath File path
     * @param string $expectedHash Expected hash value
     * @param string $algorithm Hash algorithm
     * @return bool True if checksum matches
     */
    public function verifyFile(string $filePath, string $expectedHash, string $algorithm = self::DEFAULT_ALGORITHM): bool
    {
        $actualHash = $this->hashFile($filePath, $algorithm);
        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Verify a string's checksum
     *
     * @param string $content Content to verify
     * @param string $expectedHash Expected hash value
     * @param string $algorithm Hash algorithm
     * @return bool True if checksum matches
     */
    public function verifyString(string $content, string $expectedHash, string $algorithm = self::DEFAULT_ALGORITHM): bool
    {
        $actualHash = $this->hashString($content, $algorithm);
        return hash_equals($expectedHash, $actualHash);
    }

    /**
     * Generate checksums for multiple files
     *
     * @param array $filePaths Array of file paths
     * @param string $algorithm Hash algorithm
     * @return array Map of file path => checksum
     */
    public function hashFiles(array $filePaths, string $algorithm = self::DEFAULT_ALGORITHM): array
    {
        $checksums = [];

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                $checksums[$filePath] = $this->hashFile($filePath, $algorithm);
            } else {
                $checksums[$filePath] = null;
            }
        }

        return $checksums;
    }

    /**
     * Verify multiple file checksums
     *
     * @param array $checksums Map of file path => expected checksum
     * @param string $algorithm Hash algorithm
     * @return array Verification results
     */
    public function verifyFiles(array $checksums, string $algorithm = self::DEFAULT_ALGORITHM): array
    {
        $results = [
            'valid' => [],
            'invalid' => [],
            'missing' => [],
        ];

        foreach ($checksums as $filePath => $expectedHash) {
            if (!file_exists($filePath)) {
                $results['missing'][] = $filePath;
                continue;
            }

            if ($this->verifyFile($filePath, $expectedHash, $algorithm)) {
                $results['valid'][] = $filePath;
            } else {
                $results['invalid'][] = [
                    'file' => $filePath,
                    'expected' => $expectedHash,
                    'actual' => $this->hashFile($filePath, $algorithm),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate a checksum manifest for a directory
     *
     * @param string $directory Directory path
     * @param string $algorithm Hash algorithm
     * @param array $extensions Optional: limit to these extensions
     * @return array Manifest with checksums
     */
    public function generateDirectoryManifest(
        string $directory,
        string $algorithm = self::DEFAULT_ALGORITHM,
        array $extensions = []
    ): array {
        $manifest = [
            'directory' => $directory,
            'algorithm' => $algorithm,
            'generated_at' => now()->toISOString(),
            'files' => [],
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            // Filter by extension if specified
            if (!empty($extensions)) {
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $extensions)) {
                    continue;
                }
            }

            $relativePath = str_replace($directory . '/', '', $file->getPathname());

            $manifest['files'][$relativePath] = [
                'checksum' => $this->hashFile($file->getPathname(), $algorithm),
                'size' => $file->getSize(),
                'modified' => date('c', $file->getMTime()),
            ];
        }

        $manifest['file_count'] = count($manifest['files']);

        return $manifest;
    }

    /**
     * Verify a directory against a manifest
     *
     * @param string $directory Directory path
     * @param array $manifest Checksum manifest
     * @return array Verification results
     */
    public function verifyDirectoryManifest(string $directory, array $manifest): array
    {
        $algorithm = $manifest['algorithm'] ?? self::DEFAULT_ALGORITHM;
        $results = [
            'valid' => [],
            'invalid' => [],
            'missing' => [],
            'extra' => [],
        ];

        $manifestFiles = $manifest['files'] ?? [];

        // Check files in manifest
        foreach ($manifestFiles as $relativePath => $fileInfo) {
            $fullPath = rtrim($directory, '/') . '/' . $relativePath;
            $expectedChecksum = is_array($fileInfo) ? $fileInfo['checksum'] : $fileInfo;

            if (!file_exists($fullPath)) {
                $results['missing'][] = $relativePath;
                continue;
            }

            $actualChecksum = $this->hashFile($fullPath, $algorithm);

            if (hash_equals($expectedChecksum, $actualChecksum)) {
                $results['valid'][] = $relativePath;
            } else {
                $results['invalid'][] = [
                    'file' => $relativePath,
                    'expected' => $expectedChecksum,
                    'actual' => $actualChecksum,
                ];
            }
        }

        // Find extra files not in manifest
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relativePath = str_replace($directory . '/', '', $file->getPathname());

            if (!isset($manifestFiles[$relativePath])) {
                $results['extra'][] = $relativePath;
            }
        }

        return $results;
    }

    /**
     * Create a checksum file (similar to sha256sum output)
     *
     * @param array $checksums Map of file path => checksum
     * @param string $outputPath Output file path
     * @return int Bytes written
     */
    public function writeChecksumFile(array $checksums, string $outputPath): int
    {
        $content = '';

        foreach ($checksums as $filePath => $checksum) {
            $content .= "{$checksum}  {$filePath}\n";
        }

        return file_put_contents($outputPath, $content);
    }

    /**
     * Read and parse a checksum file
     *
     * @param string $filePath Checksum file path
     * @return array Map of file path => checksum
     */
    public function readChecksumFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Checksum file not found: {$filePath}");
        }

        $checksums = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Format: checksum  filename (two spaces between)
            if (preg_match('/^([a-f0-9]+)\s{2}(.+)$/', $line, $matches)) {
                $checksums[$matches[2]] = $matches[1];
            }
        }

        return $checksums;
    }

    /**
     * Get available hash algorithms
     *
     * @return array List of algorithm names
     */
    public function getAvailableAlgorithms(): array
    {
        return hash_algos();
    }

    /**
     * Get algorithm info
     *
     * @param string $algorithm Algorithm name
     * @return array Algorithm info
     */
    public function getAlgorithmInfo(string $algorithm): array
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new \InvalidArgumentException("Unknown hash algorithm: {$algorithm}");
        }

        // Get digest length by hashing empty string
        $sampleHash = hash($algorithm, '');

        return [
            'name' => $algorithm,
            'digest_length' => strlen($sampleHash) / 2, // Hex chars to bytes
            'hex_length' => strlen($sampleHash),
        ];
    }
}
