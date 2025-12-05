<?php

namespace App\Models\Backup;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * BackupEncryptionKey Model
 *
 * Custom encryption keys for enterprise organizations.
 * Keys are encrypted at rest using Laravel's encryption.
 *
 * @property string $id
 * @property string $org_id
 * @property string $name
 * @property string|null $description
 * @property string $encrypted_key
 * @property string $key_hash
 * @property string $algorithm
 * @property bool $is_active
 * @property bool $is_default
 * @property int $usage_count
 * @property \Carbon\Carbon|null $last_used_at
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BackupEncryptionKey extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.backup_encryption_keys';

    // Supported algorithms
    public const ALGORITHM_AES_256_GCM = 'aes-256-gcm';
    public const ALGORITHM_AES_256_CBC = 'aes-256-cbc';

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'encrypted_key',
        'key_hash',
        'algorithm',
        'is_active',
        'is_default',
        'usage_count',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_key', // Never expose raw key
        'key_hash',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // When setting a key as default, unset other defaults
        static::saving(function (self $key) {
            if ($key->is_default && $key->isDirty('is_default')) {
                self::where('org_id', $key->org_id)
                    ->where('id', '!=', $key->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    // ==================== Key Management ====================

    /**
     * Generate a new encryption key
     */
    public static function generateKey(string $algorithm = self::ALGORITHM_AES_256_GCM): string
    {
        $keyLength = match ($algorithm) {
            self::ALGORITHM_AES_256_GCM,
            self::ALGORITHM_AES_256_CBC => 32, // 256 bits
            default => 32,
        };

        return random_bytes($keyLength);
    }

    /**
     * Create a new encryption key for an organization
     */
    public static function createForOrg(
        string $orgId,
        string $name,
        string $createdBy,
        ?string $description = null,
        string $algorithm = self::ALGORITHM_AES_256_GCM,
        bool $isDefault = false
    ): self {
        $rawKey = self::generateKey($algorithm);

        return self::create([
            'org_id' => $orgId,
            'name' => $name,
            'description' => $description,
            'encrypted_key' => Crypt::encryptString(base64_encode($rawKey)),
            'key_hash' => hash('sha256', $rawKey),
            'algorithm' => $algorithm,
            'is_active' => true,
            'is_default' => $isDefault,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Get the decrypted key
     */
    public function getDecryptedKey(): string
    {
        return base64_decode(Crypt::decryptString($this->encrypted_key));
    }

    /**
     * Verify a key matches the stored hash
     */
    public function verifyKey(string $key): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $key));
    }

    // ==================== Relationships ====================

    /**
     * Get the user who created this key
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get backups using this key
     */
    public function backups(): HasMany
    {
        return $this->hasMany(OrganizationBackup::class, 'encryption_key_id');
    }

    /**
     * Get schedules using this key
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(BackupSchedule::class, 'encryption_key_id');
    }

    // ==================== Scopes ====================

    /**
     * Scope: Only active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default key
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ==================== Status Methods ====================

    /**
     * Check if key can be deleted
     */
    public function canBeDeleted(): bool
    {
        // Can't delete if used by any backups
        return $this->backups()->count() === 0
            && $this->schedules()->count() === 0;
    }

    /**
     * Deactivate the key
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'is_default' => false,
        ]);
    }

    /**
     * Record key usage
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    // ==================== Helper Methods ====================

    /**
     * Get key info (without sensitive data)
     */
    public function getKeyInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'algorithm' => $this->algorithm,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Get supported algorithms
     */
    public static function getSupportedAlgorithms(): array
    {
        return [
            self::ALGORITHM_AES_256_GCM => 'AES-256-GCM (Recommended)',
            self::ALGORITHM_AES_256_CBC => 'AES-256-CBC',
        ];
    }

    /**
     * Get the default algorithm
     */
    public static function getDefaultAlgorithm(): string
    {
        return self::ALGORITHM_AES_256_GCM;
    }
}
