<?php

namespace App\Models\Metric;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Metric extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.metrics';
    protected $primaryKey = 'metric_id';
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
        'metric_id',
        'org_id',
        'campaign_id',
        'metric_type',
        'value',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
