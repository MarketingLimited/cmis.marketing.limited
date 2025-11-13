<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreativeTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.creative_templates';
    protected $primaryKey = 'template_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'template_name',
        'template_type',
        'category',
        'channel_id',
        'structure',
        'variables',
        'example_content',
        'usage_guidelines',
        'performance_score',
        'usage_count',
        'tags',
        'is_public',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'template_id' => 'string',
        'org_id' => 'string',
        'channel_id' => 'integer',
        'created_by' => 'string',
        'structure' => 'array',
        'variables' => 'array',
        'example_content' => 'array',
        'usage_guidelines' => 'array',
        'performance_score' => 'float',
        'usage_count' => 'integer',
        'tags' => 'array',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope by template type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope public templates
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope high performing
     */
    public function scopeHighPerforming($query, float $threshold = 0.7)
    {
        return $query->where('performance_score', '>=', $threshold)
            ->orderBy('performance_score', 'desc');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Render template with variables
     */
    public function render(array $data): array
    {
        $rendered = [];

        foreach ($this->structure as $key => $template) {
            $rendered[$key] = $this->replaceVariables($template, $data);
        }

        return $rendered;
    }

    /**
     * Replace variables in template string
     */
    protected function replaceVariables(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }
}
