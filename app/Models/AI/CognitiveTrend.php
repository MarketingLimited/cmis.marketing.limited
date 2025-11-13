<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CognitiveTrend extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.cognitive_trends';
    protected $primaryKey = 'trend_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'domain',
        'category',
        'topic',
        'trend_score',
        'frequency',
        'last_seen',
        'first_seen',
        'peak_score',
        'related_topics',
        'metadata',
    ];

    protected $casts = [
        'trend_score' => 'float',
        'frequency' => 'integer',
        'peak_score' => 'float',
        'related_topics' => 'array',
        'metadata' => 'array',
        'last_seen' => 'datetime',
        'first_seen' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes
    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeTrending($query, $threshold = 0.7)
    {
        return $query->where('trend_score', '>=', $threshold)
            ->orderByDesc('trend_score');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('last_seen', '>=', now()->subDays($days));
    }

    // Helpers
    public function isHot()
    {
        return $this->trend_score >= 0.8;
    }

    public function isGrowing()
    {
        return $this->trend_score > ($this->peak_score * 0.9);
    }

    public function isDeclining()
    {
        return $this->trend_score < ($this->peak_score * 0.5);
    }
}
