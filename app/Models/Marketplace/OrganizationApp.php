<?php

namespace App\Models\Marketplace;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Organization App Model
 *
 * Junction table that tracks which apps are enabled for each organization.
 * This model is subject to RLS (Row-Level Security) based on org_id.
 *
 * @property string $id
 * @property string $org_id
 * @property string $app_id
 * @property bool $is_enabled
 * @property \Carbon\Carbon|null $enabled_at
 * @property string|null $enabled_by
 * @property \Carbon\Carbon|null $disabled_at
 * @property string|null $disabled_by
 * @property array $settings
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class OrganizationApp extends BaseModel
{
    use HasOrganization, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.organization_apps';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'org_id',
        'app_id',
        'is_enabled',
        'enabled_at',
        'enabled_by',
        'disabled_at',
        'disabled_by',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'disabled_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Get the marketplace app.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(MarketplaceApp::class, 'app_id', 'app_id');
    }

    /**
     * Get the user who enabled this app.
     */
    public function enabledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by', 'user_id');
    }

    /**
     * Get the user who disabled this app.
     */
    public function disabledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by', 'user_id');
    }

    /**
     * Scope to only include enabled apps.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to only include disabled apps.
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    /**
     * Enable this app for the organization.
     */
    public function enable(string $userId): void
    {
        $this->update([
            'is_enabled' => true,
            'enabled_at' => now(),
            'enabled_by' => $userId,
            'disabled_at' => null,
            'disabled_by' => null,
        ]);
    }

    /**
     * Disable this app for the organization.
     */
    public function disable(string $userId): void
    {
        $this->update([
            'is_enabled' => false,
            'disabled_at' => now(),
            'disabled_by' => $userId,
        ]);
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->update(['settings' => $settings]);
    }
}
