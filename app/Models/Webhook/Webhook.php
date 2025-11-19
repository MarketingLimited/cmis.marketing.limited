<?php

namespace App\Models\Webhook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.webhooks';
    protected $primaryKey = 'webhook_id';
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
        'webhook_id',
        'org_id',
        'url',
        'events',
        'secret',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'events' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];
}
