<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anomaly extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.anomalies';
    protected $primaryKey = 'anomaly_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'anomaly_type',
        'severity', 'expected_value', 'actual_value', 'deviation_percentage',
        'confidence_score', 'detected_date', 'description', 'status',
        'acknowledged_by', 'acknowledged_at', 'resolution_notes'
    ];

    protected $casts = [
        'expected_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'deviation_percentage' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'detected_date' => 'date',
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by', 'user_id');
    }

    public function acknowledge(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    public function resolve(string $userId, string $notes): void
    {
        $this->update([
            'status' => 'resolved',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    public function markFalsePositive(string $userId, string $notes): void
    {
        $this->update([
            'status' => 'false_positive',
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'resolution_notes' => $notes
        ]);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('detected_date', '>=', now()->subDays($days));
    }
}
