<?php

namespace App\Models\Audience;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AudienceSegment extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.audience_segments';
    protected $primaryKey = 'segment_id';
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
        'segment_id',
        'org_id',
        'name',
        'criteria',
        'size',
    ];

    protected $casts = [
        'criteria' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
