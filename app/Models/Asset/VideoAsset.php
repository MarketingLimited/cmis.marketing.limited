<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class VideoAsset extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.video_assets';
    protected $primaryKey = 'video_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'asset_id',
        'org_id',
        'duration',
        'width',
        'height',
        'format',
        'codec',
        'bitrate',
        'frame_rate',
        'file_size',
        'has_audio',
        'audio_codec',
        'thumbnail_url',
        'preview_url',
        'captions',
        'chapters',
        'metadata',
    ];

    protected $casts = [
        'video_id' => 'string',
        'asset_id' => 'string',
        'org_id' => 'string',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'bitrate' => 'integer',
        'frame_rate' => 'float',
        'file_size' => 'integer',
        'has_audio' => 'boolean',
        'captions' => 'array',
        'chapters' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
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
     * Check if short form (< 60 seconds)
     */
    public function isShortForm(): bool
    {
        return $this->duration < 60;
    }

    /**
     * Check if long form (> 10 minutes)
     */
    public function isLongForm(): bool
    {
        return $this->duration > 600;
    }
}
