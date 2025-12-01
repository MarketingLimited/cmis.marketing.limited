<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Support\Str;

class QueueSlotLabel extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.queue_slot_labels';

    protected $fillable = [
        'org_id',
        'name',
        'slug',
        'background_color',
        'text_color',
        'color_type',
        'gradient_start',
        'gradient_end',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['computed_background'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && !$model->isDirty('slug')) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the computed background style (CSS value).
     * Returns either a solid color or gradient string.
     */
    public function getComputedBackgroundAttribute(): string
    {
        if ($this->color_type === 'gradient' && $this->gradient_start && $this->gradient_end) {
            return "linear-gradient(135deg, {$this->gradient_start}, {$this->gradient_end})";
        }

        return $this->background_color ?? '#3B82F6';
    }

    /**
     * Get style attributes for rendering the label badge.
     */
    public function getStyleAttributes(): array
    {
        return [
            'background' => $this->computed_background,
            'color' => $this->text_color ?? '#FFFFFF',
        ];
    }

    /**
     * Check if this label uses a gradient background.
     */
    public function isGradient(): bool
    {
        return $this->color_type === 'gradient';
    }

    /**
     * Get predefined solid color presets.
     */
    public static function getSolidColorPresets(): array
    {
        return [
            ['name' => 'Blue', 'value' => '#3B82F6'],
            ['name' => 'Green', 'value' => '#10B981'],
            ['name' => 'Purple', 'value' => '#8B5CF6'],
            ['name' => 'Pink', 'value' => '#EC4899'],
            ['name' => 'Orange', 'value' => '#F97316'],
            ['name' => 'Red', 'value' => '#EF4444'],
            ['name' => 'Yellow', 'value' => '#F59E0B'],
            ['name' => 'Cyan', 'value' => '#06B6D4'],
            ['name' => 'Indigo', 'value' => '#6366F1'],
            ['name' => 'Gray', 'value' => '#6B7280'],
        ];
    }

    /**
     * Get predefined gradient presets.
     */
    public static function getGradientPresets(): array
    {
        return [
            ['name' => 'Sunset', 'start' => '#F97316', 'end' => '#EC4899'],
            ['name' => 'Ocean', 'start' => '#3B82F6', 'end' => '#06B6D4'],
            ['name' => 'Forest', 'start' => '#10B981', 'end' => '#84CC16'],
            ['name' => 'Berry', 'start' => '#8B5CF6', 'end' => '#EC4899'],
            ['name' => 'Fire', 'start' => '#EF4444', 'end' => '#F97316'],
        ];
    }

    /**
     * Get text color options.
     */
    public static function getTextColorOptions(): array
    {
        return [
            ['name' => 'White', 'value' => '#FFFFFF'],
            ['name' => 'Black', 'value' => '#1F2937'],
            ['name' => 'Gray', 'value' => '#6B7280'],
        ];
    }

    /**
     * Scope to order labels by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope to search labels by name.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where('name', 'ilike', "%{$search}%");
    }
}
