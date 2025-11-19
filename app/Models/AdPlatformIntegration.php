<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AdPlatformIntegration extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.ad_platform_integrations';
    protected $primaryKey = 'integration_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'integration_id', 'org_id', 'platform', 'credentials'
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
