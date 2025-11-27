<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MediaAsset Model
 *
 * Stores media assets for social posts with comprehensive visual analysis,
 * design extraction, and layout mapping for brand knowledge base.
 *
 * @property string $asset_id
 * @property string $org_id
 * @property string $post_id
 * @property string $media_type
 * @property string|null $storage_path
 * @property array|null $color_palette
 * @property array|null $typography
 * @property string|null $design_prompt
 * @property boolean $is_analyzed
 */
class MediaAsset extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.media_assets';
    protected $primaryKey = 'asset_id';

    protected $fillable = [
        'org_id', 'post_id', 'media_type', 'original_url', 'storage_path',
        'file_name', 'mime_type', 'file_size', 'width', 'height',
        'aspect_ratio', 'duration_seconds', 'position',
        'is_analyzed', 'analysis_status', 'analyzed_at', 'analysis_error',
        'visual_caption', 'scene_description', 'detected_objects', 'detected_people',
        'camera_angle', 'depth_of_field', 'lighting',
        'text_blocks', 'extracted_text', 'primary_language',
        'design_prompt', 'style_profile', 'layout_map', 'element_positions',
        'color_palette', 'typography', 'art_direction', 'mood',
        'visual_message', 'look_and_feel', 'imagery_and_graphics',
        'icons_and_symbols', 'composition', 'background_style',
        'highlight_elements', 'deemphasize_elements',
        'motion_analysis', 'video_completion_rate', 'video_retention_curve',
        'brand_consistency_score', 'style_deviations', 'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'aspect_ratio' => 'decimal:2',
        'duration_seconds' => 'integer',
        'position' => 'integer',
        'is_analyzed' => 'boolean',
        'analyzed_at' => 'datetime',
        'detected_objects' => 'array',
        'detected_people' => 'array',
        'text_blocks' => 'array',
        'style_profile' => 'array',
        'layout_map' => 'array',
        'element_positions' => 'array',
        'color_palette' => 'array',
        'typography' => 'array',
        'imagery_and_graphics' => 'array',
        'icons_and_symbols' => 'array',
        'highlight_elements' => 'array',
        'deemphasize_elements' => 'array',
        'motion_analysis' => 'array',
        'video_completion_rate' => 'decimal:2',
        'video_retention_curve' => 'array',
        'brand_consistency_score' => 'decimal:4',
        'style_deviations' => 'array',
        'metadata' => 'array',
    ];

    // ===== Relationships =====

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id', 'id');
    }

    public function brandKnowledgeDimensions(): HasMany
    {
        return $this->hasMany(BrandKnowledgeDimension::class, 'media_asset_id', 'asset_id');
    }

    // ===== Scopes =====

    public function scopePendingAnalysis($query)
    {
        return $query->where('is_analyzed', false)
            ->where('analysis_status', 'pending');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('is_analyzed', true)
            ->where('analysis_status', 'completed');
    }

    public function scopeByMediaType($query, string $type)
    {
        return $query->where('media_type', $type);
    }

    public function scopeImages($query)
    {
        return $query->where('media_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->whereIn('media_type', ['video', 'reel']);
    }

    // ===== Helper Methods =====

    /**
     * Check if this is an image asset
     */
    public function isImage(): bool
    {
        return in_array($this->media_type, ['image', 'carousel_item']);
    }

    /**
     * Check if this is a video asset
     */
    public function isVideo(): bool
    {
        return in_array($this->media_type, ['video', 'reel']);
    }

    /**
     * Get the aspect ratio as a readable string (e.g., "16:9", "9:16", "1:1")
     */
    public function getAspectRatioLabel(): ?string
    {
        if (!$this->aspect_ratio) {
            return null;
        }

        $ratio = (float) $this->aspect_ratio;

        // Common aspect ratios
        $ratios = [
            1.00 => '1:1',
            1.78 => '16:9',
            0.56 => '9:16',
            1.33 => '4:3',
            0.80 => '4:5',
        ];

        foreach ($ratios as $value => $label) {
            if (abs($ratio - $value) < 0.05) {
                return $label;
            }
        }

        return number_format($ratio, 2) . ':1';
    }

    /**
     * Get dominant colors from color palette
     */
    public function getDominantColors(): array
    {
        return $this->color_palette['dominant'] ?? [];
    }

    /**
     * Get accent colors from color palette
     */
    public function getAccentColors(): array
    {
        return $this->color_palette['accent'] ?? [];
    }

    /**
     * Get all extracted text as plain string
     */
    public function getAllExtractedText(): ?string
    {
        return $this->extracted_text;
    }

    /**
     * Get text blocks by language
     */
    public function getTextBlocksByLanguage(string $language): array
    {
        if (!$this->text_blocks) {
            return [];
        }

        return array_filter($this->text_blocks, function ($block) use ($language) {
            return ($block['language'] ?? null) === $language;
        });
    }

    /**
     * Mark analysis as started
     */
    public function startAnalysis(): void
    {
        $this->update([
            'analysis_status' => 'in_progress',
        ]);
    }

    /**
     * Mark analysis as completed
     */
    public function completeAnalysis(array $analysisData): void
    {
        $this->update(array_merge($analysisData, [
            'is_analyzed' => true,
            'analysis_status' => 'completed',
            'analyzed_at' => now(),
            'analysis_error' => null,
        ]));
    }

    /**
     * Mark analysis as failed
     */
    public function failAnalysis(string $error): void
    {
        $this->update([
            'analysis_status' => 'failed',
            'analysis_error' => $error,
        ]);
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHuman(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if asset has been analyzed
     */
    public function hasAnalysis(): bool
    {
        return $this->is_analyzed && $this->analysis_status === 'completed';
    }

    /**
     * Get analysis completion percentage (0-100)
     */
    public function getAnalysisCompleteness(): int
    {
        if (!$this->hasAnalysis()) {
            return 0;
        }

        $fields = [
            'visual_caption',
            'design_prompt',
            'color_palette',
            'typography',
            'style_profile',
            'layout_map',
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }

        return (int) (($completed / count($fields)) * 100);
    }
}
