<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\Campaign\Campaign;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedMedia extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis_ai.generated_media';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'org_id',
        'campaign_id',
        'user_id',
        'media_type',
        'ai_model',
        'prompt_text',
        'media_url',
        'storage_path',
        'resolution',
        'duration_seconds',
        'aspect_ratio',
        'file_size_bytes',
        'generation_cost',
        'status',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'generation_cost' => 'decimal:4',
        'file_size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Media type constants
     */
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * AI model constants
     */
    const MODEL_GEMINI_3_PRO = 'gemini-3-pro-preview';
    const MODEL_GEMINI_3_IMAGE = 'gemini-3-pro-image-preview';
    const MODEL_VEO_31 = 'veo-3.1';
    const MODEL_VEO_31_FAST = 'veo-3.1-fast';

    /**
     * Get the campaign that owns the generated media.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the user who generated the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages($query): Builder
    {
        return $query->where('media_type', self::TYPE_IMAGE);
    }

    /**
     * Scope a query to only include videos.
     */
    public function scopeVideos($query): Builder
    {
        return $query->where('media_type', self::TYPE_VIDEO);
    }

    /**
     * Scope a query to only include completed media.
     */
    public function scopeCompleted($query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include failed media.
     */
    public function scopeFailed($query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope a query to filter by campaign.
     */
    public function scopeForCampaign($query, string $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return $this->media_type === self::TYPE_IMAGE;
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return $this->media_type === self::TYPE_VIDEO;
    }

    /**
     * Check if generation is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if generation failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if generation is in progress.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Mark media as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark media as completed.
     */
    public function markAsCompleted(string $mediaUrl, ?int $fileSize = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'media_url' => $mediaUrl,
            'file_size_bytes' => $fileSize,
        ]);
    }

    /**
     * Mark media as failed.
     */
    public function markAsFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size_bytes) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size_bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get model display name.
     */
    public function getModelDisplayNameAttribute(): string
    {
        return match($this->ai_model) {
            self::MODEL_GEMINI_3_PRO => 'Gemini 3 Pro',
            self::MODEL_GEMINI_3_IMAGE => 'Gemini 3 Image',
            self::MODEL_VEO_31 => 'Veo 3.1',
            self::MODEL_VEO_31_FAST => 'Veo 3.1 Fast',
            default => $this->ai_model,
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray',
        };
    }
}
