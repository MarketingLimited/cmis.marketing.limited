<?php

namespace App\Models\Analytics;

use App\Models\Campaign;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PerformanceSnapshot extends Model
{
    use HasUuids;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.performance_snapshots';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'snapshot_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'org_id',
        'campaign_id',
        'snapshot_date',
        'snapshot_type',
        'metrics',
        'aggregated_data',
        'comparison_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_id' => 'string',
            'org_id' => 'string',
            'campaign_id' => 'string',
            'snapshot_date' => 'date',
            'metrics' => 'array',
            'aggregated_data' => 'array',
            'comparison_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the organization that owns the snapshot.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the campaign that this snapshot belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Create a performance snapshot.
     *
     * @param string $orgId
     * @param string|null $campaignId
     * @param string $snapshotType
     * @param array $metrics
     * @param array|null $aggregatedData
     * @param array|null $comparisonData
     * @return static
     */
    public static function capture(
        string $orgId,
        ?string $campaignId,
        string $snapshotType,
        array $metrics,
        ?array $aggregatedData = null,
        ?array $comparisonData = null
    ): static {
        return static::create([
            'snapshot_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $orgId,
            'campaign_id' => $campaignId,
            'snapshot_date' => now()->toDateString(),
            'snapshot_type' => $snapshotType,
            'metrics' => $metrics,
            'aggregated_data' => $aggregatedData,
            'comparison_data' => $comparisonData,
        ]);
    }

    /**
     * Scope snapshots by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope snapshots by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    /**
     * Scope snapshots by campaign.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $campaignId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Get the latest snapshot for an organization.
     *
     * @param string $orgId
     * @param string|null $campaignId
     * @param string|null $type
     * @return static|null
     */
    public static function latest(string $orgId, ?string $campaignId = null, ?string $type = null): ?static
    {
        $query = static::where('org_id', $orgId);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($type) {
            $query->where('snapshot_type', $type);
        }

        return $query->orderBy('snapshot_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
