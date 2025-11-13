<?php

namespace App\Models\Market;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $table = 'cmis.markets';
    protected $primaryKey = 'market_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'market_code',
        'market_name',
        'country_code',
        'region',
        'language',
        'currency',
        'timezone',
        'market_size',
        'demographics',
        'economic_indicators',
        'cultural_notes',
        'regulatory_requirements',
        'competitive_landscape',
        'metadata',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'market_id' => 'string',
        'market_size' => 'integer',
        'demographics' => 'array',
        'economic_indicators' => 'array',
        'cultural_notes' => 'array',
        'regulatory_requirements' => 'array',
        'competitive_landscape' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get organizations in this market
     */
    public function organizations()
    {
        return $this->belongsToMany(
            \App\Models\Core\Org::class,
            'cmis.org_markets',
            'market_id',
            'org_id'
        )->withPivot([
            'entry_date',
            'market_share',
            'priority_level',
            'investment_budget',
            'is_primary_market',
            'status',
        ])->withTimestamps();
    }

    /**
     * Get org markets
     */
    public function orgMarkets()
    {
        return $this->hasMany(OrgMarket::class, 'market_id', 'market_id');
    }

    /**
     * Scope active markets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by region
     */
    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope by country
     */
    public function scopeByCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope by language
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Find by market code
     */
    public static function findByCode(string $code)
    {
        return self::where('market_code', $code)->first();
    }

    /**
     * Get demographic segment
     */
    public function getDemographic(string $segment)
    {
        return $this->demographics[$segment] ?? null;
    }

    /**
     * Get economic indicator
     */
    public function getEconomicIndicator(string $indicator)
    {
        return $this->economic_indicators[$indicator] ?? null;
    }
}
