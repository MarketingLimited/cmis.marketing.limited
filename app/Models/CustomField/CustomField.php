<?php

namespace App\Models\CustomField;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class CustomField extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.custom_fields';
    protected $primaryKey = 'field_id';
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
        'field_id',
        'org_id',
        'name',
        'field_type',
        'default_value',
        'is_required',
        'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
