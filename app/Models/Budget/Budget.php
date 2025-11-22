<?php

namespace App\Models\Budget;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Budget extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.budgets';
    protected $primaryKey = 'budget_id';

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
