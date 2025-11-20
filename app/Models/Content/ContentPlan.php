<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ContentPlan extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.content_plans';
    protected $primaryKey = 'plan_id';
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
        'plan_id',
        'org_id',
        'campaign_id',
        'name',
        'timeframe_daterange',
        'strategy',
        'brief_id',
        'creative_context_id',
        'provider',
    ];

    protected $casts = [
        'strategy' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
