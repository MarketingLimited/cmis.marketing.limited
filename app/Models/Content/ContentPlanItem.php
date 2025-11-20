<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContentPlanItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'cmis.content_items';
    protected $primaryKey = 'item_id';
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
        'item_id',
        'plan_id',
        'channel_id',
        'format_id',
        'scheduled_at',
        'title',
        'brief',
        'asset_id',
        'status',
        'context_id',
        'example_id',
        'creative_context_id',
        'provider',
        'org_id',
    ];

    protected $casts = [
        'brief' => 'array',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
