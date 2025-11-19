<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Budget extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'cmis.budgets';
    protected $primaryKey = 'budget_id';
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
        'budget_id',
        'org_id',
        'campaign_id',
        'total_amount',
        'spent_amount',
        'currency',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
