<?php

namespace App\Models\Market;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class OrgMarket extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.org_markets';
    protected $primaryKey = 'market_id';
    protected $fillable = [
        'org_id',
        'market_id',
        'is_default',
        'provider',
    ];

    protected $casts = ['org_id' => 'string',
        'market_id' => 'string',
        'entry_date' => 'date',
        'market_share' => 'float',
        'priority_level' => 'integer',
        'investment_budget' => 'decimal:2',
        'target_audience' => 'array',
        'positioning_strategy' => 'array',
        'competitive_advantages' => 'array',
        'challenges' => 'array',
        'opportunities' => 'array',
        'is_primary_market' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    

    /**
     * Get the market
     */
    public function market()
    {
        return $this->belongsTo(Market::class, 'market_id', 'market_id');

    /**
     * Scope primary markets
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary_market', true);

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);

    /**
     * Scope active markets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');

    /**
     * Scope by priority
     */
    public function scopeHighPriority($query, int $threshold = 7)
    {
        return $query->where('priority_level', '>=', $threshold);

    /**
     * Get ROI
     */
    public function calculateRoi(?float $revenue = null): ?float
    {
        if (!$this->investment_budget || $this->investment_budget == 0) {
            return null;

        if ($revenue === null) {
            return null;

        return (($revenue - $this->investment_budget) / $this->investment_budget) * 100;
}
