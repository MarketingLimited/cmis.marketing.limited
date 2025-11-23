<?php

namespace App\Models\Asset;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ImageAsset extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.image_assets';
    protected $primaryKey = 'image_id';
    protected $fillable = [
        'asset_id',
        'org_id',
        'width',
        'height',
        'format',
        'color_mode',
        'dpi',
        'file_size',
        'dominant_colors',
        'has_transparency',
        'exif_data',
        'alt_text',
        'caption',
        'copyright',
        'thumbnails',
    ];

    protected $casts = [
        'image_id' => 'string',
        'asset_id' => 'string',
        'org_id' => 'string',
        'width' => 'integer',
        'height' => 'integer',
        'file_size' => 'integer',
        'dpi' => 'integer',
        'dominant_colors' => 'array',
        'has_transparency' => 'boolean',
        'exif_data' => 'array',
        'thumbnails' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the creative asset
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');

    }
    /**
     * Get aspect ratio
     */
    public function getAspectRatioAttribute(): ?string
    {
        if (!$this->width || !$this->height) {
            return null;
        }

        $gcd = function($a, $b) use (&$gcd) {
            return $b ? $gcd($b, $a % $b) : $a;
        };

        $divisor = $gcd($this->width, $this->height);

        return ($this->width / $divisor) . ':' . ($this->height / $divisor);
    }
    /**
     * Check if portrait
     */
    public function isPortrait(): bool
    {
        return $this->height > $this->width;

    }
    /**
     * Check if landscape
     */
    public function isLandscape(): bool
    {
        return $this->width > $this->height;

    }
    /**
     * Check if square
     */
    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }
}
