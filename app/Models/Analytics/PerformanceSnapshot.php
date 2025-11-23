<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Campaign;
use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PerformanceSnapshot extends BaseModel
{
    use HasOrganization;

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
    public function scopeDateRange($query, string $startDate, string $endDate): Builder
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
    public function scopeByType($query, string $type): Builder
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
    public function scopeByCampaign($query, string $campaignId): Builder
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

        if ($type) {
            $query->where('snapshot_type', $type);

        return $query->orderBy('snapshot_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
}
}
}
