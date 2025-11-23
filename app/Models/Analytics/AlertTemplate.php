<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alert Template Model (Phase 13)
 *
 * Pre-configured alert rule templates
 *
 * @property string $template_id
 * @property string|null $created_by
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property string $entity_type
 * @property array $default_config
 * @property bool $is_public
 * @property bool $is_system
 * @property int $usage_count
 */
class AlertTemplate extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.alert_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'category',
        'entity_type',
        'default_config',
        'is_public',
        'is_system',
        'usage_count'
    ];

    protected $casts = [
        'default_config' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who created the template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope: Public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: System templates
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: By category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By entity type
     */
    public function scopeEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
