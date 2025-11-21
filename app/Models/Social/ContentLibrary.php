<?php

namespace App\Models\Social;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContentLibrary extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.content_library';
    protected $primaryKey = 'library_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'library_id',
        'org_id',
        'created_by',
        'title',
        'description',
        'content_type',
        'content',
        'media_files',
        'tags',
        'category',
        'metadata',
        'is_template',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'media_files' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_template' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class, 'content_library_id', 'library_id');
    }

    // ===== Usage Tracking =====

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function isTemplate(): bool
    {
        return $this->is_template;
    }

    // ===== Content Helpers =====

    public function hasMedia(): bool
    {
        return !empty($this->media_files);
    }

    public function getMediaCount(): int
    {
        return count($this->media_files ?? []);
    }

    public function getTagString(): string
    {
        if (empty($this->tags)) {
            return '';
        }
        return implode(', ', $this->tags);
    }

    // ===== Scopes =====

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeForContentType($query, string $type)
    {
        return $query->where('content_type', $type);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('usage_count')->limit($limit);
    }
}
