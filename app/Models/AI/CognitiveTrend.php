<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CognitiveTrend extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.cognitive_trends';
    protected $primaryKey = 'trend_id';
    protected $fillable = [
        'trend_id',
        'org_id',
        'factor_name',
        'trend_direction',
        'growth_rate',
        'trend_strength',
        'summary_insight',
        'provider',
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
    public function scopeByDomain($query, $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    public function scopeByCategory($query, $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeTrending($query, $threshold = 0.7): Builder
    {
        return $query->where('trend_score', '>=', $threshold)
            ->orderByDesc('trend_score');
    }

    public function scopeRecent($query, $days = 7): Builder
    {
        return $query->where('last_seen', '>=', now()->subDays($days));
    }

    // Helpers
    public function isHot()
    : mixed {
        return $this->trend_score >= 0.8;
    }

    public function isGrowing()
    : mixed {
        return $this->trend_score > ($this->peak_score * 0.9);
    }

    public function isDeclining()
    : mixed {
        return $this->trend_score < ($this->peak_score * 0.5);
    }
}
