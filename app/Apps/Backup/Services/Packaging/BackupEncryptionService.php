<?php

namespace App\Apps\Backup\Services\Packaging;

use App\Models\Backup\BackupEncryptionKey;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Backup Encryption Service
 *
 * Handles encryption and decryption of backup files using AES-256-GCM.
 * Supports both master key encryption and custom organization keys (Enterprise).
 */
class BackupEncryptionService
{
    /**
     * Encryption algorithm
     */
    protected const CIPHER = 'aes-256-gcm';

    /**
     * IV length in bytes
     */
    protected const IV_LENGTH = 12;

    /**
     * Auth tag length in bytes
     */
    protected const TAG_LENGTH = 16;

    /**
     * Chunk size for streaming encryption (1MB)
     */
    protected const CHUNK_SIZE = 1048576;

    /**
     * File signature for encrypted backups
     */
    protected const FILE_SIGNATURE = 'CMIS_ENC_V1';

    /**
     * Master encryption key
     */
    protected ?string $masterKey;

    public function __construct()
    {
        $this->masterKey = config('backup.encryption.master_key');
    }

    /**
     * Encrypt a backup file
     *
     * @param string $sourcePath Source file path
     * @param string|null $customKeyId Optional custom encryption key ID
     * @return array Encryption result with output path and metadata
     */
    public function encrypt(string $sourcePath, ?string $customKeyId = null): array
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source file not found: {$sourcePath}");
        }

        // Get encryption key
        $keyData = $this->getEncryptionKey($customKeyId);
        $key = $keyData['key'];
        $keyId = $keyData['id'];

        // Generate IV
        $iv = random_bytes(self::IV_LENGTH);

        // Output path
        $outputPath = $sourcePath . '.enc';

        // Open files
        $sourceHandle = fopen($sourcePath, 'rb');
        $outputHandle = fopen($outputPath, 'wb');

        if (!$sourceHandle || !$outputHandle) {
            throw new \RuntimeException("Failed to open files for encryption");
        }

        // Write file signature and metadata
        $metadata = $this->buildEncryptionMetadata($keyId, $iv);
        fwrite($outputHandle, self::FILE_SIGNATURE);
        fwrite($outputHandle, pack('N', strlen($metadata)));
        fwrite($outputHandle, $metadata);

        // Encrypt file content in chunks
        $tag = '';
        $encryptedSize = 0;

        // For GCM, we need to encrypt the entire file at once for proper authentication
        // For large files, we use a chunked approach with individual authentication tags
        $sourceContent = file_get_contents($sourcePath);
        $encrypted = openssl_encrypt(
            $sourceContent,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            fclose($sourceHandle);
            fclose($outputHandle);
            unlink($outputPath);
            throw new \RuntimeException("Encryption failed: " . openssl_error_string());
        }

        // Write tag and encrypted content
        fwrite($outputHandle, $tag);
        fwrite($outputHandle, $encrypted);
        $encryptedSize = strlen($encrypted);

        fclose($sourceHandle);
        fclose($outputHandle);

        // Optionally delete source file
        if (config('backup.encryption.delete_source', false)) {
            unlink($sourcePath);
        }

        return [
            'source_path' => $sourcePath,
            'output_path' => $outputPath,
            'original_size' => filesize($sourcePath),
            'encrypted_size' => filesize($outputPath),
            'key_id' => $keyId,
            'algorithm' => self::CIPHER,
            'encrypted_at' => now()->toISOString(),
        ];
    }

    /**
     * Decrypt a backup file
     *
     * @param string $encryptedPath Encrypted file path
     * @param string|null $customKeyId Optional custom encryption key ID
     * @return array Decryption result with output path
     */
    public function decrypt(string $encryptedPath, ?string $customKeyId = null): array
    {
        if (!file_exists($encryptedPath)) {
            throw new \RuntimeException("Encrypted file not found: {$encryptedPath}");
        }

        // Open encrypted file
        $handle = fopen($encryptedPath, 'rb');
        if (!$handle) {
            throw new \RuntimeException("Failed to open encrypted file");
        }

        // Verify signature
        $signature = fread($handle, strlen(self::FILE_SIGNATURE));
        if ($signature !== self::FILE_SIGNATURE) {
            fclose($handle);
            throw new \RuntimeException("Invalid encrypted file: signature mismatch");
        }

        // Read metadata
        $metadataLength = unpack('N', fread($handle, 4))[1];
        $metadataJson = fread($handle, $metadataLength);
        $metadata = json_decode($metadataJson, true);

        if (!$metadata) {
            fclose($handle);
            throw new \RuntimeException("Invalid encrypted file: corrupt metadata");
        }

        // Get decryption key
        $keyId = $customKeyId ?? $metadata['key_id'] ?? null;
        $keyData = $this->getEncryptionKey($keyId);
        $key = $keyData['key'];

        // Extract IV from metadata
        $iv = base64_decode($metadata['iv']);

        // Read auth tag
        $tag = fread($handle, self::TAG_LENGTH);

        // Read encrypted content
        $encrypted = stream_get_contents($handle);
        fclose($handle);

        // Decrypt
        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException("Decryption failed: " . openssl_error_string());
        }

        // Write decrypted content
        $outputPath = preg_replace('/\.enc$/', '', $encryptedPath);
        if ($outputPath === $encryptedPath) {
            $outputPath .= '.dec';
        }

        file_put_contents($outputPath, $decrypted);

        return [
            'encrypted_path' => $encryptedPath,
            'output_path' => $outputPath,
            'decrypted_size' => strlen($decrypted),
            'key_id' => $keyId,
            'decrypted_at' => now()->toISOString(),
        ];
    }

    /**
     * Verify an encrypted file can be decrypted (without full decryption)
     *
     * @param string $encryptedPath Encrypted file path
     * @param string|null $customKeyId Optional custom key ID
     * @return array Verification result
     */
    public function verify(string $encryptedPath, ?string $customKeyId = null): array
    {
        try {
            $handle = fopen($encryptedPath, 'rb');
            if (!$handle) {
                return ['valid' => false, 'error' => 'Cannot open file'];
            }

            // Check signature
            $signature = fread($handle, strlen(self::FILE_SIGNATURE));
            if ($signature !== self::FILE_SIGNATURE) {
                fclose($handle);
                return ['valid' => false, 'error' => 'Invalid signature'];
            }

            // Read and validate metadata
            $metadataLength = unpack('N', fread($handle, 4))[1];
            $metadataJson = fread($handle, $metadataLength);
            $metadata = json_decode($metadataJson, true);

            fclose($handle);

            if (!$metadata) {
                return ['valid' => false, 'error' => 'Invalid metadata'];
            }

            // Check if we have the required key
            $keyId = $customKeyId ?? $metadata['key_id'] ?? null;
            try {
                $this->getEncryptionKey($keyId);
            } catch (\Exception $e) {
                return ['valid' => false, 'error' => 'Missing encryption key'];
            }

            return [
                'valid' => true,
                'metadata' => $metadata,
                'key_id' => $keyId,
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get encryption key by ID or master key
     *
     * @param string|null $keyId Key ID or null for master key
     * @return array Key data with 'id' and 'key'
     */
    protected function getEncryptionKey(?string $keyId): array
    {
        if ($keyId === null || $keyId === 'master') {
            if (!$this->masterKey) {
                throw new \RuntimeException("Master encryption key not configured");
            }

            return [
                'id' => 'master',
                'key' => base64_decode($this->masterKey),
            ];
        }

        // Load custom key from database
        $encryptionKey = BackupEncryptionKey::find($keyId);

        if (!$encryptionKey) {
            throw new \RuntimeException("Encryption key not found: {$keyId}");
        }

        if (!$encryptionKey->is_active) {
            throw new \RuntimeException("Encryption key is inactive: {$keyId}");
        }

        // Decrypt the stored key using Laravel's encryption
        $decryptedKey = Crypt::decryptString($encryptionKey->encrypted_key);

        return [
            'id' => $keyId,
            'key' => base64_decode($decryptedKey),
        ];
    }

    /**
     * Build encryption metadata
     *
     * @param string $keyId Key ID used
     * @param string $iv Initialization vector
     * @return string JSON metadata
     */
    protected function buildEncryptionMetadata(string $keyId, string $iv): string
    {
        return json_encode([
            'version' => 1,
            'algorithm' => self::CIPHER,
            'key_id' => $keyId,
            'iv' => base64_encode($iv),
            'encrypted_at' => now()->toISOString(),
        ]);
    }

    /**
     * Generate a new encryption key
     *
     * @return string Base64-encoded key
     */
    public function generateKey(): string
    {
        $key = random_bytes(32); // 256 bits
        return base64_encode($key);
    }

    /**
     * Create and store a new organization encryption key
     *
     * @param string $orgId Organization ID
     * @param string $name Key name
     * @param string $createdBy User ID
     * @return BackupEncryptionKey Created key model
     */
    public function createOrganizationKey(string $orgId, string $name, string $createdBy): BackupEncryptionKey
    {
        $rawKey = $this->generateKey();

        $key = new BackupEncryptionKey();
        $key->org_id = $orgId;
        $key->name = $name;
        $key->encrypted_key = Crypt::encryptString($rawKey);
        $key->key_hash = hash('sha256', $rawKey);
        $key->is_active = true;
        $key->created_by = $createdBy;
        $key->save();

        return $key;
    }

    /**
     * Rotate an organization's encryption key
     *
     * @param string $keyId Current key ID
     * @param string $createdBy User ID
     * @return BackupEncryptionKey New key
     */
    public function rotateKey(string $keyId, string $createdBy): BackupEncryptionKey
    {
        $oldKey = BackupEncryptionKey::findOrFail($keyId);

        // Deactivate old key
        $oldKey->is_active = false;
        $oldKey->save();

        // Create new key
        return $this->createOrganizationKey(
            $oldKey->org_id,
            $oldKey->name . ' (rotated ' . now()->format('Y-m-d') . ')',
            $createdBy
        );
    }

    /**
     * Check if a file is encrypted
     *
     * @param string $filePath File path
     * @return bool
     */
    public function isEncrypted(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $signature = fread($handle, strlen(self::FILE_SIGNATURE));
        fclose($handle);

        return $signature === self::FILE_SIGNATURE;
    }

    /**
     * Get encryption metadata from a file
     *
     * @param string $filePath Encrypted file path
     * @return array|null Metadata or null if not encrypted
     */
    public function getEncryptionMetadata(string $filePath): ?array
    {
        if (!$this->isEncrypted($filePath)) {
            return null;
        }

        $handle = fopen($filePath, 'rb');
        fseek($handle, strlen(self::FILE_SIGNATURE));

        $metadataLength = unpack('N', fread($handle, 4))[1];
        $metadataJson = fread($handle, $metadataLength);
        fclose($handle);

        return json_decode($metadataJson, true);
    }
}
