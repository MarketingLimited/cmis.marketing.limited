<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Org extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $table = 'cmis.orgs';

    protected $primaryKey = 'org_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'default_locale',
        'currency',
    ];

    protected $casts = [
        'org_id' => 'string',
        'created_at' => 'datetime',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'org_id', 'org_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class, 'org_id', 'org_id');
    }

    public function creativeAssets(): HasMany
    {
        return $this->hasMany(CreativeAsset::class, 'org_id', 'org_id');
    }
}
