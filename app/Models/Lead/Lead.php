<?php

namespace App\Models\Lead;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'cmis.leads';
    protected $primaryKey = 'lead_id';
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
        'lead_id',
        'org_id',
        'campaign_id',
        'name',
        'email',
        'phone',
        'source',
        'status',
        'score',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
