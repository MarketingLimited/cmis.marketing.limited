<?php

namespace App\Models\Analytics;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * Report Template Model (Phase 12)
 *
 * Pre-built report configurations that users can apply
 *
 * @property string $template_id
 * @property string|null $created_by
 * @property string $name
 * @property string|null $description
 * @property string $report_type
 * @property array $default_config
 * @property string $category
 * @property bool $is_public
 * @property bool $is_system
 * @property int $usage_count
 */
class ReportTemplate extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.report_templates';
    protected $primaryKey = 'template_id';
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'report_type',
        'default_config',
        'category',
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
    public function scopePublic($query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: System templates
     */
    public function scopeSystem($query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: By category
     */
    public function scopeCategory($query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: By report type
     */
    public function scopeType($query, string $reportType): Builder
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
