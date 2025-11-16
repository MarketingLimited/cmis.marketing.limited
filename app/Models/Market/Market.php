<?php

namespace App\Models\Market;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Market extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.markets';
    protected $primaryKey = 'market_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'market_id',
        'market_name',
        'language_code',
        'currency_code',
        'text_direction',
    ];

    protected $casts = [
        'market_id' => 'string',
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
     * Scope by language
     */
    public function scopeByLanguage($query, string $languageCode)
    {
        return $query->where('language_code', $languageCode);
    }

    /**
     * Scope by currency
     */
    public function scopeByCurrency($query, string $currencyCode)
    {
        return $query->where('currency_code', $currencyCode);
    }

    /**
     * Find by market ID
     */
    public static function findByMarketId(string $marketId)
    {
        return self::where('market_id', $marketId)->first();
    }
}
