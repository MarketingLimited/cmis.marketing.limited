<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ApiKey extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.api_keys';

    protected $primaryKey = 'key_id';

    public $timestamps = true;

    protected $fillable = [
        'key_id',
        'service_name',
        'service_code',
        'api_key_encrypted',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'key_id' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to get active API keys
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by service code
     */
    public function scopeByServiceCode($query, string $serviceCode): Builder
    {
        return $query->where('service_code', $serviceCode);
    }
}
