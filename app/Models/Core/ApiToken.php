<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends BaseModel
{
    use HasFactory, HasOrganization;

    protected $table = 'cmis.api_tokens';
    protected $primaryKey = 'token_id';

    protected $fillable = [
        'token_id',
        'org_id',
        'created_by',
        'name',
        'token_hash',
        'token_prefix',
        'scopes',
        'rate_limits',
        'last_used_at',
        'usage_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'rate_limits' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    protected $hidden = [
        'token_hash',
    ];

    /**
     * Get the user who created this token.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Generate a new API token.
     */
    public static function generateToken(): array
    {
        $token = Str::random(64);
        $prefix = Str::random(8);

        return [
            'token' => $prefix . '_' . $token,
            'prefix' => $prefix,
            'hash' => hash('sha256', $token),
        ];
    }

    /**
     * Verify a token against its hash.
     */
    public function verifyToken(string $token): bool
    {
        // Extract the actual token part (after prefix_)
        $parts = explode('_', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }

        return hash_equals($this->token_hash, hash('sha256', $parts[1]));
    }

    /**
     * Mark token as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'last_used_at' => now(),
            'usage_count' => $this->usage_count + 1,
        ]);
    }

    /**
     * Check if token is valid (active and not expired).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if token has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    /**
     * Revoke the token.
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get available scopes for API tokens.
     */
    public static function getAvailableScopes(): array
    {
        return [
            'campaigns:read' => 'Read campaigns',
            'campaigns:write' => 'Create and update campaigns',
            'campaigns:delete' => 'Delete campaigns',
            'analytics:read' => 'Read analytics data',
            'content:read' => 'Read content library',
            'content:write' => 'Manage content library',
            'audiences:read' => 'Read audiences',
            'audiences:write' => 'Manage audiences',
            'settings:read' => 'Read organization settings',
            'settings:write' => 'Manage organization settings',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}
