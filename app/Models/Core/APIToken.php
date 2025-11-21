<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class APIToken extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.api_tokens';
    protected $primaryKey = 'token_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id', 'created_by', 'name', 'token_hash', 'token_prefix',
        'scopes', 'rate_limits', 'last_used_at', 'usage_count',
        'expires_at', 'is_active'
    ];

    protected $casts = [
        'scopes' => 'array',
        'rate_limits' => 'array',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $hidden = ['token_hash'];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public static function generateToken(): array
    {
        $token = 'cmis_' . Str::random(64);
        return [
            'token' => $token,
            'hash' => hash('sha256', $token),
            'prefix' => substr($token, 0, 16)
        ];
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? []);
    }

    public function recordUsage(): void
    {
        $this->update([
            'last_used_at' => now(),
            'usage_count' => $this->usage_count + 1
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
