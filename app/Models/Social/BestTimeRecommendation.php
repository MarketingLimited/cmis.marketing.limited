<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BestTimeRecommendation extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.best_time_recommendations';
    protected $primaryKey = 'recommendation_id';

    protected $fillable = [
        'recommendation_id',
        'org_id',
        'platform',
        'day_of_week',
        'hour_of_day',
        'engagement_score',
        'sample_size',
        'avg_engagement_rate',
        'performance_data',
        'calculated_at',
    ];

    protected $casts = [
        'hour_of_day' => 'integer',
        'engagement_score' => 'decimal:2',
        'sample_size' => 'integer',
        'avg_engagement_rate' => 'decimal:2',
        'performance_data' => 'array',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    // ===== Recommendation Helpers =====

    public function getTimeLabel(): string
    {
        $hour = str_pad($this->hour_of_day, 2, '0', STR_PAD_LEFT);
        return "{$hour}:00";
    }

    public function getDayLabel(): string
    {
        return ucfirst($this->day_of_week);
    }

    public function isHighEngagement(): bool
    {
        return $this->engagement_score >= 70;
    }

    public function getScoreColor(): string
    {
        if ($this->engagement_score >= 80) {
            return 'green';
        } elseif ($this->engagement_score >= 60) {
            return 'yellow';
        }
        return 'red';
    }

    // ===== Scopes =====

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeForDay($query, string $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeTopTimes($query, int $limit = 5)
    {
        return $query->orderByDesc('engagement_score')->limit($limit);
    }
}
