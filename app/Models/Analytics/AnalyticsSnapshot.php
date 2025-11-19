<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.analytics_snapshots';
    protected $primaryKey = 'snapshot_id';
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
        'snapshot_id',
        'org_id',
        'campaign_id',
        'metrics',
        'snapshot_date',
    ];

    protected $casts = [
        'metrics' => 'array',
        'snapshot_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
